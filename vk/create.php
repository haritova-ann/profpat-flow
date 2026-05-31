<?php

require_once __DIR__ . '/../includes/bootstrap.php';

$patientId = $_GET['patient_id'] ?? null;
$visitId = $_GET['visit_id'] ?? null;

if (!$patientId) {
    die('Не передан patient_id');
}

/**
 * Проверка прав доступа
 */
if (!hasRole(['admin', 'doctor'])) {
    die('У вас недостаточно прав для создания протокола ВК');
}

/**
 * Пациент
 */
$stmt = $pdo->prepare("
    SELECT
        id,
        last_name,
        first_name,
        middle_name,
        medical_card_number
    FROM patients
    WHERE id = :id
");

$stmt->execute([
    'id' => $patientId
]);

$patient = $stmt->fetch();

if (!$patient) {
    die('Пациент не найден');
}

/**
 * Вредные факторы из визита
 */
$hazardFactors = [];

if ($visitId) {

    $stmt = $pdo->prepare("
        SELECT
            hf.id,
            hf.code,
            hf.name
        FROM visit_hazard_factors vhf
        JOIN hazard_factors hf
            ON hf.id = vhf.hazard_factor_id
        WHERE vhf.visit_id = :visit_id
        ORDER BY hf.code
    ");

    $stmt->execute([
        'visit_id' => $visitId
    ]);

    $hazardFactors = $stmt->fetchAll();
}

/**
 * Члены ВК
 */
$stmt = $pdo->query("
    SELECT
        id,
        full_name,
        position
    FROM doctors
    WHERE is_vk_member = TRUE
    ORDER BY full_name
");

$doctors = $stmt->fetchAll();

// ======================
// Настраиваем header
// ======================
$pageTitle = 'Новый протокол ВК';

$topbarLeft = [
    [
        'label' => '← Медицинский осмотр',
        'href' => '/visit/visit.php?id=' . e($visitId),
    ],
    [
        'type' => 'submit',
        'label' => 'Сохранить',
        'form' => 'vk-form',
        'class' => 'topbar-primary'
    ]
];

require_once __DIR__ . '/../includes/header.php';
?>

<!-- =========================
Контейнер
========================= -->
<div class="container">

    <h2>Заключение врачебной комиссии</h2>

    <!-- Пациент -->
    <div class="card" style="margin-bottom: 20px;">

        <strong>Пациент:</strong>

        <?= e($patient['last_name']) ?>
        <?= e($patient['first_name']) ?>
        <?= e($patient['middle_name']) ?>

        <br>

        <strong>№ карты:</strong>
        <?= e($patient['medical_card_number']) ?>

    </div>

    <!-- =========================
    Форма
    ========================= -->
    <form action="save.php" id="vk-form" method="POST">

        <input type="hidden"
               name="patient_id"
               value="<?= e($patientId) ?>">

        <input type="hidden"
               name="visit_id"
               value="<?= e($visitId) ?>">

        <!-- ========================= -->
        <!-- ОСНОВНАЯ ИНФОРМАЦИЯ -->
        <!-- ========================= -->
        <div class="section">

            <h3>Основная информация</h3>

            <div class="row">

                <div class="form-group small">
                    <label>Дата протокола</label>

                    <input type="date"
                           name="protocol_date"
                           value="<?= date('Y-m-d') ?>"
                           required>
                </div>

                <div class="form-group small">
                    <label>Номер протокола</label>

                    <input type="text"
                           name="protocol_number"
                           required>
                </div>

            </div>

            <div class="form-group">
                <label>Диагноз</label>

                <textarea name="diagnosis"></textarea>
            </div>

        </div>

        <!-- ========================= -->
        <!-- ПРОТИВОПОКАЗАННЫЕ ФАКТОРЫ -->
        <!-- ========================= -->
        <div class="section">

            <h3>Противопоказанные вредные факторы</h3>

            <?php if ($hazardFactors): ?>

                <div class="document-options">

                    <?php foreach ($hazardFactors as $factor): ?>

                        <label class="document-option">

                            <input type="checkbox"
                                   name="hazard_factors[]"
                                   value="<?= e($factor['id']) ?>">

                            <div class="document-option-content">

                                <span class="document-title">
                                    <?= e($factor['code']) ?>
                                </span>

                                <div>
                                    <?= e($factor['name']) ?>
                                </div>

                            </div>

                        </label>

                    <?php endforeach; ?>

                </div>

            <?php else: ?>

                <div class="card">
                    Для данного медосмотра вредные факторы не найдены
                </div>

            <?php endif; ?>

        </div>

        <!-- ========================= -->
        <!-- РЕШЕНИЕ ВК -->
        <!-- ========================= -->
        <div class="section">

            <h3>Решение ВК</h3>

            <div class="decision-options">

                <label class="decision-option">

                    <input type="radio"
                           name="decision"
                           value="fit"
                           checked>

                    Допущен к работе
                </label>

                <label class="decision-option">

                    <input type="radio"
                           name="decision"
                           value="temporary">

                    Временные противопоказания
                </label>

                <label class="decision-option">

                    <input type="radio"
                           name="decision"
                           value="permanent">

                    Постоянные противопоказания
                </label>

            </div>

        </div>

        <!-- ========================= -->
        <!-- ВРЕМЕННЫЕ ПРОТИВОПОКАЗАНИЯ -->
        <!-- ========================= -->
        <div id="temporary-fields" class="section hidden">

            <h3>Временные противопоказания</h3>

            <div class="row">

                <div class="form-group small">
                    <label>Срок до</label>

                    <input type="date"
                           name="temporary_until">
                </div>

            </div>

            <div class="form-group">
                <label>Обоснование решения</label>

                <textarea name="temporary_reason"></textarea>
            </div>

            <div class="form-group">
                <label>Рекомендации</label>

                <textarea name="temporary_recommendations"></textarea>
            </div>

        </div>

        <!-- ========================= -->
        <!-- СОСТАВ ВК -->
        <!-- ========================= -->
        <div class="section">

            <h3>Состав врачебной комиссии</h3>

            <div class="row">

                <div class="form-group">
                    <label>Председатель ВК</label>

                    <select name="chairman_id" required>

                        <option value="">
                            Выберите врача
                        </option>

                        <?php foreach ($doctors as $doctor): ?>

                            <option value="<?= e($doctor['id']) ?>">

                                <?= e($doctor['full_name']) ?>

                                <?php if ($doctor['position']): ?>
                                    — <?= e($doctor['position']) ?>
                                <?php endif; ?>

                            </option>

                        <?php endforeach; ?>

                    </select>
                </div>

            </div>

            <div class="row">

                <div class="form-group">
                    <label>Член комиссии</label>

                    <select name="member1_id">

                        <option value="">
                            Выберите врача
                        </option>

                        <?php foreach ($doctors as $doctor): ?>

                            <option value="<?= e($doctor['id']) ?>">

                                <?= e($doctor['full_name']) ?>

                            </option>

                        <?php endforeach; ?>

                    </select>
                </div>

                <div class="form-group">
                    <label>Член комиссии</label>

                    <select name="member2_id">

                        <option value="">
                            Выберите врача
                        </option>

                        <?php foreach ($doctors as $doctor): ?>

                            <option value="<?= e($doctor['id']) ?>">

                                <?= e($doctor['full_name']) ?>

                            </option>

                        <?php endforeach; ?>

                    </select>
                </div>

                <div class="form-group">
                    <label>Член комиссии</label>

                    <select name="member3_id">

                        <option value="">
                            Выберите врача
                        </option>

                        <?php foreach ($doctors as $doctor): ?>

                            <option value="<?= e($doctor['id']) ?>">

                                <?= e($doctor['full_name']) ?>

                            </option>

                        <?php endforeach; ?>

                    </select>
                </div>

            </div>

        </div>

        <button type="submit" class="submit-btn">
            Сохранить
        </button>

    </form>

</div>

<script>

document.addEventListener('DOMContentLoaded', () => {

    const radios = document.querySelectorAll('input[name="decision"]');

    const temporaryFields =
        document.getElementById('temporary-fields');

    function toggleTemporaryFields() {

        const selected = document.querySelector(
            'input[name="decision"]:checked'
        );

        temporaryFields.classList.toggle(
            'hidden',
            selected?.value !== 'temporary'
        );
    }

    radios.forEach(radio => {
        radio.addEventListener(
            'change',
            toggleTemporaryFields
        );
    });

    toggleTemporaryFields();

});

</script>

</body>
</html>