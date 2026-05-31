<?php

require_once __DIR__ . '/../config/db.php';

try {

    $id = $_POST['id'] ?? null;

    if (!$id) {
        throw new Exception("Нет ID работодателя");
    }

    $stmt = $pdo->prepare("
        UPDATE employers
        SET
            name = :name,
            inn = :inn,
            ogrn = :ogrn,
            okvd = :okvd,
            phone = :phone,
            email = :email,
            region = :region,
            district = :district,
            locality = :locality,
            street = :street,
            house = :house,
            building = :building,
            flat = :flat
        WHERE id = :id
    ");

    $stmt->execute([
        'id' => $id,

        'name' => $_POST['name'] ?? null,
        'inn' => $_POST['inn'] ?? null,
        'ogrn' => $_POST['ogrn'] ?? null,
        'okvd' => $_POST['okvd'] ?? null,

        'phone' => $_POST['phone'] ?? null,
        'email' => $_POST['email'] ?? null,

        'region' => $_POST['region'] ?? null,
        'district' => $_POST['district'] ?? null,
        'locality' => $_POST['locality'] ?? null,

        'street' => $_POST['street'] ?? null,
        'house' => $_POST['house'] ?? null,
        'building' => $_POST['building'] ?? null,
        'flat' => $_POST['flat'] ?? null
    ]);

    header("Location: employer.php?id=" . $id);
    exit;

} catch (Exception $e) {

    echo "Ошибка: " . $e->getMessage();
}