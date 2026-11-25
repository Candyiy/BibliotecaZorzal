<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Fortify\Features;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->skipUnlessFortifyHas(Features::registration());
    }

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get(route('register'));

        $response->assertOk();
    }

    public function test_new_users_can_register(): void
    {
        $response = $this->post(route('register.store'), [
            'name' => 'John Doe',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertSessionHasNoErrors()
            ->assertRedirect(route('libros.index', absolute: false));

        $this->assertAuthenticated();
    }

    public function test_el_primer_usuario_registrado_es_super_admin(): void
    {
        $this->post(route('register.store'), [
            'name' => 'Primer usuario',
            'email' => 'primero@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertSessionHasNoErrors();

        $this->assertSame(User::ROL_SUPER_ADMIN, User::firstOrFail()->rol);
    }

    public function test_los_usuarios_posteriores_son_clientes(): void
    {
        User::factory()->create(['rol' => User::ROL_SUPER_ADMIN]);

        $this->post(route('register.store'), [
            'name' => 'Segundo usuario',
            'email' => 'segundo@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertSessionHasNoErrors();

        $this->assertSame(User::ROL_CLIENTE, User::where('email', 'segundo@example.com')->firstOrFail()->rol);
    }
}
