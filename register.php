<?php
ob_start();

// Si ya hay sesión activa, redirigir
session_start();
if(isset($_SESSION['user_id'])){
    header("Location: index.php");
    exit;
}

// Procesar registro
$errors = [];
$success = false;

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    require_once 'config/database.php';
    
    // Crear conexión
    try {
        $database = new Database();
        $db = $database->getConnection();
    } catch(Exception $e) {
        die("Error de conexión: " . $e->getMessage());
    }
    
    // Sanitizar y validar inputs
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $terms = isset($_POST['terms']);
    
    // Validaciones
    if(empty($username) || strlen($username) < 3) {
        $errors['username'] = "El usuario debe tener al menos 3 caracteres";
    } elseif(!preg_match('/^[a-zA-ZÀ-ÿ]+$/', $username)) {
        $errors['username'] = "El usuario solo puede contener letras (sin números ni caracteres especiales)";
    }
    
    if(empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = "El email no es válido";
    }
    
    if(empty($password) || strlen($password) < 6) {
        $errors['password'] = "La contraseña debe tener al menos 6 caracteres";
    } elseif(!preg_match('/[A-Z]/', $password)) {
        $errors['password'] = "La contraseña debe contener al menos una mayúscula";
    } elseif(!preg_match('/[0-9]/', $password)) {
        $errors['password'] = "La contraseña debe contener al menos un número";
    } elseif(!preg_match('/[^a-zA-Z0-9]/', $password)) {
        $errors['password'] = "La contraseña debe contener al menos un carácter especial";
    }
    
    if($password !== $confirm_password) {
        $errors['confirm_password'] = "Las contraseñas no coinciden";
    }
    
    if(!$terms) {
        $errors['terms'] = "Debes aceptar los términos y condiciones";
    }
    
    // Verificar si el usuario o email ya existen
    if(empty($errors)) {
        try {
            // Verificar si el usuario o email ya existen
            $query = "SELECT id FROM usuarios WHERE username = ? OR email = ? LIMIT 1";
            $stmt = $db->prepare($query);
            
            if(!$stmt) {
                throw new Exception("Error preparando la consulta: " . print_r($db->errorInfo(), true));
            }
            
            $stmt->execute([$username, $email]);
            
            if($stmt->fetch()) {
                $errors['general'] = "El usuario o email ya están registrados";
            } else {
                // Crear hash seguro de la contraseña
                $hashed_password = password_hash($password, PASSWORD_DEFAULT, ['cost' => 12]);
                
                // Insertar nuevo usuario
                $query = "INSERT INTO usuarios (username, email, password, fecha_registro) 
                         VALUES (?, ?, ?, NOW())";
                $stmt = $db->prepare($query);
                
                if(!$stmt) {
                    throw new Exception("Error preparando inserción: " . print_r($db->errorInfo(), true));
                }
                
                // Depurar valores antes de insertar
                error_log("Insertando usuario: $username, $email");
                
                $result = $stmt->execute([$username, $email, $hashed_password]);
                
                if($result) {
                    // Obtener el ID del usuario recién registrado
                    $user_id = $db->lastInsertId();
                    
                    // Insertar en ranking_usuarios
                    $ranking_query = "INSERT INTO ranking_usuarios (usuario_id, total_puntos, desafios_completados, eficiencia) 
                                     VALUES (?, 0, 0, 0)";
                    $ranking_stmt = $db->prepare($ranking_query);
                    $ranking_stmt->execute([$user_id]);
                    
                    ob_end_clean();
                    header("Location: login.php?registro=exitoso&nuevo=1");
                    exit;
                } else {
                    $errorInfo = $stmt->errorInfo();
                    error_log("Error en execute: " . print_r($errorInfo, true));
                    $errors['general'] = "Error al registrar el usuario. Código: " . $errorInfo[0];
                }
            }
        } catch(PDOException $e) {
            error_log("Error PDO en registro: " . $e->getMessage());
            $errors['general'] = "Error en la base de datos. Por favor, intenta más tarde.";
        } catch(Exception $e) {
            error_log("Error general en registro: " . $e->getMessage());
            $errors['general'] = "Error en el servidor: " . $e->getMessage();
        }
    }
    
    // Depurar errores
    if(!empty($errors)) {
        error_log("Errores de registro: " . print_r($errors, true));
    }
}

// Mantener valores del formulario
$formData = [
    'username' => $_POST['username'] ?? '',
    'email' => $_POST['email'] ?? ''
];
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Regístrate en nuestro foro comunitario">
    <title>Registro - Foro Comunitario</title>
    
    <!-- Preload de recursos -->
    <link rel="preload" href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" as="style">
    
    <!-- CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- CSS crítico inline -->
    <style>
        :root {
            --primary-color: #667eea;
            --secondary-color: #764ba2;
            --success-color: #10b981;
            --warning-color: #f59e0b;
            --error-color: #ef4444;
        }
        
        body {
            background-image: url('img/r.jpg');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            min-height: 100vh;
            margin: 0;
            font-family: system-ui, -apple-system, 'Segoe UI', sans-serif;
            display: flex;
            align-items: center;
        }
        
        .register-container {
            background: rgba(255, 255, 255, 0.74);
            border-radius: 16px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            max-width: 1200px;
            margin: 1rem auto;
        }
        
        .register-header {
            background-image: url('img/r.jpg');
            background-size: cover;
            background-position: center;
            color: white;
            padding: 2rem;
            position: relative;
        }
        
        .register-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1;
        }
        
        .register-header > * {
            position: relative;
            z-index: 2;
        }
        
        .register-icon {
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
        
        .register-body {
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
            margin-bottom: 1rem;
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
        
        .btn-register {
            background: linear-gradient(135deg, var(--success-color), #059669);
            border: none;
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            font-weight: 600;
            width: 100%;
            cursor: pointer;
            transition: transform 0.2s ease;
            margin-top: 1rem;
        }
        
        .btn-register:hover:not(:disabled) {
            transform: translateY(-1px);
        }
        
        .btn-register:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        
        .alert {
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1.5rem;
            animation: fadeIn 0.3s ease;
        }
        
        .alert-danger {
            background: rgba(239, 68, 68, 0.1);
            border-left: 4px solid var(--error-color);
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
            transition: width 0.3s ease;
            border-radius: 2px;
        }
        
        .password-strength-text {
            font-size: 0.75rem;
            margin-top: 0.25rem;
            text-align: right;
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
        
        /* Responsive */
        @media (max-width: 991.98px) {
            .register-container {
                margin: 0.5rem;
            }
            
            .register-body, .register-header {
                padding: 1.5rem;
            }
        }
        
        @media (max-width: 767.98px) {
            body {
                padding: 1rem;
            }
            
            .register-body, .register-header {
                padding: 1rem;
            }
        }
        
        /* Progress steps */
        .progress-step {
            display: flex;
            align-items: center;
            margin-bottom: 0.5rem;
            color: #64748b;
            font-size: 0.9rem;
        }
        
        .progress-step.active {
            color: var(--primary-color);
            font-weight: 600;
        }
        
        .step-number {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            background: #e2e8f0;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 0.5rem;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .progress-step.active .step-number {
            background: var(--primary-color);
            color: white;
        }
        
        /* Loading */
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
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-12 col-lg-10">
                <div class="register-container">
                    <div class="row g-0">
                        <!-- Formulario de Registro -->
                        <div class="col-lg-6">
                            <div class="register-body">
                                <h3 class="text-center mb-4 text-primary">Únete a Nuestra Comunidad</h3>
                                
                                <!-- Mensajes especiales -->
                                <?php if(isset($_GET['motivo']) && $_GET['motivo'] == 'limite_intentos'): ?>
                                    <div class="alert alert-warning">
                                        <div class="d-flex align-items-center">
                                            <i class="fas fa-exclamation-triangle me-3"></i>
                                            <div>
                                                <h6 class="alert-heading mb-2">¡Límite de intentos alcanzado!</h6>
                                                <p class="mb-0">Regístrate para tener acceso ilimitado.</p>
                                            </div>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                
                                <!-- Errores generales -->
                                <?php if(isset($errors['general'])): ?>
                                    <div class="alert alert-danger">
                                        <i class="fas fa-exclamation-triangle me-2"></i>
                                        <strong>Error:</strong> <?php echo htmlspecialchars($errors['general']); ?>
                                    </div>
                                <?php endif; ?>

                                <!-- Formulario de registro -->
                                <form method="POST" id="registerForm" novalidate>
                                    <!-- Username -->
                                    <div class="mb-3">
                                        <label for="username" class="form-label fw-semibold">Nombre de Usuario</label>
                                        <div class="input-group">
                                            <i class="input-icon fas fa-user"></i>
                                            <input type="text" 
                                                   class="form-control <?php echo isset($errors['username']) ? 'is-invalid' : ''; ?>" 
                                                   id="username" 
                                                   name="username" 
                                                   value="<?php echo htmlspecialchars($formData['username']); ?>"
                                                   placeholder="Ejemplo = Jose"
                                                   required
                                                   pattern="[a-zA-ZÀ-ÿ]+"
                                                   minlength="3"
                                                   maxlength="50"
                                                   title="Solo letras (sin números ni caracteres especiales)">
                                            <?php if(isset($errors['username'])): ?>
                                                <div class="invalid-feedback"><?php echo $errors['username']; ?></div>
                                            <?php endif; ?>
                                        </div>
                                        <small class="text-muted"></small>
                                        <div class="mt-1">
                                            <small class="text-muted">
                                                <i class="fas fa-check text-success"> jose </i> 
                                                <br>
                                                <i class="fas fa-times text-danger"> jose123 </i>
                                            </small>
                                        </div>
                                    </div>
                                    
                                    <!-- Email -->
                                    <div class="mb-3">
                                        <label for="email" class="form-label fw-semibold">Correo Electrónico</label>
                                        <div class="input-group">
                                            <i class="input-icon fas fa-envelope"></i>
                                            <input type="email" 
                                                   class="form-control <?php echo isset($errors['email']) ? 'is-invalid' : ''; ?>" 
                                                   id="email" 
                                                   name="email" 
                                                   value="<?php echo htmlspecialchars($formData['email']); ?>"
                                                   placeholder="tu@email.com"
                                                   required>
                                            <?php if(isset($errors['email'])): ?>
                                                <div class="invalid-feedback"><?php echo $errors['email']; ?></div>
                                            <?php endif; ?>
                                        </div>
                                        <small class="text-muted">Te enviaremos un email de confirmación</small>
                                    </div>
                                    
                                    <!-- Contraseña -->
                                    <div class="mb-3">
                                        <label for="password" class="form-label fw-semibold">Contraseña</label>
                                        <div class="input-group">
                                            <i class="input-icon fas fa-lock"></i>
                                            <input type="password" 
                                                   class="form-control <?php echo isset($errors['password']) ? 'is-invalid' : ''; ?>" 
                                                   id="password" 
                                                   name="password" 
                                                   placeholder="Mínimo 6 caracteres"
                                                   required
                                                   minlength="6">
                                            <button type="button" class="password-toggle" data-target="password">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <?php if(isset($errors['password'])): ?>
                                                <div class="invalid-feedback"><?php echo $errors['password']; ?></div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="password-strength">
                                            <div class="password-strength-bar" id="passwordStrengthBar"></div>
                                        </div>
                                        <div class="password-strength-text" id="passwordStrengthText"></div>
                                        <small class="text-muted">Debe incluir: mayúscula, número y carácter especial</small>
                                    </div>

                                    <!-- Confirmar Contraseña -->
                                    <div class="mb-3">
                                        <label for="confirm_password" class="form-label fw-semibold">Confirmar Contraseña</label>
                                        <div class="input-group">
                                            <i class="input-icon fas fa-lock"></i>
                                            <input type="password" 
                                                   class="form-control <?php echo isset($errors['confirm_password']) ? 'is-invalid' : ''; ?>" 
                                                   id="confirm_password" 
                                                   name="confirm_password" 
                                                   placeholder="Repite tu contraseña"
                                                   required>
                                            <button type="button" class="password-toggle" data-target="confirm_password">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <?php if(isset($errors['confirm_password'])): ?>
                                                <div class="invalid-feedback"><?php echo $errors['confirm_password']; ?></div>
                                            <?php endif; ?>
                                        </div>
                                        <small class="text-muted" id="passwordMatchText"></small>
                                    </div>

                                    <!-- Términos y condiciones -->
                                    <div class="mb-4">
                                        <div class="form-check">
                                            <input class="form-check-input <?php echo isset($errors['terms']) ? 'is-invalid' : ''; ?>" 
                                                   type="checkbox" 
                                                   id="terms" 
                                                   name="terms"
                                                   required>
                                            <label class="form-check-label" for="terms">
                                                Acepto los <a href="terminos-politica.php" class="text-primary" target="_blank">Términos</a> y <a href="terminos-politica.php" class="text-primary" target="_blank">Privacidad</a>
                                            </label>
                                            <?php if(isset($errors['terms'])): ?>
                                                <div class="invalid-feedback"><?php echo $errors['terms']; ?></div>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <!-- Botón de registro -->
                                    <button type="submit" class="btn btn-register" id="submitBtn">
                                        <i class="fas fa-user-plus me-2"></i>
                                        Crear Cuenta
                                    </button>
                                    
                                    <!-- Enlace a login -->
                                    <div class="text-center mt-4">
                                        <p class="text-muted mb-0">
                                            ¿Ya tienes una cuenta?
                                            <a href="login.php" class="text-decoration-none fw-semibold text-primary">
                                                Inicia Sesión
                                            </a><hr>
                                            <a href="index.php" class="text-decoration-none fw-semibold text-primary">
                                                Volver al Inicio
                                            </a>
                                        </p>
                                    </div>
                                </form>
                            </div>
                        </div>
                        
                        <!-- Información del Foro -->
                        <div class="col-lg-6 d-none d-lg-block">
                            <div class="register-header h-100 d-flex flex-column justify-content-center">
                                <div class="register-icon">
                                    <i class="fas fa-user-plus"></i>
                                </div>
                                <h2 class="text-center mb-3">Comienza tu Viaje</h2>
                                <p class="text-center mb-4">Únete a nuestra comunidad</p>
                                
                                <ul class="feature-list">
                                    <li>
                                        <i class="fas fa-rocket"></i>
                                        Acceso inmediato a todos los desafíos
                                    </li>
                                    <li>
                                        <i class="fas fa-code"></i>
                                        Practica programación en PHP
                                    </li>
                                    <li>
                                        <i class="fas fa-trophy"></i>
                                        Participa en el ranking
                                    </li>
                                    <li>
                                        <i class="fas fa-comments"></i>
                                        Discute con otros programadores
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
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('registerForm');
            const submitBtn = document.getElementById('submitBtn');
            const passwordInput = document.getElementById('password');
            const confirmPasswordInput = document.getElementById('confirm_password');
            const usernameInput = document.getElementById('username');
            const strengthBar = document.getElementById('passwordStrengthBar');
            const strengthText = document.getElementById('passwordStrengthText');
            const matchText = document.getElementById('passwordMatchText');
            
            // Función para mostrar/ocultar contraseña
            document.querySelectorAll('.password-toggle').forEach(toggle => {
                toggle.addEventListener('click', function() {
                    const targetId = this.getAttribute('data-target');
                    const targetInput = document.getElementById(targetId);
                    const icon = this.querySelector('i');
                    
                    if(targetInput.type === 'password') {
                        targetInput.type = 'text';
                        icon.classList.remove('fa-eye');
                        icon.classList.add('fa-eye-slash');
                    } else {
                        targetInput.type = 'password';
                        icon.classList.remove('fa-eye-slash');
                        icon.classList.add('fa-eye');
                    }
                });
            });
            
            // Validar username (solo letras)
            usernameInput.addEventListener('input', function() {
                const username = this.value;
                
                // Remover cualquier número o carácter especial
                const cleaned = username.replace(/[^a-zA-ZÀ-ÿ]/g, '');
                
                if (username !== cleaned) {
                    this.value = cleaned;
                }
                
                validateField(this);
                updateSubmitButton();
            });
            
            // Validar fortaleza de contraseña
            passwordInput.addEventListener('input', function() {
                const password = this.value;
                let strength = 0;
                let text = '';
                let color = '';
                
                // Criterios de fortaleza
                if(password.length >= 8) strength += 25;
                if(/[A-Z]/.test(password)) strength += 25;
                if(/\d/.test(password)) strength += 25;
                if(/[^A-Za-z0-9]/.test(password)) strength += 25;
                
                // Determinar nivel
                if(password.length === 0) {
                    text = '';
                    color = 'transparent';
                } else if(strength < 50) {
                    text = 'Débil';
                    color = '#ef4444';
                } else if(strength < 75) {
                    text = 'Media';
                    color = '#f59e0b';
                } else {
                    text = 'Fuerte';
                    color = '#10b981';
                }
                
                // Actualizar UI
                strengthBar.style.width = strength + '%';
                strengthBar.style.backgroundColor = color;
                strengthText.textContent = text;
                strengthText.style.color = color;
                
                // Validar coincidencia
                validatePasswordMatch();
            });
            
            // Validar coincidencia de contraseñas
            confirmPasswordInput.addEventListener('input', validatePasswordMatch);
            
            function validatePasswordMatch() {
                const password = passwordInput.value;
                const confirmPassword = confirmPasswordInput.value;
                
                if(confirmPassword.length === 0) {
                    matchText.textContent = '';
                    confirmPasswordInput.classList.remove('is-valid', 'is-invalid');
                } else if(password === confirmPassword) {
                    matchText.textContent = '✓ Las contraseñas coinciden';
                    matchText.style.color = '#10b981';
                    confirmPasswordInput.classList.add('is-valid');
                    confirmPasswordInput.classList.remove('is-invalid');
                } else {
                    matchText.textContent = '✗ Las contraseñas no coinciden';
                    matchText.style.color = '#ef4444';
                    confirmPasswordInput.classList.add('is-invalid');
                    confirmPasswordInput.classList.remove('is-valid');
                }
            }
            
            // Validación en tiempo real
            form.addEventListener('input', function(e) {
                const target = e.target;
                if(target.tagName === 'INPUT') {
                    validateField(target);
                }
                updateSubmitButton();
            });
            
            // Validación al perder foco
            form.addEventListener('focusout', function(e) {
                if(e.target.tagName === 'INPUT') {
                    validateField(e.target);
                }
            });
            
            // Validar campo individual
            function validateField(field) {
                field.classList.remove('is-invalid', 'is-valid');
                
                if(field.required && !field.value.trim()) {
                    field.classList.add('is-invalid');
                    return false;
                }
                
                // Validaciones específicas
                if(field.id === 'username') {
                    if(field.value.length < 3) {
                        field.classList.add('is-invalid');
                        return false;
                    }
                    
                    // Validar que solo contenga letras
                    const lettersOnly = /^[a-zA-ZÀ-ÿ]+$/;
                    if(!lettersOnly.test(field.value)) {
                        field.classList.add('is-invalid');
                        return false;
                    }
                }
                
                if(field.id === 'email' && !/\S+@\S+\.\S+/.test(field.value)) {
                    field.classList.add('is-invalid');
                    return false;
                }
                
                if(field.id === 'password') {
                    if(field.value.length < 6) {
                        field.classList.add('is-invalid');
                        return false;
                    }
                }
                
                field.classList.add('is-valid');
                return true;
            }
            
            // Actualizar estado del botón de envío
            function updateSubmitButton() {
                const inputs = form.querySelectorAll('input[required]');
                const terms = document.getElementById('terms');
                let isValid = true;
                
                inputs.forEach(input => {
                    if(!input.value.trim() || input.classList.contains('is-invalid')) {
                        isValid = false;
                    }
                });
                
                if(!terms.checked) {
                    isValid = false;
                }
                
                submitBtn.disabled = !isValid;
                submitBtn.style.opacity = isValid ? '1' : '0.6';
            }
            
            // Enviar formulario
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                
                // Validar todos los campos
                const inputs = form.querySelectorAll('input[required]');
                let isValid = true;
                
                inputs.forEach(input => {
                    if(!validateField(input)) {
                        isValid = false;
                    }
                });
                
                // Validar términos
                const terms = document.getElementById('terms');
                if(!terms.checked) {
                    terms.classList.add('is-invalid');
                    isValid = false;
                }
                
                if(isValid) {
                    // Mostrar loading
                    submitBtn.disabled = true;
                    submitBtn.classList.add('loading');
                    submitBtn.innerHTML = '';
                    
                    // Enviar formulario
                    this.submit();
                }
            });
            
            // Inicializar validación
            updateSubmitButton();
        });
    </script>
</body>
</html>