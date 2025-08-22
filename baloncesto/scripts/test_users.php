<?php
/**
 * scripts/create_test_users_baloncesto.php
 *------------------------------------------------
 * Crea tres usuarios de prueba en la tabla usuarios_baloncesto:
 *  - test_jugador       (rol: jugador)
 *  - test_principal     (rol: entrenador_principal)
 *  - test_ayudante      (rol: entrenador_ayudante)
 *
 * Uso: php scripts/create_test_users_baloncesto.php
 */

require __DIR__ . '/../config/db.php';
$pdo = getConnection(DB_NAME_BALONCESTO);

$users = [
    ['nombre' => 'test_jugador',   'password' => 'jugador123',   'rol' => 'jugador'],
    ['nombre' => 'test_principal', 'password' => 'principal123', 'rol' => 'entrenador_principal'],
    ['nombre' => 'test_ayudante',  'password' => 'ayudante123',  'rol' => 'entrenador_ayudante'],
];

$insert = $pdo->prepare(
    "INSERT INTO usuarios_baloncesto (nombre, contrasena, rol) 
     VALUES (:nombre, :hash, :rol)"
);

foreach ($users as $u) {
    $hash = password_hash($u['password'], PASSWORD_DEFAULT);
    $insert->execute([
        ':nombre' => $u['nombre'],
        ':hash'   => $hash,
        ':rol'    => $u['rol'],
    ]);
    echo "Usuario '{$u['nombre']}' creado con rol '{$u['rol']}'.\n";
}
