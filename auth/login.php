<?php
session_start();
require_once "../conf/db.php";

$email = $_POST['email'];
$password = $_POST['password'];

$sql = "SELECT * FROM usuarios WHERE email = ?";
$stmt = mysqli_prepare($conexion, $sql);
mysqli_stmt_bind_param($stmt, "s", $email);
mysqli_stmt_execute($stmt);

$resultado = mysqli_stmt_get_result($stmt);

if ($usuario = mysqli_fetch_assoc($resultado)) {

    if (password_verify($password, $usuario['contrasena'])) {
        $_SESSION['id_usuario'] = $usuario['id_usuario'];
        $_SESSION['email'] = $usuario['email'];
        $_SESSION['rol'] = $usuario['id_rol'];

        if ($usuario['id_rol'] === 'Mecanico') {
            header("Location: ../pages/panelMecanico.php");
        } else {
            header("Location: ../pages/panelCliente.php");
        }
        exit();
    }
}

header("Location: ../login.php?error=1");
exit();