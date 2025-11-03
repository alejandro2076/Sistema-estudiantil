<?php
include_once 'config/database.php';

if($_POST){
    $database = new Database();
    $db = $database->getConnection();
    
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    $errors = [];
    
    // Validaciones
    if(empty($username) || strlen($username) < 3) {
        $errors[] = "El usuario debe tener al menos 3 caracteres";
    }
    
    if(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "El email no es válido";
    }
    
    if(strlen($password) < 6) {
        $errors[] = "La contraseña debe tener al menos 6 caracteres";
    }
    
    if($password !== $confirm_password) {
        $errors[] = "Las contraseñas no coinciden";
    }

    // Validar términos aceptados
    if(!isset($_POST['terms'])) {
        $errors[] = "Debes aceptar los Términos de Servicio y Política de Privacidad";
    }
    
    // Verificar si el usuario ya existe
    $query = "SELECT id FROM usuarios WHERE username = ? OR email = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$username, $email]);
    
    if($stmt->fetch()) {
        $errors[] = "El usuario o email ya están registrados";
    }
    
    if(empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        $query = "INSERT INTO usuarios (username, email, password) VALUES (?, ?, ?)";
        $stmt = $db->prepare($query);
        
        if($stmt->execute([$username, $email, $hashed_password])){
            header("Location: login.php?registro=exitoso");
            exit;
        } else {
            $errors[] = "Error al registrar el usuario. Intenta nuevamente.";
        }
    }
}

// Manejar redirección por límite de intentos
$mensaje_especial = '';
if(isset($_GET['motivo']) && $_GET['motivo'] == 'limite_intentos') {
    $desafio_id = $_GET['desafio'] ?? '';
    $mensaje_especial = "
    <div class='alert alert-warning'>
        <div class='d-flex align-items-center'>
            <i class='fas fa-exclamation-triangle fa-2x me-3'></i>
            <div>
                <h5 class='alert-heading mb-2'>¡Límite de intentos alcanzado!</h5>
                <p class='mb-2'>Has usado tus 3 intentos gratuitos. Regístrate para tener intentos ilimitados en todos los desafíos.</p>
                " . ($desafio_id ? "<small class='text-muted'>Desafío #{$desafio_id}</small>" : "") . "
            </div>
        </div>
    </div>";
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro - Foro Comunitario</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #667eea;
            --secondary-color: #764ba2;
            --success-color: #10b981;
            --warning-color: #f59e0b;
            --error-color: #ef4444;
        }
        
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .register-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .register-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 2.5rem;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        .register-header::before {
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
        
        .register-icon {
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
        
        .register-body {
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
            margin-bottom: 1.5rem;
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
        
        .btn-register {
            background: linear-gradient(135deg, var(--success-color), #059669);
            border: none;
            color: white;
            padding: 0.75rem 2rem;
            border-radius: 50px;
            font-weight: 600;
            transition: all 0.3s ease;
            width: 100%;
            margin-top: 1rem;
        }
        
        .btn-register:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(16, 185, 129, 0.4);
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
        
        .alert {
            border: none;
            border-radius: 10px;
            padding: 1rem 1.25rem;
            margin-bottom: 1.5rem;
        }
        
        .alert-danger {
            background: rgba(239, 68, 68, 0.1);
            color: var(--error-color);
            border-left: 4px solid var(--error-color);
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
        
        .password-strength {
            height: 4px;
            border-radius: 2px;
            margin-top: 0.5rem;
            background: #e2e8f0;
            overflow: hidden;
        }
        
        .password-strength-bar {
            height: 100%;
            width: 0%;
            transition: all 0.3s ease;
            border-radius: 2px;
        }
        
        .password-strength-text {
            font-size: 0.75rem;
            margin-top: 0.25rem;
            text-align: right;
        }
        
        .terms-check {
            margin: 1.5rem 0;
        }
        
        .form-check-input:checked {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .terms-link {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
        }
        
        .terms-link:hover {
            text-decoration: underline;
        }
        
        .progress-step {
            display: flex;
            align-items: center;
            margin-bottom: 1rem;
            color: #64748b;
        }
        
        .progress-step.active {
            color: var(--primary-color);
            font-weight: 600;
        }
        
        .progress-step.completed {
            color: var(--success-color);
        }
        
        .step-number {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background: #e2e8f0;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 0.75rem;
            font-size: 0.875rem;
            font-weight: 600;
        }
        
        .progress-step.active .step-number {
            background: var(--primary-color);
            color: white;
        }
        
        .progress-step.completed .step-number {
            background: var(--success-color);
            color: white;
        }
        
        .step-connector {
            width: 2px;
            height: 20px;
            background: #e2e8f0;
            margin-left: 14px;
            margin-bottom: 0.5rem;
        }

        .small-links {
            font-size: 0.8rem;
            color: #64748b;
            text-align: center;
            margin-top: 1.5rem;
            line-height: 1.5;
        }
        
        .small-links a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
        }
        
        .small-links a:hover {
            text-decoration: underline;
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
                <div class="register-container">
                    <div class="row g-0">
                        <!-- Lado izquierdo - Formulario -->
                        <div class="col-lg-6">
                            <div class="register-body">
                                <h3 class="text-center mb-4" style="color: var(--primary-color);">Únete a Nuestra Comunidad</h3>
                                
                                <!-- Progreso de registro -->
                                <div class="mb-4">
                                    <div class="progress-step active">
                                        <div class="step-number">1</div>
                                        <span>Información Personal</span>
                                    </div>
                                    <div class="step-connector"></div>
                                    <div class="progress-step">
                                        <div class="step-number">2</div>
                                        <span>Verificación</span>
                                    </div>
                                    <div class="step-connector"></div>
                                    <div class="progress-step">
                                        <div class="step-number">3</div>
                                        <span>Completado</span>
                                    </div>
                                </div>
                                
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

                                <form method="POST" id="registerForm">
                                    <div class="mb-3">
                                        <label for="username" class="form-label fw-semibold">Nombre de Usuario</label>
                                        <div class="input-group">
                                            <i class="input-icon fas fa-user"></i>
                                            <input type="text" class="form-control" id="username" name="username" 
                                                   value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>"
                                                   placeholder="Elige un nombre de usuario" required>
                                        </div>
                                        <small class="text-muted">Mínimo 3 caracteres. Letras, números y guiones bajos.</small>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="email" class="form-label fw-semibold">Correo Electrónico</label>
                                        <div class="input-group">
                                            <i class="input-icon fas fa-envelope"></i>
                                            <input type="email" class="form-control" id="email" name="email" 
                                                   value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                                                   placeholder="tu@email.com" required>
                                        </div>
                                        <small class="text-muted">Te enviaremos un email de verificación.</small>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="password" class="form-label fw-semibold">Contraseña</label>
                                        <div class="input-group">
                                            <i class="input-icon fas fa-lock"></i>
                                            <input type="password" class="form-control" id="password" name="password" 
                                                   placeholder="Crea una contraseña segura" required>
                                            <button type="button" class="password-toggle" data-target="password">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        </div>
                                        <div class="password-strength">
                                            <div class="password-strength-bar" id="passwordStrengthBar"></div>
                                        </div>
                                        <div class="password-strength-text" id="passwordStrengthText"></div>
                                    </div>

                                    <div class="mb-3">
                                        <label for="confirm_password" class="form-label fw-semibold">Confirmar Contraseña</label>
                                        <div class="input-group">
                                            <i class="input-icon fas fa-lock"></i>
                                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" 
                                                   placeholder="Repite tu contraseña" required>
                                            <button type="button" class="password-toggle" data-target="confirm_password">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        </div>
                                        <small class="text-muted" id="passwordMatchText"></small>
                                    </div>

                                    <div class="terms-check">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="terms" name="terms" required>
                                            <label class="form-check-label" for="terms">
                                                Acepto los <a href="terminos-politica.php" class="terms-link" target="_blank">Términos de Servicio</a> y la <a href="terminos-politica.php" class="terms-link" target="_blank">Política de Privacidad</a>
                                            </label>
                                        </div>
                                    </div>

                                    <button type="submit" class="btn btn-register" id="submitBtn">
                                        <i class="fas fa-user-plus me-2"></i>
                                        Crear Cuenta
                                    </button>
                                    
                                    <div class="text-center mt-4">
                                        <p class="text-muted mb-0">
                                            ¿Ya tienes una cuenta?
                                            <a href="login.php" class="text-decoration-none fw-semibold" style="color: var(--primary-color);">
                                                Inicia Sesión
                                            </a>
                                        </p>
                                    </div>
                                </form>
                            </div>
                        </div>
                        
                        <!-- Lado derecho - Información -->
                        <div class="col-lg-6 d-none d-lg-block">
                            <div class="register-header h-100 d-flex flex-column justify-content-center">
                                <div class="register-icon">
                                    <i class="fas fa-user-plus"></i>
                                </div>
                                <h2 class="mb-3 info-text">Comienza tu Viaje</h2>
                                <p class="mb-4 info-text">Únete a miles de miembros que ya están compartiendo conocimiento</p>
                                
                                <ul class="feature-list">
                                    <li>
                                        <i class="fas fa-rocket"></i>
                                        Acceso inmediato a la comunidad
                                    </li>
                                    <li>
                                        <i class="fas fa-comments"></i>
                                        Participa en discusiones ilimitadas
                                    </li>
                                    <li>
                                        <i class="fas fa-heart"></i>
                                        Guarda tus hilos favoritos
                                    </li>
                                    <li>
                                        <i class="fas fa-bell"></i>
                                        Recibe notificaciones personalizadas
                                    </li>
                                    <li>
                                        <i class="fas fa-chart-line"></i>
                                        Construye tu reputación
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
        document.querySelectorAll('.password-toggle').forEach(toggle => {
            toggle.addEventListener('click', function() {
                const targetId = this.getAttribute('data-target');
                const passwordInput = document.getElementById(targetId);
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
        });

        // Validación de fortaleza de contraseña
        const passwordInput = document.getElementById('password');
        const strengthBar = document.getElementById('passwordStrengthBar');
        const strengthText = document.getElementById('passwordStrengthText');
        const confirmPasswordInput = document.getElementById('confirm_password');
        const passwordMatchText = document.getElementById('passwordMatchText');

        passwordInput.addEventListener('input', function() {
            const password = this.value;
            let strength = 0;
            let text = '';
            let color = '';

            // Validaciones de fortaleza
            if (password.length >= 6) strength += 25;
            if (password.match(/[a-z]/) && password.match(/[A-Z]/)) strength += 25;
            if (password.match(/\d/)) strength += 25;
            if (password.match(/[^a-zA-Z\d]/)) strength += 25;

            // Determinar texto y color
            if (password.length === 0) {
                text = '';
                color = 'transparent';
            } else if (strength < 50) {
                text = 'Débil';
                color = '#ef4444';
            } else if (strength < 75) {
                text = 'Media';
                color = '#f59e0b';
            } else {
                text = 'Fuerte';
                color = '#10b981';
            }

            strengthBar.style.width = strength + '%';
            strengthBar.style.background = color;
            strengthText.textContent = text;
            strengthText.style.color = color;
        });

        // Validación de coincidencia de contraseñas
        confirmPasswordInput.addEventListener('input', function() {
            const password = passwordInput.value;
            const confirmPassword = this.value;

            if (confirmPassword.length === 0) {
                passwordMatchText.textContent = '';
            } else if (password === confirmPassword) {
                passwordMatchText.textContent = '✓ Las contraseñas coinciden';
                passwordMatchText.style.color = '#10b981';
            } else {
                passwordMatchText.textContent = '✗ Las contraseñas no coinciden';
                passwordMatchText.style.color = '#ef4444';
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

        // Validación en tiempo real del formulario
        const form = document.getElementById('registerForm');
        const submitBtn = document.getElementById('submitBtn');

        form.addEventListener('input', function() {
            const password = passwordInput.value;
            const confirmPassword = confirmPasswordInput.value;
            const terms = document.getElementById('terms').checked;
            const username = document.getElementById('username').value;
            const email = document.getElementById('email').value;

            const isValid = password.length >= 6 && 
                           password === confirmPassword && 
                           terms && 
                           username.length >= 3 && 
                           email.includes('@');

            submitBtn.disabled = !isValid;
            submitBtn.style.opacity = isValid ? '1' : '0.6';
            submitBtn.style.cursor = isValid ? 'pointer' : 'not-allowed';
        });

        // Animación de entrada
        document.addEventListener('DOMContentLoaded', function() {
            const formElements = document.querySelectorAll('.register-body > *');
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
    </script>
</body>
</html>