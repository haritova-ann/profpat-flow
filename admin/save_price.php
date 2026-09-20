<?php

require_once __DIR__ . '/../includes/bootstrap.php';

/**
 * Получаем данные из формы
 */
$employerId =
    (int)($_POST['employer_id'] ?? 0);

$requirementId =
    (int)($_POST['requirement_id'] ?? 0);

$price =
    (float)($_POST['price'] ?? 0);

$validFrom =
    $_POST['valid_from'] ?? date('Y-m-d');


/**
 * Получаем тип ценообразования работодателя
 */
$stmt = $pdo->prepare("
    SELECT price_mode
    FROM employers
    WHERE id = :employer_id
");

$stmt->execute([
    'employer_id' => $employerId
]);

$employer = $stmt->fetch();

if (!$employer) {
    die('Работодатель не найден');
}

$priceMode = $employer['price_mode'];


/**
 * Сохраняем цену
 */
if ($priceMode === 'default') {

    $stmt = $pdo->prepare("
        INSERT INTO requirement_prices (
            requirement_id,
            price,
            valid_from
        )
        VALUES (
            :requirement_id,
            :price,
            :valid_from
        )
    ");

    $stmt->execute([
        'requirement_id' => $requirementId,
        'price' => $price,
        'valid_from' => $validFrom
    ]);

} elseif ($priceMode === 'special') {

    $stmt = $pdo->prepare("
        INSERT INTO employer_requirement_prices (
            employer_id,
            requirement_id,
            price,
            valid_from
        )
        VALUES (
            :employer_id,
            :requirement_id,
            :price,
            :valid_from
        )
    ");

    $stmt->execute([
        'employer_id' => $employerId,
        'requirement_id' => $requirementId,
        'price' => $price,
        'valid_from' => $validFrom
    ]);

} else {

    die('Для данного типа ценообразования сохранение цены исследования невозможно');
}


/**
 * Возвращаемся на страницу прайса работодателя
 */
header(
    'Location: /admin/prices.php?id=' . $employerId
);

exit;