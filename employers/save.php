<?php

require_once __DIR__ . '/../includes/bootstrap.php';

try {
    // ======================
    // 1. Получаем POST 
    // ======================
    $name = trim($_POST['name'] ?? '');
    $inn = trim($_POST['inn'] ?? '');
    $inn = $inn === '' ? null : $inn;
    $ogrn = trim($_POST['ogrn'] ?? '');
    $okvd = trim($_POST['okvd'] ?? '');

    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');

    $region = trim($_POST['region'] ?? '');
    $district = trim($_POST['district'] ?? '');
    $locality = trim($_POST['locality'] ?? '');
    $street = trim($_POST['street'] ?? '');
    $house = trim($_POST['house'] ?? '');
    $building = trim($_POST['building'] ?? '');
    $flat = trim($_POST['flat'] ?? '');

    // ======================
    // 2. Валидация 
    // ======================
    if ($name === '') {
    throw new Exception('Название организации обязательно для заполнения.');
    }

    // ======================
    // 3. Вставка 
    // ======================
    $sql = "
    INSERT INTO employers (
    name,
    inn,
    ogrn,
    okvd,
    phone,
    email,
    region,
    district,
    locality,
    street,
    house,
    building,
    flat ) VALUES (
    :name,
    :inn,
    :ogrn,
    :okvd,
    :phone,
    :email,
    :region,
    :district,
    :locality,
    :street,
    :house,
    :building,
    :flat )
    RETURNING id ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
    'name' => $name,
    'inn' => $inn !== '' ? $inn : null,
    'ogrn' => $ogrn !== '' ? $ogrn : null,
    'okvd' => $okvd !== '' ? $okvd : null,
    'phone' => $phone !== '' ? $phone : null,
    'email' => $email !== '' ? $email : null,
    'region' => $region !== '' ? $region : null,
    'district' => $district !== '' ? $district : null,
    'locality' => $locality !== '' ? $locality : null,
    'street' => $street !== '' ? $street : null,
    'house' => $house !== '' ? $house : null,
    'building' => $building !== '' ? $building : null,
    'flat' => $flat !== '' ? $flat : null,
    ]);

    $employer = $stmt->fetch();
    $employerId = $employer['id'];

    header("Location: /employers/employer.php?id=" . $employerId);
    exit;
} catch (Exception $e) {
    // Чтобы не выводить системные ошибки напрямую, можно логировать и/или показывать сообщение.
    // Для простоты пока просто выводим текст:
    echo "Ошибка: " . htmlspecialchars($e->getMessage());
    exit;
}