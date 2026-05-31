<?php

require_once __DIR__ . '/../includes/bootstrap.php';

$search = trim($_GET['search'] ?? '');
$sql = "
 SELECT e.id,
 e.name,
 e.inn,
 e.ogrn,
 e.phone,
 COUNT(v.id) AS visits_count,
 MAX(v.exam_date) AS last_exam_date FROM employers e LEFT JOIN visits v ON v.employer_id = e.id";
$params = [];

if ($search !== '') {
 $sql .= " WHERE e.name ILIKE :search OR e.inn ILIKE :search";
 $params['search'] = '%' . $search . '%';
}

$sql .= " GROUP BY e.id ORDER BY e.name";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$employers = $stmt->fetchAll();

header('Content-Type: application/json; charset=utf-8');
echo json_encode($employers, JSON_UNESCAPED_UNICODE);
exit;