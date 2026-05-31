<?php

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../includes/helpers.php';


use PhpOffice\PhpWord\TemplateProcessor;

try {

    // ======================
    // ПАРАМЕТРЫ
    // ======================
    $documentId = $_GET['id'] ?? null;
    $printType = $_GET['type'] ?? null;

    if (!$documentId || !$printType) {
        throw new Exception('Не указаны параметры');
    }

    // ======================
    // ДОКУМЕНТ + ПАЦИЕНТ
    // ======================
    $stmt = $pdo->prepare("
        SELECT
            d.*,
            p.*
        FROM patient_documents d
        JOIN patients p
            ON p.id = d.patient_id
        WHERE d.id = :id
    ");

    $stmt->execute([
        'id' => $documentId
    ]);

    $document = $stmt->fetch();

    if (!$document) {
        throw new Exception('Документ не найден');
    }

    // ======================
    // JSON DATA
    // ======================
    $json = json_decode($document['data'], true);

    // ======================
    // ШАБЛОНЫ
    // ======================
    $templates = [
        'ambulatory_card' => 'Амбулаторная карта.docx',
        'sanatory_card' => 'Санаторно-курортная карта.docx',
        'sanatory_certificate' => 'Санаторно-курортная справка.docx'
    ];

    if (!isset($templates[$printType])) {
        throw new Exception('Неизвестный тип документа');
    }

    $templatePath =
        __DIR__ .
        '/../assets/templates/' .
        $templates[$printType];

    if (!file_exists($templatePath)) {
        throw new Exception('Шаблон не найден');
    }

    // ======================
    // TEMPLATE PROCESSOR
    // ======================
    $templateProcessor = new TemplateProcessor($templatePath);

    // ======================
    // ОБЩИЕ ДАННЫЕ
    // ======================
    fillCommonData(
        $templateProcessor,
        $document
    );

    // ======================
    // ДОКУМЕНТЫ
    // ======================
    switch ($printType) {

        case 'ambulatory_card':
            fillAmbulatoryCard(
                $templateProcessor,
                $document
            );
            break;

        case 'sanatory_card':
            fillSanatoryCard(
                $templateProcessor,
                $document,
                $json
            );
            break;

        case 'sanatory_certificate':
            fillSanatoryCertificate(
                $templateProcessor,
                $document
            );
            break;

        default:
            throw new Exception('Тип документа не поддерживается');
    }

    // ======================
    // ИМЯ ФАЙЛА
    // ======================
    $fileName =
        sanitizeFileName(
            $document['last_name']
        ) .
        '_' .
        $printType .
        '_' .
        date('Y-m-d') .
        '.docx';

    // ======================
    // ВЫГРУЗКА
    // ======================
    header(
        'Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document'
    );

    header(
        'Content-Disposition: attachment; filename="' .
        $fileName .
        '"'
    );

    header('Cache-Control: max-age=0');

    $templateProcessor->saveAs('php://output');

    exit;

} catch (Exception $e) {

    http_response_code(500);

    echo 'Ошибка: ' . $e->getMessage();
}

//
// =====================================================
// HELPERS
// =====================================================
//

function fillCommonData($templateProcessor, $document)
{
    $templateProcessor->setValue(
        'FULL_NAME',
        formatFullName($document)
    );

    $templateProcessor->setValue(
        'LAST_NAME',
        $document['last_name'] ?? ''
    );

    $templateProcessor->setValue(
        'FIRST_NAME',
        $document['first_name'] ?? ''
    );

    $templateProcessor->setValue(
        'MIDDLE_NAME',
        $document['middle_name'] ?? ''
    );

    $birth = splitDate($document['birth_date']);
    $templateProcessor->setValue('D', $birth['day'] ?? '');
    $templateProcessor->setValue('M', $birth['month'] ?? '');
    $templateProcessor->setValue('Y', $birth['year'] ?? '');
    
    $templateProcessor->setValue(
        'EXAM_DATE',
        formatDate($document['exam_date'])
    );

    $templateProcessor->setValue(
        'CARD_NUMBER',
        $document['medical_card_number'] ?? ''
    );

    $templateProcessor->setValue(
        'SNILS',
        $document['snils'] ?? ''
    );

    $templateProcessor->setValue(
        'PHONE',
        $document['phone_number'] ?? ''
    );

    $templateProcessor->setValue(
        'EMAIL',
        $document['email'] ?? ''
    );

    $templateProcessor->setValue(
        'ADDRESS',
        formatAddress($document)
    );

    $templateProcessor->setValue(
        'REGION',
        $document['region'] ?? ''
    );

    $templateProcessor->setValue(
        'DISTRICT',
        $document['district'] ?? ''
    );

    $templateProcessor->setValue(
        'LOCALITY',
        $document['locality'] ?? ''
    );

    $templateProcessor->setValue(
        'STREET',
        $document['street'] ?? ''
    );
    
    $templateProcessor->setValue(
        'H',
        $document['house'] ?? ''
    );

    $templateProcessor->setValue(
        'B',
        $document['building'] ?? ''
    );

    $templateProcessor->setValue(
        'F',
        $document['flat'] ?? ''
    );

    $templateProcessor->setValue(
        'GENDER',
        formatGender($document)
    );
}

function fillAmbulatoryCard($templateProcessor, $document)
{
    $templateProcessor->setValue(
        'DIAGNOSIS',
        $document['diagnosis'] ?? ''
    );

    $templateProcessor->setValue(
        'ICD',
        $document['icd'] ?? ''
    );
}

function fillSanatoryCertificate($templateProcessor, $document)
{
    $templateProcessor->setValue(
        'DIAGNOSIS',
        $document['diagnosis'] ?? ''
    );

    $templateProcessor->setValue(
        'ICD',
        $document['icd'] ?? ''
    );
}

function fillSanatoryCard(
    $templateProcessor,
    $document,
    $json
)
{
    // ОСНОВНОЕ
    $templateProcessor->setValue(
        'DIAGNOSIS',
        $document['diagnosis'] ?? ''
    );

    $templateProcessor->setValue(
        'ICD',
        $document['icd'] ?? ''
    );

    $templateProcessor->setValue(
        'COMPLAINTS',
        $document['complaints'] ?? ''
    );

    $templateProcessor->setValue(
        'ANAMNESIS',
        $document['anamnesis'] ?? ''
    );

    // ОАК
    $templateProcessor->setValue(
        'CBC_DATE',
        formatDate($json['cbc']['date']) ?? ''
    );

    $templateProcessor->setValue(
        'CBC_L',
        $json['cbc']['leukocytes'] ?? ''
    );

    $templateProcessor->setValue(
        'CBC_E',
        $json['cbc']['erythrocytes'] ?? ''
    );

    $templateProcessor->setValue(
        'CBC_HB',
        $json['cbc']['hemoglobin'] ?? ''
    );

    $templateProcessor->setValue(
        'CBC_P',
        $json['cbc']['platelets'] ?? ''
    );

    $templateProcessor->setValue(
        'CBC_ESR',
        $json['cbc']['esr'] ?? ''
    );

    // БИОХИМИЯ
    $templateProcessor->setValue(
        'BIO_DATE',
        formatDate($json['biochemistry']['date']) ?? ''
    );

    $templateProcessor->setValue(
        'BIO_GLUCOSE',
        $json['biochemistry']['glucose'] ?? ''
    );

    $templateProcessor->setValue(
        'BIO_CHOLESTEROL',
        $json['biochemistry']['cholesterol'] ?? ''
    );

    // ОАМ
    $templateProcessor->setValue(
        'URINE_DATE',
        formatDate($json['urine']['date']) ?? ''
    );
    
    $templateProcessor->setValue(
        'URINE_D',
        $json['urine']['density'] ?? ''
    );

    $templateProcessor->setValue(
        'URINE_PROTEIN',
        $json['urine']['protein'] ?? ''
    );

    $templateProcessor->setValue(
        'URINE_GLUCOSE',
        $json['urine']['glucose'] ?? ''
    );

    // ЭКГ
    $templateProcessor->setValue(
        'ECG_DATE',
        formatDate($json['ecg']['date']) ?? ''
    );

    $templateProcessor->setValue(
        'ECG_RHYTHM',
        $json['ecg']['rhythm'] ?? ''
    );

    $templateProcessor->setValue(
        'ECG_HR',
        $json['ecg']['hr'] ?? ''
    );

    $templateProcessor->setValue(
        'ECG_NOTES',
        $json['ecg']['notes'] ?? ''
    );

    // ФЛГ
    $templateProcessor->setValue(
        'FLG_DATE',
        formatDate($json['flg']['date']) ?? ''
    );

    $templateProcessor->setValue(
        'FLG_RESULT',
        $json['flg']['result'] ?? ''
    );

    // ГИНЕКОЛОГ
    $templateProcessor->setValue(
        'GYN_RESULT',
        formatGynecology($json)
    );
}

