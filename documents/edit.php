<?php

require_once __DIR__ . '/../includes/bootstrap.php';

$docId = $_GET['id'] ?? null;

if (!$docId) {
    die('Не передан id');
}

$stmt = $pdo->prepare("
    SELECT 
        d.*,
        p.last_name,
        p.first_name,
        p.middle_name,
        p.medical_card_number,
        p.gender
    FROM patient_documents d
    JOIN patients p ON p.id = d.patient_id
    WHERE d.id = :docId
");

$stmt->execute(['docId' => $docId]);
$doc = $stmt->fetch();

if (!$doc) {
    die('Документ не найден');
}

$data = json_decode($doc['data'], true);

$gender = $doc['gender'];

// ======================
// Настраиваем header
// ======================
$pageTitle = 'Редактировать санаторно-курортный прием';

$topbarLeft = [
    [
        'label' => '← Прием',
        'href' => '/documents/view.php?id=' . $docId
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
    <h2>Редактировать санаторно-курортный прием</h2>

    <!-- Данные пациента -->
    <div class="card" style="margin-bottom: 20px;">
        <strong>Пациент:</strong>
        <?= e($doc['last_name']) ?>
        <?= e($doc['first_name']) ?>
        <?= e($doc['middle_name']) ?>
        <br>

        <strong>№ карты:</strong>
        <?= e($doc['medical_card_number']) ?>
    </div>

    <!-- =========================
     Форма ввода
    ========================= -->
    <form action="update.php" id="sanatory-form" method="POST">

        <input type="hidden" name="patient_id" value="<?= e($doc['patient_id']) ?>">
        <input type="hidden" name="document_id" value="<?= e($docId) ?>">
        <!-- ========================= -->
        <!-- ОСНОВНАЯ ИНФОРМАЦИЯ -->
        <!-- ========================= -->
        <div class="section">
            <h3>Основная информация</h3>

            <div class="row">
                <div class="form-group">
                    <label>Дата осмотра</label>
                    <input type="date" class="fill-today" name="exam_date" value='<?= e($doc['exam_date']) ?>'>
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
                    <input type="text" name="diagnosis" value="<?= e($doc['diagnosis']) ?>">
                </div>

                <div class="form-group small">
                    <label>МКБ-10</label>
                    <input type="text" name="icd" value="<?= e($doc['icd']) ?>">
                </div>
            </div>

            <div class="form-group">
                <label>Жалобы</label>
                <textarea name="complaints"><?= e(nl2br($doc['complaints'])) ?></textarea>
            </div>

            <div class="form-group">
                <label>Анамнез</label>
                <textarea name="anamnesis"><?= e($doc['anamnesis']) ?></textarea>
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
                        <input type="date" class="fill-today" name="cbc_date" value="<?= e($data['cbc']['date']) ?>">
                    </div>

                    <div class="form-group">
                        <label>Лейкоциты</label>
                        <input type="text" name="cbc_leukocytes" value="<?= e($data['cbc']['leukocytes']) ?>">
                    </div>

                    <div class="form-group">
                        <label>Эритроциты</label>
                        <input type="text" name="cbc_erythrocytes" value="<?= e($data['cbc']['erythrocytes']) ?>">
                    </div>

                    <div class="form-group">
                        <label>Гемоглобин</label>
                        <input type="text" name="cbc_hemoglobin" value="<?= e($data['cbc']['hemoglobin']) ?>">
                    </div>

                    <div class="form-group">
                        <label>Тромбоциты</label>
                        <input type="text" name="cbc_platelets" value="<?= e($data['cbc']['platelets']) ?>">
                    </div>

                    <div class="form-group">
                        <label>СОЭ</label>
                        <input type="text" name="cbc_esr" value="<?= e($data['cbc']['esr']) ?>">
                    </div>
                </div>
            </div>

            <!-- Биохимия -->
            <div class="section">
                <h3>Биохимический анализ крови</h3>

                <div class="row">
                    <div class="form-group small">
                        <label>Дата</label>
                        <input type="date" class="fill-today" name="bio_date" value="<?= e($data['biochemistry']['date']) ?>">
                    </div>

                    <div class="form-group">
                        <label>Глюкоза</label>
                        <input type="text" name="bio_glucose" value="<?= e($data['biochemistry']['glucose']) ?>">
                    </div>

                    <div class="form-group">
                        <label>Холестерин</label>
                        <input type="text" name="bio_cholesterol" value="<?= e($data['biochemistry']['cholesterol']) ?>">
                    </div>
                </div>
            </div>

            <!-- ОАМ -->
            <div class="section">
                <h3>Общий анализ мочи</h3>

                <div class="row">
                    <div class="form-group small">
                        <label>Дата</label>
                        <input type="date" class="fill-today" name="urine_date" value="<?= e($data['urine']['date']) ?>">
                    </div>

                    <div class="form-group">
                        <label>Удельный вес</label>
                        <input type="text" name="urine_density" value="<?= e($data['urine']['density']) ?>">
                    </div>

                    <div class="form-group">
                        <label>Белок в моче (+/-)</label>
                        <input type="text" name="urine_protein" value="<?= e($data['urine']['protein']) ?>">
                    </div>

                    <div class="form-group">
                        <label>Глюкоза в моче (+/-)</label>
                        <input type="text" name="urine_glucose" value="<?= e($data['urine']['glucose']) ?>">
                    </div>
                </div>
            </div>

            <!-- ЭКГ -->
            <div class="section">
                <h3>ЭКГ</h3>

                <div class="row">
                    <div class="form-group small">
                        <label>Дата</label>
                        <input type="date" class="fill-today" name="ecg_date" value="<?= e($data['ecg']['date']) ?>">
                    </div>

                    <div class="form-group">
                        <label>Ритм</label>
                        <input type="text" name="ecg_rhythm" value="<?= e($data['ecg']['rhythm']) ?>">
                    </div>

                    <div class="form-group small">
                        <label>ЧСС</label>
                        <input type="text" name="ecg_hr" value="<?= e($data['ecg']['hr']) ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label>Дополнительно</label>
                    <textarea name="ecg_notes"><?= e($data['ecg']['notes']) ?></textarea>
                </div>
            </div>

            <!-- ФЛГ -->
            <div class="section">
                <h3>Флюорография</h3>

                <div class="row">
                    <div class="form-group small">
                        <label>Дата</label>
                        <input type="date" class="fill-today" name="flg_date" value="<?= e($data['flg']['date']) ?>">
                    </div>

                    <div class="form-group">
                        <label>Заключение</label>
                        <input type="text" name="flg_result" value="<?= e($data['flg']['result']) ?>">
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
                            <input type="date" class="fill-today" name="gyn_date" value="<?= e($data['gynecology']['date']) ?>">
                        </div>

                        <div class="form-group">
                            <label>Заключение</label>
                            <input type="text" name="gyn_result" value="<?= e($data['gynecology']['result']) ?>">
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