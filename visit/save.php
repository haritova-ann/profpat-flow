<?php

require_once __DIR__ . '/../config/db.php';

try {

    $pdo->beginTransaction();

    // ======================
    // 1. Получаем данные
    // ======================
    $patientId = $_POST['patient_id'] ?? null;
    $employerId = !empty($_POST['employer_id']) ? (int)$_POST['employer_id'] : null;

    $examDate = $_POST['exam_date'] ?? null;
    $examType = $_POST['exam_type'] ?? null;

    $organizationName = $_POST['organization_name'] ?? null;
    $organizationDepartment = $_POST['organization_department'] ?? null;
    $position = $_POST['position'] ?? null;

    $hazardsString = $_POST['hazard_factors'] ?? '';

    $psychiatric_exam = $_POST['psychiatric_exam'] === 'true';
    $psychiatricFactors = $_POST['psychiatric_factors'] ?? null;

    $inn = $_POST['inn'] ?? null;
    $ogrn = $_POST['ogrn'] ?? null;
    $okvd = $_POST['okvd'] ?? null;

    $employerPhone = $_POST['employer_phone'] ?? null;
    $employerEmail = $_POST['employer_email'] ?? null;

    $employerRegion = $_POST['employer_region'] ?? null;
    $employerDistrict = $_POST['employer_district'] ?? null;
    $employerLocality = $_POST['employer_locality'] ?? null;
    $employerStreet = $_POST['employer_street'] ?? null;
    $employerHouse = $_POST['employer_house'] ?? null;
    $employerBuilding = $_POST['employer_building'] ?? null;
    $employerFlat = $_POST['employer_flat'] ?? null;

    if (!$patientId) {
        throw new Exception("Нет patient_id");
    }

    // ======================
    // 2. Дата
    // ======================
    $examDateSql = date('Y-m-d H:i:s');
    // ======================
// 2.5 Работодатель (создание при необходимости)
// ======================
if (empty($employerId) && !empty($organizationName)) {

    $stmt = $pdo->prepare("
        INSERT INTO employers (
            name, inn, ogrn, okvd, phone, email,
            region, district, locality, street, house, building, flat
        )
        VALUES (
            :name, :inn, :ogrn, :okvd, :phone, :email,
            :region, :district, :locality, :street, :house, :building, :flat
        )
        RETURNING id
    ");

    $stmt->execute([
        'name' => $organizationName,
        'inn' => $inn,
        'ogrn' => $ogrn,
        'okvd' => $okvd,
        'phone' => $employerPhone,
        'email' => $employerEmail,
        'region' => $employerRegion,
        'district' => $employerDistrict,
        'locality' => $employerLocality,
        'street' => $employerStreet,
        'house' => $employerHouse,
        'building' => $employerBuilding,
        'flat' => $employerFlat
    ]);

    $employerId = $stmt->fetchColumn();
}
    // ======================
    // 3. Вставка визита
    // ======================
    $stmt = $pdo->prepare("
        INSERT INTO visits (
            patient_id,
            employer_id,
            exam_date,
            exam_type,
            organization_name,
            organization_department,
            position,
            psychiatric_exam,
            psychiatric_factors,
            inn,
            ogrn,
            employer_phone,
            employer_email,
            employer_region,
            employer_district,
            employer_locality,
            employer_street,
            employer_house,
            employer_building,
            employer_flat
        ) VALUES (
            :patient_id,
            :employer_id,
            :exam_date,
            :exam_type,
            :organization_name,
            :organization_department,
            :position,
            :psychiatric_exam,
            :psychiatric_factors,
            :inn,
            :ogrn,
            :employer_phone,
            :employer_email,
            :employer_region,
            :employer_district,
            :employer_locality,
            :employer_street,
            :employer_house,
            :employer_building,
            :employer_flat
        )
        RETURNING id
    ");

    $stmt->execute([
        'patient_id' => $patientId,
        'employer_id' => $employerId,
        'exam_date' => $examDateSql,
        'exam_type' => $examType,
        'organization_name' => $organizationName,
        'organization_department' => $organizationDepartment,
        'position' => $position,
        'psychiatric_exam' => $psychiatricExam,
        'psychiatric_factors' => $psychiatricFactors,
        'inn' => $inn,
        'ogrn' => $ogrn,
        'employer_phone' => $employerPhone,
        'employer_email' => $employerEmail,
        'employer_region' => $employerRegion,
        'employer_district' => $employerDistrict,
        'employer_locality' => $employerLocality,
        'employer_street' => $employerStreet,
        'employer_house' => $employerHouse,
        'employer_building' => $employerBuilding,
        'employer_flat' => $employerFlat
    ]);

    $visitId = $stmt->fetchColumn();

    // ======================
    // 4. Парсинг вредностей (КОДЫ!)
    // ======================
    $codes = preg_split('/[,;]+/', $hazardsString);
    $codes = array_map('trim', $codes);
    $codes = array_filter($codes);
    $codes = array_unique($codes);

    foreach ($codes as $code) {

        $stmt = $pdo->prepare("
            SELECT id FROM hazard_factors WHERE code = :code
        ");
        $stmt->execute(['code' => $code]);

        $hazardId = $stmt->fetchColumn();

        if (!$hazardId) {
            throw new Exception("Неизвестный фактор: $code");
        }

        $stmt = $pdo->prepare("
            INSERT INTO visit_hazard_factors (visit_id, hazard_factor_id)
            VALUES (:visit_id, :hazard_id)
        ");
        $stmt->execute([
            'visit_id' => $visitId,
            'hazard_id' => $hazardId
        ]);
    }

    $pdo->commit();

    // ======================
    // 5. Возврат в карточку
    // ======================
    header("Location: /../patient/patient.php?id=" . $patientId);
    exit;

} catch (Exception $e) {

    $pdo->rollBack();
    echo "Ошибка: " . $e->getMessage();
}