<?php
/**
 * Страница редактирования медосмотра
 * 
 * Назначение: позволяет исправить данные уже созданного медосмотра
 * Загружает существующие данные и отправляет изменения в update.php
 */

require_once __DIR__ . '/../includes/bootstrap.php';

// Контекст страницы для header (активные пункты, условия отображения)
$page = 'visit';

// Получаем ID визита
$visitId = $_GET['id'] ?? null;

if (!$visitId) {
    die('Не передан ID осмотра');
}

// Загружаем данные визита с информацией о пациенте
$stmt = $pdo->prepare("
    SELECT 
        v.*,
        p.last_name,
        p.first_name,
        p.middle_name,
        p.medical_card_number
    FROM visits v
    JOIN patients p ON p.id = v.patient_id
    WHERE v.id = :id
");
$stmt->execute(['id' => $visitId]);
$visit = $stmt->fetch();

if (!$visit) {
    die('Осмотр не найден');
}

// Загружаем вредные факторы для этого визита
// Получаем коды через JOIN с таблицами visit_hazard_factors и hazard_factors
$stmt = $pdo->prepare("
    SELECT hf.code
    FROM visit_hazard_factors vhf
    JOIN hazard_factors hf ON hf.id = vhf.hazard_factor_id
    WHERE vhf.visit_id = :visit_id
");
$stmt->execute(['visit_id' => $visitId]);
$hazardCodes = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Объединяем коды в строку через запятую для отображения в поле
$hazardFactorsString = implode(', ', $hazardCodes);

// ======================
// Настраиваем header
// ======================
$pageTitle = 'Редактирование осмотра';

$topbarLeft = [
    [
        'label' => '← Медицинский осмотр',
        'href' => '/visit/visit.php?id=' . $visitId
    ],
    [
        'type' => 'submit',
        'label' => 'Сохранить',
        'form' => 'visit-form',
        'class' => 'topbar-primary'
    ]

];

require_once __DIR__ . '/../includes/header.php';
?>

<!-- =========================
    Основной контент
========================= -->
<div class="container">
<h2>Редактирование осмотра</h2>

<div class="card" style="margin-bottom: 20px;">
    <strong>Пациент:</strong>
    <?= e($visit['last_name']) ?>
    <?= e($visit['first_name']) ?>
    <?= e($visit['middle_name']) ?>
    <br>

    <strong>№ карты:</strong>
    <?= e($visit['medical_card_number']) ?>
</div>

<!-- 
    Форма отправляет данные в update.php
    Используем скрытое поле visit_id для передачи ID осмотра
-->
<form action="update.php" id="visit-form" method="POST">
<input type="hidden" name="visit_id" value="<?= e($visitId) ?>">
<input type="hidden" name="patient_id" value="<?= e($visit['patient_id']) ?>">

<div class="row">
<div class="form-group">
<label>Дата медицинского осмотра</label>
<input 
    type="text" 
    name="exam_date" 
    value="<?= e(formatDate($visit['exam_date'])) ?>" 
    readonly
>
</div>

<div class="form-group">
<label for="examType">Вид медицинского осмотра</label>
    <select name="exam_type" id="examType">
        <option value="periodic" <?= $visit['exam_type'] === 'periodic' ? 'selected' : '' ?>>
            Периодический
        </option>
        <option value="preliminary" <?= $visit['exam_type'] === 'preliminary' ? 'selected' : '' ?>>
            Предварительный
        </option>
        <option value="ad-hoc" <?= $visit['exam_type'] === 'ad-hoc' ? 'selected' : '' ?>>
            Внеочередной
        </option>
    </select>
</div>
</div>

<div class="form-group">
<label>Место работы, учебы</label>
<input 
    type="text" 
    name="organization_name" 
    value="<?= e($visit['organization_name']) ?>" 
    required
>
</div>

<div class="form-group">
<label>Структурное подразделение</label>
<input 
    type="text" 
    name="organization_department" 
    value="<?= e($visit['organization_department']) ?>"
>
</div>

<div class="form-group">
<label>Профессия (должность)</label>
<input 
    type="text" 
    name="position" 
    value="<?= e($visit['position']) ?>"
>
</div>

<div class="form-group">
<label>Вид работ, выполяемой работником</label>
<input 
    type="text" 
    name="hazard_factors" 
    value="<?= e($hazardFactorsString) ?>" 
    required
>
</div>

<div class="row">

<div class="form-group">
<label for="psychiatricExam">Необходимость психиатрического освидетельствования</label>
<select name="psychiatric_exam" id="psychiatricExam">
    <option value="false" <?= !$visit['psychiatric_exam'] ? 'selected' : '' ?>>Нет</option>
    <option value="true" <?= $visit['psychiatric_exam'] ? 'selected' : '' ?>>Да</option>
    </select>
</div>

<div class="form-group">
    <label for="psychiatricFactors">Пункт психиатрического освидетельствования</label>
    <input 
        type="text" 
        name="psychiatric_factors" 
        id="psychiatricFactors"
        value="<?= e($visit['psychiatric_factors']) ?>"
    >
</div>
                             
</div>

<div class="row">

<div class="form-group">
<label>ИНН</label>
<input 
    type="text" 
    name="inn" 
    value="<?= e($visit['inn']) ?>" 
    required
>
</div>

<div class="form-group">
<label>ОГРН (ОГРНИП)</label>
<input 
    type="text" 
    name="ogrn" 
    value="<?= e($visit['ogrn']) ?>" 
    required
>
</div>

<div class="form-group">
<label>ОКВЭД</label>
<input 
    type="text" 
    name="okvd" 
    value="<?= e($visit['okvd']) ?>" 
    required
>
</div>
</div>

<div class="row">

<div class="form-group">
<label>Номер телефона работодателя</label>
<input 
    type="text" 
    name="employer_phone" 
    value="<?= e($visit['employer_phone']) ?>" 
    placeholder="+7 ___ ___ __ __"
>
</div>

<div class="form-group">
<label>Адрес электронной почты работодателя</label>
<input 
    type="email" 
    name="employer_email" 
    value="<?= e($visit['employer_email']) ?>" 
    placeholder="example@mail.com"
>
</div>
</div>

<h3>Адрес юридического лица</h3>

<div class="row">
<div class="form-group">
<label>Субъект РФ</label>
<input 
    type="text" 
    name="employer_region" 
    value="<?= e($visit['employer_region']) ?>" 
    required
>
</div>

<div class="form-group">
<label>Регион</label>
<input 
    type="text" 
    name="employer_district" 
    value="<?= e($visit['employer_district']) ?>"
>
</div>

<div class="form-group">
<label>Населенный пункт</label>
<input 
    type="text" 
    name="employer_locality" 
    value="<?= e($visit['employer_locality']) ?>" 
    required
>
</div>
</div>

<div class="row">
<div class="form-group">
<label>Улица</label>
<input 
    type="text" 
    name="employer_street" 
    value="<?= e($visit['employer_street']) ?>" 
    required
>
</div>

<div class="form-group">
<label>Дом</label>
<input 
    type="text" 
    name="employer_house" 
    value="<?= e($visit['employer_house']) ?>" 
    required
>
</div>
</div>

<div class="row">
<div class="form-group">
<label>Корпус</label>
<input 
    type="text" 
    name="employer_building" 
    value="<?= e($visit['employer_building']) ?>"
>
</div>

<div class="form-group">
<label>Квартира</label>
<input 
    type="text" 
    name="employer_flat" 
    value="<?= e($visit['employer_flat']) ?>"
>
</div>
</div>

<button type="submit">Сохранить изменения</button>

</form>
</div>

<script>
    const need = document.getElementById('psychiatricExam');
    const point = document.getElementById('psychiatricFactors');

    function togglePsy() {
        if (need.value === 'true') {
            point.disabled = false;
            point.parentElement.style.opacity = '1';
        } else {
            point.disabled = true;
            point.value = '';
            point.parentElement.style.opacity = '0.5';
        }
    }

    need.addEventListener('change', togglePsy);

    togglePsy(); // инициализация при загрузке
</script>

<?php

require_once __DIR__ . '/../includes/footer.php';