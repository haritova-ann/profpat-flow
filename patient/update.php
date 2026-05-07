<?php
/**
 * Обработчик обновления данных пациента
 * 
 * Назначение: принимает данные из формы edit.php и обновляет запись в БД
 * 
 * Основные отличия от save.php:
 * 1. Использует UPDATE вместо INSERT
 * 2. Требует patient_id для определения какую запись обновлять
 */

require_once __DIR__ . '/../config/db.php';

try {

    // ======================
    // 1. Получаем данные из формы
    // ======================
    $patientId = $_POST['id'] ?? null;
    if (!$patientId) {
        throw new Exception("Не передан ID пациента");
    }

    $medicalCardNumber = $_POST['medical_card_number'] ?? null;

    $lastName = $_POST['last_name'] ?? '';
    $firstName = $_POST['first_name'] ?? '';
    $middleName = $_POST['middle_name'] ?? null;

    $birthDate = $_POST['birth_date'] ?? null;
    $gender = $_POST['gender'] ?? null;

    $documentType = $_POST['document_type'] ?? null;
    $documentSeries = $_POST['document_series'] ?? null;
    $documentNumber = $_POST['document_number'] ?? null;
    $documentAuthority = $_POST['document_authority'] ?? null;
    $documentAuthorityCode = $_POST['document_authority_code'] ?? null;
    $documentDate = $_POST['document_date'] ?? null;

    $snils = $_POST['snils'] ?? null;
    $phone = $_POST['phone_number'] ?? null;
    $email = $_POST['email'] ?? null;

    $region = $_POST['region'] ?? null;
    $district = $_POST['district'] ?? null;
    $locality = $_POST['locality'] ?? null;
    $street = $_POST['street'] ?? null;
    $house = $_POST['house'] ?? null;
    $building = $_POST['building'] ?? null;
    $flat = $_POST['flat'] ?? null;

    // ======================
    // 2. Валидация обязательных полей
    // ======================
    if (!$medicalCardNumber || !$lastName || !$firstName || !$birthDate) {
        throw new Exception("Не заполнены обязательные поля");
    }

    // ======================
    // 3. Преобразование даты рождения (ДД.ММ.ГГГГ → YYYY-MM-DD)
    // ======================
    $birthDateObj = DateTime::createFromFormat('d.m.Y', $birthDate);

    if (!$birthDateObj) {
        throw new Exception("Неверный формат даты рождения");
    }

    $birthDateSql = $birthDateObj->format('Y-m-d');

    // ======================
    // 2. Обновление данных пациента
    // ======================
    // Используем UPDATE для изменения существующей записи
    // WHERE id = :visit_id гарантирует, что обновится только нужная запись
    $stmt = $pdo->prepare("
        UPDATE patients SET
            medical_card_number = :medical_card_number,
            last_name = :last_name,
            first_name = :first_name,
            middle_name = :middle_name,
            birth_date = :birth_date,
            gender = :gender,
            document_type = :document_type,
            document_series = :document_series,
            document_number = :document_number,
            document_authority = :document_authority,
            document_authority_code = :document_authority_code,
            document_date = :document_date,
            snils = :snils,
            phone_number = :phone_number,
            email = :email,
            region = :region,
            district = :district,
            locality = :locality,
            street = :street,
            house = :house,
            building = :building,
            flat = :flat
        WHERE id = :id
        RETURNING id
    ");

    $stmt->execute([
        'id' => $patientId,
        'medical_card_number' => $medicalCardNumber,
        'last_name' => $lastName,
        'first_name' => $firstName,
        'middle_name' => $middleName,
        'birth_date' => $birthDateSql,
        'gender' => $gender,
        'document_type' => $documentType,
        'document_series' => $documentSeries,
        'document_number' => $documentNumber,
        'document_authority' => $documentAuthority,
        'document_authority_code' => $documentAuthorityCode,
        'document_date' => $documentDate,
        'snils' => $snils,
        'phone_number' => $phone,
        'email' => $email,
        'region' => $region,
        'district' => $district,
        'locality' => $locality,
        'street' => $street,
        'house' => $house,
        'building' => $building,
        'flat' => $flat
    ]);

    $patient = $stmt->fetch();
    $patientId = $patient['id'];

    // ======================
    // 4. Возврат на страницу осмотра
    // ======================
    header("Location: /patient/patient.php?id=" . $patientId);
    exit;

} catch (Exception $e) {

    // В случае ошибки откатываем все изменения
    echo "Ошибка при обновлении: " . $e->getMessage();
}