<?php
session_start();

require_once($_SERVER['DOCUMENT_ROOT'] . '/proyecto_final/conexion/conexion.php');

// Verificar autenticación
if (!isset($_SESSION['user_id'])) {
    // Redirigir al login si el usuario no está autenticado
    header("Location: /proyecto_final/logueo/logueo.html");
    exit();
}

$user_id = $_SESSION['user_id'];

try {
    // Consultar productos del menú y sus categorías
    $sql = "SELECT 
                mi.id,
                mi.nombre, 
                mi.descripcion, 
                mi.precio, 
                COALESCE(mi.imagen, 'default_product.jpg') AS imagen, 
                c.nombre AS categoria 
            FROM menu_items mi 
            JOIN categorias c ON mi.categoria_id = c.categoria_id";
    $stm = $pdo->prepare($sql);
    $stm->execute();
    $productos = $stm->fetchAll(PDO::FETCH_ASSOC);

    // Agrupar productos por categoría
    $productos_por_categoria = [];
    foreach ($productos as $producto) {
        $productos_por_categoria[$producto['categoria']][] = $producto;
    }
} catch (PDOException $e) {
    error_log('Error al obtener los productos: ' . $e->getMessage());
    $productos_por_categoria = [];
}

// Función para agregar un producto al carrito
if (isset($_GET['agregar'])) {
    $menu_item_id = filter_input(INPUT_GET, 'agregar', FILTER_VALIDATE_INT);
    $precio_producto = filter_input(INPUT_GET, 'precio', FILTER_VALIDATE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);

    try {
        // Verificar si ya existe el producto en el carrito
        $stmt_check = $pdo->prepare("SELECT cantidad FROM carrito_usuarios WHERE user_id = ? AND menu_item_id = ?");
        $stmt_check->execute([$user_id, $menu_item_id]);
        $resultado = $stmt_check->fetch(PDO::FETCH_ASSOC);

        if (!$resultado) {
            // Si no existe, insertar nuevo producto con cantidad 1
            $stmt_insert = $pdo->prepare("INSERT INTO carrito_usuarios (user_id, menu_item_id, cantidad, precio) VALUES (?, ?, 1, ?)");
            $stmt_insert->execute([$user_id, $menu_item_id, $precio_producto]);
            error_log("Producto añadido al carrito: " . $menu_item_id);
        } else {
            // Si existe, incrementar la cantidad
            $stmt_update = $pdo->prepare("UPDATE carrito_usuarios SET cantidad = cantidad + 1 WHERE user_id = ? AND menu_item_id = ?");
            $stmt_update->execute([$user_id, $menu_item_id]);
            error_log("Cantidad de producto actualizada: " . $menu_item_id);
        }

        // Redirigir para evitar reenvío de formulario
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    } catch (PDOException $e) {
        error_log('Error al agregar producto al carrito: ' . $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="cliente.css">
    <title>Productos del Menú</title>
</head>
<body>
    <header>
        <a href="/proyecto_final/index.php" style="text-decoration: none;">
            <div class="logo">
              <h2 style="color: #fff;">CAFETERIA <span style="color: #FF6347;">UNIV</span><span style="color: #4682B4;">ERSITARIA</span><span style="color: #fff;;">  "CUBO"</span></h2> 
            </div>
        </a>
        <nav>
            <ul>
                <li><a href="/proyecto_final/logueo/logout.php">Cerrar Sesión</a></li>
            </ul>
        </nav>
    </header>

    <div class="container">
        <h1>Productos del Menú</h1>

        <?php if (!empty($productos_por_categoria)): ?>
            <?php foreach ($productos_por_categoria as $categoria => $productos_categoria): ?>
                <div class="category-section">
                    <h2><?php echo htmlspecialchars($categoria); ?></h2>
                    <div class="product-container">
                        <?php foreach ($productos_categoria as $producto): ?>
                            <?php 
                            // Preparar rutas de imagen con múltiples respaldos
                            $imagen_paths = [
                                "/proyecto_final/administrador/uploads/" . $producto['imagen'],
                                "/proyecto_final/uploads/" . $producto['imagen'],
                                "placeholder.jpg"
                            ];
                            
                            // Encontrar primera imagen existente
                            $imagen_final = '';
                            foreach ($imagen_paths as $path) {
                                if (file_exists($_SERVER['DOCUMENT_ROOT'] . $path)) {
                                    $imagen_final = $path;
                                    break;
                                }
                            }
                            ?>
                            <div class="product-card">
                                <img src="<?php echo htmlspecialchars($imagen_final); ?>" 
                                     alt="<?php echo htmlspecialchars($producto['nombre']); ?>"
                                     onerror="this.onerror=null; this.src='placeholder.jpg'; this.alt='Imagen no disponible';">
                                <h3><?php echo htmlspecialchars($producto['nombre']); ?></h3>
                                <p><?php echo htmlspecialchars($producto['descripcion']); ?></p>
                                <p class="price"><?php echo number_format($producto['precio'], 2); ?> €</p>
                                <a href="?agregar=<?php echo urlencode($producto['id']); ?>&precio=<?php echo urlencode($producto['precio']); ?>" 
                                   class="add-to-cart">Agregar al carrito</a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>No hay productos disponibles en el menú.</p>
        <?php endif; ?>

        <div class="cart-button"> 
            <a href="carrito.php" class="view-cart">Ver Carrito</a>
        </div>
    </div>
</body>
</html>
