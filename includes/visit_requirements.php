<?php

function syncVisitRequirements(PDO $pdo, int $visitId): void
{
    // Получаем актуальный нормативный набор услуг
    $stmt = $pdo->prepare("
        SELECT DISTINCT r.id
        FROM requirements r
        JOIN visits v ON v.id = :visit_id
        JOIN patients p ON p.id = v.patient_id
        LEFT JOIN factor_requirements fr
            ON fr.requirement_id = r.id
        LEFT JOIN visit_hazard_factors vhf
            ON vhf.hazard_factor_id = fr.hazard_factor_id
            AND vhf.visit_id = v.id
        WHERE
        (
            (
                r.is_global = TRUE
                AND (r.gender IS NULL OR r.gender = p.gender)
            )
            OR vhf.visit_id IS NOT NULL
        )
        AND (r.min_age IS NULL OR r.min_age <= EXTRACT(YEAR FROM AGE(p.birth_date)))
        AND (fr.exam_type IS NULL OR fr.exam_type = v.exam_type)
        AND r.is_active = TRUE
    ");

    $stmt->execute(['visit_id' => $visitId]);

    $requiredIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // Добавляем новые нормативные услуги
    $insert = $pdo->prepare("
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
            FALSE
        )
        ON CONFLICT (visit_id, requirement_id) DO NOTHING
    ");

    foreach ($requiredIds as $requirementId) {
        $insert->execute([
            'visit_id' => $visitId,
            'requirement_id' => $requirementId
        ]);
    }

    // Удаляем старые нормативные услуги,
    // которых больше нет в актуальном наборе.
    if ($requiredIds) {
        $placeholders = implode(',', array_fill(0, count($requiredIds), '?'));

        $stmt = $pdo->prepare("
            DELETE FROM visit_requirements
            WHERE visit_id = ?
              AND is_added_manually = FALSE
              AND requirement_id NOT IN ($placeholders)
        ");

        $stmt->execute([
            $visitId,
            ...$requiredIds
        ]);
    } else {
        // Если нормативных услуг вообще не осталось,
        // удаляем все нормативные, ручные не трогаем.
        $stmt = $pdo->prepare("
            DELETE FROM visit_requirements
            WHERE visit_id = ?
              AND is_added_manually = FALSE
        ");

        $stmt->execute([$visitId]);
    }
}
