<?php
/**
 * Страница редактирования карточки пациента
 * 
 * Назначение: позволяет исправить данные уже созданной карточки пациента
 * Загружает существующие данные и отправляет изменения в update.php
 */

require __DIR__ . '/../config/db.php';

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
?>

<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="UTF-8">
<title>Редактирование данных пациента</title>
<link rel="stylesheet" href="/assets/css/forms.css">
</head>

<body>

<div class="container">

<div style="margin-bottom: 20px;">
<a href="/patient/patient.php?id=<?= $patient['id'] ?>">
    <button type="button">← К карте пациента (без сохранения)</button>
</a>
</div>

<h2>Пациент</h2>

<form action="/patient/update.php" method="POST">

<input type="hidden" name="id" value="<?= $patient['id'] ?>">

<div class="row">
<div class="form-group">
<label>Дата заполнения</label>
<input type="text" name="created_at" id="today"
       value="<?= date('d.m.Y', strtotime($patient['created_at'])) ?>" readonly>
</div>

<div class="form-group">
<label>№ АК</label>
<input type="text" name="medical_card_number" id="ak"
       value="<?= htmlspecialchars($patient['medical_card_number']) ?>" readonly>
</div>
</div>

<div class="row">
<div class="form-group">
<label>Фамилия</label>
<input type="text" name="last_name" id="lastName"
       value="<?= htmlspecialchars($patient['last_name']) ?>" required>
</div>

<div class="form-group">
<label>Имя</label>
<input type="text" name="first_name" id="firstName"
       value="<?= htmlspecialchars($patient['first_name']) ?>" required>
</div>

<div class="form-group">
<label>Отчество</label>
<input type="text" name="middle_name" id="middleName"
       value="<?= htmlspecialchars($patient['middle_name']) ?>" required>
</div>
</div>

<div class="form-group">
<label>Дата рождения</label>
<input type="text" name="birth_date" id="birthDate"
       value="<?= date('d.m.Y', strtotime($patient['birth_date'])) ?>"
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
       value="<?= htmlspecialchars($patient['document_series']) ?>" required>
</div>

<div class="form-group">
<label>Номер</label>
<input type="text" name="document_number"
       value="<?= htmlspecialchars($patient['document_number']) ?>" required>
</div>
</div>

<div class="form-group">
<label>Кем выдан</label>
<input type="text" name="document_authority"
       value="<?= htmlspecialchars($patient['document_authority']) ?>" required>
</div>

<div class="row">
<div class="form-group">
<label>Код структурного подразделения</label>
<input type="text" name="document_authority_code"
       value="<?= htmlspecialchars($patient['document_authority_code']) ?>" required>
</div>

<div class="form-group">
<label>Дата выдачи</label>
<input type="text" name="document_date"
       value="<?= date('d.m.Y', strtotime($patient['document_date'])) ?>" required>
</div>
</div>

<div class="form-group">
<label>СНИЛС</label>
<input type="text" name="snils" id="snils"
       value="<?= htmlspecialchars($patient['snils']) ?>"
       placeholder="___ ___ ___ __" maxlength="14" inputmode="numeric" required>
</div>

<div class="form-group">
<label>Телефон</label>
<input type="tel" name="phone_number" id="phoneNumber"
       value="<?= htmlspecialchars($patient['phone_number']) ?>"
       placeholder="+7 ___ ___ __ __">
</div>

<div class="form-group">
<label>Email</label>
<input type="email" name="email"
       value="<?= htmlspecialchars($patient['email']) ?>"
       placeholder="example@mail.com">
</div>

<h3>Адрес</h3>

<div class="row">
<div class="form-group">
<label>Субъект РФ</label>
<input type="text" name="region"
       value="<?= htmlspecialchars($patient['region']) ?>" required>
</div>

<div class="form-group">
<label>Регион</label>
<input type="text" name="district"
       value="<?= htmlspecialchars($patient['district']) ?>">
</div>

<div class="form-group">
<label>Населенный пункт</label>
<input type="text" name="locality"
       value="<?= htmlspecialchars($patient['locality']) ?>" required>
</div>
</div>

<div class="row">
<div class="form-group">
<label>Улица</label>
<input type="text" name="street"
       value="<?= htmlspecialchars($patient['street']) ?>" required>
</div>

<div class="form-group">
<label>Дом</label>
<input type="text" name="house"
       value="<?= htmlspecialchars($patient['house']) ?>" required>
</div>
</div>

<div class="row">
<div class="form-group">
<label>Корпус</label>
<input type="text" name="building"
       value="<?= htmlspecialchars($patient['building']) ?>">
</div>

<div class="form-group">
<label>Квартира</label>
<input type="text" name="flat"
       value="<?= htmlspecialchars($patient['flat']) ?>">
</div>
</div>

<button type="submit">Сохранить</button>

</form>
</div>

<script src="/assets/js/patient.js"></script>

</body>
</html>