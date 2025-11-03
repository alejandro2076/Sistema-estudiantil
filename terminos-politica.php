<?php
session_start();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Términos y Política de Privacidad - Foro Comunitario</title>
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
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            padding: 2rem 0;
        }
        
        .terms-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .terms-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 2.5rem;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        .terms-header::before {
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
        
        .terms-icon {
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
        
        .terms-body {
            padding: 2.5rem;
            max-height: 70vh;
            overflow-y: auto;
        }
        
        .terms-section {
            margin-bottom: 2rem;
        }
        
        .terms-section h3 {
            color: var(--primary-color);
            border-bottom: 2px solid #e2e8f0;
            padding-bottom: 0.5rem;
            margin-bottom: 1rem;
        }
        
        .btn-back {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border: none;
            color: white;
            padding: 0.75rem 2rem;
            border-radius: 50px;
            font-weight: 600;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .btn-back:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
            color: white;
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
        
        .nav-tabs {
            border-bottom: 2px solid #e2e8f0;
        }
        
        .nav-tabs .nav-link {
            border: none;
            color: #64748b;
            font-weight: 500;
            padding: 1rem 1.5rem;
        }
        
        .nav-tabs .nav-link.active {
            color: var(--primary-color);
            background: transparent;
            border-bottom: 3px solid var(--primary-color);
        }
        
        .tab-content {
            padding-top: 1.5rem;
        }
        
        .small-links {
            font-size: 0.8rem;
            color: #64748b;
            text-align: center;
            margin-top: 1.5rem;
        }
        
        .small-links a {
            color: var(--primary-color);
            text-decoration: none;
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
                <div class="terms-container">
                    <div class="terms-header">
                        <div class="terms-icon">
                            <i class="fas fa-file-contract"></i>
                        </div>
                        <h2 class="mb-3 info-text">Términos y Política de Privacidad</h2>
                        <p class="mb-0 info-text">Foro Comunitario</p>
                    </div>
                    
                    <div class="terms-body">
                        <ul class="nav nav-tabs" id="termsTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="terms-tab" data-bs-toggle="tab" data-bs-target="#terms" type="button" role="tab" aria-controls="terms" aria-selected="true">
                                    <i class="fas fa-scale-balanced me-2"></i>Términos de Uso
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="privacy-tab" data-bs-toggle="tab" data-bs-target="#privacy" type="button" role="tab" aria-controls="privacy" aria-selected="false">
                                    <i class="fas fa-shield-alt me-2"></i>Política de Privacidad
                                </button>
                            </li>
                        </ul>
                        
                        <div class="tab-content" id="termsTabsContent">
                            <div class="tab-pane fade show active" id="terms" role="tabpanel" aria-labelledby="terms-tab">
                                <div class="terms-section">
                                    <h3>1. Aceptación de los Términos</h3>
                                    <p>Al acceder y utilizar el Foro Comunitario, usted acepta cumplir con estos términos de servicio, todas las leyes y regulaciones aplicables, y acepta que es responsable del cumplimiento de las leyes locales aplicables.</p>
                                </div>
                                
                                <div class="terms-section">
                                    <h3>2. Uso de la Cuenta</h3>
                                    <p>Usted es responsable de mantener la confidencialidad de su cuenta y contraseña, y de restringir el acceso a su computadora. Usted acepta la responsabilidad de todas las actividades que ocurran bajo su cuenta o contraseña.</p>
                                </div>
                                
                                <div class="terms-section">
                                    <h3>3. Conducta del Usuario</h3>
                                    <p>Usted se compromete a no utilizar el servicio para:</p>
                                    <ul>
                                        <li>Publicar contenido ilegal, amenazante, difamatorio, obsceno o ofensivo</li>
                                        <li>Violar derechos de propiedad intelectual</li>
                                        <li>Distribuir virus informáticos o código malicioso</li>
                                        <li>Recopilar información de otros usuarios sin su consentimiento</li>
                                        <li>Realizar actividades de spam o publicidad no solicitada</li>
                                    </ul>
                                </div>
                                
                                <div class="terms-section">
                                    <h3>4. Contenido del Usuario</h3>
                                    <p>Usted conserva los derechos de cualquier contenido que envíe, publique o muestre en el Foro Comunitario. Al publicar contenido, nos otorga una licencia mundial no exclusiva para usar, modificar y mostrar dicho contenido en relación con el servicio.</p>
                                </div>
                                
                                <div class="terms-section">
                                    <h3>5. Modificaciones del Servicio</h3>
                                    <p>Nos reservamos el derecho de modificar o discontinuar el servicio (o cualquier parte del mismo) con o sin previo aviso. No seremos responsables ante usted ni ante ningún tercero por cualquier modificación, suspensión o interrupción del servicio.</p>
                                </div>
                                
                                <div class="terms-section">
                                    <h3>6. Terminación</h3>
                                    <p>Podemos terminar o suspender su acceso a nuestro servicio inmediatamente, sin previo aviso o responsabilidad, por cualquier motivo, incluido, entre otros, si incumple los Términos.</p>
                                </div>
                            </div>
                            
                            <div class="tab-pane fade" id="privacy" role="tabpanel" aria-labelledby="privacy-tab">
                                <div class="terms-section">
                                    <h3>1. Información que Recopilamos</h3>
                                    <p>Recopilamos información que usted nos proporciona directamente, incluyendo:</p>
                                    <ul>
                                        <li>Información de registro (nombre, email, etc.)</li>
                                        <li>Contenido que publica en el foro</li>
                                        <li>Información de perfil que elija compartir</li>
                                        <li>Comunicaciones con otros usuarios y con nosotros</li>
                                    </ul>
                                </div>
                                
                                <div class="terms-section">
                                    <h3>2. Cómo Usamos su Información</h3>
                                    <p>Utilizamos la información que recopilamos para:</p>
                                    <ul>
                                        <li>Proporcionar, mantener y mejorar nuestros servicios</li>
                                        <li>Personalizar su experiencia en el foro</li>
                                        <li>Comunicarnos con usted sobre el servicio</li>
                                        <li>Detectar y prevenir actividades fraudulentas</li>
                                        <li>Cumplir con obligaciones legales</li>
                                    </ul>
                                </div>
                                
                                <div class="terms-section">
                                    <h3>3. Compartición de Información</h3>
                                    <p>No vendemos su información personal a terceros. Podemos compartir información en las siguientes circunstancias:</p>
                                    <ul>
                                        <li>Con su consentimiento explícito</li>
                                        <li>Para cumplir con requisitos legales</li>
                                        <li>Para proteger nuestros derechos y seguridad</li>
                                        <li>En conexión con una fusión o venta de negocio</li>
                                    </ul>
                                </div>
                                
                                <div class="terms-section">
                                    <h3>4. Seguridad de la Información</h3>
                                    <p>Implementamos medidas de seguridad para proteger su información personal contra acceso no autorizado, alteración, divulgación o destrucción. Sin embargo, ningún método de transmisión por Internet o almacenamiento electrónico es 100% seguro.</p>
                                </div>
                                
                                <div class="terms-section">
                                    <h3>5. Sus Derechos</h3>
                                    <p>Usted tiene derecho a:</p>
                                    <ul>
                                        <li>Acceder a la información personal que tenemos sobre usted</li>
                                        <li>Solicitar la corrección de información inexacta</li>
                                        <li>Solicitar la eliminación de su información personal</li>
                                        <li>Oponerse al procesamiento de su información</li>
                                        <li>Solicitar la portabilidad de sus datos</li>
                                    </ul>
                                </div>
                                
                                <div class="terms-section">
                                    <h3>6. Cookies y Tecnologías Similares</h3>
                                    <p>Utilizamos cookies y tecnologías similares para rastrear la actividad en nuestro servicio y mantener cierta información. Puede instruir a su navegador para que rechace todas las cookies o para que indique cuándo se envía una cookie.</p>
                                </div>
                                
                                <div class="terms-section">
                                    <h3>7. Cambios a esta Política</h3>
                                    <p>Podemos actualizar nuestra Política de Privacidad periódicamente. Le notificaremos sobre cualquier cambio publicando la nueva Política de Privacidad en esta página y actualizando la fecha de "Última actualización".</p>
                                    <p><strong>Última actualización:</strong> <?php echo date('d/m/Y'); ?></p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="text-center mt-4">
                            <a href="register.php" class="btn btn-back">
                                <i class="fas fa-arrow-left me-2"></i>Volver al registro
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Animación de entrada
        document.addEventListener('DOMContentLoaded', function() {
            const elements = document.querySelectorAll('.terms-section');
            elements.forEach((element, index) => {
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