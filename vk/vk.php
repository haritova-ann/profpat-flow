<?php

require_once __DIR__ . '/../includes/bootstrap.php';

$id = $_GET['id'] ?? null;

if (!$id) {
    die('Не передан ID заключения ВК');
}

/**
 * Проверка прав доступа
 */
if (!hasRole(['admin', 'doctor'])) {
    die('У вас недостаточно прав');
}

/**
 * Заключение ВК
 */
$stmt = $pdo->prepare("
    SELECT
        vk.*,

        p.last_name,
        p.first_name,
        p.middle_name,
        p.birth_date,
        p.medical_card_number,

        ch.full_name AS chairman_name,
        ch.position AS chairman_position,

        m1.full_name AS member1_name,
        m1.position AS member1_position,

        m2.full_name AS member2_name,
        m2.position AS member2_position,

        m3.full_name AS member3_name,
        m3.position AS member3_position

    FROM vk_conclusions vk

    JOIN patients p
        ON p.id = vk.patient_id

    LEFT JOIN doctors ch
        ON ch.id = vk.chairman_id

    LEFT JOIN doctors m1
        ON m1.id = vk.member1_id

    LEFT JOIN doctors m2
        ON m2.id = vk.member2_id

    LEFT JOIN doctors m3
        ON m3.id = vk.member3_id

    WHERE vk.id = :id
");

$stmt->execute([
    'id' => $id
]);

$vk = $stmt->fetch();

if (!$vk) {
    die('Заключение ВК не найдено');
}

/**
 * Противопоказанные факторы
 */
$stmt = $pdo->prepare("
    SELECT
        hf.code,
        hf.name
    FROM vk_conclusion_factors vkf

    JOIN hazard_factors hf
        ON hf.id = vkf.hazard_factor_id

    WHERE vkf.vk_conclusion_id = :id

    ORDER BY hf.code
");

$stmt->execute([
    'id' => $id
]);

$factors = $stmt->fetchAll();

/**
 * Текст решения
 */
$decisionText = match ($vk['decision']) {

    'fit' =>
        'Допущен к работе',

    'temporary' =>
        'Временные противопоказания',

    'permanent' =>
        'Постоянные противопоказания',

    default =>
        'Не указано'
};

// ======================
// Настраиваем header
// ======================
$pageTitle = 'Заключение ВК';

$topbarLeft = [
    [
        'label' => '← Медицинский осмотр',
        'href' => '/visit/visit.php?id=' . e($vk['visit_id']),
    ],
    [
        'label' => 'Редактировать протокол',
        'href' => '/vk/edit.php?id=' . e($vk['id']),
        'class' => 'topbar-primary'
    ]
];

require_once __DIR__ . '/../includes/header.php';
?>

<!-- =========================
Контейнер
========================= -->
<div class="container">

    <h2>
        Заключение врачебной комиссии
    </h2>

    <!-- =========================
    Пациент
    ========================= -->
    <div class="card" style="margin-bottom: 20px;">

        <strong>Пациент:</strong>

        <?= e($vk['last_name']) ?>
        <?= e($vk['first_name']) ?>
        <?= e($vk['middle_name']) ?>

        <br>

        <strong>Дата рождения:</strong>
        <?= formatDate($vk['birth_date']) ?>

        <br>

        <strong>№ карты:</strong>
        <?= e($vk['medical_card_number']) ?>

    </div>

    <!-- =========================
    Действия
    ========================= -->
    <div class="section">

        <h3>Документы</h3>

                <div class="actions">

                    <a class="print-btn"
                       href="/vk/print.php?id=<?= e($vk['id']) ?>&visit_id=<?= e($vk['visit_id']) ?>">
                        Печать протокола ВК и заключения
                    </a>

                </div>

    </div>

    <!-- =========================
    Основная информация
    ========================= -->
    <div class="section">

        <h3>Основная информация</h3>

        <div class="info-grid">

            <div class="card">

                <strong>Дата протокола</strong>

                <br><br>

                <?= formatDate($vk['protocol_date']) ?>

            </div>

            <div class="card">

                <strong>Номер протокола</strong>

                <br><br>

                <?= e($vk['protocol_number']) ?>

            </div>

        </div>

    </div>

    <!-- =========================
    Диагноз
    ========================= -->
    <div class="section">

        <h3>Диагноз</h3>

        <div class="card">

            <?= nl2br(e($vk['diagnosis'])) ?>

        </div>

    </div>

    <!-- =========================
    Противопоказанные факторы
    ========================= -->
    <div class="section">

        <h3>Противопоказанные вредные факторы</h3>

        <?php if ($factors): ?>

            <div class="factor-list">

                <?php foreach ($factors as $factor): ?>

                    <div class="factor-item">

                        <strong>
                            <?= e($factor['code']) ?>
                        </strong>

                        <br>

                        <?= e($factor['name']) ?>

                    </div>

                <?php endforeach; ?>

            </div>

        <?php else: ?>

            <div class="card">
                Противопоказанные факторы не указаны
            </div>

        <?php endif; ?>

    </div>

    <!-- =========================
    Решение ВК
    ========================= -->
    <div class="section">

        <h3>Решение врачебной комиссии</h3>

        <div class="card">

            <div class="status-badge">
                <?= e($decisionText) ?>
            </div>

            <?php if ($vk['decision'] === 'temporary'): ?>

                <hr style="margin: 20px 0;">

                <?php if ($vk['temporary_until']): ?>

                    <p>

                        <strong>Срок до:</strong>

                        <?= formatDate($vk['temporary_until']) ?>

                    </p>

                <?php endif; ?>

                <?php if ($vk['temporary_reason']): ?>

                    <p>

                        <strong>Обоснование:</strong>

                        <br><br>

                        <?= nl2br(e($vk['temporary_reason'])) ?>

                    </p>

                <?php endif; ?>

                <?php if ($vk['temporary_recommendations']): ?>

                    <p>

                        <strong>Рекомендации:</strong>

                        <br><br>

                        <?= nl2br(e($vk['temporary_recommendations'])) ?>

                    </p>

                <?php endif; ?>

            <?php endif; ?>

        </div>

    </div>

    <!-- =========================
    Состав комиссии
    ========================= -->
    <div class="section">

        <h3>Состав врачебной комиссии</h3>

        <div class="info-grid">

            <?php if ($vk['chairman_name']): ?>

                <div class="doctor-card">

                    <strong>
                        Председатель ВК
                    </strong>

                    <br><br>

                    <?= e($vk['chairman_name']) ?>

                    <?php if ($vk['chairman_position']): ?>

                        <br>

                        <small>
                            <?= e($vk['chairman_position']) ?>
                        </small>

                    <?php endif; ?>

                </div>

            <?php endif; ?>

            <?php if ($vk['member1_name']): ?>

                <div class="doctor-card">

                    <strong>
                        Член комиссии
                    </strong>

                    <br><br>

                    <?= e($vk['member1_name']) ?>

                    <?php if ($vk['member1_position']): ?>

                        <br>

                        <small>
                            <?= e($vk['member1_position']) ?>
                        </small>

                    <?php endif; ?>

                </div>

            <?php endif; ?>

            <?php if ($vk['member2_name']): ?>

                <div class="doctor-card">

                    <strong>
                        Член комиссии
                    </strong>

                    <br><br>

                    <?= e($vk['member2_name']) ?>

                    <?php if ($vk['member2_position']): ?>

                        <br>

                        <small>
                            <?= e($vk['member2_position']) ?>
                        </small>

                    <?php endif; ?>

                </div>

            <?php endif; ?>

            <?php if ($vk['member3_name']): ?>

                <div class="doctor-card">

                    <strong>
                        Член комиссии
                    </strong>

                    <br><br>

                    <?= e($vk['member3_name']) ?>

                    <?php if ($vk['member3_position']): ?>

                        <br>

                        <small>
                            <?= e($vk['member3_position']) ?>
                        </small>

                    <?php endif; ?>

                </div>

            <?php endif; ?>

        </div>

    </div>

</div>
<script>
    /**
    * Функция для печати документа
    * 
    * @param {string} documentType - тип документа для генерации
    * 
    * Эта функция открывает новое окно с обработчиком print_document.php,
    * который сгенерирует DOCX файл и отдаст его на скачивание
    */
    function printDocument(documentType) {
        const vkId = <?= $vk['id'] ?>;
        const visitId = <?= $vk['visit_id'] ?>;
        
        // Открываем в новом окне, чтобы не покидать текущую страницу
        window.open(
            `/vk/print.php?id=${vkId}&visit_id=${visitId}`,
            '_blank'
        );
    }
</script>
</body>
</html>