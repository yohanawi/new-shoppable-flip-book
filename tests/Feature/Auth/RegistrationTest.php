<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Providers\RouteServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered()
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_new_users_can_register()
    {
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('catalog.pdfs.create'));
    }

    public function test_new_users_are_registered_as_customers_and_can_open_catalog_upload(): void
    {
        $this->post('/register', [
            'name' => 'Catalog Customer',
            'email' => 'catalog@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        /** @var User $user */
        $user = User::query()->where('email', 'catalog@example.com')->firstOrFail();

        $this->assertSame('customer', $user->role);
        $this->assertTrue($user->isCustomer());

        $response = $this->actingAs($user)->get(route('catalog.pdfs.create'));

        $response->assertOk();
    }
}
