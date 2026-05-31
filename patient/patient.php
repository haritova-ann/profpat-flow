<?php

require_once __DIR__ . '/../includes/bootstrap.php';

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
// Медицинские осмотры
// ======================
$stmt = $pdo->prepare("
    SELECT * FROM visits
    WHERE patient_id = :id
    ORDER BY exam_date DESC
");
$stmt->execute(['id' => $id]);
$visits = $stmt->fetchAll();

// ======================
// Иные цели посещений
// ======================
$stmt = $pdo->prepare("
    SELECT id, exam_date, diagnosis, need_card, need_certificate
    FROM patient_documents
    WHERE patient_id = :id
    ORDER BY exam_date DESC
");
$stmt->execute(['id' => $id]);
$documents = $stmt->fetchAll();

// ======================
// Протоколы ВК
// ======================
$stmt = $pdo->prepare("
    SELECT
        vk.id,
        vk.protocol_date,
        vk.protocol_number,
        vk.decision,
        vk.diagnosis
    FROM vk_conclusions vk
    WHERE vk.patient_id = :id
    ORDER BY vk.protocol_date DESC
");

$stmt->execute(['id' => $id]);
$vkList = $stmt->fetchAll();


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

// ======================
// Настраиваем header
// ======================
$pageTitle = 'Карта пациента';

$topbarLeft = [
    [
        'label' => '← Регистратура',
        'href' => '/index.php'
    ],
    [
        'label' => 'Редактировать карту',
        'href' => '/patient/edit.php?id=' . $patient['id'],
        'class' => 'topbar-primary'
    ],
    [
        'label' => 'Добавить осмотр',
        'href' => '/visit/create.php?patient_id=' . $patient['id'],
        'class' => 'topbar-primary'
    ],
];

require_once __DIR__ . '/../includes/header.php';

?>


<!-- =========================
    Основной контент
========================= -->
<div class="container">
<h2>Карта пациента</h2>

<!-- ======================
     БЛОК 1: Пациент
====================== -->
<div class="card">

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
    <p>Осмотров нет</p>
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
    <td><?= formatDate($visit['exam_date']) ?></td>

    <td>
    <?= e([
        'periodic' => 'Периодический',
        'preliminary' => 'Предварительный',
        'ad-hoc' => 'Внеочередной'
    ][$visit['exam_type']] ?? $visit['exam_type']) ?>
    </td>

    <td><?= e($visit['organization_name']) ?></td>

    <td><?= e($visit['position']) ?></td>
</tr>
<?php endforeach; ?>

</tbody>
</table>

<?php endif; ?>

</div>

<!-- ======================
     БЛОК 2.5: ВК
====================== -->
<?php if (hasRole(['admin', 'doctor'])): ?>
<div class="card">

    <div class="section-title">Протоколы врачебной комиссии</div>

    <?php if (empty($vkList)): ?>
        <p>Протоколов ВК нет</p>
    <?php else: ?>

    <table class="patients-table">
        <thead>
        <tr>
            <th>Дата</th>
            <th>Номер</th>
            <th>Решение</th>
            <th>Диагноз</th>
        </tr>
        </thead>

        <tbody>

        <?php foreach ($vkList as $vk): ?>
        <tr onclick="window.location='/vk/vk.php?id=<?= $vk['id'] ?>'"
            style="cursor:pointer;">

            <td><?= formatDate($vk['protocol_date']) ?></td>

            <td><?= e($vk['protocol_number']) ?></td>

            <td>
                <?= e([
                    'fit' => 'Допущен',
                    'temporary' => 'Временные противопоказания',
                    'permanent' => 'Постоянные противопоказания'
                ][$vk['decision']] ?? $vk['decision']) ?>
            </td>

            <td><?= e($vk['diagnosis']) ?></td>

        </tr>
        <?php endforeach; ?>

        </tbody>
    </table>

    <?php endif; ?>

</div>

<!-- ======================
     БЛОК 3: ИНЫЕ ПРИЁМЫ
====================== -->
<div class="card">

    <div style="margin-bottom: 20px;">
        <a href="/documents/create.php?patient_id=<?= $patient['id'] ?>">
            <button type="button">Добавить приём</button>
        </a>
    </div>

    <div class="section-title">Санаторно-курортные приёмы</div>

    <?php if (empty($documents)): ?>
        <p>Приёмов нет</p>
    <?php else: ?>

    <table class="patients-table">
        <thead>
        <tr>
            <th>Дата</th>
            <th>Диагноз</th>
            <th>Документы</th>
        </tr>
        </thead>

        <tbody>

        <?php foreach ($documents as $doc): ?>
        <tr onclick="window.location='/documents/view.php?id=<?= $doc['id'] ?>'" style="cursor:pointer;">

            <td><?= formatDate($doc['exam_date']) ?></td>

            <td><?= e($doc['diagnosis']) ?></td>

            <td>
                <?= $doc['need_card'] ? 'Карта ' : '' ?>
                <?= $doc['need_certificate'] ? 'Справка' : '' ?>
            </td>

        </tr>
        <?php endforeach; ?>

        </tbody>
    </table>

    <?php endif; ?>

</div>
<?php endif; ?>
</div>

</div>

<?php

require_once __DIR__ . '/../includes/footer.php';