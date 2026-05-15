<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Talleres - Acceso</title>

    <link rel="stylesheet" href="css/custom.css">

    <script src="node_modules/jquery/dist/jquery.min.js"></script>
    <script src="node_modules/popper.js/dist/umd/popper.min.js"></script>
    <script src="node_modules/bootstrap/dist/js/bootstrap.min.js"></script>
</head>

<body class="bg-light">

    <!-- Navbar -->
    <nav class="navbar navbar-dark bg-dark px-3">
        <a class="navbar-brand d-flex align-items-center" href="index.php">
            <img src="assets/imagenes/logo.png" width="42" height="40" class="mr-2" alt="Logo">
            Gestión de Talleres
        </a>

        <a href="index.php" class="btn btn-outline-light btn-sm">
            Volver al inicio
        </a>
    </nav>

    <!-- Contenedor -->
    <div class="container d-flex justify-content-center align-items-center" style="min-height: 90vh;">

        <div class="card shadow-sm" style="width: 100%; max-width: 420px;">
            <div class="card-body">

                <!-- Tabs -->
                <ul class="nav nav-tabs mb-4" id="loginTabs" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" id="login-tab" data-toggle="tab" href="#login" role="tab">
                            Iniciar sesión
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="register-tab" data-toggle="tab" href="#register" role="tab">
                            Registrarse
                        </a>
                    </li>
                </ul>

                <div class="tab-content">

                    <!-- LOGIN -->
                    <div class="tab-pane fade show active" id="login" role="tabpanel">
                        <form action="auth/login.php" method="POST">
                            <div class="form-group">
                                <label>Correo electrónico</label>
                                <input type="email" class="form-control" name="email" required placeholder="usuario@email.com">
                            </div>

                            <div class="form-group">
                                <label>Contraseña</label>
                                <input type="password" class="form-control" name="password" required placeholder="••••••••">
                            </div>

                            <?php
                                if (isset($_GET['error']) && $_GET['error'] == 1) {
                                    echo '<div class="alert alert-danger">Correo electrónico o contraseña incorrectos.</div>';
                                }
                            ?>

                            <button type="submit" class="btn btn-primary btn-block mt-3">
                                Entrar
                            </button>
                        </form>
                    </div>

                    <!-- REGISTRO -->
                    <div class="tab-pane fade" id="register" role="tabpanel">
                        <form action="auth/register.php" method="POST">

                            <?php
                                if (isset($_GET['register_success'])) {
                                    echo '<div class="alert alert-success mt-2">Registro completado correctamente. Ahora puedes iniciar sesión.</div>';
                                } elseif (isset($_GET['register_error'])) {
                                    if ($_GET['register_error'] == 1) {
                                        echo '<div class="alert alert-danger mt-2">Las contraseñas no coinciden.</div>';
                                    } elseif ($_GET['register_error'] == 2) {
                                        echo '<div class="alert alert-danger mt-2">El correo ya está registrado.</div>';
                                    }
                                }
                            ?>

                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label for="nombre">Nombre:</label>
                                    <input type="text" class="form-control" id="nombre" name="nombre" placeholder="Juan" required>
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="apellidos">Apellidos:</label>
                                    <input type="text" class="form-control" id="apellidos" name="apellidos" placeholder="Pérez" required>
                                </div>
                            </div>

                            <div class="form-group">
                                <label>Correo electrónico:</label>
                                <input type="email" class="form-control" name="email" placeholder="usuario@email.com" required>
                            </div>

                            <div class="form-group">
                                <label>Contraseña:</label>
                                <input type="password" class="form-control" name="password" placeholder="••••••••" required>
                            </div>

                            <div class="form-group">
                                <label>Confirmar contraseña:</label>
                                <input type="password" class="form-control" name="password_confirm" placeholder="••••••••" required>
                            </div>

                            <button type="submit" class="btn btn-success btn-block mt-3">
                                Crear cuenta
                            </button>
                        </form>
                    </div>

                </div>

            </div>
        </div>

    </div>

    <script>
        $(document).ready(function() {
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.has('register_success') || urlParams.has('register_error')) {
                $('#register-tab').tab('show');
            }
        });
    </script>
</body>

</html>