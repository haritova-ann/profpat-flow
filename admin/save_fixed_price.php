<?php

require_once __DIR__ . '/../includes/bootstrap.php';

/**
 * Получаем данные из формы
 */
$employerId =
    (int)($_POST['employer_id'] ?? 0);

$fixedPrice =
    (float)($_POST['fixed_price'] ?? 0);


/**
 * Проверяем работодателя и тип ценообразования
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

if ($employer['price_mode'] !== 'fixed') {
    die('Для данного работодателя не используется фиксированная цена');
}


/**
 * Сохраняем фиксированную цену
 */
$stmt = $pdo->prepare("
    UPDATE employers
    SET fixed_price = :fixed_price
    WHERE id = :employer_id
");

$stmt->execute([
    'fixed_price' => $fixedPrice,
    'employer_id' => $employerId
]);


/**
 * Возвращаемся на страницу прайса
 */
header(
    'Location: /admin/prices.php?id=' . $employerId
);

exit;