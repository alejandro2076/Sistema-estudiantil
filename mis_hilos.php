<?php
include_once 'includes/auth.php';
include_once 'config/database.php';

// Verificar que el usuario esté logueado
if (!isLoggedIn()) {
    header("Location: login.php");
    exit;
}

$database = new Database();
$db = $database->getConnection();

$user_id = $_SESSION['user_id'];

// Obtener información del usuario
$query = "SELECT username, email, fecha_registro FROM usuarios WHERE id = ?";
$stmt = $db->prepare($query);
$stmt->execute([$user_id]);
$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

// Obtener hilos del usuario con paginación
$pagina = $_GET['pagina'] ?? 1;
$hilos_por_pagina = 10;
$offset = ($pagina - 1) * $hilos_por_pagina;

$query = "SELECT h.*, c.nombre as categoria_nombre,
          (SELECT COUNT(*) FROM respuestas r WHERE r.hilo_id = h.id) as total_respuestas,
          (SELECT u2.username FROM respuestas r2 
           JOIN usuarios u2 ON r2.usuario_id = u2.id 
           WHERE r2.hilo_id = h.id 
           ORDER BY r2.fecha_creacion DESC LIMIT 1) as ultimo_usuario,
          (SELECT r2.fecha_creacion FROM respuestas r2 
           WHERE r2.hilo_id = h.id 
           ORDER BY r2.fecha_creacion DESC LIMIT 1) as ultima_respuesta
          FROM hilos h 
          JOIN categorias c ON h.categoria_id = c.id 
          WHERE h.usuario_id = ? 
          ORDER BY h.fecha_creacion DESC 
          LIMIT ? OFFSET ?";

$stmt = $db->prepare($query);
$stmt->bindValue(1, $user_id, PDO::PARAM_INT);
$stmt->bindValue(2, $hilos_por_pagina, PDO::PARAM_INT);
$stmt->bindValue(3, $offset, PDO::PARAM_INT);
$stmt->execute();
$mis_hilos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Contar total de hilos del usuario
$query = "SELECT COUNT(*) as total FROM hilos WHERE usuario_id = ?";
$stmt = $db->prepare($query);
$stmt->execute([$user_id]);
$total_hilos = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
$total_paginas = ceil($total_hilos / $hilos_por_pagina);

// Obtener estadísticas del usuario
$query = "SELECT 
          COUNT(DISTINCT h.id) as total_hilos,
          COUNT(DISTINCT r.id) as total_respuestas,
          (SELECT COUNT(*) FROM hilos h2 WHERE h2.usuario_id = ? AND DATE(h2.fecha_creacion) = CURDATE()) as hilos_hoy,
          (SELECT COUNT(*) FROM respuestas r2 WHERE r2.usuario_id = ? AND DATE(r2.fecha_creacion) = CURDATE()) as respuestas_hoy
          FROM usuarios u 
          LEFT JOIN hilos h ON u.id = h.usuario_id 
          LEFT JOIN respuestas r ON u.id = r.usuario_id 
          WHERE u.id = ?";

$stmt = $db->prepare($query);
$stmt->execute([$user_id, $user_id, $user_id]);
$estadisticas = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Hilos - Foro Comunitario</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #667eea;
            --secondary-color: #764ba2;
        }
        
        .profile-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
            border-radius: 0 0 20px 20px;
        }
        
        .stat-card {
            background: white;
            border: none;
            border-radius: 12px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.12);
        }
        
        .thread-card {
            background: white;
            border: none;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            transition: all 0.3s ease;
            margin-bottom: 1rem;
            border-left: 4px solid var(--primary-color);
        }
        
        .thread-card:hover {
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            transform: translateY(-2px);
        }
        
        .empty-state {
            padding: 4rem 2rem;
            text-align: center;
            background: #f8f9fa;
            border-radius: 15px;
        }
        
        .user-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: white;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary-color);
            font-weight: bold;
            font-size: 2rem;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>
    
    <!-- Header del Perfil -->
    <div class="profile-header">
        <div class="container">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="index.php" class="text-white"><i class="fas fa-home"></i> Inicio</a></li>
                    <li class="breadcrumb-item active text-white">Mis Hilos</li>
                </ol>
            </nav>
            
            <div class="row align-items-center">
                <div class="col-md-8">
                    <div class="d-flex align-items-center">
                        <div class="user-avatar me-4">
                            <?php echo strtoupper(substr($usuario['username'], 0, 1)); ?>
                        </div>
                        <div>
                            <h1 class="display-5 fw-bold mb-2">Mis Hilos de Discusión</h1>
                            <p class="lead mb-0 opacity-75">Gestiona y revisa todos tus hilos creados</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 text-end">
                    <a href="nuevo_hilo.php" class="btn btn-light btn-lg">
                        <i class="fas fa-plus-circle me-2"></i> Nuevo Hilo
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <!-- Estadísticas del Usuario -->
        <div class="row mb-5">
            <div class="col-md-3">
                <div class="stat-card text-center p-4">
                    <i class="fas fa-comments fa-2x text-primary mb-3"></i>
                    <h3 class="text-primary"><?php echo $estadisticas['total_hilos']; ?></h3>
                    <p class="text-muted mb-0">Hilos Creados</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card text-center p-4">
                    <i class="fas fa-reply fa-2x text-success mb-3"></i>
                    <h3 class="text-success"><?php echo $estadisticas['total_respuestas']; ?></h3>
                    <p class="text-muted mb-0">Respuestas Totales</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card text-center p-4">
                    <i class="fas fa-calendar-day fa-2x text-info mb-3"></i>
                    <h3 class="text-info"><?php echo $estadisticas['hilos_hoy']; ?></h3>
                    <p class="text-muted mb-0">Hilos Hoy</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card text-center p-4">
                    <i class="fas fa-comment-dots fa-2x text-warning mb-3"></i>
                    <h3 class="text-warning"><?php echo $estadisticas['respuestas_hoy']; ?></h3>
                    <p class="text-muted mb-0">Respuestas Hoy</p>
                </div>
            </div>
        </div>

        <!-- Lista de Hilos -->
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-0 py-3">
                <div class="d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">
                        <i class="fas fa-list me-2 text-primary"></i>
                        Mis Hilos
                    </h4>
                    <span class="badge bg-primary"><?php echo $total_hilos; ?> hilos</span>
                </div>
            </div>
            <div class="card-body">
                <?php if(count($mis_hilos) > 0): ?>
                    <?php foreach($mis_hilos as $hilo): ?>
                        <div class="thread-card">
                            <div class="card-body">
                                <div class="row align-items-center">
                                    <div class="col-md-8">
                                        <h5 class="card-title mb-2">
                                            <a href="hilo.php?id=<?php echo $hilo['id']; ?>" class="text-decoration-none text-dark">
                                                <?php echo htmlspecialchars($hilo['titulo']); ?>
                                            </a>
                                        </h5>
                                        <div class="d-flex align-items-center text-muted small mb-2">
                                            <span class="me-3">
                                                <i class="fas fa-folder"></i> 
                                                <?php echo htmlspecialchars($hilo['categoria_nombre']); ?>
                                            </span>
                                            <span class="me-3">
                                                <i class="fas fa-clock"></i> 
                                                <?php echo date('d/m/Y H:i', strtotime($hilo['fecha_creacion'])); ?>
                                            </span>
                                        </div>
                                        <?php if($hilo['ultimo_usuario']): ?>
                                            <small class="text-muted">
                                                Última respuesta por <strong><?php echo htmlspecialchars($hilo['ultimo_usuario']); ?></strong> 
                                                el <?php echo date('d/m/Y H:i', strtotime($hilo['ultima_respuesta'])); ?>
                                            </small>
                                        <?php else: ?>
                                            <small class="text-muted">
                                                <i class="fas fa-comment-slash"></i> Sin respuestas aún
                                            </small>
                                        <?php endif; ?>
                                    </div>
                                    <div class="col-md-4 text-end">
                                        <div class="d-flex flex-column align-items-end">
                                            <span class="badge bg-light text-dark p-2 mb-2">
                                                <i class="fas fa-reply"></i> 
                                                <?php echo $hilo['total_respuestas']; ?> respuestas
                                            </span>
                                            <div class="btn-group">
                                                <a href="hilo.php?id=<?php echo $hilo['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-eye"></i> Ver
                                                </a>
                                                <a href="editar_hilo.php?id=<?php echo $hilo['id']; ?>" class="btn btn-sm btn-outline-secondary">
                                                    <i class="fas fa-edit"></i> Editar
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <!-- Paginación -->
                    <?php if($total_paginas > 1): ?>
                        <nav aria-label="Paginación de hilos" class="mt-4">
                            <ul class="pagination justify-content-center">
                                <?php if($pagina > 1): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?pagina=<?php echo $pagina - 1; ?>">
                                            <i class="fas fa-chevron-left"></i> Anterior
                                        </a>
                                    </li>
                                <?php endif; ?>

                                <?php for($i = 1; $i <= $total_paginas; $i++): ?>
                                    <li class="page-item <?php echo $i == $pagina ? 'active' : ''; ?>">
                                        <a class="page-link" href="?pagina=<?php echo $i; ?>">
                                            <?php echo $i; ?>
                                        </a>
                                    </li>
                                <?php endfor; ?>

                                <?php if($pagina < $total_paginas): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?pagina=<?php echo $pagina + 1; ?>">
                                            Siguiente <i class="fas fa-chevron-right"></i>
                                        </a>
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </nav>
                    <?php endif; ?>

                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-comments fa-4x text-muted mb-4"></i>
                        <h3 class="text-muted">No has creado ningún hilo aún</h3>
                        <p class="text-muted mb-4">Comienza compartiendo tus ideas y preguntas con la comunidad</p>
                        <a href="nuevo_hilo.php" class="btn btn-primary btn-lg">
                            <i class="fas fa-plus-circle me-2"></i> Crear Mi Primer Hilo
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>