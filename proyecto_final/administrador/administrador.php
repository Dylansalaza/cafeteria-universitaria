<?php
// Strict error reporting and display
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session
session_start();

// Use relative path for better portability
require_once ('C:\xampp\htdocs\proyecto_final/conexion/conexion.php');

// Secure image upload directory
$upload_dir = 'uploads/';

// Create upload directory securely
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

/**
 * Handle image upload with enhanced security and error handling
 * 
 * @param array $files $_FILES array
 * @param string $upload_dir Upload directory path
 * @param string|null $old_image Previous image filename
 * @return string|null Uploaded image filename or null
 * @throws Exception On upload errors
 */
function handleImageUpload($files, $upload_dir, $old_image = null) {
    // Validate file upload
    if (!isset($files['imagen']) || $files['imagen']['error'] === UPLOAD_ERR_NO_FILE) {
        return $old_image;
    }

    // Allowed image types and max file size
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
    $max_size = 5 * 1024 * 1024; // 5MB

    $file = $files['imagen'];

    // Validate file type
    $file_type = mime_content_type($file['tmp_name']);
    if (!in_array($file_type, $allowed_types)) {
        throw new Exception('Invalid file type. Only JPEG, PNG, and GIF are allowed.');
    }

    // Validate file size
    if ($file['size'] > $max_size) {
        throw new Exception('File too large. Maximum 5MB allowed.');
    }

    // Generate secure filename
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $filename = bin2hex(random_bytes(16)) . '.' . $extension;
    $full_path = $upload_dir . $filename;

    // Move uploaded file with additional checks
    if (!move_uploaded_file($file['tmp_name'], $full_path)) {
        throw new Exception('Failed to save image. Check directory permissions.');
    }

    // Delete old image if exists
    if ($old_image && file_exists($upload_dir . $old_image)) {
        unlink($upload_dir . $old_image);
    }

    return $filename;
}

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        // Validate required fields
        $required_fields = ['nombre', 'descripcion', 'precio', 'categoria'];
        foreach ($required_fields as $field) {
            if (empty($_POST[$field])) {
                throw new Exception("El campo $field es obligatorio.");
            }
        }

        // Validate price
        if (!is_numeric($_POST['precio']) || $_POST['precio'] <= 0) {
            throw new Exception("El precio debe ser un número positivo.");
        }

        // Validate category
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM categorias WHERE categoria_id = :categoria_id");
        $stmt->execute([':categoria_id' => $_POST['categoria']]);
        if ($stmt->fetchColumn() == 0) {
            throw new Exception("Categoría inválida.");
        }

        // Handle image upload
        $imagen_path = null;
        if (!empty($_FILES['imagen']['name'])) {
            $imagen_path = handleImageUpload($_FILES, $upload_dir);
        }

        // Prepare and execute insert with prepared statement
        $sql = "INSERT INTO menu_items (nombre, descripcion, precio, categoria_id, imagen) 
                VALUES (:nombre, :descripcion, :precio, :categoria, :imagen)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':nombre' => strip_tags(trim($_POST['nombre'])),
            ':descripcion' => strip_tags(trim($_POST['descripcion'])),
            ':precio' => floatval($_POST['precio']),
            ':categoria' => intval($_POST['categoria']),
            ':imagen' => $imagen_path
        ]);

        // Redirect to prevent form resubmission
        header('Location: ' . $_SERVER['PHP_SELF'] . '?success=1');
        exit();

    } catch (Exception $e) {
        // Store error in session to display after redirect
        $_SESSION['error_message'] = $e->getMessage();
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="administrador.css">
    <title>Panel de Administración - Cafetería Universitaria</title>
</head>
<body> 
    <header class='header'>
        <?php include 'header.php'; ?>
    </header>

    <div class="formulario"> 
        <h1>Agregar Producto</h1> 
        
        <?php
        // Display success or error messages
        if (isset($_GET['success'])) {
            echo "<div class='success-message'>Producto añadido correctamente.</div>";
        }
        
        if (isset($_SESSION['error_message'])) {
            echo "<div class='error-message'>" . htmlspecialchars($_SESSION['error_message']) . "</div>";
            unset($_SESSION['error_message']);
        }
        ?>

        <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="POST" enctype="multipart/form-data"> 
            <div class="input-box"> 
                <label for="nombre">Nombre del item:</label> 
                <input type="text" id="nombre" name="nombre" required placeholder="Nombre del Producto" maxlength="100"> 
            </div>
            
            <div class="input-box"> 
                <label for="descripcion">Descripción del item:</label> 
                <textarea id="descripcion" name="descripcion" required placeholder="Descripción del Producto" maxlength="500"></textarea> 
            </div> 
            
            <div class="input-box"> 
                <label for="precio">Precio del item:</label> 
                <input type="number" id="precio" name="precio" step="0.01" min="0" required placeholder="Precio"> 
            </div> 
            
            <div class="input-box"> 
                <label for="categoria">Categoría:</label> 
                <select id="categoria" name="categoria" required> 
                    <?php 
                    // Fetch categories securely
                    $cat_stmt = $pdo->query("SELECT categoria_id, nombre FROM categorias ORDER BY nombre"); 
                    while ($categoria = $cat_stmt->fetch(PDO::FETCH_ASSOC)) {
                        echo "<option value='" . htmlspecialchars($categoria['categoria_id']) . "'>" 
                             . htmlspecialchars($categoria['nombre']) . "</option>"; 
                    } 
                    ?> 
                </select> 
            </div> 
            
            <div class="input-box"> 
                <label for="imagen">Imagen:</label> 
                <input type="file" id="imagen" name="imagen" accept="image/jpeg,image/png,image/gif"> 
            </div> 
            
            <button type="submit" class="btn">Agregar producto</button> 
        </form> 
    </div> 
</body>
</html>