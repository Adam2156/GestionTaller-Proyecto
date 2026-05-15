<?php
    session_start();
    require_once "../conf/db.php";

    if (!isset($_SESSION["email"])) {
        header("Location: ../login.php");
        exit();
    }

    $email = $_SESSION["email"];
    $query_nombre = "SELECT nombre FROM usuarios WHERE email = ?";
    $stmt_nombre = mysqli_prepare($conexion, $query_nombre);
    mysqli_stmt_bind_param($stmt_nombre, "s", $email);
    mysqli_stmt_execute($stmt_nombre);
    $result_nombre = mysqli_stmt_get_result($stmt_nombre);
    $usuario = mysqli_fetch_assoc($result_nombre);
    $nombre_usuario = $usuario['nombre'];

    function getUsuarios($conexion) {
        $query = "SELECT id_usuario, nombre, apellidos FROM usuarios WHERE id_rol = 'Cliente'";
        $result = mysqli_query($conexion, $query);
        $usuarios = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $usuarios[] = $row;
        }
        return $usuarios;
    }

    if (isset($_POST['action']) && $_POST['action'] === 'crear') {
        $id_usuario = $_POST['id_usuario'];
        $marca = $_POST['marca'];
        $modelo = $_POST['modelo'];
        $anio = $_POST['anio'];
        $matricula = $_POST['matricula'];

        $stmt = mysqli_prepare($conexion, "INSERT INTO vehiculos (id_usuario, marca, modelo, anio, matricula) VALUES (?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "issis", $id_usuario, $marca, $modelo, $anio, $matricula);
        mysqli_stmt_execute($stmt);

        $id_vehiculo = mysqli_insert_id($conexion);

        $stmt_estado = mysqli_prepare($conexion, "INSERT INTO estados_vehiculo (id_vehiculo, estado, fecha_actualizacion) VALUES (?, 'Pendiente', NOW())");
        mysqli_stmt_bind_param($stmt_estado, "i", $id_vehiculo);
        mysqli_stmt_execute($stmt_estado);

        header("Location: vehiculosMecanico.php");
        exit();
    }

    if (isset($_POST['action']) && $_POST['action'] === 'editar') {
        $id_vehiculo = $_POST['id_vehiculo'];
        $id_usuario = $_POST['id_usuario'];
        $marca = $_POST['marca'];
        $modelo = $_POST['modelo'];
        $anio = $_POST['anio'];
        $matricula = $_POST['matricula'];

        $stmt = mysqli_prepare($conexion, "UPDATE vehiculos SET id_usuario=?, marca=?, modelo=?, anio=?, matricula=? WHERE id_vehiculo=?");
        mysqli_stmt_bind_param($stmt, "issisi", $id_usuario, $marca, $modelo, $anio, $matricula, $id_vehiculo);
        mysqli_stmt_execute($stmt);
        header("Location: vehiculosMecanico.php");
        exit();
    }

    if (isset($_GET['delete'])) {
        $id_vehiculo = $_GET['delete'];
        $stmt = mysqli_prepare($conexion, "DELETE FROM vehiculos WHERE id_vehiculo=?");
        mysqli_stmt_bind_param($stmt, "i", $id_vehiculo);
        mysqli_stmt_execute($stmt);
        header("Location: vehiculosMecanico.php");
        exit();
    }

    $query_vehiculos = "SELECT v.id_vehiculo, v.marca, v.modelo, v.anio, v.matricula, u.nombre, u.apellidos 
                        FROM vehiculos v
                        INNER JOIN usuarios u ON v.id_usuario = u.id_usuario
                        ORDER BY v.id_vehiculo DESC";
    $result_vehiculos = mysqli_query($conexion, $query_vehiculos);
    $usuarios = getUsuarios($conexion);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vehículos - Gestión de Talleres</title>
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
                <li class="nav-item">
                    <a class="nav-link" href="panelMecanico.php">Panel Principal</a>
                </li>
                <li class="nav-item active">
                    <a class="nav-link" href="vehiculosMecanico.php">Vehículos</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="inventario.php">Inventario</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#">Citas</a>
                </li>
            </ul>
            <span class="navbar-text mr-3">Hola, <?= htmlspecialchars($nombre_usuario) ?></span>
            <a href="../auth/logout.php" class="btn btn-outline-light btn-sm">Salir</a>
        </div>
    </nav>

<div class="container mt-4">
    <h3 class="mb-4">Gestión de Vehículos</h3>

    <button class="btn btn-success mb-3" data-toggle="modal" data-target="#crearModal">Agregar Vehículo</button>

    <table class="table table-bordered table-hover">
        <thead class="thead-dark">
            <tr>
                <th>ID</th>
                <th>Marca</th>
                <th>Modelo</th>
                <th>Año</th>
                <th>Matrícula</th>
                <th>Cliente</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($veh = mysqli_fetch_assoc($result_vehiculos)) : ?>
            <tr>
                <td><?= $veh['id_vehiculo'] ?></td>
                <td><?= htmlspecialchars($veh['marca']) ?></td>
                <td><?= htmlspecialchars($veh['modelo']) ?></td>
                <td><?= $veh['anio'] ?></td>
                <td><?= htmlspecialchars($veh['matricula']) ?></td>
                <td><?= htmlspecialchars($veh['nombre'] . ' ' . $veh['apellidos']) ?></td>
                <td>
                    <div class="d-flex">
                        <button class="btn btn-primary btn-sm mr-2" data-toggle="modal" data-target="#editarModal<?= $veh['id_vehiculo'] ?>">Editar</button>
                        <a href="?delete=<?= $veh['id_vehiculo'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('¿Eliminar este vehículo?')">Eliminar</a>
                    </div>
                </td>
            </tr>

            <!-- Modal Editar -->
            <div class="modal fade" id="editarModal<?= $veh['id_vehiculo'] ?>" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <form method="POST" action="vehiculosMecanico.php">
                            <div class="modal-header">
                                <h5 class="modal-title">Editar Vehículo</h5>
                                <button type="button" class="close" data-dismiss="modal">&times;</button>
                            </div>
                            <div class="modal-body">
                                <input type="hidden" name="action" value="editar">
                                <input type="hidden" name="id_vehiculo" value="<?= $veh['id_vehiculo'] ?>">
                                <div class="form-group">
                                    <label>Marca</label>
                                    <input type="text" class="form-control" name="marca" value="<?= htmlspecialchars($veh['marca']) ?>" required>
                                    <label>Modelo</label>
                                    <input type="text" class="form-control" name="modelo" value="<?= htmlspecialchars($veh['modelo']) ?>" required>
                                    <label>Año</label>
                                    <input type="number" class="form-control" name="anio" value="<?= $veh['anio'] ?>" required>
                                    <label>Matrícula</label>
                                    <input type="text" class="form-control" name="matricula" value="<?= htmlspecialchars($veh['matricula']) ?>" required>
                                    <label>Cliente</label>
                                    <select name="id_usuario" class="form-control" required>
                                        <?php foreach($usuarios as $user): ?>
                                            <option value="<?= $user['id_usuario'] ?>"
                                                <?= (isset($veh['id_usuario']) && $user['id_usuario'] == $veh['id_usuario']) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($user['nombre'].' '.$user['apellidos']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                                <button class="btn btn-primary">Guardar cambios</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<!-- Modal Crear -->
<div class="modal fade" id="crearModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="vehiculosMecanico.php">
                <div class="modal-header">
                    <h5 class="modal-title">Agregar Vehículo</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="crear">
                    <div class="form-group">
                        <label>Marca</label>
                        <input type="text" class="form-control" name="marca" required>
                        <label>Modelo</label>
                        <input type="text" class="form-control" name="modelo" required>
                        <label>Año</label>
                        <input type="number" class="form-control" name="anio" required>
                        <label>Matrícula</label>
                        <input type="text" class="form-control" name="matricula" required>
                        <label>Cliente</label>
                        <select name="id_usuario" class="form-control" required>
                            <?php foreach($usuarios as $user): ?>
                                <option value="<?= $user['id_usuario'] ?>"><?= htmlspecialchars($user['nombre'].' '.$user['apellidos']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button class="btn btn-success">Agregar</button>
                </div>
            </form>
        </div>
    </div>
</div>

</body>
</html>
