<?php
require __DIR__ . '/../config/db.php';

$q = $_GET['q'] ?? '';

$stmt = $pdo->prepare("
    SELECT id, name, inn
    FROM employers
    WHERE name ILIKE :q
    LIMIT 10
");

$stmt->execute(['q' => "%$q%"]);

echo json_encode($stmt->fetchAll());