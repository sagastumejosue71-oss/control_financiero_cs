<?php

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\Cuenta;
use App\Models\Deuda;
use App\Models\Movimiento;
use App\Models\Negocio;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Cobertura completa del módulo "Mis Negocios": separación de dinero por
 * negocio, ownership (IDOR), validaciones de duplicados y cascada al
 * eliminar. Corre contra sqlite en memoria (ver phpunit.xml).
 */
class NegociosFeatureTest extends TestCase
{
    use RefreshDatabase;

    private function loginComo(User $user): void
    {
        $this->withSession(['user_id' => $user->id, 'finanzas_auth' => true]);
    }

    // ── Autenticación / ownership ──────────────────────────────────────

    public function test_sin_sesion_no_puede_listar_negocios(): void
    {
        $this->getJson('/api/negocios')->assertStatus(401);
    }

    public function test_sin_sesion_no_puede_crear_negocio(): void
    {
        $this->postJson('/api/negocios', ['nombre' => 'X'])->assertStatus(401);
    }

    public function test_usuario_no_puede_ver_negocio_de_otro_usuario(): void
    {
        $dueño = User::factory()->create();
        $otro = User::factory()->create();
        $negocio = Negocio::factory()->for($dueño)->create();

        $this->loginComo($otro);
        $this->getJson("/api/negocios/{$negocio->id}")->assertStatus(404);
    }

    public function test_usuario_no_puede_eliminar_negocio_de_otro_usuario(): void
    {
        $dueño = User::factory()->create();
        $otro = User::factory()->create();
        $negocio = Negocio::factory()->for($dueño)->create();

        $this->loginComo($otro);
        $this->deleteJson("/api/negocios/{$negocio->id}")->assertStatus(404);
        $this->assertDatabaseHas('negocios', ['id' => $negocio->id]);
    }

    // ── Negocio: crear / editar / eliminar / duplicados ────────────────

    public function test_crea_negocio_correctamente(): void
    {
        $user = User::factory()->create();
        $this->loginComo($user);

        $res = $this->postJson('/api/negocios', ['nombre' => 'Panadería', 'moneda' => 'GTQ']);
        $res->assertStatus(201)->assertJsonPath('negocio.user_id', $user->id);

        $this->assertDatabaseHas('negocios', ['nombre' => 'Panadería', 'user_id' => $user->id]);
    }

    public function test_no_permite_negocio_con_nombre_duplicado_del_mismo_usuario(): void
    {
        $user = User::factory()->create();
        Negocio::factory()->for($user)->create(['nombre' => 'Panadería']);
        $this->loginComo($user);

        $res = $this->postJson('/api/negocios', ['nombre' => 'Panadería', 'moneda' => 'GTQ']);
        $res->assertStatus(422)->assertJsonValidationErrors('nombre');
    }

    public function test_dos_usuarios_distintos_si_pueden_usar_el_mismo_nombre(): void
    {
        $a = User::factory()->create();
        $b = User::factory()->create();
        Negocio::factory()->for($a)->create(['nombre' => 'Panadería']);

        $this->loginComo($b);
        $this->postJson('/api/negocios', ['nombre' => 'Panadería', 'moneda' => 'GTQ'])->assertStatus(201);
    }

    public function test_edita_negocio(): void
    {
        $user = User::factory()->create();
        $negocio = Negocio::factory()->for($user)->create(['nombre' => 'Viejo']);
        $this->loginComo($user);

        $this->putJson("/api/negocios/{$negocio->id}", ['nombre' => 'Nuevo'])
            ->assertOk()->assertJsonPath('negocio.nombre', 'Nuevo');
    }

    public function test_eliminar_negocio_elimina_en_cascada_cuentas_categorias_movimientos_y_deudas(): void
    {
        $user = User::factory()->create();
        $negocio = Negocio::factory()->for($user)->create();
        $cuenta = Cuenta::factory()->for($negocio)->create(['saldo_inicial' => 100]);
        $categoria = Categoria::factory()->for($negocio)->create();
        $mov = Movimiento::factory()->for($negocio)->create([
            'cuenta_id' => $cuenta->id,
            'categoria_id' => $categoria->id,
            'created_by' => $user->id,
        ]);
        $deuda = Deuda::factory()->for($negocio)->create();

        $this->loginComo($user);
        $this->deleteJson("/api/negocios/{$negocio->id}")->assertOk();

        $this->assertDatabaseMissing('negocios', ['id' => $negocio->id]);
        $this->assertDatabaseMissing('cuentas', ['id' => $cuenta->id]);
        $this->assertDatabaseMissing('categorias', ['id' => $categoria->id]);
        $this->assertDatabaseMissing('movimientos', ['id' => $mov->id]);
        $this->assertDatabaseMissing('deudas', ['id' => $deuda->id]);
    }

    // ── Cuentas ─────────────────────────────────────────────────────────

    public function test_crea_cuenta_y_calcula_saldo_actual_con_movimientos(): void
    {
        $user = User::factory()->create();
        $negocio = Negocio::factory()->for($user)->create();
        $this->loginComo($user);

        $cuenta = $this->postJson("/api/negocios/{$negocio->id}/cuentas", [
            'nombre' => 'Caja', 'tipo' => 'efectivo', 'saldo_inicial' => 100,
        ])->assertStatus(201)->json('cuenta');

        $this->postJson("/api/negocios/{$negocio->id}/movimientos", [
            'cuenta_id' => $cuenta['id'], 'tipo' => 'ingreso', 'monto' => 50, 'fecha' => now()->toDateString(),
        ])->assertStatus(201);

        $lista = $this->getJson("/api/negocios/{$negocio->id}/cuentas")->json();
        $this->assertEquals(150, $lista[0]['saldo_actual']);
    }

    public function test_eliminar_cuenta_elimina_en_cascada_sus_movimientos(): void
    {
        $user = User::factory()->create();
        $negocio = Negocio::factory()->for($user)->create();
        $cuenta = Cuenta::factory()->for($negocio)->create();
        $mov = Movimiento::factory()->for($negocio)->create(['cuenta_id' => $cuenta->id, 'created_by' => $user->id]);
        $this->loginComo($user);

        $this->deleteJson("/api/negocios/{$negocio->id}/cuentas/{$cuenta->id}")->assertOk();

        $this->assertDatabaseMissing('movimientos', ['id' => $mov->id]);
    }

    // ── Categorías: crear / editar / eliminar / duplicados ─────────────

    public function test_crea_edita_y_elimina_categoria(): void
    {
        $user = User::factory()->create();
        $negocio = Negocio::factory()->for($user)->create();
        $this->loginComo($user);

        $cat = $this->postJson("/api/negocios/{$negocio->id}/categorias", [
            'nombre' => 'Ventas', 'tipo' => 'ingreso',
        ])->assertStatus(201)->json('categoria');

        $this->putJson("/api/negocios/{$negocio->id}/categorias/{$cat['id']}", [
            'nombre' => 'Ventas mostrador', 'tipo' => 'ingreso',
        ])->assertOk()->assertJsonPath('categoria.nombre', 'Ventas mostrador');

        $this->deleteJson("/api/negocios/{$negocio->id}/categorias/{$cat['id']}")->assertOk();
        $this->assertDatabaseMissing('categorias', ['id' => $cat['id']]);
    }

    public function test_no_permite_categoria_duplicada_mismo_nombre_y_tipo(): void
    {
        $user = User::factory()->create();
        $negocio = Negocio::factory()->for($user)->create();
        Categoria::factory()->for($negocio)->create(['nombre' => 'Renta', 'tipo' => 'gasto']);
        $this->loginComo($user);

        $this->postJson("/api/negocios/{$negocio->id}/categorias", [
            'nombre' => 'Renta', 'tipo' => 'gasto',
        ])->assertStatus(422)->assertJsonValidationErrors('nombre');
    }

    // ── Movimientos: crear / editar / eliminar (con recálculo de saldo) ─

    public function test_edita_movimiento_y_recalcula_saldo(): void
    {
        $user = User::factory()->create();
        $negocio = Negocio::factory()->for($user)->create();
        $cuenta = Cuenta::factory()->for($negocio)->create(['saldo_inicial' => 0]);
        $this->loginComo($user);

        $mov = $this->postJson("/api/negocios/{$negocio->id}/movimientos", [
            'cuenta_id' => $cuenta->id, 'tipo' => 'ingreso', 'monto' => 100, 'fecha' => now()->toDateString(),
        ])->assertStatus(201)->json('movimiento');

        // Corrige el monto de 100 a 40.
        $this->putJson("/api/negocios/{$negocio->id}/movimientos/{$mov['id']}", [
            'cuenta_id' => $cuenta->id, 'tipo' => 'ingreso', 'monto' => 40, 'fecha' => now()->toDateString(),
        ])->assertOk();

        $lista = $this->getJson("/api/negocios/{$negocio->id}/cuentas")->json();
        $this->assertEquals(40, $lista[0]['saldo_actual']);
    }

    public function test_no_permite_editar_movimiento_con_cuenta_de_otro_negocio(): void
    {
        $user = User::factory()->create();
        $negocioA = Negocio::factory()->for($user)->create();
        $negocioB = Negocio::factory()->for($user)->create();
        $cuentaA = Cuenta::factory()->for($negocioA)->create();
        $cuentaB = Cuenta::factory()->for($negocioB)->create();
        $mov = Movimiento::factory()->for($negocioA)->create(['cuenta_id' => $cuentaA->id, 'created_by' => $user->id]);
        $this->loginComo($user);

        $this->putJson("/api/negocios/{$negocioA->id}/movimientos/{$mov['id']}", [
            'cuenta_id' => $cuentaB->id, 'tipo' => 'ingreso', 'monto' => 10, 'fecha' => now()->toDateString(),
        ])->assertStatus(422);
    }

    public function test_eliminar_movimiento_recalcula_saldo(): void
    {
        $user = User::factory()->create();
        $negocio = Negocio::factory()->for($user)->create();
        $cuenta = Cuenta::factory()->for($negocio)->create(['saldo_inicial' => 100]);
        $this->loginComo($user);

        $mov = $this->postJson("/api/negocios/{$negocio->id}/movimientos", [
            'cuenta_id' => $cuenta->id, 'tipo' => 'gasto', 'monto' => 30, 'fecha' => now()->toDateString(),
        ])->assertStatus(201)->json('movimiento');

        $this->deleteJson("/api/negocios/{$negocio->id}/movimientos/{$mov['id']}")->assertOk();

        $lista = $this->getJson("/api/negocios/{$negocio->id}/cuentas")->json();
        $this->assertEquals(100, $lista[0]['saldo_actual']);
    }

    // ── Deudas: crear / editar / marcar pagada / eliminar ──────────────

    public function test_crea_edita_marca_pagada_y_elimina_deuda(): void
    {
        $user = User::factory()->create();
        $negocio = Negocio::factory()->for($user)->create();
        $this->loginComo($user);

        $deuda = $this->postJson("/api/negocios/{$negocio->id}/deudas", [
            'acreedor' => 'Banco X', 'monto_total' => 1000, 'saldo_pendiente' => 1000,
        ])->assertStatus(201)->json('deuda');

        $this->putJson("/api/negocios/{$negocio->id}/deudas/{$deuda['id']}", [
            'saldo_pendiente' => 600,
        ])->assertOk()->assertJsonPath('deuda.saldo_pendiente', '600.00');

        $this->putJson("/api/negocios/{$negocio->id}/deudas/{$deuda['id']}", [
            'saldada' => true, 'saldo_pendiente' => 0,
        ])->assertOk()->assertJsonPath('deuda.saldada', true);

        $this->deleteJson("/api/negocios/{$negocio->id}/deudas/{$deuda['id']}")->assertOk();
        $this->assertDatabaseMissing('deudas', ['id' => $deuda['id']]);
    }
}
