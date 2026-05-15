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

$stmt_v = mysqli_prepare($conexion, "SELECT v.*, u.nombre, u.apellidos, u.email AS email_prop, u.telefono FROM vehiculos v JOIN usuarios u ON v.id_usuario = u.id_usuario WHERE v.id_vehiculo = ? AND v.id_usuario = ?");
mysqli_stmt_bind_param($stmt_v, "ii", $id_vehiculo, $id_usuario);
mysqli_stmt_execute($stmt_v);
$result_v = mysqli_stmt_get_result($stmt_v);
$vehiculo = mysqli_fetch_assoc($result_v);
mysqli_free_result($result_v);
mysqli_stmt_close($stmt_v);
if (!$vehiculo) { header("Location: panelCliente.php"); exit(); }

$stmt_e = mysqli_prepare($conexion, "SELECT estado FROM estados_vehiculo WHERE id_vehiculo = ? ORDER BY fecha_actualizacion DESC LIMIT 1");
mysqli_stmt_bind_param($stmt_e, "i", $id_vehiculo);
mysqli_stmt_execute($stmt_e);
$result_e   = mysqli_stmt_get_result($stmt_e);
$estado_row = mysqli_fetch_assoc($result_e);
mysqli_free_result($result_e);
mysqli_stmt_close($stmt_e);
$estado_actual = $estado_row['estado'] ?? 'Pendiente';

$stmt_hist = mysqli_prepare($conexion, "SELECT estado, fecha_actualizacion FROM estados_vehiculo WHERE id_vehiculo = ? ORDER BY fecha_actualizacion ASC");
mysqli_stmt_bind_param($stmt_hist, "i", $id_vehiculo);
mysqli_stmt_execute($stmt_hist);
$result_hist = mysqli_stmt_get_result($stmt_hist);
$historial   = mysqli_fetch_all($result_hist, MYSQLI_ASSOC);
mysqli_free_result($result_hist);
mysqli_stmt_close($stmt_hist);

$stmt_r = mysqli_prepare($conexion, "SELECT * FROM reparaciones WHERE id_vehiculo = ? ORDER BY fecha_estimada ASC");
mysqli_stmt_bind_param($stmt_r, "i", $id_vehiculo);
mysqli_stmt_execute($stmt_r);
$result_r    = mysqli_stmt_get_result($stmt_r);
$reparaciones = mysqli_fetch_all($result_r, MYSQLI_ASSOC);
mysqli_free_result($result_r);
mysqli_stmt_close($stmt_r);

$piezas_por_rep = [];
if (!empty($reparaciones)) {
    $stmt_pr = mysqli_prepare($conexion,
        "SELECT pr.id_reparacion, pr.cantidad_usada, p.nombre, p.precio_unitario
         FROM productos_reparacion pr
         JOIN productos p ON pr.id_producto = p.id_producto
         WHERE pr.id_reparacion IN (
             SELECT id_reparacion FROM reparaciones WHERE id_vehiculo = ?
         )");
    mysqli_stmt_bind_param($stmt_pr, "i", $id_vehiculo);
    mysqli_stmt_execute($stmt_pr);
    $result_pr = mysqli_stmt_get_result($stmt_pr);
    while ($row = mysqli_fetch_assoc($result_pr)) {
        $piezas_por_rep[$row['id_reparacion']][] = $row;
    }
    mysqli_free_result($result_pr);
    mysqli_stmt_close($stmt_pr);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($vehiculo['marca'].' '.$vehiculo['modelo']) ?> - Mi Vehículo</title>
    <link rel="stylesheet" href="../css/custom.css">
    <script src="../node_modules/jquery/dist/jquery.min.js"></script>
    <script src="../node_modules/popper.js/dist/umd/popper.min.js"></script>
    <script src="../node_modules/bootstrap/dist/js/bootstrap.min.js"></script>
    <style>
        .stepper { display:flex; align-items:center; margin:1rem 0 0.5rem; }
        .step { display:flex; flex-direction:column; align-items:center; flex:1; text-align:center; }
        .step-circle { width:38px; height:38px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:1.1rem; font-weight:bold; border:3px solid #dee2e6; background:#fff; color:#adb5bd; }
        .step-label { font-size:0.78rem; margin-top:5px; color:#6c757d; font-weight:500; }
        .step-line { flex:1; height:4px; background:#dee2e6; margin-bottom:22px; }
        .step[data-step="Pendiente"].active .step-circle,.step[data-step="Pendiente"].done .step-circle{background:#ffc107;border-color:#ffc107;color:#fff;}
        .step[data-step="Pendiente"].active .step-label{color:#d39e00;font-weight:700;}
        .step[data-step="En proceso"].active .step-circle,.step[data-step="En proceso"].done .step-circle{background:#17a2b8;border-color:#17a2b8;color:#fff;}
        .step[data-step="En proceso"].active .step-label{color:#138496;font-weight:700;}
        .step[data-step="Finalizado"].active .step-circle,.step[data-step="Finalizado"].done .step-circle{background:#28a745;border-color:#28a745;color:#fff;}
        .step[data-step="Finalizado"].active .step-label{color:#1e7e34;font-weight:700;}
        .step-line.done{background:#28a745;}
        .step-line.partial-info{background:#17a2b8;}
        .historial-timeline{list-style:none;padding:0;margin:0;}
        .historial-timeline li{display:flex;align-items:center;gap:10px;padding:6px 0;border-bottom:1px solid #f0f0f0;font-size:0.85rem;}
        .historial-timeline li:last-child{border-bottom:none;}
        .hist-dot{width:12px;height:12px;border-radius:50%;flex-shrink:0;}
        .hist-dot.Pendiente{background:#ffc107;}
        .hist-dot.En-proceso{background:#17a2b8;}
        .hist-dot.Finalizado{background:#28a745;}
        .mano-obra-box{background:#f8f9fa;border-radius:6px;padding:12px 16px;margin-top:12px;}
        .mano-obra-box .mo-row{display:flex;justify-content:space-between;font-size:0.88rem;padding:3px 0;}
        .mano-obra-box .mo-total{font-weight:700;border-top:1px solid #dee2e6;margin-top:6px;padding-top:6px;font-size:0.9rem;}
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark px-3">
    <a class="navbar-brand d-flex align-items-center" href="panelCliente.php">
        <img src="../assets/imagenes/logo.png" alt="Logo" width="42" height="40" class="mr-2">
        Gestión de Talleres
    </a>
    <button class="navbar-toggler ml-auto" type="button" data-toggle="collapse" data-target="#navbarCliente">
        <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarCliente">
        <ul class="navbar-nav mr-auto">
            <li class="nav-item"><a class="nav-link" href="panelCliente.php">Panel Principal</a></li>
            <li class="nav-item active"><a class="nav-link" href="panelCliente.php">Mis Vehículos</a></li>
        </ul>
        <span class="navbar-text mr-3">Hola, <?= htmlspecialchars($usuario['nombre']) ?></span>
        <a href="../auth/logout.php" class="btn btn-outline-light btn-sm">Salir</a>
    </div>
</nav>

<div class="container mt-4">

    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <h4 class="card-title mb-1"><?= htmlspecialchars($vehiculo['marca'].' '.$vehiculo['modelo']) ?> (<?= $vehiculo['anio'] ?>)</h4>
                    <p class="mb-1"><strong>Matrícula:</strong> <?= htmlspecialchars($vehiculo['matricula']) ?></p>
                    <p class="mb-3"><strong>Propietario:</strong> <?= htmlspecialchars($vehiculo['nombre'].' '.$vehiculo['apellidos']) ?></p>
                </div>
                <?php if ($estado_actual === 'Finalizado'): ?>
                <a href="factura.php?id=<?= $id_vehiculo ?>" target="_blank" class="btn btn-success">
                    📄 Descargar factura
                </a>
                <?php else: ?>
                <span class="badge badge-secondary p-2" style="font-size:0.8rem">Factura disponible al finalizar</span>
                <?php endif; ?>
            </div>

            <?php
                $pasos  = ['Pendiente','En proceso','Finalizado'];
                $iconos = ['⏳','🔧','✅'];
                $idx_actual = array_search($estado_actual, $pasos);
                if ($idx_actual === false) $idx_actual = 0;
            ?>
            <strong class="d-block mb-1">Estado del vehículo:</strong>
            <div class="stepper">
                <?php foreach ($pasos as $i => $paso): ?>
                    <?php if ($i > 0):
                        $lc = 'step-line';
                        if ($i===1 && $idx_actual>=1) $lc .= ' partial-info';
                        if ($i===2 && $idx_actual>=2) $lc .= ' done';
                    ?><div class="<?= $lc ?>"></div><?php endif; ?>
                    <?php $sc = 'step'.($i<$idx_actual?' done':'').($i===$idx_actual?' active':''); ?>
                    <div class="<?= $sc ?>" data-step="<?= $paso ?>">
                        <div class="step-circle"><?= $iconos[$i] ?></div>
                        <span class="step-label"><?= $paso ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <?php if (!empty($historial)): ?>
    <div class="card shadow-sm mb-4">
        <div class="card-header py-2"><strong>📋 Historial de estados</strong></div>
        <div class="card-body py-2">
            <ul class="historial-timeline">
                <?php foreach ($historial as $h): $dot = str_replace(' ','-',$h['estado']); ?>
                <li>
                    <span class="hist-dot <?= $dot ?>"></span>
                    <span><?= htmlspecialchars($h['estado']) ?></span>
                    <span class="text-muted ml-auto"><?= date('d/m/Y H:i', strtotime($h['fecha_actualizacion'])) ?></span>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
    <?php endif; ?>

    <h5 class="mb-3">Reparaciones / Mantenimientos</h5>
    <?php foreach ($reparaciones as $rep):
        $id_rep     = $rep['id_reparacion'];
        $rep_estado = $rep['estado'] ?? 'Pendiente';
        $badge_map  = ['Pendiente'=>'warning','En proceso'=>'info','Finalizado'=>'success'];
        $badge_rep  = $badge_map[$rep_estado] ?? 'secondary';
        $piezas     = $piezas_por_rep[$id_rep] ?? [];
        $total_piezas = array_sum(array_map(fn($p) => $p['cantidad_usada'] * $p['precio_unitario'], $piezas));
        $horas      = floatval($rep['horas_mano_obra'] ?? 0);
        $precio_h   = floatval($rep['precio_hora'] ?? 0);
        $total_mo   = $horas * $precio_h;
    ?>
    <div class="card shadow-sm mb-3">
        <div class="card-header py-2 d-flex justify-content-between align-items-center">
            <strong><?= htmlspecialchars($rep['titulo']) ?></strong>
            <span class="badge badge-<?= $badge_rep ?>"><?= $rep_estado ?></span>
        </div>
        <div class="card-body py-3">
            <p class="text-muted mb-2" style="font-size:0.9rem"><?= htmlspecialchars($rep['descripcion']) ?></p>
            <div class="row">
                <div class="col-sm-4"><small class="text-muted">Fecha estimada</small><br><strong><?= htmlspecialchars($rep['fecha_estimada']) ?></strong></div>
                <div class="col-sm-4"><small class="text-muted">Coste estimado</small><br><strong><?= number_format($rep['costo_estimado'],2) ?> €</strong></div>
                <div class="col-sm-4"><small class="text-muted">Coste final</small><br><strong><?= $rep['costo_final'] ? number_format($rep['costo_final'],2).' €' : '—' ?></strong></div>
            </div>

            <?php if (!empty($piezas)): ?>
            <hr>
            <p class="mb-1"><strong>🔧 Piezas utilizadas</strong></p>
            <table class="table table-sm">
                <thead><tr><th>Producto</th><th>Cant.</th><th>Precio/ud.</th><th>Subtotal</th></tr></thead>
                <tbody>
                <?php foreach ($piezas as $pz): ?>
                <tr>
                    <td><?= htmlspecialchars($pz['nombre']) ?></td>
                    <td><?= $pz['cantidad_usada'] ?></td>
                    <td><?= number_format($pz['precio_unitario'],2) ?> €</td>
                    <td><?= number_format($pz['cantidad_usada']*$pz['precio_unitario'],2) ?> €</td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <p class="text-right mb-0 text-muted" style="font-size:0.88rem">Subtotal piezas: <strong><?= number_format($total_piezas,2) ?> €</strong></p>
            <?php endif; ?>

            <?php if ($horas > 0): ?>
            <div class="mano-obra-box <?= empty($piezas) ? 'mt-3' : '' ?>">
                <p class="mb-2"><strong>⏱️ Mano de obra</strong></p>
                <div class="mo-row">
                    <span>Horas realizadas</span>
                    <span><?= number_format($horas, 2) ?> h</span>
                </div>
                <div class="mo-row">
                    <span>Precio por hora</span>
                    <span><?= number_format($precio_h, 2) ?> €/h</span>
                </div>
                <div class="mo-row mo-total">
                    <span>Total mano de obra</span>
                    <span><?= number_format($total_mo, 2) ?> €</span>
                </div>
            </div>
            <?php endif; ?>

        </div>
    </div>
    <?php endforeach; ?>
    <?php if (empty($reparaciones)): ?>
        <p class="text-muted">No hay reparaciones registradas para este vehículo.</p>
    <?php endif; ?>

    <a href="panelCliente.php" class="btn btn-secondary mt-2 mb-4">← Volver al panel</a>

</div>
</body>
</html>
