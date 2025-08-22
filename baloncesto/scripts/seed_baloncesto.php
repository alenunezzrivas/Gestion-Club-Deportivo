<?php
// scripts/seed_baloncesto.php
// --------------------------------------------------
// Uso: php scripts/seed_baloncesto.php
// --------------------------------------------------

require __DIR__ . '/../config/db.php';

// 1) Conexión
$pdo = getConnection(DB_NAME_BALONCESTO);

// 2) Definimos un array con 30 “jugadores” de ejemplo
$jugadores = [];
for ($i = 1; $i <= 30; $i++) {
    $posicion = match (true) {
        $i <= 6   => 'Base',        // primeros 6 son bases
        $i <= 12  => 'Escolta',     // siguientes 6 son escoltas
        $i <= 20  => 'Alero',       // siguientes 8 son aleros
        $i <= 26  => 'Ala-pívot',   // siguientes 6 son ala-pívot
        default   => 'Pívot',       // últimos 4 son pivots
    };

    $jugadores[] = [
        'nombre'    => "Jugador_B$i",
        'password'  => "passB$i",
        'posicion'  => $posicion,
        'edad'      => rand(18, 35),
        'altura'    => rand(185, 210), // en cm
        'peso'      => rand(75, 110),  // en kg
        'lesionado' => false,
    ];
}

// 3) Preparar sentencias
$stmtUser = $pdo->prepare("
    INSERT INTO usuarios_baloncesto (nombre, contrasena, rol)
    VALUES (:nombre, :hash, 'jugador')
");
$stmtJugador = $pdo->prepare("
    INSERT INTO jugadores_baloncesto
      (usuario_id, posicion, edad, altura, peso, lesionado)
    VALUES
      (:usuario_id, :posicion, :edad, :altura, :peso, :lesionado)
");

$pdo->beginTransaction();

try {
    foreach ($jugadores as $j) {
        // 3.1 Insertar usuario con hash
        $hash = password_hash($j['password'], PASSWORD_DEFAULT);
        $stmtUser->execute([
            ':nombre' => $j['nombre'],
            ':hash'   => $hash,
        ]);
        $userId = $pdo->lastInsertId();

        // 3.2 Insertar ficha de jugador
        $stmtJugador->execute([
            ':usuario_id' => $userId,
            ':posicion'   => $j['posicion'],
            ':edad'       => $j['edad'],
            ':altura'     => $j['altura'],
            ':peso'       => $j['peso'],
            ':lesionado'  => $j['lesionado'] ? 1 : 0,
        ]);
    }
    $pdo->commit();
    echo "✅ Insertados 30 jugadores en club_baloncesto.\n";

} catch (Exception $e) {
    $pdo->rollBack();
    echo "❌ Error al insertar: " . $e->getMessage() . "\n";
    exit(1);
}
