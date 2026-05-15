<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Talleres</title>

    <link rel="stylesheet" href="css/custom.css">

    <style>
        html {
            scroll-behavior: smooth;
        }

        .hero {
            background: linear-gradient(rgba(20, 23, 26, 0.75), rgba(20, 23, 26, 0.75)),
                        url('assets/imagenes/portada-taller.jpg') center/cover no-repeat;
            color: white;
            min-height: 100vh;
            display: flex;
            align-items: center;
        }

        .section-title {
            font-weight: 700;
            margin-bottom: 1.5rem;
        }

        .service-card,
        .product-card,
        .contact-card {
            height: 100%;
        }

        .info-icon {
            font-size: 2rem;
            font-weight: bold;
        }

        footer {
            background-color: #212529;
            color: white;
        }
    </style>

    <script src="node_modules/jquery/dist/jquery.min.js"></script>
    <script src="node_modules/popper.js/dist/umd/popper.min.js"></script>
    <script src="node_modules/bootstrap/dist/js/bootstrap.min.js"></script>
</head>

<body class="bg-light">

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark px-3 sticky-top">
        <a class="navbar-brand d-flex align-items-center" href="#">
            <img src="assets/imagenes/logo.png" width="42" height="40" class="mr-2" alt="Logo">
            Gestión de Talleres
        </a>

        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarLanding"
            aria-controls="navbarLanding" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarLanding">
            <ul class="navbar-nav mr-auto ml-3">
                <li class="nav-item">
                    <a class="nav-link" href="#servicios">Servicios</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#productos">Productos</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#ubicacion">Ubicación</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#contacto">Contacto</a>
                </li>
            </ul>

            <a href="login.php" class="btn btn-outline-light my-2 my-lg-0">
                Iniciar sesión
            </a>
        </div>
    </nav>

    <!-- Hero -->
    <section class="hero">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-7">
                    <h1 class="display-4 font-weight-bold">Tu taller de confianza</h1>
                    <p class="lead mt-3">
                        Gestión de Talleres es una solución para clientes y mecánicos donde puedes consultar
                        el estado del vehículo, reparaciones y seguimiento del taller.
                    </p>
                    <div class="mt-4">
                        <a href="#servicios" class="btn btn-primary btn-lg mr-2">Ver servicios</a>
                        <a href="#contacto" class="btn btn-outline-light btn-lg">Contactar</a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Sobre nosotros -->
    <section class="py-5 bg-white">
        <div class="container">
            <h2 class="section-title text-center">Sobre el taller</h2>
            <p class="text-center text-muted mx-auto" style="max-width: 800px;">
                Somos un taller especializado en mantenimiento y reparación de vehículos. Apostamos por una atención
                cercana, diagnósticos claros y una gestión organizada para que tanto el cliente como el mecánico
                tengan siempre acceso a la información importante del vehículo.
            </p>
        </div>
    </section>

    <!-- Servicios -->
    <section id="servicios" class="py-5">
        <div class="container">
            <h2 class="section-title text-center">Servicios destacados</h2>
            <div class="row">
                <div class="col-md-6 col-lg-3 mb-4">
                    <div class="card shadow-sm service-card">
                        <div class="card-body">
                            <h5 class="card-title">Diagnóstico general</h5>
                            <p class="card-text text-muted">
                                Revisión del estado general del vehículo para detectar averías y planificar reparaciones.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="col-md-6 col-lg-3 mb-4">
                    <div class="card shadow-sm service-card">
                        <div class="card-body">
                            <h5 class="card-title">Mantenimiento periódico</h5>
                            <p class="card-text text-muted">
                                Cambio de aceite, filtros, revisión de niveles y comprobaciones básicas del vehículo.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="col-md-6 col-lg-3 mb-4">
                    <div class="card shadow-sm service-card">
                        <div class="card-body">
                            <h5 class="card-title">Sistema de frenos</h5>
                            <p class="card-text text-muted">
                                Sustitución de discos, pastillas y revisión completa del sistema de frenado.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="col-md-6 col-lg-3 mb-4">
                    <div class="card shadow-sm service-card">
                        <div class="card-body">
                            <h5 class="card-title">Embrague y transmisión</h5>
                            <p class="card-text text-muted">
                                Reparación o sustitución de componentes mecánicos clave para el funcionamiento del vehículo.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Productos -->
    <section id="productos" class="py-5 bg-white">
        <div class="container">
            <h2 class="section-title text-center">Productos destacados</h2>
            <div class="row">
                <div class="col-md-6 col-lg-3 mb-4">
                    <div class="card shadow-sm product-card">
                        <div class="card-body">
                            <h5 class="card-title">Aceite 5W30</h5>
                            <p class="card-text text-muted">Aceite sintético para mantenimiento de motor.</p>
                            <span class="badge badge-primary">Disponible</span>
                        </div>
                    </div>
                </div>

                <div class="col-md-6 col-lg-3 mb-4">
                    <div class="card shadow-sm product-card">
                        <div class="card-body">
                            <h5 class="card-title">Filtro de aceite</h5>
                            <p class="card-text text-muted">Recambio compatible para revisiones periódicas.</p>
                            <span class="badge badge-primary">Disponible</span>
                        </div>
                    </div>
                </div>

                <div class="col-md-6 col-lg-3 mb-4">
                    <div class="card shadow-sm product-card">
                        <div class="card-body">
                            <h5 class="card-title">Kit de embrague</h5>
                            <p class="card-text text-muted">Conjunto completo para reparación de embrague.</p>
                            <span class="badge badge-warning">Bajo pedido</span>
                        </div>
                    </div>
                </div>

                <div class="col-md-6 col-lg-3 mb-4">
                    <div class="card shadow-sm product-card">
                        <div class="card-body">
                            <h5 class="card-title">Pastillas de freno</h5>
                            <p class="card-text text-muted">Repuesto esencial para mantenimiento del sistema de frenos.</p>
                            <span class="badge badge-primary">Disponible</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Ubicación -->
    <section id="ubicacion" class="py-5">
        <div class="container">
            <h2 class="section-title text-center">Ubicación</h2>
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="card shadow-sm">
                        <div class="card-body text-center">
                            <h5 class="card-title">Estamos cerca de ti</h5>
                            <p class="card-text text-muted mb-2">Calle del Taller, 123</p>
                            <p class="card-text text-muted mb-2">08000 Barcelona</p>
                            <p class="card-text text-muted mb-0">Lunes a viernes · 08:00 a 18:00</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Contacto -->
    <section id="contacto" class="py-5 bg-white">
        <div class="container">
            <h2 class="section-title text-center">Contacto</h2>
            <div class="row">
                <div class="col-md-4 mb-4">
                    <div class="card shadow-sm contact-card">
                        <div class="card-body text-center">
                            <h5 class="card-title">Teléfono</h5>
                            <p class="card-text text-muted">631 694 288</p>
                        </div>
                    </div>
                </div>

                <div class="col-md-4 mb-4">
                    <div class="card shadow-sm contact-card">
                        <div class="card-body text-center">
                            <h5 class="card-title">Correo</h5>
                            <p class="card-text text-muted">contacto@gestiontaller.com</p>
                        </div>
                    </div>
                </div>

                <div class="col-md-4 mb-4">
                    <div class="card shadow-sm contact-card">
                        <div class="card-body text-center">
                            <h5 class="card-title">Atención</h5>
                            <p class="card-text text-muted">Respuesta rápida y seguimiento personalizado</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="py-4">
        <div class="container text-center">
            <p class="mb-1">Gestión de Talleres</p>
            <small class="text-white-50">Proyecto de gestión para talleres mecánicos</small>
        </div>
    </footer>

</body>
</html>
