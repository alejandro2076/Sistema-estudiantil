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

$hilo_id = $_GET['id'] ?? 0;
$user_id = $_SESSION['user_id'];

// Verificar que el hilo pertenezca al usuario
$query = "SELECT h.*, c.nombre as categoria_nombre 
          FROM hilos h 
          JOIN categorias c ON h.categoria_id = c.id 
          WHERE h.id = ? AND h.usuario_id = ?";
$stmt = $db->prepare($query);
$stmt->execute([$hilo_id, $user_id]);
$hilo = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$hilo) {
    header("Location: mis_hilos.php");
    exit;
}

// Obtener categorías para el dropdown
$query = "SELECT * FROM categorias ORDER BY nombre";
$stmt = $db->prepare($query);
$stmt->execute();
$categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Procesar edición del hilo
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $titulo = $_POST['titulo'] ?? '';
    $contenido = $_POST['contenido'] ?? '';
    $categoria_id = $_POST['categoria_id'] ?? '';
    
    if (!empty($titulo) && !empty($contenido) && !empty($categoria_id)) {
        $query = "UPDATE hilos SET titulo = ?, contenido = ?, categoria_id = ?, fecha_actualizacion = NOW() WHERE id = ?";
        $stmt = $db->prepare($query);
        
        if ($stmt->execute([$titulo, $contenido, $categoria_id, $hilo_id])) {
            $_SESSION['success_message'] = "Hilo actualizado correctamente";
            header("Location: mis_hilos.php");
            exit;
        } else {
            $error_message = "Error al actualizar el hilo";
        }
    } else {
        $error_message = "Todos los campos son obligatorios";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Hilo - Foro Comunitario</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <?php include 'navbar.php'; ?>
    
    <div class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0">
                            <i class="fas fa-edit me-2"></i>Editar Hilo
                        </h4>
                    </div>
                    <div class="card-body">
                        <?php if (isset($error_message)): ?>
                            <div class="alert alert-danger"><?php echo $error_message; ?></div>
                        <?php endif; ?>
                        
                        <form method="POST">
                            <div class="mb-3">
                                <label for="titulo" class="form-label">Título del Hilo</label>
                                <input type="text" class="form-control" id="titulo" name="titulo" 
                                       value="<?php echo htmlspecialchars($hilo['titulo']); ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="categoria_id" class="form-label">Categoría</label>
                                <select class="form-select" id="categoria_id" name="categoria_id" required>
                                    <option value="">Selecciona una categoría</option>
                                    <?php foreach($categorias as $categoria): ?>
                                        <option value="<?php echo $categoria['id']; ?>" 
                                            <?php echo $categoria['id'] == $hilo['categoria_id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($categoria['nombre']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label for="contenido" class="form-label">Contenido</label>
                                <textarea class="form-control" id="contenido" name="contenido" rows="8" required><?php echo htmlspecialchars($hilo['contenido']); ?></textarea>
                            </div>
                            
                            <div class="d-flex justify-content-between">
                                <a href="mis_hilos.php" class="btn btn-secondary">
                                    <i class="fas fa-arrow-left me-2"></i>Cancelar
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>Actualizar Hilo
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>