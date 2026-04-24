<?php

namespace Tests\Feature\Tickets;

use App\Models\SupportTicket;
use App\Models\SupportTicketCategory;
use App\Models\User;
use Database\Seeders\ProjectPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SupportTicketWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_create_ticket_with_attachment_and_managed_category(): void
    {
        $disk = Storage::fake('public');
        $this->seed(ProjectPermissionsSeeder::class);

        $customer = $this->createCustomer();
        $category = SupportTicketCategory::query()->where('slug', 'payment')->firstOrFail();

        $response = $this->actingAs($customer)->post(route('tickets.store'), [
            'subject' => 'Invoice mismatch',
            'category_id' => $category->id,
            'priority' => 'high',
            'message' => 'My invoice total is incorrect.',
            'attachment' => UploadedFile::fake()->create('invoice.pdf', 120, 'application/pdf'),
        ]);

        $response->assertRedirect(route('tickets.index'));

        $ticket = SupportTicket::query()->where('subject', 'Invoice mismatch')->firstOrFail();

        $this->assertSame($category->id, $ticket->support_ticket_category_id);
        $this->assertSame('payment', $ticket->category);
        $this->assertSame('invoice.pdf', $ticket->attachment_name);
        $this->assertNotNull($ticket->attachment_path);
        $this->assertTrue($disk->exists($ticket->attachment_path));
    }

    public function test_customer_cannot_open_another_customers_ticket(): void
    {
        $this->seed(ProjectPermissionsSeeder::class);

        $owner = $this->createCustomer();
        $otherCustomer = $this->createCustomer();

        $ticket = SupportTicket::query()->create([
            'user_id' => $owner->id,
            'subject' => 'Private ticket',
            'category' => 'technical',
            'priority' => 'medium',
            'status' => 'open',
            'message' => 'This should not be visible to another customer.',
        ]);

        $this->actingAs($otherCustomer)
            ->get(route('tickets.show', $ticket))
            ->assertForbidden();
    }

    public function test_admin_can_close_ticket_and_customer_can_submit_feedback(): void
    {
        $this->seed(ProjectPermissionsSeeder::class);

        $admin = $this->createAdmin();
        $customer = $this->createCustomer();

        $ticket = SupportTicket::query()->create([
            'user_id' => $customer->id,
            'subject' => 'Resolved issue',
            'category' => 'general',
            'priority' => 'low',
            'status' => 'in_progress',
            'message' => 'Issue details.',
        ]);

        $this->actingAs($admin)
            ->patch(route('tickets.status.update', $ticket), ['status' => 'closed'])
            ->assertRedirect();

        $ticket->refresh();

        $this->assertSame('closed', $ticket->status);
        $this->assertNotNull($ticket->closed_at);

        $this->actingAs($customer)
            ->post(route('tickets.feedback.store', $ticket), [
                'feedback_rating' => 5,
                'feedback_comment' => 'Fast and helpful support.',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('support_tickets', [
            'id' => $ticket->id,
            'feedback_rating' => 5,
            'feedback_comment' => 'Fast and helpful support.',
        ]);
    }

    public function test_admin_can_manage_ticket_categories_and_deleting_one_detaches_existing_tickets(): void
    {
        $this->seed(ProjectPermissionsSeeder::class);

        $admin = $this->createAdmin();
        $customer = $this->createCustomer();

        $this->actingAs($admin)
            ->post(route('tickets.categories.store'), [
                'name' => 'Technical Escalation',
                'description' => 'Complex issues that need escalation.',
                'is_active' => '1',
            ])
            ->assertRedirect();

        $category = SupportTicketCategory::query()->where('slug', 'technical-escalation')->firstOrFail();

        $this->actingAs($admin)
            ->put(route('tickets.categories.update', $category), [
                'name' => 'Escalated Technical',
                'slug' => 'escalated-technical',
                'description' => 'Escalated issues only.',
                'is_active' => '0',
            ])
            ->assertRedirect();

        $category->refresh();

        $this->assertSame('Escalated Technical', $category->name);
        $this->assertSame('escalated-technical', $category->slug);
        $this->assertFalse($category->is_active);

        $ticket = SupportTicket::query()->create([
            'user_id' => $customer->id,
            'support_ticket_category_id' => $category->id,
            'subject' => 'Needs escalation',
            'category' => $category->slug,
            'priority' => 'high',
            'status' => 'open',
            'message' => 'Complex issue details.',
        ]);

        $this->actingAs($admin)
            ->delete(route('tickets.categories.destroy', $category))
            ->assertRedirect();

        $this->assertDatabaseMissing('support_ticket_categories', ['id' => $category->id]);
        $this->assertDatabaseHas('support_tickets', [
            'id' => $ticket->id,
            'support_ticket_category_id' => null,
            'category' => 'escalated-technical',
        ]);
    }

    private function createAdmin(): User
    {
        $user = User::factory()->create(['role' => 'admin']);
        $user->assignRole('admin');

        return $user;
    }

    private function createCustomer(): User
    {
        $user = User::factory()->create(['role' => 'customer']);
        $user->assignRole('customer');

        return $user;
    }
}
