<?php

namespace App\Support;

class FinanzasDataDefaults
{
    public static function array(): array
    {
        return [
            'ingresos'             => [],
            'gastos_fijos'         => [],
            'gastos_variables'     => [],
            'deudas'               => [],
            'pagos_realizados'     => [],
            'historial_mensual'    => [],
            'metas_ahorro'         => [],
            'expansion_scenarios'  => [],
            'expansion_active_id'  => null,
            'exchange_rate'        => 7.70,
            '_rev'                 => 0,
        ];
    }
}
