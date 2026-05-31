<?php

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpWord\TemplateProcessor;

try {

    // ======================
    // ПАРАМЕТРЫ
    // ======================

    $id = $_GET['id'] ?? null;
    $visitId = $_GET['visit_id'] ?? null;

    if (!$id) {
        throw new Exception('Не указан ID заключения ВК');
    }

    // ======================
    // Загружаем данные визита + пациента
    // ======================
    $stmt = $pdo->prepare("
        SELECT 
            v.id AS visit_id,
            v.*,
            p.*
        FROM visits v
        JOIN patients p ON p.id = v.patient_id
        WHERE v.id = :id
    ");

    $stmt->execute(['id' => $visitId]);
    $data = $stmt->fetch();

    // ======================
    // Загружаем вредные факторы визита
    // ======================   
    $stmt = $pdo->prepare("
        SELECT hf.code
        FROM visit_hazard_factors vhf
        JOIN hazard_factors hf ON hf.id = vhf.hazard_factor_id
        WHERE vhf.visit_id = :visit_id
        ORDER BY hf.code
    ");

    $stmt->execute(['visit_id' => $visitId]);

    $visitFactors =
        array_column(
            $stmt->fetchAll(),
            'code'
        );

    // ======================
    // ЗАКЛЮЧЕНИЕ ВК
    // ======================

    $stmt = $pdo->prepare("
        SELECT
            vk.*,

            ch.full_name AS chairman_name,

            m1.full_name AS member1_name,
            m2.full_name AS member2_name,
            m3.full_name AS member3_name
            

        FROM vk_conclusions vk

        LEFT JOIN doctors ch
            ON ch.id = vk.chairman_id

        LEFT JOIN doctors m1
            ON m1.id = vk.member1_id

        LEFT JOIN doctors m2
            ON m2.id = vk.member2_id

        LEFT JOIN doctors m3
            ON m3.id = vk.member3_id

        WHERE vk.id = :id
    ");

    $stmt->execute([
        'id' => $id
    ]);

    $vk = $stmt->fetch();

    if (!$vk) {
        throw new Exception('Заключение ВК не найдено');
    }

    // ======================
    // ПРОТИВОПОКАЗАННЫЕ ФАКТОРЫ
    // ======================

    $stmt = $pdo->prepare("
        SELECT
            hf.code
        FROM vk_conclusion_factors vkf

        JOIN hazard_factors hf
            ON hf.id = vkf.hazard_factor_id

        WHERE vkf.vk_conclusion_id = :id

        ORDER BY hf.code
    ");

    $stmt->execute([
        'id' => $id
    ]);

    $disallowedFactors =
        array_column(
            $stmt->fetchAll(),
            'code'
        );

    $allowedFactors =
        array_diff(
            $visitFactors,
            $disallowedFactors
        );

    // ======================
    // СТРОКА ФАКТОРОВ
    // ======================

    $hazardFactorsText =
    implode(', ', $visitFactors);

    $disallowedFactorsText =
        implode(', ', $disallowedFactors);

    $allowedFactorsText =
        implode(', ', $allowedFactors);

    // ======================
    // ШАБЛОН
    // ======================

    $templatePath =
        __DIR__
        . '/../assets/templates/Протокол ВК и заключение.docx';

    if (!file_exists($templatePath)) {
        throw new Exception('Шаблон не найден');
    }

    // ======================
    // TEMPLATE PROCESSOR
    // ======================

    $templateProcessor =
        new TemplateProcessor($templatePath);

    // ======================
    // ПАЦИЕНТ
    // ======================

    $templateProcessor->setValue('FULL_NAME', formatFullName($data) ?? '');
    $templateProcessor->setValue('BIRTH_DATE', formatDate($data['birth_date']) ?? '');
    $templateProcessor->setValue('GENDER', formatGender($data) ?? '');
    $templateProcessor->setValue('DOCUMENT', formatIdentityDocument($data) ?? '');
    $templateProcessor->setValue('SNILS', $data['snils'] ?? '');
    $templateProcessor->setValue('ADDRESS', formatAddress($data) ?? '');

    // ======================
    // РАБОТОДАТЕЛЬ
    // ======================

    $templateProcessor->setValue('EMPLOYER', $data['organization_name'] ?? '');
    $templateProcessor->setValue('DEPARTMENT', $data['organization_department'] ?? '');
    $templateProcessor->setValue('POSITION', $data['position'] ?? '');
    $templateProcessor->setValue('INN', $data['inn'] ?? '');
    $templateProcessor->setValue('OGRN', $data['ogrn'] ?? '');
    $templateProcessor->setValue('EMPLOYER_ADDRESS', formatEmployerAddress($data) ?? '');

    // ======================
    // ПРОТОКОЛ
    // ======================
    // Форматируем тип осмотра для вставки в шпаку заключения
    $examTypesFormattedForConclusion = [
        'periodic' => 'ПЕРИОДИЧЕСКОГО',
        'preliminary' => 'ПРЕДВАРИТЕЛЬНОГО',
        'ad-hoc' => 'ВНЕОЧЕРЕДНОГО'
    ];
    $examTypeFormattedForConclusion = $examTypesFormattedForConclusion[$data['exam_type']] ?? $data['exam_type'];
    $templateProcessor->setValue('EXAM_TYPE', $examTypeFormattedForConclusion ?? '');

    $protocolDate = splitDate($vk['protocol_date']);
    $templateProcessor->setValue('D', $protocolDate['day'] ?? '');
    $templateProcessor->setValue('M', $protocolDate['month'] ?? '');
    $templateProcessor->setValue('Y', $protocolDate['year'] ?? '');
    $templateProcessor->setValue('PROTOCOL_NUMBER', $vk['protocol_number'] ?? '');

    // ======================
    // ФАКТОРЫ
    // ======================
    $templateProcessor->setValue('HAZARD_FACTORS', $hazardFactorsText ?? '');
    $templateProcessor->setValue('DISALLOWED_FACTORS', $disallowedFactorsText ?? '');
    $templateProcessor->setValue('ALLOWED_FACTORS', $allowedFactorsText ?? '');

    // ======================
    // РЕШЕНИЕ ВК
    // ======================

    $decisionText = match ($vk['decision']) {

        'fit' =>
            'Работник признан пригодным по состоянию здоровья к выполнению отдельных видов работ.',

        'temporary' =>
            'Работник признан временно непригодным по состоянию здоровья к выполнению отдельных видов работ на срок до',

        'permanent' =>
            'Работник признан постоянно непригодным по состоянию здоровья к выполнению отдельных видов работ.',

        default =>
            ''
    };

    $templateProcessor->setValue('DECISION', $decisionText);

    // ======================
    // ВРЕМЕННЫЕ ПРОТИВОПОКАЗАНИЯ
    // ======================

    $templateProcessor->setValue('TEMPORARY_UNTIL', formatDate($vk['temporary_until'])) ?? '';
    $templateProcessor->setValue('TEMPORARY_REASON', $vk['temporary_reason'] ?? '');
    $templateProcessor->setValue('TEMPORARY_RECOMMENDATIONS', $vk['temporary_recommendations'] ?? '');

    // ======================
    // КОМИССИЯ
    // ======================

    $templateProcessor->setValue('CHAIRMAN', $vk['chairman_name'] ?? '');
    $templateProcessor->setValue('MEMBER1', $vk['member1_name'] ?? '');
    $templateProcessor->setValue('MEMBER2', $vk['member2_name'] ?? '');
    $templateProcessor->setValue('MEMBER3', $vk['member3_name'] ?? '');

    // ======================
    // ИМЯ ФАЙЛА
    // ======================

    $fileName =
        sanitizeFileName(
            $data['last_name']
        )
        . '_ВК_'
        . date('d-m-Y')
        . '.docx';

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