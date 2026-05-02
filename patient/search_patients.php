<?php
require __DIR__ . '/../config/db.php';

$search = trim($_GET['search'] ?? '');

$sql = "
    SELECT 
        p.id,
        p.medical_card_number,
        p.last_name,
        p.first_name,
        p.middle_name,
        p.birth_date,
        MAX(v.exam_date) AS last_exam_date
    FROM patients p
    LEFT JOIN visits v ON v.patient_id = p.id
";

$params = [];

if ($search !== '') {
    $sql .= "
        WHERE 
            p.medical_card_number ILIKE :search
            OR p.last_name ILIKE :search
            OR p.first_name ILIKE :search
            OR p.middle_name ILIKE :search
    ";
    $params['search'] = "%$search%";
}

$sql .= "
    GROUP BY p.id
    ORDER BY p.last_name
    LIMIT 50
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// форматируем даты сразу тут
foreach ($rows as &$r) {
    $r['birth_date'] = $r['birth_date'] 
        ? date('d.m.Y', strtotime($r['birth_date'])) 
        : '';

    $r['last_exam_date'] = $r['last_exam_date'] 
        ? date('d.m.Y', strtotime($r['last_exam_date'])) 
        : '—';
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode($rows);