<?php
/**
 * Обработчик генерации документов
 * 
 * Назначение: загружает шаблон документа, заполняет его данными пациента и осмотра,
 * и отдаёт готовый файл на скачивание
 * 
 * Принимает параметры:
 * - visit_id: ID медосмотра
 * - type: тип документа (ambulatory_card, contract, medical_consent, personal_data_consent)
 */

require_once __DIR__ . '/../includes/bootstrap.php';

// Подключаем автозагрузчик Composer, который загрузит PHPWord
require_once __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpWord\TemplateProcessor;

try {
    // ======================
    // 1. Получаем параметры
    // ======================
    $visitId = $_GET['visit_id'] ?? null;
    $documentType = $_GET['type'] ?? null;

    if (!$visitId || !$documentType) {
        throw new Exception("Не указаны обязательные параметры");
    }

    // ======================
    // 2. Загружаем данные из БД
    // ======================
    // Получаем все данные одним запросом через JOIN
    $stmt = $pdo->prepare("
        SELECT 
        v.id AS visit_id,
        v.*,
        p.*,
        STRING_AGG(DISTINCT hf.code, ', ') AS hazard_factors,
        STRING_AGG(DISTINCT pf.name, ', ') AS psychiatric_factors_name
        FROM visits v
        JOIN patients p ON p.id = v.patient_id
        LEFT JOIN visit_hazard_factors vhf ON vhf.visit_id = v.id
        LEFT JOIN hazard_factors hf ON hf.id = vhf.hazard_factor_id
        LEFT JOIN visit_psychiatric_factors vpf ON vpf.visit_id = v.id
        LEFT JOIN psychiatric_factors pf ON pf.id = vpf.psychiatric_factor_id
        WHERE v.id = :id
        GROUP BY v.id, p.id
    ");
    
    $stmt->execute(['id' => $visitId]);
    $data = $stmt->fetch();

    if (!$data) {
        throw new Exception("Медосмотр не найден");
    }

    // ======================
    // 3. Определяем путь к шаблону
    // ======================
    // Сопоставляем тип документа с файлом шаблона
    $templates = [
        'ambulatory_card' => 'Амбулаторная карта.docx',
        'contract' => 'Договор об оказании платных медицинских услуг.docx',
        'medical_consent' => 'Согласие на медицинское вмешательство.docx',
        'personal_data_consent' => 'Согласние на ОПД.docx',
        'psyhiatric_certificate' => 'Справка психиатра нарколога.docx',
        'medical_record_extract' => 'Выписка из медицинской карты.docx',
        'pack_with_psy' => 'Пакет документов с психиатрическим освидетельствованием.docx',
        'pack_without_psy' => 'Пакет документов без психиатрического освидетельствования.docx' 
    ];

    if (!isset($templates[$documentType])) {
        throw new Exception("Неизвестный тип документа");
    }

    $templatePath = __DIR__ . '/../assets/templates/' . $templates[$documentType];

    if (!file_exists($templatePath)) {
        throw new Exception("Файл шаблона не найден: " . $templatePath);
    }

    // ======================
    // 4. Загружаем шаблон
    // ======================
    // TemplateProcessor - класс PHPWord для работы с шаблонами
    // Ищет и заменяет плейсхолдеры вида ${ИМЯ_ПЕРЕМЕННОЙ}
    $templateProcessor = new TemplateProcessor($templatePath);

    // ======================
    // 5. Подготавливаем данные для подстановки
    // ======================

    
    // Форматируем тип осмотра
    $examTypes = [
        'periodic' => 'ПЕРИОДИЧЕСКИЙ',
        'preliminary' => 'ПРЕДВАРИТЕЛЬНЫЙ',
        'ad-hoc' => 'ВНЕОЧЕРЕДНОЙ'
    ];
    $examType = $examTypes[$data['exam_type']] ?? $data['exam_type'];

    // Форматируем тип осмотра для вставки в шпаку заключения
    $examTypesFormattedForConclusion = [
        'periodic' => 'ПЕРИОДИЧЕСКОГО',
        'preliminary' => 'ПРЕДВАРИТЕЛЬНОГО',
        'ad-hoc' => 'ВНЕОЧЕРЕДНОГО'
    ];
    $examTypeFormattedForConclusion = $examTypesFormattedForConclusion[$data['exam_type']] ?? $data['exam_type'];

    // Форматируем тип осмотра для вставки в заключение
    $examTypesFormatted = [
        'periodic' => 'периодического',
        'preliminary' => 'предварительного',
        'ad-hoc' => 'внеочередного'
    ];
    $examTypeFormatted = $examTypesFormatted[$data['exam_type']] ?? $data['exam_type'];
    
    $documentAuthority = trim($data['document_authority'] . ' ' . $data['document_date']);
    
    // ======================
    // 6. Заменяем плейсхолдеры
    // ======================
    // Метод setValue заменяет ${ИМЯ} на значение
    // Важно: имена переменных должны совпадать с плейсхолдерами в шаблоне
    
    // Данные пациента
    $templateProcessor->setValue('CARD_NUMBER', $data['medical_card_number'] ?? '');
    $templateProcessor->setValue('FULL_NAME', formatFullName($data) ?? '');
    $templateProcessor->setValue('LAST_NAME', $data['last_name'] ?? '');
    $templateProcessor->setValue('FIRST_NAME', $data['first_name'] ?? '');
    $templateProcessor->setValue('MIDDLE_NAME', $data['middle_name'] ?? '');
    $templateProcessor->setValue('BIRTH_DATE', formatDate($data['birth_date']) ?? '');
    $templateProcessor->setValue('GENDER', formatGender($data) ?? '');
    $templateProcessor->setValue('SNILS', $data['snils'] ?? '');
    $templateProcessor->setValue('DOCUMENT', formatIdentityDocument($data) ?? '');
    $templateProcessor->setValue('DOCUMENT_AUTHORITY', $documentAuthority);
    $templateProcessor->setValue('PHONE', $data['phone_number'] ?? '');
    $templateProcessor->setValue('EMAIL', $data['email'] ?? '');
    $templateProcessor->setValue('ADDRESS',  formatAddress($data));
    
    // Данные осмотра
    $templateProcessor->setValue('EXAM_DATE', formatDate($data['exam_date']) ?? '');
    $templateProcessor->setValue('EXAM_TYPE', $examType);
    $templateProcessor->setValue('EXAM_TYPE_FORMATTED', $examTypeFormatted);
    $templateProcessor->setValue('EXAM_TYPE_CONCL', $examTypeFormattedForConclusion);
    $templateProcessor->setValue('HAZARD_FACTORS', $data['hazard_factors'] ?? '');
    $templateProcessor->setValue('PSYCHIATRIC_EXAM', $data['psychiatric_exam'] ? 'Да' : 'Нет');
    $templateProcessor->setValue('PSYCHIATRIC_FACTORS_NAME', $data['psychiatric_factors_name'] ?? '');
    
    // Данные работодателя
    $templateProcessor->setValue('ORGANIZATION_NAME', $data['organization_name'] ?? '');
    $templateProcessor->setValue('ORGANIZATION_DEPARTMENT', $data['organization_department'] ?? '');
    $templateProcessor->setValue('POSITION', $data['position'] ?? '');
    $templateProcessor->setValue('INN', $data['inn'] ?? '');
    $templateProcessor->setValue('OGRN', $data['ogrn'] ?? '');
    $templateProcessor->setValue('OKVD', $data['okvd'] ?? '');
    $templateProcessor->setValue('EMPLOYER_PHONE', $data['employer_phone'] ?? '');
    $templateProcessor->setValue('EMPLOYER_EMAIL', $data['employer_email'] ?? '');
    $templateProcessor->setValue('EMPLOYER_ADDRESS', formatEmployerAddress($data));

    // ======================
    // 7. Генерируем имя файла
    // ======================
    // Создаём понятное имя файла с ФИО и датой
    $fileName = $data['last_name'] . '_' . $documentType . '_' . date('Y-m-d') . '.docx';
    
    // Убираем недопустимые символы из имени файла
    $fileName = preg_replace('/[^a-zA-Zа-яА-Я0-9._-]/u', '_', $fileName);

    // ======================
    // 8. Отдаём файл на скачивание
    // ======================
    // Устанавливаем заголовки для скачивания файла
    header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
    header('Content-Disposition: attachment; filename="' . $fileName . '"');
    header('Cache-Control: max-age=0');
    
    // Сохраняем документ в выходной поток (php://output)
    // Это отправляет файл напрямую браузеру без сохранения на диск
    $templateProcessor->saveAs('php://output');
    exit;

} catch (Exception $e) {
    // В случае ошибки показываем сообщение
    http_response_code(500);
    echo "Ошибка при генерации документа: " . $e->getMessage();
}
