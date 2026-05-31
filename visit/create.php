<?php

require_once __DIR__ . '/../includes/bootstrap.php';

// Контекст страницы для header (активные пункты, условия отображения)
$page = 'visit';

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

// ======================
// Настраиваем header
// ======================
$pageTitle = 'Новый осмотр';

$topbarLeft = [
    [
        'label' => '← Карта пациента',
        'href' => '/patient/patient.php?id=' . $patientId
    ],
    [
        'type' => 'button',
        'label' => 'Заполнить из предыдущего осмотра',
        'data-action' => 'fill-from-previous-visit',
        'class' => 'topbar-secondary'
    ],
    [
        'type' => 'submit',
        'label' => 'Сохранить',
        'form' => 'visit-form',
        'class' => 'topbar-primary'
    ]

];

// Получаем последний визит пациента
$stmt = $pdo->prepare("
    SELECT * FROM visits 
    WHERE patient_id = :patient_id 
    ORDER BY exam_date DESC 
    LIMIT 1
");
$stmt->execute(['patient_id' => $patientId]);
$lastVisit = $stmt->fetch();

require_once __DIR__ . '/../includes/header.php';
?>

<!-- =========================
    Основной контент
========================= -->
<div class="container">
<h2>Новый медицинский осмотр</h2>

<div class="card" style="margin-bottom: 20px;">
    <strong>Пациент:</strong>
    <?= e($patient['last_name']) ?>
    <?= e($patient['first_name']) ?>
    <?= e($patient['middle_name']) ?>
    <br>

    <strong>№ карты:</strong>
    <?= e($patient['medical_card_number']) ?>
</div>

<form action="save.php" id="visit-form" method="POST">
<input type="hidden" name="patient_id" value="<?= e($patientId) ?>">
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

<div class="form-group employer-select">

    <label for="employerSearch">
        Место работы, учебы
    </label>

    <div class="dropdown-wrapper">

        <input
            type="text"
            id="employerSearch"
            placeholder="Начните вводить название..."
            autocomplete="off"
        >

        <div id="employerDropdown" class="dropdown"></div>

    </div>

    <input type="hidden" name="employer_id" id="employerId">
    <input type="hidden" name="organization_name" id="organizationName">

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
<input type="text" name="inn" id="inn" maxlength="12" >
</div>

<div class="form-group">
<label>ОГРН (ОГРНИП)</label>
<input type="text" name="ogrn" id="ogrn" maxlength="15" >
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
<input type="text" name="employer_region" id="region" value="Красноярский край">
</div>

<div class="form-group">
<label>Регион</label>
<input type="text" name="employer_district" id="district" id="district">
</div>

<div class="form-group">
<label>Населенный пункт</label>
<input type="text" name="employer_locality" id="locality" value="г. Красноярск">
</div>
</div>

<div class="row">
<div class="form-group">
<label>Улица</label>
<input type="text" name="employer_street" id="street">
</div>

<div class="form-group">
<label>Дом</label>
<input type="text" name="employer_house" id="house">
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


</form>
</div>

<?php

require_once __DIR__ . '/../includes/footer.php';