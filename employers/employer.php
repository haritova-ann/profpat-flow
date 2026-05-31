<?php

require_once __DIR__ . '/../includes/bootstrap.php';

$id = $_GET['id'] ?? null;

if (!$id) {
    die("Нет ID работодателя");
}

// ======================
// Работодатель
// ======================
$stmt = $pdo->prepare("
    SELECT *
    FROM employers
    WHERE id = :id
");
$stmt->execute(['id' => $id]);

$employer = $stmt->fetch();

if (!$employer) {
    die("Работодатель не найден");
}

// ======================
// Осмотры по работодателю
// ======================
$stmt = $pdo->prepare("
    SELECT
        v.*,

        p.last_name,
        p.first_name,
        p.middle_name,
        p.birth_date,
        p.medical_card_number

    FROM visits v

    LEFT JOIN patients p
        ON p.id = v.patient_id

    WHERE v.employer_id = :id

    ORDER BY v.exam_date DESC
");

$stmt->execute(['id' => $id]);

$visits = $stmt->fetchAll();

// ======================
// Форматируем адрес
// ======================
$address = trim(implode(', ', array_filter([
    $employer['region'],
    $employer['district'],
    $employer['locality'],
    $employer['street'] ? 'ул. ' . $employer['street'] : '',
    $employer['house'] ? 'д. ' . $employer['house'] : '',
    $employer['building'] ? 'корп. ' . $employer['building'] : '',
    $employer['flat'] ? 'кв. ' . $employer['flat'] : ''
])));

// ======================
// Настраиваем header
// ======================
$pageTitle = e($employer['name']);

$topbarLeft = [
    [
        'label' => '← Регистратура',
        'href' => '/index.php'
    ],
    [
        'label' => '← Список организаций',
        'href' => '/employers/employers.php'
    ]
];

require_once __DIR__ . '/../includes/header.php';
?>

<!-- =========================
Основной контент
========================= -->
<div class="container">

<h2>Карточка работодателя</h2>

<!-- ======================
БЛОК 1: Работодатель
====================== -->
<div class="card">

    <div style="margin-bottom: 20px;">
        <a href="/employers/edit.php?id=<?= $employer['id'] ?>">
            <button type="button">
                Редактировать
            </button>
        </a>
    </div>

    <div class="section-title">
        Данные работодателя
    </div>

    <div class="info-row">
        <div class="info-label">Название:</div>

        <div class="info-value">
            <?= e($employer['name']) ?>
        </div>
    </div>

    <div class="info-row">
        <div class="info-label">ИНН:</div>

        <div class="info-value">
            <?= e($employer['inn']) ?>
        </div>
    </div>

    <div class="info-row">
        <div class="info-label">ОГРН:</div>

        <div class="info-value">
            <?= e($employer['ogrn']) ?>
        </div>
    </div>

    <div class="info-row">
        <div class="info-label">ОКВЭД:</div>

        <div class="info-value">
            <?= e($employer['okvd']) ?>
        </div>
    </div>

    <div class="info-row">
        <div class="info-label">Телефон:</div>

        <div class="info-value">
            <?= e($employer['phone']) ?>
        </div>
    </div>

    <div class="info-row">
        <div class="info-label">Email:</div>

        <div class="info-value">
            <?= e($employer['email']) ?>
        </div>
    </div>

    <div class="info-row">
        <div class="info-label">Юридический адрес:</div>

        <div class="info-value">
            <?= e($address) ?>
        </div>
    </div>

    <div class="info-row">
        <div class="info-label">Дата создания:</div>

        <div class="info-value">
            <?= formatDate($employer['created_at']) ?>
        </div>
    </div>

    <?php if (hasRole(['admin'])): ?>
        <form action="/employers/delete.php" method="POST" onsubmit="return confirm('Удалить организацию?');">
            <input type="hidden" name="id" value="<?= e($employer['id']) ?>">
            <button type="submit">Удалить</button>
        </form>
    <?php endif; ?>

</div>

<!-- ======================
БЛОК 2: Осмотры
====================== -->
<div class="card">

    <div class="section-title">
        Медицинские осмотры
    </div>

<?php if (empty($visits)): ?>

    <p>Осмотров нет</p>

<?php else: ?>

<table class="patients-table">

<thead>
<tr>
    <th>Дата</th>
    <th>Пациент</th>
    <th>№ карты</th>
    <th>Тип осмотра</th>
    <th>Должность</th>
</tr>
</thead>

<tbody>

<?php foreach ($visits as $visit): ?>

<tr
    onclick="window.location='/visit/visit.php?id=<?= $visit['id'] ?>'"
    style="cursor:pointer;"
>

    <td>
        <?= formatDate($visit['exam_date']) ?>
    </td>

    <td>
        <?= e($visit['last_name']) ?>
        <?= e($visit['first_name']) ?>
        <?= e($visit['middle_name']) ?>
    </td>

    <td>
        <?= e($visit['medical_card_number']) ?>
    </td>

    <td>
        <?= e([
            'periodic' => 'Периодический',
            'preliminary' => 'Предварительный',
            'ad-hoc' => 'Внеочередной'
        ][$visit['exam_type']] ?? $visit['exam_type']) ?>
    </td>

    <td>
        <?= e($visit['position']) ?>
    </td>

</tr>

<?php endforeach; ?>

</tbody>
</table>

<?php endif; ?>

</div>

</div>

</body>
</html>