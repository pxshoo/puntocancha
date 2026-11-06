<!doctype html>
<html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"></head>
<body style="margin:0;background:#f1f5f9;font-family:Arial,sans-serif;color:#0f172a">
<table role="presentation" width="100%" cellspacing="0" cellpadding="0"><tr><td align="center" style="padding:32px 16px">
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:600px;background:white;border-radius:16px;overflow:hidden">
<tr><td style="padding:24px;background:#0f172a;color:#a7f3d0;font-size:24px;font-weight:bold">PuntoCancha.</td></tr>
<tr><td style="padding:28px">
@if($confirmation['demo'])<p style="padding:12px;background:#fef3c7;color:#92400e">MODO DE PRUEBA: no se realizó un cobro real.</p>@endif
<h1 style="font-size:24px">{{ $confirmation['title'] }}</h1>
<p>{{ $confirmation['message'] }}</p>
<p style="color:#64748b">{{ $confirmation['club'] }}</p>
<table width="100%" cellspacing="0" cellpadding="0" style="border-collapse:collapse">
@foreach($confirmation['details'] as $label => $value)<tr><td style="padding:12px 4px;border-bottom:1px solid #e2e8f0;color:#64748b">{{ $label }}</td><td style="padding:12px 4px;border-bottom:1px solid #e2e8f0;text-align:right;font-weight:bold">{{ $value }}</td></tr>@endforeach
</table>
@if(!empty($confirmation['items']))<h2 style="font-size:18px">Productos</h2>@foreach($confirmation['items'] as $item)<p>{{ $item['quantity'] }} × {{ $item['name'] }} — {{ $item['total'] }}</p>@endforeach @endif
@if(!empty($confirmation['instructions']))<h2 style="font-size:18px">Indicaciones para tu partido</h2><ul style="padding-left:20px;line-height:1.7">@foreach($confirmation['instructions'] as $instruction)<li>{{ $instruction }}</li>@endforeach</ul>@endif
@foreach($confirmation['actions'] ?? [] as $action)<p style="margin-top:20px"><a href="{{ $action['url'] }}" style="display:inline-block;padding:12px 18px;border-radius:8px;background:#047857;color:#ffffff;text-decoration:none;font-weight:bold">{{ $action['label'] }}</a></p>@endforeach
@if(!empty($confirmation['policy']))<h2 style="font-size:18px">Condiciones de la reserva</h2><p style="line-height:1.6">{{ $confirmation['policy'] }}</p>@endif
<p style="margin-top:28px;font-size:12px;color:#64748b">Conserva este correo como confirmación. No reemplaza una boleta o factura tributaria.</p>
</td></tr></table></td></tr></table></body></html>
