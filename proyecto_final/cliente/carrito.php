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

// Manejar actualización de cantidad
if (isset($_POST['actualizar_cantidad'])) {
    $menu_item_id = filter_input(INPUT_POST, 'menu_item_id', FILTER_VALIDATE_INT);
    $nueva_cantidad = filter_input(INPUT_POST, 'cantidad', FILTER_VALIDATE_INT);

    if ($nueva_cantidad > 0) {
        try {
            $stmt = $pdo->prepare("UPDATE carrito_usuarios SET cantidad = ? WHERE user_id = ? AND menu_item_id = ?");
            $stmt->execute([$nueva_cantidad, $user_id, $menu_item_id]);
        } catch (PDOException $e) {
            error_log('Error al actualizar cantidad: ' . $e->getMessage());
        }
    }

    header("Location: carrito.php");
    exit();
}

// Manejar eliminación de producto
if (isset($_GET['eliminar'])) {
    $menu_item_id = filter_input(INPUT_GET, 'eliminar', FILTER_VALIDATE_INT);

    try {
        $stmt = $pdo->prepare("DELETE FROM carrito_usuarios WHERE user_id = ? AND menu_item_id = ?");
        $stmt->execute([$user_id, $menu_item_id]);
    } catch (PDOException $e) {
        error_log('Error al eliminar producto del carrito: ' . $e->getMessage());
    }

    header("Location: carrito.php");
    exit();
}

// Obtener productos del carrito
try {
    $stmt = $pdo->prepare("
        SELECT 
            cu.menu_item_id, 
            cu.cantidad, 
            cu.precio, 
            mi.nombre AS producto_nombre,
            mi.imagen,
            mi.descripcion,
            (cu.cantidad * cu.precio) AS subtotal 
        FROM carrito_usuarios cu
        JOIN menu_items mi ON cu.menu_item_id = mi.id
        WHERE cu.user_id = ?
    ");
    $stmt->execute([$user_id]);
    $productos_carrito = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Calcular total
    $total = array_reduce($productos_carrito, function($carry, $item) {
        return $carry + $item['subtotal'];
    }, 0);
} catch (PDOException $e) {
    error_log('Error al obtener productos del carrito: ' . $e->getMessage());
    $productos_carrito = [];
    $total = 0;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="cliente.css">
    <title>Carrito de Compras</title>
    <style>
        .cart-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        .cart-item {
            display: flex;
            align-items: center;
            border-bottom: 1px solid #ddd;
            padding: 10px 0;
        }
        .cart-item img {
            width: 100px;
            height: 100px;
            object-fit: cover;
            margin-right: 20px;
        }
        .cart-item-details {
            flex-grow: 1;
        }
        .cart-actions {
            display: flex;
            align-items: center;
        }
        .cart-total {
            text-align: right;
            margin-top: 20px;
            font-size: 1.2em;
            font-weight: bold;
        }
    </style>
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

    <div class="cart-container">
        <h1>Tu Carrito de Compras</h1>

        <?php if (empty($productos_carrito)): ?>
            <p>Tu carrito está vacío.</p>
            <a href="cliente.php" class="add-to-cart">Volver al menú</a>
        <?php else: ?>
            <?php foreach ($productos_carrito as $producto): ?>
                <div class="cart-item">
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
                    <img src="<?php echo htmlspecialchars($imagen_final); ?>" 
                         alt="<?php echo htmlspecialchars($producto['producto_nombre']); ?>"
                         onerror="this.onerror=null; this.src='placeholder.jpg'; this.alt='Imagen no disponible';">
                    
                    <div class="cart-item-details">
                        <h3><?php echo htmlspecialchars($producto['producto_nombre']); ?></h3>
                        <p><?php echo htmlspecialchars($producto['descripcion']); ?></p>
                        <p>Precio: <?php echo number_format($producto['precio'], 2); ?> €</p>
                        
                        <form method="POST" class="cart-actions">
                            <input type="hidden" name="menu_item_id" value="<?php echo $producto['menu_item_id']; ?>">
                            
                            <label for="cantidad-<?php echo $producto['menu_item_id']; ?>">Cantidad:</label>
                            <input type="number" 
                                   id="cantidad-<?php echo $producto['menu_item_id']; ?>" 
                                   name="cantidad" 
                                   value="<?php echo $producto['cantidad']; ?>" 
                                   min="1" 
                                   onchange="this.form.submit()">
                            
                            <input type="hidden" name="actualizar_cantidad" value="1">
                            
                            <a href="?eliminar=<?php echo $producto['menu_item_id']; ?>" 
                               onclick="return confirm('¿Estás seguro de eliminar este producto?');"
                               class="add-to-cart">Eliminar</a>
                        </form>
                        
                        <p>Subtotal: <?php echo number_format($producto['subtotal'], 2); ?> €</p>
                    </div>
                </div>
            <?php endforeach; ?>

            <div class="cart-total">
                <strong>Total: <?php echo number_format($total, 2); ?> €</strong>
            </div>

            <div class="cart-actions">
                <a href="cliente.php" class="add-to-cart">Seguir comprando</a>
                <a href="#" class="add-to-cart" onclick="alert('Funcionalidad de pago próximamente');">Proceder al Pago</a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
