<?php

return [
    'test_prices' => env('BILLING_TEST_PRICES', false),
    'fixed_links' => ['basic' => env('BILLING_LINK_BASIC'), 'pro' => env('BILLING_LINK_PRO'), 'premium' => env('BILLING_LINK_PREMIUM')],
    'payment_link' => env('BILLING_PAYMENT_LINK'),
    'link_test_enabled' => env('BILLING_LINK_TEST_ENABLED', false),
    'mode' => env('BILLING_WEBPAY_MODE', env('APP_ENV') === 'production' ? 'disabled' : 'integration'),
    'commerce_code' => env('BILLING_WEBPAY_COMMERCE_CODE'),
    'api_key' => env('BILLING_WEBPAY_API_KEY'),
    'legal_approved' => env('BILLING_LEGAL_APPROVED', false),
    'provider' => ['name' => 'pxshoo', 'rut' => '21.431.110-0', 'email' => 'franciscogonzalezandrades@gmail.com'],
    'version' => '2026-11-05',
    'terms' => [
        'Servicio' => 'PuntoCancha es una plataforma de gestión de clubes deportivos ofrecida por pxshoo, RUT 21.431.110-0. El acceso contratado corresponde al club y a las funciones habilitadas en su plan.',
        'Planes y pago' => 'Antes de pagar se informa el plan, período y monto total en pesos chilenos. El pago mensual habilita un mes; el anual habilita un año con el descuento indicado. La creación de la cuenta no genera un cobro. La activación requiere verificar el pago. En pagos por enlace Webpay.cl la verificación y activación son manuales; abrir el enlace no acredita un pago. La opción independiente de prueba de cobro no activa planes. Los planes con precios temporales de prueba habilitan el período contratado después de verificar el pago.',
        'Renovación' => 'Este flujo no guarda tarjetas ni realiza cargos recurrentes automáticos. La renovación requiere un nuevo pago autorizado. Una renovación del mismo plan vigente agrega el período pagado a su vencimiento; un cambio de plan comienza al aprobarse el pago.',
        'Uso de la cuenta' => 'El administrador debe proporcionar información correcta, proteger sus credenciales y autorizar solo al personal que corresponda. No se permite acceder a datos de otros clubes ni utilizar el servicio para actividades ilícitas.',
        'Reservas y cobros del club' => 'El pago de acceso a PuntoCancha es independiente de los pagos que los jugadores realizan al club. El club administra sus canchas, tarifas, condiciones de reserva y su propia relación con los jugadores.',
        'Soporte y solicitudes' => 'Para consultas sobre acceso, pagos, cancelación o devolución, escribe a franciscogonzalezandrades@gmail.com indicando tu club y orden de compra. Cada solicitud se revisará según sus antecedentes y los derechos que correspondan. Estas condiciones no excluyen derechos establecidos por la legislación aplicable.',
    ],
    'privacy' => [
        'Responsable y contacto' => 'pxshoo, RUT 21.431.110-0, administra los datos de las cuentas de PuntoCancha. Puedes contactar a franciscogonzalezandrades@gmail.com para consultas o solicitudes sobre tus datos personales.',
        'Datos utilizados' => 'Para operar la cuenta se utilizan nombre, correo, teléfono, datos del club, configuración, reservas y movimientos registrados en la plataforma. Al contratar se registra la cuenta que aceptó las políticas, su versión, fecha, plan y orden de pago.',
        'Finalidades' => 'Los datos permiten autenticar usuarios, separar la información de cada club, prestar las funciones contratadas, gestionar pagos y atender solicitudes de soporte. La aceptación de estas políticas no suscribe al usuario a publicidad.',
        'Pagos y servicios externos' => 'Los datos de tarjeta se ingresan directamente en Transbank; PuntoCancha no almacena el número completo de tarjeta ni el CVV. Se conservan identificadores, montos y estado de la transacción para verificar el pago. El pronóstico del tiempo consulta la ciudad del club en Open-Meteo.',
        'Acceso y conservación' => 'La información operativa se limita al club y a sus usuarios autorizados. Se mantiene mientras sea necesaria para la prestación del servicio, resolver incidencias y atender obligaciones aplicables. Las credenciales de acceso y pago se protegen mediante los mecanismos de seguridad de la plataforma.',
        'Solicitudes y cambios' => 'Puedes solicitar información, corrección o eliminación de tus datos al correo de contacto. Se verificará la identidad del solicitante y las obligaciones de conservación antes de resolver. Las versiones de estas políticas aceptadas al contratar quedan asociadas a la operación.',
    ],
];
