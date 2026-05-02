<?php
require __DIR__ . '/config/db.php';

$search = $_GET['search'] ?? '';
$patients = [];

// --- ПОИСК ---
if ($search) {
    if (preg_match('/^\d+$/', $search)) {
        // поиск по № АК
        $sql = "
            SELECT 
                p.id,
                p.medical_card_number,
                p.last_name,
                p.first_name,
                p.middle_name,
                p.birth_date,
                MAX(v.exam_date) AS last_exam_date
            FROM patients p
            LEFT JOIN visits v ON v.patient_id = p.id
            WHERE p.medical_card_number ILIKE :search
            GROUP BY p.id
            ORDER BY p.last_name
        ";
    } else {
        // поиск по ФИО
        $sql = "
            SELECT 
                p.id,
                p.medical_card_number,
                p.last_name,
                p.first_name,
                p.middle_name,
                p.birth_date,
                MAX(v.exam_date) AS last_exam_date
            FROM patients p
            LEFT JOIN visits v ON v.patient_id = p.id
            WHERE 
                p.last_name ILIKE :search OR
                p.first_name ILIKE :search OR
                p.middle_name ILIKE :search
            GROUP BY p.id
            ORDER BY p.last_name
        ";
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute(['search' => "%$search%"]);
    $patients = $stmt->fetchAll();
}

// --- ОСМОТРЫ ЗА СЕГОДНЯ ---
$todaySql = "
    SELECT 
        v.id as visit_id,
        p.id as patient_id,
        p.last_name,
        p.first_name,
        p.middle_name,
        p.birth_date,
        v.exam_date
    FROM visits v
    JOIN patients p ON p.id = v.patient_id
    WHERE DATE(v.exam_date) = CURRENT_DATE
    ORDER BY v.exam_date DESC
";

$todayVisits = $pdo->query($todaySql)->fetchAll();
?>

<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="UTF-8">
<title>Регистратура</title>
<link rel="stylesheet" href="/assets/css/forms.css">
</head>

<body>

<div class="container">
<h2>Регистратура</h2>

<a href="patient/patients.php">
    <button type="button">Список всех пациентов</button>
</a>

<!-- ПОИСК -->
<form method="GET" class="form-group">
    <input 
        type="text" 
        name="search" 
        placeholder="Поиск: ФИО или № амбулаторной карты"
        value="<?= htmlspecialchars($search) ?>"
    >
</form>

<!-- РЕЗУЛЬТАТЫ ПОИСКА -->
<?php if ($search): ?>

    <h3>Результаты поиска</h3>

    <?php if ($patients): ?>
        <table class="patients-table">
            <thead>
                <tr>
                    <th>№ карты</th>
                    <th>ФИО</th>
                    <th>Дата рождения</th>
                    <th>Последний осмотр</th>
                </tr>
            </thead>
            <tbody>

            <?php foreach ($patients as $p): ?>
                <tr onclick="window.location='/patient/patient.php?id=<?= $p['id'] ?>'" style="cursor:pointer;">
                    <td><?= htmlspecialchars($p['medical_card_number']) ?></td>

                    <td>
                        <?= htmlspecialchars($p['last_name']) ?>
                        <?= htmlspecialchars($p['first_name']) ?>
                        <?= htmlspecialchars($p['middle_name']) ?>
                    </td>

                    <td>
                        <?= $p['birth_date'] ? date('d.m.Y', strtotime($p['birth_date'])) : '' ?>
                    </td>

                    <td>
                        <?= $p['last_exam_date'] 
                            ? date('d.m.Y', strtotime($p['last_exam_date'])) 
                            : '—' ?>
                    </td>
                </tr>

            <?php endforeach; ?>
                 <a href="patient/create.php?<?= $query ?>">
                    <button type="button">Создать пациента</button>
                </a>
            </tbody>
        </table>

    <?php else: ?>

        <p>Пациент не найден</p>

        <?php
        // префилл ФИО
        $parts = explode(' ', trim($search));
        $query = http_build_query([
            'last_name' => $parts[0] ?? '',
            'first_name' => $parts[1] ?? '',
            'middle_name' => $parts[2] ?? ''
        ]);
        ?>

        <a href="patient/create.php?<?= $query ?>">
            <button type="button">Создать пациента</button>
        </a>

    <?php endif; ?>

<?php endif; ?>

<!-- ОСМОТРЫ ЗА СЕГОДНЯ -->
<h3 style="margin-top:30px;">Осмотры за сегодня</h3>

<table class="patients-table">
    <thead>
        <tr>
            <th>ФИО</th>
            <th>Дата рождения</th>
            <th>Дата осмотра</th>
        </tr>
    </thead>

    <tbody>

    <?php foreach ($todayVisits as $v): ?>
        <tr onclick="window.location='/visit/visit.php?id=<?= $v['visit_id'] ?>'" style="cursor:pointer;">
            <td>
                <?= htmlspecialchars($v['last_name']) ?>
                <?= htmlspecialchars($v['first_name']) ?>
                <?= htmlspecialchars($v['middle_name']) ?>
            </td>

            <td>
                <?= $v['birth_date'] ? date('d.m.Y', strtotime($v['birth_date'])) : '' ?>
            </td>

            <td>
                <?= date('d.m.Y', strtotime($v['exam_date'])) ?>
            </td>
        </tr>
    <?php endforeach; ?>

    </tbody>
</table>

</div>

</body>
</html>