<?php

require_once __DIR__ . '/../includes/bootstrap.php';

$patientId = $_GET['patient_id'] ?? null;

if (!$patientId) {
    die('Не передан patient_id');
}

$stmt = $pdo->prepare("SELECT last_name, first_name, middle_name, medical_card_number, gender FROM patients WHERE id = :id");
$stmt->execute(['id' => $patientId]);
$patient = $stmt->fetch();

if (!$patient) {
    die('Пациент не найден');
}

$gender = $patient['gender'];

// ======================
// Настраиваем header
// ======================
$pageTitle = ' Санаторно-курортное лечение';

$topbarLeft = [
    [
        'label' => '← Карта пациента',
        'href' => '/patient/patient.php?id=' . $patientId
    ],
    [
        'type' => 'submit',
        'label' => 'Сохранить',
        'form' => 'sanatory-form',
        'class' => 'topbar-primary'
    ]
];

require_once __DIR__ . '/../includes/header.php';
?>

    <!-- =========================
    Информационный блок
    ========================= -->
    <div class="container">
    <h2>Санаторно-курортное лечение</h2>

    <!-- Данные пациента -->
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
     Форма ввода
    ========================= -->
    <form action="save.php" id="sanatory-form" method="POST">

        <input type="hidden" name="patient_id" value="<?= e($patientId) ?>">

        <!-- ========================= -->
        <!-- ОСНОВНАЯ ИНФОРМАЦИЯ -->
        <!-- ========================= -->
        <div class="section">
            <h3>Основная информация</h3>

            <div class="row">
                <div class="form-group">
                    <label>Дата осмотра</label>
                    <input type="date" class="fill-today" name="exam_date">
                </div>

                <div class="form-group">
                    <label>Документы</label>

                    <div class="document-options">

                        <label class="document-option">
                            <input type="checkbox" id="need_card" name="need_card" value="1" checked>

                            <div class="document-option-content">
                                <span class="document-title">Санаторно-курортная карта</span>
                            </div>
                        </label>

                        <label class="document-option">
                            <input type="checkbox" id="need_certificate" name="need_certificate" value="1" checked>

                            <div class="document-option-content">
                                <span class="document-title">Санаторно-курортная справка</span>
                            </div>
                        </label>

                    </div>
                </div>
            </div>

            <div class="row">
                <div class="form-group">
                    <label>Диагноз</label>
                    <input type="text" name="diagnosis">
                </div>

                <div class="form-group small">
                    <label>МКБ-10</label>
                    <input type="text" name="icd">
                </div>
            </div>

            <div class="form-group">
                <label>Жалобы</label>
                <textarea name="complaints"></textarea>
            </div>

            <div class="form-group">
                <label>Анамнез</label>
                <textarea name="anamnesis"></textarea>
            </div>
        </div>

        <!-- ========================= -->
        <!-- КАРТА (СКРЫВАЕМАЯ) -->
        <!-- ========================= -->
        <div id="card-fields">

            <!-- ОАК -->
            <div class="section">
                <h3>Клинический анализ крови</h3>

                <div class="row">
                    <div class="form-group small">
                        <label>Дата</label>
                        <input type="date" class="fill-today" name="cbc_date">
                    </div>

                    <div class="form-group">
                        <label>Лейкоциты</label>
                        <input type="text" name="cbc_leukocytes">
                    </div>

                    <div class="form-group">
                        <label>Эритроциты</label>
                        <input type="text" name="cbc_erythrocytes">
                    </div>

                    <div class="form-group">
                        <label>Гемоглобин</label>
                        <input type="text" name="cbc_hemoglobin">
                    </div>

                    <div class="form-group">
                        <label>Тромбоциты</label>
                        <input type="text" name="cbc_platelets">
                    </div>

                    <div class="form-group">
                        <label>СОЭ</label>
                        <input type="text" name="cbc_esr">
                    </div>
                </div>
            </div>

            <!-- Биохимия -->
            <div class="section">
                <h3>Биохимический анализ крови</h3>

                <div class="row">
                    <div class="form-group small">
                        <label>Дата</label>
                        <input type="date" class="fill-today" name="bio_date">
                    </div>

                    <div class="form-group">
                        <label>Глюкоза</label>
                        <input type="text" name="bio_glucose">
                    </div>

                    <div class="form-group">
                        <label>Холестерин</label>
                        <input type="text" name="bio_cholesterol">
                    </div>
                </div>
            </div>

            <!-- ОАМ -->
            <div class="section">
                <h3>Общий анализ мочи</h3>

                <div class="row">
                    <div class="form-group small">
                        <label>Дата</label>
                        <input type="date" class="fill-today" name="urine_date">
                    </div>

                    <div class="form-group">
                        <label>Удельный вес</label>
                        <input type="text" name="urine_density">
                    </div>

                    <div class="form-group">
                        <label>Белок в моче (+/-)</label>
                        <input type="text" name="urine_protein" value="-">
                    </div>

                    <div class="form-group">
                        <label>Глюкоза в моче (+/-)</label>
                        <input type="text" name="urine_glucose" value="-">
                    </div>
                </div>
            </div>

            <!-- ЭКГ -->
            <div class="section">
                <h3>ЭКГ</h3>

                <div class="row">
                    <div class="form-group small">
                        <label>Дата</label>
                        <input type="date" class="fill-today" name="ecg_date">
                    </div>

                    <div class="form-group">
                        <label>Ритм</label>
                        <input type="text" name="ecg_rhythm" value="синусовый">
                    </div>

                    <div class="form-group small">
                        <label>ЧСС</label>
                        <input type="text" name="ecg_hr">
                    </div>
                </div>

                <div class="form-group">
                    <label>Дополнительно</label>
                    <textarea name="ecg_notes"></textarea>
                </div>
            </div>

            <!-- ФЛГ -->
            <div class="section">
                <h3>Флюорография</h3>

                <div class="row">
                    <div class="form-group small">
                        <label>Дата</label>
                        <input type="date" class="fill-today" name="flg_date">
                    </div>

                    <div class="form-group">
                        <label>Заключение</label>
                        <input type="text" name="flg_result" value="Без патологии">
                    </div>
                </div>
            </div>

            <!-- ГИНЕКОЛОГ -->
            <?php if ($gender === 'female'): ?>

                <div class="section">
                    <h3>Гинеколог</h3>

                    <div class="row">
                        <div class="form-group small">
                            <label>Дата</label>
                            <input type="date" class="fill-today" name="gyn_date">
                        </div>

                        <div class="form-group">
                            <label>Заключение</label>
                            <input type="text" name="gyn_result" value="Противопоказаний для санаторно-курортного лечения не выявлено">
                        </div>
                    </div>
                </div>

            <?php endif; ?>

        </div>

        <button type="submit" class="submit-btn">
            Сохранить
        </button>

    </form>

<script>
    document.addEventListener('DOMContentLoaded', () => {

        const needCard = document.getElementById('need_card');
        const cardFields = document.getElementById('card-fields');

        function toggleCard() {
            cardFields.classList.toggle('hidden', !needCard.checked);
        }

        needCard.addEventListener('change', toggleCard);

        toggleCard();
    });
</script>

<?php

require_once __DIR__ . '/../includes/footer.php';