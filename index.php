<?php
include_once 'includes/auth.php';
include_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();

// Obtener categorías y estadísticas
$query = "SELECT c.*, 
          COUNT(h.id) as total_hilos,
          (SELECT COUNT(*) FROM respuestas r 
           JOIN hilos h2 ON r.hilo_id = h2.id 
           WHERE h2.categoria_id = c.id) as total_respuestas,
          (SELECT h3.titulo FROM hilos h3 
           WHERE h3.categoria_id = c.id 
           ORDER BY h3.ultima_respuesta DESC LIMIT 1) as ultimo_hilo_titulo,
          (SELECT u.username FROM hilos h3 
           JOIN usuarios u ON h3.usuario_id = u.id 
           WHERE h3.categoria_id = c.id 
           ORDER BY h3.ultima_respuesta DESC LIMIT 1) as ultimo_usuario,
          (SELECT h3.ultima_respuesta FROM hilos h3 
           WHERE h3.categoria_id = c.id 
           ORDER BY h3.ultima_respuesta DESC LIMIT 1) as ultima_fecha
          FROM categorias c 
          LEFT JOIN hilos h ON c.id = h.categoria_id 
          GROUP BY c.id 
          ORDER BY c.id";
$stmt = $db->prepare($query);
$stmt->execute();
$categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener hilos más activos
$query = "SELECT h.*, u.username as autor, c.nombre as categoria,
          (SELECT COUNT(*) FROM respuestas r WHERE r.hilo_id = h.id) as total_respuestas
          FROM hilos h 
          JOIN usuarios u ON h.usuario_id = u.id 
          JOIN categorias c ON h.categoria_id = c.id 
          ORDER BY h.ultima_respuesta DESC 
          LIMIT 8";
$stmt = $db->prepare($query);
$stmt->execute();
$hilos_activos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener estadísticas generales del foro
$query = "SELECT 
          (SELECT COUNT(*) FROM usuarios) as total_usuarios,
          (SELECT COUNT(*) FROM hilos) as total_hilos,
          (SELECT COUNT(*) FROM respuestas) as total_respuestas,
          (SELECT username FROM usuarios ORDER BY fecha_registro DESC LIMIT 1) as ultimo_usuario";
$stmt = $db->prepare($query);
$stmt->execute();
$estadisticas_foro = $stmt->fetch(PDO::FETCH_ASSOC);

// Obtener usuarios activos
$query = "SELECT u.username, 
          COUNT(DISTINCT h.id) as hilos_count,
          COUNT(DISTINCT r.id) as respuestas_count
          FROM usuarios u 
          LEFT JOIN hilos h ON u.id = h.usuario_id 
          LEFT JOIN respuestas r ON u.id = r.usuario_id 
          GROUP BY u.id 
          ORDER BY (COUNT(h.id) + COUNT(r.id)) DESC 
          LIMIT 6";
$stmt = $db->prepare($query);
$stmt->execute();
$usuarios_activos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener estadísticas en tiempo real
$query = "SELECT 
          (SELECT COUNT(*) FROM usuarios) as usuarios_registrados,
          (SELECT COUNT(*) FROM hilos WHERE DATE(fecha_creacion) = CURDATE()) as hilos_hoy,
          (SELECT COUNT(*) FROM respuestas WHERE DATE(fecha_creacion) = CURDATE()) as respuestas_hoy";
$stmt = $db->prepare($query);
$stmt->execute();
$estadisticas_tiempo_real = $stmt->fetch(PDO::FETCH_ASSOC);

// Estimación de usuarios online (5% de los usuarios totales, mínimo 1)
$usuarios_online = max(1, round($estadisticas_foro['total_usuarios'] * 0.05));
$estadisticas_tiempo_real['usuarios_online'] = $usuarios_online;
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Foro de Discusión - Comunidad</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #667eea;
            --secondary-color: #764ba2;
            --accent-color: #f093fb;
            --light-bg: #f8f9fa;
            --dark-bg: #343a40;
        }
        
        .hero-section {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            padding: 4rem 0;
            margin-bottom: 2rem;
            border-radius: 0 0 30px 30px;
        }
        
        .stat-card {
            background: white;
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            border-left: 4px solid var(--primary-color);
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
        }
        
        .category-card {
            background: white;
            border: none;
            border-radius: 15px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
            margin-bottom: 1.5rem;
        }
        
        .category-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.12);
        }
        
        .thread-card {
            background: white;
            border: none;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            transition: all 0.3s ease;
            margin-bottom: 1rem;
        }
        
        .thread-card:hover {
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .badge-custom {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
        }
        
        .nav-pills .nav-link.active {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border: none;
        }
        
        .feature-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }
        
        .activity-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #28a745;
            display: inline-block;
            margin-right: 5px;
        }
        
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--light-bg);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary-color);
            font-weight: bold;
        }
        
        .trending-badge {
            background: linear-gradient(135deg, #ff6b6b, #ee5a24);
            color: white;
        }
        
        .info-card {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            border-radius: 15px;
            transition: all 0.3s ease;
        }
        
        .info-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
        }
        
        .code-example {
            background: #2d3748;
            color: #e2e8f0;
            border-radius: 8px;
            padding: 1rem;
            font-family: 'Courier New', monospace;
            font-size: 0.9rem;
            margin: 1rem 0;
            border-left: 4px solid #667eea;
        }
        
        .tech-badge {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            border: 1px solid rgba(255, 255, 255, 0.3);
        }
        
        .resource-link {
            color: #90cdf4;
            text-decoration: none;
            transition: color 0.3s ease;
        }
        
        .resource-link:hover {
            color: #63b3ed;
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>
    
    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-8">
                    <h1 class="display-4 fw-bold mb-3">Bienvenido a Nuestra Comunidad</h1>
                    <p class="lead mb-4">Comparte, aprende y conecta con personas que comparten tus intereses. Únete a la conversación y forma parte de nuestra comunidad en crecimiento.</p>
                    <div class="d-flex gap-3 flex-wrap">
                        <?php if(isLoggedIn()): ?>
                            <a href="nuevo_hilo.php" class="btn btn-light btn-lg">
                                <i class="fas fa-plus-circle"></i> Crear Nuevo Hilo
                            </a>
                        <?php else: ?>
                            <a href="register.php" class="btn btn-light btn-lg">
                                <i class="fas fa-user-plus"></i> Únete a la Comunidad
                            </a>
                            <a href="login.php" class="btn btn-outline-light btn-lg">
                                <i class="fas fa-sign-in-alt"></i> Iniciar Sesión
                            </a>
                        <?php endif; ?>
                        <a href="#categorias" class="btn btn-outline-light btn-lg">
                            <i class="fas fa-explore"></i> Explorar Foro
                        </a>
                    </div>
                </div>
                <div class="col-lg-4 text-center">
                    <div class="feature-icon mx-auto">
                        <i class="fas fa-comments"></i>
                    </div>
                    <h4>Comunidad Activa</h4>
                    <p class="mb-0">Únete a nuestras discusiones</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Estadísticas del Foro -->
    <section class="container mb-5">
        <div class="row g-4">
            <div class="col-md-3">
                <div class="stat-card text-center p-4">
                    <i class="fas fa-users fa-2x text-primary mb-3"></i>
                    <h3 class="text-primary"><?php echo $estadisticas_foro['total_usuarios']; ?></h3>
                    <p class="text-muted mb-0">Miembros</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card text-center p-4">
                    <i class="fas fa-comments fa-2x text-success mb-3"></i>
                    <h3 class="text-success"><?php echo $estadisticas_foro['total_hilos']; ?></h3>
                    <p class="text-muted mb-0">Discusiones</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card text-center p-4">
                    <i class="fas fa-reply fa-2x text-info mb-3"></i>
                    <h3 class="text-info"><?php echo $estadisticas_foro['total_respuestas']; ?></h3>
                    <p class="text-muted mb-0">Respuestas</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card text-center p-4">
                    <i class="fas fa-user-plus fa-2x text-warning mb-3"></i>
                    <h3 class="text-warning"><?php echo htmlspecialchars($estadisticas_foro['ultimo_usuario']); ?></h3>
                    <p class="text-muted mb-0">Último miembro</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Sección de Información sobre Programación -->
    <section class="container mb-5">
        <div class="row">
            <div class="col-12">
                <div class="info-card p-4 mb-4">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h2 class="mb-3">
                                <i class="fas fa-code me-2"></i>Recursos de Programación
                            </h2>
                            <p class="lead mb-0">Aprende y mejora tus habilidades con información verificada y ejemplos prácticos</p>
                        </div>
                        <div class="col-md-4 text-center">
                            <div class="feature-icon mx-auto bg-white text-primary">
                                <i class="fas fa-laptop-code"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <!-- Conceptos Fundamentales -->
            <div class="col-md-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-cogs me-2"></i>Conceptos Fundamentales
                        </h5>
                    </div>
                    <div class="card-body">
                        <h6>Principios SOLID</h6>
                        <p class="small text-muted">Los 5 principios de diseño orientado a objetos:</p>
                        <ul class="small">
                            <li><strong>S</strong> - Single Responsibility</li>
                            <li><strong>O</strong> - Open/Closed</li>
                            <li><strong>L</strong> - Liskov Substitution</li>
                            <li><strong>I</strong> - Interface Segregation</li>
                            <li><strong>D</strong> - Dependency Inversion</li>
                        </ul>
                        
                        <h6 class="mt-3">Complejidad Algorítmica</h6>
                        <p class="small text-muted">Notación Big O común:</p>
                        <div class="code-example small">
                            O(1) - Tiempo constante<br>
                            O(log n) - Logarítmico<br>
                            O(n) - Lineal<br>
                            O(n²) - Cuadrático<br>
                            O(2ⁿ) - Exponencial
                        </div>
                    </div>
                </div>
            </div>

            <!-- Ejemplo de Código PHP -->
            <div class="col-md-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0">
                            <i class="fab fa-php me-2"></i>Ejemplo PHP - MVC
                        </h5>
                    </div>
                    <div class="card-body">
                        <p class="small text-muted">Estructura básica de un controlador en MVC:</p>
                        <div class="code-example">
                            <span style="color: #fbbf24">class</span> <span style="color: #90cdf4">UserController</span> {<br>
                            &nbsp;&nbsp;<span style="color: #fbbf24">public function</span> <span style="color: #90cdf4">show</span>($id) {<br>
                            &nbsp;&nbsp;&nbsp;&nbsp;$user = User::find($id);<br>
                            &nbsp;&nbsp;&nbsp;&nbsp;<span style="color: #fbbf24">return</span> view('user.profile', [<br>
                            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'user' => $user<br>
                            &nbsp;&nbsp;&nbsp;&nbsp;]);<br>
                            &nbsp;&nbsp;}<br>
                            &nbsp;&nbsp;<br>
                            &nbsp;&nbsp;<span style="color: #fbbf24">public function</span> <span style="color: #90cdf4">store</span>(Request $request) {<br>
                            &nbsp;&nbsp;&nbsp;&nbsp;$validated = $request->validate([<br>
                            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'name' => 'required|string',<br>
                            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'email' => 'required|email'<br>
                            &nbsp;&nbsp;&nbsp;&nbsp;]);<br>
                            &nbsp;&nbsp;&nbsp;&nbsp;User::create($validated);<br>
                            &nbsp;&nbsp;&nbsp;&nbsp;<span style="color: #fbbf24">return</span> redirect('/users');<br>
                            &nbsp;&nbsp;}<br>
                            }
                        </div>
                    </div>
                </div>
            </div>

            <!-- Buenas Prácticas -->
            <div class="col-md-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-check-circle me-2"></i>Buenas Prácticas
                        </h5>
                    </div>
                    <div class="card-body">
                        <h6>Seguridad Web</h6>
                        <ul class="small">
                            <li>Validar y sanitizar todas las entradas</li>
                            <li>Usar prepared statements para SQL</li>
                            <li>Implementar CSRF protection</li>
                            <li>Hash de contraseñas con password_hash()</li>
                            <li>Headers de seguridad HTTP</li>
                        </ul>
                        
                        <h6 class="mt-3">Performance</h6>
                        <ul class="small">
                            <li>Minimizar queries a la base de datos</li>
                            <li>Usar caching cuando sea posible</li>
                            <li>Optimizar assets (CSS, JS, imágenes)</li>
                            <li>Lazy loading para recursos pesados</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Recursos de Aprendizaje -->
            <div class="col-md-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-warning text-dark">
                        <h5 class="mb-0">
                            <i class="fas fa-graduation-cap me-2"></i>Recursos Recomendados
                        </h5>
                    </div>
                    <div class="card-body">
                        <h6>Documentación Oficial</h6>
                        <ul class="small">
                            <li><a href="https://www.php.net/docs.php" class="resource-link" target="_blank">PHP Documentation</a></li>
                            <li><a href="https://dev.mysql.com/doc/" class="resource-link" target="_blank">MySQL Documentation</a></li>
                            <li><a href="https://getbootstrap.com/docs/" class="resource-link" target="_blank">Bootstrap Docs</a></li>
                        </ul>
                        
                        <h6 class="mt-3">Plataformas de Aprendizaje</h6>
                        <ul class="small">
                            <li><a href="https://freecodecamp.org" class="resource-link" target="_blank">freeCodeCamp</a></li>
                            <li><a href="https://www.theodinproject.com" class="resource-link" target="_blank">The Odin Project</a></li>
                            <li><a href="https://developer.mozilla.org" class="resource-link" target="_blank">MDN Web Docs</a></li>
                        </ul>
                        
                        <h6 class="mt-3">Herramientas Útiles</h6>
                        <ul class="small">
                            <li><strong>Git:</strong> Control de versiones</li>
                            <li><strong>Composer:</strong> Gestor de dependencias PHP</li>
                            <li><strong>VS Code:</strong> Editor de código</li>
                            <li><strong>Postman:</strong> Testing de APIs</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <div class="container">
        <div class="row">
            <!-- Contenido Principal -->
            <div class="col-lg-8">
                <!-- Categorías de Discusión -->
                <div class="card border-0 shadow-sm mb-4" id="categorias">
                    <div class="card-header bg-white border-0 py-3">
                        <h4 class="mb-0">
                            <i class="fas fa-layer-group text-primary"></i> Categorías de Discusión
                        </h4>
                    </div>
                    <div class="card-body">
                        <?php for($i = 0; $i < min(3, count($categorias)); $i++): 
                            $categoria = $categorias[$i];
                        ?>
                            <div class="category-card p-4">
                                <div class="row align-items-center">
                                    <div class="col-md-8">
                                        <h5 class="card-title mb-2">
                                            <a href="categoria.php?id=<?php echo $categoria['id']; ?>" class="text-decoration-none text-dark">
                                                <?php echo htmlspecialchars($categoria['nombre']); ?>
                                            </a>
                                        </h5>
                                        <p class="card-text text-muted mb-2">
                                            <?php echo htmlspecialchars($categoria['descripcion']); ?>
                                        </p>
                                        <div class="d-flex align-items-center text-muted small">
                                            <span class="me-3">
                                                <i class="fas fa-comments"></i> 
                                                <?php echo $categoria['total_hilos']; ?> hilos
                                            </span>
                                            <span>
                                                <i class="fas fa-reply"></i> 
                                                <?php echo $categoria['total_respuestas']; ?> respuestas
                                            </span>
                                        </div>
                                    </div>
                                    <div class="col-md-4 text-end">
                                        <?php if($categoria['ultimo_hilo_titulo']): ?>
                                            <div class="latest-activity">
                                                <small class="text-muted d-block">Última actividad:</small>
                                                <strong class="d-block text-truncate">
                                                    <?php echo htmlspecialchars($categoria['ultimo_hilo_titulo']); ?>
                                                </strong>
                                                <small class="text-muted">
                                                    por <?php echo htmlspecialchars($categoria['ultimo_usuario']); ?>
                                                    <br>
                                                    <?php echo date('d/m H:i', strtotime($categoria['ultima_fecha'])); ?>
                                                </small>
                                            </div>
                                        <?php else: ?>
                                            <span class="text-muted">Sin actividad aún</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endfor; ?>
                    </div>
                </div>

                <!-- Hilos Más Activos -->
                <div class="card border-0 shadow-sm mb-5">
                    <div class="card-header bg-white border-0 py-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <h4 class="mb-0">
                                <i class="fas fa-fire text-warning"></i> Hilos Más Activos
                            </h4>
                            <span class="badge trending-badge">En Tendencia</span>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if(count($hilos_activos) > 0): ?>
                            <?php foreach($hilos_activos as $hilo): ?>
                                <div class="thread-card p-3">
                                    <div class="row align-items-center">
                                        <div class="col-md-8">
                                            <h6 class="mb-1">
                                                <a href="hilo.php?id=<?php echo $hilo['id']; ?>" class="text-decoration-none text-dark">
                                                    <?php echo htmlspecialchars($hilo['titulo']); ?>
                                                </a>
                                            </h6>
                                            <div class="d-flex align-items-center text-muted small">
                                                <span class="user-avatar me-2">
                                                    <?php echo strtoupper(substr($hilo['autor'], 0, 1)); ?>
                                                </span>
                                                <span class="me-3"><?php echo htmlspecialchars($hilo['autor']); ?></span>
                                                <span class="me-3">
                                                    <i class="fas fa-folder"></i> 
                                                    <?php echo htmlspecialchars($hilo['categoria']); ?>
                                                </span>
                                                <span>
                                                    <i class="fas fa-clock"></i> 
                                                    <?php echo date('d/m H:i', strtotime($hilo['ultima_respuesta'])); ?>
                                                </span>
                                            </div>
                                        </div>
                                        <div class="col-md-4 text-end">
                                            <span class="badge bg-light text-dark p-2">
                                                <i class="fas fa-reply"></i> 
                                                <?php echo $hilo['total_respuestas']; ?> respuestas
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="text-center py-4">
                                <i class="fas fa-comments fa-3x text-muted mb-3"></i>
                                <h5>No hay hilos activos</h5>
                                <p class="text-muted">Sé el primero en crear un hilo de discusión</p>
                                <?php if(isLoggedIn()): ?>
                                    <a href="nuevo_hilo.php" class="btn btn-primary">Crear Primer Hilo</a>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            

            <!-- Sidebar -->
            <div class="col-lg-4">
                <!-- Acciones Rápidas -->
                <?php if(isLoggedIn()): ?>
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-header bg-white border-0">
                            <h5 class="mb-0">
                                <i class="fas fa-bolt text-warning"></i> Acciones Rápidas
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="d-grid gap-2">
                                <a href="nuevo_hilo.php" class="btn btn-primary">
                                    <i class="fas fa-plus"></i> Nuevo Hilo
                                </a>
                                <a href="perfil.php" class="btn btn-outline-primary">
                                    <i class="fas fa-user"></i> Mi Perfil
                                </a>
                                <a href="mis_hilos.php" class="btn btn-outline-secondary">
                                    <i class="fas fa-comments"></i> Mis Hilos
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Miembros Activos -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white border-0">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">
                                <i class="fas fa-users text-success"></i> Miembros Activos
                            </h5>
                            <div class="input-group input-group-sm" style="width: 150px;">
                                <input type="text" class="form-control" placeholder="Buscar">
                                <button class="btn btn-outline-secondary" type="button">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php foreach($usuarios_activos as $usuario): ?>
                            <div class="d-flex align-items-center mb-3">
                                <div class="user-avatar me-3">
                                    <?php echo strtoupper(substr($usuario['username'], 0, 1)); ?>
                                </div>
                                <div class="flex-grow-1">
                                    <strong class="d-block"><?php echo htmlspecialchars($usuario['username']); ?></strong>
                                    <small class="text-muted">
                                        <?php echo ($usuario['hilos_count'] + $usuario['respuestas_count']); ?> contribuciones
                                    </small>
                                </div>
                                <span class="badge bg-light text-dark">
                                    <?php echo $usuario['hilos_count']; ?>/<i class="fas fa-reply"></i><?php echo $usuario['respuestas_count']; ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Estadísticas en Tiempo Real -->
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-0">
                        <h5 class="mb-0">
                            <i class="fas fa-chart-bar text-info"></i> Estadísticas en Tiempo Real
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="text-center mb-3">
                            <div class="fw-bold text-primary" style="font-size: 2rem;">
                                <?php echo $estadisticas_tiempo_real['usuarios_online']; ?>
                            </div>
                            <small class="text-muted">Miembros en línea</small>
                        </div>
                        <div class="text-center mb-3">
                            <div class="fw-bold text-success" style="font-size: 2rem;">
                                <?php echo $estadisticas_tiempo_real['hilos_hoy'] ?? 0; ?>
                            </div>
                            <small class="text-muted">Hilos hoy</small>
                        </div>
                        <div class="text-center mb-3">
                            <div class="fw-bold text-warning" style="font-size: 2rem;">
                                <?php echo $estadisticas_tiempo_real['respuestas_hoy'] ?? 0; ?>
                            </div>
                            <small class="text-muted">Respuestas hoy</small>
                        </div>
                        <div class="text-center">
                            <div class="fw-bold text-info" style="font-size: 2rem;">
                                <?php echo $estadisticas_foro['total_usuarios']; ?>
                            </div>
                            <small class="text-muted">Usuarios registrados</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-dark text-light mt-5 py-4">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h5>Foro de Discusión</h5>
                    <p class="mb-0">Comunidad de aprendizaje y colaboración</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <small>&copy; <?php echo date('Y'); ?> Foro de Discusión. Todos los derechos reservados.</small>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Animación simple para las tarjetas
        document.addEventListener('DOMContentLoaded', function() {
            const cards = document.querySelectorAll('.stat-card, .category-card, .info-card');
            cards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                
                setTimeout(() => {
                    card.style.transition = 'all 0.5s ease';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * 100);
            });
        });
    </script>
</body>
</html>