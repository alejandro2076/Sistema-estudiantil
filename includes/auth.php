<?php
// Iniciar sesión solo si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Evitar redeclaraciones: declarar funciones solo si no existen
if (!function_exists('isLoggedIn')) {
    function isLoggedIn()
    {
        return isset($_SESSION['user_id']);
    }
}

if (!function_exists('isAdmin')) {
    function isAdmin()
    {
        return isset($_SESSION['rol']) && in_array($_SESSION['rol'], ['admin', 'moderador']);
    }
}

if (!function_exists('getUserID')) {
    function getUserID()
    {
        return $_SESSION['user_id'] ?? null;
    }
}

if (!function_exists('getUserRole')) {
    function getUserRole()
    {
        return $_SESSION['rol'] ?? 'usuario';
    }
}

// Funciones para ranking (asumen $db es un objeto PDO válido)
if (!function_exists('actualizarRankingUsuario')) {
    function actualizarRankingUsuario($usuario_id, $db)
    {
        $query = "SELECT 
                  COUNT(DISTINCT s.desafio_id) as desafios_completados,
                  COALESCE(SUM(s.puntos_obtenidos), 0) as total_puntos,
                  COALESCE(AVG(s.tiempo_ejecucion), 0) as tiempo_promedio,
                  CASE 
                      WHEN COALESCE(AVG(s.tiempo_ejecucion), 0) > 0 
                      THEN COALESCE(SUM(s.puntos_obtenidos), 0) / AVG(s.tiempo_ejecucion)
                      ELSE COALESCE(SUM(s.puntos_obtenidos), 0)
                  END as eficiencia
                  FROM soluciones_desafio s 
                  WHERE s.usuario_id = ? AND s.es_correcta = TRUE";

        $stmt = $db->prepare($query);
        $stmt->execute([$usuario_id]);
        $stats = $stmt->fetch(PDO::FETCH_ASSOC);

        $query = "INSERT INTO ranking_usuarios 
                  (usuario_id, total_puntos, desafios_completados, eficiencia, ultima_actualizacion) 
                  VALUES (?, ?, ?, ?, NOW())
                  ON DUPLICATE KEY UPDATE 
                  total_puntos = VALUES(total_puntos),
                  desafios_completados = VALUES(desafios_completados),
                  eficiencia = VALUES(eficiencia),
                  ultima_actualizacion = NOW()";

        $stmt = $db->prepare($query);
        $stmt->execute([
            $usuario_id,
            $stats['total_puntos'] ?? 0,
            $stats['desafios_completados'] ?? 0,
            $stats['eficiencia'] ?? 0
        ]);

        // Intentar recalcular posiciones si la función está disponible
        if (function_exists('recalcularPosicionesRanking')) {
            recalcularPosicionesRanking($db);
        }
    }
}

if (!function_exists('recalcularPosicionesRanking')) {
    function recalcularPosicionesRanking($db)
    {
        // Nota: ROW_NUMBER() requiere MySQL 8+. Si usa MySQL <8, esta consulta debe adaptarse.
        $query = "UPDATE ranking_usuarios r 
                  JOIN (
                      SELECT usuario_id,
                      ROW_NUMBER() OVER (ORDER BY eficiencia DESC, total_puntos DESC, ultima_actualizacion ASC) as nueva_posicion
                      FROM ranking_usuarios
                  ) ranked ON r.usuario_id = ranked.usuario_id
                  SET r.posicion = ranked.nueva_posicion";

        $stmt = $db->prepare($query);
        $stmt->execute();
    }
}

// No se añade cierre PHP para evitar problemas con espacios/headers

