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

    // Проверяем, что услуга существует и активна
    $stmt = $pdo->prepare("
        SELECT id, name, type, room, comment, sort_order
        FROM requirements
        WHERE id = :requirement_id
          AND is_active = TRUE
    ");

    $stmt->execute([
        'requirement_id' => $requirementId
    ]);

    $requirement = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$requirement) {
        throw new Exception('Услуга не найдена');
    }

    // Добавляем услугу в маршрутный лист
    $stmt = $pdo->prepare("
        INSERT INTO visit_requirements (
            visit_id,
            requirement_id,
            is_selected,
            is_added_manually
        )
        VALUES (
            :visit_id,
            :requirement_id,
            TRUE,
            TRUE
        )
        ON CONFLICT (visit_id, requirement_id) DO NOTHING
    ");

    $stmt->execute([
        'visit_id' => $visitId,
        'requirement_id' => $requirementId
    ]);

    echo json_encode([
        'success' => true,
        'requirement' => $requirement
    ]);

} catch (Exception $e) {

    http_response_code(400);

    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}