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

// Function to handle image upload (reusing from previous script)
function handleImageUpload($file, $upload_dir, $old_image = null) {
    if (!isset($file['imagen']) || $file['imagen']['error'] === UPLOAD_ERR_NO_FILE) {
        return $old_image;
    }

    // Validate file
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
    $max_size = 10 * 1024 * 1024; // 10MB

    // Check file type
    if (!in_array($file['imagen']['type'], $allowed_types)) {
        throw new Exception('Tipo de archivo inválido. Solo se permiten JPEG, PNG y GIF.');
    }

    // Check file size
    if ($file['imagen']['size'] > $max_size) {
        throw new Exception('Archivo demasiado grande. Máximo 10MB.');
    }

    // Generate unique filename
    $extension = strtolower(pathinfo($file['imagen']['name'], PATHINFO_EXTENSION));
    $nuevo_nombre = uniqid('producto_') . '.' . $extension;
    $ruta_completa = $upload_dir . $nuevo_nombre;

    // Move uploaded file
    if (!move_uploaded_file($file['imagen']['tmp_name'], $ruta_completa)) {
        throw new Exception('No se pudo guardar la imagen. Verifique los permisos del directorio.');
    }

    // Delete old image if exists
    if ($old_image && file_exists($upload_dir . $old_image)) {
        unlink($upload_dir . $old_image);
    }

    return $nuevo_nombre;
}

// Process form submission for updating product
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        // Check if this is an update operation
        if (!isset($_POST['producto_id'])) {
            throw new Exception("ID de producto no proporcionado");
        }

        // Validate required fields
        $required_fields = ['nombre', 'descripcion', 'precio', 'categoria'];
        foreach ($required_fields as $field) {
            if (empty($_POST[$field])) {
                throw new Exception("El campo $field es obligatorio.");
            }
        }

        // Validate category
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM categorias WHERE categoria_id = :categoria_id");
        $stmt->execute([':categoria_id' => $_POST['categoria']]);
        if ($stmt->fetchColumn() == 0) {
            throw new Exception("Categoría inválida.");
        }

        // Retrieve existing product information
        $stmt = $pdo->prepare("SELECT imagen FROM menu_items WHERE id = :id");
        $stmt->execute([':id' => $_POST['producto_id']]);
        $producto_actual = $stmt->fetch(PDO::FETCH_ASSOC);

        // Handle image upload
        $imagen_path = $producto_actual['imagen'];
        if (!empty($_FILES['imagen']['name'])) {
            $imagen_path = handleImageUpload($_FILES, $upload_dir, $producto_actual['imagen']);
        }

        // Prepare and execute update
        $sql = "UPDATE menu_items 
                SET nombre = :nombre, 
                    descripcion = :descripcion, 
                    precio = :precio, 
                    categoria_id = :categoria, 
                    imagen = :imagen 
                WHERE id = :producto_id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':nombre' => $_POST['nombre'],
            ':descripcion' => $_POST['descripcion'],
            ':precio' => $_POST['precio'],
            ':categoria' => $_POST['categoria'],
            ':imagen' => $imagen_path,
            ':producto_id' => $_POST['producto_id']
        ]);

        // Set success message in session
        $_SESSION['mensaje_exito'] = "Producto actualizado correctamente.";
        
        // Redirect to prevent form resubmission
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit();

    } catch (Exception $e) {
        // Set error message in session
        $_SESSION['mensaje_error'] = "Error: " . $e->getMessage();
        
        // Redirect to prevent form resubmission
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit();
    }
}

// Retrieve product for editing (if producto_id is set in GET)
$producto_editar = null;
if (isset($_GET['producto_id'])) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM menu_items WHERE id = :id");
        $stmt->execute([':id' => $_GET['producto_id']]);
        $producto_editar = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$producto_editar) {
            throw new Exception("Producto no encontrado");
        }
    } catch (Exception $e) {
        $_SESSION['mensaje_error'] = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="administrador.css">
    <title>Actualizar Producto - CAFETERIA UNIVERSITARIA</title>
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
        <h1>Actualizar Producto</h1>

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

        <?php if (!$producto_editar): ?>
            <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="GET">
                <div class="input-box">
                    <label>Seleccione el producto a actualizar:</label>
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
                <button type="submit" class="btn">Seleccionar producto</button>
            </form>
        <?php else: ?>
            <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="producto_id" value="<?php echo htmlspecialchars($producto_editar['id']); ?>">
                
                <div class="input-box">
                    <label>Nombre del item:</label>
                    <input type="text" name="nombre" required placeholder="Nombre del Producto" 
                           value="<?php echo htmlspecialchars($producto_editar['nombre']); ?>">
                </div>
                
                <div class="input-box">
                    <label>Descripción del item:</label>
                    <textarea name="descripcion" required placeholder="Descripción del Producto"><?php 
                        echo htmlspecialchars($producto_editar['descripcion']); 
                    ?></textarea>
                </div>
                
                <div class="input-box">
                    <label>Precio del item:</label>
                    <input type="number" name="precio" step="0.01" required placeholder="Precio"
                           value="<?php echo htmlspecialchars($producto_editar['precio']); ?>">
                </div>
                
                <div class="input-box">
                    <label>Categoría:</label>
                    <select name="categoria" required>
                        <?php 
                        $cat_stmt = $pdo->query("SELECT categoria_id, nombre FROM categorias");
                        while ($categoria = $cat_stmt->fetch(PDO::FETCH_ASSOC)) {
                            $selected = ($categoria['categoria_id'] == $producto_editar['categoria_id']) ? 'selected' : '';
                            echo "<option value='" . htmlspecialchars($categoria['categoria_id']) . "' $selected>" 
                                 . htmlspecialchars($categoria['nombre']) . "</option>";
                        }
                        ?>
                    </select>
                </div>
                
                <div class="input-box">
                    <label>Imagen actual:</label>
                    <?php if ($producto_editar['imagen']): ?>
                        <img src="uploads/<?php echo htmlspecialchars($producto_editar['imagen']); ?>" 
                             alt="Imagen actual" style="max-width: 200px; max-height: 200px;">
                    <?php else: ?>
                        <p>No hay imagen</p>
                    <?php endif; ?>
                </div>
                
                <div class="input-box">
                    <label>Actualizar imagen:</label>
                    <input type="file" name="imagen" accept="image/jpeg,image/png,image/gif">
                </div>
                
                <button type="submit" class="btn">Actualizar producto</button>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>