<?php

use App\Models\Contract\Operation;
use App\Models\Folio\Folio;
use App\Models\Guest\Guest;
use App\Models\Reservation\Reservation;
use App\Models\Room\Room;
use App\Repositories\Contracts\OperationInterface;
use App\Repositories\Folio\FolioRepositoryInterface;
use App\Repositories\Folio\FolioChargeRepositoryInterface;
use App\Repositories\Room\RoomRepositoryInterface;
use App\Repositories\Reservation\ReservationInterface;
use App\Services\FolioService;
use App\Services\GuestService;
use App\Services\OperationService;
use App\Services\ReservationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->guestService = Mockery::mock(GuestService::class);
    $this->reservationService = Mockery::mock(ReservationService::class);
    $this->roomRepository = Mockery::mock(RoomRepositoryInterface::class);
    $this->operationRepository = Mockery::mock(OperationInterface::class);
    $this->folioRepository = Mockery::mock(FolioRepositoryInterface::class);
    $this->folioChargeRepository = Mockery::mock(FolioChargeRepositoryInterface::class);
    $this->reservationRepository = Mockery::mock(ReservationInterface::class);

    $this->folioService = new FolioService(
        $this->folioRepository,
        $this->folioChargeRepository,
        $this->reservationRepository
    );

    $this->service = new OperationService(
        $this->guestService,
        $this->reservationService,
        $this->folioService,
        $this->roomRepository,
        $this->operationRepository
    );
});

afterEach(function () {
    Mockery::close();
});

/*
|--------------------------------------------------------------------------
| Check-In Tests
|--------------------------------------------------------------------------
*/

test('service can check in a pending reservation', function () {
    $reservation = Reservation::factory()->make([
        'id' => 1,
        'room_id' => 10,
        'status' => 'pending',
        'check_in' => '2026-09-13',
        'check_out' => '2026-09-15',
        'nightly_rate' => 150,
        'total_amount' => 300,
    ]);

    $room = Room::factory()->make([
        'id' => 10,
        'status' => 'reserved',
    ]);

    $folio = new Folio([
        'reservation_id' => 1,
        'status' => 'open',
        'opened_at' => now(),
    ]);
    $folio->id = 1;

    $operation = Operation::factory()->make([
        'reservation_id' => 1,
        'type' => 'check_in',
        'performed_by' => 5,
    ]);

    $this->reservationService
        ->shouldReceive('findById')
        ->once()
        ->with(1)
        ->andReturn($reservation);

    $this->roomRepository
        ->shouldReceive('findAndLock')
        ->once()
        ->with(10)
        ->andReturn($room);

    $this->roomRepository
        ->shouldReceive('update')
        ->once()
        ->with(10, [
            'status' => 'occupied',
        ])
        ->andReturn($room);

    $this->folioRepository
        ->shouldReceive('findByReservation')
        ->once()
        ->with(1)
        ->andReturn(null);

    $this->folioRepository
        ->shouldReceive('create')
        ->once()
        ->with(Mockery::on(function ($data) {
            return $data['reservation_id'] === 1
                && $data['status'] === 'open'
                && $data['opened_at'] instanceof \Illuminate\Support\Carbon;
        }))
        ->andReturn($folio);

    $this->reservationRepository
        ->shouldReceive('findById')
        ->once()
        ->with(1)
        ->andReturn($reservation);

    $this->folioChargeRepository
        ->shouldReceive('create')
        ->once()
        ->with(Mockery::on(function ($data) use ($folio, $reservation) {
            return $data['folio_id'] === $folio->id
                && $data['service_id'] === null
                && $data['type'] === 'accommodation'
                && $data['description'] === 'Room accommodation'
                && $data['quantity'] == 2
                && $data['unit_price'] == $reservation->nightly_rate
                && $data['amount'] == $reservation->total_amount;
        }));

    $this->operationRepository
        ->shouldReceive('create')
        ->once()
        ->with(Mockery::on(function ($data) {
            return $data['reservation_id'] === 1
                && $data['type'] === 'check_in'
                && $data['performed_by'] === 5
                && $data['notes'] === 'Guest arrived.';
        }))
        ->andReturn($operation);

    $result = $this->service->checkIn(
        reservationId: 1,
        performedBy: 5,
        notes: 'Guest arrived.'
    );

    expect($result)->toBe($operation);
});

test('service rejects check in when reservation is not pending', function () {
    $reservation = Reservation::factory()->make([
        'id' => 1,
        'room_id' => 10,
        'status' => 'checked_in',
    ]);

    $room = Room::factory()->make([
        'id' => 10,
        'status' => 'reserved',
    ]);

    $this->reservationService
        ->shouldReceive('findById')
        ->once()
        ->with(1)
        ->andReturn($reservation);

    $this->roomRepository
        ->shouldReceive('findAndLock')
        ->once()
        ->with(10)
        ->andReturn($room);

    $this->folioRepository
        ->shouldNotReceive('findByReservation');

    $this->folioRepository
        ->shouldNotReceive('create');

    $this->folioChargeRepository
        ->shouldNotReceive('create');

    expect(fn() => $this->service->checkIn(
        reservationId: 1,
        performedBy: 5
    ))->toThrow(ValidationException::class);
});

test('service rejects check in when room is not reserved', function () {
    $reservation = Reservation::factory()->make([
        'id' => 1,
        'room_id' => 10,
        'status' => 'pending',
    ]);

    $room = Room::factory()->make([
        'id' => 10,
        'status' => 'available',
    ]);

    $this->reservationService
        ->shouldReceive('findById')
        ->once()
        ->with(1)
        ->andReturn($reservation);

    $this->roomRepository
        ->shouldReceive('findAndLock')
        ->once()
        ->with(10)
        ->andReturn($room);

    $this->folioRepository
        ->shouldNotReceive('findByReservation');

    $this->folioRepository
        ->shouldNotReceive('create');

    $this->folioChargeRepository
        ->shouldNotReceive('create');

    expect(fn() => $this->service->checkIn(
        reservationId: 1,
        performedBy: 5
    ))->toThrow(ValidationException::class);
});

/*
|--------------------------------------------------------------------------
| Walk-In Tests
|--------------------------------------------------------------------------
*/

test('service can complete a walk in for a new guest', function () {
    $guest = Guest::factory()->make([
        'id' => 1,
        'id_number' => '12345678',
    ]);

    $room = Room::factory()->make([
        'id' => 10,
        'status' => 'available',
    ]);

    $reservation = Reservation::factory()->make([
        'id' => 20,
        'guest_id' => 1,
        'room_id' => 10,
        'status' => 'pending',
        'check_in' => '2026-09-13',
        'check_out' => '2026-09-15',
        'nightly_rate' => 150,
        'total_amount' => 300,
    ]);

    $folio = new Folio([
        'reservation_id' => 20,
        'status' => 'open',
        'opened_at' => now(),
    ]);
    $folio->id = 1;

    $operation = Operation::factory()->make([
        'id' => 30,
        'reservation_id' => 20,
        'type' => 'check_in',
        'performed_by' => 5,
    ]);

    $guestData = [
        'first_name' => 'John',
        'last_name' => 'Kamau',
        'id_number' => '12345678',
        'phone_number' => '0712345678',
        'email' => 'john@test.com',
        'country' => 'Kenya',
        'city' => 'Nairobi',
        'address' => 'Westlands',
    ];

    $this->guestService
        ->shouldReceive('findByIdNumber')
        ->once()
        ->with('12345678')
        ->andReturn(null);

    $this->guestService
        ->shouldReceive('create')
        ->once()
        ->with($guestData)
        ->andReturn($guest);

    $this->roomRepository
        ->shouldReceive('findAndLock')
        ->once()
        ->with(10)
        ->andReturn($room);

    $this->reservationService
        ->shouldReceive('createWithoutTransaction')
        ->once()
        ->with([
            'guest_id' => 1,
            'room_id' => 10,
            'check_in' => '2026-09-13',
            'check_out' => '2026-09-15',
            'number_of_guests' => 2,
        ])
        ->andReturn($reservation);

    $this->roomRepository
        ->shouldReceive('update')
        ->once()
        ->with(10, [
            'status' => 'occupied',
        ])
        ->andReturn($room);

    $this->folioRepository
        ->shouldReceive('findByReservation')
        ->once()
        ->with(20)
        ->andReturn(null);

    $this->folioRepository
        ->shouldReceive('create')
        ->once()
        ->with(Mockery::on(function ($data) {
            return $data['reservation_id'] === 20
                && $data['status'] === 'open'
                && $data['opened_at'] instanceof \Illuminate\Support\Carbon;
        }))
        ->andReturn($folio);

    $this->reservationRepository
        ->shouldReceive('findById')
        ->once()
        ->with(20)
        ->andReturn($reservation);

    $this->folioChargeRepository
        ->shouldReceive('create')
        ->once()
        ->with(Mockery::on(function ($data) use ($folio, $reservation) {
            return $data['folio_id'] === $folio->id
                && $data['service_id'] === null
                && $data['type'] === 'accommodation'
                && $data['description'] === 'Room accommodation'
                && $data['quantity'] == 2
                && $data['unit_price'] == $reservation->nightly_rate
                && $data['amount'] == $reservation->total_amount;
        }));

    $this->operationRepository
        ->shouldReceive('create')
        ->once()
        ->with(Mockery::on(function ($data) {
            return $data['reservation_id'] === 20
                && $data['type'] === 'check_in'
                && $data['performed_by'] === 5
                && $data['notes'] === 'Walk-in guest.';
        }))
        ->andReturn($operation);

    $result = $this->service->walkIn(
        guestData: $guestData,
        roomId: 10,
        checkIn: '2026-09-13',
        checkOut: '2026-09-15',
        numberOfGuests: 2,
        performedBy: 5,
        notes: 'Walk-in guest.'
    );

    expect($result)->toBe($operation);
});

test('service reuses an existing guest during walk in', function () {
    $guest = Guest::factory()->make([
        'id' => 1,
        'id_number' => '12345678',
    ]);

    $room = Room::factory()->make([
        'id' => 10,
        'status' => 'available',
    ]);

    $reservation = Reservation::factory()->make([
        'id' => 20,
        'guest_id' => 1,
        'room_id' => 10,
        'status' => 'pending',
        'check_in' => '2026-09-13',
        'check_out' => '2026-09-15',
        'nightly_rate' => 150,
        'total_amount' => 300,
    ]);

    $folio = new Folio([
        'reservation_id' => 20,
        'status' => 'open',
        'opened_at' => now(),
    ]);
    $folio->id = 1;

    $operation = Operation::factory()->make([
        'reservation_id' => 20,
        'type' => 'check_in',
        'performed_by' => 5,
    ]);

    $guestData = [
        'first_name' => 'John',
        'last_name' => 'Kamau',
        'id_number' => '12345678',
        'phone_number' => '0712345678',
        'email' => null,
        'country' => 'Kenya',
        'city' => 'Nairobi',
        'address' => null,
    ];

    $this->guestService
        ->shouldReceive('findByIdNumber')
        ->once()
        ->with('12345678')
        ->andReturn($guest);

    $this->guestService
        ->shouldNotReceive('create');

    $this->roomRepository
        ->shouldReceive('findAndLock')
        ->once()
        ->with(10)
        ->andReturn($room);

    $this->reservationService
        ->shouldReceive('createWithoutTransaction')
        ->once()
        ->andReturn($reservation);

    $this->roomRepository
        ->shouldReceive('update')
        ->once()
        ->with(10, [
            'status' => 'occupied',
        ])
        ->andReturn($room);

    $this->folioRepository
        ->shouldReceive('findByReservation')
        ->once()
        ->with(20)
        ->andReturn(null);

    $this->folioRepository
        ->shouldReceive('create')
        ->once()
        ->with(Mockery::on(function ($data) {
            return $data['reservation_id'] === 20
                && $data['status'] === 'open'
                && $data['opened_at'] instanceof \Illuminate\Support\Carbon;
        }))
        ->andReturn($folio);

    $this->reservationRepository
        ->shouldReceive('findById')
        ->once()
        ->with(20)
        ->andReturn($reservation);

    $this->folioChargeRepository
        ->shouldReceive('create')
        ->once()
        ->with(Mockery::on(function ($data) use ($folio, $reservation) {
            return $data['folio_id'] === $folio->id
                && $data['service_id'] === null
                && $data['type'] === 'accommodation'
                && $data['description'] === 'Room accommodation'
                && $data['quantity'] == 2
                && $data['unit_price'] == $reservation->nightly_rate
                && $data['amount'] == $reservation->total_amount;
        }));

    $this->operationRepository
        ->shouldReceive('create')
        ->once()
        ->andReturn($operation);

    $result = $this->service->walkIn(
        guestData: $guestData,
        roomId: 10,
        checkIn: '2026-09-13',
        checkOut: '2026-09-15',
        numberOfGuests: 2,
        performedBy: 5
    );

    expect($result)->toBe($operation);
});

test('service rejects walk in when room is not available', function () {
    $room = Room::factory()->make([
        'id' => 10,
        'status' => 'occupied',
    ]);

    $this->guestService
        ->shouldReceive('findByIdNumber')
        ->once()
        ->andReturn(null);

    $this->guestService
        ->shouldReceive('create')
        ->once()
        ->andReturn(
            Guest::factory()->make([
                'id' => 1,
            ])
        );

    $this->roomRepository
        ->shouldReceive('findAndLock')
        ->once()
        ->with(10)
        ->andReturn($room);

    $this->reservationService
        ->shouldNotReceive('createWithoutTransaction');

    $this->folioRepository
        ->shouldNotReceive('findByReservation');

    $this->folioRepository
        ->shouldNotReceive('create');

    $this->folioChargeRepository
        ->shouldNotReceive('create');

    expect(fn() => $this->service->walkIn(
        guestData: [
            'first_name' => 'John',
            'last_name' => 'Kamau',
            'id_number' => '12345678',
            'phone_number' => '0712345678',
            'country' => 'Kenya',
            'city' => 'Nairobi',
        ],
        roomId: 10,
        checkIn: '2026-09-13',
        checkOut: '2026-09-15',
        numberOfGuests: 2,
        performedBy: 5
    ))->toThrow(ValidationException::class);
});

/*
|--------------------------------------------------------------------------
| Check-Out Tests
|--------------------------------------------------------------------------
*/

test('service can check out a checked in reservation', function () {
    $reservation = Reservation::factory()->make([
        'id' => 1,
        'room_id' => 10,
        'status' => 'checked_in',
    ]);

    $room = Room::factory()->make([
        'id' => 10,
        'status' => 'occupied',
    ]);

    $operation = Operation::factory()->make([
        'reservation_id' => 1,
        'type' => 'check_out',
        'performed_by' => 5,
    ]);

    $this->reservationService
        ->shouldReceive('findById')
        ->once()
        ->with(1)
        ->andReturn($reservation);

    $this->roomRepository
        ->shouldReceive('findAndLock')
        ->once()
        ->with(10)
        ->andReturn($room);

    $this->roomRepository
        ->shouldReceive('update')
        ->once()
        ->with(10, [
            'status' => 'available',
        ])
        ->andReturn($room);

    $this->operationRepository
        ->shouldReceive('create')
        ->once()
        ->with(Mockery::on(function ($data) {
            return $data['reservation_id'] === 1
                && $data['type'] === 'check_out'
                && $data['performed_by'] === 5;
        }))
        ->andReturn($operation);

    $result = $this->service->checkOut(
        reservationId: 1,
        performedBy: 5,
        notes: 'Guest departed.'
    );

    expect($result)->toBe($operation);
});

test('service rejects check out when reservation is not checked in', function () {
    $reservation = Reservation::factory()->make([
        'id' => 1,
        'room_id' => 10,
        'status' => 'pending',
    ]);

    $room = Room::factory()->make([
        'id' => 10,
        'status' => 'occupied',
    ]);

    $this->reservationService
        ->shouldReceive('findById')
        ->once()
        ->with(1)
        ->andReturn($reservation);

    $this->roomRepository
        ->shouldReceive('findAndLock')
        ->once()
        ->with(10)
        ->andReturn($room);

    expect(fn() => $this->service->checkOut(
        reservationId: 1,
        performedBy: 5
    ))->toThrow(ValidationException::class);
});

test('service rejects check out when room is not occupied', function () {
    $reservation = Reservation::factory()->make([
        'id' => 1,
        'room_id' => 10,
        'status' => 'checked_in',
    ]);

    $room = Room::factory()->make([
        'id' => 10,
        'status' => 'available',
    ]);

    $this->reservationService
        ->shouldReceive('findById')
        ->once()
        ->with(1)
        ->andReturn($reservation);

    $this->roomRepository
        ->shouldReceive('findAndLock')
        ->once()
        ->with(10)
        ->andReturn($room);

    expect(fn() => $this->service->checkOut(
        reservationId: 1,
        performedBy: 5
    ))->toThrow(ValidationException::class);
});
