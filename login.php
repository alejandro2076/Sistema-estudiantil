<?php
session_start();
include_once 'config/database.php';

if(isset($_SESSION['user_id'])){
    header("Location: index.php");
    exit;
}

if($_POST){
    $database = new Database();
    $db = $database->getConnection();
    
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $remember = isset($_POST['remember']);
    
    $query = "SELECT * FROM usuarios WHERE username = ? OR email = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$username, $username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if($user && password_verify($password, $user['password'])){
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['rol'] = $user['rol'];
        
        // Recordar usuario por 30 días
        if($remember){
            setcookie('user_remember', $user['username'], time() + (30 * 24 * 60 * 60), "/");
        }
        
        // Redirigir a la página anterior o al index
        $redirect = $_SESSION['redirect_url'] ?? 'index.php';
        unset($_SESSION['redirect_url']);
        header("Location: " . $redirect);
        exit;
    } else {
        $error = "Usuario o contraseña incorrectos";
    }
}

// Guardar URL actual para redirigir después del login
if(!isset($_SESSION['redirect_url']) && isset($_SERVER['HTTP_REFERER'])){
    $_SESSION['redirect_url'] = $_SERVER['HTTP_REFERER'];
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - Foro Comunitario</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #667eea;
            --secondary-color: #764ba2;
            --accent-color: #f093fb;
            --success-color: #10b981;
            --warning-color: #f59e0b;
        }
        
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .login-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .login-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 2.5rem;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        .login-header::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 1%, transparent 1%);
            background-size: 20px 20px;
            transform: rotate(30deg);
            animation: float 20s infinite linear;
        }
        
        @keyframes float {
            0% { transform: rotate(30deg) translateX(0); }
            100% { transform: rotate(30deg) translateX(-20px); }
        }
        
        .login-icon {
            width: 80px;
            height: 80px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            font-size: 2rem;
            border: 2px solid rgba(255, 255, 255, 0.3);
        }
        
        .login-body {
            padding: 2.5rem;
        }
        
        .form-control {
            border: none;
            border-bottom: 2px solid #e2e8f0;
            border-radius: 0;
            padding: 0.75rem 0;
            background: transparent;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            border-bottom-color: var(--primary-color);
            box-shadow: none;
            background: transparent;
        }
        
        .input-group {
            position: relative;
        }
        
        .input-icon {
            position: absolute;
            left: 0;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            z-index: 10;
        }
        
        .form-control {
            padding-left: 2.5rem;
        }
        
        .btn-login {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border: none;
            color: white;
            padding: 0.75rem 2rem;
            border-radius: 50px;
            font-weight: 600;
            transition: all 0.3s ease;
            width: 100%;
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        
        .social-login {
            display: flex;
            gap: 1rem;
            margin: 1.5rem 0;
        }
        
        .social-btn {
            flex: 1;
            padding: 0.75rem;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            background: white;
            color: #64748b;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            font-weight: 500;
            cursor: not-allowed;
            opacity: 0.6;
        }
        
        .social-btn:hover {
            transform: none;
            cursor: not-allowed;
        }
        
        .divider {
            text-align: center;
            margin: 2rem 0;
            position: relative;
        }
        
        .divider::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            height: 1px;
            background: #e2e8f0;
        }
        
        .divider span {
            background: white;
            padding: 0 1rem;
            color: #64748b;
            font-size: 0.875rem;
        }
        
        .feature-list {
            list-style: none;
            padding: 0;
            margin: 2rem 0 0;
        }
        
        .feature-list li {
            padding: 0.5rem 0;
            color: white;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 500;
        }
        
        .feature-list li i {
            color: #fbbf24;
        }
        
        .password-toggle {
            position: absolute;
            right: 0;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #94a3b8;
            cursor: pointer;
            z-index: 10;
        }
        
        .remember-forgot {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 1.5rem 0;
        }
        
        .form-check-input:checked {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .forgot-link {
            color: var(--primary-color);
            text-decoration: none;
            font-size: 0.875rem;
        }
        
        .forgot-link:hover {
            text-decoration: underline;
        }
        
        .alert {
            border: none;
            border-radius: 10px;
            padding: 1rem 1.25rem;
        }
        
        .alert-success {
            background: rgba(16, 185, 129, 0.1);
            color: var(--success-color);
            border-left: 4px solid var(--success-color);
        }
        
        .alert-danger {
            background: rgba(239, 68, 68, 0.1);
            color: #ef4444;
            border-left: 4px solid #ef4444;
        }
        
        .floating-shapes {
            position: absolute;
            width: 100%;
            height: 100%;
            top: 0;
            left: 0;
            pointer-events: none;
            z-index: -1;
        }
        
        .shape {
            position: absolute;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            animation: float-shapes 20s infinite linear;
        }
        
        .shape:nth-child(1) {
            width: 100px;
            height: 100px;
            top: 10%;
            left: 10%;
            animation-delay: 0s;
        }
        
        .shape:nth-child(2) {
            width: 150px;
            height: 150px;
            top: 60%;
            right: 10%;
            animation-delay: -5s;
        }
        
        .shape:nth-child(3) {
            width: 80px;
            height: 80px;
            bottom: 20%;
            left: 20%;
            animation-delay: -10s;
        }
        
        @keyframes float-shapes {
            0%, 100% {
                transform: translateY(0) rotate(0deg);
            }
            33% {
                transform: translateY(-20px) rotate(120deg);
            }
            66% {
                transform: translateY(20px) rotate(240deg);
            }
        }

        .info-text {
            color: white;
            opacity: 0.9;
            font-weight: 500;
        }

        .rating-stars {
            color: #fbbf24;
        }
    </style>
</head>
<body>
    <div class="floating-shapes">
        <div class="shape"></div>
        <div class="shape"></div>
        <div class="shape"></div>
    </div>

    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-10 col-lg-8">
                <div class="login-container">
                    <div class="row g-0">
                        <!-- Lado izquierdo - Formulario -->
                        <div class="col-lg-6">
                            <div class="login-body">
                                <h3 class="text-center mb-4" style="color: var(--primary-color);">Bienvenido de Nuevo</h3>
                                
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
                                
                                <?php if(isset($error)): ?>
                                    <div class="alert alert-danger d-flex align-items-center">
                                        <i class="fas fa-exclamation-triangle me-2"></i>
                                        <div><?php echo $error; ?></div>
                                    </div>
                                <?php endif; ?>

                                <form method="POST">
                                    <div class="mb-3">
                                        <label for="username" class="form-label fw-semibold">Usuario o Email</label>
                                        <div class="input-group">
                                            <i class="input-icon fas fa-user"></i>
                                            <input type="text" class="form-control" id="username" name="username" 
                                                   value="<?php echo isset($_COOKIE['user_remember']) ? htmlspecialchars($_COOKIE['user_remember']) : ''; ?>"
                                                   placeholder="Ingresa tu usuario o email" required>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="password" class="form-label fw-semibold">Contraseña</label>
                                        <div class="input-group">
                                            <i class="input-icon fas fa-lock"></i>
                                            <input type="password" class="form-control" id="password" name="password" 
                                                   placeholder="Ingresa tu contraseña" required>
                                            <button type="button" class="password-toggle" id="togglePassword">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        </div>
                                    </div>

                                    <div class="remember-forgot">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="remember" name="remember" 
                                                <?php echo isset($_COOKIE['user_remember']) ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="remember">
                                                Recordarme
                                            </label>
                                        </div>
                                        <a href="forgot-password.php" class="forgot-link">
                                            ¿Olvidaste tu contraseña?
                                        </a>
                                    </div>

                                    <button type="submit" class="btn btn-login mb-3">
                                        <i class="fas fa-sign-in-alt me-2"></i>
                                        Iniciar Sesión
                                    </button>
                                </form>

                               
                                <div class="text-center">
                                    <p class="text-muted mb-0">
                                        ¿No tienes una cuenta?
                                        <a href="register.php" class="text-decoration-none fw-semibold" style="color: var(--primary-color);">
                                            Regístrate aquí
                                        </a>
                                    </p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Lado derecho - Información -->
                        <div class="col-lg-6 d-none d-lg-block">
                            <div class="login-header h-100 d-flex flex-column justify-content-center">
                                <div class="login-icon">
                                    <i class="fas fa-comments"></i>
                                </div>
                                <h2 class="mb-3 info-text">Foro Comunitario</h2>
                                <p class="mb-4 info-text">Únete a la conversación y conecta con la comunidad</p>
                                
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
                                    <li>
                                        <i class="fas fa-check-circle"></i>
                                        Recibe notificaciones
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Mostrar/ocultar contraseña
        document.getElementById('togglePassword').addEventListener('click', function() {
            const passwordInput = document.getElementById('password');
            const icon = this.querySelector('i');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });

        // Efecto de focus en inputs
        const inputs = document.querySelectorAll('.form-control');
        inputs.forEach(input => {
            input.addEventListener('focus', function() {
                this.parentElement.querySelector('.input-icon').style.color = 'var(--primary-color)';
            });
            
            input.addEventListener('blur', function() {
                this.parentElement.querySelector('.input-icon').style.color = '#94a3b8';
            });
        });

        // Animación de entrada
        document.addEventListener('DOMContentLoaded', function() {
            const formElements = document.querySelectorAll('.login-body > *');
            formElements.forEach((element, index) => {
                element.style.opacity = '0';
                element.style.transform = 'translateY(20px)';
                
                setTimeout(() => {
                    element.style.transition = 'all 0.5s ease';
                    element.style.opacity = '1';
                    element.style.transform = 'translateY(0)';
                }, index * 100 + 200);
            });
        });

        // Mostrar mensaje al hacer hover en botones sociales
        document.querySelectorAll('.social-btn').forEach(btn => {
            btn.addEventListener('mouseenter', function() {
                this.title = 'Funcionalidad disponible próximamente';
            });
        });
    </script>
</body>
</html>