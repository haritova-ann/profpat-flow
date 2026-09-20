<?php

require_once __DIR__ . '/../includes/bootstrap.php';

/**
 * Проверка прав доступа
 */
if (!hasRole(['admin'])) {
    die('У вас недостаточно прав');
}

$employerId = (int)($_GET['id'] ?? 0);

if ($employerId <= 0) {
    die('Не указан работодатель');
}


/**
 * Получаем работодателя
 */
$stmt = $pdo->prepare("
    SELECT
        id,
        name,
        price_mode,
        fixed_price
    FROM employers
    WHERE id = :id
");

$stmt->execute([
    'id' => $employerId
]);

$employer = $stmt->fetch();

if (!$employer) {
    die('Работодатель не найден');
}


/**
 * Изменение типа ценообразования
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_price_mode'])) {

    $priceMode = $_POST['price_mode'] ?? '';

    $allowedModes = [
        'default',
        'special',
        'fixed'
    ];

    if (!in_array($priceMode, $allowedModes, true)) {
        die('Недопустимый тип ценообразования');
    }

    $stmt = $pdo->prepare("
        UPDATE employers
        SET price_mode = :price_mode
        WHERE id = :id
    ");

    $stmt->execute([
        'price_mode' => $priceMode,
        'id' => $employerId
    ]);

    header('Location: /admin/prices.php?id=' . $employerId);
    exit;
}


/**
 * Загружаем цены
 */
$priceList = [];

if ($employer['price_mode'] === 'default') {

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

} elseif ($employer['price_mode'] === 'special') {

    $stmt = $pdo->prepare("
        SELECT
            r.id,
            r.name,
            r.type,

            erp.price,
            erp.valid_from

        FROM requirements r

        LEFT JOIN LATERAL (

            SELECT
                price,
                valid_from

            FROM employer_requirement_prices

            WHERE employer_id = :employer_id
              AND requirement_id = r.id

            ORDER BY valid_from DESC

            LIMIT 1

        ) erp ON TRUE

        ORDER BY
            r.type,
            r.name
    ");

    $stmt->execute([
        'employer_id' => $employerId
    ]);

    $priceList = $stmt->fetchAll();
}


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
        'label' => '← Работодатель',
        'href' => '/employers/employer.php?id=' . $employerId
    ]
];

require_once __DIR__ . '/../includes/header.php';
?>


<div class="container">

    <h2>
        Цены: <?= e($employer['name']) ?>
    </h2>


    <!-- =========================
        Тип ценообразования
    ========================= -->

    <div class="info-block">

        <h3>Тип ценообразования</h3>

        <form method="POST">

            <select name="price_mode">

                <option
                    value="default"
                    <?= $employer['price_mode'] === 'default' ? 'selected' : '' ?>
                >
                    Общий прайс
                </option>

                <option
                    value="special"
                    <?= $employer['price_mode'] === 'special' ? 'selected' : '' ?>
                >
                    Специальные цены
                </option>

                <option
                    value="fixed"
                    <?= $employer['price_mode'] === 'fixed' ? 'selected' : '' ?>
                >
                    Фиксированная цена
                </option>

            </select>

            <button
                type="submit"
                name="save_price_mode"
            >
                Сохранить
            </button>

        </form>

    </div>


    <?php if ($employer['price_mode'] === 'fixed'): ?>

        <!-- =========================
            Фиксированная цена
        ========================= -->

        <div class="info-block">

            <h3>Фиксированная стоимость</h3>

            <form
                action="/admin/save_fixed_price.php"
                method="POST"
            >

                <input
                    type="hidden"
                    name="employer_id"
                    value="<?= $employerId ?>"
                >

                <input
                    type="number"
                    name="fixed_price"
                    value="<?= e($employer['fixed_price']) ?>"
                    placeholder="Стоимость"
                    min="0"
                    step="0.01"
                >

                <span>₽</span>

                <button type="submit">
                    Сохранить
                </button>

            </form>

        </div>


    <?php elseif (
        $employer['price_mode'] === 'default'
        || $employer['price_mode'] === 'special'
    ): ?>


        <!-- =========================
            Прайс по исследованиям
        ========================= -->
        <h3>Прайс</h3>

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
                echo e($groupTitles[$currentGroup] ?? $currentGroup);
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
                        name="employer_id"
                        value="<?= $employerId ?>"
                    >

                    <input
                        type="hidden"
                        name="requirement_id"
                        value="<?= $price['id'] ?>"
                    >

                    <input
                        type="hidden"
                        name="price_mode"
                        value="<?= e($employer['price_mode']) ?>"
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
                        min="0"
                        step="0.01"
                    >

                    <button type="submit">
                        Сохранить
                    </button>

                </form>

            </div>

        <?php endforeach; ?>

        <?php if ($currentGroup !== null): ?>
            </div>
        <?php endif; ?>


    <?php endif; ?>

</div>

<?php

require_once __DIR__ . '/../includes/footer.php';