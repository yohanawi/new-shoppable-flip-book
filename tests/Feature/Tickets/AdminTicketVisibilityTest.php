<?php

namespace Tests\Feature\Tickets;

use App\Models\SupportTicket;
use App\Models\SupportTicketMessage;
use App\Models\User;
use Database\Seeders\ProjectPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminTicketVisibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_see_customer_submitted_tickets_in_the_ticket_list(): void
    {
        $this->seed(ProjectPermissionsSeeder::class);

        /** @var User $admin */
        $admin = User::factory()->create(['role' => 'admin']);
        $admin->assignRole('admin');

        /** @var User $customer */
        $customer = User::factory()->create(['role' => 'customer']);
        $customer->assignRole('customer');

        SupportTicket::query()->create([
            'user_id' => $customer->id,
            'subject' => 'Customer needs help',
            'category' => 'technical',
            'priority' => 'high',
            'status' => 'open',
            'message' => 'The uploaded PDF is not showing.',
        ]);

        $response = $this->actingAs($admin)->get(route('tickets.index'));

        $response->assertOk();
        $response->assertSee('Customer needs help');
        $response->assertSee($customer->email);
    }

    public function test_admin_can_open_customer_ticket_and_see_the_original_message(): void
    {
        $this->seed(ProjectPermissionsSeeder::class);

        /** @var User $admin */
        $admin = User::factory()->create(['role' => 'admin']);
        $admin->assignRole('admin');

        /** @var User $customer */
        $customer = User::factory()->create(['role' => 'customer']);
        $customer->assignRole('customer');

        $ticket = SupportTicket::query()->create([
            'user_id' => $customer->id,
            'subject' => 'Need billing help',
            'category' => 'billing',
            'priority' => 'medium',
            'status' => 'open',
            'message' => 'I was charged twice.',
        ]);

        $response = $this->actingAs($admin)->get(route('tickets.show', $ticket));

        $response->assertOk();
        $response->assertSee('I was charged twice.');
        $response->assertSee($customer->email);
    }

    public function test_admin_reply_marks_message_as_admin_and_updates_ticket_status(): void
    {
        $this->seed(ProjectPermissionsSeeder::class);

        /** @var User $admin */
        $admin = User::factory()->create(['role' => 'admin']);
        $admin->assignRole('admin');

        /** @var User $customer */
        $customer = User::factory()->create(['role' => 'customer']);
        $customer->assignRole('customer');

        $ticket = SupportTicket::query()->create([
            'user_id' => $customer->id,
            'subject' => 'Question about sharing',
            'category' => 'general',
            'priority' => 'low',
            'status' => 'open',
            'message' => 'How do I share my catalog?',
        ]);

        $response = $this->actingAs($admin)->post(route('tickets.reply', $ticket), [
            'message' => 'Please open the catalog and use the share button.',
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('support_ticket_messages', [
            'support_ticket_id' => $ticket->id,
            'user_id' => $admin->id,
            'is_admin' => true,
            'message' => 'Please open the catalog and use the share button.',
        ]);

        $this->assertDatabaseHas('support_tickets', [
            'id' => $ticket->id,
            'status' => 'in_progress',
        ]);
    }

    public function test_customer_ticket_creation_stores_the_initial_message_in_the_thread(): void
    {
        $this->seed(ProjectPermissionsSeeder::class);

        /** @var User $customer */
        $customer = User::factory()->create(['role' => 'customer']);
        $customer->assignRole('customer');

        $this->actingAs($customer)
            ->post(route('tickets.store'), [
                'subject' => 'Catalog issue',
                'category' => 'technical',
                'priority' => 'high',
                'message' => 'The slicer preview is blank.',
            ])
            ->assertRedirect(route('tickets.index'));

        $ticket = SupportTicket::query()->where('subject', 'Catalog issue')->firstOrFail();

        $this->assertDatabaseHas('support_ticket_messages', [
            'support_ticket_id' => $ticket->id,
            'user_id' => $customer->id,
            'is_admin' => false,
            'message' => 'The slicer preview is blank.',
        ]);
    }
}
