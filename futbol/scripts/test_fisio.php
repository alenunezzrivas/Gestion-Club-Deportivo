<?php
// scripts/create_test_fisioterapeuta.php
/**
 * Crea un usuario de prueba 'test_fisioterapeuta' con contraseña 'fisio123'
 * y rol 'fisioterapeuta' en la tabla usuarios_futbol.
 * Uso: php scripts/create_test_fisioterapeuta.php
 */
require __DIR__ . '/../config/db.php';

$pdo = getConnection(DB_NAME_FUTBOL);

$username = 'test_fisioterapeuta';
$password = 'fisio123';
$hash     = password_hash($password, PASSWORD_DEFAULT);

$stmt = $pdo->prepare("
    INSERT INTO usuarios_futbol (nombre, contrasena, rol)
    VALUES (:nombre, :hash, 'fisioterapeuta')
");
$stmt->execute([
    ':nombre' => $username,
    ':hash'   => $hash,
]);

echo "Usuario '{$username}' (rol: fisioterapeuta) creado correctamente.\n";
