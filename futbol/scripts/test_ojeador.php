<?php
// scripts/create_test_ojeador.php
/**
 * Crea un usuario de prueba 'test_ojeador' con contraseña 'ojeador123'
 * y rol 'ojeador' en la tabla usuarios_futbol.
 * Uso: php scripts/create_test_ojeador.php
 */
require __DIR__ . '/../config/db.php';
$pdo = getConnection(DB_NAME_FUTBOL);

$username = 'test_ojeador';
$password = 'ojeador123';
$hash     = password_hash($password, PASSWORD_DEFAULT);

$stmt = $pdo->prepare("
    INSERT INTO usuarios_futbol (nombre, contrasena, rol)
    VALUES (:nombre, :hash, 'ojeador')
");
$stmt->execute([
    ':nombre' => $username,
    ':hash'   => $hash,
]);

echo "Usuario '{$username}' (rol: ojeador) creado correctamente.\\n\"";