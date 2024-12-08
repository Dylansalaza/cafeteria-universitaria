<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/proyecto_final/conexion/conexion.php');

try {
    $sql = "SELECT 
                mi.nombre, 
                COALESCE(mi.imagen, 'default_product.jpg') AS imagen, 
                c.nombre AS categoria 
            FROM menu_items mi 
            JOIN categorias c ON mi.categoria_id = c.categoria_id";
    $stm = $pdo->prepare($sql);
    $stm->execute();
    $productos = $stm->fetchAll(PDO::FETCH_ASSOC);

    $productos_por_categoria = [];
    foreach ($productos as $producto) {
        $productos_por_categoria[$producto['categoria']][] = $producto;
    }
} catch (PDOException $e) {
    error_log('Error al obtener los productos: ' . $e->getMessage());
    $productos_por_categoria = [];
}

// Carousel images configuration
$carousel_images = [
    [
        'src' => '/proyecto_final/img/carrucel1.jpg', 
        'alt' => 'Deliciosos platillos',
        'title' => 'Bienvenidos a CUBO'
    ],
    [
        'src' => '/proyecto_final/img/descarga.jpg', 
        'alt' => 'Variedad de comida',
        'title' => 'Sabor y Calidad'
    ],
];
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="index.css">
    <title>Cafeteria universitaria 'CUBO'</title>
</head>
<body>
    <header>
        <div class="logo">
            
        <h2 style="color: #fff;">CAFETERIA <span style="color: #FF6347;">UNIV</span><span style="color: #4682B4;">ERSITARIA</span><span style="color: #fff;;">  "CUBO"</span></h2> 
        </div>
        <nav>
            <ul>
                <li><a href="/proyecto_final/logueo/logueo.html">Iniciar Sesión</a></li>
                <li><a href="/proyecto_final/registro/registro.html">Registrarse</a></li>
            </ul>
        </nav>
    </header>

    <main>
        <section class="hero">
            <div class="hero-carousel">
                <?php foreach ($carousel_images as $index => $image): ?>
                    <img 
                        src="<?php echo htmlspecialchars($image['src']); ?>" 
                        alt="<?php echo htmlspecialchars($image['alt']); ?>" 
                        class="carousel-image <?php echo $index === 0 ? 'active' : ''; ?>"
                        onerror="this.onerror=null; this.src='placeholder.jpg'; this.alt='Imagen no disponible';"
                    >
                <?php endforeach; ?>

                <div class="carousel-overlay">
                    <h1> Bienvenido a tu Cafeteria </h1>
                    <a href="/proyecto_final/logueo/logueo.html">
                        <button>Ordena ahora</button>
                    </a>
                </div>

                <button class="carousel-button carousel-prev">&#10094;</button>
                <button class="carousel-button carousel-next">&#10095;</button>

                <div class="carousel-indicators">
                    <?php foreach ($carousel_images as $index => $image): ?>
                        <span 
                            class="indicator <?php echo $index === 0 ? 'active' : ''; ?>" 
                            data-slide="<?php echo $index; ?>">
                        </span>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
        
        <section class="menu-sections">
        <h1  class="h1">Menu</h1> 
            <?php if (!empty($productos_por_categoria)): ?>
                <?php foreach ($productos_por_categoria as $categoria => $productos_categoria): ?>
                    <?php foreach ($productos_categoria as $producto): ?>
                        <?php 
                        $imagen_paths = [
                            "/proyecto_final/administrador/uploads/" . $producto['imagen'],
                            "/proyecto_final/uploads/" . $producto['imagen'],
                            "placeholder.jpg"
                        ];
                        
                        $imagen_final = '';
                        foreach ($imagen_paths as $path) {
                            if (file_exists($_SERVER['DOCUMENT_ROOT'] . $path)) {
                                $imagen_final = $path;
                                break;
                            }
                        }
                        ?>
                        <div class="menu-item">
                            <img src="<?php echo htmlspecialchars($imagen_final); ?>" 
                                 alt="<?php echo htmlspecialchars($producto['nombre']); ?>"
                                 onerror="this.onerror=null; this.src='placeholder.jpg'; this.alt='Imagen no disponible';">
                            <h3><?php echo htmlspecialchars($producto['nombre']); ?></h3>
                        </div>
                    <?php endforeach; ?>
                <?php endforeach; ?>
            <?php else: ?>
                <p>No hay productos disponibles en el menú.</p>
            <?php endif; ?>
        </section>
    </main>

    <footer>
        <p>&copy; 2024 Cafeteria Universitaria 'CUBO'. Todos los derechos reservados.</p>
        <p><a href="#">Política de Privacidad</a> | <a href="#">Términos y Condiciones</a></p>
        <p>Síguenos en: 
            <a href="#">Facebook</a> | 
            <a href="#">Twitter</a> | 
            <a href="#">Instagram</a>
        </p>
    </footer>

    <script src="index.js"></script>
</body>
</html>