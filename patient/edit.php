<?php
/**
 * Страница редактирования карточки пациента
 * 
 * Назначение: позволяет исправить данные уже созданной карточки пациента
 * Загружает существующие данные и отправляет изменения в update.php
 */

require_once __DIR__ . '/../includes/bootstrap.php';

// Контекст страницы для header (активные пункты, условия отображения)
$page = 'patient';

// Получаем ID пациента
$patientId = $_GET['id'] ?? null;

if (!$patientId) {
    die('Не передан ID пациента');
}

// Загружаем информацию о пациенте
$stmt = $pdo->prepare("
    SELECT *
    FROM patients
    WHERE id = :id
");
$stmt->execute(['id' => $patientId]);
$patient = $stmt->fetch();

if (!$patient) {
    die('Пациента не найден');
}

// ======================
// Настраиваем topbar
// ======================
$topbarLeft = [
    [
        'label' => '← Карта пациента',
        'href' => '/patient/patient.php?id=' . $patientId
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
<h2>Редактирование данных пациента</h2>

<form action="/patient/update.php" id="patient-form" method="POST">

<input type="hidden" name="id" value="<?= $patient['id'] ?>">

<div class="row">
<div class="form-group">
<label>Дата заполнения</label>
<input type="text" name="created_at" id="today"
       value="<?= formatDate($patient['created_at']) ?>" readonly>
</div>

<div class="form-group">
<label>№ АК</label>
<input type="text" name="medical_card_number" id="ak"
       value="<?= e($patient['medical_card_number']) ?>" readonly>
</div>
</div>

<div class="row">
<div class="form-group">
<label>Фамилия</label>
<input type="text" name="last_name" id="lastName"
       value="<?= e($patient['last_name']) ?>" required>
</div>

<div class="form-group">
<label>Имя</label>
<input type="text" name="first_name" id="firstName"
       value="<?= e($patient['first_name']) ?>" required>
</div>

<div class="form-group">
<label>Отчество</label>
<input type="text" name="middle_name" id="middleName"
       value="<?= e($patient['middle_name']) ?>">
</div>
</div>

<div class="form-group">
<label>Дата рождения</label>
<input type="text" name="birth_date" id="birthDate"
       value="<?= formatDate($patient['birth_date']) ?>"
       placeholder="ДД.ММ.ГГГГ" required>
</div>

<div class="form-group">
<label for="gender">Пол</label>
<select name="gender" id="gender">
    <option value="male" <?= $patient['gender'] === 'male' ? 'selected' : '' ?>>Мужской</option>
    <option value="female" <?= $patient['gender'] === 'female' ? 'selected' : '' ?>>Женский</option>
</select>
</div>

<div class="form-group">
<label for="documentType">Документ</label>
<select name="document_type" id="documentType">
    <option value="passport" <?= $patient['document_type'] === 'passport' ? 'selected' : '' ?>>Паспорт РФ</option>
    <option value="passport_foreign" <?= $patient['document_type'] === 'passport_foreign' ? 'selected' : '' ?>>Паспорт иностранного гражданина</option>
    <option value="residence_permit" <?= $patient['document_type'] === 'residence_permit' ? 'selected' : '' ?>>ВНЖ</option>
</select>
</div>

<div class="row">
<div class="form-group">
<label>Серия</label>
<input type="text" name="document_series"
       value="<?= e($patient['document_series']) ?>" class="numeric-input" required>
</div>

<div class="form-group">
<label>Номер</label>
<input type="text" name="document_number"
       value="<?= e($patient['document_number']) ?>" class="numeric-input" required>
</div>
</div>

<div class="form-group">
<label>Кем выдан</label>
<input type="text" name="document_authority"
       value="<?= e($patient['document_authority']) ?>" required>
</div>

<div class="row">
<div class="form-group">
<label>Код структурного подразделения</label>
<input type="text" name="document_authority_code"
       value="<?= e($patient['document_authority_code']) ?>" class="numeric-input" required>
</div>

<div class="form-group">
<label>Дата выдачи</label>
<input type="text" name="document_date"
       value="<?= formatDate($patient['document_date']) ?>" class="numeric-input" required>
</div>
</div>

<div class="form-group">
<label>СНИЛС</label>
<input type="text" name="snils" id="snils"
       value="<?= e($patient['snils']) ?>"
       placeholder="___ ___ ___ __" maxlength="14" inputmode="numeric" required>
</div>

<div class="form-group">
<label>Телефон</label>
<input type="tel" name="phone_number" id="phoneNumber"
       value="<?= e($patient['phone_number']) ?>"
       placeholder="+7 ___ ___ __ __">
</div>

<div class="form-group">
<label>Email</label>
<input type="email" name="email"
       value="<?= e($patient['email']) ?>"
       placeholder="example@mail.com">
</div>

<h3>Адрес</h3>

<div class="row">
<div class="form-group">
<label>Субъект РФ</label>
<input type="text" name="region"
       value="<?= e($patient['region']) ?>" required>
</div>

<div class="form-group">
<label>Регион</label>
<input type="text" name="district"
       value="<?= e($patient['district']) ?>">
</div>

<div class="form-group">
<label>Населенный пункт</label>
<input type="text" name="locality"
       value="<?= e($patient['locality']) ?>" required>
</div>
</div>

<div class="row">
<div class="form-group">
<label>Улица</label>
<input type="text" name="street"
       value="<?= e($patient['street']) ?>" required>
</div>

<div class="form-group">
<label>Дом</label>
<input type="text" name="house"
       value="<?= e($patient['house']) ?>" required>
</div>
</div>

<div class="row">
<div class="form-group">
<label>Корпус</label>
<input type="text" name="building"
       value="<?= e($patient['building']) ?>">
</div>

<div class="form-group">
<label>Квартира</label>
<input type="text" name="flat"
       value="<?= e($patient['flat']) ?>">
</div>
</div>

<button type="submit">Сохранить</button>

</form>
</div>

<?php

require_once __DIR__ . '/../includes/footer.php';