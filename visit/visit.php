<?php
/**
 * Страница детального просмотра медосмотра
 * 
 * Назначение: отображение всех данных о конкретном медосмотре
 * и предоставление возможности печати связанных документов
 */

require_once __DIR__ . '/../config/db.php';

// ======================
// Получаем ID визита из URL
// ======================
$visitId = $_GET['id'] ?? null;

if (!$visitId) {
    die("Не указан ID медосмотра");
}

// ======================
// Загружаем данные визита + пациента
// ======================
$stmt = $pdo->prepare("
    SELECT 
        v.id AS visit_id,
        v.*,
        p.*,
        STRING_AGG(hf.code, ', ') AS hazard_factors
    FROM visits v
    JOIN patients p ON p.id = v.patient_id
    LEFT JOIN visit_hazard_factors vhf ON vhf.visit_id = v.id
    LEFT JOIN hazard_factors hf ON hf.id = vhf.hazard_factor_id
    WHERE v.id = :id
    GROUP BY v.id, p.id
");

$stmt->execute(['id' => $visitId]);
$data = $stmt->fetch();

if (!$data) {
    die("Медосмотр не найден");
}

// ======================
// Вспомогательная функция для форматирования дат
// ======================
function formatDate($date) {
    return $date ? date('d.m.Y', strtotime($date)) : '—';
}

// ======================
// Вспомогательная функция для безопасного вывода текста
// ======================
function e($text) {
    return htmlspecialchars($text ?? '');
}

// Форматируем адрес проживания
    $address = trim(implode(', ', array_filter([
        $data['region'],
        $data['district'],
        $data['locality'],
        $data['street'] ? 'ул. ' . $data['street'] : '',
        $data['house'] ? 'д. ' . $data['house'] : '',
        $data['building'] ? 'корп. ' . $data['building'] : '',
        $data['flat'] ? 'кв. ' . $data['flat'] : ''
    ])));

// Форматируем документ
    $documentTypes = [
        'passport' => 'Паспорт РФ',
        'passport_foreign' => 'Паспорт иностранного гражданина',
        'residence_permit' => 'ВНЖ'
    ];
    $documentType = $documentTypes[$data['document_type']] ?? $data['document_type'];
    $document = trim($documentType . ': серия ' . $data['document_series'] . ' №' . $data['document_number'] . ' выдан: ' . $data['document_authority'] . ' ' . $data['document_authority_code']  . ' ' . $data['document_date']);

    // Форматируем адрес работодателя
$employerAddress = trim(implode(', ', array_filter([
    $data['employer_region'],
    $data['employer_district'],
    $data['employer_locality'],
    $data['employer_street'],
    $data['employer_house'] ? 'д. ' . $data['employer_house'] : '',
    $data['employer_building'] ? 'корп. ' . $data['employer_building'] : '',
    $data['employer_flat'] ? 'кв. ' . $data['employer_flat'] : ''
])));
?>

<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="UTF-8">
<title>Медосмотр от <?= formatDate($data['exam_date']) ?></title>
<link rel="stylesheet" href="/assets/css/forms.css">
</head>

<body>

<div class="container">

<!-- Навигация -->
<a href="/patient/patient.php?id=<?= $data['patient_id'] ?>" class="back-link">
    ← К карточке пациента
</a>

<h2>Медицинский осмотр от <?= formatDate($data['exam_date']) ?></h2>

<!-- Кнопка редактирования -->
<div style="margin-bottom: 20px;">
    <a href="/visit/edit.php?id=<?= $data['visit_id'] ?>" class="edit-btn">
        Редактировать осмотр
    </a>
</div>

<!-- ======================
     БЛОК 1: Данные пациента
     ====================== -->
<div class="card">
    <div class="section-title">Данные пациента</div>

    <div class="info-row">
        <div class="info-label">№ амбулаторной карты:</div>
        <div class="info-value"><?= e($data['medical_card_number']) ?></div>
    </div>
    
    <div class="info-row">
        <div class="info-label">ФИО:</div>
        <div class="info-value">
            <?= e($data['last_name']) ?> 
            <?= e($data['first_name']) ?> 
            <?= e($data['middle_name']) ?>
        </div>
    </div>
    
    <div class="info-row">
        <div class="info-label">Дата рождения:</div>
        <div class="info-value"><?= formatDate($data['birth_date']) ?></div>
    </div>
    
    <div class="info-row">
        <div class="info-label">Пол:</div>
        <div class="info-value">
            <?= $data['gender'] === 'male' ? 'Мужской' : 'Женский' ?>
        </div>
    </div>
    
    <div class="info-row">
        <div class="info-label">СНИЛС:</div>
        <div class="info-value"><?= e($data['snils']) ?></div>
    </div>
    
    <div class="info-row">
        <div class="info-label">Документ:</div>
        <div class="info-value">
            <?= e($document) ?>
        </div>
    </div>
    
    <div class="info-row">
        <div class="info-label">Телефон:</div>
        <div class="info-value"><?= e($data['phone_number']) ?></div>
    </div>
    
    <div class="info-row">
        <div class="info-label">Email:</div>
        <div class="info-value"><?= e($data['email']) ?></div>
    </div>
    
    <div class="info-row">
        <div class="info-label">Адрес проживания:</div>
        <div class="info-value">
            <?= e($address) ?>
        </div>
    </div>
</div>

<!-- ======================
     БЛОК 2: Данные медосмотра
     ====================== -->
<div class="card">
    <div class="section-title">Данные медицинского осмотра</div>
    
    <div class="info-row">
        <div class="info-label">Дата медосмотра:</div>
        <div class="info-value"><?= formatDate($data['exam_date']) ?></div>
    </div>
    
    <div class="info-row">
        <div class="info-label">Вид медосмотра:</div>
        <div class="info-value">
            <?php
            $examTypes = [
                'periodic' => 'Периодический',
                'preliminary' => 'Предварительный',
                'ad-hoc' => 'Внеочередной'
            ];
            echo $examTypes[$data['exam_type']] ?? e($data['exam_type']);
            ?>
        </div>
    </div>
    
    <div class="info-row">
        <div class="info-label">Вредные факторы:</div>
        <div class="info-value"><?= e($data['hazard_factors']) ?></div>
    </div>
    
    <div class="info-row">
        <div class="info-label">ОПО: </div>
        <div class="info-value">
            <?= $data['psychiatric_exam'] ? 'Требуется' : 'Не требуется' ?>
        </div>
    </div>
    
    <?php if ($data['psychiatric_exam']): ?>
    <div class="info-row">
        <div class="info-label">Пункт психиатрического освидетельствования:</div>
        <div class="info-value"><?= e($data['psychiatric_factors']) ?></div>
    </div>
    <?php endif; ?>
</div>

<!-- ======================
     БЛОК 3: Данные работодателя
     ====================== -->
<div class="card">
    <div class="section-title">Данные работодателя</div>
    
    <div class="info-row">
        <div class="info-label">Место работы:</div>
        <div class="info-value"><?= e($data['organization_name']) ?></div>
    </div>
    
    <div class="info-row">
        <div class="info-label">Структурное подразделение:</div>
        <div class="info-value"><?= e($data['organization_department']) ?></div>
    </div>
    
    <div class="info-row">
        <div class="info-label">Профессия (должность):</div>
        <div class="info-value"><?= e($data['position']) ?></div>
    </div>
    
    <div class="info-row">
        <div class="info-label">ИНН:</div>
        <div class="info-value"><?= e($data['inn']) ?></div>
    </div>
    
    <div class="info-row">
        <div class="info-label">ОГРН (ОГРНИП):</div>
        <div class="info-value"><?= e($data['ogrn']) ?></div>
    </div>
    
    <div class="info-row">
        <div class="info-label">Телефон работодателя:</div>
        <div class="info-value"><?= e($data['employer_phone']) ?></div>
    </div>
    
    <div class="info-row">
        <div class="info-label">Email работодателя:</div>
        <div class="info-value"><?= e($data['employer_email']) ?></div>
    </div>
    
    <div class="info-row">
        <div class="info-label">Юридический адрес:</div>
        <div class="info-value">
            <?= e($employerAddress) ?>
        </div>
    </div>
</div>

<!-- ======================
     БЛОК 4: Печать документов
     ====================== -->
<div class="card">
    <div class="section-title">Печать документов</div>
    
    <div class="print-buttons">
        <button class="print-btn" onclick="printDocument('ambulatory_card')">
            Амбулаторная карта
        </button>
        
        <button class="print-btn" onclick="printDocument('contract')">
            Договор об оказании платных медицинских услуг
        </button>
        
        <button class="print-btn" onclick="printDocument('medical_consent')">
            Согласие на медицинское вмешательство
        </button>
        
        <button class="print-btn" onclick="printDocument('personal_data_consent')">
            Согласие на ОПД
        </button>

        <button class="print-btn" onclick="printDocument('psyhiatric_certificate')">
            Справка психиатра-нарколога
        </button>
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
    const visitId = <?= $data['visit_id'] ?>;
    
    // Открываем в новом окне, чтобы не покидать текущую страницу
    window.open(
        `/visit/print_document.php?visit_id=${visitId}&type=${documentType}`,
        '_blank'
    );
}
</script>

</body>
</html>