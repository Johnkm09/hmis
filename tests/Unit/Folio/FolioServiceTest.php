<?php

use App\Models\Folio\Folio;
use App\Models\Reservation\Reservation;
use App\Repositories\Folio\FolioRepositoryInterface;
use App\Services\FolioService;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    $this->repository = Mockery::mock(FolioRepositoryInterface::class);
    $this->service = new FolioService($this->repository);
});

it('can get a folio by reservation', function () {
    $folio = new Folio([
        'reservation_id' => 1,
        'status' => 'open',
    ]);

    $this->repository
        ->shouldReceive('getByReservation')
        ->once()
        ->with(1)
        ->andReturn($folio);

    $result = $this->service->getByReservation(1);

    expect($result)->toBe($folio);
});

it('can create a folio', function () {
    $data = [
        'reservation_id' => 1,
        'status' => 'open',
        'opened_at' => now(),
    ];

    $folio = new Folio($data);

    $this->repository
        ->shouldReceive('findByReservation')
        ->once()
        ->with($data['reservation_id'])
        ->andReturn(null);

    $this->repository
        ->shouldReceive('create')
        ->once()
        ->with($data)
        ->andReturn($folio);

    $result = $this->service->create($data);

    expect($result)->toBe($folio);
});

test('it prevents creating a second folio for the same reservation', function () {
    $reservation = Reservation::factory()->create();

    $existingFolio = Folio::factory()->create([
        'reservation_id' => $reservation->id,
    ]);

    $this->repository
        ->shouldReceive('findByReservation')
        ->once()
        ->with($reservation->id)
        ->andReturn($existingFolio);

    $this->repository
        ->shouldNotReceive('create');

    expect(fn() => $this->service->create([
        'reservation_id' => $reservation->id,
    ]))
        ->toThrow(
            ValidationException::class,
            'A folio already exists for this reservation.'
        );
});

it('can find a folio by id', function () {
    $folio = new Folio([
        'reservation_id' => 1,
        'status' => 'open',
    ]);

    $this->repository
        ->shouldReceive('findById')
        ->once()
        ->with(1)
        ->andReturn($folio);

    $result = $this->service->findById(1);

    expect($result)->toBe($folio);
});

it('can update a folio', function () {
    $data = [
        'status' => 'closed',
        'closed_at' => now(),
    ];

    $folio = new Folio([
        'reservation_id' => 1,
        'status' => 'closed',
    ]);

    $this->repository
        ->shouldReceive('update')
        ->once()
        ->with(1, $data)
        ->andReturn($folio);

    $result = $this->service->update(1, $data);

    expect($result)->toBe($folio);
});
