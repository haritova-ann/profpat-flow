<?php
require __DIR__ . '/../config/db.php';

$id = $_GET['id'] ?? null;

$stmt = $pdo->prepare("SELECT * FROM employers WHERE id = :id");
$stmt->execute(['id' => $id]);

echo json_encode($stmt->fetch());