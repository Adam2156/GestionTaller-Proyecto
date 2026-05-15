<?php
    session_start();
    require_once "../conf/db.php";

    if (!isset($_SESSION["email"])) {
        header("Location: ../login.php");
        exit();
    }

    // Subquery para coger SOLO el último estado de cada vehículo (evita duplicados)
    $sql = "SELECT v.id_vehiculo, v.marca, v.modelo, v.anio, v.matricula,
                   u.nombre, u.apellidos,
                   (
                       SELECT e.estado
                       FROM estados_vehiculo e
                       WHERE e.id_vehiculo = v.id_vehiculo
                       ORDER BY e.fecha_actualizacion DESC
                       LIMIT 1
                   ) AS estado
            FROM vehiculos v
            JOIN usuarios u ON v.id_usuario = u.id_usuario
            ORDER BY v.id_vehiculo DESC";

    $resultado = mysqli_query($conexion, $sql);

    $sql_count = "SELECT COUNT(*) AS total FROM vehiculos";
    $result_count = mysqli_query($conexion, $sql_count);
    $row_count = mysqli_fetch_assoc($result_count);
    $total_vehiculos = $row_count['total'];

    $email = $_SESSION["email"];
    $stmt_nombre = mysqli_prepare($conexion, "SELECT nombre FROM usuarios WHERE email = ?");
    mysqli_stmt_bind_param($stmt_nombre, "s", $email);
    mysqli_stmt_execute($stmt_nombre);
    $usuario = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_nombre));
    $nombre_usuario = $usuario['nombre'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Principal - Gestión de Talleres</title>
    <link rel="stylesheet" href="../css/custom.css">
    <script src="../node_modules/jquery/dist/jquery.min.js"></script>
    <script src="../node_modules/popper.js/dist/umd/popper.min.js"></script>
    <script src="../node_modules/bootstrap/dist/js/bootstrap.min.js"></script>
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
                <li class="nav-item active"><a class="nav-link" href="panelMecanico.php">Panel Principal</a></li>
                <li class="nav-item"><a class="nav-link" href="vehiculosMecanico.php">Vehículos</a></li>
                <li class="nav-item"><a class="nav-link" href="inventario.php">Inventario</a></li>
                <li class="nav-item"><a class="nav-link" href="#">Citas</a></li>
            </ul>
            <span class="navbar-text mr-3">Hola, <?= htmlspecialchars($nombre_usuario) ?></span>
            <a href="../auth/logout.php" class="btn btn-outline-light btn-sm">Salir</a>
        </div>
    </nav>

    <div class="container mt-4">
        <h5 class="mb-4">Panel principal</h5>

        <div class="row">
            <div class="col-md-4 mb-3">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <small class="text-muted">Vehículos en taller</small>
                        <h3 class="mt-2"><?= $total_vehiculos ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <small class="text-muted">Citas pendientes</small>
                        <h3 class="mt-2">--</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <small class="text-muted">Notificaciones</small>
                        <h3 class="mt-2">3</h3>
                    </div>
                </div>
            </div>
        </div>

        <h5 class="mt-4 mb-3">Vehículos en taller</h5>
        <div class="list-group">
            <?php while ($v = mysqli_fetch_assoc($resultado)): ?>
            <div class="list-group-item d-flex justify-content-between align-items-center">
                <div>
                    <strong><?= htmlspecialchars($v['marca'] . ' ' . $v['modelo'] . ' ' . $v['anio']) ?></strong><br>
                    <small class="text-muted"><?= htmlspecialchars($v['nombre'] . ' ' . $v['apellidos']) ?> &bull; <?= htmlspecialchars($v['matricula']) ?></small>
                </div>
                <?php
                    $claseEstado = 'secondary';
                    if ($v['estado'] === 'Pendiente')  $claseEstado = 'warning';
                    if ($v['estado'] === 'En proceso') $claseEstado = 'info';
                    if ($v['estado'] === 'Finalizado') $claseEstado = 'success';
                ?>
                <div class="d-flex align-items-center">
                    <span class="badge badge-<?= $claseEstado ?> mr-3"><?= htmlspecialchars($v['estado'] ?? 'Sin estado') ?></span>
                    <a href="vehiculo.php?id=<?= $v['id_vehiculo'] ?>" class="btn btn-dark btn-sm">Abrir</a>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
    </div>
</body>
</html>
