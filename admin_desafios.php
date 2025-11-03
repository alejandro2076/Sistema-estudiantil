<?php
include_once 'includes/auth.php';
include_once 'config/database.php';

// Verificar si es administrador (deberías tener una columna 'rol' en la tabla usuarios)
if (!isLoggedIn() || $_SESSION['rol'] != 'admin') {
    header("Location: index.php");
    exit;
}

$database = new Database();
$db = $database->getConnection();

// Procesar creación de nuevo desafío
if ($_POST && isset($_POST['crear_desafio'])) {
    $titulo = $_POST['titulo'];
    $descripcion = $_POST['descripcion'];
    $codigo_base = $_POST['codigo_base'];
    $categoria_id = $_POST['categoria_id'];
    $dificultad = $_POST['dificultad'];
    $puntos = $_POST['puntos'];
    
    $query = "INSERT INTO desafios (titulo, descripcion, codigo_base, categoria_id, dificultad, puntos) 
              VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $db->prepare($query);
    $stmt->execute([$titulo, $descripcion, $codigo_base, $categoria_id, $dificultad, $puntos]);
    
    $_SESSION['success_message'] = "Desafío creado exitosamente";
    header("Location: admin_desafios.php");
    exit;
}

// Obtener categorías para el formulario
$query = "SELECT * FROM categorias";
$stmt = $db->prepare($query);
$stmt->execute();
$categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener lista de desafíos existentes
$query = "SELECT d.*, c.nombre as categoria_nombre, 
          COUNT(s.id) as total_soluciones,
          COUNT(DISTINCT s.usuario_id) as usuarios_resueltos
          FROM desafios d 
          JOIN categorias c ON d.categoria_id = c.id 
          LEFT JOIN soluciones_desafio s ON d.id = s.desafio_id AND s.es_correcta = TRUE
          GROUP BY d.id 
          ORDER BY d.fecha_creacion DESC";
$stmt = $db->prepare($query);
$stmt->execute();
$desafios = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administrar Desafíos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <?php include 'navbar.php'; ?>
    
    <div class="container mt-4">
        <h1>Administrar Desafíos</h1>
        
        <!-- Formulario para crear desafío -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Crear Nuevo Desafío</h5>
            </div>
            <div class="card-body">
                <form method="POST">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Título</label>
                            <input type="text" class="form-control" name="titulo" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Categoría</label>
                            <select class="form-select" name="categoria_id" required>
                                <?php foreach($categorias as $categoria): ?>
                                    <option value="<?php echo $categoria['id']; ?>"><?php echo htmlspecialchars($categoria['nombre']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Dificultad</label>
                            <select class="form-select" name="dificultad" required>
                                <option value="facil">Fácil</option>
                                <option value="medio" selected>Medio</option>
                                <option value="dificil">Difícil</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Descripción</label>
                            <textarea class="form-control" name="descripcion" rows="4" required></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Código Base (Opcional)</label>
                            <textarea class="form-control" name="codigo_base" rows="6" placeholder="Código inicial para que los usuarios completen..."></textarea>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Puntos</label>
                            <input type="number" class="form-control" name="puntos" value="100" min="10" max="1000" required>
                        </div>
                        <div class="col-12">
                            <button type="submit" name="crear_desafio" class="btn btn-success">Crear Desafío</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Lista de desafíos existentes -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Desafíos Existentes</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Título</th>
                                <th>Categoría</th>
                                <th>Dificultad</th>
                                <th>Puntos</th>
                                <th>Resueltos</th>
                                <th>Fecha</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($desafios as $desafio): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($desafio['titulo']); ?></td>
                                <td><?php echo htmlspecialchars($desafio['categoria_nombre']); ?></td>
                                <td>
                                    <span class="badge bg-<?php echo $desafio['dificultad'] == 'facil' ? 'success' : ($desafio['dificultad'] == 'medio' ? 'warning' : 'danger'); ?>">
                                        <?php echo ucfirst($desafio['dificultad']); ?>
                                    </span>
                                </td>
                                <td><?php echo $desafio['puntos']; ?></td>
                                <td>
                                    <small><?php echo $desafio['usuarios_resueltos']; ?> usuarios</small>
                                    <br>
                                    <small><?php echo $desafio['total_soluciones']; ?> soluciones</small>
                                </td>
                                <td><?php echo date('d/m/Y', strtotime($desafio['fecha_creacion'])); ?></td>
                                <td>
                                    <a href="desafio.php?id=<?php echo $desafio['id']; ?>" class="btn btn-sm btn-outline-primary">Ver</a>
                                    <button class="btn btn-sm btn-outline-warning">Editar</button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</body>
</html>