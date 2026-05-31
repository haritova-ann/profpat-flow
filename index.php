<?php

require_once __DIR__ . '/includes/bootstrap.php';

$search = $_GET['search'] ?? '';
$patients = [];

// --- ПОИСК ---
if ($search) {
    $searchTrimmed = trim($search);
    $wordCount = str_word_count($searchTrimmed, 0, 'абвгдеёжзийклмнопрстуфхцчшщъыьэюяАБВГДЕЁЖЗИЙКЛМНОПРСТУФХЦЧШЩЪЫЬЭЮЯ');
    
    // Определяем тип поиска по количеству слов
    if ($wordCount === 0 || strpos($searchTrimmed, ' ') === false) {
        // Нет пробелов - поиск по номеру карты ИЛИ по фамилии
        $sql = "
            SELECT 
                p.id,
                p.medical_card_number,
                p.last_name,
                p.first_name,
                p.middle_name,
                p.birth_date,
                p.snils,
                MAX(v.exam_date) AS last_exam_date
            FROM patients p
            LEFT JOIN visits v ON v.patient_id = p.id
            WHERE 
                p.medical_card_number ILIKE :search OR
                p.last_name ILIKE :search_last_name
            GROUP BY p.id
            ORDER BY 
                CASE 
                    WHEN p.medical_card_number ILIKE :search THEN 1
                    WHEN p.last_name ILIKE :search_exact THEN 2
                    ELSE 3
                END,
                p.last_name
        ";
        // Поиск по номеру карты с начала, по фамилии - точное совпадение или с начала
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'search' => $searchTrimmed . '%',
            'search_last_name' => $searchTrimmed . '%',
            'search_exact' => $searchTrimmed
        ]);
    } else {
        // Есть пробелы - поиск по полному ФИО
        $sql = "
            SELECT 
                p.id,
                p.medical_card_number,
                p.last_name,
                p.first_name,
                p.middle_name,
                p.birth_date,
                p.snils,
                MAX(v.exam_date) AS last_exam_date
            FROM patients p
            LEFT JOIN visits v ON v.patient_id = p.id
            WHERE 
                CONCAT(p.last_name, ' ', p.first_name, ' ', COALESCE(p.middle_name, '')) ILIKE :search
            GROUP BY p.id
            ORDER BY p.last_name
        ";
        // Поиск по любой части ФИО
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['search' => '%' . $searchTrimmed . '%']);
    }

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
        p.snils,
        v.exam_date
    FROM visits v
    JOIN patients p ON p.id = v.patient_id
    WHERE DATE(v.exam_date) = CURRENT_DATE
    ORDER BY v.exam_date DESC
";

$todayVisits = $pdo->query($todaySql)->fetchAll();

// ======================
// Настраиваем header
// ======================
$pageTitle = 'Регистратура';

$topbarLeft = [
    [
        'label' => 'Список пациентов',
        'href' => 'patient/patients.php'
    ],
    [
        'label' => 'Список организаций',
        'href' => 'employers/employers.php'
    ]
];

require_once __DIR__ . '/includes/header.php';
?>

<!-- =========================
    Основной контент
========================= -->
<div class="container">
<h2>Регистратура</h2>

<!-- ПОИСК -->
<form method="GET" class="search-form">
    <input 
        type="text" 
        name="search" 
        placeholder="Введите: № карты, фамилию или полное ФИО"
        value="<?= e($search) ?>"
    >
    <div>
        <button type="submit">Найти</button>
    </div>
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
                    <th>СНИЛС</th>
                    <th>Последний осмотр</th>
                </tr>
            </thead>
            <tbody>

            <?php foreach ($patients as $p): ?>
                <tr onclick="window.location='/patient/patient.php?id=<?= $p['id'] ?>'" style="cursor:pointer;">
                    <td><?= e($p['medical_card_number']) ?></td>

                    <td>
                        <?= e($p['last_name']) ?>
                        <?= e($p['first_name']) ?>
                        <?= e($p['middle_name']) ?>
                    </td>

                    <td>
                        <?= formatDate($p['birth_date']) ?>
                    </td>

                    <td>
                        <?= e($p['snils']) ?>
                    </td>

                    <td>
                        <?= formatDate($p['last_exam_date']) ?>
                    </td>
                </tr>
            <?php endforeach; ?>

            </tbody>
        </table>

        <?php
        // Подготовка данных для создания нового пациента
        $parts = array_map(
            'normalizeName',
            preg_split('/\s+/', trim($search))
        );
        $query = http_build_query([
            'last_name' => $parts[0] ?? '',
            'first_name' => $parts[1] ?? '',
            'middle_name' => $parts[2] ?? ''
        ]);
        ?>
        
        <div style="margin-top: 20px;">
        <a href="patient/create.php?<?= $query ?>">
            <button type="button">Создать нового пациента</button>
        </a>
        </div>

    <?php else: ?>

        <p>Пациент не найден</p>

        <?php
        // префилл ФИО
        $parts = array_map(
            'normalizeName',
            preg_split('/\s+/', trim($search))
        );
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
<h3>Осмотры за сегодня</h3>

<table class="patients-table">
    <thead>
        <tr>
            <th>ФИО</th>
            <th>Дата рождения</th>
            <th>СНИЛС</th>
            <th>Дата осмотра</th>
        </tr>
    </thead>

    <tbody>

    <?php foreach ($todayVisits as $v): ?>
        <tr onclick="window.location='/visit/visit.php?id=<?= $v['visit_id'] ?>'" style="cursor:pointer;">
            <td>
                <?= e($v['last_name']) ?>
                <?= e($v['first_name']) ?>
                <?= e($v['middle_name']) ?>
            </td>

            <td>
                <?= formatDate($v['birth_date']) ?>
            </td>

            <td>
                <?= e($v['snils']) ?>
            </td>

            <td>
                <?= formatDate($v['exam_date']) ?>
            </td>
        </tr>
    <?php endforeach; ?>

    </tbody>
</table>

</div>

<?php

require_once __DIR__ . '/includes/footer.php';