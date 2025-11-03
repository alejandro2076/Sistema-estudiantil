<?php
include_once 'includes/auth.php';
include_once 'config/database.php';

if(!isLoggedIn()){
    header("Location: login.php");
    exit;
}

$database = new Database();
$db = $database->getConnection();

// Obtener categorías
$query = "SELECT * FROM categorias";
$stmt = $db->prepare($query);
$stmt->execute();
$categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);

if($_POST){
    $titulo = trim($_POST['titulo']);
    $contenido = trim($_POST['contenido']);
    $categoria_id = $_POST['categoria_id'];
    $usuario_id = getUserID();
    
    $errors = [];
    
    // Validaciones
    if(empty($titulo) || strlen($titulo) < 5) {
        $errors[] = "El título debe tener al menos 5 caracteres";
    }
    
    if(empty($contenido) || strlen($contenido) < 10) {
        $errors[] = "El contenido debe tener al menos 10 caracteres";
    }
    
    if(empty($categoria_id)) {
        $errors[] = "Debes seleccionar una categoría";
    }
    
    if(empty($errors)){
        $query = "INSERT INTO hilos (titulo, contenido, usuario_id, categoria_id) VALUES (?, ?, ?, ?)";
        $stmt = $db->prepare($query);
        
        if($stmt->execute([$titulo, $contenido, $usuario_id, $categoria_id])){
            header("Location: index.php?creado=exitoso");
            exit;
        } else {
            $errors[] = "Error al crear el hilo. Intenta nuevamente.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear Nuevo Hilo - Foro Comunitario</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #667eea;
            --secondary-color: #764ba2;
        }
        
        .create-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
        }
        
        .form-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            padding: 2rem;
            margin-bottom: 2rem;
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        
        .btn-create {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border: none;
            color: white;
            padding: 0.75rem 2rem;
            border-radius: 50px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-create:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        
        .char-count {
            font-size: 0.875rem;
            text-align: right;
            color: #6c757d;
        }
        
        .char-count.warning {
            color: #f59e0b;
        }
        
        .char-count.danger {
            color: #ef4444;
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>
    
    <!-- Header -->
    <div class="create-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="display-6 fw-bold mb-3">
                        <i class="fas fa-plus-circle me-2"></i>
                        Crear Nuevo Hilo
                    </h1>
                    <p class="lead mb-0">Comparte tus ideas, preguntas o conocimientos con la comunidad</p>
                </div>
                <div class="col-md-4 text-end">
                    <a href="index.php" class="btn btn-outline-light">
                        <i class="fas fa-arrow-left me-2"></i>Volver al Foro
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="form-container">
                    <?php if(!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <strong>Por favor corrige los siguientes errores:</strong>
                            </div>
                            <ul class="mb-0 mt-2">
                                <?php foreach($errors as $error): ?>
                                    <li><?php echo $error; ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <form method="POST" id="threadForm">
                        <div class="mb-4">
                            <label for="titulo" class="form-label fw-semibold fs-5">
                                <i class="fas fa-heading text-primary me-2"></i>Título del Hilo
                            </label>
                            <input type="text" class="form-control form-control-lg" id="titulo" name="titulo" 
                                   value="<?php echo isset($_POST['titulo']) ? htmlspecialchars($_POST['titulo']) : ''; ?>"
                                   placeholder="Escribe un título claro y descriptivo..." required>
                            <div class="char-count mt-1">
                                <span id="titleCount">0</span>/100 caracteres
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <label for="categoria_id" class="form-label fw-semibold fs-5">
                                <i class="fas fa-folder text-primary me-2"></i>Categoría
                            </label>
                            <select class="form-select form-select-lg" id="categoria_id" name="categoria_id" required>
                                <option value="">Selecciona una categoría</option>
                                <?php foreach($categorias as $categoria): ?>
                                    <option value="<?php echo $categoria['id']; ?>" 
                                        <?php if(isset($_POST['categoria_id']) && $_POST['categoria_id'] == $categoria['id']) echo 'selected'; ?>>
                                        <?php echo htmlspecialchars($categoria['nombre']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-4">
                            <label for="contenido" class="form-label fw-semibold fs-5">
                                <i class="fas fa-edit text-primary me-2"></i>Contenido
                            </label>
                            <textarea class="form-control" id="contenido" name="contenido" rows="12" 
                                      placeholder="Describe tu pregunta, idea o tema de discusión en detalle...
                                      
Puedes usar:
**negrita** para texto importante
*cursiva* para énfasis
`código` para fragmentos de código
- Listas con guiones" required><?php echo isset($_POST['contenido']) ? htmlspecialchars($_POST['contenido']) : ''; ?></textarea>
                            <div class="char-count mt-1">
                                <span id="contentCount">0</span>/5000 caracteres
                            </div>
                        </div>

                        <div class="d-flex justify-content-between align-items-center mt-4 pt-3 border-top">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="notificaciones" checked>
                                <label class="form-check-label" for="notificaciones">
                                    Recibir notificaciones de respuestas
                                </label>
                            </div>
                            <div class="d-flex gap-3">
                                <a href="index.php" class="btn btn-outline-secondary px-4">
                                    <i class="fas fa-times me-2"></i>Cancelar
                                </a>
                                <button type="submit" class="btn btn-create px-4">
                                    <i class="fas fa-paper-plane me-2"></i>Publicar Hilo
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
                
                <!-- Tips -->
                <div class="alert alert-info">
                    <h6 class="alert-heading">
                        <i class="fas fa-lightbulb me-2"></i>Consejos para un buen hilo:
                    </h6>
                    <ul class="mb-0">
                        <li>Escribe un título claro y específico</li>
                        <li>Explica tu pregunta o idea en detalle</li>
                        <li>Usa un lenguaje respetuoso y claro</li>
                        <li>Selecciona la categoría más apropiada</li>
                        <li>Revisa tu contenido antes de publicar</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Contadores de caracteres
        const tituloInput = document.getElementById('titulo');
        const contenidoTextarea = document.getElementById('contenido');
        const titleCount = document.getElementById('titleCount');
        const contentCount = document.getElementById('contentCount');
        
        function updateCharCount(element, counter, max) {
            const length = element.value.length;
            counter.textContent = length;
            
            if(length > max * 0.9) {
                counter.classList.add('danger');
                counter.classList.remove('warning');
            } else if(length > max * 0.7) {
                counter.classList.add('warning');
                counter.classList.remove('danger');
            } else {
                counter.classList.remove('warning', 'danger');
            }
        }
        
        if(tituloInput && titleCount) {
            tituloInput.addEventListener('input', function() {
                updateCharCount(this, titleCount, 100);
            });
            // Inicializar contador
            updateCharCount(tituloInput, titleCount, 100);
        }
        
        if(contenidoTextarea && contentCount) {
            contenidoTextarea.addEventListener('input', function() {
                updateCharCount(this, contentCount, 5000);
            });
            // Inicializar contador
            updateCharCount(contenidoTextarea, contentCount, 5000);
        }
        
        // Validación del formulario
        const form = document.getElementById('threadForm');
        form.addEventListener('submit', function(e) {
            const titulo = tituloInput.value.trim();
            const contenido = contenidoTextarea.value.trim();
            const categoria = document.getElementById('categoria_id').value;
            
            if(titulo.length < 5) {
                e.preventDefault();
                alert('El título debe tener al menos 5 caracteres');
                tituloInput.focus();
                return;
            }
            
            if(contenido.length < 10) {
                e.preventDefault();
                alert('El contenido debe tener al menos 10 caracteres');
                contenidoTextarea.focus();
                return;
            }
            
            if(!categoria) {
                e.preventDefault();
                alert('Debes seleccionar una categoría');
                document.getElementById('categoria_id').focus();
                return;
            }
        });
        
        // Auto-expand textarea
        if(contenidoTextarea) {
            contenidoTextarea.addEventListener('input', function() {
                this.style.height = 'auto';
                this.style.height = (this.scrollHeight) + 'px';
            });
        }
    </script>
</body>
</html>