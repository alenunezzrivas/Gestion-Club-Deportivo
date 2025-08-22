<?php
// config/db.php

// Datos de conexión
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME_FUTBOL', 'club_futbol');
define('DB_NAME_BALONCESTO', 'club_baloncesto');

/**
 * Obtiene la conexión PDO a la base de datos indicada.
 *
 * @param string $dbname Nombre de la base de datos (constante DB_NAME_FUTBOL o DB_NAME_BALONCESTO)
 * @return PDO
 */
function getConnection($dbname) {
    $dsn = "mysql:host=" . DB_HOST . ";dbname={$dbname};charset=utf8mb4";
    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        return $pdo;
    } catch (PDOException $e) {
        // En producción podrías registrar el error en un log
        die("Error de conexión a la base de datos: " . $e->getMessage());
    }
}

?>