<?php
include 'includes/auth.php';
include 'config/database.php';

$database = new Database();
$db = $database->getConnection();

$hilo_id = $_GET['id'] ?? 0;

// Obtener información del hilo
$query = "SELECT h.*, u.username as autor, c.nombre as categoria 
          FROM hilos h 
          JOIN usuarios u ON h.usuario_id = u.id 
          JOIN categorias c ON h.categoria_id = c.id 
          WHERE h.id = ?";
$stmt = $db->prepare($query);
$stmt->execute([$hilo_id]);
$hilo = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$hilo){
    header("Location: index.php");
    exit;
}

// Obtener respuestas
$query = "SELECT r.*, u.username as autor 
          FROM respuestas r 
          JOIN usuarios u ON r.usuario_id = u.id 
          WHERE r.hilo_id = ? 
          ORDER BY r.fecha_creacion ASC";
$stmt = $db->prepare($query);
$stmt->execute([$hilo_id]);
$respuestas = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Procesar nueva respuesta
if($_POST && isLoggedIn()){
    $contenido = $_POST['contenido'];
    $usuario_id = getUserID();
    
    $query = "INSERT INTO respuestas (contenido, usuario_id, hilo_id) VALUES (?, ?, ?)";
    $stmt = $db->prepare($query);
    
    if($stmt->execute([$contenido, $usuario_id, $hilo_id])){
        // Actualizar última respuesta del hilo
        $query = "UPDATE hilos SET ultima_respuesta = NOW() WHERE id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$hilo_id]);
        
        header("Location: hilo.php?id=" . $hilo_id);
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($hilo['titulo']); ?> - Foro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <?php include 'navbar.php'; ?>
    
    <div class="container mt-4">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php">Foro</a></li>
                <li class="breadcrumb-item"><a href="categoria.php?id=<?php echo $hilo['categoria_id']; ?>">
                    <?php echo htmlspecialchars($hilo['categoria']); ?>
                </a></li>
                <li class="breadcrumb-item active"><?php echo htmlspecialchars($hilo['titulo']); ?></li>
            </ol>
        </nav>

        <!-- Hilo principal -->
        <div class="card mb-4">
            <div class="card-header">
                <h4><?php echo htmlspecialchars($hilo['titulo']); ?></h4>
                <small class="text-muted">
                    Por <?php echo htmlspecialchars($hilo['autor']); ?> 
                    el <?php echo date('d/m/Y H:i', strtotime($hilo['fecha_creacion'])); ?>
                </small>
            </div>
            <div class="card-body">
                <p class="card-text"><?php echo nl2br(htmlspecialchars($hilo['contenido'])); ?></p>
            </div>
        </div>

        <!-- Respuestas -->
        <h5>Respuestas (<?php echo count($respuestas); ?>)</h5>
        
        <?php foreach($respuestas as $respuesta): ?>
            <div class="card mb-3">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-2 text-center">
                            <strong><?php echo htmlspecialchars($respuesta['autor']); ?></strong>
                            <br>
                            <small class="text-muted">
                                <?php echo date('d/m/Y H:i', strtotime($respuesta['fecha_creacion'])); ?>
                            </small>
                        </div>
                        <div class="col-md-10">
                            <p class="card-text"><?php echo nl2br(htmlspecialchars($respuesta['contenido'])); ?></p>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>

        <!-- Formulario de respuesta -->
        <?php if(isLoggedIn()): ?>
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Responder</h5>
                    <form method="POST">
                        <div class="mb-3">
                            <textarea class="form-control" id="contenido" name="contenido" rows="4" required 
                                      placeholder="Escribe tu respuesta aquí..."></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">Enviar Respuesta</button>
                    </form>
                </div>
            </div>
        <?php else: ?>
            <div class="alert alert-info">
                <a href="login.php">Inicia sesión</a> para poder responder en este hilo.
            </div>
        <?php endif; ?>
    </div>
</body>
</html>