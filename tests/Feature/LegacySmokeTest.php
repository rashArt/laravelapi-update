<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LegacySmokeTest extends TestCase
{
    use RefreshDatabase;

    // -----------------------------------------------------------------------
    // Health
    // -----------------------------------------------------------------------

    public function test_health_retorna_status_200_y_estructura_esperada(): void
    {
        $response = $this->getJson('/api/health');

        $response->assertStatus(200)
                 ->assertJsonStructure(['status', 'database'])
                 ->assertJsonFragment(['status' => 'ok', 'database' => 'connected']);
    }

    // -----------------------------------------------------------------------
    // Login — credenciales válidas
    // -----------------------------------------------------------------------

    public function test_login_exitoso_devuelve_token_y_datos_de_usuario(): void
    {
        // Crear usuario de prueba con contraseña conocida
        $user = User::factory()->create([
            'email'    => 'test@example.com',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->postJson('/api/login', [
            'email'    => 'test@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'data' => [
                         'token',
                         'user' => ['id', 'name', 'email'],
                     ],
                 ])
                 ->assertJsonPath('data.user.email', 'test@example.com');
    }

    // -----------------------------------------------------------------------
    // Login — correo no registrado
    // -----------------------------------------------------------------------

    public function test_login_falla_con_correo_no_registrado(): void
    {
        $response = $this->postJson('/api/login', [
            'email'    => 'noexiste@example.com',
            'password' => 'cualquier-password',
        ]);

        $response->assertStatus(422)
                 ->assertJsonStructure(['errors' => ['email']]);
    }

    // -----------------------------------------------------------------------
    // Login — contraseña incorrecta
    // -----------------------------------------------------------------------

    public function test_login_falla_con_password_incorrecto(): void
    {
        User::factory()->create([
            'email'    => 'otro@example.com',
            'password' => Hash::make('correcta'),
        ]);

        $response = $this->postJson('/api/login', [
            'email'    => 'otro@example.com',
            'password' => 'incorrecta',
        ]);

        $response->assertStatus(422)
                 ->assertJsonStructure(['errors' => ['password']]);
    }
}
