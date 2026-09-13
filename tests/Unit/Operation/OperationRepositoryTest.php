<?php

use App\Models\Contract\Operation;
use App\Models\User;
use App\Models\Reservation\Reservation;
use App\Repositories\Contracts\OperationRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->repository = new OperationRepository();
});

/*
|--------------------------------------------------------------------------
| Operation Tests
|--------------------------------------------------------------------------
*/

test('repository can create an operation', function () {
    $user = User::factory()->create();
    $reservation = Reservation::factory()->create();

    $data = [
        'reservation_id' => $reservation->id,
        'type' => 'check_in',
        'performed_at' => now(),
        'performed_by' => $user->id,
        'notes' => 'Guest checked in successfully.',
    ];

    $result = $this->repository->create($data);

    expect($result)
        ->toBeInstanceOf(Operation::class)
        ->and($result->reservation_id)->toBe($reservation->id)
        ->and($result->type)->toBe('check_in')
        ->and($result->performed_by)->toBe($user->id)
        ->and($result->notes)->toBe('Guest checked in successfully.');

    $this->assertDatabaseHas('operations', [
        'id' => $result->id,
        'reservation_id' => $reservation->id,
        'type' => 'check_in',
        'performed_by' => $user->id,
    ]);
});

test('repository can get operations by reservation', function () {
    $user = User::factory()->create();
    $reservation = Reservation::factory()->create();

    Operation::create([
        'reservation_id' => $reservation->id,
        'type' => 'check_in',
        'performed_at' => now()->subHour(),
        'performed_by' => $user->id,
        'notes' => 'Guest checked in.',
    ]);

    Operation::create([
        'reservation_id' => $reservation->id,
        'type' => 'check_out',
        'performed_at' => now(),
        'performed_by' => $user->id,
        'notes' => 'Guest checked out.',
    ]);

    $result = $this->repository->getByReservation($reservation->id);

    expect($result)
        ->toHaveCount(2)
        ->and($result->first())->toBeInstanceOf(Operation::class)
        ->and($result->first()->relationLoaded('performedBy'))->toBeTrue()
        ->and($result->first()->performedBy->id)->toBe($user->id);
});

test('repository only returns operations for the requested reservation', function () {
    $user = User::factory()->create();

    $reservation1 = Reservation::factory()->create();
    $reservation2 = Reservation::factory()->create();

    Operation::create([
        'reservation_id' => $reservation1->id,
        'type' => 'check_in',
        'performed_at' => now(),
        'performed_by' => $user->id,
    ]);

    Operation::create([
        'reservation_id' => $reservation2->id,
        'type' => 'check_in',
        'performed_at' => now(),
        'performed_by' => $user->id,
    ]);

    $result = $this->repository->getByReservation($reservation1->id);

    expect($result)
        ->toHaveCount(1)
        ->and($result->first()->reservation_id)->toBe($reservation1->id);
});

test('repository returns operations ordered by performed time descending', function () {
    $user = User::factory()->create();
    $reservation = Reservation::factory()->create();

    $older = Operation::create([
        'reservation_id' => $reservation->id,
        'type' => 'check_in',
        'performed_at' => now()->subDay(),
        'performed_by' => $user->id,
    ]);

    $newer = Operation::create([
        'reservation_id' => $reservation->id,
        'type' => 'check_out',
        'performed_at' => now(),
        'performed_by' => $user->id,
    ]);

    $result = $this->repository->getByReservation($reservation->id);

    expect($result->first()->id)->toBe($newer->id)
        ->and($result->last()->id)->toBe($older->id);
});

test('repository returns an empty collection when reservation has no operations', function () {
    $reservation = Reservation::factory()->create();

    $result = $this->repository->getByReservation($reservation->id);

    expect($result)
        ->toBeEmpty()
        ->toBeInstanceOf(Collection::class);
});
