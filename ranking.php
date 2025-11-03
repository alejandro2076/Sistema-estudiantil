<?php
include_once 'includes/auth.php';
include_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();

// Manejo seguro de consultas: envolver en try/catch para evitar errores fatales si faltan tablas
$ranking_global = [];
$user_stats = null;
$pageError = null;

try {
    // Obtener ranking global
    $query = "SELECT u.username, 
              COUNT(DISTINCT s.desafio_id) as desafios_completados,
              COALESCE(SUM(s.puntos_obtenidos), 0) as puntos_totales,
              COALESCE(AVG(s.tiempo_ejecucion), 0.1) as tiempo_promedio,
              (COALESCE(SUM(s.puntos_obtenidos), 0) / COALESCE(AVG(s.tiempo_ejecucion), 0.1)) as eficiencia
              FROM usuarios u 
              LEFT JOIN soluciones_desafio s ON u.id = s.usuario_id AND s.es_correcta = TRUE 
              GROUP BY u.id 
              HAVING puntos_totales > 0
              ORDER BY eficiencia DESC, puntos_totales DESC 
              LIMIT 50";

    $stmt = $db->prepare($query);
    $stmt->execute();
    $ranking_global = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Estadísticas del usuario actual
    if (isLoggedIn()) {
        $query = "SELECT 
                  COUNT(DISTINCT s.desafio_id) as desafios_completados,
                  COALESCE(SUM(s.puntos_obtenidos), 0) as puntos_totales,
                  COALESCE(AVG(s.tiempo_ejecucion), 0.1) as tiempo_promedio,
                  (COALESCE(SUM(s.puntos_obtenidos), 0) / COALESCE(AVG(s.tiempo_ejecucion), 0.1)) as eficiencia
                  FROM soluciones_desafio s 
                  WHERE s.usuario_id = ? AND s.es_correcta = TRUE";
        $stmt = $db->prepare($query);
        $stmt->execute([getUserID()]);
        $user_stats = $stmt->fetch(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    error_log("[ranking.php] PDOException: " . $e->getMessage());
    $pageError = "Error de base de datos: por favor verifica la estructura de la base de datos (tablas faltantes o permisos). Detalle registrado en el log del servidor.";
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ranking - Foro Comunitario</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .ranking-table tr:hover {
            background-color: #f8f9fa;
        }
        .user-highlight {
            background-color: #e3f2fd !important;
            font-weight: bold;
        }
        .medal-gold { color: #ffd700; }
        .medal-silver { color: #c0c0c0; }
        .medal-bronze { color: #cd7f32; }
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
                <h1 class="mb-4"><i class="fas fa-trophy text-warning"></i> Ranking de Programadores</h1>
                
                <!-- Información para usuarios no registrados -->
                <?php if (!isLoggedIn()): ?>
                <div class="alert alert-info mb-4">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-info-circle fa-2x me-3"></i>
                        <div>
                            <h5 class="alert-heading mb-2">¿Quieres aparecer en el ranking?</h5>
                            <p class="mb-2">Regístrate y comienza a resolver desafíos para acumular puntos y mejorar tu posición.</p>
                            <div class="d-flex gap-2">
                                <a href="register.php" class="btn btn-primary btn-sm">
                                    <i class="fas fa-user-plus"></i> Regístrate
                                </a>
                                <a href="desafios.php" class="btn btn-outline-primary btn-sm">
                                    <i class="fas fa-code"></i> Ver Desafíos
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if(isLoggedIn() && $user_stats): ?>
                <!-- Estadísticas del usuario -->
                <div class="card mb-4 border-primary">
                    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Tu Posición en el Ranking</h5>
                        <span class="badge bg-light text-primary">Usuario Registrado</span>
                    </div>
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-md-3">
                                <h3 class="text-primary"><?php echo $user_stats['desafios_completados']; ?></h3>
                                <small class="text-muted">Desafíos Completados</small>
                            </div>
                            <div class="col-md-3">
                                <h3 class="text-success"><?php echo $user_stats['puntos_totales'] ?? 0; ?></h3>
                                <small class="text-muted">Puntos Totales</small>
                            </div>
                            <div class="col-md-3">
                                <h3 class="text-info"><?php echo number_format($user_stats['tiempo_promedio'] ?? 0, 4); ?>s</h3>
                                <small class="text-muted">Tiempo Promedio</small>
                            </div>
                            <div class="col-md-3">
                                <h3 class="text-warning"><?php echo number_format($user_stats['eficiencia'] ?? 0, 2); ?></h3>
                                <small class="text-muted">Eficiencia</small>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Ranking Global -->
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Top 50 Programadores</h5>
                        <span class="badge bg-secondary"><?php echo count($ranking_global); ?> programadores</span>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0 ranking-table">
                                <thead class="table-light">
                                    <tr>
                                        <th width="60">#</th>
                                        <th>Usuario</th>
                                        <th>Desafíos</th>
                                        <th>Puntos</th>
                                        <th>Tiempo Prom.</th>
                                        <th>Eficiencia</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (count($ranking_global) > 0): ?>
                                        <?php foreach($ranking_global as $index => $usuario): ?>
                                        <tr class="<?php echo isLoggedIn() && $usuario['username'] == $_SESSION['username'] ? 'user-highlight' : ''; ?>">
                                            <td>
                                                <?php if($index < 3): ?>
                                                    <span class="medal-<?php echo $index == 0 ? 'gold' : ($index == 1 ? 'silver' : 'bronze'); ?>">
                                                        <i class="fas fa-medal fa-lg"></i>
                                                    </span>
                                                <?php else: ?>
                                                    <strong><?php echo $index + 1; ?></strong>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($usuario['username']); ?></strong>
                                                <?php if(isLoggedIn() && $usuario['username'] == $_SESSION['username']): ?>
                                                    <span class="badge bg-primary ms-1">Tú</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo $usuario['desafios_completados']; ?></td>
                                            <td class="fw-bold text-success"><?php echo $usuario['puntos_totales']; ?></td>
                                            <td class="<?php echo $usuario['tiempo_promedio'] < 0.2 ? 'text-success' : ($usuario['tiempo_promedio'] < 0.5 ? 'text-warning' : 'text-danger'); ?>">
                                                <?php echo number_format($usuario['tiempo_promedio'], 4); ?>s
                                            </td>
                                            <td class="fw-bold text-warning">
                                                <?php echo number_format($usuario['eficiencia'], 2); ?>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="6" class="text-center py-4">
                                                <i class="fas fa-trophy fa-3x text-muted mb-3"></i>
                                                <h5 class="text-muted">No hay participantes aún</h5>
                                                <p class="text-muted">Sé el primero en resolver desafíos y aparecer en el ranking</p>
                                                <a href="desafios.php" class="btn btn-primary">
                                                    <i class="fas fa-code me-2"></i>Ir a Desafíos
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Leyenda y información -->
                <div class="row mt-4">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-body">
                                <h6><i class="fas fa-info-circle text-primary me-2"></i>¿Cómo funciona el ranking?</h6>
                                <ul class="small text-muted mb-0">
                                    <li>La <strong>eficiencia</strong> se calcula como (Puntos Totales / Tiempo Promedio)</li>
                                    <li>Un valor más alto indica mejor rendimiento</li>
                                    <li>Los puntos se obtienen resolviendo desafíos correctamente</li>
                                    <li>Solo usuarios registrados pueden aparecer en el ranking</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-body text-center">
                                <h6><i class="fas fa-rocket text-success me-2"></i>¿Quieres mejorar tu posición?</h6>
                                <p class="small text-muted mb-3">Practica con los desafíos y mejora tus habilidades</p>
                                <div class="d-flex gap-2 justify-content-center">
                                    <a href="desafios.php" class="btn btn-success btn-sm">
                                        <i class="fas fa-code"></i> Desafíos
                                    </a>
                                    <a href="ayuda_ia.php" class="btn btn-outline-info btn-sm">
                                        <i class="fas fa-robot"></i> Ayuda IA
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>