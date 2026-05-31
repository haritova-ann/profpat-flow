<?php

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /');
    exit;
}

/**
 * Проверка прав доступа
 */
if (!hasRole(['admin', 'doctor'])) {
    die('У вас недостаточно прав для обновления протокола ВК');
}

/**
 * Основные данные
 */
$id = $_POST['id'] ?? null;
$patientId = $_POST['patient_id'] ?? null;
$visitId = $_POST['visit_id'] ?? null;

$protocolDate = $_POST['protocol_date'] ?? null;
$protocolNumber = trim($_POST['protocol_number'] ?? '');

$diagnosis = trim($_POST['diagnosis'] ?? '');

$decision = $_POST['decision'] ?? 'fit';

/**
 * Временные противопоказания
 */
$temporaryUntil =
    !empty($_POST['temporary_until'])
        ? $_POST['temporary_until']
        : null;

$temporaryReason =
    trim($_POST['temporary_reason'] ?? '');

$temporaryRecommendations =
    trim($_POST['temporary_recommendations'] ?? '');

/**
 * Комиссия
 */
$chairmanId =
    !empty($_POST['chairman_id'])
        ? (int) $_POST['chairman_id']
        : null;

$member1Id =
    !empty($_POST['member1_id'])
        ? (int) $_POST['member1_id']
        : null;

$member2Id =
    !empty($_POST['member2_id'])
        ? (int) $_POST['member2_id']
        : null;

$member3Id =
    !empty($_POST['member3_id'])
        ? (int) $_POST['member3_id']
        : null;

/**
 * Факторы
 */
$hazardFactors = $_POST['hazard_factors'] ?? [];

/**
 * Валидация
 */
if (!$id) {
    die('Не передан ID заключения ВК');
}

if (!$patientId) {
    die('Не передан patient_id');
}

if (!$protocolDate) {
    die('Не указана дата протокола');
}

if (!$protocolNumber) {
    die('Не указан номер протокола');
}

if (!in_array($decision, ['fit', 'temporary', 'permanent'])) {
    die('Некорректное решение ВК');
}

/**
 * Транзакция
 */
try {

    $pdo->beginTransaction();

    /**
     * Обновление заключения ВК
     */
    $stmt = $pdo->prepare("
        UPDATE vk_conclusions SET
            protocol_date = :protocol_date,
            protocol_number = :protocol_number,

            diagnosis = :diagnosis,

            decision = :decision,

            temporary_until = :temporary_until,
            temporary_reason = :temporary_reason,
            temporary_recommendations = :temporary_recommendations,

            chairman_id = :chairman_id,
            member1_id = :member1_id,
            member2_id = :member2_id,
            member3_id = :member3_id

        WHERE id = :id
    ");

    $stmt->execute([
        'id' => $id,

        'protocol_date' => $protocolDate,
        'protocol_number' => $protocolNumber,

        'diagnosis' => $diagnosis,

        'decision' => $decision,

        'temporary_until' => $temporaryUntil,
        'temporary_reason' => $temporaryReason ?: null,
        'temporary_recommendations' => $temporaryRecommendations ?: null,

        'chairman_id' => $chairmanId,
        'member1_id' => $member1Id,
        'member2_id' => $member2Id,
        'member3_id' => $member3Id
    ]);

    /**
     * Удаление существующих факторов
     */
    $stmt = $pdo->prepare("
        DELETE FROM vk_conclusion_factors
        WHERE vk_conclusion_id = :id
    ");

    $stmt->execute(['id' => $id]);

    /**
     * Сохранение новых факторов
     */
    if (!empty($hazardFactors)) {

        $stmtFactor = $pdo->prepare("
            INSERT INTO vk_conclusion_factors (
                vk_conclusion_id,
                hazard_factor_id
            )
            VALUES (
                :vk_conclusion_id,
                :hazard_factor_id
            )
        ");

        foreach ($hazardFactors as $factorId) {

            $stmtFactor->execute([
                'vk_conclusion_id' => $id,
                'hazard_factor_id' => $factorId
            ]);
        }
    }

    /**
     * Коммит
     */
    $pdo->commit();

    /**
     * Редирект
     */
    header("Location: /../vk/vk.php?id={$id}");
    exit;

} catch (Exception $e) {

    $pdo->rollBack();

    die(
        'Ошибка обновления заключения ВК: '
        . $e->getMessage()
    );
}