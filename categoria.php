<?php
include_once 'includes/auth.php';
include_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();

$categoria_id = $_GET['id'] ?? 0;

// Obtener información de la categoría
$query = "SELECT * FROM categorias WHERE id = ?";
$stmt = $db->prepare($query);
$stmt->execute([$categoria_id]);
$categoria = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$categoria){
    header("Location: index.php");
    exit;
}

// Determinar ordenamiento
$orden = $_GET['orden'] ?? 'recientes';
$orden_sql = "h.ultima_respuesta DESC";
switch($orden) {
    case 'antiguos':
        $orden_sql = "h.fecha_creacion ASC";
        break;
    case 'respuestas':
        $orden_sql = "total_respuestas DESC";
        break;
    case 'vistas':
        $orden_sql = "h.vistas DESC";
        break;
    case 'recientes':
    default:
        $orden_sql = "h.ultima_respuesta DESC";
        break;
}

// Obtener hilos de esta categoría con paginación
$pagina = $_GET['pagina'] ?? 1;
$hilos_por_pagina = 10;
$offset = ($pagina - 1) * $hilos_por_pagina;

$query = "SELECT h.*, u.username as autor, 
          (SELECT COUNT(*) FROM respuestas r WHERE r.hilo_id = h.id) as total_respuestas,
          (SELECT u2.username FROM respuestas r2 
           JOIN usuarios u2 ON r2.usuario_id = u2.id 
           WHERE r2.hilo_id = h.id 
           ORDER BY r2.fecha_creacion DESC LIMIT 1) as ultimo_usuario,
          (SELECT r2.fecha_creacion FROM respuestas r2 
           WHERE r2.hilo_id = h.id 
           ORDER BY r2.fecha_creacion DESC LIMIT 1) as ultima_respuesta
          FROM hilos h 
          JOIN usuarios u ON h.usuario_id = u.id 
          WHERE h.categoria_id = ? 
          ORDER BY $orden_sql 
          LIMIT ? OFFSET ?";

$stmt = $db->prepare($query);
$stmt->bindValue(1, $categoria_id, PDO::PARAM_INT);
$stmt->bindValue(2, $hilos_por_pagina, PDO::PARAM_INT);
$stmt->bindValue(3, $offset, PDO::PARAM_INT);
$stmt->execute();
$hilos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Contar total de hilos para paginación
$query = "SELECT COUNT(*) as total FROM hilos WHERE categoria_id = ?";
$stmt = $db->prepare($query);
$stmt->execute([$categoria_id]);
$total_hilos = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
$total_paginas = ceil($total_hilos / $hilos_por_pagina);

// Obtener estadísticas de la categoría
$query = "SELECT 
          COUNT(DISTINCT h.usuario_id) as usuarios_activos,
          (SELECT COUNT(*) FROM respuestas r 
           JOIN hilos h2 ON r.hilo_id = h2.id 
           WHERE h2.categoria_id = ?) as total_respuestas
          FROM hilos h 
          WHERE h.categoria_id = ?";
$stmt = $db->prepare($query);
$stmt->execute([$categoria_id, $categoria_id]);
$estadisticas = $stmt->fetch(PDO::FETCH_ASSOC);

// Obtener otras categorías para el sidebar
$query = "SELECT * FROM categorias WHERE id != ? ORDER BY nombre LIMIT 5";
$stmt = $db->prepare($query);
$stmt->execute([$categoria_id]);
$otras_categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($categoria['nombre']); ?> - Foro Comunitario</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #667eea;
            --secondary-color: #764ba2;
        }
        
        .category-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
        }
        
        .thread-card {
            background: white;
            border: none;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            transition: all 0.3s ease;
            margin-bottom: 1rem;
            border-left: 4px solid var(--primary-color);
            cursor: pointer;
        }
        
        .thread-card:hover {
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            transform: translateY(-2px);
            border-left-color: var(--secondary-color);
        }
        
        .stats-card {
            background: white;
            border: none;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }
        
        .user-avatar-sm {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 0.8rem;
        }
        
        .empty-state {
            padding: 4rem 2rem;
            text-align: center;
            background: #f8f9fa;
            border-radius: 15px;
        }
        
        .thread-title {
            color: #2c3e50;
            transition: color 0.3s ease;
        }
        
        .thread-card:hover .thread-title {
            color: var(--primary-color);
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>
    
    <div class="category-header">
        <div class="container">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="index.php" class="text-white"><i class="fas fa-home"></i> Foro</a></li>
                    <li class="breadcrumb-item active text-white"><?php echo htmlspecialchars($categoria['nombre']); ?></li>
                </ol>
            </nav>
            
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="display-5 fw-bold mb-3"><?php echo htmlspecialchars($categoria['nombre']); ?></h1>
                    <p class="lead mb-0 opacity-75"><?php echo htmlspecialchars($categoria['descripcion']); ?></p>
                </div>
                <div class="col-md-4 text-end">
                    <?php if(isLoggedIn()): ?>
                        <a href="nuevo_hilo.php?categoria=<?php echo $categoria_id; ?>" class="btn btn-light btn-lg">
                            <i class="fas fa-plus-circle me-2"></i> Nuevo Hilo
                        </a>
                    <?php else: ?>
                        <a href="login.php" class="btn btn-outline-light btn-lg">
                            <i class="fas fa-sign-in-alt me-2"></i> Iniciar Sesión
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="row">
            <div class="col-lg-9">
                <!-- Estadísticas -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="stats-card text-center">
                            <i class="fas fa-comments fa-2x text-primary mb-2"></i>
                            <h4 class="text-primary"><?php echo $total_hilos; ?></h4>
                            <p class="text-muted mb-0">Hilos</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stats-card text-center">
                            <i class="fas fa-reply fa-2x text-success mb-2"></i>
                            <h4 class="text-success"><?php echo $estadisticas['total_respuestas']; ?></h4>
                            <p class="text-muted mb-0">Respuestas</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stats-card text-center">
                            <i class="fas fa-users fa-2x text-info mb-2"></i>
                            <h4 class="text-info"><?php echo $estadisticas['usuarios_activos']; ?></h4>
                            <p class="text-muted mb-0">Usuarios Activos</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stats-card text-center">
                            <i class="fas fa-chart-line fa-2x text-warning mb-2"></i>
                            <h4 class="text-warning"><?php echo $total_hilos + $estadisticas['total_respuestas']; ?></h4>
                            <p class="text-muted mb-0">Total Contribuciones</p>
                        </div>
                    </div>
                </div>

                <!-- Lista de Hilos -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h3 class="mb-0">
                        <i class="fas fa-list me-2 text-primary"></i>
                        Hilos de Discusión
                    </h3>
                    <div class="dropdown">
                        <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                            <i class="fas fa-sort me-2"></i> Ordenar por
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item <?php echo $orden == 'recientes' ? 'active' : ''; ?>" href="?id=<?php echo $categoria_id; ?>&orden=recientes">Más recientes</a></li>
                            <li><a class="dropdown-item <?php echo $orden == 'antiguos' ? 'active' : ''; ?>" href="?id=<?php echo $categoria_id; ?>&orden=antiguos">Más antiguos</a></li>
                            <li><a class="dropdown-item <?php echo $orden == 'respuestas' ? 'active' : ''; ?>" href="?id=<?php echo $categoria_id; ?>&orden=respuestas">Más respuestas</a></li>
                        </ul>
                    </div>
                </div>

                <?php if(count($hilos) > 0): ?>
                    <?php foreach($hilos as $hilo): ?>
                        <div class="thread-card" onclick="window.location.href='hilo.php?id=<?php echo $hilo['id']; ?>'">
                            <div class="card-body">
                                <div class="row align-items-center">
                                    <div class="col-md-8">
                                        <h5 class="card-title mb-2 thread-title">
                                            <?php echo htmlspecialchars($hilo['titulo']); ?>
                                        </h5>
                                        <div class="d-flex align-items-center text-muted small mb-2">
                                            <div class="user-avatar-sm me-2">
                                                <?php echo strtoupper(substr($hilo['autor'], 0, 1)); ?>
                                            </div>
                                            <span class="me-3"><?php echo htmlspecialchars($hilo['autor']); ?></span>
                                            <span class="me-3">
                                                <i class="fas fa-clock"></i> 
                                                <?php echo date('d/m/Y', strtotime($hilo['fecha_creacion'])); ?>
                                            </span>
                                        </div>
                                        <?php if($hilo['ultimo_usuario']): ?>
                                            <small class="text-muted">
                                                Última respuesta por <strong><?php echo htmlspecialchars($hilo['ultimo_usuario']); ?></strong> 
                                                el <?php echo date('d/m/Y H:i', strtotime($hilo['ultima_respuesta'])); ?>
                                            </small>
                                        <?php endif; ?>
                                    </div>
                                    <div class="col-md-4 text-end">
                                        <div class="d-flex flex-column align-items-end">
                                            <span class="badge bg-light text-dark p-2 mb-2">
                                                <i class="fas fa-reply"></i> 
                                                <?php echo $hilo['total_respuestas']; ?> respuestas
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-comments fa-4x text-muted mb-4"></i>
                        <h3 class="text-muted">No hay hilos en esta categoría</h3>
                        <p class="text-muted mb-4">Sé el primero en iniciar una discusión</p>
                        <?php if(isLoggedIn()): ?>
                            <a href="nuevo_hilo.php?categoria=<?php echo $categoria_id; ?>" class="btn btn-primary btn-lg">
                                <i class="fas fa-plus-circle me-2"></i> Crear Primer Hilo
                            </a>
                        <?php else: ?>
                            <a href="login.php" class="btn btn-primary btn-lg">
                                <i class="fas fa-sign-in-alt me-2"></i> Iniciar Sesión para Crear Hilo
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Sidebar -->
            <div class="col-lg-3">
                <div class="stats-card">
                    <h6 class="fw-bold mb-3">
                        <i class="fas fa-info-circle text-primary me-2"></i>
                        Sobre esta Categoría
                    </h6>
                    <p class="text-muted small"><?php echo htmlspecialchars($categoria['descripcion']); ?></p>
                </div>

                <div class="stats-card">
                    <h6 class="fw-bold mb-3">
                        <i class="fas fa-bolt text-warning me-2"></i>
                        Acciones Rápidas
                    </h6>
                    <div class="d-grid gap-2">
                        <?php if(isLoggedIn()): ?>
                            <a href="nuevo_hilo.php?categoria=<?php echo $categoria_id; ?>" class="btn btn-primary btn-sm">
                                <i class="fas fa-plus me-2"></i> Nuevo Hilo
                            </a>
                        <?php endif; ?>
                        <a href="index.php" class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-arrow-left me-2"></i> Volver al Foro
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>