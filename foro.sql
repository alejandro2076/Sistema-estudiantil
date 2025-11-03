-- Base de datos del foro
CREATE DATABASE foro;
USE foro;

-- Tabla de usuarios
CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    rol ENUM('usuario', 'moderador', 'admin') DEFAULT 'usuario'
);

-- Tabla de categorías
CREATE TABLE categorias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    descripcion TEXT
);

-- Tabla de hilos
CREATE TABLE hilos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(255) NOT NULL,
    contenido TEXT NOT NULL,
    usuario_id INT,
    categoria_id INT,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ultima_respuesta TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id),
    FOREIGN KEY (categoria_id) REFERENCES categorias(id)
);

-- Tabla de respuestas
CREATE TABLE respuestas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    contenido TEXT NOT NULL,
    usuario_id INT,
    hilo_id INT,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id),
    FOREIGN KEY (hilo_id) REFERENCES hilos(id)
);

-- Insertar algunas categorías de ejemplo
INSERT INTO categorias (nombre, descripcion) VALUES 
('General', 'Discusiones generales sobre el foro'),
('Ayuda', 'Pide ayuda sobre cualquier tema'),
('Programación', 'Todo sobre programación y desarrollo');

-- Tabla para desafíos/problemas de programación
CREATE TABLE desafios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(255) NOT NULL,
    descripcion TEXT NOT NULL,
    codigo_base TEXT,
    categoria_id INT,
    dificultad ENUM('facil', 'medio', 'dificil') DEFAULT 'medio',
    puntos INT DEFAULT 100,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (categoria_id) REFERENCES categorias(id)
);

-- Tabla para soluciones de usuarios
CREATE TABLE soluciones_desafio (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT,
    desafio_id INT,
    codigo_solucion TEXT NOT NULL,
    tiempo_ejecucion DECIMAL(10,4), -- tiempo en segundos
    memoria_utilizada INT, -- memoria en KB
    puntos_obtenidos INT,
    fecha_envio TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    es_correcta BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id),
    FOREIGN KEY (desafio_id) REFERENCES desafios(id)
);

-- Tabla para casos de prueba
CREATE TABLE casos_prueba (
    id INT AUTO_INCREMENT PRIMARY KEY,
    desafio_id INT,
    entrada TEXT NOT NULL,
    salida_esperada TEXT NOT NULL,
    es_visible BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (desafio_id) REFERENCES desafios(id)
);

-- Agregar estas tablas a tu foro.sql existente

-- Tabla para ranking (mejorada)
CREATE TABLE IF NOT EXISTS ranking_usuarios (
    usuario_id INT PRIMARY KEY,
    total_puntos INT DEFAULT 0,
    desafios_completados INT DEFAULT 0,
    eficiencia DECIMAL(10,4) DEFAULT 0,
    posicion INT DEFAULT 0,
    ultima_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
);

-- Insertar algunos desafíos de ejemplo
INSERT INTO desafios (titulo, descripcion, categoria_id, dificultad, puntos) VALUES 
('Hola Mundo en PHP', 'Crea un programa que imprima "¡Hola, Mundo!" en PHP.', 3, 'facil', 50),
('Suma de Dos Números', 'Escribe una función que reciba dos números y devuelva su suma.', 3, 'facil', 75),
('Factorial Recursivo', 'Implementa una función recursiva que calcule el factorial de un número.', 3, 'medio', 150),
('Verificar Palíndromo', 'Crea una función que determine si una cadena es un palíndromo.', 3, 'medio', 125),
('Ordenamiento Burbuja', 'Implementa el algoritmo de ordenamiento burbuja en PHP.', 3, 'dificil', 200);

-- Insertar casos de prueba para los desafíos
INSERT INTO casos_prueba (desafio_id, entrada, salida_esperada, es_visible) VALUES
(1, '', '¡Hola, Mundo!', TRUE),
(2, '5,3', '8', TRUE),
(2, '10,-2', '8', FALSE),
(3, '5', '120', TRUE),
(4, 'anilina', 'true', TRUE),
(4, 'php', 'false', TRUE);