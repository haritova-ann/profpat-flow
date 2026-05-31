<?php

require_once __DIR__ . '/../includes/bootstrap.php';

// Контекст страницы для header (активные пункты, условия отображения)
$page = 'patient';

$lastName = $_GET['last_name'] ?? '';
$firstName = $_GET['first_name'] ?? '';
$middleName = $_GET['middle_name'] ?? '';

// ======================
// Настраиваем header
// ======================
$pageTitle = 'Новый пациент';

$topbarLeft = [
    [
        'label' => '← Регистратура',
        'href' => '/index.php'
    ],
    [
        'type' => 'submit',
        'label' => 'Сохранить',
        'form' => 'patient-form',
        'class' => 'topbar-primary'
    ]
];

require_once __DIR__ . '/../includes/header.php';
?>

<!-- =========================
    Основной контент
========================= -->
<div class="container">

<h2>Новый пациент</h2>

<form action="save.php" id="patient-form" method="POST">

<div class="row">
<div class="form-group">
<label>Дата заполнения</label>
<input type="text" name="created_at" id="today" readonly>
</div>

<div class="form-group">
<label>№ АК</label>
<input type="text" name="medical_card_number" id="ak" readonly>
</div>
</div>

<div class="row">
<div class="form-group">
<label>Фамилия</label>
<input type="text" name="last_name" id="lastName" value="<?= e($lastName) ?>" required>
</div>

<div class="form-group">
<label>Имя</label>
<input type="text" name="first_name" id="firstName" value="<?= e($firstName) ?>" required>
</div>

<div class="form-group">
<label>Отчество</label>
<input type="text" name="middle_name" id="middleName" value="<?= e($middleName) ?>">
</div>
</div>

<div class="form-group">
<label>Дата рождения</label>
<input type="text" name="birth_date" id="birthDate" placeholder="ДД.ММ.ГГГГ" required>
</div>

<div class="form-group">
    <label for="gender">Пол</label>
    <select name="gender" id="gender">
        <option value="male">Мужской</option>
        <option value="female">Женский</option>
    </select>
</div>

<div class="form-group">
    <label for="documentType">Документ</label>
    <select name="document_type" id="documentType">
        <option value="passport">Паспорт РФ</option>
        <option value="passport_foreign">Паспорт иностранного гражданина</option>
        <option value="residence_permit">ВНЖ</option>
    </select>
</div>

<div class="row">
<div class="form-group">
<label>Серия</label>
<input type="text" name="document_series" maxlength="4" class="numeric-input" required>
</div>

<div class="form-group">
<label>Номер</label>
<input type="text" name="document_number" maxlength="6" class="numeric-input" required>
</div>
</div>

<div class="form-group">
<label>Кем выдан</label>
<input type="text" name="document_authority" required>
</div>

<div class="row">
<div class="form-group">
<label>Код структурного подразделения</label>
<input type="text" name="document_authority_code" class="numeric-input" required>
</div>

<div class="form-group">
<label>Дата выдачи</label>
<input type="text" name="document_date" id="documentDate" placeholder="ДД.ММ.ГГГГ" required>
</div>
</div>

<div class="form-group">
<label>СНИЛС</label>
<input type="text" name="snils" id="snils" placeholder="___ ___ ___ __" maxlength="14" inputmode="numeric">
</div>

<div class="form-group">
<label>Телефон</label>
<input type="tel" name="phone_number" id="phoneNumber" placeholder="+7 ___ ___ __ __">
</div>

<div class="form-group">
<label>Email</label>
<input type="email" name="email" placeholder="example@mail.com">
</div>

<h3>Адрес</h3>

<div class="row">
<div class="form-group">
<label>Субъект РФ</label>
<input type="text" name="region" value="Красноярский край" required>
</div>

<div class="form-group">
<label>Район</label>
<input type="text" name="district">
</div>

<div class="form-group">
<label>Населенный пункт</label>
<input type="text" name="locality" value="г. Красноярск" required>
</div>
</div>

<div class="row">
<div class="form-group">
<label>Улица</label>
<input type="text" name="street" required>
</div>

<div class="form-group">
<label>Дом</label>
<input type="text" name="house" required>
</div>
</div>

<div class="row">
<div class="form-group">
<label>Корпус</label>
<input type="text" name="building">
</div>

<div class="form-group">
<label>Квартира</label>
<input type="text" name="flat">
</div>
</div>

</form>
</div>

<?php

require_once __DIR__ . '/../includes/footer.php';