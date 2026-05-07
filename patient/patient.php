<?php

require_once __DIR__ . '/../config/db.php';

$id = $_GET['id'] ?? null;

if (!$id) {
    die("Нет ID пациента");
}

// ======================
// Пациент
// ======================
$stmt = $pdo->prepare("SELECT * FROM patients WHERE id = :id");
$stmt->execute(['id' => $id]);
$patient = $stmt->fetch();

if (!$patient) {
    die("Пациент не найден");
}

// ======================
// Визиты
// ======================
$stmt = $pdo->prepare("
    SELECT * FROM visits
    WHERE patient_id = :id
    ORDER BY exam_date DESC
");
$stmt->execute(['id' => $id]);
$visits = $stmt->fetchAll();

// ======================
// Вспомогательная функция для безопасного вывода текста
// ======================
function e($text) {
    return htmlspecialchars($text ?? '');
}

// ======================
// Вспомогательная функция для форматирования дат
// ======================
function formatDate($date) {
    return $date ? date('d.m.Y', strtotime($date)) : '—';
}

// Форматируем адрес проживания
    $address = trim(implode(', ', array_filter([
        $patient['region'],
        $patient['district'],
        $patient['locality'],
        $patient['street'] ? 'ул. ' . $patient['street'] : '',
        $patient['house'] ? 'д. ' . $patient['house'] : '',
        $patient['building'] ? 'корп. ' . $patient['building'] : '',
        $patient['flat'] ? 'кв. ' . $patient['flat'] : ''
    ])));

// Форматируем документ
    $documentTypes = [
        'passport' => 'Паспорт РФ',
        'passport_foreign' => 'Паспорт иностранного гражданина',
        'residence_permit' => 'ВНЖ'
    ];
    $documentType = $documentTypes[$patient['document_type']] ?? $patient['document_type'];
    $document = trim($documentType . ': серия ' . $patient['document_series'] . ' №' . $patient['document_number'] . ' выдан: ' . $patient['document_authority'] . ' ' . $patient['document_authority_code']  . ' ' . $patient['document_date']);


?>

<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="UTF-8">
<title>Карточка пациента</title>
<link rel="stylesheet" href="/assets/css/forms.css">
</head>

<body>

<div class="container">

<div style="margin-bottom: 20px;">
<a href="/index.php">
    <button type="button">← К поиску</button>
</a>
</div>

<h2>Карточка пациента</h2>

<!-- ======================
     БЛОК 1: Пациент
====================== -->
<div class="card">

        <div style="margin-bottom: 20px;">
    <a href="/patient/edit.php?id=<?= $patient['id'] ?>">
        <button type="button">Редактировать
        </button>
    </a>
    </div>

   <div class="section-title">Данные пациента</div>

    <div class="info-row">
        <div class="info-label">№ амбулаторной карты:</div>
        <div class="info-value"><?= e($patient['medical_card_number']) ?></div>
    </div>
    
    <div class="info-row">
        <div class="info-label">ФИО:</div>
        <div class="info-value">
            <?= e($patient['last_name']) ?> 
            <?= e($patient['first_name']) ?> 
            <?= e($patient['middle_name']) ?>
        </div>
    </div>

<div class="info-row">
        <div class="info-label">Дата рождения:</div>
        <div class="info-value"><?= formatDate($patient['birth_date']) ?></div>
    </div>
    
    <div class="info-row">
        <div class="info-label">Пол:</div>
        <div class="info-value">
            <?= $patient['gender'] === 'male' ? 'Мужской' : 'Женский' ?>
        </div>
    </div>
    
    <div class="info-row">
        <div class="info-label">СНИЛС:</div>
        <div class="info-value"><?= e($patient['snils']) ?></div>
    </div>

 <div class="info-row">
        <div class="info-label">Документ:</div>
        <div class="info-value">
            <?= e($document) ?>
        </div>
    </div>
    
    <div class="info-row">
        <div class="info-label">Телефон:</div>
        <div class="info-value"><?= e($patient['phone_number']) ?></div>
    </div>
    
    <div class="info-row">
        <div class="info-label">Email:</div>
        <div class="info-value"><?= e($patient['email']) ?></div>
    </div>
    
    <div class="info-row">
        <div class="info-label">Адрес проживания:</div>
        <div class="info-value">
            <?= e($address) ?>
        </div>
    </div>

</div>

<!-- ======================
     БЛОК 2: Визиты
====================== -->
<div class="card">
 <div style="margin-bottom: 20px;">
    <a href="/visit/create.php?patient_id=<?= $patient['id'] ?>">
        <button type="button">Добавить осмотр
        </button>
    </a>
</div>

   <div class="section-title">История медицинских осмотров</div>

<?php if (empty($visits)): ?>
    <p>Осмотров пока нет</p>
<?php else: ?>

<table class="patients-table">
<thead>
<tr>
    <th>Дата</th>
    <th>Тип</th>
    <th>Организация</th>
    <th>Должность</th>
</tr>
</thead>

<tbody>

<?php foreach ($visits as $visit): ?>
<tr onclick="window.location='/visit/visit.php?id=<?= $visit['id'] ?>'" style="cursor:pointer;">
    <td><?= date('d.m.Y', strtotime($visit['exam_date'])) ?></td>

    <td>
    <?= htmlspecialchars([
        'periodic' => 'Периодический',
        'preliminary' => 'Предварительный',
        'ad-hoc' => 'Внеочередной'
    ][$visit['exam_type']] ?? $visit['exam_type']) ?>
    </td>

    <td><?= htmlspecialchars($visit['organization_name']) ?></td>

    <td><?= htmlspecialchars($visit['position']) ?></td>
</tr>
<?php endforeach; ?>

</tbody>
</table>

<?php endif; ?>

</div>

</div>

</body>
</html>