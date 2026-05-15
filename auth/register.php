<?php
session_start();
require_once "../conf/db.php";

$nombre = trim($_POST['nombre']);
$apellidos = trim($_POST['apellidos']);
$email = trim($_POST['email']);
$password = $_POST['password'];
$password_confirm = $_POST['password_confirm'];

if ($password !== $password_confirm) {
    header("Location: ../login.php?register_error=1");
    exit();
}

// Comprobar si el correo ya existe
$sql_check = "SELECT id_usuario FROM usuarios WHERE email = ?";
$stmt_check = mysqli_prepare($conexion, $sql_check);
mysqli_stmt_bind_param($stmt_check, "s", $email);
mysqli_stmt_execute($stmt_check);
$result_check = mysqli_stmt_get_result($stmt_check);

if (mysqli_fetch_assoc($result_check)) {
    header("Location: ../login.php?register_error=2");
    exit();
}

// Hashear contraseña
$contrasena_hash = password_hash($password, PASSWORD_DEFAULT);
$rol = "Cliente";

$sql = "INSERT INTO usuarios (nombre, apellidos, email, contrasena, id_rol, fecha_registro)
        VALUES (?, ?, ?, ?, ?, NOW())";

$stmt = mysqli_prepare($conexion, $sql);
mysqli_stmt_bind_param($stmt, "sssss", $nombre, $apellidos, $email, $contrasena_hash, $rol);

if (mysqli_stmt_execute($stmt)) {
    header("Location: ../login.php?register_success=1");
    exit();
} else {
    header("Location: ../login.php?register_error=3");
    exit();
}