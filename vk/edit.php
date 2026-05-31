<?php

require_once __DIR__ . '/../includes/bootstrap.php';

$id = $_GET['id'] ?? null;

if (!$id) {
    die('Не передан ID заключения ВК');
}

/**
 * Проверка прав доступа
 */
if (!hasRole(['admin', 'doctor'])) {
    die('У вас недостаточно прав для редактирования протокола ВК');
}

/**
 * Загрузка данных протокола ВК
 */
$stmt = $pdo->prepare("
    SELECT
        vk.*,
        p.id AS patient_id,
        p.last_name,
        p.first_name,
        p.middle_name,
        p.medical_card_number,
        vk.visit_id
    FROM vk_conclusions vk
    JOIN patients p ON p.id = vk.patient_id
    WHERE vk.id = :id
");

$stmt->execute(['id' => $id]);
$vk = $stmt->fetch();

if (!$vk) {
    die('Заключение ВК не найдено');
}

/**
 * Загрузка противопоказанных факторов
 */
$stmt = $pdo->prepare("
    SELECT
        hf.id,
        hf.code,
        hf.name
    FROM vk_conclusion_factors vkf
    JOIN hazard_factors hf ON hf.id = vkf.hazard_factor_id
    WHERE vkf.vk_conclusion_id = :id
    ORDER BY hf.code
");

$stmt->execute(['id' => $id]);
$selectedFactors = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);

/**
 * Загрузка всех вредных факторов визита
 */
$stmt = $pdo->prepare("
    SELECT
        hf.id,
        hf.code,
        hf.name
    FROM visit_hazard_factors vhf
    JOIN hazard_factors hf ON hf.id = vhf.hazard_factor_id
    WHERE vhf.visit_id = :visit_id
    ORDER BY hf.code
");

$stmt->execute(['visit_id' => $vk['visit_id']]);
$hazardFactors = $stmt->fetchAll();

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
$pageTitle = 'Редактирование протокола ВК';

$topbarLeft = [
    [
        'label' => '← Заключение ВК',
        'href' => '/vk/vk.php?id=' . e($id),
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

<div class="container">

    <h2>Редактирование заключения врачебной комиссии</h2>

    <!-- Пациент -->
    <div class="card" style="margin-bottom: 20px;">

        <strong>Пациент:</strong>

        <?= e($vk['last_name']) ?>
        <?= e($vk['first_name']) ?>
        <?= e($vk['middle_name']) ?>

        <br>

        <strong>№ карты:</strong>
        <?= e($vk['medical_card_number']) ?>

    </div>

    <!-- =========================
    Форма
    ========================= -->
    <form action="update.php" id="vk-form" method="POST">

        <input type="hidden" name="id" value="<?= e($id) ?>">
        <input type="hidden" name="patient_id" value="<?= e($vk['patient_id']) ?>">
        <input type="hidden" name="visit_id" value="<?= e($vk['visit_id']) ?>">

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
                           value="<?= e($vk['protocol_date']) ?>"
                           required>
                </div>

                <div class="form-group small">
                    <label>Номер протокола</label>

                    <input type="text"
                           name="protocol_number"
                           value="<?= e($vk['protocol_number']) ?>"
                           required>
                </div>

            </div>

            <div class="form-group">
                <label>Диагноз</label>

                <textarea name="diagnosis"><?= e($vk['diagnosis']) ?></textarea>
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
                                   value="<?= e($factor['id']) ?>"
                                   <?= in_array($factor['id'], $selectedFactors) ? 'checked' : '' ?>>

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
                           <?= $vk['decision'] === 'fit' ? 'checked' : '' ?>>

                    Допущен к работе
                </label>

                <label class="decision-option">

                    <input type="radio"
                           name="decision"
                           value="temporary"
                           <?= $vk['decision'] === 'temporary' ? 'checked' : '' ?>>

                    Временные противопоказания
                </label>

                <label class="decision-option">

                    <input type="radio"
                           name="decision"
                           value="permanent"
                           <?= $vk['decision'] === 'permanent' ? 'checked' : '' ?>>

                    Постоянные противопоказания
                </label>

            </div>

        </div>

        <!-- ========================= -->
        <!-- ВРЕМЕННЫЕ ПРОТИВОПОКАЗАНИЯ -->
        <!-- ========================= -->
        <div id="temporary-fields" class="section <?= $vk['decision'] !== 'temporary' ? 'hidden' : '' ?>">

            <h3>Временные противопоказания</h3>

            <div class="row">

                <div class="form-group small">
                    <label>Срок до</label>

                    <input type="date"
                           name="temporary_until"
                           value="<?= e($vk['temporary_until']) ?>">
                </div>

            </div>

            <div class="form-group">
                <label>Обоснование решения</label>

                <textarea name="temporary_reason"><?= e($vk['temporary_reason']) ?></textarea>
            </div>

            <div class="form-group">
                <label>Рекомендации</label>

                <textarea name="temporary_recommendations"><?= e($vk['temporary_recommendations']) ?></textarea>
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

                            <option value="<?= e($doctor['id']) ?>"
                                    <?= $vk['chairman_id'] == $doctor['id'] ? 'selected' : '' ?>>

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

                            <option value="<?= e($doctor['id']) ?>"
                                    <?= $vk['member1_id'] == $doctor['id'] ? 'selected' : '' ?>>

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

                            <option value="<?= e($doctor['id']) ?>"
                                    <?= $vk['member2_id'] == $doctor['id'] ? 'selected' : '' ?>>

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

                            <option value="<?= e($doctor['id']) ?>"
                                    <?= $vk['member3_id'] == $doctor['id'] ? 'selected' : '' ?>>

                                <?= e($doctor['full_name']) ?>

                            </option>

                        <?php endforeach; ?>

                    </select>
                </div>

            </div>

        </div>

        <button type="submit" class="submit-btn">
            Сохранить изменения
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