<?php
// Test Singapore Neon connection speed

$start = microtime(true);
$dsn = 'pgsql:host=ep-blue-glitter-aoqw6gmg.c-2.ap-southeast-1.aws.neon.tech;port=5432;dbname=neondb;sslmode=require';
try {
    $pdo = new PDO($dsn, 'neondb_owner', 'npg_7KQaoH8nODEZ');
    $connectTime = round((microtime(true) - $start) * 1000);
    echo "Singapore connection time: {$connectTime}ms\n";

    $start2 = microtime(true);
    $stmt = $pdo->query('SELECT 1');
    $queryTime = round((microtime(true) - $start2) * 1000);
    echo "Singapore query time: {$queryTime}ms\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "\n--- Comparison ---\n";
echo "OLD (US-East):  Connection ~1500ms, Query ~500ms\n";
echo "NEW (Singapore): Connection {$connectTime}ms, Query {$queryTime}ms\n";
