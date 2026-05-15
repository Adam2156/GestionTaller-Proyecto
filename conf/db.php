<?php
    $host = "localhost";
    $db = "bdgestion";
    $user = "root";
    $pass = "";

    $conexion = mysqli_connect($host, $user, $pass, $db);

    if ($conexion === false) {
        die("Error de conexion: " . mysqli_connect_error());
    }
?>