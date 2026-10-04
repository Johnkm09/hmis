<?php

use App\Models\Folio\Folio;
use App\Models\Reservation\Reservation;
use App\Repositories\Folio\FolioRepositoryInterface;
use App\Repositories\Folio\FolioChargeRepositoryInterface;
use App\Repositories\Reservation\ReservationInterface;
use App\Services\FolioService;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    $this->repository = Mockery::mock(FolioRepositoryInterface::class);
    $this->chargeRepository = Mockery::mock(FolioChargeRepositoryInterface::class);
    $this->reservationRepository = Mockery::mock(ReservationInterface::class);

    $this->service = new FolioService(
        $this->repository,
        $this->chargeRepository,
        $this->reservationRepository
    );
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
    $reservation = new Reservation([
        'check_in' => '2026-09-10',
        'check_out' => '2026-09-13',
        'nightly_rate' => '150.00',
        'total_amount' => '450.00',
    ]);

    $reservation->id = 1;

    $data = [
        'reservation_id' => 1,
        'status' => 'open',
        'opened_at' => now(),
        'charged_by' => 1,
    ];

    $folio = new Folio($data);
    $folio->id = 1;

    $this->repository
        ->shouldReceive('findByReservation')
        ->once()
        ->with(1)
        ->andReturn(null);

    $this->repository
        ->shouldReceive('create')
        ->once()
        ->with($data)
        ->andReturn($folio);

    $this->reservationRepository
        ->shouldReceive('findById')
        ->once()
        ->with(1)
        ->andReturn($reservation);

    $this->chargeRepository
        ->shouldReceive('create')
        ->once()
        ->with(Mockery::on(function ($charge) use ($folio) {
            return $charge['folio_id'] === $folio->id
                && $charge['service_id'] === null
                && $charge['type'] === 'accommodation'
                && $charge['description'] === 'Room accommodation'
                && $charge['quantity'] == 3
                && $charge['unit_price'] == 150.00
                && $charge['amount'] == 450.00
                && $charge['charged_by'] === 1;
        }));

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
