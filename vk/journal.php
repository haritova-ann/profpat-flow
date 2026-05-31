<?php

require_once __DIR__ . '/../includes/bootstrap.php';

/**
 * Проверка прав доступа
 */
if (!hasRole(['admin', 'doctor'])) {
    die('У вас недостаточно прав');
}

// ======================
// ДАННЫЕ ЖУРНАЛА
// ======================

$stmt = $pdo->prepare("
    SELECT
        vk.id,
        vk.protocol_date,
        vk.protocol_number,
        vk.decision,
        vk.diagnosis,

        p.id AS patient_id,
        p.last_name,
        p.first_name,
        p.middle_name

    FROM vk_conclusions vk
    JOIN patients p ON p.id = vk.patient_id
    ORDER BY vk.protocol_date DESC
");

$stmt->execute();
$vkList = $stmt->fetchAll();

// ======================
// Настраиваем header
// ======================
$pageTitle = 'Журнал ВК';

$topbarLeft = [
    [
    'label' => '← Регистратура',
    'href' => '/index.php'
    ]

];

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container">

<h2>Журнал врачебной комиссии</h2>

<?php if (empty($vkList)): ?>
    <p>Записей нет</p>
<?php else: ?>

<table class="patients-table">

<thead>
<tr>
    <th>Дата</th>
    <th>№ протокола</th>
    <th>Пациент</th>
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
        <?= e($vk['last_name']) ?>
        <?= e($vk['first_name']) ?>
        <?= e($vk['middle_name']) ?>
    </td>

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

</body>
</html>