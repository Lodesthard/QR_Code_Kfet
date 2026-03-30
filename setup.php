<?php
/**
 * Script de setup initial - Crée les comptes par défaut
 * À exécuter une seule fois après l'installation
 *
 * Comptes créés :
 *   Admin   -> n° 100000 / mdp: admin
 *   Barista -> n° 200000 / mdp: barista
 *   Client  -> n° 300000 / mdp: client
 */

require_once('lib/connect.php');
$mysqli = connectToDatabase();

$accounts = [
    ['student_number' => '100000', 'username' => 'Admin',   'password' => 'admin',   'auth_level' => 2, 'credit' => 100],
    ['student_number' => '200000', 'username' => 'Barista', 'password' => 'barista', 'auth_level' => 1, 'credit' => 50],
    ['student_number' => '300000', 'username' => 'Client',  'password' => 'client',  'auth_level' => 0, 'credit' => 20],
];

$created = 0;
$skipped = 0;

foreach ($accounts as $account) {
    // Check if already exists
    $stmt = $mysqli->prepare('SELECT id FROM users WHERE student_number = ?');
    $stmt->bind_param('s', $account['student_number']);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->close();
        echo "[SKIP] " . $account['username'] . " (n° " . $account['student_number'] . ") existe déjà.\n";
        $skipped++;
        continue;
    }
    $stmt->close();

    // Create account
    $hash = password_hash($account['password'], PASSWORD_DEFAULT);
    $stmt = $mysqli->prepare(
        'INSERT INTO users (student_number, password, username, bdlc_member, auth_level, credit, activated)
         VALUES (?, ?, ?, 0, ?, ?, 1)'
    );
    $stmt->bind_param('sssid', $account['student_number'], $hash, $account['username'], $account['auth_level'], $account['credit']);
    $stmt->execute();
    $stmt->close();

    $role = ['Client', 'Barista', 'Admin'][$account['auth_level']];
    echo "[OK]   " . $account['username'] . " créé (n° " . $account['student_number'] . " / mdp: " . $account['password'] . " / rôle: " . $role . ")\n";
    $created++;
}

echo "\n--- Terminé : $created créé(s), $skipped ignoré(s) ---\n";

$mysqli->close();
?>