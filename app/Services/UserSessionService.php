<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Equivalente a sessionAuth() del legacy (pages.php).
 *
 * Inicializa session('user') y session('cart') tras la autenticación.
 * Las claves del array user mantienen los mismos nombres que el objeto stdClass
 * del legacy para facilitar el port progresivo de controladores.
 *
 * Fases:
 *   2 (actual) — datos esenciales: perfil, tipo, emails, agente, dirección principal
 *   3           — políticas de descuento reales (SPs), direcciones de envío, localidades
 *   4           — keys de pasarela de pago (Stripe / Conekta / MercadoPago)
 */
class UserSessionService
{
    public function initSession(int $clienteId): void
    {
        $cliente = DB::selectOne("
            SELECT CLIENTE_ID, CLAVE_CLIENTE, NOMBRE, CONTADO, SOLO_VTA_CONTADO,
                   TIPO_CLIENTE_ID, PCTJ_PRONTOPAGO, PERMITIR_CLTE_RECOGE, OPERADOR_TMK_ASIG
            FROM _CLIENTES
            WHERE CLIENTE_ID = ?
            LIMIT 1
        ", [$clienteId]);

        if (! $cliente) {
            Log::warning("UserSessionService: CLIENTE_ID {$clienteId} no encontrado.");
            return;
        }

        $tipo        = $this->loadClientType($cliente->TIPO_CLIENTE_ID);
        $emailStr    = $this->loadEmails($clienteId);
        $agent       = $this->loadAgent($cliente->OPERADOR_TMK_ASIG ?? null);
        $mainAddress = $this->loadMainAddress($clienteId);

        // is_public: contado => cliente general; crédito => distribuidor
        $isPublic = ($cliente->CONTADO === 'S') ? 1 : 0;

        // Tipo general — base en tabla, override por TIPO_CLIENTE_ID
        [$clientType, $cashSale, $switched] = $this->resolveClientType(
            (string) $cliente->TIPO_CLIENTE_ID,
            $tipo
        );

        // TIPO_LOCALIDAD viene de _LOCALIDADES via el JOIN en loadMainAddress
        $tipoLocalidad = $mainAddress['TIPO_LOCALIDAD'] ?? '';
        $userType = $this->resolveUserType($isPublic, $tipoLocalidad);

        $discountRange = $this->loadDiscountRange($userType);

        // distrib_label / distrib_url (misma lógica que el Blade)
        $distribLabel = $isPublic ? 'Quiero Ser Distribuidor' : 'Soy Distribuidor';
        $distribUrl   = $isPublic ? '/pages/distributed'      : '/spages/dealer_location';

        $user = [
            // ─── Identidad ────────────────────────────────────────────────
            'CLIENTE_ID'           => $cliente->CLIENTE_ID,
            'CLAVE_CLIENTE'        => $cliente->CLAVE_CLIENTE,
            'NOMBRE'               => $cliente->NOMBRE,
            'EMAIL'                => $emailStr,
            'email_default'        => explode(',', $emailStr)[0] ?? '',

            // ─── Tipo de cliente ──────────────────────────────────────────
            'TIPO_CLIENTE_ID'      => $cliente->TIPO_CLIENTE_ID,
            'CONTADO'              => $cliente->CONTADO,
            'SOLO_VTA_CONTADO'     => $cliente->SOLO_VTA_CONTADO,
            'is_public'            => $isPublic,
            'client_type'          => $clientType,
            'client_subtype'       => $tipo->NOMBRE       ?? null,
            'subtype_description'  => $tipo->DESCRIPCION  ?? null,
            'user_type'            => $userType,
            'cash_sale'            => $cashSale,
            'switched'             => $switched,

            // ─── Agente TMK ───────────────────────────────────────────────
            'ASESOR_LOGIN_ID'      => null,   // solo se asigna en logins de staff via _root
            'OPERADOR_TMK_ASIG'    => $cliente->OPERADOR_TMK_ASIG,
            'OPERADOR_TMK'         => $agent['clave']      ?? '',
            'OPERADOR_TMK_ID'      => $agent['id']         ?? null,
            'EMAILAGENT'           => $agent['email']      ?? '',
            'OPERADOR'             => '',
            'OPERADOR_ID'          => null,

            // ─── Configuración ────────────────────────────────────────────
            'TIPO_LOCALIDAD'       => $tipoLocalidad,
            'PCTJ_PRONTOPAGO'      => $cliente->PCTJ_PRONTOPAGO,
            'PERMITIR_CLTE_RECOGE' => $cliente->PERMITIR_CLTE_RECOGE ?? 'N',
            'config_empresa_id'    => '',
            'CLT_RECOJE'           => '',
            'isPickup'             => 0,
            'positive_balance'     => 0,
            'price_level_reached'  => 1,

            // ─── Descuentos (Fase 3: SPs reales) ─────────────────────────
            'discount'             => 0,
            'initial_discount'     => 0,
            'discount_range'       => $discountRange,
            'policy_artcli_id'     => null,   // Fase 3
            'policy_artclivol_id'  => null,   // Fase 3

            // ─── Dirección principal ──────────────────────────────────────
            'main_address'         => $mainAddress,

            // ─── Fase 4 (pasarelas de pago) ───────────────────────────────
            'stripe_customer'      => null,
            'pEmpresa'             => 2,      // 2 = TEST hasta configurar en Fase 4

            // ─── Estado transaccional ─────────────────────────────────────
            'PENDIENTES'           => null,   // Fase 3
            'COTIZACIONACTUAL'     => null,
            'DIR_CONSIG_ID'        => null,

            // ─── Frontend ─────────────────────────────────────────────────
            'distrib_label'        => $distribLabel,
            'distrib_url'          => $distribUrl,
        ];

        $cart = [
            'items'            => [],
            'total'            => 0,
            'shipping'         => 0,
            'shippingTax'      => 0,
            'location_id'      => null,
            'positive_balance' => 0,
            'discount_reached' => 0,
        ];

        session(['user' => $user, 'cart' => $cart]);
    }

    // ─── Helpers privados ────────────────────────────────────────────────────

    private function loadClientType(?string $tipoClienteId): ?object
    {
        if (! $tipoClienteId) return null;
        return DB::selectOne(
            "SELECT TIPO_GRAL, NOMBRE, DESCRIPCION FROM _TIPOS_CLIENTES WHERE TIPO_CLIENTE_ID = ? LIMIT 1",
            [$tipoClienteId]
        );
    }

    private function loadEmails(int $clienteId): string
    {
        $rows = DB::select("SELECT EMAIL FROM _BR_EMAILS_CLIENTES WHERE CLIENTE_ID = ?", [$clienteId]);
        return implode(',', array_column($rows, 'EMAIL'));
    }

    private function loadAgent(?string $operadorId): array
    {
        if (! $operadorId) return [];
        $row = DB::selectOne(
            "SELECT CORREO_VENDEDOR, CLAVE_VENDEDOR, OPERADOR_ID FROM _vendedor WHERE OPERADOR_ID = ? LIMIT 1",
            [$operadorId]
        );
        if (! $row) return [];
        return [
            'email' => $row->CORREO_VENDEDOR,
            'clave' => $row->CLAVE_VENDEDOR,
            'id'    => $row->OPERADOR_ID,
        ];
    }

    private function loadMainAddress(int $clienteId): array
    {
        $row = DB::selectOne("
            SELECT dc.*,
                   c.NOMBRE            AS ciudad,
                   e.NOMBRE            AS estado,
                   p.NOMBRE            AS pais,
                   l.NOMBRE_LOCALIDAD  AS localidad
            FROM _DIRS_CLIENTES dc
            LEFT JOIN _CIUDADES    c ON dc.CIUDAD_ID    = c.CIUDAD_ID
            LEFT JOIN _ESTADOS     e ON dc.ESTADO_ID    = e.ESTADO_ID
            LEFT JOIN _PAISES      p ON dc.PAIS_ID      = p.PAIS_ID
            LEFT JOIN _LOCALIDADES l ON dc.LOCALIDAD_ID = l.LOCALIDAD_ID
            WHERE dc.CLIENTE_ID = ? AND dc.ES_DIR_PPAL = 'S'
            LIMIT 1
        ", [$clienteId]);

        return $row ? (array) $row : [];
    }

    private function loadDiscountRange(string $userType): array
    {
        try {
            $row = DB::selectOne("
                SELECT
                    (SELECT initial_percentage FROM discount_range WHERE `type` = ? ORDER BY initial_percentage LIMIT 1)     AS initial_percentage,
                    (SELECT final_percentage   FROM discount_range WHERE `type` = ? ORDER BY final_percentage DESC LIMIT 1)  AS final_percentage
            ", [$userType, $userType]);
            return [
                'initial_percentage' => $row->initial_percentage ?? 0,
                'final_percentage'   => $row->final_percentage   ?? 0,
            ];
        } catch (\Throwable $e) {
            return ['initial_percentage' => 0, 'final_percentage' => 0];
        }
    }

    private function resolveClientType(string $tipoId, ?object $tipo): array
    {
        $base = $tipo->TIPO_GRAL ?? 'WG';
        return match ($tipoId) {
            '187185'    => ['CR', 'N', 'N'],
            '88721'     => ['WD', 'N', 'S'],
            default     => [$base ?: 'WG', 'S', 'S'],
        };
    }

    private function resolveUserType(int $isPublic, string $tipoLocalidad): string
    {
        $base = $isPublic ? 'GENERAL' : 'DISTRIBUIDOR';
        return match (true) {
            $base === 'DISTRIBUIDOR' && $tipoLocalidad === '0000-0010' => 'DISTRIBUIDOR_LOC',
            $base === 'GENERAL'      && $tipoLocalidad === '0000-0010' => 'GENERAL_LOC',
            default => $base,
        };
    }
}
