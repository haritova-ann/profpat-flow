<?php
/**
 * Обработчик обновления медосмотра
 * 
 * Назначение: принимает данные из формы edit.php и обновляет запись в БД
 * 
 * Основные отличия от save.php:
 * 1. Использует UPDATE вместо INSERT
 * 2. Требует visit_id для определения какую запись обновлять
 * 3. Удаляет старые связи с вредными факторами и создаёт новые
 */

require_once __DIR__ . '/../config/db.php';

try {

    $pdo->beginTransaction();

    // ======================
    // 1. Получаем данные из формы
    // ======================
    $visitId = $_POST['visit_id'] ?? null;
    $patientId = $_POST['patient_id'] ?? null;

    $examType = $_POST['exam_type'] ?? null;

    $organizationName = $_POST['organization_name'] ?? null;
    $organizationDepartment = $_POST['organization_department'] ?? null;
    $position = $_POST['position'] ?? null;

    $hazardsString = $_POST['hazard_factors'] ?? '';

    $psychiatricExam = ($_POST['psychiatric_exam'] ?? 'false') === 'true' ? 1 : 0;
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

    // Проверяем обязательные поля
    if (!$visitId) {
        throw new Exception("Нет visit_id");
    }

    if (!$patientId) {
        throw new Exception("Нет patient_id");
    }

    // ======================
    // 2. Обновление визита
    // ======================
    // Используем UPDATE для изменения существующей записи
    // WHERE id = :visit_id гарантирует, что обновится только нужная запись
    $stmt = $pdo->prepare("
        UPDATE visits SET
            exam_type = :exam_type,
            organization_name = :organization_name,
            organization_department = :organization_department,
            position = :position,
            psychiatric_exam = :psychiatric_exam,
            psychiatric_factors = :psychiatric_factors,
            inn = :inn,
            ogrn = :ogrn,
            okvd =:okvd,
            employer_phone = :employer_phone,
            employer_email = :employer_email,
            employer_region = :employer_region,
            employer_district = :employer_district,
            employer_locality = :employer_locality,
            employer_street = :employer_street,
            employer_house = :employer_house,
            employer_building = :employer_building,
            employer_flat = :employer_flat
        WHERE id = :visit_id
    ");

    $stmt->execute([
        'visit_id' => $visitId,
        'exam_type' => $examType,
        'organization_name' => $organizationName,
        'organization_department' => $organizationDepartment,
        'position' => $position,
        'psychiatric_exam' => $psychiatricExam,
        'psychiatric_factors' => $psychiatricFactors,
        'inn' => $inn,
        'ogrn' => $ogrn,
        'okvd' => $okvd,
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

    // ======================
    // 3. Обновление вредных факторов
    // ======================
    
    // Сначала удаляем все старые связи
    // Это проще, чем сравнивать что изменилось
    $stmt = $pdo->prepare("
        DELETE FROM visit_hazard_factors
        WHERE visit_id = :visit_id
    ");
    $stmt->execute(['visit_id' => $visitId]);

    // Парсим новый список кодов вредных факторов
    $codes = preg_split('/[,;]+/', $hazardsString);
    $codes = array_map('trim', $codes);
    $codes = array_filter($codes);
    $codes = array_unique($codes);

    // Создаём новые связи
    foreach ($codes as $code) {

        // Находим ID фактора по коду
        $stmt = $pdo->prepare("
            SELECT id FROM hazard_factors WHERE code = :code
        ");
        $stmt->execute(['code' => $code]);

        $hazardId = $stmt->fetchColumn();

        if (!$hazardId) {
            throw new Exception("Неизвестный фактор: $code");
        }

        // Вставляем связь визит-фактор
        $stmt = $pdo->prepare("
            INSERT INTO visit_hazard_factors (visit_id, hazard_factor_id)
            VALUES (:visit_id, :hazard_id)
        ");
        $stmt->execute([
            'visit_id' => $visitId,
            'hazard_id' => $hazardId
        ]);
    }

    // Фиксируем все изменения
    $pdo->commit();

    // ======================
    // 4. Возврат на страницу осмотра
    // ======================
    header("Location: /visit/visit.php?id=" . $visitId);
    exit;

} catch (Exception $e) {

    // В случае ошибки откатываем все изменения
    $pdo->rollBack();
    echo "Ошибка при обновлении: " . $e->getMessage();
}
