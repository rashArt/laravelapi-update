<?php

namespace Tests\Feature;

use App\Enums\StockMovementType;
use App\Models\Category;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockMovementCrudTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        // Usuario con token conocido para autenticación legacy
        $this->user = User::factory()->create([
            'api_token' => 'test-token-12345',
        ]);

        // Producto base con stock conocido para todas las pruebas
        $this->product = Product::factory()->create([
            'stock'       => 100,
            'category_id' => Category::factory()->create()->id,
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

    // URL base del recurso de movimientos para el producto base
    private function baseUrl(?int $productId = null): string
    {
        $id = $productId ?? $this->product->id;
        return "/api/products/{$id}/stock-movements";
    }

    // -----------------------------------------------------------------------
    // INDEX — Listado paginado de movimientos
    // -----------------------------------------------------------------------

    public function test_index_retorna_listado_paginado_de_movimientos(): void
    {
        StockMovement::factory()->count(5)->create([
            'product_id' => $this->product->id,
            'user_id'    => $this->user->id,
        ]);

        $response = $this->getJson($this->baseUrl(), $this->authHeaders());

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'status',
                     'data' => [
                         '*' => ['id', 'product_id', 'type', 'quantity', 'reason', 'user', 'created_at', 'updated_at'],
                     ],
                     'links' => ['first', 'last', 'prev', 'next'],
                     'meta'  => ['path', 'per_page', 'next_cursor', 'prev_cursor'],
                 ])
                 ->assertJsonPath('status', true);
    }

    public function test_index_filtra_por_tipo_entrada(): void
    {
        StockMovement::factory()->entrada()->count(3)->create([
            'product_id' => $this->product->id,
            'user_id'    => $this->user->id,
        ]);
        StockMovement::factory()->salida()->count(2)->create([
            'product_id' => $this->product->id,
            'user_id'    => $this->user->id,
        ]);

        $response = $this->getJson($this->baseUrl() . '?type=entrada', $this->authHeaders());

        $response->assertStatus(200);

        // Todos los registros devueltos deben ser de tipo entrada
        collect($response->json('data'))->each(
            fn ($item) => $this->assertEquals('entrada', $item['type'])
        );
    }

    public function test_index_filtra_por_tipo_salida(): void
    {
        StockMovement::factory()->entrada()->count(3)->create([
            'product_id' => $this->product->id,
            'user_id'    => $this->user->id,
        ]);
        StockMovement::factory()->salida()->count(2)->create([
            'product_id' => $this->product->id,
            'user_id'    => $this->user->id,
        ]);

        $response = $this->getJson($this->baseUrl() . '?type=salida', $this->authHeaders());

        $response->assertStatus(200);

        collect($response->json('data'))->each(
            fn ($item) => $this->assertEquals('salida', $item['type'])
        );
    }

    public function test_index_filtra_por_user_id(): void
    {
        $otroUsuario = User::factory()->create();

        StockMovement::factory()->count(3)->create([
            'product_id' => $this->product->id,
            'user_id'    => $this->user->id,
        ]);
        StockMovement::factory()->count(2)->create([
            'product_id' => $this->product->id,
            'user_id'    => $otroUsuario->id,
        ]);

        $response = $this->getJson($this->baseUrl() . "?user_id={$this->user->id}", $this->authHeaders());

        $response->assertStatus(200);

        // Solo deben aparecer movimientos del usuario filtrado
        $this->assertCount(3, $response->json('data'));
    }

    public function test_index_filtra_por_rango_de_fechas(): void
    {
        // Movimiento en fecha pasada
        StockMovement::factory()->create([
            'product_id' => $this->product->id,
            'user_id'    => $this->user->id,
            'created_at' => '2025-01-15 10:00:00',
        ]);

        // Movimiento en fecha dentro del rango
        StockMovement::factory()->create([
            'product_id' => $this->product->id,
            'user_id'    => $this->user->id,
            'created_at' => '2025-06-10 10:00:00',
        ]);

        $response = $this->getJson(
            $this->baseUrl() . '?date_from=2025-06-01&date_to=2025-06-30',
            $this->authHeaders()
        );

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
    }

    public function test_index_respeta_per_page(): void
    {
        StockMovement::factory()->count(10)->create([
            'product_id' => $this->product->id,
            'user_id'    => $this->user->id,
        ]);

        $response = $this->getJson($this->baseUrl() . '?per_page=3', $this->authHeaders());

        $response->assertStatus(200);
        $this->assertCount(3, $response->json('data'));
    }

    public function test_index_rechaza_tipo_invalido(): void
    {
        $response = $this->getJson($this->baseUrl() . '?type=invalido', $this->authHeaders());

        $response->assertStatus(422);
    }

    public function test_index_retorna_404_si_producto_no_existe(): void
    {
        $response = $this->getJson($this->baseUrl(99999), $this->authHeaders());

        $response->assertStatus(404);
    }

    public function test_index_requiere_autenticacion(): void
    {
        $response = $this->getJson($this->baseUrl());

        $response->assertStatus(401);
    }

    // -----------------------------------------------------------------------
    // STORE — Registro de movimiento de stock
    // -----------------------------------------------------------------------

    public function test_store_registra_entrada_y_aumenta_stock(): void
    {
        $stockInicial = $this->product->stock; // 100

        $response = $this->postJson($this->baseUrl(), [
            'type'     => 'entrada',
            'quantity' => 20,
            'reason'   => 'Reposición de inventario',
        ], $this->authHeaders());

        $response->assertStatus(201)
                 ->assertJsonStructure([
                     'status',
                     'data' => ['id', 'product_id', 'type', 'quantity', 'reason', 'user', 'created_at'],
                 ])
                 ->assertJsonPath('data.type', 'entrada')
                 ->assertJsonPath('data.quantity', 20)
                 ->assertJsonPath('status', true);

        // Verificar que el stock se actualizó correctamente en la base de datos
        $this->assertEquals($stockInicial + 20, $this->product->fresh()->stock);
    }

    public function test_store_registra_salida_y_disminuye_stock(): void
    {
        $stockInicial = $this->product->stock; // 100

        $response = $this->postJson($this->baseUrl(), [
            'type'     => 'salida',
            'quantity' => 30,
            'reason'   => 'Venta al cliente',
        ], $this->authHeaders());

        $response->assertStatus(201)
                 ->assertJsonPath('data.type', 'salida')
                 ->assertJsonPath('data.quantity', 30);

        $this->assertEquals($stockInicial - 30, $this->product->fresh()->stock);
    }

    public function test_store_asigna_usuario_autenticado_automaticamente(): void
    {
        $response = $this->postJson($this->baseUrl(), [
            'type'     => 'entrada',
            'quantity' => 10,
        ], $this->authHeaders());

        $response->assertStatus(201);

        // El movimiento debe estar asociado al usuario autenticado
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $this->product->id,
            'user_id'    => $this->user->id,
            'type'       => 'entrada',
            'quantity'   => 10,
        ]);
    }

    public function test_store_rechaza_salida_con_stock_insuficiente(): void
    {
        // El producto tiene 100 de stock; intentar sacar más de lo disponible
        $response = $this->postJson($this->baseUrl(), [
            'type'     => 'salida',
            'quantity' => 150,
        ], $this->authHeaders());

        $response->assertStatus(422);

        // El stock no debe haber cambiado
        $this->assertEquals(100, $this->product->fresh()->stock);
    }

    public function test_store_rechaza_cantidad_cero(): void
    {
        $response = $this->postJson($this->baseUrl(), [
            'type'     => 'entrada',
            'quantity' => 0,
        ], $this->authHeaders());

        $response->assertStatus(422);
    }

    public function test_store_rechaza_cantidad_negativa(): void
    {
        $response = $this->postJson($this->baseUrl(), [
            'type'     => 'salida',
            'quantity' => -5,
        ], $this->authHeaders());

        $response->assertStatus(422);
    }

    public function test_store_rechaza_tipo_invalido(): void
    {
        $response = $this->postJson($this->baseUrl(), [
            'type'     => 'devolucion',
            'quantity' => 10,
        ], $this->authHeaders());

        $response->assertStatus(422);
    }

    public function test_store_rechaza_payload_sin_tipo(): void
    {
        $response = $this->postJson($this->baseUrl(), [
            'quantity' => 10,
        ], $this->authHeaders());

        $response->assertStatus(422);
    }

    public function test_store_rechaza_payload_sin_cantidad(): void
    {
        $response = $this->postJson($this->baseUrl(), [
            'type' => 'entrada',
        ], $this->authHeaders());

        $response->assertStatus(422);
    }

    public function test_store_acepta_reason_nulo(): void
    {
        $response = $this->postJson($this->baseUrl(), [
            'type'     => 'entrada',
            'quantity' => 5,
            'reason'   => null,
        ], $this->authHeaders());

        $response->assertStatus(201);
    }

    public function test_store_retorna_404_si_producto_no_existe(): void
    {
        $response = $this->postJson($this->baseUrl(99999), [
            'type'     => 'entrada',
            'quantity' => 10,
        ], $this->authHeaders());

        $response->assertStatus(404);
    }

    public function test_store_requiere_autenticacion(): void
    {
        $response = $this->postJson($this->baseUrl(), [
            'type'     => 'entrada',
            'quantity' => 10,
        ]);

        $response->assertStatus(401);
    }

    public function test_store_registra_movimiento_en_base_de_datos(): void
    {
        $this->postJson($this->baseUrl(), [
            'type'     => 'entrada',
            'quantity' => 15,
            'reason'   => 'Prueba de integración',
        ], $this->authHeaders());

        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $this->product->id,
            'type'       => 'entrada',
            'quantity'   => 15,
            'reason'     => 'Prueba de integración',
            'user_id'    => $this->user->id,
        ]);
    }

    public function test_store_movimientos_consecutivos_mantienen_consistencia(): void
    {
        // Entrada: 100 + 50 = 150
        $this->postJson($this->baseUrl(), ['type' => 'entrada', 'quantity' => 50], $this->authHeaders())
             ->assertStatus(201);

        // Salida: 150 - 30 = 120
        $this->postJson($this->baseUrl(), ['type' => 'salida', 'quantity' => 30], $this->authHeaders())
             ->assertStatus(201);

        // Salida: 120 - 120 = 0
        $this->postJson($this->baseUrl(), ['type' => 'salida', 'quantity' => 120], $this->authHeaders())
             ->assertStatus(201);

        $this->assertEquals(0, $this->product->fresh()->stock);
        $this->assertDatabaseCount('stock_movements', 3);
    }
}
