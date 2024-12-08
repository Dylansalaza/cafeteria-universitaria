<?php

session_start();

require_once ('C:\xampp\htdocs\proyecto_final/conexion/conexion.php');

//procesar el formulario cuando se envie
if($_SERVER['REQUEST_METHOD'] == 'POST'){
    $nombre = $_POST['nombre'] ?? '';
    $apellido = $_POST['apellido'] ?? '';
    $email = $_POST['email'] ?? ''; 
    $telefono = $_POST['telefono'] ?? '';
    
    // Use the correct capitalization from your form
    $usuario = $nombre; // Assuming you want to use nombre as usuario
    $contrasena = $_POST['contrasena'] ?? ''; // Note the capital 'C'

    // Validate that required fields are not empty
    if(empty($usuario) || empty($contrasena)) {
        die("Error: Usuario y contraseña son obligatorios.");
    }

    $contrasena_hash = password_hash($contrasena, PASSWORD_DEFAULT);

    //Preparamos la consulta SQL
    $sql = "INSERT INTO usuario_registroSistema(nombre, apellido, email, telefono, usuario, contrasena)
    VALUES (:nombre, :apellido, :email, :telefono, :usuario, :contrasena)";

    try {
        $stm = $pdo->prepare($sql);
        $stm->execute([
            ':nombre' => $nombre,
            ':apellido' => $apellido,
            ':email' => $email,
            ':telefono' => $telefono,
            ':usuario' => $usuario, // Using nombre as usuario
            ':contrasena' => $contrasena_hash,
        ]);

        //si el registro es exitoso, redirigir a la pagina de logueo
        header("Location: /proyecto_final/logueo/logueo.html");
        exit();
    } catch(PDOException $e) {
        $error_message = 'Error al registro: ' . $e->getMessage();
        die($error_message);
    }
}
?>