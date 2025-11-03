<?php
include_once 'includes/auth.php';
include_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Asistente IA - Foro Comunitario</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .ai-container {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 2rem 0;
        }
        
        .ai-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            backdrop-filter: blur(10px);
        }
        
        .message-container {
            height: 400px;
            overflow-y: auto;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 1rem;
            background: #f8f9fa;
        }
        
        .user-message {
            background: #007bff;
            color: white;
            border-radius: 15px 15px 5px 15px;
            padding: 0.75rem 1rem;
            margin: 0.5rem 0;
            max-width: 80%;
            margin-left: auto;
        }
        
        .ai-message {
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 15px 15px 15px 5px;
            padding: 0.75rem 1rem;
            margin: 0.5rem 0;
            max-width: 80%;
        }
        
        .code-block {
            background: #2d3748;
            color: #e2e8f0;
            padding: 1rem;
            border-radius: 8px;
            font-family: 'Courier New', monospace;
            font-size: 0.9rem;
            margin: 0.5rem 0;
            overflow-x: auto;
        }
        
        .suggestion-item {
            border-left: 3px solid #007bff;
            padding-left: 1rem;
            margin: 0.5rem 0;
        }
        
        .ai-icon {
            color: #667eea;
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>
    
    <div class="ai-container">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-10">
                    <div class="ai-card p-4">
                        <div class="text-center mb-4">
                            <div class="ai-icon mb-3">
                                <i class="fas fa-robot fa-3x"></i>
                            </div>
                            <h2>Asistente IA para Programación</h2>
                            <p class="text-muted">Obtén ayuda instantánea con tus problemas de código</p>
                        </div>
                        
                        <div class="message-container mb-3" id="messageContainer">
                            <div class="ai-message">
                                <strong><i class="fas fa-robot text-primary"></i> Asistente IA:</strong>
                                <p class="mb-0">¡Hola! Soy tu asistente de programación. Puedo ayudarte a:</p>
                                <ul class="mb-0">
                                    <li>Revisar errores en tu código PHP</li>
                                    <li>Sugerir mejoras de sintaxis</li>
                                    <li>Explicar conceptos de programación</li>
                                    <li>Optimizar algoritmos simples</li>
                                    <li>Revisar seguridad y buenas prácticas</li>
                                </ul>
                                <p class="mt-2 mb-0">Pega tu código PHP abajo y te ayudaré a mejorarlo.</p>
                            </div>
                        </div>
                        
                        <form id="aiForm">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Tu Código PHP:</label>
                                <textarea class="form-control" id="codigoInput" rows="8" 
                                          placeholder="Pega tu código PHP aquí..." required></textarea>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label fw-bold">¿Qué problema tienes?</label>
                                <select class="form-select" id="problemaTipo">
                                    <option value="revision">Revisión general</option>
                                    <option value="error">Error de sintaxis</option>
                                    <option value="optimizacion">Optimización</option>
                                    <option value="explicacion">Explicación de código</option>
                                    <option value="seguridad">Revisión de seguridad</option>
                                </select>
                            </div>
                            
                            <button type="submit" class="btn btn-primary w-100" id="submitBtn">
                                <i class="fas fa-paper-plane me-2"></i>
                                Obtener Ayuda de la IA
                            </button>
                        </form>
                        
                        <div class="mt-4">
                            <h5><i class="fas fa-lightbulb text-warning"></i> Consejos Rápidos</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="suggestion-item">
                                        <strong>Validación de Entradas</strong>
                                        <p class="mb-0 small">Siempre valida y sanitiza las entradas del usuario con <code>htmlspecialchars()</code> y <code>trim()</code>.</p>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="suggestion-item">
                                        <strong>Manejo de Errores</strong>
                                        <p class="mb-0 small">Usa <code>try-catch</code> para operaciones que puedan fallar, como consultas a la base de datos.</p>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="suggestion-item">
                                        <strong>Seguridad</strong>
                                        <p class="mb-0 small">Nunca confíes en datos del usuario. Usa consultas preparadas para prevenir SQL injection.</p>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="suggestion-item">
                                        <strong>Performance</strong>
                                        <p class="mb-0 small">Evita consultas dentro de bucles. Mejor obtener todos los datos necesarios de una vez.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('aiForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const codigo = document.getElementById('codigoInput').value;
            const problema = document.getElementById('problemaTipo').value;
            const submitBtn = document.getElementById('submitBtn');
            const messageContainer = document.getElementById('messageContainer');
            
            if (!codigo.trim()) {
                alert('Por favor, ingresa tu código PHP');
                return;
            }
            
            // Mostrar mensaje del usuario
            const userMessage = document.createElement('div');
            userMessage.className = 'user-message';
            userMessage.innerHTML = `<strong>Tú:</strong><br><pre class="mb-0">${codigo.substring(0, 200)}${codigo.length > 200 ? '...' : ''}</pre>`;
            messageContainer.appendChild(userMessage);
            
            // Deshabilitar botón
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Procesando...';
            
            // Simular respuesta de IA (en un sistema real, aquí harías una llamada a una API)
            setTimeout(() => {
                const respuesta = generarRespuestaIA(codigo, problema);
                
                const aiMessage = document.createElement('div');
                aiMessage.className = 'ai-message';
                aiMessage.innerHTML = `<strong><i class="fas fa-robot text-primary"></i> Asistente IA:</strong>${respuesta}`;
                messageContainer.appendChild(aiMessage);
                
                // Restaurar botón
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-paper-plane me-2"></i>Obtener Ayuda de la IA';
                
                // Scroll al final
                messageContainer.scrollTop = messageContainer.scrollHeight;
            }, 2000);
        });
        
        function generarRespuestaIA(codigo, problema) {
            // Análisis básico del código (simulado)
            const tieneEcho = codigo.includes('echo') || codigo.includes('print');
            const tieneHtmlspecialchars = codigo.includes('htmlspecialchars');
            const tieneTryCatch = codigo.includes('try') && codigo.includes('catch');
            const tienePDO = codigo.includes('PDO');
            const lineas = codigo.split('\n').length;
            
            let respuesta = '';
            
            switch(problema) {
                case 'revision':
                    respuesta = `<p>He revisado tu código de ${lineas} líneas:</p>`;
                    if (!tieneHtmlspecialchars && tieneEcho) {
                        respuesta += `<div class="code-block">// Sugerencia: Considera usar htmlspecialchars() para output seguro<br>echo htmlspecialchars($variable, ENT_QUOTES, 'UTF-8');</div>`;
                    }
                    if (!tieneTryCatch && tienePDO) {
                        respuesta += `<div class="code-block">// Sugerencia: Manejo de errores con PDO<br>try {<br>    // tu código PDO<br>} catch(PDOException $e) {<br>    error_log("Error: " . $e->getMessage());<br>}</div>`;
                    }
                    if (codigo.includes('mysql_')) {
                        respuesta += `<div class="alert alert-danger mt-2">⚠️ <strong>Advertencia:</strong> Las funciones mysql_* están obsoletas. Usa PDO o MySQLi.</div>`;
                    }
                    break;
                    
                case 'error':
                    respuesta = `<p>Basado en análisis de sintaxis:</p>`;
                    if (!codigo.includes('<?php') && codigo.includes('$')) {
                        respuesta += `<div class="alert alert-warning">Parece que falta la etiqueta de apertura PHP <code>&lt;?php</code></div>`;
                    }
                    if (codigo.includes('mysql_') && !codigo.includes('PDO') && !codigo.includes('mysqli')) {
                        respuesta += `<div class="alert alert-danger">Las funciones mysql_* están obsoletas. Usa PDO o MySQLi.</div>`;
                    }
                    if (codigo.includes('$') && !codigo.includes('=')) {
                        respuesta += `<div class="alert alert-info">Asegúrate de que todas las variables estén inicializadas antes de usarlas.</div>`;
                    }
                    break;
                    
                case 'optimizacion':
                    respuesta = `<p>Sugerencias de optimización:</p>`;
                    if (codigo.includes('SELECT *')) {
                        respuesta += `<div class="code-block">// En lugar de SELECT *, especifica las columnas:<br>SELECT id, nombre, email FROM usuarios</div>`;
                    }
                    if (codigo.includes('for') && codigo.includes('count(')) {
                        respuesta += `<div class="code-block">// Precalcula el count fuera del bucle:<br>$total = count($array);<br>for($i = 0; $i < $total; $i++) { ... }</div>`;
                    }
                    break;
                    
                case 'explicacion':
                    respuesta = `<p>Explicación del código:</p>`;
                    if (codigo.includes('function')) {
                        respuesta += `<p>Tu código define una función personalizada. Las funciones ayudan a organizar y reutilizar código.</p>`;
                    }
                    if (codigo.includes('class')) {
                        respuesta += `<p>Se está utilizando programación orientada a objetos (POO) con clases.</p>`;
                    }
                    break;
                    
                case 'seguridad':
                    respuesta = `<p>Revisión de seguridad:</p>`;
                    if (!tieneHtmlspecialchars && tieneEcho) {
                        respuesta += `<div class="alert alert-warning">⚠️ <strong>XSS:</strong> Usa htmlspecialchars() para prevenir ataques XSS</div>`;
                    }
                    if (codigo.includes('$_GET') || codigo.includes('$_POST') || codigo.includes('$_REQUEST')) {
                        respuesta += `<div class="code-block">// Siempre valida entradas:<br>$input = filter_input(INPUT_GET, 'param', FILTER_SANITIZE_STRING);<br>if ($input === false) { /* manejar error */ }</div>`;
                    }
                    break;
            }
            
            // Consejo general
            respuesta += `<div class="mt-3 p-3 bg-light rounded">
                <strong>💡 Consejo:</strong> Siempre prueba tu código en un entorno de desarrollo antes de producción.
            </div>`;
            
            return respuesta;
        }
        
        // Auto-scroll al cargar
        document.addEventListener('DOMContentLoaded', function() {
            const messageContainer = document.getElementById('messageContainer');
            messageContainer.scrollTop = messageContainer.scrollHeight;
        });
    </script>
</body>
</html>