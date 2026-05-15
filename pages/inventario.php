<?php
session_start();
require_once "../conf/db.php";

if (!isset($_SESSION['email'])) {
    header("Location: ../login.php");
    exit();
}
if ($_SESSION['rol'] !== 'Mecanico') {
    header("Location: ../login.php");
    exit();
}

$email = $_SESSION['email'];
$stmt_u = mysqli_prepare($conexion, "SELECT nombre FROM usuarios WHERE email = ?");
mysqli_stmt_bind_param($stmt_u, "s", $email);
mysqli_stmt_execute($stmt_u);
$usuario = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_u));
$nombre_usuario = $usuario['nombre'];

// --- ACCIONES POST ---

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'crear') {
        $nombre      = trim($_POST['nombre']);
        $descripcion = trim($_POST['descripcion']);
        $cantidad    = intval($_POST['cantidad_disponible']);
        $precio      = floatval($_POST['precio_unitario']);
        $stmt = mysqli_prepare($conexion, "INSERT INTO productos (nombre, descripcion, cantidad_disponible, precio_unitario) VALUES (?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "ssid", $nombre, $descripcion, $cantidad, $precio);
        mysqli_stmt_execute($stmt);
    }

    if ($action === 'editar') {
        $id          = intval($_POST['id_producto']);
        $nombre      = trim($_POST['nombre']);
        $descripcion = trim($_POST['descripcion']);
        $cantidad    = intval($_POST['cantidad_disponible']);
        $precio      = floatval($_POST['precio_unitario']);
        $stmt = mysqli_prepare($conexion, "UPDATE productos SET nombre=?, descripcion=?, cantidad_disponible=?, precio_unitario=? WHERE id_producto=?");
        mysqli_stmt_bind_param($stmt, "ssidi", $nombre, $descripcion, $cantidad, $precio, $id);
        mysqli_stmt_execute($stmt);
    }

    header("Location: inventario.php");
    exit();
}

if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $stmt = mysqli_prepare($conexion, "DELETE FROM productos WHERE id_producto = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    header("Location: inventario.php");
    exit();
}

// --- LISTADO ---
$productos = mysqli_fetch_all(
    mysqli_query($conexion, "SELECT * FROM productos ORDER BY nombre ASC"),
    MYSQLI_ASSOC
);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventario - Gestión de Talleres</title>
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
            <li class="nav-item"><a class="nav-link" href="panelMecanico.php">Panel Principal</a></li>
            <li class="nav-item"><a class="nav-link" href="vehiculosMecanico.php">Vehículos</a></li>
            <li class="nav-item active"><a class="nav-link" href="inventario.php">Inventario</a></li>
            <li class="nav-item"><a class="nav-link" href="#">Citas</a></li>
        </ul>
        <span class="navbar-text mr-3">Hola, <?= htmlspecialchars($nombre_usuario) ?></span>
        <a href="../auth/logout.php" class="btn btn-outline-light btn-sm">Salir</a>
    </div>
</nav>

<div class="container mt-4">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="mb-0">Inventario de Productos</h5>
        <button class="btn btn-success btn-sm" data-toggle="modal" data-target="#modalCrear">
            + Añadir producto
        </button>
    </div>

    <!-- Buscador -->
    <div class="mb-3">
        <input type="text" id="buscador" class="form-control" placeholder="Buscar por nombre o descripción...">
    </div>

    <!-- Resumen stock -->
    <?php
        $total_productos  = count($productos);
        $bajo_stock       = count(array_filter($productos, fn($p) => $p['cantidad_disponible'] > 0 && $p['cantidad_disponible'] <= 5));
        $agotados         = count(array_filter($productos, fn($p) => $p['cantidad_disponible'] == 0));
    ?>
    <div class="row mb-3">
        <div class="col-4">
            <div class="card shadow-sm text-center">
                <div class="card-body py-2">
                    <small class="text-muted">Total productos</small>
                    <h4 class="mb-0"><?= $total_productos ?></h4>
                </div>
            </div>
        </div>
        <div class="col-4">
            <div class="card shadow-sm text-center">
                <div class="card-body py-2">
                    <small class="text-muted">Stock bajo (≤5)</small>
                    <h4 class="mb-0 text-warning"><?= $bajo_stock ?></h4>
                </div>
            </div>
        </div>
        <div class="col-4">
            <div class="card shadow-sm text-center">
                <div class="card-body py-2">
                    <small class="text-muted">Agotados</small>
                    <h4 class="mb-0 text-danger"><?= $agotados ?></h4>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabla productos -->
    <div class="table-responsive">
        <table class="table table-bordered table-hover" id="tablaProductos">
            <thead class="thead-dark">
                <tr>
                    <th>#</th>
                    <th>Nombre</th>
                    <th>Descripción</th>
                    <th>Stock</th>
                    <th>Precio unitario</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($productos as $p):
                $cant = $p['cantidad_disponible'];
                if ($cant == 0) {
                    $badge_clase = 'danger';
                    $badge_texto = 'Agotado';
                } elseif ($cant <= 5) {
                    $badge_clase = 'warning';
                    $badge_texto = 'Stock bajo';
                } else {
                    $badge_clase = 'success';
                    $badge_texto = 'Disponible';
                }
            ?>
            <tr class="fila-producto">
                <td><?= $p['id_producto'] ?></td>
                <td><?= htmlspecialchars($p['nombre']) ?></td>
                <td><?= htmlspecialchars($p['descripcion'] ?? '-') ?></td>
                <td><strong><?= $cant ?></strong> uds.</td>
                <td><?= number_format($p['precio_unitario'], 2) ?> €</td>
                <td><span class="badge badge-<?= $badge_clase ?>"><?= $badge_texto ?></span></td>
                <td>
                    <div class="d-flex">
                        <button class="btn btn-primary btn-sm mr-2"
                            data-toggle="modal"
                            data-target="#modalEditar<?= $p['id_producto'] ?>">
                            Editar
                        </button>
                        <a href="?delete=<?= $p['id_producto'] ?>"
                           class="btn btn-danger btn-sm"
                           onclick="return confirm('¿Eliminar este producto?')">
                           Eliminar
                        </a>
                    </div>
                </td>
            </tr>

            <!-- Modal Editar -->
            <div class="modal fade" id="modalEditar<?= $p['id_producto'] ?>" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <form method="POST" action="inventario.php">
                            <div class="modal-header">
                                <h5 class="modal-title">Editar producto</h5>
                                <button type="button" class="close" data-dismiss="modal">&times;</button>
                            </div>
                            <div class="modal-body">
                                <input type="hidden" name="action" value="editar">
                                <input type="hidden" name="id_producto" value="<?= $p['id_producto'] ?>">
                                <div class="form-group">
                                    <label>Nombre</label>
                                    <input type="text" class="form-control" name="nombre"
                                        value="<?= htmlspecialchars($p['nombre']) ?>" required>
                                </div>
                                <div class="form-group">
                                    <label>Descripción</label>
                                    <textarea class="form-control" name="descripcion" rows="2"><?= htmlspecialchars($p['descripcion'] ?? '') ?></textarea>
                                </div>
                                <div class="form-group">
                                    <label>Cantidad disponible</label>
                                    <input type="number" class="form-control" name="cantidad_disponible"
                                        value="<?= $p['cantidad_disponible'] ?>" min="0" required>
                                </div>
                                <div class="form-group">
                                    <label>Precio unitario (€)</label>
                                    <input type="number" step="0.01" class="form-control" name="precio_unitario"
                                        value="<?= $p['precio_unitario'] ?>" min="0" required>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                                <button type="submit" class="btn btn-primary">Guardar cambios</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <?php endforeach; ?>

            <?php if (empty($productos)): ?>
            <tr><td colspan="7" class="text-center text-muted">No hay productos en el inventario.</td></tr>
            <?php endif; ?>

            </tbody>
        </table>
    </div>
</div>

<!-- Modal Crear -->
<div class="modal fade" id="modalCrear" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="inventario.php">
                <div class="modal-header">
                    <h5 class="modal-title">Añadir producto</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="crear">
                    <div class="form-group">
                        <label>Nombre</label>
                        <input type="text" class="form-control" name="nombre" required>
                    </div>
                    <div class="form-group">
                        <label>Descripción</label>
                        <textarea class="form-control" name="descripcion" rows="2"></textarea>
                    </div>
                    <div class="form-group">
                        <label>Cantidad disponible</label>
                        <input type="number" class="form-control" name="cantidad_disponible" min="0" value="0" required>
                    </div>
                    <div class="form-group">
                        <label>Precio unitario (€)</label>
                        <input type="number" step="0.01" class="form-control" name="precio_unitario" min="0" value="0.00" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success">Añadir</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Búsqueda en tiempo real
$('#buscador').on('keyup', function () {
    var texto = $(this).val().toLowerCase();
    $('#tablaProductos tbody .fila-producto').each(function () {
        var nombre = $(this).find('td:eq(1)').text().toLowerCase();
        var desc   = $(this).find('td:eq(2)').text().toLowerCase();
        $(this).toggle(nombre.includes(texto) || desc.includes(texto));
    });
});
</script>

</body>
</html>
