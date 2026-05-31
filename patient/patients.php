<?php

require_once __DIR__ . '/../includes/bootstrap.php';

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
    GROUP BY p.id
    ORDER BY p.last_name
";

$stmt = $pdo->query($sql);
$patients = $stmt->fetchAll();

// ======================
// Настраиваем header
// ======================
$topbarLeft = [
    [
        'label' => '← Регистратура',
        'href' => '/index.php'
    ]
];

require_once __DIR__ . '/../includes/header.php';
?>

<!-- =========================
    Основной контент
========================= -->
<div class="container">
<h2>Пациенты</h2>

<div class="search-form">
    <input 
        type="text" 
        id="searchInput"
        placeholder="Поиск по ФИО или № карты"
    >
    <div>
        <button type="submit">Найти</button>
    </div>
</div>

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
        <tr class="clickable-row" data-href="patient.php?id=<?= $p['id'] ?>">
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
                <?= formatDate($p['last_exam_date']) ?>
            </td>
        </tr>
    <?php endforeach; ?>

    </tbody>
</table>

</div>
<script>
document.querySelectorAll('.clickable-row').forEach(row => {
    row.addEventListener('click', (e) => {
        // если клик был по ссылке — не перехватываем
        if (e.target.closest('a')) return;

        window.location = row.dataset.href;
    });
});

const input = document.getElementById('searchInput');
const tbody = document.querySelector('.patients-table tbody');

let timeout = null;

input.addEventListener('input', () => {
    clearTimeout(timeout);

    timeout = setTimeout(() => {
        fetchPatients(input.value);
    }, 300); // debounce
});

function fetchPatients(query) {
    fetch(`search_patients.php?search=${encodeURIComponent(query)}`)
        .then(res => res.json())
        .then(data => renderTable(data))
        .catch(() => {
            tbody.innerHTML = '<tr><td colspan="4">Ошибка загрузки</td></tr>';
        });
}

function renderTable(patients) {
    if (!patients.length) {
        tbody.innerHTML = '<tr><td colspan="4">Ничего не найдено</td></tr>';
        return;
    }

    tbody.innerHTML = patients.map(p => `
        <tr class="clickable-row" data-href="patient.php?id=${p.id}">
            <td>${escapeHtml(p.medical_card_number)}</td>
            <td>${escapeHtml(p.last_name)} ${escapeHtml(p.first_name)} ${escapeHtml(p.middle_name ?? '')}</td>
            <td>${p.birth_date}</td>
            <td>${p.last_exam_date}</td>
        </tr>
    `).join('');

    attachRowHandlers();
}

// повторно навешиваем обработчик клика
function attachRowHandlers() {
    document.querySelectorAll('.clickable-row').forEach(row => {
        row.addEventListener('click', (e) => {
            if (e.target.closest('a')) return;
            window.location = row.dataset.href;
        });
    });
}

// защита от XSS
function escapeHtml(str) {
    if (!str) return '';
    return str.replace(/[&<>"']/g, function(m) {
        return ({
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        })[m];
    });
}

// при загрузке — навесить клики на текущие строки
attachRowHandlers();

</script>

<?php

require_once __DIR__ . '/../includes/footer.php';