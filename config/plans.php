<?php

$plans = [
    'basic' => ['name' => 'Club Básico', 'price' => '35.000', 'annual' => '28.000', 'total' => '336.000', 'description' => 'El primer gran paso para poner tu club en orden.', 'cta' => 'Elegir Plan Básico', 'features' => ['Hasta 2 canchas activas', 'Portal de reservas online', 'POS básico: hasta 25 productos', 'Alertas de stock bajo', 'Soporte vía email']],
    'pro' => ['name' => 'Club Pro', 'price' => '65.000', 'annual' => '52.000', 'total' => '624.000', 'description' => 'Para clubes con alto flujo y un quiosco que no para.', 'cta' => 'Comenzar con Pro', 'features' => ['Hasta 6 canchas activas', 'Portal de reservas personalizado para clientes', 'POS ilimitado + cuentas abiertas por cancha', 'Control de stock y alerta de reposición', 'Dashboard de ingresos, costos y margen bruto', 'Hasta 3 cuentas de recepcionista/cajero', 'Soporte prioritario por WhatsApp']],
    'premium' => ['name' => 'Complejo Premium', 'price' => '110.000', 'annual' => '88.000', 'total' => '1.056.000', 'description' => 'Toda la potencia para grandes recintos multideporte.', 'cta' => 'Contactar para Premium', 'features' => ['Canchas ilimitadas', 'Todas las funciones Pro incluidas', 'Reporte financiero consolidado y flujo de caja', 'Multi-bodega para cafetería y tienda de pádel', 'Cuentas de personal y staff ilimitadas', 'Asistencia 24/7 y onboarding guiado']],
];

if (env('BILLING_TEST_PRICES', false)) {
    foreach (['basic' => 100, 'pro' => 150, 'premium' => 200] as $key => $amount) {
        $plans[$key]['price'] = (string) $amount;
        $plans[$key]['annual'] = (string) $amount;
        $plans[$key]['total'] = (string) $amount;
    }
}

return $plans;
