<?php

require __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die('Invalid request');
}

$patientId = $_POST['patient_id'] ?? null;

if (!$patientId) {
    die('Не передан patient_id');
}

/**
 * 1. Основные данные
 */
$examDate = $_POST['exam_date'] ?? null;
$diagnosis = $_POST['diagnosis'] ?? null;
$icd = $_POST['icd'] ?? null;
$complaints = $_POST['complaints'] ?? null;
$anamnesis = $_POST['anamnesis'] ?? null;

$needCard = isset($_POST['need_card']) ? 1 : 0;
$needCertificate = isset($_POST['need_certificate']) ? 1 : 0;

/**
 * 2. Сбор "карты" в структуру
 */
$cardData = [
    'cbc' => [
        'date' => $_POST['cbc_date'] ?? null,
        'leukocytes' => $_POST['cbc_leukocytes'] ?? null,
        'erythrocytes' => $_POST['cbc_erythrocytes'] ?? null,
        'hemoglobin' => $_POST['cbc_hemoglobin'] ?? null,
        'platelets' => $_POST['cbc_platelets'] ?? null,
        'esr' => $_POST['cbc_esr'] ?? null,
    ],

    'biochemistry' => [
        'date' => $_POST['bio_date'] ?? null,
        'cholesterol' => $_POST['bio_cholesterol'] ?? null,
        'glucose' => $_POST['bio_glucose'] ?? null,
    ],

    'urine' => [
        'date' => $_POST['urine_date'] ?? null,
        'density' => $_POST['urine_density'] ?? null,
        'protein' => $_POST['urine_protein'] ?? null,
        'glucose' => $_POST['urine_glucose'] ?? null,
    ],

    'ecg' => [
        'date' => $_POST['ecg_date'] ?? null,
        'rhythm' => $_POST['ecg_rhythm'] ?? null,
        'hr' => $_POST['ecg_hr'] ?? null,
        'notes' => $_POST['ecg_notes'] ?? null,
    ],

    'flg' => [
        'date' => $_POST['flg_date'] ?? null,
        'result' => $_POST['flg_result'] ?? null,
    ]
];

/**
 * 3. Гинекология (только если есть в POST)
 */
if (isset($_POST['gyn_date']) || isset($_POST['gyn_result'])) {
    $cardData['gynecology'] = [
        'date' => $_POST['gyn_date'] ?? null,
        'result' => $_POST['gyn_result'] ?? null,
    ];
}

/**
 * 4. JSONB упаковка
 */
$jsonData = json_encode($cardData, JSON_UNESCAPED_UNICODE);

/**
 * 5. Запись в БД
 */
$stmt = $pdo->prepare("
    INSERT INTO patient_documents (
        patient_id,
        exam_date,
        diagnosis,
        icd,
        complaints,
        anamnesis,
        need_card,
        need_certificate,
        data,
        created_at
    )
    VALUES (
        :patient_id,
        :exam_date,
        :diagnosis,
        :icd,
        :complaints,
        :anamnesis,
        :need_card,
        :need_certificate,
        :data,
        NOW()
    )
");

$stmt->execute([
    'patient_id' => $patientId,
    'exam_date' => $examDate,
    'diagnosis' => $diagnosis,
    'icd' => $icd,
    'complaints' => $complaints,
    'anamnesis' => $anamnesis,
    'need_card' => $needCard,
    'need_certificate' => $needCertificate,
    'data' => $jsonData
]);

header("Location: /patient/patient.php?id=" . $patientId);
exit;