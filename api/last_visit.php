<?php
require_once __DIR__ . '/../includes/bootstrap.php';

header('Content-Type: application/json');

$patientId = $_GET['patient_id'] ?? null;


    $stmt = $pdo->prepare("
    SELECT 
        v.id AS visit_id,
        v.*,
        p.*,
        STRING_AGG(DISTINCT hf.code, ', ') AS hazard_factors,
        STRING_AGG(DISTINCT pf.code, ', ') AS psychiatric_factors
    FROM visits v
    JOIN patients p ON p.id = v.patient_id
    LEFT JOIN visit_hazard_factors vhf ON vhf.visit_id = v.id
    LEFT JOIN hazard_factors hf ON hf.id = vhf.hazard_factor_id
    LEFT JOIN visit_psychiatric_factors vpf ON vpf.visit_id = v.id
    LEFT JOIN psychiatric_factors pf ON pf.id = vpf.psychiatric_factor_id

    WHERE v.patient_id = :patient_id

    GROUP BY v.id, p.id

    ORDER BY v.exam_date DESC

    LIMIT 1
");

    $stmt->execute([
        'patient_id' => $patientId
    ]);

    $lastVisit = $stmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode($lastVisit);