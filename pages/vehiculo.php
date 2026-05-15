<?php
session_start();
require_once "../conf/db.php";

if (!isset($_SESSION["email"]) || $_SESSION['rol'] !== 'Mecanico') {
    header("Location: ../login.php");
    exit();
}

$email = $_SESSION["email"];
$stmt_nombre = mysqli_prepare($conexion, "SELECT id_usuario, nombre FROM usuarios WHERE email = ?");
mysqli_stmt_bind_param($stmt_nombre, "s", $email);
mysqli_stmt_execute($stmt_nombre);
$usuario_nav = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_nombre));
$nombre_usuario = $usuario_nav['nombre'];
$id_mecanico    = $usuario_nav['id_usuario'];

$id_vehiculo = $_GET['id'] ?? $_POST['id_vehiculo'] ?? null;
if (!$id_vehiculo) die("Vehículo no especificado.");
$id_vehiculo = intval($id_vehiculo);

// ── Nueva reparación / mantenimiento ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'nueva_reparacion') {
    $titulo      = trim($_POST['titulo_nueva']);
    $descripcion = trim($_POST['descripcion_nueva']);
    $fecha       = $_POST['fecha_nueva'];
    $costo       = floatval($_POST['costo_nueva']);
    if ($titulo && $fecha) {
        $stmt_nr = mysqli_prepare($conexion,
            "INSERT INTO reparaciones (id_vehiculo, titulo, descripcion, fecha_estimada, costo_estimado, estado)
             VALUES (?, ?, ?, ?, ?, 'Pendiente')");
        mysqli_stmt_bind_param($stmt_nr, "isssd", $id_vehiculo, $titulo, $descripcion, $fecha, $costo);
        mysqli_stmt_execute($stmt_nr);
        header("Location: vehiculo.php?id=$id_vehiculo&guardado=1");
    } else {
        header("Location: vehiculo.php?id=$id_vehiculo&error=datos");
    }
    exit();
}

// ── Añadir pieza a reparación ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_pieza') {
    $id_rep    = intval($_POST['id_reparacion']);
    $id_prod   = intval($_POST['id_producto']);
    $cantidad  = intval($_POST['cantidad_usada']);
    if ($cantidad > 0 && $id_rep && $id_prod) {
        $stmt_stock = mysqli_prepare($conexion, "SELECT cantidad_disponible FROM productos WHERE id_producto = ?");
        mysqli_stmt_bind_param($stmt_stock, "i", $id_prod);
        mysqli_stmt_execute($stmt_stock);
        $stock_row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_stock));
        if ($stock_row && $stock_row['cantidad_disponible'] >= $cantidad) {
            $stmt_ins = mysqli_prepare($conexion, "INSERT INTO productos_reparacion (id_producto, id_reparacion, cantidad_usada) VALUES (?, ?, ?)");
            mysqli_stmt_bind_param($stmt_ins, "iii", $id_prod, $id_rep, $cantidad);
            mysqli_stmt_execute($stmt_ins);
            $stmt_upd = mysqli_prepare($conexion, "UPDATE productos SET cantidad_disponible = cantidad_disponible - ? WHERE id_producto = ?");
            mysqli_stmt_bind_param($stmt_upd, "ii", $cantidad, $id_prod);
            mysqli_stmt_execute($stmt_upd);
            header("Location: vehiculo.php?id=$id_vehiculo&guardado=1");
        } else {
            $disponible = $stock_row ? $stock_row['cantidad_disponible'] : 0;
            header("Location: vehiculo.php?id=$id_vehiculo&error=stock&disponible=$disponible");
        }
    } else {
        header("Location: vehiculo.php?id=$id_vehiculo&error=datos");
    }
    exit();
}

// ── Quitar pieza de reparación (devuelve stock) ──
if (isset($_GET['delete_pieza'])) {
    $id_pr = intval($_GET['delete_pieza']);
    $stmt_get = mysqli_prepare($conexion, "SELECT id_producto, cantidad_usada FROM productos_reparacion WHERE id_producto_reparacion = ?");
    mysqli_stmt_bind_param($stmt_get, "i", $id_pr);
    mysqli_stmt_execute($stmt_get);
    $pr_row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_get));
    if ($pr_row) {
        $stmt_dev = mysqli_prepare($conexion, "UPDATE productos SET cantidad_disponible = cantidad_disponible + ? WHERE id_producto = ?");
        mysqli_stmt_bind_param($stmt_dev, "ii", $pr_row['cantidad_usada'], $pr_row['id_producto']);
        mysqli_stmt_execute($stmt_dev);
        $stmt_del = mysqli_prepare($conexion, "DELETE FROM productos_reparacion WHERE id_producto_reparacion = ?");
        mysqli_stmt_bind_param($stmt_del, "i", $id_pr);
        mysqli_stmt_execute($stmt_del);
    }
    header("Location: vehiculo.php?id=$id_vehiculo");
    exit();
}

// ── Guardar reparación (incluye horas_mano_obra y precio_hora) ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_reparacion']) && !isset($_POST['action'])) {
    $id            = intval($_POST['id_reparacion']);
    $titulo        = $_POST['titulo'];
    $descripcion   = $_POST['descripcion'];
    $fecha         = $_POST['fecha_estimacion'];
    $costoEstimado = floatval($_POST['costo_estimado']);
    $costoFinal    = isset($_POST['costo_final']) && $_POST['costo_final'] !== '' ? floatval($_POST['costo_final']) : null;
    $nuevo_estado  = $_POST['estado'];
    $horas         = isset($_POST['horas_mano_obra']) && $_POST['horas_mano_obra'] !== '' ? floatval($_POST['horas_mano_obra']) : null;
    $precio_hora   = isset($_POST['precio_hora'])    && $_POST['precio_hora']    !== '' ? floatval($_POST['precio_hora'])    : null;

    $sql = "UPDATE reparaciones SET titulo=?, descripcion=?, fecha_estimada=?, costo_estimado=?, costo_final=?, estado=?, horas_mano_obra=?, precio_hora=? WHERE id_reparacion=?";
    $stmt = mysqli_prepare($conexion, $sql);
    mysqli_stmt_bind_param($stmt, "sssddsddi", $titulo, $descripcion, $fecha, $costoEstimado, $costoFinal, $nuevo_estado, $horas, $precio_hora, $id);
    mysqli_stmt_execute($stmt);

    // Actualizar historial de estados si cambió
    $stmt_ult = mysqli_prepare($conexion, "SELECT estado FROM estados_vehiculo WHERE id_vehiculo = ? ORDER BY fecha_actualizacion DESC LIMIT 1");
    mysqli_stmt_bind_param($stmt_ult, "i", $id_vehiculo);
    mysqli_stmt_execute($stmt_ult);
    $ult = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_ult));
    $estado_anterior = $ult['estado'] ?? null;

    if ($estado_anterior === null || $nuevo_estado !== $estado_anterior) {
        $stmt_ins = mysqli_prepare($conexion, "INSERT INTO estados_vehiculo (id_vehiculo, estado, fecha_actualizacion) VALUES (?, ?, NOW())");
        mysqli_stmt_bind_param($stmt_ins, "is", $id_vehiculo, $nuevo_estado);
        mysqli_stmt_execute($stmt_ins);
    }

    header("Location: vehiculo.php?id=$id_vehiculo&guardado=1");
    exit();
}

// ── Consultas de datos ──
$stmt_v = mysqli_prepare($conexion, "SELECT v.*, u.id_usuario AS id_propietario, u.nombre, u.apellidos, u.email, u.telefono FROM vehiculos v JOIN usuarios u ON v.id_usuario = u.id_usuario WHERE v.id_vehiculo = ?");
mysqli_stmt_bind_param($stmt_v, "i", $id_vehiculo);
mysqli_stmt_execute($stmt_v);
$vehiculo = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_v));

$stmt_r = mysqli_prepare($conexion, "SELECT * FROM reparaciones WHERE id_vehiculo = ? ORDER BY fecha_estimada ASC");
mysqli_stmt_bind_param($stmt_r, "i", $id_vehiculo);
mysqli_stmt_execute($stmt_r);
$result_reparaciones = mysqli_stmt_get_result($stmt_r);
$reparaciones = mysqli_fetch_all($result_reparaciones, MYSQLI_ASSOC);

$stmt_e = mysqli_prepare($conexion, "SELECT estado FROM estados_vehiculo WHERE id_vehiculo = ? ORDER BY fecha_actualizacion DESC LIMIT 1");
mysqli_stmt_bind_param($stmt_e, "i", $id_vehiculo);
mysqli_stmt_execute($stmt_e);
$estado_actual_row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_e));
$estado_actual = $estado_actual_row['estado'] ?? 'Pendiente';

$stmt_hist = mysqli_prepare($conexion, "SELECT estado, fecha_actualizacion FROM estados_vehiculo WHERE id_vehiculo = ? ORDER BY fecha_actualizacion ASC");
mysqli_stmt_bind_param($stmt_hist, "i", $id_vehiculo);
mysqli_stmt_execute($stmt_hist);
$historial_estados = mysqli_fetch_all(mysqli_stmt_get_result($stmt_hist), MYSQLI_ASSOC);

$productos_stock = mysqli_fetch_all(
    mysqli_query($conexion, "SELECT id_producto, nombre, cantidad_disponible, precio_unitario FROM productos WHERE cantidad_disponible > 0 ORDER BY nombre ASC"),
    MYSQLI_ASSOC
);

$piezas_por_rep = [];
$stmt_pr = mysqli_prepare($conexion,
    "SELECT pr.id_producto_reparacion, pr.id_reparacion, pr.cantidad_usada,
            p.nombre, p.precio_unitario
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
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Vehículo <?= htmlspecialchars($vehiculo['marca'] . ' ' . $vehiculo['modelo']); ?> - Gestión de Talleres</title>
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
        .step[data-step="Pendiente"].active .step-circle,.step[data-step="Pendiente"].done .step-circle { background:#ffc107; border-color:#ffc107; color:#fff; }
        .step[data-step="Pendiente"].active .step-label { color:#d39e00; font-weight:700; }
        .step[data-step="En proceso"].active .step-circle,.step[data-step="En proceso"].done .step-circle { background:#17a2b8; border-color:#17a2b8; color:#fff; }
        .step[data-step="En proceso"].active .step-label { color:#138496; font-weight:700; }
        .step[data-step="Finalizado"].active .step-circle,.step[data-step="Finalizado"].done .step-circle { background:#28a745; border-color:#28a745; color:#fff; }
        .step[data-step="Finalizado"].active .step-label { color:#1e7e34; font-weight:700; }
        .step-line.done { background:#28a745; }
        .step-line.partial-info { background:#17a2b8; }
        .historial-timeline { list-style:none; padding:0; margin:0; }
        .historial-timeline li { display:flex; align-items:center; gap:10px; padding:6px 0; border-bottom:1px solid #f0f0f0; font-size:0.85rem; }
        .historial-timeline li:last-child { border-bottom:none; }
        .hist-dot { width:12px; height:12px; border-radius:50%; flex-shrink:0; }
        .hist-dot.Pendiente { background:#ffc107; }
        .hist-dot.En-proceso { background:#17a2b8; }
        .hist-dot.Finalizado { background:#28a745; }
        .piezas-section { background:#f8f9fa; border:1px solid #dee2e6; border-radius:6px; padding:12px 16px; margin-top:12px; }
        .mano-obra-section { background:#fff8e1; border:1px solid #ffe082; border-radius:6px; padding:12px 16px; margin-top:8px; }
        .total-section { background:#e8f5e9; border:1px solid #a5d6a7; border-radius:6px; padding:10px 16px; margin-top:8px; }
        .mo-saved { font-size:0.82rem; color:#555; margin-top:4px; }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark px-3">
    <a class="navbar-brand d-flex align-items-center" href="panelMecanico.php">
        <img src="../assets/imagenes/logo.png" alt="Logo" width="42" height="40" class="mr-2">
        Gestión de Talleres
    </a>
    <button class="navbar-toggler ml-auto" type="button" data-toggle="collapse" data-target="#navbarMecanico"
        aria-controls="navbarMecanico" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarMecanico">
        <ul class="navbar-nav mr-auto">
            <li class="nav-item"><a class="nav-link" href="panelMecanico.php">Panel Principal</a></li>
            <li class="nav-item active"><a class="nav-link" href="vehiculosMecanico.php">Vehículos</a></li>
            <li class="nav-item"><a class="nav-link" href="inventario.php">Inventario</a></li>
            <li class="nav-item"><a class="nav-link" href="#">Citas</a></li>
        </ul>
        <span class="navbar-text mr-3">Hola, <?= htmlspecialchars($nombre_usuario) ?></span>
        <a href="../auth/logout.php" class="btn btn-outline-light btn-sm">Salir</a>
    </div>
</nav>

<div class="container mt-4">

    <?php if (isset($_GET['guardado'])): ?>
    <div class="alert alert-success alert-dismissible fade show">
        ✅ Guardado correctamente.
        <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
    </div>
    <?php endif; ?>

    <?php if (isset($_GET['error']) && $_GET['error'] === 'stock'): ?>
    <div class="alert alert-danger alert-dismissible fade show">
        ❌ <strong>Stock insuficiente.</strong> Solo hay <?= intval($_GET['disponible'] ?? 0) ?> unidad(es) disponibles de ese producto. No se ha añadido la pieza.
        <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
    </div>
    <?php elseif (isset($_GET['error']) && $_GET['error'] === 'datos'): ?>
    <div class="alert alert-warning alert-dismissible fade show">
        ⚠️ Datos incorrectos. Asegúrate de seleccionar un producto y una cantidad mayor que 0.
        <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
    </div>
    <?php endif; ?>

    <!-- Datos del vehículo -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <h4 class="card-title mb-1"><?= htmlspecialchars($vehiculo['marca'].' '.$vehiculo['modelo']); ?> (<?= $vehiculo['anio'] ?>)</h4>
            <p class="card-text mb-1"><strong>Matrícula:</strong> <?= htmlspecialchars($vehiculo['matricula']); ?></p>
            <p class="card-text mb-1"><strong>Propietario:</strong> <?= htmlspecialchars($vehiculo['nombre'].' '.$vehiculo['apellidos']); ?> | <?= htmlspecialchars($vehiculo['email']); ?></p>
            <p class="card-text mb-3"><strong>Teléfono:</strong> <?= htmlspecialchars($vehiculo['telefono']); ?></p>
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
                    <?php
                        $sc = 'step';
                        if ($i < $idx_actual)  $sc .= ' done';
                        if ($i === $idx_actual) $sc .= ' active';
                    ?>
                    <div class="<?= $sc ?>" data-step="<?= $paso ?>">
                        <div class="step-circle"><?= $iconos[$i] ?></div>
                        <span class="step-label"><?= $paso ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Historial de estados -->
    <?php if (!empty($historial_estados)): ?>
    <div class="card shadow-sm mb-4">
        <div class="card-header py-2"><strong>📋 Historial de estados</strong></div>
        <div class="card-body py-2">
            <ul class="historial-timeline">
                <?php foreach ($historial_estados as $h):
                    $dot = str_replace(' ','-',$h['estado']);
                ?>
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

    <!-- Cabecera sección reparaciones -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="mb-0">Reparaciones / Mantenimientos</h5>
        <button class="btn btn-success" data-toggle="modal" data-target="#nuevaReparacionModal">
            ➕ Nueva Reparación / Mantenimiento
        </button>
    </div>

    <?php if (empty($reparaciones)): ?>
    <div class="alert alert-info">
        Este vehículo aún no tiene reparaciones. Usa el botón <strong>➕ Nueva Reparación / Mantenimiento</strong> para añadir la primera.
    </div>
    <?php endif; ?>

    <?php foreach ($reparaciones as $rep):
        $rep_estado = $rep['estado'] ?? 'Pendiente';
        $badge_map  = ['Pendiente'=>'warning','En proceso'=>'info','Finalizado'=>'success'];
        $badge_rep  = $badge_map[$rep_estado] ?? 'secondary';
        $id_rep     = $rep['id_reparacion'];
        $piezas     = $piezas_por_rep[$id_rep] ?? [];
        $total_piezas = array_sum(array_map(fn($p) => $p['cantidad_usada'] * $p['precio_unitario'], $piezas));
        $horas_guardadas   = floatval($rep['horas_mano_obra'] ?? 0);
        $tarifa_guardada   = floatval($rep['precio_hora']     ?? 0);
        $mo_guardada       = $horas_guardadas * $tarifa_guardada;
    ?>
    <div class="card shadow-sm mb-3">
        <div class="card-header d-flex justify-content-between align-items-center py-2">
            <span><strong><?= htmlspecialchars($rep['titulo']) ?></strong> &nbsp;<span class="badge badge-<?= $badge_rep ?>"><?= $rep_estado ?></span></span>
            <button class="btn btn-sm btn-primary"
                data-toggle="modal" data-target="#editarReparacionModal"
                data-id="<?= $id_rep ?>"
                data-titulo="<?= htmlspecialchars($rep['titulo'], ENT_QUOTES) ?>"
                data-descripcion="<?= htmlspecialchars($rep['descripcion'], ENT_QUOTES) ?>"
                data-fecha="<?= htmlspecialchars($rep['fecha_estimada'], ENT_QUOTES) ?>"
                data-costo_estimado="<?= htmlspecialchars($rep['costo_estimado'], ENT_QUOTES) ?>"
                data-costo_final="<?= htmlspecialchars($rep['costo_final'] ?? '', ENT_QUOTES) ?>"
                data-estado="<?= htmlspecialchars($rep_estado, ENT_QUOTES) ?>"
                data-horas="<?= $horas_guardadas ?>"
                data-precio_hora="<?= $tarifa_guardada ?>"
            >Editar</button>
        </div>
        <div class="card-body py-2">
            <p class="mb-1 text-muted" style="font-size:0.9rem"><?= htmlspecialchars($rep['descripcion']) ?></p>
            <small class="text-muted">Fecha estimada: <?= htmlspecialchars($rep['fecha_estimada']) ?> &nbsp;|
            Coste estimado: <?= number_format($rep['costo_estimado'],2) ?> € &nbsp;|
            Coste final: <strong><?= ($rep['costo_final'] ? number_format($rep['costo_final'],2).' €' : '—') ?></strong></small>

            <!-- Piezas usadas -->
            <div class="piezas-section mt-3">
                <strong>🔧 Piezas utilizadas</strong>
                <?php if (!empty($piezas)): ?>
                <table class="table table-sm mt-2 mb-1">
                    <thead><tr><th>Producto</th><th>Cantidad</th><th>Precio/ud.</th><th>Subtotal</th><th></th></tr></thead>
                    <tbody>
                    <?php foreach ($piezas as $pz): ?>
                    <tr>
                        <td><?= htmlspecialchars($pz['nombre']) ?></td>
                        <td><?= $pz['cantidad_usada'] ?></td>
                        <td><?= number_format($pz['precio_unitario'],2) ?> €</td>
                        <td><?= number_format($pz['cantidad_usada']*$pz['precio_unitario'],2) ?> €</td>
                        <td>
                            <a href="vehiculo.php?id=<?= $id_vehiculo ?>&delete_pieza=<?= $pz['id_producto_reparacion'] ?>"
                               class="btn btn-danger btn-sm"
                               onclick="return confirm('¿Quitar esta pieza? Se devolverá al stock.')">×</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <p class="text-right mb-0"><strong>Total piezas: <?= number_format($total_piezas,2) ?> €</strong></p>
                <?php else: ?>
                <p class="text-muted mb-1 mt-1" style="font-size:0.85rem">Sin piezas añadidas todavía.</p>
                <?php endif; ?>

                <!-- Formulario añadir pieza -->
                <form method="POST" action="vehiculo.php" class="form-inline mt-2">
                    <input type="hidden" name="action" value="add_pieza">
                    <input type="hidden" name="id_vehiculo" value="<?= $id_vehiculo ?>">
                    <input type="hidden" name="id_reparacion" value="<?= $id_rep ?>">
                    <select name="id_producto" class="form-control form-control-sm mr-2" required style="min-width:180px">
                        <option value="">-- Seleccionar producto --</option>
                        <?php foreach ($productos_stock as $ps): ?>
                        <option value="<?= $ps['id_producto'] ?>"><?= htmlspecialchars($ps['nombre']) ?> (<?= $ps['cantidad_disponible'] ?> uds.)</option>
                        <?php endforeach; ?>
                    </select>
                    <input type="number" name="cantidad_usada" class="form-control form-control-sm mr-2" placeholder="Cant." min="1" required style="width:70px">
                    <button type="submit" class="btn btn-success btn-sm">+ Añadir</button>
                </form>
            </div>

            <!-- Mano de obra -->
            <div class="mano-obra-section" id="manoObra_<?= $id_rep ?>">
                <strong>⏱️ Mano de obra</strong>
                <?php if ($horas_guardadas > 0): ?>
                <p class="mo-saved">✅ Guardado: <?= number_format($horas_guardadas,2) ?> h × <?= number_format($tarifa_guardada,2) ?> €/h = <strong><?= number_format($mo_guardada,2) ?> €</strong></p>
                <?php endif; ?>
                <div class="form-inline mt-2">
                    <label class="mr-2">Horas:</label>
                    <input type="number" class="form-control form-control-sm mr-3 horas-input" step="0.5" min="0"
                           value="<?= $horas_guardadas > 0 ? $horas_guardadas : '' ?>" placeholder="0"
                           style="width:80px" data-rep="<?= $id_rep ?>">
                    <label class="mr-2">€ / hora:</label>
                    <input type="number" class="form-control form-control-sm mr-3 tarifa-input" step="0.01" min="0"
                           value="<?= $tarifa_guardada > 0 ? $tarifa_guardada : '' ?>" placeholder="0"
                           style="width:90px" data-rep="<?= $id_rep ?>">
                    <span class="text-muted">Subtotal M.O.: <strong class="subtotal-mo" data-rep="<?= $id_rep ?>"><?= $mo_guardada > 0 ? number_format($mo_guardada,2).' €' : '0.00 €' ?></strong></span>
                </div>
            </div>

            <!-- Total final -->
            <div class="total-section" id="total_<?= $id_rep ?>">
                <div class="d-flex justify-content-between align-items-center">
                    <span>💶 <strong>Coste total calculado:</strong> <span class="total-calculado" data-rep="<?= $id_rep ?>">—</span></span>
                    <button type="button" class="btn btn-warning btn-sm calcular-btn"
                            data-rep="<?= $id_rep ?>"
                            data-piezas="<?= $total_piezas ?>">
                        Calcular total
                    </button>
                    <form method="POST" action="vehiculo.php" class="ml-2 guardar-total-form" data-rep="<?= $id_rep ?>">
                        <input type="hidden" name="id_vehiculo"       value="<?= $id_vehiculo ?>">
                        <input type="hidden" name="id_reparacion"     value="<?= $id_rep ?>">
                        <input type="hidden" name="titulo"            value="<?= htmlspecialchars($rep['titulo'], ENT_QUOTES) ?>">
                        <input type="hidden" name="descripcion"       value="<?= htmlspecialchars($rep['descripcion'], ENT_QUOTES) ?>">
                        <input type="hidden" name="fecha_estimacion"  value="<?= htmlspecialchars($rep['fecha_estimada'], ENT_QUOTES) ?>">
                        <input type="hidden" name="costo_estimado"    value="<?= htmlspecialchars($rep['costo_estimado'], ENT_QUOTES) ?>">
                        <input type="hidden" name="estado"            value="<?= htmlspecialchars($rep_estado, ENT_QUOTES) ?>">
                        <input type="hidden" name="costo_final"       class="costo-final-hidden" value="">
                        <input type="hidden" name="horas_mano_obra"   class="horas-hidden"       value="">
                        <input type="hidden" name="precio_hora"       class="precio-hora-hidden" value="">
                        <button type="submit" class="btn btn-success btn-sm guardar-total-btn" data-rep="<?= $id_rep ?>" disabled>
                            Guardar en reparación
                        </button>
                    </form>
                </div>
            </div>

        </div>
    </div>
    <?php endforeach; ?>

    <a href="vehiculosMecanico.php" class="btn btn-secondary mt-2 mb-4">← Volver a Vehículos</a>

</div>

<!-- Modal Nueva Reparación / Mantenimiento -->
<div class="modal fade" id="nuevaReparacionModal" tabindex="-1">
  <div class="modal-dialog">
    <form method="POST" action="vehiculo.php">
      <input type="hidden" name="action" value="nueva_reparacion">
      <input type="hidden" name="id_vehiculo" value="<?= $id_vehiculo ?>">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">➕ Nueva Reparación / Mantenimiento</h5>
          <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
        </div>
        <div class="modal-body">
            <div class="form-group">
                <label>Tipo</label>
                <select class="form-control" id="tipo_nueva" onchange="prefillTitulo(this)">
                    <option value="">-- Seleccionar tipo --</option>
                    <option value="Reparación">🔧 Reparación</option>
                    <option value="Mantenimiento">🛠️ Mantenimiento</option>
                    <option value="Revisión">🔍 Revisión</option>
                    <option value="Diagnóstico">📋 Diagnóstico</option>
                </select>
            </div>
            <div class="form-group">
                <label>Título <span class="text-danger">*</span></label>
                <input type="text" class="form-control" name="titulo_nueva" id="titulo_nueva"
                       placeholder="Ej: Cambio de aceite, Revisión de frenos..." required>
            </div>
            <div class="form-group">
                <label>Descripción</label>
                <textarea class="form-control" name="descripcion_nueva" rows="3"
                          placeholder="Detalla el trabajo a realizar..."></textarea>
            </div>
            <div class="form-row">
                <div class="form-group col-md-6">
                    <label>Fecha estimada <span class="text-danger">*</span></label>
                    <input type="date" class="form-control" name="fecha_nueva" required
                           min="<?= date('Y-m-d') ?>">
                </div>
                <div class="form-group col-md-6">
                    <label>Coste estimado (€)</label>
                    <input type="number" step="0.01" min="0" class="form-control"
                           name="costo_nueva" placeholder="0.00" value="0">
                </div>
            </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-success">✅ Crear reparación</button>
        </div>
      </div>
    </form>
  </div>
</div>

<!-- Modal Editar Reparación -->
<div class="modal fade" id="editarReparacionModal" tabindex="-1">
  <div class="modal-dialog">
    <form method="POST" action="vehiculo.php">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Editar Reparación</h5>
          <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
        </div>
        <div class="modal-body">
            <input type="hidden" name="id_reparacion"   id="modal_id_reparacion">
            <input type="hidden" name="id_vehiculo"     value="<?= $id_vehiculo ?>">
            <input type="hidden" name="horas_mano_obra" id="modal_horas_mano_obra">
            <input type="hidden" name="precio_hora"     id="modal_precio_hora">
            <div class="form-group">
                <label>Título</label>
                <input type="text" class="form-control" name="titulo" id="modal_titulo" required>
            </div>
            <div class="form-group">
                <label>Descripción</label>
                <textarea class="form-control" name="descripcion" id="modal_descripcion" rows="3"></textarea>
            </div>
            <div class="form-group">
                <label>Fecha estimada</label>
                <input type="date" class="form-control" name="fecha_estimacion" id="modal_fecha" required>
            </div>
            <div class="form-group">
                <label>Coste estimado (€)</label>
                <input type="number" step="0.01" class="form-control" name="costo_estimado" id="modal_costo_estimado" required>
            </div>
            <div class="form-group">
                <label>Coste final (€)</label>
                <input type="number" step="0.01" class="form-control" name="costo_final" id="modal_costo_final">
            </div>
            <div class="form-group">
                <label>Estado</label>
                <select class="form-control" name="estado" id="modal_estado" required>
                    <option value="Pendiente">⏳ Pendiente</option>
                    <option value="En proceso">🔧 En proceso</option>
                    <option value="Finalizado">✅ Finalizado</option>
                </select>
            </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-primary">Guardar cambios</button>
        </div>
      </div>
    </form>
  </div>
</div>

<script>
function prefillTitulo(sel) {
    var t = document.getElementById('titulo_nueva');
    if (!t.value) t.value = sel.value;
}

$('#editarReparacionModal').on('show.bs.modal', function (event) {
    var b = $(event.relatedTarget), m = $(this);
    m.find('#modal_id_reparacion').val(b.data('id'));
    m.find('#modal_titulo').val(b.data('titulo'));
    m.find('#modal_descripcion').val(b.data('descripcion'));
    m.find('#modal_fecha').val(b.data('fecha'));
    m.find('#modal_costo_estimado').val(b.data('costo_estimado'));
    m.find('#modal_costo_final').val(b.data('costo_final'));
    m.find('#modal_estado').val(b.data('estado'));
    m.find('#modal_horas_mano_obra').val(b.data('horas') || '');
    m.find('#modal_precio_hora').val(b.data('precio_hora') || '');
});

$(document).on('input', '.horas-input, .tarifa-input', function () {
    var rep    = $(this).data('rep');
    var horas  = parseFloat($('.horas-input[data-rep="'  + rep + '"]').val()) || 0;
    var tarifa = parseFloat($('.tarifa-input[data-rep="' + rep + '"]').val()) || 0;
    $('.subtotal-mo[data-rep="' + rep + '"]').text((horas * tarifa).toFixed(2) + ' €');
});

$(document).on('click', '.calcular-btn', function () {
    var rep      = $(this).data('rep');
    var piezas   = parseFloat($(this).data('piezas')) || 0;
    var horas    = parseFloat($('.horas-input[data-rep="'  + rep + '"]').val()) || 0;
    var tarifa   = parseFloat($('.tarifa-input[data-rep="' + rep + '"]').val()) || 0;
    var manoObra = horas * tarifa;
    var total    = piezas + manoObra;

    $('.total-calculado[data-rep="' + rep + '"]').html(
        '<strong>' + total.toFixed(2) + ' €</strong> (Piezas: ' + piezas.toFixed(2) + ' € + M.O.: ' + manoObra.toFixed(2) + ' €)'
    );

    var $form = $('.guardar-total-form[data-rep="' + rep + '"]');
    $form.find('.costo-final-hidden').val(total.toFixed(2));
    $form.find('.horas-hidden').val(horas > 0 ? horas : '');
    $form.find('.precio-hora-hidden').val(tarifa > 0 ? tarifa : '');
    $('.guardar-total-btn[data-rep="' + rep + '"]').prop('disabled', false);
});
</script>

</body>
</html>
