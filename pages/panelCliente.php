<?php
session_start();
require_once "../conf/db.php";

if (!isset($_SESSION["email"])) {
    header("Location: ../login.php");
    exit();
}
if ($_SESSION['rol'] !== 'Cliente') {
    header("Location: ../login.php");
    exit();
}

$email = $_SESSION["email"];
$stmt_user = mysqli_prepare($conexion, "SELECT * FROM usuarios WHERE email = ?");
mysqli_stmt_bind_param($stmt_user, "s", $email);
mysqli_stmt_execute($stmt_user);
$usuario = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_user));
$id_usuario = $usuario['id_usuario'];

// Vehículos con su último estado
$stmt_v = mysqli_prepare($conexion, "
    SELECT v.*,
        (SELECT estado FROM estados_vehiculo WHERE id_vehiculo = v.id_vehiculo ORDER BY fecha_actualizacion DESC LIMIT 1) AS estado_actual
    FROM vehiculos v
    WHERE v.id_usuario = ?
");
mysqli_stmt_bind_param($stmt_v, "i", $id_usuario);
mysqli_stmt_execute($stmt_v);
$vehiculos = mysqli_fetch_all(mysqli_stmt_get_result($stmt_v), MYSQLI_ASSOC);
$num_vehiculos = count($vehiculos);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Panel Cliente - Gestión de Talleres</title>
    <link rel="stylesheet" href="../css/custom.css">
    <script src="../node_modules/jquery/dist/jquery.min.js"></script>
    <script src="../node_modules/popper.js/dist/umd/popper.min.js"></script>
    <script src="../node_modules/bootstrap/dist/js/bootstrap.min.js"></script>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark px-3">
    <a class="navbar-brand d-flex align-items-center" href="panelCliente.php">
        <img src="../assets/imagenes/logo.png" alt="Logo" width="42" height="40" class="mr-2">
        Gestión de Talleres
    </a>
    <button class="navbar-toggler ml-auto" type="button" data-toggle="collapse" data-target="#navbarCliente"
        aria-controls="navbarCliente" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarCliente">
        <ul class="navbar-nav mr-auto">
            <li class="nav-item active">
                <a class="nav-link" href="panelCliente.php">Panel Principal</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="panelCliente.php">Mis Vehículos</a>
            </li>
        </ul>
        <span class="navbar-text mr-3">Hola, <?= htmlspecialchars($usuario['nombre']) ?></span>
        <a href="../auth/logout.php" class="btn btn-outline-light btn-sm">Salir</a>
    </div>
</nav>

<div class="container mt-4">

    <h5 class="mb-4">Panel Cliente</h5>

    <!-- Tarjeta resumen -->
    <div class="row mb-4">
        <div class="col-md-4 mb-3">
            <div class="card shadow-sm">
                <div class="card-body">
                    <small class="text-muted">Mis vehículos</small>
                    <h3 class="mt-2"><?= $num_vehiculos ?></h3>
                </div>
            </div>
        </div>
    </div>

    <!-- Lista de vehículos -->
    <h6 class="mb-3">🚗 Mis Vehículos</h6>
    <div class="list-group">
        <?php foreach ($vehiculos as $v):
            $estado = $v['estado_actual'] ?? 'Sin estado';
            $badge_map = ['Pendiente' => 'warning', 'En proceso' => 'info', 'Finalizado' => 'success'];
            $badge = $badge_map[$estado] ?? 'secondary';
        ?>
        <div class="list-group-item d-flex justify-content-between align-items-center">
            <div>
                <strong><?= htmlspecialchars($v['marca'] . ' ' . $v['modelo'] . ' ' . $v['anio']) ?></strong><br>
                <small class="text-muted">Matrícula: <?= htmlspecialchars($v['matricula']) ?></small>
            </div>
            <div class="d-flex align-items-center">
                <span class="badge badge-<?= $badge ?> mr-2"><?= htmlspecialchars($estado) ?></span>
                <a href="vehiculoCliente.php?id=<?= $v['id_vehiculo'] ?>" class="btn btn-primary btn-sm">Ver detalles</a>
            </div>
        </div>
        <?php endforeach; ?>
        <?php if (empty($vehiculos)): ?>
            <div class="list-group-item text-muted">No tienes vehículos registrados.</div>
        <?php endif; ?>
    </div>

</div>

</body>
</html>
