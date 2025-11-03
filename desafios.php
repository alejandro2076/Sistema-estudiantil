<?php
include_once 'includes/auth.php';
include_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();

// Manejo seguro de consultas: envolver en try/catch para evitar errores fatales si faltan tablas
$desafios = [];
$estadisticas = ['total_soluciones' => 0];
$pageError = null;

try {
    // Obtener desafíos con información de progreso del usuario
    $query = "SELECT d.*, 
              c.nombre as categoria_nombre,
              (SELECT COUNT(*) FROM soluciones_desafio s WHERE s.desafio_id = d.id AND s.usuario_id = ?) as intentos_usuario,
              (SELECT es_correcta FROM soluciones_desafio s WHERE s.desafio_id = d.id AND s.usuario_id = ? ORDER BY fecha_envio DESC LIMIT 1) as completado
              FROM desafios d 
              JOIN categorias c ON d.categoria_id = c.id 
              ORDER BY d.dificultad, d.fecha_creacion DESC";

    $stmt = $db->prepare($query);
    $user_id = isLoggedIn() ? getUserID() : 0;
    $stmt->execute([$user_id, $user_id]);
    $desafios = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Estadísticas generales
    $query = "SELECT 
              COUNT(DISTINCT s.usuario_id) as usuarios_activos,
              COUNT(*) as total_soluciones,
              AVG(tiempo_ejecucion) as tiempo_promedio
              FROM soluciones_desafio s 
              WHERE s.es_correcta = TRUE";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $estadisticas = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("[desafios.php] PDOException: " . $e->getMessage());
    $pageError = "Error de base de datos: por favor verifica la estructura de la base de datos (tablas faltantes o permisos). Detalle registrado en el log del servidor.";
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Desafíos de Programación - Foro Comunitario</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .desafio-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            margin-bottom: 1.5rem;
        }
        
        .desafio-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 15px rgba(0,0,0,0.2);
        }
        
        .dificultad-facil { border-left: 5px solid #28a745; }
        .dificultad-medio { border-left: 5px solid #ffc107; }
        .dificultad-dificil { border-left: 5px solid #dc3545; }
        
        .completado-badge {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
        }
        
        .guest-badge {
            background: linear-gradient(135deg, #6c757d, #495057);
            color: white;
        }
        
        .attempts-badge {
            background: #17a2b8;
            color: white;
            font-size: 0.75rem;
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>
    <?php if (!empty($pageError)): ?>
        <div class="container mt-3">
            <div class="alert alert-danger" role="alert">
                <?php echo htmlspecialchars($pageError); ?>
            </div>
        </div>
    <?php endif; ?>
    
    <div class="container mt-4">
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1><i class="fas fa-code me-2"></i>Desafíos de Programación</h1>
                    <div class="stat-card bg-primary text-white p-3 rounded">
                        <h4 class="mb-0"><?php echo $estadisticas['total_soluciones'] ?? 0; ?></h4>
                        <small>Soluciones enviadas</small>
                    </div>
                </div>

                <!-- Banner para usuarios no registrados -->
                <?php if (!isLoggedIn()): ?>
                <div class="alert alert-info d-flex align-items-center">
                    <div class="me-3">
                        <i class="fas fa-info-circle fa-2x"></i>
                    </div>
                    <div>
                        <h5 class="alert-heading mb-2">¡Prueba los desafíos gratis!</h5>
                        <p class="mb-2">Tienes <strong>3 intentos gratuitos</strong> por desafío sin necesidad de registro.</p>
                        <div class="d-flex gap-2">
                            <a href="register.php" class="btn btn-primary btn-sm">
                                <i class="fas fa-user-plus"></i> Regístrate para intentos ilimitados
                            </a>
                            <a href="login.php" class="btn btn-outline-primary btn-sm">
                                <i class="fas fa-sign-in-alt"></i> Iniciar Sesión
                            </a>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Filtros -->
                <div class="card mb-4">
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Dificultad</label>
                                <select class="form-select" id="filtroDificultad">
                                    <option value="todas">Todas</option>
                                    <option value="facil">Fácil</option>
                                    <option value="medio">Medio</option>
                                    <option value="dificil">Difícil</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Estado</label>
                                <select class="form-select" id="filtroEstado">
                                    <option value="todos">Todos</option>
                                    <option value="completados">Completados</option>
                                    <option value="pendientes">Pendientes</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Ordenar por</label>
                                <select class="form-select" id="filtroOrden">
                                    <option value="dificultad">Dificultad</option>
                                    <option value="puntos">Puntos</option>
                                    <option value="recientes">Más recientes</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Lista de Desafíos -->
                <div class="row" id="listaDesafios">
                    <?php foreach($desafios as $desafio): ?>
                        <div class="col-md-6 col-lg-4" data-dificultad="<?php echo $desafio['dificultad']; ?>" 
                             data-estado="<?php echo $desafio['completado'] ? 'completado' : 'pendiente'; ?>">
                            <div class="card desafio-card dificultad-<?php echo $desafio['dificultad']; ?>">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <h5 class="card-title"><?php echo htmlspecialchars($desafio['titulo']); ?></h5>
                                        <div class="d-flex flex-column align-items-end">
                                            <?php if($desafio['completado']): ?>
                                                <span class="badge completado-badge mb-1">
                                                    <i class="fas fa-check"></i> Completado
                                                </span>
                                            <?php elseif(!isLoggedIn()): ?>
                                                <span class="badge guest-badge mb-1">
                                                    <i class="fas fa-user-clock"></i> 3 Intentos
                                                </span>
                                            <?php endif; ?>
                                            <?php if($desafio['intentos_usuario'] > 0 && !$desafio['completado']): ?>
                                                <span class="badge attempts-badge">
                                                    <?php echo $desafio['intentos_usuario']; ?> intento(s)
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    
                                    <p class="card-text text-muted small">
                                        <?php echo htmlspecialchars(substr($desafio['descripcion'], 0, 100)); ?>...
                                    </p>
                                    
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="badge bg-secondary">
                                            <?php echo htmlspecialchars($desafio['categoria_nombre']); ?>
                                        </span>
                                        <span class="badge bg-warning text-dark">
                                            <?php echo $desafio['puntos']; ?> pts
                                        </span>
                                    </div>
                                    
                                    <div class="mt-3">
                                        <small class="text-muted">
                                            <i class="fas fa-signal"></i>
                                            <?php echo ucfirst($desafio['dificultad']); ?>
                                        </small>
                                    </div>
                                    
                                    <div class="mt-3">
                                        <?php if(isLoggedIn()): ?>
                                            <a href="desafio.php?id=<?php echo $desafio['id']; ?>" 
                                               class="btn btn-primary btn-sm w-100">
                                                <?php echo $desafio['completado'] ? 'Ver Solución' : 'Resolver Desafío'; ?>
                                            </a>
                                        <?php else: ?>
                                            <a href="desafio.php?id=<?php echo $desafio['id']; ?>" 
                                               class="btn btn-outline-primary btn-sm w-100">
                                                Probar Gratis (3 intentos)
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Sección de ayuda -->
                <div class="card mt-4">
                    <div class="card-body text-center">
                        <h5><i class="fas fa-robot text-primary me-2"></i>¿Necesitas ayuda con tu código?</h5>
                        <p class="text-muted mb-3">Usa nuestro asistente IA para revisar y mejorar tu código</p>
                        <a href="ayuda_ia.php" class="btn btn-outline-primary">
                            <i class="fas fa-magic me-2"></i>Ir al Asistente IA
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Filtros en tiempo real
        document.addEventListener('DOMContentLoaded', function() {
            const filtros = ['filtroDificultad', 'filtroEstado', 'filtroOrden'];
            
            filtros.forEach(filtroId => {
                document.getElementById(filtroId).addEventListener('change', filtrarDesafios);
            });
            
            function filtrarDesafios() {
                const dificultad = document.getElementById('filtroDificultad').value;
                const estado = document.getElementById('filtroEstado').value;
                const desafios = document.querySelectorAll('#listaDesafios > div');
                
                desafios.forEach(desafio => {
                    const showDificultad = dificultad === 'todas' || desafio.dataset.dificultad === dificultad;
                    const showEstado = estado === 'todos' || desafio.dataset.estado === estado;
                    
                    desafio.style.display = (showDificultad && showEstado) ? 'block' : 'none';
                });
            }
        });
    </script>
</body>
</html>