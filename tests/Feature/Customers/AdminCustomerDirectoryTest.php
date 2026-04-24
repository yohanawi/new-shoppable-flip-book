<?php

namespace Tests\Feature\Customers;

use App\Models\BillingInvoice;
use App\Models\BillingTransaction;
use App\Models\CatalogPdf;
use App\Models\CatalogPdfEvent;
use App\Models\SupportTicket;
use App\Models\User;
use Database\Seeders\ProjectPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminCustomerDirectoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_dashboard_renders_customers_menu(): void
    {
        $this->seed(ProjectPermissionsSeeder::class);

        /** @var User $admin */
        $admin = User::factory()->create(['role' => 'admin']);
        $admin->assignRole('admin');

        $response = $this->actingAs($admin)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee(route('admin.customers.index'), false);
    }

    public function test_customer_cannot_access_admin_customer_routes(): void
    {
        $this->seed(ProjectPermissionsSeeder::class);

        /** @var User $customer */
        $customer = User::factory()->create(['role' => 'customer']);
        $customer->assignRole('customer');

        $dashboardResponse = $this->actingAs($customer)->get(route('dashboard'));

        $dashboardResponse->assertOk();
        $dashboardResponse->assertDontSee(route('admin.customers.index'), false);

        $this->actingAs($customer)
            ->get(route('admin.customers.index'))
            ->assertForbidden();
    }

    public function test_admin_can_open_customer_workspace_with_catalog_logs_and_billing_data(): void
    {
        $this->seed(ProjectPermissionsSeeder::class);

        /** @var User $admin */
        $admin = User::factory()->create(['role' => 'admin']);
        $admin->assignRole('admin');

        /** @var User $customer */
        $customer = User::factory()->create([
            'role' => 'customer',
            'name' => 'Acme Customer',
            'email' => 'acme@example.com',
            'stripe_id' => 'cus_12345',
            'last_login_at' => now()->subDay(),
            'last_login_ip' => '127.0.0.1',
        ]);
        $customer->assignRole('customer');

        $pdf = CatalogPdf::create([
            'user_id' => $customer->id,
            'title' => 'Spring Lookbook',
            'template_type' => CatalogPdf::TEMPLATE_UPLOADED,
            'visibility' => CatalogPdf::VISIBILITY_PUBLIC,
            'storage_disk' => 'public',
            'pdf_path' => 'catalog-pdfs/spring-lookbook.pdf',
            'original_filename' => 'spring-lookbook.pdf',
            'mime_type' => 'application/pdf',
            'size' => 2048,
        ]);

        CatalogPdfEvent::create([
            'catalog_pdf_id' => $pdf->id,
            'session_id' => 'session-123',
            'event_type' => CatalogPdfEvent::EVENT_BOOK_OPEN,
            'created_at' => now()->subHours(3),
        ]);

        CatalogPdfEvent::create([
            'catalog_pdf_id' => $pdf->id,
            'session_id' => 'session-123',
            'event_type' => CatalogPdfEvent::EVENT_READING_TIME,
            'meta' => ['duration_ms' => 120000],
            'created_at' => now()->subHours(2),
        ]);

        SupportTicket::create([
            'user_id' => $customer->id,
            'subject' => 'Need help with upload',
            'category' => 'technical_support',
            'priority' => 'high',
            'status' => 'open',
            'message' => 'Upload gets stuck at 90%.',
        ]);

        $invoice = BillingInvoice::create([
            'user_id' => $customer->id,
            'stripe_invoice_id' => 'in_12345',
            'number' => 'INV-2026-001',
            'currency' => 'usd',
            'amount_due' => 2900,
            'amount_paid' => 2900,
            'subtotal' => 2900,
            'tax' => 0,
            'status' => 'paid',
            'paid_at' => now()->subHours(5),
        ]);

        BillingTransaction::create([
            'user_id' => $customer->id,
            'invoice_id' => $invoice->id,
            'amount' => 2900,
            'currency' => 'usd',
            'type' => 'charge',
            'status' => 'succeeded',
            'description' => 'Monthly plan charge',
            'processed_at' => now()->subHours(5),
        ]);

        $indexResponse = $this->actingAs($admin)->get(route('admin.customers.index'));
        $indexResponse->assertOk();
        $indexResponse->assertSee('Customer Directory');
        $indexResponse->assertSee('Acme Customer');

        $showResponse = $this->actingAs($admin)->get(route('admin.customers.show', $customer));

        $showResponse->assertOk();
        $showResponse->assertSee('Customer Workspace');
        $showResponse->assertSee('Acme Customer');
        $showResponse->assertSee('Spring Lookbook');
        $showResponse->assertSee('Need help with upload');
        $showResponse->assertSee('INV-2026-001');
        $showResponse->assertSee('Monthly plan charge');
        $showResponse->assertSee('Viewer opened the catalog');
    }
}
