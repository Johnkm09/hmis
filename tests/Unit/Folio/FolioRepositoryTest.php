<?php

use App\Models\Folio\Folio;
use App\Models\Reservation\Reservation;
use App\Repositories\Folio\FolioRepository;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->repository = new FolioRepository();
});

it('can create a folio', function () {
    $reservation = Reservation::factory()->create();

    $folio = $this->repository->create([
        'reservation_id' => $reservation->id,
        'status' => 'open',
        'opened_at' => now(),
    ]);

    expect($folio)
        ->toBeInstanceOf(Folio::class)
        ->reservation_id->toBe($reservation->id)
        ->status->toBe('open');

    $this->assertDatabaseHas('folios', [
        'id' => $folio->id,
        'reservation_id' => $reservation->id,
        'status' => 'open',
    ]);
});

it('can find a folio by reservation', function () {
    $reservation = Reservation::factory()->create();

    $folio = Folio::factory()->create([
        'reservation_id' => $reservation->id,
    ]);

    $repository = new FolioRepository();

    $result = $repository->findByReservation($reservation->id);

    expect($result->id)->toBe($folio->id);
});

it('throws an exception when reservation has no folio', function () {
    $reservation = Reservation::factory()->create();

    $this->repository->getByReservation($reservation->id);
})->throws(ModelNotFoundException::class);

it('can find a folio by id', function () {
    $folio = Folio::factory()->create();

    $result = $this->repository->findById($folio->id);

    expect($result->id)->toBe($folio->id);
});

it('throws an exception when folio does not exist', function () {
    $this->repository->findById(999999);
})->throws(ModelNotFoundException::class);

it('can update a folio', function () {
    $folio = Folio::factory()->create();

    $result = $this->repository->update($folio->id, [
        'status' => 'closed',
        'closed_at' => now(),
    ]);

    expect($result->status)->toBe('closed');

    $this->assertDatabaseHas('folios', [
        'id' => $folio->id,
        'status' => 'closed',
    ]);
});
