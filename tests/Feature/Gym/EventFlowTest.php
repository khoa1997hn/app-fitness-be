<?php

namespace Tests\Feature\Gym;

use App\Share\Enums\EventStatus;
use App\Share\Enums\OrderStatus;
use App\Share\Enums\Role;
use App\Share\Mail\GymNoticeMail;
use App\Share\Models\Event;
use App\Share\Models\EventRegistration;
use App\Share\Models\Order;
use App\Share\Models\User;
use App\Share\Models\UserNotification;
use App\Share\Services\Gym\EventService;
use App\Share\Services\Gym\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class EventFlowTest extends TestCase
{
    use RefreshDatabase;

    private const SIGNATURE = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==';

    private User $admin;

    private User $member;

    private User $other;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-11-10 10:00:00');
        $this->admin = User::factory()->create(['role' => Role::Admin]);
        $this->member = User::factory()->create(['phone' => '0900000004']);
        $this->other = User::factory()->create();
        config(['gym.payment_mock_enabled' => true]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function as(User $user): static
    {
        return $this->withToken(auth('api')->login($user));
    }

    private function createEvent(array $overrides = []): Event
    {
        return Event::query()->create(array_merge([
            'event_date' => '2026-12-20 09:00:00',
            'location' => 'Sảnh A',
            'ticket_price' => 300000,
            'total_tickets' => 5,
            'status' => EventStatus::Open,
            'vi' => ['name' => 'Workshop', 'description' => 'Mô tả'],
        ], $overrides));
    }

    private function createOrderFor(Event $event, ?User $user = null, int $quantity = 1): string
    {
        return $this->as($user ?? $this->member)
            ->postJson('/api/v1/orders', ['type' => 'event', 'event_id' => $event->id, 'quantity' => $quantity])
            ->assertCreated()
            ->json('data.code');
    }

    private function signAndPay(string $code, ?User $user = null): void
    {
        $client = $this->as($user ?? $this->member);
        $client->postJson("/api/v1/orders/{$code}/accept-terms", [
            'terms_accepted' => true,
            'signature_data' => self::SIGNATURE,
            'buyer_name' => 'Khách',
            'buyer_phone' => '0900000004',
            'buyer_email' => 'khach@example.com',
        ])->assertOk();
        $client->postJson('/api/v1/payments', ['order_code' => $code, 'payment_method' => 'momo'])->assertOk();
    }

    public function test_public_list_shows_only_open_upcoming_events_with_counts(): void
    {
        $open = $this->createEvent();
        $this->createEvent(['status' => EventStatus::Closed]);
        $this->createEvent(['event_date' => '2026-11-01 09:00:00']);
        $this->createOrderFor($open, quantity: 2);

        $this->flushHeaders();
        $this->app['auth']->forgetGuards();
        $this->getJson('/api/v1/events')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $open->id)
            ->assertJsonPath('data.0.sold_tickets', 0)
            ->assertJsonPath('data.0.tickets_left', 3);
    }

    public function test_free_registration_gets_ticket_immediately_and_blocks_duplicate(): void
    {
        $event = $this->createEvent(['ticket_price' => 0]);
        $client = $this->as($this->member);

        $response = $client->postJson("/api/v1/events/{$event->id}/register")
            ->assertCreated()
            ->assertJsonPath('data.status', 'paid')
            ->assertJsonPath('data.order_code', null);
        $this->assertSame("EVT-{$event->id}-{$response->json('data.id')}", $response->json('data.ticket_code'));

        $client->postJson("/api/v1/events/{$event->id}/register")->assertUnprocessable();
        $client->postJson('/api/v1/orders', ['type' => 'event', 'event_id' => $event->id, 'quantity' => 1])->assertUnprocessable();
        $this->assertDatabaseHas('notifications', ['user_id' => $this->member->id, 'type' => 'event']);
    }

    public function test_paid_event_must_use_order_and_guest_cannot_register(): void
    {
        $event = $this->createEvent();

        $this->postJson("/api/v1/events/{$event->id}/register")->assertUnauthorized();
        $this->as($this->member)->postJson("/api/v1/events/{$event->id}/register")->assertUnprocessable();
        $this->as($this->admin)->postJson('/api/v1/orders', ['type' => 'event', 'event_id' => $event->id, 'quantity' => 1])->assertForbidden();
    }

    public function test_paid_ticket_email_includes_ticket_code(): void
    {
        Mail::fake();
        $code = $this->createOrderFor($this->createEvent());
        $this->signAndPay($code);

        $ticketCode = EventRegistration::query()->where('order_id', Order::query()->where('code', $code)->value('id'))->value('ticket_code');

        $this->assertNotNull($ticketCode);
        Mail::assertQueued(GymNoticeMail::class, fn (GymNoticeMail $mail) => str_contains($mail->notice, "Mã vé: {$ticketCode}."));
    }

    public function test_paid_flow_holds_ticket_then_pays_and_counts_sold(): void
    {
        $event = $this->createEvent();
        $code = $this->createOrderFor($event, quantity: 2);

        $this->assertDatabaseHas('orders', ['code' => $code, 'amount' => 600000, 'event_id' => $event->id, 'status' => 'pending_terms']);
        $this->as($this->member)->getJson('/api/v1/users/me/event-registrations')
            ->assertOk()
            ->assertJsonPath('data.0.status', 'pending')
            ->assertJsonPath('data.0.order_code', $code)
            ->assertJsonPath('data.0.event.tickets_left', 3);

        $this->signAndPay($code);

        $this->as($this->member)->getJson('/api/v1/users/me/event-registrations')
            ->assertJsonPath('data.0.status', 'paid')
            ->assertJsonPath('data.0.event.sold_tickets', 2)
            ->assertJsonPath('data.0.event.tickets_left', 3);

        $messages = UserNotification::query()->where('user_id', $this->member->id)->pluck('message');
        $this->assertCount(1, $messages);
        $this->assertStringContainsString("EVT-{$event->id}-", $messages[0]);
    }

    public function test_held_tickets_prevent_overselling_until_hold_expires(): void
    {
        $event = $this->createEvent(['total_tickets' => 2]);
        $this->createOrderFor($event, quantity: 2);

        $this->as($this->other)->postJson('/api/v1/orders', ['type' => 'event', 'event_id' => $event->id, 'quantity' => 1])->assertUnprocessable();

        Carbon::setTestNow('2026-11-10 10:31:00');

        $this->createOrderFor($event, $this->other);
    }

    public function test_expired_hold_cannot_pay_and_is_released(): void
    {
        $event = $this->createEvent();
        $signed = $this->createOrderFor($event);
        $unsigned = $this->createOrderFor($event, $this->other);
        $this->as($this->member)->postJson("/api/v1/orders/{$signed}/accept-terms", [
            'terms_accepted' => true,
            'signature_data' => self::SIGNATURE,
            'buyer_name' => 'Khách',
            'buyer_phone' => '0900000004',
            'buyer_email' => 'khach@example.com',
        ])->assertOk();

        Carbon::setTestNow('2026-11-10 10:31:00');

        $this->as($this->member)->postJson('/api/v1/payments', ['order_code' => $signed, 'payment_method' => 'momo'])->assertUnprocessable();
        $this->assertSame(2, app(EventService::class)->releaseExpiredHolds());
        $this->assertSame(0, app(EventService::class)->releaseExpiredHolds());

        $this->assertTrue(Order::query()->where('code', $signed)->first()->status->is(OrderStatus::Cancelled));
        $this->assertDatabaseHas('event_registrations', ['user_id' => $this->other->id, 'status' => 'cancelled']);
        $this->as($this->other)->postJson("/api/v1/orders/{$unsigned}/accept-terms", [
            'terms_accepted' => true,
            'signature_data' => self::SIGNATURE,
            'buyer_name' => 'Khách',
            'buyer_phone' => '0900000004',
            'buyer_email' => 'khach@example.com',
        ])->assertUnprocessable();
        $this->as($this->member)->postJson('/api/v1/payments', ['order_code' => $signed, 'payment_method' => 'momo'])->assertUnprocessable();

        $this->createOrderFor($event);
    }

    public function test_quantity_and_sale_window_rules(): void
    {
        $client = $this->as($this->member);
        $big = $this->createEvent(['total_tickets' => 100]);
        $closed = $this->createEvent(['status' => EventStatus::Closed]);
        $past = $this->createEvent(['event_date' => '2026-11-01 09:00:00']);

        $client->postJson('/api/v1/orders', ['type' => 'event', 'event_id' => $big->id, 'quantity' => 21])->assertUnprocessable();
        $client->postJson('/api/v1/orders', ['type' => 'event', 'event_id' => $closed->id, 'quantity' => 1])->assertUnprocessable();
        $client->postJson('/api/v1/orders', ['type' => 'event', 'event_id' => $past->id, 'quantity' => 1])->assertUnprocessable();
    }

    public function test_admin_crud_and_validation(): void
    {
        $body = ['name' => 'Yoga Day', 'description' => 'Mô tả', 'event_date' => '2026-12-01T09:00', 'location' => 'Sảnh', 'ticket_price' => 0, 'total_tickets' => 10];
        $admin = $this->as($this->admin);

        $id = $admin->postJson('/api/v1/admin/events', $body)->assertCreated()->assertJsonPath('data.status', 'open')->json('data.id');
        $admin->postJson('/api/v1/admin/events', [...$body, 'event_date' => '2026-11-01T09:00'])->assertUnprocessable();
        $admin->patchJson("/api/v1/admin/events/{$id}", [...$body, 'name' => 'Tên mới'])->assertOk()->assertJsonPath('data.name', 'Tên mới');
        $admin->getJson('/api/v1/admin/events?q=Tên mới')->assertOk()->assertJsonCount(1, 'data');
        $admin->getJson('/api/v1/admin/events?status=closed')->assertOk()->assertJsonCount(0, 'data');

        $this->as($this->member)->getJson('/api/v1/admin/events')->assertForbidden();
    }

    public function test_total_tickets_cannot_go_below_sold_and_held(): void
    {
        $event = $this->createEvent();
        $this->createOrderFor($event, quantity: 3);
        $body = ['name' => 'Workshop', 'event_date' => '2026-12-20T09:00', 'location' => 'Sảnh A', 'ticket_price' => 300000];
        $admin = $this->as($this->admin);

        $admin->patchJson("/api/v1/admin/events/{$event->id}", [...$body, 'total_tickets' => 2])->assertUnprocessable();
        $admin->patchJson("/api/v1/admin/events/{$event->id}", [...$body, 'total_tickets' => 3])->assertOk();
    }

    public function test_checkin_only_paid_once_and_lists_registrations(): void
    {
        $event = $this->createEvent();
        $this->signAndPay($this->createOrderFor($event));
        $this->createOrderFor($event, $this->other);

        $admin = $this->as($this->admin);
        $list = $admin->getJson("/api/v1/admin/events/{$event->id}/registrations")->assertOk()->assertJsonCount(2, 'data')->json('data');
        $pending = collect($list)->firstWhere('status', 'pending');
        $paid = collect($list)->firstWhere('status', 'paid');

        $admin->patchJson("/api/v1/admin/registrations/{$pending['id']}/checkin")->assertUnprocessable();
        $admin->patchJson("/api/v1/admin/registrations/{$paid['id']}/checkin")->assertOk()->assertJsonPath('data.member_phone', '0900000004');
        $admin->patchJson("/api/v1/admin/registrations/{$paid['id']}/checkin")->assertUnprocessable();

        $this->as($this->member)->patchJson("/api/v1/admin/registrations/{$paid['id']}/checkin")->assertForbidden();
    }

    public function test_cancel_event_cancels_tickets_notifies_and_flags_refund(): void
    {
        $event = $this->createEvent();
        $paidCode = $this->createOrderFor($event);
        $this->signAndPay($paidCode);
        $pendingCode = $this->createOrderFor($event, $this->other);

        $admin = $this->as($this->admin);
        $admin->patchJson("/api/v1/admin/events/{$event->id}/status", ['status' => 'cancelled', 'reason' => 'Mưa bão'])
            ->assertOk()
            ->assertJsonPath('data.status', 'cancelled')
            ->assertJsonPath('data.cancel_reason', 'Mưa bão')
            ->assertJsonPath('data.sold_tickets', 0)
            ->assertJsonPath('data.revenue', 300000);

        $registrations = collect($admin->getJson("/api/v1/admin/events/{$event->id}/registrations")->json('data'));
        $this->assertTrue($registrations->firstWhere('member_email', $this->member->email)['needs_refund']);
        $this->assertFalse($registrations->firstWhere('member_email', $this->other->email)['needs_refund']);

        $this->assertTrue(Order::query()->where('code', $paidCode)->first()->status->is(OrderStatus::Success));
        $this->assertTrue(Order::query()->where('code', $pendingCode)->first()->status->is(OrderStatus::Cancelled));
        $this->assertTrue(UserNotification::query()->where('user_id', $this->other->id)->where('message', 'like', '%Mưa bão%')->exists());

        $admin->patchJson("/api/v1/admin/events/{$event->id}/status", ['status' => 'open'])->assertUnprocessable();
        $admin->patchJson("/api/v1/admin/events/{$event->id}", ['name' => 'X', 'event_date' => '2026-12-20T09:00', 'location' => 'A', 'ticket_price' => 0, 'total_tickets' => 5])->assertUnprocessable();
        $this->as($this->other)->postJson('/api/v1/payments', ['order_code' => $pendingCode, 'payment_method' => 'momo'])->assertUnprocessable();
    }

    public function test_close_stops_sale_but_held_ticket_can_still_pay_and_reopen_works(): void
    {
        $event = $this->createEvent();
        $code = $this->createOrderFor($event);
        $admin = $this->as($this->admin);

        $admin->patchJson("/api/v1/admin/events/{$event->id}/status", ['status' => 'closed'])->assertOk();
        $this->as($this->other)->postJson('/api/v1/orders', ['type' => 'event', 'event_id' => $event->id, 'quantity' => 1])->assertUnprocessable();
        $this->signAndPay($code);

        $this->as($this->admin)->patchJson("/api/v1/admin/events/{$event->id}/status", ['status' => 'open'])->assertOk();
        $this->createOrderFor($event, $this->other);
    }

    public function test_member_with_event_ticket_cannot_delete_account(): void
    {
        $event = $this->createEvent(['ticket_price' => 0]);
        $client = $this->as($this->member);
        $client->postJson("/api/v1/events/{$event->id}/register")->assertCreated();

        $client->deleteJson('/api/v1/auth/me')->assertUnprocessable();
    }

    public function test_reminder_sent_once_for_paid_ticket(): void
    {
        $event = $this->createEvent(['ticket_price' => 0, 'event_date' => '2026-11-11 09:00:00']);
        $this->as($this->member)->postJson("/api/v1/events/{$event->id}/register")->assertCreated();

        $service = app(NotificationService::class);

        $this->assertSame(1, $service->sendReminders());
        $this->assertSame(0, $service->sendReminders());
    }
}
