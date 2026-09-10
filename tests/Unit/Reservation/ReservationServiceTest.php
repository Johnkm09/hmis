<?php

use App\Models\Guest\Guest;
use App\Models\Reservation\Reservation;
use App\Models\Room\Room;
use App\Models\RoomType\RoomType;
use App\Repositories\Reservation\ReservationInterface;
use App\Services\ReservationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->repository = Mockery::mock(ReservationInterface::class);

    $this->service = new ReservationService($this->repository);
});

afterEach(function () {
    Mockery::close();
});

/*
|--------------------------------------------------------------------------
| Basic Service Tests
|--------------------------------------------------------------------------
*/

test('service can get all reservations', function () {

    $reservations = collect([
        Reservation::factory()->make(),
        Reservation::factory()->make(),
    ]);

    $this->repository
        ->shouldReceive('getAll')
        ->once()
        ->andReturn($reservations);

    $result = $this->service->getAll();

    expect($result)->toBe($reservations);
});


test('service can find reservation by id', function () {

    $reservation = Reservation::factory()->make([
        'id' => 1,
    ]);

    $this->repository
        ->shouldReceive('findById')
        ->once()
        ->with(1)
        ->andReturn($reservation);

    $result = $this->service->findById(1);

    expect($result)->toBe($reservation);
});


test('service can delete reservation', function () {

    $this->repository
        ->shouldReceive('delete')
        ->once()
        ->with(1);

    $result = $this->service->delete(1);

    expect($result)->toBeNull();
});


/*
|--------------------------------------------------------------------------
| Reservation Creation Business Rules
|--------------------------------------------------------------------------
*/

test('service can create a reservation', function () {

    $guest = Guest::factory()->create();

    $room = Room::factory()->create([
        'price' => '150.00',
        'is_active' => true,
    ]);

    $data = [
        'guest_id' => $guest->id,
        'room_id' => $room->id,
        'check_in' => '2026-09-10',
        'check_out' => '2026-09-13',
        'number_of_guests' => 2,
    ];

    $reservation = Reservation::factory()->make([
        ...$data,
        'nightly_rate' => '150.00',
        'total_amount' => '450.00',
        'status' => 'pending',
    ]);

    $this->repository
        ->shouldReceive('hasOverlappingReservation')
        ->once()
        ->with(
            $room->id,
            '2026-09-10',
            '2026-09-13'
        )
        ->andReturnFalse();

    $this->repository
        ->shouldReceive('create')
        ->once()
        ->with([
            ...$data,
            'nightly_rate' => '150.00',
            'total_amount' => 450.00,
        ])
        ->andReturn($reservation);

    $result = $this->service->create($data);

    expect($result)->toBe($reservation);
});


test('service calculates total amount using number of nights and room price', function () {

    $guest = Guest::factory()->create();

    $roomType = RoomType::factory()->create([
        'max_occupancy' => 2,
    ]);

    $room = Room::factory()->create([
        'room_type_id' => $roomType->id,
        'price' => '200.00',
        'is_active' => true,
    ]);

    $data = [
        'guest_id' => $guest->id,
        'room_id' => $room->id,
        'check_in' => '2026-09-10',
        'check_out' => '2026-09-15',
        'number_of_guests' => 2,
    ];

    $reservation = Reservation::factory()->make([
        'guest_id' => $guest->id,
        'room_id' => $room->id,
        'nightly_rate' => '200.00',
        'total_amount' => '1000.00',
    ]);

    $this->repository
        ->shouldReceive('hasOverlappingReservation')
        ->once()
        ->andReturnFalse();

    $this->repository
        ->shouldReceive('create')
        ->once()
        ->with([
            ...$data,
            'nightly_rate' => '200.00',
            'total_amount' => 1000.0,
        ])
        ->andReturn($reservation);

    $result = $this->service->create($data);

    expect($result->total_amount)->toBe('1000.00');
});


test('service stores current room price as reservation nightly rate', function () {

    $guest = Guest::factory()->create();

    $room = Room::factory()->create([
        'price' => '175.50',
        'is_active' => true,
    ]);

    $data = [
        'guest_id' => $guest->id,
        'room_id' => $room->id,
        'check_in' => '2026-09-10',
        'check_out' => '2026-09-12',
        'number_of_guests' => 1,
    ];

    $reservation = Reservation::factory()->make([
        'nightly_rate' => '175.50',
        'total_amount' => '351.00',
    ]);

    $this->repository
        ->shouldReceive('hasOverlappingReservation')
        ->once()
        ->andReturnFalse();

    $this->repository
        ->shouldReceive('create')
        ->once()
        ->with([
            ...$data,
            'nightly_rate' => '175.50',
            'total_amount' => 351.0,
        ])
        ->andReturn($reservation);

    $result = $this->service->create($data);

    expect($result->nightly_rate)->toBe('175.50');
});


test('service rejects reservation for inactive room', function () {

    $guest = Guest::factory()->create();

    $room = Room::factory()->create([
        'is_active' => false,
    ]);

    $data = [
        'guest_id' => $guest->id,
        'room_id' => $room->id,
        'check_in' => '2026-09-10',
        'check_out' => '2026-09-13',
        'number_of_guests' => 2,
    ];

    expect(fn() => $this->service->create($data))
        ->toThrow(ValidationException::class);
});


test('service rejects reservation when guest count exceeds room occupancy', function () {

    $guest = Guest::factory()->create();

    $room = Room::factory()->create([
        'is_active' => true,
    ]);

    $room->roomType->update([
        'max_occupancy' => 2,
    ]);

    $data = [
        'guest_id' => $guest->id,
        'room_id' => $room->id,
        'check_in' => '2026-09-10',
        'check_out' => '2026-09-13',
        'number_of_guests' => 3,
    ];

    expect(fn() => $this->service->create($data))
        ->toThrow(ValidationException::class);
});


test('service rejects overlapping reservation', function () {

    $guest = Guest::factory()->create();

    $room = Room::factory()->create([
        'is_active' => true,
    ]);

    $data = [
        'guest_id' => $guest->id,
        'room_id' => $room->id,
        'check_in' => '2026-09-12',
        'check_out' => '2026-09-15',
        'number_of_guests' => 1,
    ];

    $this->repository
        ->shouldReceive('hasOverlappingReservation')
        ->once()
        ->andReturnTrue();

    expect(fn() => $this->service->create($data))
        ->toThrow(ValidationException::class);
});


test('service allows back to back reservation', function () {

    $guest = Guest::factory()->create();

    $room = Room::factory()->create([
        'is_active' => true,
    ]);

    $data = [
        'guest_id' => $guest->id,
        'room_id' => $room->id,
        'check_in' => '2026-09-15',
        'check_out' => '2026-09-20',
        'number_of_guests' => 1,
    ];

    $reservation = Reservation::factory()->make([
        'guest_id' => $guest->id,
        'room_id' => $room->id,
        'nightly_rate' => $room->price,
        'total_amount' => ((float) $room->price * 5),
    ]);

    $this->repository
        ->shouldReceive('hasOverlappingReservation')
        ->once()
        ->andReturnFalse();

    $this->repository
        ->shouldReceive('create')
        ->once()
        ->andReturn($reservation);

    $result = $this->service->create($data);

    expect($result)->toBe($reservation);
});


/*
|--------------------------------------------------------------------------
| Reservation Update Business Rules
|--------------------------------------------------------------------------
*/

test('service can update a reservation', function () {

    $guest = Guest::factory()->create();

    $room = Room::factory()->create([
        'price' => '150.00',
        'is_active' => true,
    ]);

    $reservation = Reservation::factory()->create([
        'guest_id' => $guest->id,
        'room_id' => $room->id,
        'check_in' => '2026-09-10',
        'check_out' => '2026-09-13',
        'number_of_guests' => 2,
        'nightly_rate' => '150.00',
        'total_amount' => '450.00',
    ]);

    $updatedReservation = Reservation::factory()->make([
        'id' => $reservation->id,
        'guest_id' => $guest->id,
        'room_id' => $room->id,
        'check_in' => '2026-09-10',
        'check_out' => '2026-09-15',
        'number_of_guests' => 2,
        'nightly_rate' => '150.00',
        'total_amount' => '750.00',
    ]);

    $this->repository
        ->shouldReceive('findById')
        ->once()
        ->with($reservation->id)
        ->andReturn($reservation);

    $this->repository
        ->shouldReceive('hasOverlappingReservation')
        ->once()
        ->andReturnFalse();

    $this->repository
        ->shouldReceive('update')
        ->once()
        ->with(
            $reservation->id,
            [
                'check_out' => '2026-09-15',
                'nightly_rate' => '150.00',
                'total_amount' => 750.0,
            ]
        )
        ->andReturn($updatedReservation);

    $result = $this->service->update(
        $reservation->id,
        [
            'check_out' => '2026-09-15',
        ]
    );

    expect($result)->toBe($updatedReservation);
});


test('service rejects update when new dates overlap another reservation', function () {

    $guest = Guest::factory()->create();

    $room = Room::factory()->create([
        'is_active' => true,
    ]);

    $reservation = Reservation::factory()->create([
        'guest_id' => $guest->id,
        'room_id' => $room->id,
        'check_in' => '2026-09-10',
        'check_out' => '2026-09-13',
    ]);

    $this->repository
        ->shouldReceive('findById')
        ->once()
        ->with($reservation->id)
        ->andReturn($reservation);

    $this->repository
        ->shouldReceive('hasOverlappingReservation')
        ->once()
        ->with(
            $room->id,
            '2026-09-10',
            '2026-09-20',
            $reservation->id
        )
        ->andReturnTrue();

    expect(fn() => $this->service->update(
        $reservation->id,
        [
            'check_out' => '2026-09-20',
        ]
    ))->toThrow(ValidationException::class);
});


test('service recalculates total when reservation dates change', function () {

    $guest = Guest::factory()->create();

    $room = Room::factory()->create([
        'price' => '100.00',
        'is_active' => true,
    ]);

    $reservation = Reservation::factory()->create([
        'guest_id' => $guest->id,
        'room_id' => $room->id,
        'check_in' => '2026-09-10',
        'check_out' => '2026-09-12',
        'number_of_guests' => 1,
        'nightly_rate' => '100.00',
        'total_amount' => '200.00',
    ]);

    $updatedReservation = Reservation::factory()->make([
        'id' => $reservation->id,
        'total_amount' => '500.00',
        'nightly_rate' => '100.00',
    ]);

    $this->repository
        ->shouldReceive('findById')
        ->once()
        ->andReturn($reservation);

    $this->repository
        ->shouldReceive('hasOverlappingReservation')
        ->once()
        ->andReturnFalse();

    $this->repository
        ->shouldReceive('update')
        ->once()
        ->with(
            $reservation->id,
            [
                'check_out' => '2026-09-15',
                'nightly_rate' => '100.00',
                'total_amount' => 500.0,
            ]
        )
        ->andReturn($updatedReservation);

    $result = $this->service->update(
        $reservation->id,
        [
            'check_out' => '2026-09-15',
        ]
    );

    expect($result->total_amount)->toBe('500.00');
});


test('service rejects update for inactive room', function () {

    $guest = Guest::factory()->create();

    $room = Room::factory()->create([
        'is_active' => false,
    ]);

    $reservation = Reservation::factory()->create([
        'guest_id' => $guest->id,
        'room_id' => $room->id,
    ]);

    $this->repository
        ->shouldReceive('findById')
        ->once()
        ->with($reservation->id)
        ->andReturn($reservation);

    expect(fn() => $this->service->update(
        $reservation->id,
        []
    ))->toThrow(ValidationException::class);
});


test('service rejects update when guest count exceeds room occupancy', function () {

    $guest = Guest::factory()->create();

    $room = Room::factory()->create([
        'is_active' => true,
    ]);

    $room->roomType->update([
        'max_occupancy' => 2,
    ]);

    $reservation = Reservation::factory()->create([
        'guest_id' => $guest->id,
        'room_id' => $room->id,
        'number_of_guests' => 1,
    ]);

    $this->repository
        ->shouldReceive('findById')
        ->once()
        ->with($reservation->id)
        ->andReturn($reservation);

    expect(fn() => $this->service->update(
        $reservation->id,
        [
            'number_of_guests' => 3,
        ]
    ))->toThrow(ValidationException::class);
});
