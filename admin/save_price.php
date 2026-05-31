<?php

require_once __DIR__ . '/../includes/bootstrap.php';

$requirementId =
    (int)$_POST['requirement_id'];

$price =
    (float)$_POST['price'];

$stmt = $pdo->prepare("
INSERT INTO requirement_prices (
    requirement_id,
    price
)
VALUES (
    :requirement_id,
    :price
)
");

$stmt->execute([
    'requirement_id' => $requirementId,
    'price' => $price
]);

header(
    'Location: /admin/prices.php'
);

exit;