<?php
// scripts/seed_futbol.php
// --------------------------------------------------
// Uso: php seed_futbol.php
// --------------------------------------------------

require __DIR__ . '/../config/db.php';

// 1) Conexión
$pdo = getConnection(DB_NAME_FUTBOL);

// 2) Definimos un array con 30 jugadores
$jugadores = [];
for ($i = 1; $i <= 30; $i++) {
    // Puedes variar aquí si quieres contraseñas distintas
    $jugadores[] = [
        'nombre'    => "Jugador $i",
        'password'  => "pass$i",
        // Distribuimos posiciones
        'posicion'  => match (true) {
            $i <= 3   => 'Portero',
            $i <= 15  => 'Defensa',
            $i <= 25  => 'Mediocentro',
            default   => 'Delantero',
        },
        // Datos de ejemplo; cámbialos si lo deseas
        'edad'      => rand(20, 30),
        'altura'    => rand(175, 190),
        'peso'      => rand(70, 90),
        'lesionado' => false,
    ];
}

// 3) Preparar sentencias
$stmtUser = $pdo->prepare("
    INSERT INTO usuarios_futbol (nombre, contrasena, rol)
    VALUES (:nombre, :hash, 'jugador')
");
$stmtJugador = $pdo->prepare("
    INSERT INTO jugadores_futbol
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
    echo "✅ Insertados {$i} jugadores en club_futbol.\n";

} catch (Exception $e) {
    $pdo->rollBack();
    echo "❌ Error al insertar: " . $e->getMessage() . "\n";
    exit(1);
}
