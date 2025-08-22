<?php
// scripts/create_test_ojeador_baloncesto.php
/**
 * Crea un usuario de prueba 'test_ojeador' con contraseña 'ojeador123'
 * y rol 'ojeador' en la tabla usuarios_baloncesto.
 * Uso: php scripts/create_test_ojeador_baloncesto.php
 */

require __DIR__ . '/../config/db.php';
$pdo = getConnection(DB_NAME_BALONCESTO);

$username = 'test_ojeador';
$password = 'ojeador123';
$hash     = password_hash($password, PASSWORD_DEFAULT);

$stmt = $pdo->prepare("
    INSERT INTO usuarios_baloncesto (nombre, contrasena, rol)
    VALUES (:nombre, :hash, 'ojeador')
");

$stmt->execute([
    ':nombre' => $username,
    ':hash'   => $hash,
]);

echo "Usuario '{$username}' (rol: ojeador) creado correctamente en baloncesto.\n";
