<?php

use App\Repositories\Guest\GuestRepository;
use App\Models\Guest\Guest;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(
    function () {
        $this->repository = new GuestRepository();
    }
);

// CRUD tests
test('repository can get all guests', function () {
    Guest::factory()->count(3)->create();

    $result = $this->repository->getAll();

    expect($result->total())->toBe(3);
});

test('repository can create a guest', function () {
    $data = [
        'first_name' => 'Kamau',
        'last_name' => 'Njatha',
        'id_number' => 'A1234567',
        'phone_number' => '+254729000111',
        'email' => 'email@email.com',
        'country' => 'Kenya',
        'city' => 'Nairobi',
        'address' => '6700-100',
    ];

    $result = $this->repository->create($data);

    expect($result)->toBeInstanceOf(Guest::class)
        ->and($result->first_name)->toBe('Kamau')
        ->and($result->phone_number)->toBe('+254729000111');

    $this->assertDatabaseHas('guests', [
        'first_name' => "Kamau",
        'last_name' => 'Njatha',
        'phone_number' => '+254729000111',
    ]);
});

test('repository can find guest by id', function () {
    $guest = Guest::factory()->create();

    $result = $this->repository->findById($guest->id);

    expect($result)->toBeInstanceOf(Guest::class)
        ->and($result->id)->toBe($guest->id);
});

test('repository can update a guest', function () {
    $guest = Guest::factory()->create();

    $result = $this->repository->update($guest->id, [
        'first_name' => 'Njoroge'
    ]);

    expect($result)->toBeInstanceOf(Guest::class)
        ->and($result->first_name)->toBe('Njoroge');

    $this->assertDatabaseHas('guests', [
        'id' => $guest->id,
        'first_name' => 'Njoroge',
    ]);
});

test('repository can delete a guest', function () {
    $guest = Guest::factory()->create();

    $this->repository->delete($guest->id);

    $this->assertSoftDeleted('guests', [
        'id' => $guest->id
    ]);
});

//Filter Tests
test('repository can filter guests by first name', function () {
    Guest::factory()->create(['first_name' => 'Kamau']);
    Guest::factory()->create(['first_name' => 'Macharia']);

    request()->merge([
        'filter' => [
            'first_name' => 'Kamau'
        ]
    ]);

    $result = $this->repository->getAll();

    expect($result->total())->toBe(1)->and($result->first()->first_name)->toBe('Kamau');
});

test('repository can filter guests by last name', function () {
    Guest::factory()->create(['last_name' => 'Kamau']);
    Guest::factory()->create(['last_name' => 'Macharia']);

    request()->merge([
        'filter' => [
            'last_name' => 'Kamau'
        ]
    ]);

    $result = $this->repository->getAll();

    expect($result->total())->toBe(1)->and($result->first()->last_name)->toBe('Kamau');
});

test('repository can partially filter guests by email', function () {
    Guest::factory()->create(['email' => 'admin@email.com']);

    Guest::factory()->create(['email' => 'email@test.com']);

    request()->merge([
        'filter' => [
            'email' => 'email.com'
        ]
    ]);

    $result = $this->repository->getAll();

    expect($result->total())->toBe(1)->and($result->first()->email)->toBe('admin@email.com');
});

test('repository can filter guests by country', function () {
    Guest::factory()->create(['country' => 'Kenya']);

    Guest::factory()->create(['country' => 'Uganda']);

    request()->merge([
        'filter' => [
            'country' => 'Kenya'
        ]
    ]);

    $result = $this->repository->getAll();

    expect($result->total())->toBe(1)->and($result->first()->country)->toBe('Kenya');
});

test('repository can filter guests by city', function () {
    Guest::factory()->create(['city' => 'Nairobi']);

    Guest::factory()->create(['city' => 'Dar']);

    request()->merge([
        'filter' => [
            'city' => 'Nairobi'
        ]
    ]);

    $result = $this->repository->getAll();

    expect($result->total())->toBe(1)->and($result->first()->city)->toBe('Nairobi');
});

test('repository does not partially filter guests by country', function () {
    Guest::factory()->create([
        'country' => 'Kenya'
    ]);

    request()->merge([
        'filter' => [
            'country' => 'Ken'
        ]
    ]);

    $result = $this->repository->getAll();

    expect($result->total())->toBe(0);
});

test('repository does not partially filter guests by city', function () {
    Guest::factory()->create([
        'city' => 'Nairobi'
    ]);

    request()->merge([
        'filter' => [
            'city' => 'Nair'
        ]
    ]);

    $result = $this->repository->getAll();

    expect($result->total())->toBe(0);
});

test('repository can sort guests by first name', function () {
    Guest::factory()->create([
        'first_name' => 'Kamau'
    ]);

    Guest::factory()->create([
        'first_name' => 'Nyambu'
    ]);

    request()->merge([
        'sort' => 'first_name'
    ]);

    $result = $this->repository->getAll();

    expect($result->first()->first_name)->toBe('Kamau');
});

test('repository can sort guests by first name descending', function () {
    Guest::factory()->create([
        'first_name' => 'Kamau'
    ]);

    Guest::factory()->create([
        'first_name' => 'Nyambu'
    ]);

    request()->merge([
        'sort' => '-first_name'
    ]);

    $result = $this->repository->getAll();

    expect($result->first()->first_name)->toBe('Nyambu');
});
