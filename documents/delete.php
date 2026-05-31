<?php

require_once __DIR__ . '/../includes/bootstrap.php';

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    throw new Exception('Неверный метод запроса.');
    }

    $id = $_POST['id'] ?? null;
    $patientId = $_POST['patient_id'] ?? null;

    if (!$id || !ctype_digit((string) $id)) {
    throw new Exception('Указан неверный идентификатор приема.');
    }

    $sql = 'DELETE FROM patient_documents WHERE id = :id';
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['id' => $id]);

    if ($stmt->rowCount() ===0) {
    throw new Exception('Прием не найден или уже удален.');
    }

    header('Location: /patient/patient.php?id=' . $patientId);
    exit;
} catch (Exception $e) {
 // Можно логировать и/или показывать пользовательское сообщение.
 echo 'Ошибка при удалении приема: ' . htmlspecialchars($e->getMessage());
 exit;
}