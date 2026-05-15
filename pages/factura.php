<?php
session_start();
require_once "../conf/db.php";

if (!isset($_SESSION['email']) || $_SESSION['rol'] !== 'Cliente') {
    header("Location: ../login.php");
    exit();
}

$email = $_SESSION['email'];
$stmt_u = mysqli_prepare($conexion, "SELECT * FROM usuarios WHERE email = ?");
mysqli_stmt_bind_param($stmt_u, "s", $email);
mysqli_stmt_execute($stmt_u);
$result_u = mysqli_stmt_get_result($stmt_u);
$usuario  = mysqli_fetch_assoc($result_u);
mysqli_free_result($result_u);
mysqli_stmt_close($stmt_u);
$id_usuario = $usuario['id_usuario'];

$id_vehiculo = intval($_GET['id'] ?? 0);
if (!$id_vehiculo) { header("Location: panelCliente.php"); exit(); }

$stmt_v = mysqli_prepare($conexion, "SELECT * FROM vehiculos WHERE id_vehiculo = ? AND id_usuario = ?");
mysqli_stmt_bind_param($stmt_v, "ii", $id_vehiculo, $id_usuario);
mysqli_stmt_execute($stmt_v);
$result_v = mysqli_stmt_get_result($stmt_v);
$vehiculo = mysqli_fetch_assoc($result_v);
mysqli_free_result($result_v);
mysqli_stmt_close($stmt_v);
if (!$vehiculo) { header("Location: panelCliente.php"); exit(); }

$stmt_r = mysqli_prepare($conexion, "SELECT * FROM reparaciones WHERE id_vehiculo = ? ORDER BY fecha_estimada ASC");
mysqli_stmt_bind_param($stmt_r, "i", $id_vehiculo);
mysqli_stmt_execute($stmt_r);
$result_r     = mysqli_stmt_get_result($stmt_r);
$reparaciones = mysqli_fetch_all($result_r, MYSQLI_ASSOC);
mysqli_free_result($result_r);
mysqli_stmt_close($stmt_r);

$piezas_por_rep = [];
$stmt_pr = mysqli_prepare($conexion,
    "SELECT pr.id_reparacion, pr.cantidad_usada, p.nombre, p.precio_unitario
     FROM productos_reparacion pr
     JOIN productos p ON pr.id_producto = p.id_producto
     WHERE pr.id_reparacion IN (SELECT id_reparacion FROM reparaciones WHERE id_vehiculo = ?)");
mysqli_stmt_bind_param($stmt_pr, "i", $id_vehiculo);
mysqli_stmt_execute($stmt_pr);
$result_pr = mysqli_stmt_get_result($stmt_pr);
while ($row = mysqli_fetch_assoc($result_pr)) {
    $piezas_por_rep[$row['id_reparacion']][] = $row;
}
mysqli_free_result($result_pr);
mysqli_stmt_close($stmt_pr);

$subtotal_global = 0;
foreach ($reparaciones as $rep) {
    $subtotal_global += floatval($rep['costo_final'] ?? $rep['costo_estimado']);
}

$num_factura  = 'FAC-' . date('Y') . '-' . str_pad($id_vehiculo, 4, '0', STR_PAD_LEFT);
$fecha_factura = date('d/m/Y');

// ── DATOS DEL TALLER (editar aquí) ──────────────────────────
$taller_nombre    = 'Taller Mecánico El Bakkali';
$taller_direccion = 'Calle Mayor, 12 - 31001 Pamplona, Navarra';
$taller_telefono  = '948 000 000';
$taller_email     = 'taller@elbakkali.com';
$taller_cif       = 'B-12345678';
// ─────────────────────────────────────────────────────────────
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Factura <?= $num_factura ?></title>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Segoe UI', Arial, sans-serif; font-size: 13px; color: #222; background: #f0f2f5; }

        .btn-print {
            display: block; margin: 20px auto 0; padding: 10px 32px;
            background: #212529; color: #fff; border: none; border-radius: 6px;
            font-size: 15px; cursor: pointer; text-align: center; width: fit-content;
        }
        .btn-print:hover { background: #343a40; }
        .screen-only { display: block; }

        .factura {
            background: #fff; max-width: 800px; margin: 30px auto;
            padding: 48px 52px; box-shadow: 0 4px 24px rgba(0,0,0,.10); border-radius: 8px;
        }

        .factura-header { display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:36px; border-bottom:3px solid #212529; padding-bottom:20px; }
        .taller-nombre { font-size:22px; font-weight:700; color:#212529; }
        .taller-datos  { font-size:12px; color:#555; line-height:1.7; margin-top:4px; }
        .factura-meta  { text-align:right; }
        .factura-meta h2 { font-size:28px; font-weight:800; color:#212529; letter-spacing:1px; }
        .factura-meta p  { font-size:12px; color:#666; margin-top:4px; line-height:1.7; }

        .info-grid { display:grid; grid-template-columns:1fr 1fr; gap:24px; margin-bottom:32px; }
        .info-box { background:#f8f9fa; border-radius:6px; padding:14px 18px; }
        .info-box h4 { font-size:11px; text-transform:uppercase; letter-spacing:.8px; color:#888; margin-bottom:8px; }
        .info-box p  { font-size:13px; color:#222; line-height:1.8; }

        .section-title { font-size:13px; font-weight:700; text-transform:uppercase; letter-spacing:.8px; color:#555; margin-bottom:10px; border-bottom:1px solid #dee2e6; padding-bottom:6px; }
        table { width:100%; border-collapse:collapse; margin-bottom:24px; }
        thead th { background:#212529; color:#fff; padding:9px 12px; font-size:12px; text-align:left; }
        tbody td { padding:8px 12px; border-bottom:1px solid #f0f0f0; vertical-align:top; }
        tbody tr:last-child td { border-bottom:none; }
        .sub-table { width:100%; margin-top:5px; }
        .sub-table td { padding:2px 4px; font-size:11px; color:#555; border:none; }
        .sub-table td:last-child { text-align:right; }
        .sub-table .mo-row td { color:#0c5460; font-style:italic; }
        .text-right { text-align:right; }
        .text-muted  { color:#888; }
        .fw-bold { font-weight:700; }

        .totales { display:flex; justify-content:flex-end; margin-top:8px; }
        .totales-box { width:280px; }
        .totales-row { display:flex; justify-content:space-between; padding:5px 0; font-size:13px; border-bottom:1px solid #f0f0f0; }
        .totales-row:last-child { border-bottom:none; }
        .totales-row.total-final { font-size:16px; font-weight:800; color:#212529; padding-top:10px; border-top:2px solid #212529; margin-top:4px; }

        .factura-footer { margin-top:48px; text-align:center; font-size:11px; color:#aaa; border-top:1px solid #dee2e6; padding-top:16px; }

        @media print {
            html, body { background:#fff !important; }
            .screen-only, .btn-print { display:none !important; }
            .factura { margin:0; padding:24px 28px; box-shadow:none; border-radius:0; max-width:100%; }
            @page { margin:15mm 12mm; }
        }
    </style>
</head>
<body>

<div class="screen-only" style="text-align:center;padding-top:24px">
    <button class="btn-print" onclick="window.print()">🖨️ Imprimir / Guardar como PDF</button>
    <p style="font-size:12px;color:#888;margin-top:8px">En el diálogo de impresión selecciona <em>Guardar como PDF</em></p>
</div>

<div class="factura">

    <div class="factura-header">
        <div>
            <div class="taller-nombre"><?= htmlspecialchars($taller_nombre) ?></div>
            <div class="taller-datos">
                <?= htmlspecialchars($taller_direccion) ?><br>
                Tel: <?= htmlspecialchars($taller_telefono) ?> &nbsp;|&nbsp; <?= htmlspecialchars($taller_email) ?><br>
                CIF: <?= htmlspecialchars($taller_cif) ?>
            </div>
        </div>
        <div class="factura-meta">
            <h2>FACTURA</h2>
            <p>
                <strong>Nº:</strong> <?= $num_factura ?><br>
                <strong>Fecha:</strong> <?= $fecha_factura ?>
            </p>
        </div>
    </div>

    <div class="info-grid">
        <div class="info-box">
            <h4>Cliente</h4>
            <p>
                <strong><?= htmlspecialchars($usuario['nombre'].' '.$usuario['apellidos']) ?></strong><br>
                <?= htmlspecialchars($usuario['email']) ?><br>
                <?= htmlspecialchars($usuario['telefono'] ?? '') ?>
            </p>
        </div>
        <div class="info-box">
            <h4>Vehículo</h4>
            <p>
                <strong><?= htmlspecialchars($vehiculo['marca'].' '.$vehiculo['modelo']) ?> (<?= $vehiculo['anio'] ?>)</strong><br>
                Matrícula: <?= htmlspecialchars($vehiculo['matricula']) ?>
            </p>
        </div>
    </div>

    <div class="section-title">Detalle de reparaciones</div>
    <table>
        <thead>
            <tr>
                <th style="width:32%">Concepto</th>
                <th>Desglose</th>
                <th style="width:110px" class="text-right">Importe</th>
            </tr>
        </thead>
        <tbody>
        <?php
        $subtotal_reps = 0;
        foreach ($reparaciones as $rep):
            $importe  = floatval($rep['costo_final'] ?? $rep['costo_estimado']);
            $subtotal_reps += $importe;
            $piezas   = $piezas_por_rep[$rep['id_reparacion']] ?? [];
            $horas    = floatval($rep['horas_mano_obra'] ?? 0);
            $precio_h = floatval($rep['precio_hora'] ?? 0);
            $total_mo = $horas * $precio_h;
        ?>
        <tr>
            <td class="fw-bold">
                <?= htmlspecialchars($rep['titulo']) ?><br>
                <span class="text-muted" style="font-weight:normal;font-size:11px"><?= htmlspecialchars($rep['descripcion']) ?></span>
            </td>
            <td>
                <?php if (!empty($piezas)): ?>
                <table class="sub-table">
                    <tr><td colspan="2" style="font-weight:600;color:#333;padding-bottom:2px">🔧 Piezas</td></tr>
                    <?php foreach ($piezas as $pz): ?>
                    <tr>
                        <td><?= htmlspecialchars($pz['nombre']) ?> × <?= $pz['cantidad_usada'] ?></td>
                        <td><?= number_format($pz['cantidad_usada'] * $pz['precio_unitario'], 2) ?> €</td>
                    </tr>
                    <?php endforeach; ?>
                </table>
                <?php endif; ?>
                <?php if ($horas > 0): ?>
                <table class="sub-table" style="margin-top:<?= !empty($piezas)?'8px':'0' ?>">
                    <tr><td colspan="2" style="font-weight:600;color:#0c5460;padding-bottom:2px">⏱️ Mano de obra</td></tr>
                    <tr class="mo-row">
                        <td><?= number_format($horas,2) ?> h × <?= number_format($precio_h,2) ?> €/h</td>
                        <td><?= number_format($total_mo,2) ?> €</td>
                    </tr>
                </table>
                <?php endif; ?>
                <?php if (empty($piezas) && $horas == 0): ?>
                    <span class="text-muted" style="font-size:11px">Sin desglose adicional</span>
                <?php endif; ?>
            </td>
            <td class="text-right fw-bold"><?= number_format($importe, 2) ?> €</td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <div class="totales">
        <div class="totales-box">
            <div class="totales-row">
                <span>Subtotal</span>
                <span><?= number_format($subtotal_reps, 2) ?> €</span>
            </div>
            <div class="totales-row">
                <span>IVA (21%)</span>
                <span><?= number_format($subtotal_reps * 0.21, 2) ?> €</span>
            </div>
            <div class="totales-row total-final">
                <span>TOTAL</span>
                <span><?= number_format($subtotal_reps * 1.21, 2) ?> €</span>
            </div>
        </div>
    </div>

    <div class="factura-footer">
        Gracias por confiar en <?= htmlspecialchars($taller_nombre) ?>.<br>
        Esta factura ha sido generada electrónicamente y es válida sin firma ni sello.
    </div>

</div>
</body>
</html>
