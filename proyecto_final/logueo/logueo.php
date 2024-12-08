<?php
// Iniciar una sesión PHP para manejar datos de sesión
session_start();

// Incluir el archivo de conexión a la base de datos
require_once ('C:\xampp\htdocs\proyecto_final/conexion/conexion.php');

// Verificar si el formulario fue enviado mediante el método POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Obtener los valores ingresados por el usuario en el formulario
    $usuario = $_POST['usuario'] ?? '';
    $password = $_POST['contrasena'] ?? '';

    try {
        // Preparar la consulta para verificar en la tabla usuario_registro
        $sql_logueo = "SELECT * FROM usuario_registro WHERE nombre_usuario = :usuario";
        $stmt_logueo = $pdo->prepare($sql_logueo);
        $stmt_logueo->execute([':usuario' => $usuario]);
        $user_logueo = $stmt_logueo->fetch(PDO::FETCH_ASSOC);

        // Preparar la consulta para verificar en la tabla usuario_registrosistema
        $sql_registro = "SELECT * FROM usuario_registrosistema WHERE usuario = :usuario";
        $stmt_registro = $pdo->prepare($sql_registro);
        $stmt_registro->execute([':usuario' => $usuario]);
        $user_registro = $stmt_registro->fetch(PDO::FETCH_ASSOC);

        // Verificar si el usuario existe en alguna de las dos tablas y si la contraseña es correcta
        if ($user_logueo && password_verify($password, $user_logueo['contrasena'])) {
            // Si existe en usuario_registro y la contraseña es correcta, establecer user_id en la sesión
            $_SESSION['user_id'] = $user_logueo['id']; // Asegúrate de que 'id' es el nombre correcto del campo en tu tabla usuario_registro
            header("Location: /proyecto_final/administrador/administrador.php");
            exit;
        } elseif ($user_registro && password_verify($password, $user_registro['contrasena'])) {
            // Si existe en usuario_registrosistema y la contraseña es correcta, establecer user_id en la sesión
            $_SESSION['user_id'] = $user_registro['id']; // Asegúrate de que 'id' es el nombre correcto del campo en tu tabla usuario_registrosistema
            header("Location: /proyecto_final/cliente/cliente.php");
            exit;
        } else {
            // Si las credenciales no coinciden, mostrar un mensaje de error
            echo "Nombre de usuario o contraseña incorrectos.";
        }
    } catch (Exception $e) {
        // Capturar cualquier excepción y mostrar el mensaje de error
        echo "Error al intentar iniciar sesión: " . $e->getMessage();
    }
}
?>
