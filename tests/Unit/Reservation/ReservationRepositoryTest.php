```php
<?php

use App\Models\Guest\Guest;
use App\Models\Reservation\Reservation;
use App\Models\Room\Room;
use App\Repositories\Reservation\ReservationRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->repository = new ReservationRepository();
});

/*
|--------------------------------------------------------------------------
| CRUD Tests
|--------------------------------------------------------------------------
*/

test('repository can get all reservations', function () {
    Reservation::factory()->count(3)->create();

    $result = $this->repository->getAll();

    expect($result->total())->toBe(3);
});

test('repository can create a reservation', function () {
    $guest = Guest::factory()->create();
    $room = Room::factory()->create();

    $data = [
        'guest_id' => $guest->id,
        'room_id' => $room->id,
        'check_in' => '2026-09-10',
        'check_out' => '2026-09-13',
        'number_of_guests' => 2,
        'nightly_rate' => '150.00',
        'total_amount' => '450.00',
        'status' => 'pending',
    ];

    $result = $this->repository->create($data);

    expect($result)
        ->toBeInstanceOf(Reservation::class)
        ->and($result->guest_id)->toBe($guest->id)
        ->and($result->room_id)->toBe($room->id)
        ->and($result->nightly_rate)->toBe('150.00')
        ->and($result->total_amount)->toBe('450.00');

    $this->assertDatabaseHas('reservations', [
        'guest_id' => $guest->id,
        'room_id' => $room->id,
    ]);

    $reservation = Reservation::findOrFail($result->id);

    expect($reservation->check_in->format('Y-m-d'))->toBe('2026-09-10')
        ->and($reservation->check_out->format('Y-m-d'))->toBe('2026-09-13');
});

test('repository can find reservation by id', function () {
    $reservation = Reservation::factory()->create();

    $result = $this->repository->findById($reservation->id);

    expect($result)
        ->toBeInstanceOf(Reservation::class)
        ->and($result->id)->toBe($reservation->id);
});

test('repository can update a reservation', function () {
    $reservation = Reservation::factory()->create();

    $result = $this->repository->update($reservation->id, [
        'number_of_guests' => 3,
    ]);

    expect($result)
        ->toBeInstanceOf(Reservation::class)
        ->and($result->number_of_guests)->toBe(3);

    $this->assertDatabaseHas('reservations', [
        'id' => $reservation->id,
        'number_of_guests' => 3,
    ]);
});

test('repository can delete a reservation', function () {
    $reservation = Reservation::factory()->create();

    $this->repository->delete($reservation->id);

    $this->assertDatabaseMissing('reservations', [
        'id' => $reservation->id,
    ]);
});

/*
|--------------------------------------------------------------------------
| Relationship Tests
|--------------------------------------------------------------------------
*/

test('repository loads guest and room relationships', function () {
    $reservation = Reservation::factory()->create();

    $result = $this->repository->findById($reservation->id);

    expect($result->relationLoaded('guest'))->toBeTrue()
        ->and($result->relationLoaded('room'))->toBeTrue()
        ->and($result->room->relationLoaded('roomType'))->toBeTrue();
});

test('repository loads relationships when getting all reservations', function () {
    Reservation::factory()->create();

    $result = $this->repository->getAll();

    $reservation = $result->first();

    expect($reservation->relationLoaded('guest'))->toBeTrue()
        ->and($reservation->relationLoaded('room'))->toBeTrue()
        ->and($reservation->room->relationLoaded('roomType'))->toBeTrue();
});

/*
|--------------------------------------------------------------------------
| Filter Tests
|--------------------------------------------------------------------------
*/

test('repository can filter reservations by guest', function () {
    $guest1 = Guest::factory()->create();
    $guest2 = Guest::factory()->create();

    Reservation::factory()->create(['guest_id' => $guest1->id]);
    Reservation::factory()->create(['guest_id' => $guest2->id]);

    $this->getJson("/api/v1/reservations?filter[guest_id]={$guest1->id}");

    $result = $this->repository->getAll();

    expect($result->total())->toBe(1)
        ->and($result->first()->guest_id)->toBe($guest1->id);
});

test('repository can filter reservations by room', function () {
    $room1 = Room::factory()->create();
    $room2 = Room::factory()->create();

    Reservation::factory()->create(['room_id' => $room1->id]);
    Reservation::factory()->create(['room_id' => $room2->id]);

    $this->getJson("/api/v1/reservations?filter[room_id]={$room1->id}");

    $result = $this->repository->getAll();

    expect($result->total())->toBe(1)
        ->and($result->first()->room_id)->toBe($room1->id);
});

test('repository can filter reservations by status', function () {
    Reservation::factory()->create(['status' => 'pending']);
    Reservation::factory()->create(['status' => 'confirmed']);

    $this->getJson('/api/v1/reservations?filter[status]=pending');

    $result = $this->repository->getAll();

    expect($result->total())->toBe(1)
        ->and($result->first()->status)->toBe('pending');
});

test('repository can filter reservations by number of guests', function () {
    Reservation::factory()->create(['number_of_guests' => 2]);
    Reservation::factory()->create(['number_of_guests' => 4]);

    $this->getJson('/api/v1/reservations?filter[number_of_guests]=2');

    $result = $this->repository->getAll();

    expect($result->total())->toBe(1)
        ->and($result->first()->number_of_guests)->toBe(2);
});

test('repository can filter reservations by check in from date', function () {
    Reservation::factory()->create([
        'check_in' => '2026-09-10',
        'check_out' => '2026-09-12',
    ]);

    Reservation::factory()->create([
        'check_in' => '2026-09-20',
        'check_out' => '2026-09-22',
    ]);

    $this->getJson(
        '/api/v1/reservations?filter[check_in_from]=2026-09-15'
    );

    $result = $this->repository->getAll();

    expect($result->total())->toBe(1)
        ->and($result->first()->check_in->format('Y-m-d'))
        ->toBe('2026-09-20');
});

test('repository can filter reservations by check in to date', function () {
    Reservation::factory()->create([
        'check_in' => '2026-09-10',
        'check_out' => '2026-09-12',
    ]);

    Reservation::factory()->create([
        'check_in' => '2026-09-20',
        'check_out' => '2026-09-22',
    ]);

    $this->getJson(
        '/api/v1/reservations?filter[check_in_to]=2026-09-15'
    );

    $result = $this->repository->getAll();

    expect($result->total())->toBe(1)
        ->and($result->first()->check_in->format('Y-m-d'))
        ->toBe('2026-09-10');
});

test('repository can filter reservations by check out from date', function () {
    Reservation::factory()->create([
        'check_in' => '2026-09-10',
        'check_out' => '2026-09-12',
    ]);

    Reservation::factory()->create([
        'check_in' => '2026-09-20',
        'check_out' => '2026-09-22',
    ]);

    $this->getJson(
        '/api/v1/reservations?filter[check_out_from]=2026-09-15'
    );

    $result = $this->repository->getAll();

    expect($result->total())->toBe(1)
        ->and($result->first()->check_out->format('Y-m-d'))
        ->toBe('2026-09-22');
});

test('repository can filter reservations by check out to date', function () {
    Reservation::factory()->create([
        'check_in' => '2026-09-10',
        'check_out' => '2026-09-12',
    ]);

    Reservation::factory()->create([
        'check_in' => '2026-09-20',
        'check_out' => '2026-09-22',
    ]);

    $this->getJson(
        '/api/v1/reservations?filter[check_out_to]=2026-09-15'
    );

    $result = $this->repository->getAll();

    expect($result->total())->toBe(1)
        ->and($result->first()->check_out->format('Y-m-d'))
        ->toBe('2026-09-12');
});

/*
|--------------------------------------------------------------------------
| Sorting & Pagination Tests
|--------------------------------------------------------------------------
*/

test('repository can sort reservations by check in', function () {
    Reservation::factory()->create([
        'check_in' => '2026-09-20',
        'check_out' => '2026-09-22',
    ]);

    Reservation::factory()->create([
        'check_in' => '2026-09-10',
        'check_out' => '2026-09-12',
    ]);

    $this->getJson('/api/v1/reservations?sort=check_in');

    $result = $this->repository->getAll();

    expect($result->first()->check_in->format('Y-m-d'))
        ->toBe('2026-09-10');
});

test('repository can sort reservations by check in descending', function () {
    Reservation::factory()->create([
        'check_in' => '2026-09-10',
        'check_out' => '2026-09-12',
    ]);

    Reservation::factory()->create([
        'check_in' => '2026-09-20',
        'check_out' => '2026-09-22',
    ]);

    $this->getJson('/api/v1/reservations?sort=-check_in');

    $result = $this->repository->getAll();

    expect($result->first()->check_in->format('Y-m-d'))
        ->toBe('2026-09-20');
});

test('repository can paginate reservations', function () {
    Reservation::factory()->count(15)->create();

    $this->getJson('/api/v1/reservations?per_page=5');

    $result = $this->repository->getAll();

    expect($result->total())->toBe(15)
        ->and($result->perPage())->toBe(5)
        ->and($result->count())->toBe(5);
});

/*
|--------------------------------------------------------------------------
| Reservation Availability Tests
|--------------------------------------------------------------------------
*/

test('repository detects overlapping reservation', function () {
    $room = Room::factory()->create();

    Reservation::factory()->create([
        'room_id' => $room->id,
        'check_in' => '2026-09-10',
        'check_out' => '2026-09-15',
    ]);

    $result = $this->repository->hasOverlappingReservation(
        $room->id,
        '2026-09-12',
        '2026-09-18'
    );

    expect($result)->toBeTrue();
});

test('repository detects reservation containing requested period', function () {
    $room = Room::factory()->create();

    Reservation::factory()->create([
        'room_id' => $room->id,
        'check_in' => '2026-09-10',
        'check_out' => '2026-09-20',
    ]);

    $result = $this->repository->hasOverlappingReservation(
        $room->id,
        '2026-09-12',
        '2026-09-15'
    );

    expect($result)->toBeTrue();
});

test('repository detects requested period containing existing reservation', function () {
    $room = Room::factory()->create();

    Reservation::factory()->create([
        'room_id' => $room->id,
        'check_in' => '2026-09-12',
        'check_out' => '2026-09-15',
    ]);

    $result = $this->repository->hasOverlappingReservation(
        $room->id,
        '2026-09-10',
        '2026-09-20'
    );

    expect($result)->toBeTrue();
});

test('repository allows back to back reservations', function () {
    $room = Room::factory()->create();

    Reservation::factory()->create([
        'room_id' => $room->id,
        'check_in' => '2026-09-10',
        'check_out' => '2026-09-15',
    ]);

    $result = $this->repository->hasOverlappingReservation(
        $room->id,
        '2026-09-15',
        '2026-09-20'
    );

    expect($result)->toBeFalse();
});

test('repository does not detect reservation on another room as overlapping', function () {
    $room1 = Room::factory()->create();
    $room2 = Room::factory()->create();

    Reservation::factory()->create([
        'room_id' => $room1->id,
        'check_in' => '2026-09-10',
        'check_out' => '2026-09-15',
    ]);

    $result = $this->repository->hasOverlappingReservation(
        $room2->id,
        '2026-09-12',
        '2026-09-18'
    );

    expect($result)->toBeFalse();
});

test('repository can exclude a reservation from overlap detection', function () {
    $reservation = Reservation::factory()->create([
        'check_in' => '2026-09-10',
        'check_out' => '2026-09-15',
    ]);

    $result = $this->repository->hasOverlappingReservation(
        $reservation->room_id,
        '2026-09-10',
        '2026-09-15',
        $reservation->id
    );

    expect($result)->toBeFalse();
});
