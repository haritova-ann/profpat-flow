<?php

require_once __DIR__ . '/../includes/bootstrap.php';

/**
 * Проверка прав доступа
 */
if (!hasRole(['admin'])) {
    die('У вас недостаточно прав');
}

$stmt = $pdo->prepare("
SELECT
    r.id,
    r.name,
    r.type,

    rp.price,
    rp.valid_from

FROM requirements r

LEFT JOIN LATERAL (

    SELECT
        price,
        valid_from

    FROM requirement_prices

    WHERE requirement_id = r.id

    ORDER BY valid_from DESC

    LIMIT 1

) rp ON TRUE

ORDER BY
    r.type,
    r.name
");

$stmt->execute();
$priceList = $stmt->fetchAll();

$groupTitles = [
    'lab' => 'Лабораторные исследования',
    'exam' => 'Осмотры врачей',
    'instr' => 'Инструментальные исследования'
];

// ======================
// Настраиваем header
// ======================
$pageTitle = 'Цены';

$topbarLeft = [
    [
        'label' => '← Регистратура',
        'href' => '/index.php'
    ]
];

require_once __DIR__ . '/../includes/header.php';
?>

<!-- =========================
    Информационный блок
========================= -->
<div class="container">
<h2>Цены</h2>

<?php
$currentGroup = null;

foreach ($priceList as $price):

    if ($currentGroup !== $price['type']):

        if ($currentGroup !== null) {
            echo '</div>';
        }

        $currentGroup = $price['type'];

        echo '<div class="route-group">';
        echo '<div class="route-group-title">';
        echo $groupTitles[$currentGroup];
        echo '</div>';

    endif;
?>

    <div class="route-item">

    <div class="route-item-name">
        <?= e($price['name']) ?>
    </div>

    <form
        action="/admin/save_price.php"
        method="POST"
    >

        <input
            type="hidden"
            name="requirement_id"
            value="<?= $price['id'] ?>"
        >

        <input
            type="date"
            name="valid_from"
            value="<?= date('Y-m-d') ?>"
        >

        <input
            type="number"
            name="price"
            value="<?= e($price['price']) ?>"
            placeholder="Цена"
        >

        <button>
            Сохранить
        </button>

    </form>

</div>

<?php endforeach; ?>

<?php if ($currentGroup !== null): ?>
    </div>
<?php endif; ?>

</div>

<?php

require_once __DIR__ . '/../includes/footer.php';