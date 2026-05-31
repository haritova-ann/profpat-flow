<?php

require_once __DIR__ . '/../includes/bootstrap.php';

$id = $_GET['id'] ?? null;

if (!$id) {
    die("Нет ID работодателя");
}

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
// Настраиваем header
// ======================
$pageTitle = 'Редактировать ' . e($employer['name']);

$topbarLeft = [
    [
        'label' => '← Организация',
        'href' => '/employers/employer.php?id=' . $employer['id']
    ],
    [
        'type' => 'submit',
        'label' => 'Сохранить',
        'form' => 'employer-form',
        'class' => 'topbar-primary'
    ]
];

require_once __DIR__ . '/../includes/header.php';
?>

<!-- =========================
Основной контент
========================= -->
<div class="container">

<h2>Редактирование работодателя</h2>

<form action="update.php" id='employer-form' method="POST">

<input type="hidden" name="id" value="<?= $employer['id'] ?>">

<div class="section">

<h3>Основные данные</h3>

<div class="form-group">
<label>Название организации</label>
<input
    type="text"
    name="name"
    required
    value="<?= e($employer['name']) ?>"
>
</div>

<div class="row">

<div class="form-group">
<label>ИНН</label>
<input
    type="text"
    name="inn"
    value="<?= e($employer['inn']) ?>"
>
</div>

<div class="form-group">
<label>ОГРН</label>
<input
    type="text"
    name="ogrn"
    value="<?= e($employer['ogrn']) ?>"
>
</div>

<div class="form-group">
<label>ОКВЭД</label>
<input
    type="text"
    name="okvd"
    value="<?= e($employer['okvd']) ?>"
>
</div>

</div>

</div>

<div class="section">

<h3>Контакты</h3>

<div class="row">

<div class="form-group">
<label>Телефон</label>
<input
    type="text"
    name="phone"
    value="<?= e($employer['phone']) ?>"
>
</div>

<div class="form-group">
<label>Email</label>
<input
    type="email"
    name="email"
    value="<?= e($employer['email']) ?>"
>
</div>

</div>

</div>

<div class="section">

<h3>Юридический адрес</h3>

<div class="row">

<div class="form-group">
<label>Субъект РФ</label>
<input
    type="text"
    name="region"
    value="<?= e($employer['region']) ?>"
>
</div>

<div class="form-group">
<label>Район</label>
<input
    type="text"
    name="district"
    value="<?= e($employer['district']) ?>"
>
</div>

<div class="form-group">
<label>Населенный пункт</label>
<input
    type="text"
    name="locality"
    value="<?= e($employer['locality']) ?>"
>
</div>

</div>

<div class="row">

<div class="form-group">
<label>Улица</label>
<input
    type="text"
    name="street"
    value="<?= e($employer['street']) ?>"
>
</div>

<div class="form-group small">
<label>Дом</label>
<input
    type="text"
    name="house"
    value="<?= e($employer['house']) ?>"
>
</div>

<div class="form-group small">
<label>Корпус</label>
<input
    type="text"
    name="building"
    value="<?= e($employer['building']) ?>"
>
</div>

<div class="form-group small">
<label>Квартира</label>
<input
    type="text"
    name="flat"
    value="<?= e($employer['flat']) ?>"
>
</div>

</div>

</div>

<button type="submit">
    Сохранить изменения
</button>

</form>

</div>

</body>
</html>