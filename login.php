<?php
ob_start();
session_start();

// Evitar acceso si ya está logueado
if(isset($_SESSION['user_id'])){
    header("Location: index.php");
    exit;
}

// Procesar login
if($_SERVER['REQUEST_METHOD'] === 'POST'){
    require_once 'config/database.php';
    
    $database = new Database();
    $db = $database->getConnection();
    
    // Validar y sanitizar inputs
    $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING);
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']);
    
    // Validaciones
    $errors = [];
    
    if(empty($username)){
        $errors[] = "El usuario o email es requerido";
    }
    
    if(empty($password)){
        $errors[] = "La contraseña es requerida";
    }
    
    // Si no hay errores, verificar credenciales
    if(empty($errors)){
        $query = "SELECT id, username, password, rol, email FROM usuarios WHERE username = ? OR email = ?";
        $stmt = $db->prepare($query);
        
        if($stmt->execute([$username, $username])){
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if($user && password_verify($password, $user['password'])){
                // Establecer sesión
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['rol'] = $user['rol'];
                $_SESSION['email'] = $user['email'];
                
                // Cookie de recordar (30 días)
                if($remember){
                    $cookie_value = base64_encode($user['username'] . '|' . time());
                    setcookie('user_remember', $cookie_value, time() + (30 * 24 * 60 * 60), "/", "", true, true);
                }
                
                // Redirigir
                $redirect = $_SESSION['redirect_url'] ?? 'index.php';
                unset($_SESSION['redirect_url']);
                
                ob_end_clean();
                header("Location: " . $redirect);
                exit;
            }
        }
        
        $errors[] = "Usuario o contraseña incorrectos";
    }
}

// Guardar URL anterior para redirección
if(!isset($_SESSION['redirect_url']) && isset($_SERVER['HTTP_REFERER'])){
    $referer = filter_var($_SERVER['HTTP_REFERER'], FILTER_SANITIZE_URL);
    if(strpos($referer, $_SERVER['HTTP_HOST']) !== false){
        $_SESSION['redirect_url'] = $referer;
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Inicia sesión en nuestro foro comunitario">
    <title>Iniciar Sesión - Foro Comunitario</title>
    
    <!-- Preload de recursos críticos -->
    <link rel="preload" href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" as="style">
    <link rel="preload" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" as="style">
    
    <!-- CSS optimizado -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet" media="all">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- CSS inline (crítico) -->
    <style>
        :root {
            --primary-color: #667eea;
            --secondary-color: #764ba2;
            --accent-color: #f093fb;
            --success-color: #10b981;
            --warning-color: #f59e0b;
        }
        
        body {
            background-image: url('img/l.jpg');
            /*background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);*/
            min-height: 100vh;
            margin: 0;
            font-family: system-ui, -apple-system, 'Segoe UI', sans-serif;
            display: flex;
            align-items: center;
        }
        
        .login-container {
            background: rgba(255, 255, 255, 0.98);
            border-radius: 16px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            max-width: 1200px;
            margin: 1rem auto;
        }
        
        .login-header {
            background-image: url('img/l.jpg');
            /*background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));*/
            color: white;
            padding: 2rem;
            position: relative;
        }
        
        .login-icon {
            width: 64px;
            height: 64px;
            background: rgba(255, 255, 255, 0.15);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            font-size: 1.5rem;
        }
        
        .login-body {
            padding: 2rem;
        }
        
        .form-control {
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 0.75rem;
            transition: all 0.2s ease;
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .input-group {
            position: relative;
        }
        
        .input-icon {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            pointer-events: none;
        }
        
        .form-control {
            padding-left: 40px;
        }
        
        .btn-login {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border: none;
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            font-weight: 600;
            width: 100%;
            cursor: pointer;
            transition: transform 0.2s ease;
        }
        
        .btn-login:hover:not(:disabled) {
            transform: translateY(-1px);
        }
        
        .btn-login:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        
        .password-toggle {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #94a3b8;
            cursor: pointer;
            padding: 4px;
        }
        
        .alert {
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1.5rem;
            animation: fadeIn 0.3s ease;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .feature-list {
            list-style: none;
            padding: 0;
            margin: 1.5rem 0 0;
        }
        
        .feature-list li {
            padding: 0.5rem 0;
            color: white;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .feature-list li i {
            color: #fbbf24;
        }
        
        /* Responsive optimizado */
        @media (max-width: 991.98px) {
            .login-container {
                margin: 0.5rem;
            }
            
            .login-body, .login-header {
                padding: 1.5rem;
            }
        }
        
        @media (max-width: 767.98px) {
            body {
                padding: 1rem;
            }
            
            .login-body, .login-header {
                padding: 1rem;
            }
        }
        
        /* Loading state */
        .loading {
            position: relative;
            pointer-events: none;
        }
        
        .loading::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 20px;
            height: 20px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-top-color: white;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }
        
        @keyframes spin {
            to { transform: translate(-50%, -50%) rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-12 col-lg-10">
                <div class="login-container">
                    <div class="row g-0">
                        <!-- Formulario de Login -->
                        <div class="col-lg-6">
                            <div class="login-body">
                                <h3 class="text-center mb-4 text-primary">Bienvenido de Nuevo</h3>
                                
                                <!-- Mensajes -->
                                <?php if(isset($_GET['registro']) && $_GET['registro'] == 'exitoso'): ?>
                                    <div class="alert alert-success d-flex align-items-center">
                                        <i class="fas fa-check-circle me-2"></i>
                                        <div>Registro exitoso. Ahora puedes iniciar sesión.</div>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if(isset($_GET['logout']) && $_GET['logout'] == 'true'): ?>
                                    <div class="alert alert-success d-flex align-items-center">
                                        <i class="fas fa-sign-out-alt me-2"></i>
                                        <div>Sesión cerrada correctamente.</div>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if(isset($errors) && !empty($errors)): ?>
                                    <div class="alert alert-danger">
                                        <div class="d-flex align-items-center mb-2">
                                            <i class="fas fa-exclamation-triangle me-2"></i>
                                            <strong>Error en el inicio de sesión</strong>
                                        </div>
                                        <ul class="mb-0 ps-3">
                                            <?php foreach($errors as $error): ?>
                                                <li><?php echo htmlspecialchars($error); ?></li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                <?php endif; ?>

                                <!-- Formulario -->
                                <form method="POST" id="loginForm" novalidate>
                                    <div class="mb-3">
                                        <label for="username" class="form-label fw-semibold">Usuario o Email</label>
                                        <div class="input-group">
                                            <i class="input-icon fas fa-user"></i>
                                            <input type="text" 
                                                   class="form-control" 
                                                   id="username" 
                                                   name="username" 
                                                   value="<?php echo isset($_COOKIE['user_remember']) ? htmlspecialchars(base64_decode(explode('|', $_COOKIE['user_remember'])[0])) : ''; ?>"
                                                   placeholder="Ingresa tu usuario o email"
                                                   required
                                                   autocomplete="username">
                                            <div class="invalid-feedback">
                                                Por favor ingresa tu usuario o email
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="password" class="form-label fw-semibold">Contraseña</label>
                                        <div class="input-group">
                                            <i class="input-icon fas fa-lock"></i>
                                            <input type="password" 
                                                   class="form-control" 
                                                   id="password" 
                                                   name="password" 
                                                   placeholder="Ingresa tu contraseña"
                                                   required
                                                   autocomplete="current-password"
                                                   minlength="6">
                                            <button type="button" class="password-toggle" id="togglePassword" aria-label="Mostrar contraseña">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <div class="invalid-feedback">
                                                La contraseña debe tener al menos 6 caracteres
                                            </div>
                                        </div>
                                    </div>

                                    <div class="d-flex justify-content-between align-items-center mb-4">
                                        <div class="form-check">
                                            <input class="form-check-input" 
                                                   type="checkbox" 
                                                   id="remember" 
                                                   name="remember"
                                                   <?php echo isset($_COOKIE['user_remember']) ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="remember">
                                                Recordarme
                                            </label>
                                        </div>
                                        <a href="forgot-password.php" class="text-primary text-decoration-none">
                                            ¿Olvidaste tu contraseña?
                                        </a>
                                    </div>

                                    <button type="submit" class="btn btn-login" id="submitBtn">
                                        <i class="fas fa-sign-in-alt me-2"></i>
                                        Iniciar Sesión
                                    </button>
                                </form>

                                <div class="text-center mt-4">
                                    <p class="text-muted mb-0">
                                        ¿No tienes una cuenta?
                                        <a href="register.php" class="text-decoration-none fw-semibold text-primary">
                                            Regístrate aquí
                                        </a><hr>

                                         <a href="index.php" class="text-decoration-none fw-semibold text-primary">
                                            Volver al Inicio.
                                        </a>
                                    </p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Información del Foro -->
                        <div class="col-lg-6 d-none d-lg-block">
                            <div class="login-header h-100 d-flex flex-column justify-content-center">
                                <div class="login-icon">
                                    <i class="fas fa-comments"></i>
                                </div>
                                <h2 class="text-center mb-3">Foro Comunitario</h2>
                                <p class="text-center mb-4">Únete a la conversación y conecta con la comunidad</p>
                                
                                <ul class="feature-list">
                                    <li>
                                        <i class="fas fa-check-circle"></i>
                                        Acceso a todas las discusiones
                                    </li>
                                    <li>
                                        <i class="fas fa-check-circle"></i>
                                        Crea tus propios hilos
                                    </li>
                                    <li>
                                        <i class="fas fa-check-circle"></i>
                                        Responde a otros miembros
                                    </li>
                                    <li>
                                        <i class="fas fa-check-circle"></i>
                                        Personaliza tu perfil
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- JavaScript optimizado -->
    <script>
        // Inicialización cuando el DOM está listo
        document.addEventListener('DOMContentLoaded', function() {
            // Elementos del DOM
            const loginForm = document.getElementById('loginForm');
            const submitBtn = document.getElementById('submitBtn');
            const togglePassword = document.getElementById('togglePassword');
            const passwordInput = document.getElementById('password');
            
            // Función para mostrar/ocultar contraseña
            if(togglePassword) {
                togglePassword.addEventListener('click', function() {
                    const type = passwordInput.type === 'password' ? 'text' : 'password';
                    passwordInput.type = type;
                    this.querySelector('i').className = type === 'password' ? 'fas fa-eye' : 'fas fa-eye-slash';
                });
            }
            
            // Validación del formulario en tiempo real
            if(loginForm) {
                // Validar en input
                loginForm.addEventListener('input', function(e) {
                    const target = e.target;
                    if(target.tagName === 'INPUT') {
                        validateField(target);
                    }
                });
                
                // Validar en blur
                loginForm.addEventListener('focusout', function(e) {
                    if(e.target.tagName === 'INPUT') {
                        validateField(e.target);
                    }
                });
                
                // Enviar formulario con validación
                loginForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    
                    if(validateForm()) {
                        // Mostrar loading
                        submitBtn.disabled = true;
                        submitBtn.classList.add('loading');
                        submitBtn.innerHTML = '';
                        
                        // Enviar formulario
                        this.submit();
                    }
                });
            }
            
            // Función para validar un campo
            function validateField(field) {
                field.classList.remove('is-invalid', 'is-valid');
                
                if(field.required && !field.value.trim()) {
                    field.classList.add('is-invalid');
                    return false;
                }
                
                // Validaciones específicas
                if(field.id === 'password' && field.value.length < 6) {
                    field.classList.add('is-invalid');
                    return false;
                }
                
                if(field.id === 'username' && field.value.length < 3) {
                    field.classList.add('is-invalid');
                    return false;
                }
                
                field.classList.add('is-valid');
                return true;
            }
            
            // Función para validar todo el formulario
            function validateForm() {
                const inputs = loginForm.querySelectorAll('input[required]');
                let isValid = true;
                
                inputs.forEach(input => {
                    if(!validateField(input)) {
                        isValid = false;
                    }
                });
                
                return isValid;
            }
            
            // Animar elementos al cargar
            const formElements = document.querySelectorAll('.login-body > *');
            formElements.forEach((element, index) => {
                element.style.opacity = '0';
                element.style.transform = 'translateY(10px)';
                
                setTimeout(() => {
                    element.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
                    element.style.opacity = '1';
                    element.style.transform = 'translateY(0)';
                }, index * 100);
            });
        });
    </script>
</body>
</html>