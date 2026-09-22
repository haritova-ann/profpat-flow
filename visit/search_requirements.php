<?php

require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json');

try {
    $search = trim($_GET['search'] ?? '');

    if ($search === '') {
        echo json_encode([]);
        exit;
    }

    $stmt = $pdo->prepare("
        SELECT id, name, type, room, comment, sort_order
        FROM requirements
        WHERE is_active = TRUE
          AND name ILIKE :search
        ORDER BY name
        LIMIT 20
    ");

    $stmt->execute([
        'search' => '%' . $search . '%'
    ]);

    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));

} catch (Exception $e) {
    http_response_code(400);

    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}