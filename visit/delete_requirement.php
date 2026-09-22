<?php

require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json');

try {
    $data = json_decode(file_get_contents('php://input'), true);

    $visitId = (int)($data['visit_id'] ?? 0);
    $requirementId = (int)($data['requirement_id'] ?? 0);

    if (!$visitId || !$requirementId) {
        throw new Exception('Некорректные данные');
    }

    $stmt = $pdo->prepare("
        DELETE FROM visit_requirements
        WHERE visit_id = :visit_id
          AND requirement_id = :requirement_id
          AND is_added_manually = TRUE
    ");

    $stmt->execute([
        'visit_id' => $visitId,
        'requirement_id' => $requirementId
    ]);

    echo json_encode([
        'success' => true
    ]);

} catch (Exception $e) {
    http_response_code(400);

    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}