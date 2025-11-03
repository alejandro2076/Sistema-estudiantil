<?php
include_once 'includes/auth.php';
include_once 'config/database.php';

if(!isLoggedIn()){
    header("Location: login.php");
    exit;
}

$database = new Database();
$db = $database->getConnection();

$user_id = getUserID();

// Obtener datos del usuario
$query = "SELECT * FROM usuarios WHERE id = ?";
$stmt = $db->prepare($query);
$stmt->execute([$user_id]);
$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

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

// Obtener últimos hilos del usuario
$query = "SELECT h.*, c.nombre as categoria_nombre,
          (SELECT COUNT(*) FROM respuestas r WHERE r.hilo_id = h.id) as total_respuestas
          FROM hilos h 
          JOIN categorias c ON h.categoria_id = c.id 
          WHERE h.usuario_id = ? 
          ORDER BY h.fecha_creacion DESC 
          LIMIT 5";
$stmt = $db->prepare($query);
$stmt->execute([$user_id]);
$ultimos_hilos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener últimas respuestas
$query = "SELECT r.*, h.titulo as hilo_titulo, h.id as hilo_id
          FROM respuestas r 
          JOIN hilos h ON r.hilo_id = h.id 
          WHERE r.usuario_id = ? 
          ORDER BY r.fecha_creacion DESC 
          LIMIT 5";
$stmt = $db->prepare($query);
$stmt->execute([$user_id]);
$ultimas_respuestas = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Mi Perfil - Foro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .profile-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 3rem 0;
            margin-bottom: 2rem;
        }
        .avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            border: 4px solid white;
            background: #f8f9fa;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            color: #667eea;
        }
        .stat-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: #667eea;
        }
        .activity-item {
            border-left: 3px solid #667eea;
            padding-left: 1rem;
            margin-bottom: 1rem;
        }
        .badge-custom {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
        }
        .nav-pills .nav-link.active {
            background: linear-gradient(135deg, #667eea, #764ba2);
            border: none;
        }
        .progress {
            height: 8px;
            border-radius: 10px;
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>
    
    <!-- Header del Perfil -->
    <div class="profile-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-2 text-center">
                    <div class="avatar">
                        <i class="fas fa-user"></i>
                    </div>
                </div>
                <div class="col-md-8">
                    <h1 class="display-5"><?php echo htmlspecialchars($usuario['username']); ?></h1>
                    <p class="lead mb-0">
                        <i class="fas fa-envelope"></i> <?php echo htmlspecialchars($usuario['email']); ?>
                    </p>
                    <p class="mb-0">
                        <i class="fas fa-calendar"></i> Miembro desde: 
                        <?php echo date('d/m/Y', strtotime($usuario['fecha_registro'])); ?>
                    </p>
                </div>
                <div class="col-md-2 text-end">
                    <span class="badge bg-light text-dark fs-6">
                        <i class="fas fa-shield-alt"></i> <?php echo ucfirst($usuario['rol']); ?>
                    </span>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <!-- Navegación del perfil -->
        <ul class="nav nav-pills mb-4" id="profileTabs">
            <li class="nav-item">
                <a class="nav-link active" href="#estadisticas" data-bs-toggle="tab">
                    <i class="fas fa-chart-bar"></i> Estadísticas
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#hilos" data-bs-toggle="tab">
                    <i class="fas fa-comments"></i> Mis Hilos
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#respuestas" data-bs-toggle="tab">
                    <i class="fas fa-reply"></i> Mis Respuestas
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#configuracion" data-bs-toggle="tab">
                    <i class="fas fa-cog"></i> Configuración
                </a>
            </li>
        </ul>

        <div class="tab-content">
            <!-- Pestaña de Estadísticas -->
            <div class="tab-pane fade show active" id="estadisticas">
                <div class="row">
                    <!-- Tarjetas de Estadísticas -->
                    <div class="col-md-3 mb-4">
                        <div class="card stat-card text-center">
                            <div class="card-body">
                                <i class="fas fa-comments fa-2x text-primary mb-3"></i>
                                <div class="stat-number"><?php echo $estadisticas['total_hilos']; ?></div>
                                <p class="text-muted">Hilos Creados</p>
                                <small class="text-success">
                                    <i class="fas fa-arrow-up"></i> 
                                    <?php echo $estadisticas['hilos_hoy']; ?> hoy
                                </small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3 mb-4">
                        <div class="card stat-card text-center">
                            <div class="card-body">
                                <i class="fas fa-reply fa-2x text-success mb-3"></i>
                                <div class="stat-number"><?php echo $estadisticas['total_respuestas']; ?></div>
                                <p class="text-muted">Respuestas</p>
                                <small class="text-success">
                                    <i class="fas fa-arrow-up"></i> 
                                    <?php echo $estadisticas['respuestas_hoy']; ?> hoy
                                </small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3 mb-4">
                        <div class="card stat-card text-center">
                            <div class="card-body">
                                <i class="fas fa-calendar-day fa-2x text-warning mb-3"></i>
                                <div class="stat-number">
                                    <?php 
                                        $dias_miembro = floor((time() - strtotime($usuario['fecha_registro'])) / (60 * 60 * 24));
                                        echo $dias_miembro;
                                    ?>
                                </div>
                                <p class="text-muted">Días como Miembro</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3 mb-4">
                        <div class="card stat-card text-center">
                            <div class="card-body">
                                <i class="fas fa-trophy fa-2x text-warning mb-3"></i>
                                <div class="stat-number">
                                    <?php echo $estadisticas['total_hilos'] + $estadisticas['total_respuestas']; ?>
                                </div>
                                <p class="text-muted">Contribuciones Totales</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Actividad Reciente -->
                <div class="row">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0"><i class="fas fa-fire"></i> Actividad Reciente</h5>
                            </div>
                            <div class="card-body">
                                <?php if(count($ultimos_hilos) > 0 || count($ultimas_respuestas) > 0): ?>
                                    <?php 
                                    $actividades = [];
                                    foreach($ultimos_hilos as $hilo) {
                                        $actividades[] = [
                                            'tipo' => 'hilo',
                                            'titulo' => $hilo['titulo'],
                                            'fecha' => $hilo['fecha_creacion'],
                                            'id' => $hilo['id']
                                        ];
                                    }
                                    foreach($ultimas_respuestas as $respuesta) {
                                        $actividades[] = [
                                            'tipo' => 'respuesta',
                                            'titulo' => $respuesta['hilo_titulo'],
                                            'fecha' => $respuesta['fecha_creacion'],
                                            'id' => $respuesta['hilo_id']
                                        ];
                                    }
                                    
                                    // Ordenar por fecha
                                    usort($actividades, function($a, $b) {
                                        return strtotime($b['fecha']) - strtotime($a['fecha']);
                                    });
                                    
                                    $actividades = array_slice($actividades, 0, 5);
                                    ?>
                                    
                                    <?php foreach($actividades as $actividad): ?>
                                        <div class="activity-item">
                                            <div class="d-flex justify-content-between">
                                                <h6 class="mb-1">
                                                    <?php if($actividad['tipo'] == 'hilo'): ?>
                                                        <i class="fas fa-plus text-success"></i>
                                                        Creó el hilo:
                                                    <?php else: ?>
                                                        <i class="fas fa-reply text-info"></i>
                                                        Respondió en:
                                                    <?php endif; ?>
                                                    <a href="hilo.php?id=<?php echo $actividad['id']; ?>">
                                                        <?php echo htmlspecialchars($actividad['titulo']); ?>
                                                    </a>
                                                </h6>
                                            </div>
                                            <small class="text-muted">
                                                <?php echo date('d/m/Y H:i', strtotime($actividad['fecha'])); ?>
                                            </small>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <p class="text-muted">Aún no tienes actividad en el foro.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header bg-success text-white">
                                <h5 class="mb-0"><i class="fas fa-chart-line"></i> Progreso</h5>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label>Nivel de Actividad</label>
                                    <div class="progress mb-2">
                                        <?php 
                                        $nivel_actividad = min(100, ($estadisticas['total_hilos'] + $estadisticas['total_respuestas']) * 5);
                                        ?>
                                        <div class="progress-bar bg-success" style="width: <?php echo $nivel_actividad; ?>%">
                                            <?php echo $nivel_actividad; ?>%
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label>Completitud del Perfil</label>
                                    <div class="progress mb-2">
                                        <div class="progress-bar bg-info" style="width: 75%">75%</div>
                                    </div>
                                </div>
                                
                                <div class="achievements">
                                    <h6>Logros</h6>
                                    <?php if($estadisticas['total_hilos'] >= 10): ?>
                                        <span class="badge badge-custom me-1 mb-1">
                                            <i class="fas fa-star"></i> Creador Prolífico
                                        </span>
                                    <?php endif; ?>
                                    
                                    <?php if($estadisticas['total_respuestas'] >= 20): ?>
                                        <span class="badge badge-custom me-1 mb-1">
                                            <i class="fas fa-comments"></i> Gran Colaborador
                                        </span>
                                    <?php endif; ?>
                                    
                                    <?php if($dias_miembro >= 30): ?>
                                        <span class="badge badge-custom me-1 mb-1">
                                            <i class="fas fa-calendar"></i> Miembro Leal
                                        </span>
                                    <?php endif; ?>
                                    
                                    <?php if($estadisticas['total_hilos'] == 0 && $estadisticas['total_respuestas'] == 0): ?>
                                        <span class="badge bg-secondary me-1 mb-1">
                                            <i class="fas fa-seedling"></i> Nuevo Miembro
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Pestaña de Mis Hilos -->
            <div class="tab-pane fade" id="hilos">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-comments"></i> Mis Hilos Recientes</h5>
                    </div>
                    <div class="card-body">
                        <?php if(count($ultimos_hilos) > 0): ?>
                            <?php foreach($ultimos_hilos as $hilo): ?>
                                <div class="card mb-3">
                                    <div class="card-body">
                                        <h5 class="card-title">
                                            <a href="hilo.php?id=<?php echo $hilo['id']; ?>">
                                                <?php echo htmlspecialchars($hilo['titulo']); ?>
                                            </a>
                                        </h5>
                                        <p class="card-text">
                                            <small class="text-muted">
                                                <i class="fas fa-folder"></i> <?php echo htmlspecialchars($hilo['categoria_nombre']); ?> | 
                                                <i class="fas fa-clock"></i> <?php echo date('d/m/Y H:i', strtotime($hilo['fecha_creacion'])); ?> | 
                                                <i class="fas fa-reply"></i> <?php echo $hilo['total_respuestas']; ?> respuestas
                                            </small>
                                        </p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            
                            <div class="text-center">
                                <a href="mis_hilos.php" class="btn btn-primary">Ver Todos Mis Hilos</a>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-4">
                                <i class="fas fa-comments fa-3x text-muted mb-3"></i>
                                <h5>No has creado ningún hilo todavía</h5>
                                <p class="text-muted">¡Comienza una discusión en el foro!</p>
                                <a href="nuevo_hilo.php" class="btn btn-primary">Crear Mi Primer Hilo</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Pestaña de Mis Respuestas -->
            <div class="tab-pane fade" id="respuestas">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-reply"></i> Mis Respuestas Recientes</h5>
                    </div>
                    <div class="card-body">
                        <?php if(count($ultimas_respuestas) > 0): ?>
                            <?php foreach($ultimas_respuestas as $respuesta): ?>
                                <div class="card mb-3">
                                    <div class="card-body">
                                        <h6 class="card-title">
                                            <a href="hilo.php?id=<?php echo $respuesta['hilo_id']; ?>#respuesta-<?php echo $respuesta['id']; ?>">
                                                Respuesta en: <?php echo htmlspecialchars($respuesta['hilo_titulo']); ?>
                                            </a>
                                        </h6>
                                        <p class="card-text">
                                            <?php echo nl2br(htmlspecialchars(substr($respuesta['contenido'], 0, 150) . '...')); ?>
                                        </p>
                                        <small class="text-muted">
                                            <i class="fas fa-clock"></i> <?php echo date('d/m/Y H:i', strtotime($respuesta['fecha_creacion'])); ?>
                                        </small>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="text-center py-4">
                                <i class="fas fa-reply fa-3x text-muted mb-3"></i>
                                <h5>No has respondido en ningún hilo</h5>
                                <p class="text-muted">¡Participa en las discusiones del foro!</p>
                                <a href="index.php" class="btn btn-primary">Explorar Hilos</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Pestaña de Configuración -->
            <div class="tab-pane fade" id="configuracion">
                <div class="row">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-user-cog"></i> Configuración del Perfil</h5>
                            </div>
                            <div class="card-body">
                                <form>
                                    <div class="mb-3">
                                        <label for="username" class="form-label">Nombre de Usuario</label>
                                        <input type="text" class="form-control" id="username" value="<?php echo htmlspecialchars($usuario['username']); ?>" disabled>
                                    </div>
                                    <div class="mb-3">
                                        <label for="email" class="form-label">Email</label>
                                        <input type="email" class="form-control" id="email" value="<?php echo htmlspecialchars($usuario['email']); ?>" disabled>
                                    </div>
                                    <div class="mb-3">
                                        <label for="bio" class="form-label">Biografía</label>
                                        <textarea class="form-control" id="bio" rows="3" placeholder="Cuéntanos algo sobre ti..."></textarea>
                                    </div>
                                    <button type="submit" class="btn btn-primary" disabled>Actualizar Perfil (Próximamente)</button>
                                </form>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-bell"></i> Preferencias</h5>
                            </div>
                            <div class="card-body">
                                <div class="form-check form-switch mb-3">
                                    <input class="form-check-input" type="checkbox" id="notifications" checked disabled>
                                    <label class="form-check-label" for="notifications">Recibir notificaciones por email</label>
                                </div>
                                <div class="form-check form-switch mb-3">
                                    <input class="form-check-input" type="checkbox" id="newsletter" checked disabled>
                                    <label class="form-check-label" for="newsletter">Recibir newsletter</label>
                                </div>
                                <div class="form-check form-switch mb-3">
                                    <input class="form-check-input" type="checkbox" id="publicProfile" checked disabled>
                                    <label class="form-check-label" for="publicProfile">Perfil público</label>
                                </div>
                                
                                <div class="alert alert-info">
                                    <small>
                                        <i class="fas fa-info-circle"></i> 
                                        La configuración avanzada estará disponible próximamente.
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Activar pestañas
        var triggerTabList = [].slice.call(document.querySelectorAll('#profileTabs a'))
        triggerTabList.forEach(function (triggerEl) {
            var tabTrigger = new bootstrap.Tab(triggerEl)
            triggerEl.addEventListener('click', function (event) {
                event.preventDefault()
                tabTrigger.show()
            })
        });
    </script>
</body>
</html>