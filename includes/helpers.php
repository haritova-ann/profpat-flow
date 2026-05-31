<?php

function e($text): string
{
    return htmlspecialchars(
        $text ?? '',
        ENT_QUOTES | ENT_SUBSTITUTE,
        'UTF-8'
    );
}

function formatDate($date, $empty = ''): string
{
    if (!$date) {
        return $empty;
    }

    return date('d.m.Y', strtotime($date));
}

/**
 * Форматирует пол пациента (полный или краткий вариант)
 * 
 * @param array $data Массив с данными пациента
 * @param bool $short Если true, вернет "М" или "Ж", если false — "Мужской" или "Женский"
 */
function formatGender(array $data, bool $short = false): string {
    $genderValue = $data['gender'] ?? '';

    if ($short) {
        return $genderValue === 'male' ? 'М' : ($genderValue === 'female' ? 'Ж' : '');
    }

    return $genderValue === 'male' ? 'Мужской' : ($genderValue === 'female' ? 'Женский' : '');
}

function formatFullName(array $data): string
{
    return trim(
        ($data['last_name'] ?? '') . ' ' .
        ($data['first_name'] ?? '') . ' ' .
        ($data['middle_name'] ?? '')
    );
}

function sanitizeFileName($name): string
{
    return preg_replace(
        '/[^a-zA-Zа-яА-Я0-9._-]/u',
        '_',
        $name
    );
}

function normalizeName($text): string
{
    return mb_convert_case(
        mb_strtolower(trim($text), 'UTF-8'),
        MB_CASE_TITLE,
        'UTF-8'
    );
}

function formatAddress($document)
{
    return trim(implode(', ', array_filter([
        $document['region'] ?? '',
        $document['district'] ?? '',
        $document['locality'] ?? '',
        !empty(trim($document['street'] ?? ''))
            ? 'ул. ' . $document['street']
            : '',

        !empty(trim($document['house'] ?? ''))
            ? 'д. ' . $document['house']
            : '',

        !empty(trim($document['building'] ?? ''))
            ? 'корп. ' . $document['building']
            : '',

        !empty(trim($document['flat'] ?? ''))
            ? 'кв. ' . $document['flat']
            : ''
    ])));
}

function formatEmployerAddress($document)
{
    return trim(implode(', ', array_filter([
        $document['employer_region'] ?? '',
        $document['employer_district'] ?? '',
        $document['employer_locality'] ?? '',
        !empty(trim($document['employer_street'] ?? ''))
            ? 'ул. ' . $document['employer_street']
            : '',

        !empty(trim($document['employer_house'] ?? ''))
            ? 'д. ' . $document['employer_house']
            : '',

        !empty(trim($document['employer_building'] ?? ''))
            ? 'корп. ' . $document['employer_building']
            : '',

        !empty(trim($document['employer_flat'] ?? ''))
            ? 'кв. ' . $document['employer_flat']
            : ''
    ])));
}

/**
 * Форматирует документ удостоверения личности в единую строку
 */
function formatIdentityDocument(array $data): string {
    // 1. Определяем понятное название типа документа
    $documentTypes = [
        'passport' => 'Паспорт РФ',
        'passport_foreign' => 'Паспорт иностранного гражданина',
        'residence_permit' => 'ВНЖ'
    ];
    $documentType = $documentTypes[$data['document_type']] ?? ($data['document_type'] ?? 'Документ');

    // 2. Собираем основную часть (Тип, Серия, Номер)
    $parts = [$documentType];

    if (!empty($data['document_series'])) {
        $parts[] = 'серия ' . trim($data['document_series']);
    }
    
    if (!empty($data['document_number'])) {
        $parts[] = '№ ' . trim($data['document_number']);
    }

    $documentBase = implode(' ', $parts);

    // 3. Собираем информацию о выдаче (Кем и Когда)
    $authorityParts = [];
    if (!empty($data['document_authority'])) {
        $authorityParts[] = trim($data['document_authority']);
    }
    
    if (!empty($data['document_date'])) {
        // Переводим дату из YYYY-MM-DD в человеческий формат DD.MM.YYYY
        $authorityParts[] = date('d.m.Y', strtotime($data['document_date']));
    }

    $documentAuthority = implode(' ', $authorityParts);

    // 4. Объединяем все вместе через запятую
    if (!empty($documentAuthority)) {
        return $documentBase . ', выдан ' . $documentAuthority;
    }

    return $documentBase;
}

function splitDate($date): array
{
    $ts = strtotime($date);

    if (!$ts) {
        return [
            'day' => '',
            'month' => '',
            'year' => ''
        ];
    }

    return [
        'day' => date('d', $ts),
        'month' => date('m', $ts),
        'year' => date('Y', $ts),
    ];
}

function formatGynecology(array $json): string
{
    if (empty($json['gynecology'])) {
        return '';
    }

    $date = formatDate($json['gynecology']['date'] ?? null);
    $result = $json['gynecology']['result'] ?? '';

    return trim($date . ' Гинеколог ' . $result);
}