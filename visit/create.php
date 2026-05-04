<?php
require __DIR__ . '/../config/db.php';

$patientId = $_GET['patient_id'] ?? null;

if (!$patientId) {
    die('Не передан patient_id');
}

$stmt = $pdo->prepare("SELECT last_name, first_name, middle_name, medical_card_number FROM patients WHERE id = :id");
$stmt->execute(['id' => $patientId]);
$patient = $stmt->fetch();

if (!$patient) {
    die('Пациент не найден');
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="UTF-8">
<title>Медицинский осмотр</title>
<link rel="stylesheet" href="/assets/css/forms.css">
</head>

<body>

<div class="container">
<h2>Медицинский осмотр</h2>

<div class="card" style="margin-bottom: 20px;">
    <strong>Пациент:</strong>
    <?= htmlspecialchars($patient['last_name']) ?>
    <?= htmlspecialchars($patient['first_name']) ?>
    <?= htmlspecialchars($patient['middle_name']) ?>
    <br>

    <strong>№ карты:</strong>
    <?= htmlspecialchars($patient['medical_card_number']) ?>
</div>

<form action="save.php" method="POST">
<input type="hidden" name="patient_id" value="<?= htmlspecialchars($patientId) ?>">
<div class="row">
<div class="form-group">
<label>Дата медицинского осмотра</label>
<input type="text" name="exam_date" id="today" readonly>
</div>

<div class="form-group">
<label for="examType">Вид медицинского осмотра</label>
    <select name="exam_type" id="examType">
        <option value="periodic">Периодический</option>
        <option value="preliminary">Предварительный</option>
        <option value="ad-hoc">Внеочередной</option>
    </select>
</div>
</div>

<div class="form-group">
    <label>Место работы, учебы</label>

    <input type="text" id="employerSearch" placeholder="Начните вводить название..." autocomplete="off">

    <input type="hidden" name="employer_id" id="employerId">
    <input type="hidden" name="organization_name" id="organizationName">

    <div id="employerDropdown" class="dropdown"></div>
</div>

<div class="form-group">
<label>Структурное подразделение</label>
<input type="text" name="organization_department" id="organizationDepartment">
</div>

<div class="form-group">
<label>Профессия (должность)</label>
<input type="text" name="position" id="position">
</div>

<div class="form-group">
<label>Вид работ, выполяемой работником </label>
<input type="text" name="hazard_factors" id="hazardFactors" required>
</div>

<div class="row">

<div class="form-group">
<label for="psychiatricExam">Необходимость психиатрического освидетельствования</label>
<select name="psychiatric_exam" id="psychiatricExam">
    <option value="false">Нет</option>
    <option value="true">Да</option>
    </select>
</div>

<div class="form-group">
    <label for="psychiatricFactors">Пункт психиатрического освидетельствования</label>
    <input type="text" name="psychiatric_factors" id="psychiatricFactors">
</div>
                             
</div>

<div class="row">

<div class="form-group">
<label>ИНН</label>
<input type="text" name="inn" id="inn" required>
</div>

<div class="form-group">
<label>ОГРН (ОГРНИП)</label>
<input type="text" name="ogrn" id="ogrn" required>
</div>

<div class="form-group">
<label>ОКВЭД</label>
<input type="text" name="okvd" id="okvd">
</div>
</div>

<div class="row">

<div class="form-group">
<label>Номер телефона работодателя</label>
<input type="text" name="employer_phone" id="phoneNumber" placeholder="+7 ___ ___ __ __">
</div>

<div class="form-group">
<label>Адрес электронной почты работодателя</label>
<input type="email" name="employer_email" id="email" placeholder="example@mail.com">
</div>
</div>

<h3>Адрес юридического лица</h3>

<div class="row">
<div class="form-group">
<label>Субъект РФ</label>
<input type="text" name="employer_region" id="region" value="Красноярский край" required>
</div>

<div class="form-group">
<label>Регион</label>
<input type="text" name="employer_district" id="district" id="district">
</div>

<div class="form-group">
<label>Населенный пункт</label>
<input type="text" name="employer_locality" id="locality" value="г. Красноярск" required>
</div>
</div>

<div class="row">
<div class="form-group">
<label>Улица</label>
<input type="text" name="employer_street" id="street" required>
</div>

<div class="form-group">
<label>Дом</label>
<input type="text" name="employer_house" id="house" required>
</div>
</div>

<div class="row">
<div class="form-group">
<label>Корпус</label>
<input type="text" name="employer_building" id="building">
</div>

<div class="form-group">
<label>Квартира</label>
<input type="text" name="employer_flat" id="flat">
</div>
</div>

<button type="submit">Сохранить</button>

</form>
</div>

<script src="/assets/js/visit.js"></script>

</body>
</html>