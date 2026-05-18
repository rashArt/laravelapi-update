<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryCrudTest extends TestCase
{
    use RefreshDatabase;

    // Usuario autenticado reutilizable en cada test
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        // Crear usuario con token conocido para autenticación legacy
        $this->user = User::factory()->create([
            'api_token' => 'test-token-12345',
        ]);
    }

    // Cabeceras comunes de autenticación
    private function authHeaders(): array
    {
        return [
            'Authorization' => 'Bearer test-token-12345',
            'Accept'        => 'application/json',
        ];
    }

    // -----------------------------------------------------------------------
    // INDEX — Listado paginado de categorías
    // -----------------------------------------------------------------------

    public function test_index_retorna_listado_paginado_de_categorias(): void
    {
        Category::factory()->count(5)->create();

        $response = $this->getJson('/api/categories', $this->authHeaders());

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'status',
                     'data' => [
                         '*' => ['id', 'name', 'description', 'status', 'created_at', 'updated_at'],
                     ],
                     'links' => ['first', 'last', 'prev', 'next'],
                     'meta'  => ['path', 'per_page', 'next_cursor', 'prev_cursor'],
                 ]);
    }

    public function test_index_filtra_por_nombre(): void
    {
        Category::factory()->create(['name' => 'Electrónica']);
        Category::factory()->create(['name' => 'Ropa']);

        $response = $this->getJson('/api/categories?name=Electrónica', $this->authHeaders());

        $response->assertStatus(200)
                 ->assertJsonFragment(['name' => 'Electrónica'])
                 ->assertJsonMissing(['name' => 'Ropa']);
    }

    public function test_index_filtra_por_status(): void
    {
        Category::factory()->create(['name' => 'Activa', 'status' => true]);
        Category::factory()->create(['name' => 'Inactiva', 'status' => false]);

        $response = $this->getJson('/api/categories?status=1', $this->authHeaders());

        $response->assertStatus(200)
                 ->assertJsonFragment(['name' => 'Activa'])
                 ->assertJsonMissing(['name' => 'Inactiva']);
    }

    public function test_index_rechaza_per_page_invalido(): void
    {
        $response = $this->getJson('/api/categories?per_page=0', $this->authHeaders());

        $response->assertStatus(422);
    }

    // -----------------------------------------------------------------------
    // STORE — Creación de categoría
    // -----------------------------------------------------------------------

    public function test_store_crea_categoria_con_datos_validos(): void
    {
        $payload = [
            'name'        => 'Nueva Categoría',
            'description' => 'Descripción de prueba',
            'status'      => true,
        ];

        $response = $this->postJson('/api/categories', $payload, $this->authHeaders());

        $response->assertStatus(201)
                 ->assertJsonStructure([
                     'status',
                     'data' => ['id', 'name', 'description', 'status', 'created_at', 'updated_at'],
                 ])
                 ->assertJsonPath('data.name', 'Nueva Categoría')
                 ->assertJsonPath('data.status', true);

        $this->assertDatabaseHas('categories', ['name' => 'Nueva Categoría']);
    }

    public function test_store_usa_status_true_por_defecto(): void
    {
        $response = $this->postJson('/api/categories', [
            'name' => 'Sin Status',
        ], $this->authHeaders());

        $response->assertStatus(201);
        $this->assertDatabaseHas('categories', ['name' => 'Sin Status']);
    }

    public function test_store_falla_si_falta_el_nombre(): void
    {
        $response = $this->postJson('/api/categories', [
            'description' => 'Sin nombre',
        ], $this->authHeaders());

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['name']);
    }

    public function test_store_falla_si_nombre_ya_existe(): void
    {
        Category::factory()->create(['name' => 'Duplicada']);

        $response = $this->postJson('/api/categories', [
            'name' => 'Duplicada',
        ], $this->authHeaders());

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['name']);
    }

    public function test_store_falla_si_nombre_excede_255_caracteres(): void
    {
        $response = $this->postJson('/api/categories', [
            'name' => str_repeat('a', 256),
        ], $this->authHeaders());

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['name']);
    }

    // -----------------------------------------------------------------------
    // SHOW — Detalle de categoría
    // -----------------------------------------------------------------------

    public function test_show_retorna_categoria_existente(): void
    {
        $category = Category::factory()->create();

        $response = $this->getJson("/api/categories/{$category->id}", $this->authHeaders());

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'status',
                     'data' => ['id', 'name', 'description', 'status', 'created_at', 'updated_at'],
                 ])
                 ->assertJsonPath('data.id', $category->id);
    }

    public function test_show_retorna_404_si_categoria_no_existe(): void
    {
        $response = $this->getJson('/api/categories/99999', $this->authHeaders());

        $response->assertStatus(404)
                 ->assertJsonFragment(['status' => false]);
    }

    // -----------------------------------------------------------------------
    // UPDATE — Actualización de categoría
    // -----------------------------------------------------------------------

    public function test_update_modifica_categoria_existente(): void
    {
        $category = Category::factory()->create(['name' => 'Original', 'status' => true]);

        $response = $this->putJson("/api/categories/{$category->id}", [
            'name'   => 'Modificada',
            'status' => false,
        ], $this->authHeaders());

        $response->assertStatus(200)
                 ->assertJsonPath('data.name', 'Modificada')
                 ->assertJsonPath('data.status', false);

        $this->assertDatabaseHas('categories', ['id' => $category->id, 'name' => 'Modificada']);
    }

    public function test_update_permite_mantener_el_mismo_nombre(): void
    {
        $category = Category::factory()->create(['name' => 'Sin Cambio']);

        $response = $this->putJson("/api/categories/{$category->id}", [
            'name' => 'Sin Cambio',
        ], $this->authHeaders());

        $response->assertStatus(200)
                 ->assertJsonPath('data.name', 'Sin Cambio');
    }

    public function test_update_falla_si_nombre_pertenece_a_otra_categoria(): void
    {
        Category::factory()->create(['name' => 'Existente']);
        $category = Category::factory()->create(['name' => 'Propia']);

        $response = $this->putJson("/api/categories/{$category->id}", [
            'name' => 'Existente',
        ], $this->authHeaders());

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['name']);
    }

    public function test_update_retorna_404_si_categoria_no_existe(): void
    {
        $response = $this->putJson('/api/categories/99999', [
            'name' => 'No existe',
        ], $this->authHeaders());

        $response->assertStatus(404);
    }

    // -----------------------------------------------------------------------
    // DESTROY — Eliminación de categoría
    // -----------------------------------------------------------------------

    public function test_destroy_elimina_categoria_existente(): void
    {
        $category = Category::factory()->create();

        $response = $this->deleteJson("/api/categories/{$category->id}", [], $this->authHeaders());

        $response->assertStatus(204);
        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
    }

    public function test_destroy_retorna_404_si_categoria_no_existe(): void
    {
        $response = $this->deleteJson('/api/categories/99999', [], $this->authHeaders());

        $response->assertStatus(404);
    }
}
