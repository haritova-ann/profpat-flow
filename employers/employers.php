<?php

require_once __DIR__ . '/../includes/bootstrap.php';

$sql = "
    SELECT
        e.id,
        e.name,
        e.inn,
        e.ogrn,
        e.phone,

        COUNT(v.id) AS visits_count,
        MAX(v.exam_date) AS last_exam_date

    FROM employers e

    LEFT JOIN visits v
        ON v.employer_id = e.id

    GROUP BY e.id

    ORDER BY e.name
";

$stmt = $pdo->query($sql);
$employers = $stmt->fetchAll();

// ======================
// Настраиваем header
// ======================
$pageTitle = 'Список организаций';

$topbarLeft = [
    [
        'label' => '← Регистратура',
        'href' => '/index.php'
    ],
    [
        'label' => 'Создать новую организацию',
        'href' => '/employers/create.php',
        'class' => 'topbar-primary'
    ],
];

require_once __DIR__ . '/../includes/header.php';
?>

<!-- =========================
Основной контент
========================= -->
<div class="container">

<h2>Список организаций</h2>

<div class="search-form">
    <input
        type="text"
        id="searchInput"
        placeholder="Поиск по названию или ИНН"
    >

    <div>
        <button type="button">Найти</button>
    </div>
</div>

<table class="patients-table">

    <thead>
        <tr>
            <th>Название</th>
            <th>ИНН</th>
            <th>ОГРН</th>
            <th>Телефон</th>
            <th>Осмотров</th>
            <th>Последний осмотр</th>
        </tr>
    </thead>

    <tbody>

    <?php foreach ($employers as $e): ?>

        <tr
            class="clickable-row"
            data-href="employer.php?id=<?= $e['id'] ?>"
        >

            <td>
                <?= e($e['name']) ?>
            </td>

            <td>
                <?= e($e['inn']) ?>
            </td>

            <td>
                <?= e($e['ogrn']) ?>
            </td>

            <td>
                <?= e($e['phone']) ?>
            </td>

            <td>
                <?= e($e['visits_count']) ?>
            </td>

            <td>
                <?= formatDate($e['last_exam_date']) ?>
            </td>

        </tr>

    <?php endforeach; ?>

    </tbody>

</table>

</div>

<script>

document.querySelectorAll('.clickable-row').forEach(row => {
    row.addEventListener('click', (e) => {

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
        fetchEmployers(input.value);
    }, 300);

});

function fetchEmployers(query) {

    fetch(`search_employers.php?search=${encodeURIComponent(query)}`)
        .then(res => res.json())
        .then(data => renderTable(data))
        .catch(() => {
            tbody.innerHTML = `
                <tr>
                    <td colspan="6">
                        Ошибка загрузки
                    </td>
                </tr>
            `;
        });
}

function renderTable(employers) {

    if (!employers.length) {

        tbody.innerHTML = `
            <tr>
                <td colspan="6">
                    Ничего не найдено
                </td>
            </tr>
        `;

        return;
    }

    tbody.innerHTML = employers.map(e => `
        <tr
            class="clickable-row"
            data-href="employer.php?id=${e.id}"
        >

            <td>${escapeHtml(e.name)}</td>
            <td>${escapeHtml(e.inn ?? '')}</td>
            <td>${escapeHtml(e.ogrn ?? '')}</td>
            <td>${escapeHtml(e.phone ?? '')}</td>
            <td>${escapeHtml(e.visits_count ?? '0')}</td>
            <td>${e.last_exam_date ?? '—'}</td>

        </tr>
    `).join('');

    attachRowHandlers();
}

function attachRowHandlers() {

    document.querySelectorAll('.clickable-row').forEach(row => {

        row.addEventListener('click', (e) => {

            if (e.target.closest('a')) return;

            window.location = row.dataset.href;
        });

    });
}

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

attachRowHandlers();

</script>

</body>
</html>