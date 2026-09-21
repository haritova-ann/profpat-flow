<?php

require_once __DIR__ . '/../includes/bootstrap.php';

// ======================
// Настраиваем header
// ======================
$pageTitle = 'Кабинет врача';

$topbarLeft = [
    [
    'label' => '← Регистратура',
    'href' => '/index.php'
    ]

];

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container">
    <a class="topbar-btn"
           href="/vk/journal.php">
            Журнал врачебной комиссии
        </a>

</div>