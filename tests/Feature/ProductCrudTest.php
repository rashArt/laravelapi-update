<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductCrudTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        // Usuario con token conocido para autenticación legacy
        $this->user = User::factory()->create([
            'api_token' => 'test-token-12345',
        ]);

        // Categoría base para asociar productos
        $this->category = Category::factory()->create();
    }

    // Cabeceras comunes de autenticación
    private function authHeaders(): array
    {
        return [
            'Authorization' => 'Bearer test-token-12345',
            'Accept'        => 'application/json',
        ];
    }

    // Payload válido base para crear un producto
    private function productPayload(array $overrides = []): array
    {
        return array_merge([
            'name'        => 'Producto de Prueba',
            'description' => 'Descripción de prueba',
            'price'       => 99.99,
            'stock'       => 10,
            'status'      => true,
            'category_id' => $this->category->id,
        ], $overrides);
    }

    // -----------------------------------------------------------------------
    // INDEX — Listado paginado de productos
    // -----------------------------------------------------------------------

    public function test_index_retorna_listado_paginado_de_productos(): void
    {
        Product::factory()->count(5)->create(['category_id' => $this->category->id]);

        $response = $this->getJson('/api/products', $this->authHeaders());

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'status',
                     'data' => [
                         '*' => ['id', 'name', 'description', 'price', 'stock', 'status', 'category_id', 'created_at', 'updated_at'],
                     ],
                     'links' => ['first', 'last', 'prev', 'next'],
                     'meta'  => ['path', 'per_page', 'next_cursor', 'prev_cursor'],
                 ]);
    }

    public function test_index_filtra_por_nombre(): void
    {
        Product::factory()->create(['name' => 'Laptop Pro', 'category_id' => $this->category->id]);
        Product::factory()->create(['name' => 'Mouse USB', 'category_id' => $this->category->id]);

        $response = $this->getJson('/api/products?name=Laptop', $this->authHeaders());

        $response->assertStatus(200)
                 ->assertJsonFragment(['name' => 'Laptop Pro'])
                 ->assertJsonMissing(['name' => 'Mouse USB']);
    }

    public function test_index_filtra_por_category_id(): void
    {
        $otraCategoria = Category::factory()->create();
        Product::factory()->create(['name' => 'En Mi Cat', 'category_id' => $this->category->id]);
        Product::factory()->create(['name' => 'En Otra Cat', 'category_id' => $otraCategoria->id]);

        $response = $this->getJson("/api/products?category_id={$this->category->id}", $this->authHeaders());

        $response->assertStatus(200)
                 ->assertJsonFragment(['name' => 'En Mi Cat'])
                 ->assertJsonMissing(['name' => 'En Otra Cat']);
    }

    public function test_index_filtra_por_status(): void
    {
        Product::factory()->create(['name' => 'Activo', 'status' => true, 'category_id' => $this->category->id]);
        Product::factory()->create(['name' => 'Inactivo', 'status' => false, 'category_id' => $this->category->id]);

        $response = $this->getJson('/api/products?status=1', $this->authHeaders());

        $response->assertStatus(200)
                 ->assertJsonFragment(['name' => 'Activo'])
                 ->assertJsonMissing(['name' => 'Inactivo']);
    }

    public function test_index_rechaza_per_page_invalido(): void
    {
        $response = $this->getJson('/api/products?per_page=0', $this->authHeaders());

        $response->assertStatus(422);
    }

    public function test_index_rechaza_category_id_inexistente(): void
    {
        $response = $this->getJson('/api/products?category_id=99999', $this->authHeaders());

        $response->assertStatus(422);
    }

    // -----------------------------------------------------------------------
    // STORE — Creación de producto
    // -----------------------------------------------------------------------

    public function test_store_crea_producto_con_datos_validos(): void
    {
        $response = $this->postJson('/api/products', $this->productPayload(), $this->authHeaders());

        $response->assertStatus(201)
                 ->assertJsonStructure([
                     'status',
                     'data' => ['id', 'name', 'description', 'price', 'stock', 'status', 'category_id', 'created_at', 'updated_at'],
                 ])
                 ->assertJsonPath('data.name', 'Producto de Prueba')
                 ->assertJsonPath('data.price', '99.99')
                 ->assertJsonPath('data.stock', 10);

        $this->assertDatabaseHas('products', ['name' => 'Producto de Prueba']);
    }

    public function test_store_crea_producto_sin_categoria(): void
    {
        $payload = $this->productPayload(['category_id' => null]);

        $response = $this->postJson('/api/products', $payload, $this->authHeaders());

        $response->assertStatus(201);
        $this->assertDatabaseHas('products', ['name' => 'Producto de Prueba', 'category_id' => null]);
    }

    public function test_store_falla_si_falta_el_nombre(): void
    {
        $payload = $this->productPayload(['name' => '']);

        $response = $this->postJson('/api/products', $payload, $this->authHeaders());

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['name']);
    }

    public function test_store_falla_si_falta_el_precio(): void
    {
        $payload = $this->productPayload();
        unset($payload['price']);

        $response = $this->postJson('/api/products', $payload, $this->authHeaders());

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['price']);
    }

    public function test_store_falla_si_precio_es_negativo(): void
    {
        $response = $this->postJson('/api/products', $this->productPayload(['price' => -1]), $this->authHeaders());

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['price']);
    }

    public function test_store_falla_si_stock_es_negativo(): void
    {
        $response = $this->postJson('/api/products', $this->productPayload(['stock' => -5]), $this->authHeaders());

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['stock']);
    }

    public function test_store_falla_si_category_id_no_existe(): void
    {
        $response = $this->postJson('/api/products', $this->productPayload(['category_id' => 99999]), $this->authHeaders());

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['category_id']);
    }

    public function test_store_falla_si_nombre_excede_255_caracteres(): void
    {
        $response = $this->postJson('/api/products', $this->productPayload(['name' => str_repeat('a', 256)]), $this->authHeaders());

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['name']);
    }

    // -----------------------------------------------------------------------
    // SHOW — Detalle de producto
    // -----------------------------------------------------------------------

    public function test_show_retorna_producto_existente_con_categoria(): void
    {
        $product = Product::factory()->create(['category_id' => $this->category->id]);

        $response = $this->getJson("/api/products/{$product->id}", $this->authHeaders());

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'status',
                     'data' => ['id', 'name', 'price', 'stock', 'status', 'category_id'],
                 ])
                 ->assertJsonPath('data.id', $product->id);
    }

    public function test_show_retorna_404_si_producto_no_existe(): void
    {
        $response = $this->getJson('/api/products/99999', $this->authHeaders());

        $response->assertStatus(404)
                 ->assertJsonFragment(['status' => false]);
    }

    // -----------------------------------------------------------------------
    // UPDATE — Actualización de producto
    // -----------------------------------------------------------------------

    public function test_update_modifica_producto_existente(): void
    {
        $product = Product::factory()->create([
            'name'        => 'Original',
            'price'       => 50.00,
            'category_id' => $this->category->id,
        ]);

        $response = $this->putJson("/api/products/{$product->id}", [
            'name'  => 'Modificado',
            'price' => 75.50,
        ], $this->authHeaders());

        $response->assertStatus(200)
                 ->assertJsonPath('data.name', 'Modificado')
                 ->assertJsonPath('data.price', '75.50');

        $this->assertDatabaseHas('products', ['id' => $product->id, 'name' => 'Modificado']);
    }

    public function test_update_falla_si_precio_es_negativo(): void
    {
        $product = Product::factory()->create(['category_id' => $this->category->id]);

        $response = $this->putJson("/api/products/{$product->id}", [
            'price' => -10,
        ], $this->authHeaders());

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['price']);
    }

    public function test_update_falla_si_category_id_no_existe(): void
    {
        $product = Product::factory()->create(['category_id' => $this->category->id]);

        $response = $this->putJson("/api/products/{$product->id}", [
            'category_id' => 99999,
        ], $this->authHeaders());

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['category_id']);
    }

    public function test_update_retorna_404_si_producto_no_existe(): void
    {
        $response = $this->putJson('/api/products/99999', [
            'name' => 'No existe',
        ], $this->authHeaders());

        $response->assertStatus(404);
    }

    // -----------------------------------------------------------------------
    // DESTROY — Eliminación de producto
    // -----------------------------------------------------------------------

    public function test_destroy_elimina_producto_existente(): void
    {
        $product = Product::factory()->create(['category_id' => $this->category->id]);

        $response = $this->deleteJson("/api/products/{$product->id}", [], $this->authHeaders());

        $response->assertStatus(204);
        $this->assertDatabaseMissing('products', ['id' => $product->id]);
    }

    public function test_destroy_retorna_404_si_producto_no_existe(): void
    {
        $response = $this->deleteJson('/api/products/99999', [], $this->authHeaders());

        $response->assertStatus(404);
    }
}
