<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

require_once ('C:\xampp\htdocs\proyecto_final/conexion/conexion.php');

// Debug directory information
$upload_dir = 'uploads/';

// Create directory if not exists
if(!file_exists($upload_dir)){
    mkdir($upload_dir, 0777, true);
    chmod($upload_dir, 0777);
}

// Function to handle product deletion
function eliminarProducto($pdo, $producto_id, $upload_dir) {
    try {
        // Get the image filename associated with the product
        $stmt = $pdo->prepare("SELECT imagen FROM menu_items WHERE id = :producto_id");
        $stmt->execute([':producto_id' => $producto_id]);
        $imagen = $stmt->fetchColumn();

        // Delete the product from the database
        $stmt = $pdo->prepare("DELETE FROM menu_items WHERE id = :producto_id");
        $stmt->execute([':producto_id' => $producto_id]);

        // Delete the associated image file if it exists
        if ($imagen && file_exists($upload_dir . $imagen)) {
            unlink($upload_dir . $imagen);
        }

        return "Producto eliminado correctamente.";
    } catch (Exception $e) {
        throw new Exception("Error al eliminar producto: " . $e->getMessage());
    }
}

// Process form submission to delete a product
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['producto_id'])) {
    try {
        // Validate input
        $producto_id = filter_input(INPUT_POST, 'producto_id', FILTER_VALIDATE_INT);
        
        if (!$producto_id) {
            throw new Exception("ID de producto inválido");
        }

        // Check if product exists before deletion
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM menu_items WHERE id = :producto_id");
        $stmt->execute([':producto_id' => $producto_id]);
        
        if ($stmt->fetchColumn() == 0) {
            throw new Exception("El producto no existe");
        }

        // Perform deletion
        $mensaje = eliminarProducto($pdo, $producto_id, $upload_dir);
        
        // Set success message in session
        $_SESSION['mensaje_exito'] = $mensaje;
        
    } catch (Exception $e) {
        // Set error message in session
        $_SESSION['mensaje_error'] = $e->getMessage();
    }
    
    // Redirect to prevent form resubmission
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="administrador.css">
    <title>Eliminar Producto - CAFETERIA UNIVERSITARIA</title>
    <style>
        .mensaje-exito {
            background-color: #d4edda;
            color: #155724;
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #c3e6cb;
            border-radius: 4px;
        }
        .mensaje-error {
            background-color: #f8d7da;
            color: #721c24;
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #f5c6cb;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <header class='header'>
        <?php include 'header.php'; ?>
    </header>

    <div class="formulario">
        <h1>Eliminar Producto</h1>

        <?php
        // Mostrar mensajes de éxito o error
        if (isset($_SESSION['mensaje_exito'])) {
            echo "<div class='mensaje-exito'>" . htmlspecialchars($_SESSION['mensaje_exito']) . "</div>";
            unset($_SESSION['mensaje_exito']);
        }
        
        if (isset($_SESSION['mensaje_error'])) {
            echo "<div class='mensaje-error'>" . htmlspecialchars($_SESSION['mensaje_error']) . "</div>";
            unset($_SESSION['mensaje_error']);
        }
        ?>

        <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="POST">
            <div class="input-box">
                <label>Seleccione el producto a eliminar:</label>
                <select name="producto_id" required>
                    <option value="">Seleccione un producto</option>
                    <?php
                    try {
                        $stmt = $pdo->query("SELECT id, nombre FROM menu_items ORDER BY nombre");
                        while ($producto = $stmt->fetch(PDO::FETCH_ASSOC)) {
                            echo "<option value='" . htmlspecialchars($producto['id']) . "'>" 
                                 . htmlspecialchars($producto['nombre']) . "</option>";
                        }
                    } catch (PDOException $e) {
                        echo "<option>Error al cargar productos</option>";
                    }
                    ?>
                </select>
            </div>
            <button type="submit" class="btn">Eliminar producto</button><br>
        </form>
    </div>
    
</body>
</html>