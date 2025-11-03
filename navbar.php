<?php
// include 'includes/auth.php'; // QUITAR ESTA LÍNEA si ya se incluye en el archivo principal
?>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container">
        <a class="navbar-brand" href="index.php">
            <i class="fas fa-comments me-2"></i>Foro
        </a>
        
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link" href="index.php">
                        <i class="fas fa-home"></i> Inicio
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="desafios.php">
                        <i class="fas fa-code"></i> Desafíos
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="ranking.php">
                        <i class="fas fa-trophy"></i> Ranking
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="ayuda_ia.php">
                        <i class="fas fa-robot"></i> Asistente IA
                    </a>
                </li>
                <?php if(isLoggedIn()): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="nuevo_hilo.php">
                            <i class="fas fa-plus"></i> Nuevo Hilo
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
            
            <ul class="navbar-nav">
                <?php if(isLoggedIn()): ?>
                    <li class="nav-item">
                        <span class="navbar-text me-3">
                            <i class="fas fa-user"></i> Hola, <?php echo $_SESSION['username']; ?>
                        </span>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="perfil.php">
                            <i class="fas fa-id-card"></i> Mi Perfil
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php">
                            <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
                        </a>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link" href="login.php">
                            <i class="fas fa-sign-in-alt"></i> Iniciar Sesión
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="register.php">
                            <i class="fas fa-user-plus"></i> Registrarse
                        </a>
                    </li>
                <?php endif; ?>
                
                <!-- Enlace a administración para admins -->
                <?php if(isLoggedIn() && isAdmin()): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="admin_desafios.php">
                            <i class="fas fa-cog"></i> Admin Desafíos
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>