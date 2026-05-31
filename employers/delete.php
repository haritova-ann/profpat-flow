<?php

require_once __DIR__ . '/../includes/bootstrap.php';

try {
 if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
 throw new Exception('Неверный метод запроса.');
 }

 $id = $_POST['id'] ?? null;

 if (!$id || !ctype_digit((string) $id)) {
 throw new Exception('Не указан корректный идентификатор организации.');
 }

 $sql = 'DELETE FROM employers WHERE id = :id';
 $stmt = $pdo->prepare($sql);
 $stmt->execute(['id' => $id]);

 if ($stmt->rowCount() ===0) {
 throw new Exception('Организация не найдена или уже удалена.');
 }

 header('Location: /employers/employers.php');
 exit;
} catch (Exception $e) {
 // Можно логировать и/или показывать пользовательское сообщение.
 echo 'Ошибка при удалении организации: ' . htmlspecialchars($e->getMessage());
 exit;
}