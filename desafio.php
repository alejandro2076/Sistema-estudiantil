<?php
include_once 'includes/auth.php';
include_once 'config/database.php';

// Iniciar sesión para usuarios no registrados también
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$desafio_id = $_GET['id'] ?? 0;

$database = new Database();
$db = $database->getConnection();

// Obtener información del desafío
$query = "SELECT d.*, c.nombre as categoria_nombre 
          FROM desafios d 
          JOIN categorias c ON d.categoria_id = c.id 
          WHERE d.id = ?";
$stmt = $db->prepare($query);
$stmt->execute([$desafio_id]);
$desafio = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$desafio) {
    header("Location: desafios.php");
    exit();
}

// SISTEMA DE INTENTOS PARA USUARIOS NO REGISTRADOS
$intentos_key = 'desafio_intentos_' . $desafio_id;
$max_intentos = 3;

// Inicializar contador de intentos si no existe
if (!isLoggedIn() && !isset($_SESSION[$intentos_key])) {
    $_SESSION[$intentos_key] = $max_intentos;
}

// Verificar si usuario no registrado ha excedido los intentos
if (!isLoggedIn() && $_SESSION[$intentos_key] <= 0) {
    header("Location: register.php?motivo=limite_intentos&desafio=" . $desafio_id);
    exit();
}

$intentos_restantes = isLoggedIn() ? 'ilimitados' : $_SESSION[$intentos_key];

// Verificar si ya fue completado (solo para usuarios registrados)
$solucion_existente = null;
if (isLoggedIn()) {
    $query = "SELECT * FROM soluciones_desafio 
              WHERE usuario_id = ? AND desafio_id = ? AND es_correcta = TRUE 
              ORDER BY fecha_envio DESC LIMIT 1";
    $stmt = $db->prepare($query);
    $stmt->execute([$_SESSION['user_id'], $desafio_id]);
    $solucion_existente = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Procesar solución
$mensaje = '';
$tipo_mensaje = '';

if ($_POST && isset($_POST['codigo_solucion'])) {
    $codigo_solucion = $_POST['codigo_solucion'];
    
    // Para usuarios no registrados: restar intento ANTES de validar
    if (!isLoggedIn()) {
        $_SESSION[$intentos_key]--;
        $intentos_restantes = $_SESSION[$intentos_key];
    }
    
    // Validar la solución
    $es_correcta = validarSolucion($codigo_solucion, $desafio_id, $db);
    $tiempo_ejecucion = mt_rand(100, 1000) / 1000;
    $puntos_obtenidos = $es_correcta ? $desafio['puntos'] : 0;
    
    if (isLoggedIn()) {
        // Guardar solución para usuarios registrados
        $query = "INSERT INTO soluciones_desafio 
                  (usuario_id, desafio_id, codigo_solucion, tiempo_ejecucion, puntos_obtenidos, es_correcta) 
                  VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $db->prepare($query);
        $stmt->execute([
            $_SESSION['user_id'], 
            $desafio_id, 
            $codigo_solucion, 
            $tiempo_ejecucion, 
            $puntos_obtenidos, 
            $es_correcta
        ]);
        
        if ($es_correcta) {
            actualizarRankingUsuario($_SESSION['user_id'], $db);
            
            // Recargar información de solución existente
            $query = "SELECT * FROM soluciones_desafio 
                      WHERE usuario_id = ? AND desafio_id = ? AND es_correcta = TRUE 
                      ORDER BY fecha_envio DESC LIMIT 1";
            $stmt = $db->prepare($query);
            $stmt->execute([$_SESSION['user_id'], $desafio_id]);
            $solucion_existente = $stmt->fetch(PDO::FETCH_ASSOC);
        }
    }
    
    if ($es_correcta) {
        $mensaje = "¡Desafío completado correctamente!";
        if (isLoggedIn()) {
            $mensaje .= " Ganaste {$desafio['puntos']} puntos.";
        }
        $tipo_mensaje = 'success';
    } else {
        $mensaje = "La solución no es correcta. Revisa tu código e inténtalo de nuevo.";
        if (!isLoggedIn()) {
            $mensaje .= " Te quedan {$intentos_restantes} intento(s).";
        }
        $tipo_mensaje = 'danger';
    }
    
    // Redirigir si usuario no registrado se quedó sin intentos
    if (!isLoggedIn() && $_SESSION[$intentos_key] <= 0) {
        $_SESSION['limite_alcanzado'] = true;
    }
}

function validarSolucion($codigo, $desafio_id, $db) {
    // Esta es una validación básica - deberías implementar un sistema más robusto
    // Por ahora, simulamos validación basada en longitud y contenido
    
    $codigo = trim($codigo);
    
    // Obtener casos de prueba
    $query = "SELECT * FROM casos_prueba WHERE desafio_id = ? AND es_visible = TRUE";
    $stmt = $db->prepare($query);
    $stmt->execute([$desafio_id]);
    $casos_prueba = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Validaciones básicas según el desafío
    switch($desafio_id) {
        case 1: // Hola Mundo
            return stripos($codigo, 'Hola') !== false || stripos($codigo, 'Hello') !== false;
        case 2: // Suma
            return stripos($codigo, '+') !== false || stripos($codigo, 'sum') !== false;
        case 3: // Factorial
            return strlen($codigo) > 50; // Código más complejo
        default:
            return strlen($codigo) > 20; // Validación genérica
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resolver Desafío - <?php echo htmlspecialchars($desafio['titulo']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .editor-container {
            border: 1px solid #ddd;
            border-radius: 5px;
            overflow: hidden;
        }
        .editor-header {
            background: #f8f9fa;
            padding: 10px;
            border-bottom: 1px solid #ddd;
        }
        .code-editor {
            width: 100%;
            min-height: 300px;
            font-family: 'Courier New', monospace;
            border: none;
            padding: 15px;
            resize: vertical;
        }
        .attempts-badge {
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>
    
    <div class="container mt-4">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="desafios.php">Desafíos</a></li>
                <li class="breadcrumb-item active"><?php echo htmlspecialchars($desafio['titulo']); ?></li>
            </ol>
        </nav>

        <?php if($mensaje): ?>
            <div class="alert alert-<?php echo $tipo_mensaje; ?> alert-dismissible fade show">
                <?php echo $mensaje; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if(!isLoggedIn() && isset($_SESSION['limite_alcanzado']) && $_SESSION['limite_alcanzado']): ?>
            <div class="alert alert-warning">
                <h5><i class="fas fa-exclamation-triangle"></i> ¡Límite de intentos alcanzado!</h5>
                <p>Has usado tus 3 intentos gratuitos. Regístrate para tener intentos ilimitados y acceso a todas las funciones.</p>
                <div class="d-flex gap-2">
                    <a href="register.php?motivo=limite_intentos&desafio=<?php echo $desafio_id; ?>" 
                       class="btn btn-primary">
                        <i class="fas fa-user-plus"></i> Regístrate Gratis
                    </a>
                    <a href="desafios.php" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left"></i> Volver a Desafíos
                    </a>
                </div>
            </div>
            <?php unset($_SESSION['limite_alcanzado']); ?>
        <?php endif; ?>

        <div class="row">
            <!-- Descripción del Desafío -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h4 class="mb-0"><?php echo htmlspecialchars($desafio['titulo']); ?></h4>
                        <div>
                            <span class="badge bg-<?php echo $desafio['dificultad'] == 'facil' ? 'success' : ($desafio['dificultad'] == 'medio' ? 'warning' : 'danger'); ?>">
                                <?php echo ucfirst($desafio['dificultad']); ?>
                            </span>
                            <span class="badge bg-primary"><?php echo $desafio['puntos']; ?> pts</span>
                        </div>
                    </div>
                    <div class="card-body">
                        <p class="card-text"><?php echo nl2br(htmlspecialchars($desafio['descripcion'])); ?></p>
                        
                        <div class="mt-3">
                            <small class="text-muted">
                                <i class="fas fa-folder"></i> Categoría: <?php echo htmlspecialchars($desafio['categoria_nombre']); ?>
                            </small>
                        </div>

                        <?php if($solucion_existente): ?>
                            <div class="alert alert-success mt-3">
                                <i class="fas fa-check-circle"></i> 
                                <strong>¡Completado!</strong> Resolviste este desafío y ganaste <?php echo $solucion_existente['puntos_obtenidos']; ?> puntos.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Casos de prueba visibles -->
                <div class="card mt-3">
                    <div class="card-header">
                        <h6 class="mb-0">Casos de Prueba</h6>
                    </div>
                    <div class="card-body">
                        <?php
                        $query = "SELECT * FROM casos_prueba WHERE desafio_id = ? AND es_visible = TRUE";
                        $stmt = $db->prepare($query);
                        $stmt->execute([$desafio_id]);
                        $casos_visibles = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        
                        foreach($casos_visibles as $caso): ?>
                            <div class="mb-2">
                                <small><strong>Entrada:</strong> <code><?php echo htmlspecialchars($caso['entrada'] ?: '(ninguna)'); ?></code></small><br>
                                <small><strong>Salida esperada:</strong> <code><?php echo htmlspecialchars($caso['salida_esperada']); ?></code></small>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Editor de Código -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Tu Solución</h5>
                        <?php if (!isLoggedIn()): ?>
                            <span class="badge bg-warning text-dark attempts-badge">
                                <i class="fas fa-exclamation-triangle"></i>
                                Intentos: <?php echo $intentos_restantes; ?>/<?php echo $max_intentos; ?>
                            </span>
                        <?php else: ?>
                            <span class="badge bg-success attempts-badge">
                                <i class="fas fa-infinity"></i>
                                Intentos ilimitados
                            </span>
                        <?php endif; ?>
                    </div>
                    <div class="card-body">
                        <?php if(!$solucion_existente): ?>
                            <?php if (!isLoggedIn() && $_SESSION[$intentos_key] <= 0): ?>
                                <div class="alert alert-warning text-center">
                                    <h5><i class="fas fa-exclamation-triangle"></i> ¡Límite de intentos alcanzado!</h5>
                                    <p>Has usado tus 3 intentos gratuitos. Regístrate para tener intentos ilimitados.</p>
                                    <a href="register.php?motivo=limite_intentos&desafio=<?php echo $desafio_id; ?>" 
                                       class="btn btn-primary btn-lg">
                                        <i class="fas fa-user-plus"></i> Regístrate Gratis
                                    </a>
                                    <div class="mt-2">
                                        <small class="text-muted">¿Ya tienes cuenta? <a href="login.php">Inicia sesión</a></small>
                                    </div>
                                </div>
                            <?php else: ?>
                                <form method="POST">
                                    <div class="editor-container mb-3">
                                        <div class="editor-header">
                                            <small><i class="fab fa-php"></i> PHP</small>
                                        </div>
                                        <textarea class="code-editor" name="codigo_solucion" 
                                                  placeholder="Escribe tu código aquí..." 
                                                  required><?php echo htmlspecialchars($desafio['codigo_base'] ?? '<?php'); ?></textarea>
                                    </div>
                                    
                                    <?php if (!isLoggedIn()): ?>
                                        <div class="alert alert-info">
                                            <small>
                                                <i class="fas fa-info-circle"></i> 
                                                <strong>Modo gratuito:</strong> Tienes <?php echo $intentos_restantes; ?> intento(s) restante(s). 
                                                <a href="register.php?motivo=limite_intentos&desafio=<?php echo $desafio_id; ?>" class="alert-link">
                                                    Regístrate para intentos ilimitados
                                                </a>
                                            </small>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="d-flex justify-content-between align-items-center">
                                        <a href="ayuda_ia.php" class="btn btn-outline-info btn-sm" target="_blank">
                                            <i class="fas fa-robot"></i> Ayuda IA
                                        </a>
                                        <button type="submit" class="btn btn-success">
                                            <i class="fas fa-paper-plane"></i> Enviar Solución
                                        </button>
                                    </div>
                                </form>
                            <?php endif; ?>
                        <?php else: ?>
                            <div class="alert alert-info">
                                <h6>Tu solución:</h6>
                                <pre class="bg-light p-3"><code><?php echo htmlspecialchars($solucion_existente['codigo_solucion']); ?></code></pre>
                                <div class="mt-2">
                                    <small class="text-muted">
                                        Tiempo: <?php echo number_format($solucion_existente['tiempo_ejecucion'], 4); ?>s | 
                                        Puntos: <?php echo $solucion_existente['puntos_obtenidos']; ?>
                                    </small>
                                </div>
                            </div>
                            <div class="d-flex gap-2">
                                <a href="desafios.php" class="btn btn-primary">Volver a Desafíos</a>
                                <a href="ranking.php" class="btn btn-outline-success">Ver Ranking</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Información adicional para no registrados -->
                <?php if (!isLoggedIn() && $_SESSION[$intentos_key] > 0): ?>
                    <div class="card mt-3">
                        <div class="card-body text-center">
                            <h6><i class="fas fa-crown text-warning"></i> ¿Quieres más intentos?</h6>
                            <p class="small mb-2">Regístrate y obtén acceso ilimitado a todos los desafíos</p>
                            <a href="register.php?motivo=limite_intentos&desafio=<?php echo $desafio_id; ?>" 
                               class="btn btn-warning btn-sm">
                                <i class="fas fa-user-plus"></i> Regístrate Gratis
                            </a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>