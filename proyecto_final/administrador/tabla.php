<?php  
 

require_once ('C:\xampp\htdocs\proyecto_final/conexion/conexion.php'); 


//consulta para obtener todos los productos del menu

try{
    $sql = "SELECT mi.nombre, mi.descripcion, mi.precio, mi.imagen , c.nombre AS categoria
            FROM menu_items mi
            JOIN categorias c ON mi.categoria_id = c.categoria_id";
    
    $stm = $pdo->prepare($sql);
    $stm->execute();

    //Obtener los resultados
    $productos = $stm->fetchAll(PDO::FETCH_ASSOC);
    
}catch(Exception $e){
    echo'error al obtener los productos: ' . $e->getMessage();
}



?>



<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="tabla.css">
    <title>Productos del Menú</title>
    
</head>
<body>

    <header class='header'>
        <?php include 'header.php'; ?>
    </header>

    <h1>Productos del Menú</h1>
    <?php if (!empty($productos)): ?>
        <table>
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Descripción</th>
                    <th>Precio</th>
                    <th>Categoría</th>
                    <th>Imagen</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($productos as $producto): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($producto['nombre']); ?></td>
                        <td><?php echo htmlspecialchars($producto['descripcion']); ?></td>
                        <td><?php echo htmlspecialchars($producto['precio']); ?></td>
                        <td><?php echo htmlspecialchars($producto['categoria']); ?></td>
                        <td>
                        <?php if (!empty($producto['imagen'])): ?>
                                <img src="uploads/<?php echo htmlspecialchars($producto['imagen']); ?>" 
                                     alt="<?php echo htmlspecialchars($producto['nombre']); ?>" 
                                     class="product-image"
                                     onerror="this.onerror=null; this.alt='Error al cargar imagen'; this.src='placeholder.jpg';">
                            <?php else: ?>
                                No hay imagen
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>No hay productos en el menú.</p>
    <?php endif; ?>
</body>
</html>
