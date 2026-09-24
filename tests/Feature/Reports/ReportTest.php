<?php

use App\Models\Folio\Folio;
use App\Models\Folio\FolioCharge;
use App\Models\Payment\Payment;
use App\Models\Payment\Refund;
use App\Models\Reservation\Reservation;
use App\Models\Guest\Guest;
use App\Models\Room\Room;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

function actingAsReportUser(string $role)
{
    $user = User::factory()->create();

    $user->assignRole($role);

    test()->actingAs($user, 'sanctum');

    return $user;
}

test('manager can view revenue report', function () {
    actingAsReportUser('manager');

    $guest = Guest::factory()->create();
    $room = Room::factory()->create();

    $reservation = Reservation::factory()->create([
        'guest_id' => $guest->id,
        'room_id' => $room->id,
    ]);

    $folio = Folio::factory()->create([
        'reservation_id' => $reservation->id,
    ]);

    FolioCharge::factory()->create([
        'folio_id' => $folio->id,
        'amount' => 120000,
        'charged_at' => now(),
    ]);

    Payment::factory()->create([
        'folio_id' => $folio->id,
        'amount' => 95000,
        'status' => 'completed',
        'paid_at' => now(),
    ]);

    Refund::factory()->create([
        'payment_id' => Payment::query()->latest()->first()->id,
        'amount' => 5000,
        'status' => 'completed',
        'refunded_at' => now(),
    ]);

    $response = $this->getJson('/api/v1/reports/revenue');

    $response
        ->assertStatus(200)
        ->assertJsonPath('status', 'success')
        ->assertJsonPath('data.charges', 120000)
        ->assertJsonPath('data.completed_payments', 95000)
        ->assertJsonPath('data.completed_refunds', 5000)
        ->assertJsonPath('data.net_collected', 90000)
        ->assertJsonPath('data.outstanding', 25000);
});

test('receptionist cannot view revenue report', function () {
    actingAsReportUser('receptionist');

    $response = $this->getJson('/api/v1/reports/revenue');

    $response->assertStatus(403);
});

test('user cannot view revenue report', function () {
    actingAsReportUser('user');

    $response = $this->getJson('/api/v1/reports/revenue');

    $response->assertStatus(403);
});

test('revenue report respects date filters', function () {
    actingAsReportUser('manager');

    $guest = Guest::factory()->create();
    $room = Room::factory()->create();

    $reservation = Reservation::factory()->create([
        'guest_id' => $guest->id,
        'room_id' => $room->id,
    ]);

    $folio = Folio::factory()->create([
        'reservation_id' => $reservation->id,
    ]);

    FolioCharge::factory()->create([
        'folio_id' => $folio->id,
        'amount' => 50000,
        'charged_at' => '2026-09-10 10:00:00',
    ]);

    FolioCharge::factory()->create([
        'folio_id' => $folio->id,
        'amount' => 30000,
        'charged_at' => '2026-08-10 10:00:00',
    ]);

    $response = $this->getJson(
        '/api/v1/reports/revenue?from=2026-09-01&to=2026-09-30'
    );

    $response
        ->assertStatus(200)
        ->assertJsonPath('data.charges', 50000);
});

test('revenue report defaults to current month', function () {
    actingAsReportUser('manager');

    $guest = Guest::factory()->create();
    $room = Room::factory()->create();

    $reservation = Reservation::factory()->create([
        'guest_id' => $guest->id,
        'room_id' => $room->id,
    ]);

    $folio = Folio::factory()->create([
        'reservation_id' => $reservation->id,
    ]);

    FolioCharge::factory()->create([
        'folio_id' => $folio->id,
        'amount' => 40000,
        'charged_at' => now(),
    ]);

    FolioCharge::factory()->create([
        'folio_id' => $folio->id,
        'amount' => 20000,
        'charged_at' => now()->subMonth(),
    ]);

    $response = $this->getJson('/api/v1/reports/revenue');

    $response
        ->assertStatus(200)
        ->assertJsonPath('data.charges', 40000);
});

test('revenue report rejects invalid date range', function () {
    actingAsReportUser('manager');

    $response = $this->getJson(
        '/api/v1/reports/revenue?from=2026-09-30&to=2026-09-01'
    );

    $response
        ->assertStatus(422)
        ->assertJsonValidationErrors(['to']);
});

test('revenue report only counts completed payments', function () {
    actingAsReportUser('manager');

    $guest = Guest::factory()->create();
    $room = Room::factory()->create();

    $reservation = Reservation::factory()->create([
        'guest_id' => $guest->id,
        'room_id' => $room->id,
    ]);

    $folio = Folio::factory()->create([
        'reservation_id' => $reservation->id,
    ]);

    Payment::factory()->create([
        'folio_id' => $folio->id,
        'amount' => 60000,
        'status' => 'completed',
        'paid_at' => now(),
    ]);

    Payment::factory()->create([
        'folio_id' => $folio->id,
        'amount' => 30000,
        'status' => 'pending',
        'paid_at' => now(),
    ]);

    Payment::factory()->create([
        'folio_id' => $folio->id,
        'amount' => 10000,
        'status' => 'failed',
        'paid_at' => now(),
    ]);

    $response = $this->getJson('/api/v1/reports/revenue');

    $response
        ->assertStatus(200)
        ->assertJsonPath('data.completed_payments', 60000);
});

test('revenue report only counts completed refunds', function () {
    actingAsReportUser('manager');

    $guest = Guest::factory()->create();
    $room = Room::factory()->create();

    $reservation = Reservation::factory()->create([
        'guest_id' => $guest->id,
        'room_id' => $room->id,
    ]);

    $folio = Folio::factory()->create([
        'reservation_id' => $reservation->id,
    ]);

    $payment = Payment::factory()->create([
        'folio_id' => $folio->id,
        'amount' => 80000,
        'status' => 'completed',
        'paid_at' => now(),
    ]);

    Refund::factory()->create([
        'payment_id' => $payment->id,
        'amount' => 10000,
        'status' => 'completed',
        'refunded_at' => now(),
    ]);

    Refund::factory()->create([
        'payment_id' => $payment->id,
        'amount' => 5000,
        'status' => 'pending',
        'refunded_at' => now(),
    ]);

    $response = $this->getJson('/api/v1/reports/revenue');

    $response
        ->assertStatus(200)
        ->assertJsonPath('data.completed_refunds', 10000);
});

test('revenue report calculates outstanding amount correctly', function () {
    actingAsReportUser('manager');

    $guest = Guest::factory()->create();
    $room = Room::factory()->create();

    $reservation = Reservation::factory()->create([
        'guest_id' => $guest->id,
        'room_id' => $room->id,
    ]);

    $folio = Folio::factory()->create([
        'reservation_id' => $reservation->id,
    ]);

    FolioCharge::factory()->create([
        'folio_id' => $folio->id,
        'amount' => 100000,
        'charged_at' => now(),
    ]);

    Payment::factory()->create([
        'folio_id' => $folio->id,
        'amount' => 70000,
        'status' => 'completed',
        'paid_at' => now(),
    ]);

    $response = $this->getJson('/api/v1/reports/revenue');

    $response
        ->assertStatus(200)
        ->assertJsonPath('data.outstanding', 30000);
});

test('unauthenticated user cannot view revenue report', function () {
    $response = $this->getJson('/api/v1/reports/revenue');

    $response->assertStatus(401);
});
test('manager can view occupancy report', function () {
    actingAsReportUser('manager');

    $guest = Guest::factory()->create();
    $room = Room::factory()->create();

    Reservation::factory()->create([
        'guest_id' => $guest->id,
        'room_id' => $room->id,
        'check_in' => '2026-09-10',
        'check_out' => '2026-09-13',
        'status' => 'confirmed',
    ]);

    $response = $this->getJson(
        '/api/v1/reports/occupancy?from=2026-09-01&to=2026-09-30'
    );

    $response
        ->assertStatus(200)
        ->assertJsonPath('status', 'success')
        ->assertJsonPath('data.occupied_room_nights', 3)
        ->assertJsonPath('data.available_room_nights', 30)
        ->assertJsonPath('data.occupancy_percentage', 10);
});

test('receptionist cannot view occupancy report', function () {
    actingAsReportUser('receptionist');

    $response = $this->getJson('/api/v1/reports/occupancy');

    $response->assertStatus(403);
});

test('user cannot view occupancy report', function () {
    actingAsReportUser('user');

    $response = $this->getJson('/api/v1/reports/occupancy');

    $response->assertStatus(403);
});

test('unauthenticated user cannot view occupancy report', function () {
    $response = $this->getJson('/api/v1/reports/occupancy');

    $response->assertStatus(401);
});

test('occupancy report respects date filters', function () {
    actingAsReportUser('manager');

    $guest = Guest::factory()->create();
    $room = Room::factory()->create();

    Reservation::factory()->create([
        'guest_id' => $guest->id,
        'room_id' => $room->id,
        'check_in' => '2026-08-10',
        'check_out' => '2026-08-13',
        'status' => 'confirmed',
    ]);

    Reservation::factory()->create([
        'guest_id' => $guest->id,
        'room_id' => $room->id,
        'check_in' => '2026-09-10',
        'check_out' => '2026-09-13',
        'status' => 'confirmed',
    ]);

    $response = $this->getJson(
        '/api/v1/reports/occupancy?from=2026-09-01&to=2026-09-30'
    );

    $response
        ->assertStatus(200)
        ->assertJsonPath('data.occupied_room_nights', 3);
});

test('occupancy report counts only occupied reservation statuses', function () {
    actingAsReportUser('manager');

    $guest = Guest::factory()->create();
    $room = Room::factory()->create();

    Reservation::factory()->create([
        'guest_id' => $guest->id,
        'room_id' => $room->id,
        'check_in' => '2026-09-10',
        'check_out' => '2026-09-13',
        'status' => 'confirmed',
    ]);

    Reservation::factory()->create([
        'guest_id' => $guest->id,
        'room_id' => $room->id,
        'check_in' => '2026-09-15',
        'check_out' => '2026-09-18',
        'status' => 'pending',
    ]);

    $response = $this->getJson(
        '/api/v1/reports/occupancy?from=2026-09-01&to=2026-09-30'
    );

    $response
        ->assertStatus(200)
        ->assertJsonPath('data.occupied_room_nights', 3);
});

test('occupancy report counts checked in and checked out reservations', function () {
    actingAsReportUser('manager');

    $guest = Guest::factory()->create();
    $room = Room::factory()->create();

    Reservation::factory()->create([
        'guest_id' => $guest->id,
        'room_id' => $room->id,
        'check_in' => '2026-09-01',
        'check_out' => '2026-09-04',
        'status' => 'checked_in',
    ]);

    Reservation::factory()->create([
        'guest_id' => $guest->id,
        'room_id' => $room->id,
        'check_in' => '2026-09-05',
        'check_out' => '2026-09-07',
        'status' => 'checked_out',
    ]);

    $response = $this->getJson(
        '/api/v1/reports/occupancy?from=2026-09-01&to=2026-09-30'
    );

    $response
        ->assertStatus(200)
        ->assertJsonPath('data.occupied_room_nights', 5);
});

test('occupancy report counts check ins and check outs', function () {
    actingAsReportUser('manager');

    $guest = Guest::factory()->create();
    $room = Room::factory()->create();

    Reservation::factory()->create([
        'guest_id' => $guest->id,
        'room_id' => $room->id,
        'check_in' => '2026-09-10',
        'check_out' => '2026-09-13',
        'status' => 'checked_in',
    ]);

    Reservation::factory()->create([
        'guest_id' => $guest->id,
        'room_id' => $room->id,
        'check_in' => '2026-09-15',
        'check_out' => '2026-09-18',
        'status' => 'checked_out',
    ]);

    $response = $this->getJson(
        '/api/v1/reports/occupancy?from=2026-09-01&to=2026-09-30'
    );

    $response
        ->assertStatus(200)
        ->assertJsonPath('data.check_ins', 1)
        ->assertJsonPath('data.check_outs', 1);
});

test('occupancy report calculates partial date overlap correctly', function () {
    actingAsReportUser('manager');

    $guest = Guest::factory()->create();
    $room = Room::factory()->create();

    Reservation::factory()->create([
        'guest_id' => $guest->id,
        'room_id' => $room->id,
        'check_in' => '2026-08-29',
        'check_out' => '2026-09-03',
        'status' => 'confirmed',
    ]);

    $response = $this->getJson(
        '/api/v1/reports/occupancy?from=2026-09-01&to=2026-09-30'
    );

    $response
        ->assertStatus(200)
        ->assertJsonPath('data.occupied_room_nights', 2);
});

test('occupancy report rejects invalid date range', function () {
    actingAsReportUser('manager');

    $response = $this->getJson(
        '/api/v1/reports/occupancy?from=2026-09-30&to=2026-09-01'
    );

    $response
        ->assertStatus(422)
        ->assertJsonValidationErrors(['to']);
});

test('manager can view reservations report', function () {
    actingAsReportUser('manager');

    $guest = Guest::factory()->create();
    $room = Room::factory()->create();

    Reservation::factory()->create([
        'guest_id' => $guest->id,
        'room_id' => $room->id,
        'status' => 'pending',
    ]);

    Reservation::factory()->create([
        'guest_id' => $guest->id,
        'room_id' => $room->id,
        'status' => 'confirmed',
    ]);

    Reservation::factory()->create([
        'guest_id' => $guest->id,
        'room_id' => $room->id,
        'status' => 'checked_in',
    ]);

    Reservation::factory()->create([
        'guest_id' => $guest->id,
        'room_id' => $room->id,
        'status' => 'checked_out',
    ]);

    $response = $this->getJson(
        '/api/v1/reports/reservations?from=2026-09-01&to=2026-09-30'
    );

    $response
        ->assertStatus(200)
        ->assertJsonPath('status', 'success')
        ->assertJsonPath('data.total', 4)
        ->assertJsonPath('data.pending', 1)
        ->assertJsonPath('data.confirmed', 1)
        ->assertJsonPath('data.checked_in', 1)
        ->assertJsonPath('data.checked_out', 1);
});

test('receptionist cannot view reservations report', function () {
    actingAsReportUser('receptionist');

    $response = $this->getJson('/api/v1/reports/reservations');

    $response->assertStatus(403);
});

test('user cannot view reservations report', function () {
    actingAsReportUser('user');

    $response = $this->getJson('/api/v1/reports/reservations');

    $response->assertStatus(403);
});

test('unauthenticated user cannot view reservations report', function () {
    $response = $this->getJson('/api/v1/reports/reservations');

    $response->assertStatus(401);
});

test('reservations report respects date filters', function () {
    actingAsReportUser('manager');

    $guest = Guest::factory()->create();
    $room = Room::factory()->create();

    Reservation::factory()->create([
        'guest_id' => $guest->id,
        'room_id' => $room->id,
        'created_at' => '2026-09-10 10:00:00',
        'status' => 'confirmed',
    ]);

    Reservation::factory()->create([
        'guest_id' => $guest->id,
        'room_id' => $room->id,
        'created_at' => '2026-08-10 10:00:00',
        'status' => 'confirmed',
    ]);

    $response = $this->getJson(
        '/api/v1/reports/reservations?from=2026-09-01&to=2026-09-30'
    );

    $response
        ->assertStatus(200)
        ->assertJsonPath('data.total', 1)
        ->assertJsonPath('data.confirmed', 1);
});

test('reservations report defaults to current month', function () {
    actingAsReportUser('manager');

    $guest = Guest::factory()->create();
    $room = Room::factory()->create();

    Reservation::factory()->create([
        'guest_id' => $guest->id,
        'room_id' => $room->id,
        'created_at' => now(),
        'status' => 'confirmed',
    ]);

    Reservation::factory()->create([
        'guest_id' => $guest->id,
        'room_id' => $room->id,
        'created_at' => now()->subMonth(),
        'status' => 'confirmed',
    ]);

    $response = $this->getJson('/api/v1/reports/reservations');

    $response
        ->assertStatus(200)
        ->assertJsonPath('data.total', 1);
});

test('reservations report counts pending reservations', function () {
    actingAsReportUser('manager');

    $guest = Guest::factory()->create();
    $room = Room::factory()->create();

    Reservation::factory()->create([
        'guest_id' => $guest->id,
        'room_id' => $room->id,
        'status' => 'pending',
    ]);

    $response = $this->getJson('/api/v1/reports/reservations');

    $response
        ->assertStatus(200)
        ->assertJsonPath('data.pending', 1);
});

test('reservations report counts confirmed reservations', function () {
    actingAsReportUser('manager');

    $guest = Guest::factory()->create();
    $room = Room::factory()->create();

    Reservation::factory()->create([
        'guest_id' => $guest->id,
        'room_id' => $room->id,
        'status' => 'confirmed',
    ]);

    $response = $this->getJson('/api/v1/reports/reservations');

    $response
        ->assertStatus(200)
        ->assertJsonPath('data.confirmed', 1);
});

test('reservations report counts checked in reservations', function () {
    actingAsReportUser('manager');

    $guest = Guest::factory()->create();
    $room = Room::factory()->create();

    Reservation::factory()->create([
        'guest_id' => $guest->id,
        'room_id' => $room->id,
        'status' => 'checked_in',
    ]);

    $response = $this->getJson('/api/v1/reports/reservations');

    $response
        ->assertStatus(200)
        ->assertJsonPath('data.checked_in', 1);
});

test('reservations report counts checked out reservations', function () {
    actingAsReportUser('manager');

    $guest = Guest::factory()->create();
    $room = Room::factory()->create();

    Reservation::factory()->create([
        'guest_id' => $guest->id,
        'room_id' => $room->id,
        'status' => 'checked_out',
    ]);

    $response = $this->getJson('/api/v1/reports/reservations');

    $response
        ->assertStatus(200)
        ->assertJsonPath('data.checked_out', 1);
});

test('reservations report rejects invalid date range', function () {
    actingAsReportUser('manager');

    $response = $this->getJson(
        '/api/v1/reports/reservations?from=2026-09-30&to=2026-09-01'
    );

    $response
        ->assertStatus(422)
        ->assertJsonValidationErrors(['to']);
});

test('manager can view payments report', function () {
    actingAsReportUser('manager');

    $guest = Guest::factory()->create();
    $room = Room::factory()->create();

    $reservation = Reservation::factory()->create([
        'guest_id' => $guest->id,
        'room_id' => $room->id,
    ]);

    $folio = Folio::factory()->create([
        'reservation_id' => $reservation->id,
    ]);

    Payment::factory()->create([
        'folio_id' => $folio->id,
        'amount' => 50000,
        'method' => 'mpesa',
        'status' => 'completed',
        'created_at' => now(),
    ]);

    Payment::factory()->create([
        'folio_id' => $folio->id,
        'amount' => 30000,
        'method' => 'stripe',
        'status' => 'completed',
        'created_at' => now(),
    ]);

    Payment::factory()->create([
        'folio_id' => $folio->id,
        'amount' => 10000,
        'method' => 'cash',
        'status' => 'pending',
        'created_at' => now(),
    ]);

    $response = $this->getJson('/api/v1/reports/payments');

    $response
        ->assertStatus(200)
        ->assertJsonPath('status', 'success')
        ->assertJsonPath('data.total', 3)
        ->assertJsonPath('data.completed', 2)
        ->assertJsonPath('data.pending', 1)
        ->assertJsonPath('data.failed', 0)
        ->assertJsonPath('data.total_completed_amount', 80000)
        ->assertJsonPath('data.mpesa', 50000)
        ->assertJsonPath('data.stripe', 30000)
        ->assertJsonPath('data.cash', 0);
});

test('receptionist cannot view payments report', function () {
    actingAsReportUser('receptionist');

    $response = $this->getJson('/api/v1/reports/payments');

    $response->assertStatus(403);
});

test('user cannot view payments report', function () {
    actingAsReportUser('user');

    $response = $this->getJson('/api/v1/reports/payments');

    $response->assertStatus(403);
});

test('unauthenticated user cannot view payments report', function () {
    $response = $this->getJson('/api/v1/reports/payments');

    $response->assertStatus(401);
});

test('payments report respects date filters', function () {
    actingAsReportUser('manager');

    $guest = Guest::factory()->create();
    $room = Room::factory()->create();

    $reservation = Reservation::factory()->create([
        'guest_id' => $guest->id,
        'room_id' => $room->id,
    ]);

    $folio = Folio::factory()->create([
        'reservation_id' => $reservation->id,
    ]);

    Payment::factory()->create([
        'folio_id' => $folio->id,
        'amount' => 40000,
        'status' => 'completed',
        'created_at' => '2026-09-10 10:00:00',
    ]);

    Payment::factory()->create([
        'folio_id' => $folio->id,
        'amount' => 20000,
        'status' => 'completed',
        'created_at' => '2026-08-10 10:00:00',
    ]);

    $response = $this->getJson(
        '/api/v1/reports/payments?from=2026-09-01&to=2026-09-30'
    );

    $response
        ->assertStatus(200)
        ->assertJsonPath('data.total', 1)
        ->assertJsonPath('data.total_completed_amount', 40000);
});

test('payments report defaults to current month', function () {
    actingAsReportUser('manager');

    $guest = Guest::factory()->create();
    $room = Room::factory()->create();

    $reservation = Reservation::factory()->create([
        'guest_id' => $guest->id,
        'room_id' => $room->id,
    ]);

    $folio = Folio::factory()->create([
        'reservation_id' => $reservation->id,
    ]);

    Payment::factory()->create([
        'folio_id' => $folio->id,
        'amount' => 50000,
        'status' => 'completed',
        'created_at' => now(),
    ]);

    Payment::factory()->create([
        'folio_id' => $folio->id,
        'amount' => 20000,
        'status' => 'completed',
        'created_at' => now()->subMonth(),
    ]);

    $response = $this->getJson('/api/v1/reports/payments');

    $response
        ->assertStatus(200)
        ->assertJsonPath('data.total', 1)
        ->assertJsonPath('data.total_completed_amount', 50000);
});

test('payments report counts failed payments', function () {
    actingAsReportUser('manager');

    $guest = Guest::factory()->create();
    $room = Room::factory()->create();

    $reservation = Reservation::factory()->create([
        'guest_id' => $guest->id,
        'room_id' => $room->id,
    ]);

    $folio = Folio::factory()->create([
        'reservation_id' => $reservation->id,
    ]);

    Payment::factory()->create([
        'folio_id' => $folio->id,
        'amount' => 15000,
        'status' => 'failed',
        'created_at' => now(),
    ]);

    $response = $this->getJson('/api/v1/reports/payments');

    $response
        ->assertStatus(200)
        ->assertJsonPath('data.failed', 1)
        ->assertJsonPath('data.total_completed_amount', 0);
});

test('payments report separates payment methods', function () {
    actingAsReportUser('manager');

    $guest = Guest::factory()->create();
    $room = Room::factory()->create();

    $reservation = Reservation::factory()->create([
        'guest_id' => $guest->id,
        'room_id' => $room->id,
    ]);

    $folio = Folio::factory()->create([
        'reservation_id' => $reservation->id,
    ]);

    Payment::factory()->create([
        'folio_id' => $folio->id,
        'amount' => 20000,
        'method' => 'mpesa',
        'status' => 'completed',
    ]);

    Payment::factory()->create([
        'folio_id' => $folio->id,
        'amount' => 30000,
        'method' => 'stripe',
        'status' => 'completed',
    ]);

    Payment::factory()->create([
        'folio_id' => $folio->id,
        'amount' => 10000,
        'method' => 'cash',
        'status' => 'completed',
    ]);

    $response = $this->getJson('/api/v1/reports/payments');

    $response
        ->assertStatus(200)
        ->assertJsonPath('data.mpesa', 20000)
        ->assertJsonPath('data.stripe', 30000)
        ->assertJsonPath('data.cash', 10000);
});

test('payments report excludes pending and failed payments from completed amounts', function () {
    actingAsReportUser('manager');

    $guest = Guest::factory()->create();
    $room = Room::factory()->create();

    $reservation = Reservation::factory()->create([
        'guest_id' => $guest->id,
        'room_id' => $room->id,
    ]);

    $folio = Folio::factory()->create([
        'reservation_id' => $reservation->id,
    ]);

    Payment::factory()->create([
        'folio_id' => $folio->id,
        'amount' => 50000,
        'status' => 'completed',
    ]);

    Payment::factory()->create([
        'folio_id' => $folio->id,
        'amount' => 20000,
        'status' => 'pending',
    ]);

    Payment::factory()->create([
        'folio_id' => $folio->id,
        'amount' => 10000,
        'status' => 'failed',
    ]);

    $response = $this->getJson('/api/v1/reports/payments');

    $response
        ->assertStatus(200)
        ->assertJsonPath('data.total_completed_amount', 50000);
});

test('payments report rejects invalid date range', function () {
    actingAsReportUser('manager');

    $response = $this->getJson(
        '/api/v1/reports/payments?from=2026-09-30&to=2026-09-01'
    );

    $response
        ->assertStatus(422)
        ->assertJsonValidationErrors(['to']);
});
