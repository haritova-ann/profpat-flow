<?php

require_once __DIR__ . '/../includes/bootstrap.php';

$id = $_GET['id'] ?? null;

if (!$id) {
    die('Не передан id документа');
}

$stmt = $pdo->prepare("
    SELECT 
        d.*,
        p.last_name,
        p.first_name,
        p.middle_name,
        p.medical_card_number
    FROM patient_documents d
    JOIN patients p ON p.id = d.patient_id
    WHERE d.id = :id
");

$stmt->execute(['id' => $id]);
$doc = $stmt->fetch();

if (!$doc) {
    die('Документ не найден');
}

$data = json_decode($doc['data'], true);

// ======================
// Настраиваем header
// ======================
$pageTitle = 'Санаторно-курортное лечение';

$topbarLeft = [
    [
        'label' => '← Карта пациента',
        'href' => '/patient/patient.php?id=' . $doc['patient_id']
    ],
    [
        'label' => 'Редактировать',
        'href' => '/documents/edit.php?id=' . $doc['id'],
        'class' => 'topbar-primary'
    ]
];

require_once __DIR__ . '/../includes/header.php';
?>

    <!-- =========================
     Основной контент
    ========================= -->
    <div class="wrapper">

        <!-- LEFT -->
        <div>
            <!-- ======================
            БЛОК 1: Данные пациента
            ====================== -->
            <h2>Санаторно-курортная карта</h2>

            <div class="patient">
                <strong>
                    <?= e($doc['last_name']) ?>
                    <?= e($doc['first_name']) ?>
                    <?= e($doc['middle_name']) ?>
                </strong>
                <br>
                № карты: <?= e($doc['medical_card_number']) ?>
            </div>

            <!-- ========================= -->
            <!-- ОСНОВНОЕ -->
            <!-- ========================= -->
            <div class="section">
                <h3>Общая информация</h3>

                <div class="grid">
                    <div class="label">Дата осмотра</div>
                    <div class="value"><?= formatDate($doc['exam_date']) ?></div>

                    <div class="label">Диагноз</div>
                    <div class="value"><?= e($doc['diagnosis']) ?></div>

                    <div class="label">МКБ-10</div>
                    <div class="value"><?= e($doc['icd']) ?></div>

                    <div class="label">Жалобы</div>
                    <div class="value"><?= nl2br(e($doc['complaints'])) ?></div>

                    <div class="label">Анамнез</div>
                    <div class="value"><?= nl2br(e($doc['anamnesis'])) ?></div>
                </div>
            </div>

            <!-- ========================= -->
            <!-- ОАК -->
            <!-- ========================= -->
            <div class="section">
                <h3>Клинический анализ крови</h3>

                <div class="grid">
                    <div class="label">Дата</div>
                    <div class="value"><?= formatDate($data['cbc']['date']) ?? '-' ?></div>

                    <div class="label">Гемоглобин</div>
                    <div class="value"><?= $data['cbc']['hemoglobin'] ?? '-' ?></div>

                    <div class="label">Лейкоциты</div>
                    <div class="value"><?= $data['cbc']['leukocytes'] ?? '-' ?></div>

                    <div class="label">Эритроциты</div>
                    <div class="value"><?= $data['cbc']['erythrocytes'] ?? '-' ?></div>

                    <div class="label">Тромбоциты</div>
                    <div class="value"><?= $data['cbc']['platelets'] ?? '-' ?></div>

                    <div class="label">СОЭ</div>
                    <div class="value"><?= $data['cbc']['esr'] ?? '-' ?></div>
                </div>
            </div>

            <!-- ========================= -->
            <!-- БИОХИМИЯ -->
            <!-- ========================= -->
            <div class="section">
                <h3>Биохимический анализ</h3>

                <div class="grid">
                    <div class="label">Дата</div>
                    <div class="value"><?= formatDate($data['biochemistry']['date']) ?? '-' ?></div>

                    <div class="label">Холестерин</div>
                    <div class="value"><?= $data['biochemistry']['cholesterol'] ?? '-' ?></div>

                    <div class="label">Глюкоза</div>
                    <div class="value"><?= $data['biochemistry']['glucose'] ?? '-' ?></div>
                </div>
            </div>

            <!-- ========================= -->
            <!-- ОАМ -->
            <!-- ========================= -->
            <div class="section">
                <h3>Общий анализ мочи</h3>

                <div class="grid">
                    <div class="label">Дата</div>
                    <div class="value"><?= formatDate($data['urine']['date']) ?></div>

                    <div class="label">Удельный вес</div>
                    <div class="value"><?= $data['urine']['density'] ?? '-' ?></div>

                    <div class="label">Белок</div>
                    <div class="value"><?= $data['urine']['protein'] ?? '-' ?></div>

                    <div class="label">Глюкоза</div>
                    <div class="value"><?= $data['urine']['glucose'] ?? '-' ?></div>
                </div>
            </div>

            <!-- ========================= -->
            <!-- ЭКГ -->
            <!-- ========================= -->
            <div class="section">
                <h3>ЭКГ</h3>

                <div class="grid">
                    <div class="label">Дата</div>
                    <div class="value"><?= formatDate($data['ecg']['date']) ?? '-' ?></div>

                    <div class="label">Ритм</div>
                    <div class="value"><?= $data['ecg']['rhythm'] ?? '-' ?></div>

                    <div class="label">ЧСС</div>
                    <div class="value"><?= $data['ecg']['hr'] ?? '-' ?></div>

                    <div class="label">Дополнительно</div>
                    <div class="value"><?= nl2br($data['ecg']['notes'] ?? '-') ?></div>
                </div>
            </div>

            <!-- ========================= -->
            <!-- ФЛГ -->
            <!-- ========================= -->
            <div class="section">
                <h3>Флюорография</h3>

                <div class="grid">
                    <div class="label">Дата</div>
                    <div class="value"><?= formatDate($data['flg']['date']) ?? '-' ?></div>

                    <div class="label">Заключение</div>
                    <div class="value"><?= $data['flg']['result'] ?? '-' ?></div>
                </div>
            </div>

            <?php if (!empty($data['gynecology'])): ?>
            <div class="section">
                <h3>Гинеколог</h3>

                <div class="grid">
                    <div class="label">Дата</div>
                    <div class="value"><?= formatDate($data['gynecology']['date']) ?? '-' ?></div>

                    <div class="label">Заключение</div>
                    <div class="value"><?= $data['gynecology']['result'] ?? '-' ?></div>
                </div>
            </div>
            <?php endif; ?>

            <?php if (hasRole(['admin'])): ?>
                <form action="/documents/delete.php" method="POST" onsubmit="return confirm('Удалить прием?');">
                    <input type="hidden" name="id" value="<?= e($doc['id']) ?>">
                    <input type="hidden" name="patient_id" value="<?= e($doc['patient_id']) ?>">
                    <button type="submit">Удалить</button>
                </form>
            <?php endif; ?>
        </div>

        <!-- RIGHT -->
        <div class="sidebar">
        
            <!-- ======================
                БЛОК 4: Печать документов
                ====================== -->
            <div class="card">
                <div class="section-title">Печать документов</div>
                
                <div class="print-buttons">
                    <button class="print-btn" onclick="printDocument('ambulatory_card')">
                        Амбулаторная карта
                    </button>

                    <button class="print-btn" onclick="printDocument('sanatory_card')">
                        Санаторно-курортная карта
                    </button>
                    
                    <button class="print-btn" onclick="printDocument('sanatory_certificate')">
                        Санаторно-курортная справка
                    </button>
                </div>
            </div>

            
        </div>

    </div>

<script>
    /**
    * Функция для печати документа
    * 
    * @param {string} documentType - тип документа для генерации
    * 
    * Эта функция открывает новое окно с обработчиком print_document.php,
    * который сгенерирует DOCX файл и отдаст его на скачивание
    */
    function printDocument(documentType) {
        const documentId = <?= $doc['id'] ?>;
        
        // Открываем в новом окне, чтобы не покидать текущую страницу
        window.open(
            `/documents/print.php?id=${documentId}&type=${documentType}`,
            '_blank'
        );
    }
</script>
</body>

</html>