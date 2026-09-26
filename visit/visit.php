<?php
/**
 * Страница детального просмотра медосмотра
 * 
 * Назначение: отображение всех данных о конкретном медосмотре
 * и предоставление возможности печати связанных документов
 */

require_once __DIR__ . '/../includes/bootstrap.php';

// ======================
// Получаем ID визита из URL
// ======================
$visitId = $_GET['id'] ?? null;

if (!$visitId) {
    die("Не указан ID медосмотра");
}

// ======================
// Загружаем данные визита + пациента
// ======================
$stmt = $pdo->prepare("
    SELECT 
        v.id AS visit_id,
        v.*,
        p.*,
        STRING_AGG(DISTINCT hf.code, ', ') AS hazard_factors,
        STRING_AGG(DISTINCT pf.code, ', ') AS psychiatric_factors
    FROM visits v
    JOIN patients p ON p.id = v.patient_id
    LEFT JOIN visit_hazard_factors vhf ON vhf.visit_id = v.id
    LEFT JOIN hazard_factors hf ON hf.id = vhf.hazard_factor_id
    LEFT JOIN visit_psychiatric_factors vpf ON vpf.visit_id = v.id
    LEFT JOIN psychiatric_factors pf ON pf.id = vpf.psychiatric_factor_id
    WHERE v.id = :id
    GROUP BY v.id, p.id
");

$stmt->execute(['id' => $visitId]);
$data = $stmt->fetch();

if (!$data) {
    die("Медосмотр не найден");
}

// ======================
// Заключение ВК
// ======================

$stmt = $pdo->prepare("
    SELECT
        id,
        protocol_date,
        protocol_number,
        decision
    FROM vk_conclusions
    WHERE visit_id = :visit_id
    ORDER BY created_at DESC
    LIMIT 1
");

$stmt->execute([
    'visit_id' => $visitId
]);

$vkConclusion = $stmt->fetch();

// ======================
// Считаем возраст
// ======================

$age = (new DateTime($data['birth_date']))
    ->diff(new DateTime())
    ->y;

// ======================
// Получаем маршрутный лист
// ======================

$stmt = $pdo->prepare("
    SELECT
        r.id,
        r.name,
        r.type,
        r.room,
        r.comment,
        r.sort_order,
        vr.is_selected,
        vr.is_added_manually,
        rp.price
    FROM visit_requirements vr
    JOIN requirements r
        ON r.id = vr.requirement_id
    JOIN visits v
        ON v.id = vr.visit_id
    LEFT JOIN LATERAL (
        SELECT
            CASE
                WHEN v.price_mode = 'special'
                    THEN COALESCE(
                        (
                            SELECT erp.price
                            FROM employer_requirement_prices erp
                            WHERE erp.employer_id = v.employer_id
                              AND erp.requirement_id = r.id
                            ORDER BY erp.valid_from DESC
                            LIMIT 1
                        ),
                        (
                            SELECT rp.price
                            FROM requirement_prices rp
                            WHERE rp.requirement_id = r.id
                            ORDER BY rp.valid_from DESC
                            LIMIT 1
                        )
                    )

                WHEN v.price_mode = 'fixed'
                    AND r.name NOT IN (
                        'Оформление ЛМК',
                        'Фото 3х4',
                        'Гигиеническое обучение'
                    )
                    THEN NULL

                ELSE (
                    SELECT rp.price
                    FROM requirement_prices rp
                    WHERE rp.requirement_id = r.id
                    ORDER BY rp.valid_from DESC
                    LIMIT 1
                )
            END AS price
    ) rp ON TRUE
    WHERE vr.visit_id = :visit_id
    ORDER BY r.sort_order, r.name
");

$stmt->execute([
    'visit_id' => $visitId
]);

$routeRequirements = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare("
    SELECT
        r.id,
        r.name,
        rp.price
    FROM requirements r
    JOIN visits v ON v.id = :visit_id

    LEFT JOIN LATERAL (
        SELECT
            CASE
                WHEN v.price_mode = 'special'
                    THEN COALESCE(
                        (
                            SELECT erp.price
                            FROM employer_requirement_prices erp
                            WHERE erp.employer_id = v.employer_id
                              AND erp.requirement_id = r.id
                            ORDER BY erp.valid_from DESC
                            LIMIT 1
                        ),
                        (
                            SELECT rp.price
                            FROM requirement_prices rp
                            WHERE rp.requirement_id = r.id
                            ORDER BY rp.valid_from DESC
                            LIMIT 1
                        )
                    )

                ELSE (
                    SELECT rp.price
                    FROM requirement_prices rp
                    WHERE rp.requirement_id = r.id
                    ORDER BY rp.valid_from DESC
                    LIMIT 1
                )
            END AS price
    ) rp ON TRUE

    WHERE r.id = 42
      AND r.is_active = TRUE
");

$stmt->execute([
    'visit_id' => $visitId
]);

$psychiatricService = $stmt->fetch(PDO::FETCH_ASSOC);

if ($data['price_mode'] === 'fixed') {
    $totalPrice = (float)$data['fixed_price'];
} else {
    $totalPrice = 0;

    foreach ($routeRequirements as $req) {
        if ($req['is_selected']) {
            $totalPrice += (float)$req['price'];
        }
    }
}
// ======================
// Настраиваем header
// ======================
$pageTitle = 'Медосмотр от ' . formatDate($data['exam_date']);

$topbarLeft = [
    [
    'label' => '← Регистратура',
    'href' => '/index.php'
    ],
    [
    'label' => '← Карта пациента',
    'href' => '/patient/patient.php?id=' . $data['patient_id']
    ],
    [
    'label' => 'Редактировать осмотр',
    'href' => '/visit/edit.php?id=' . $data['visit_id'],
    'class' => 'topbar-primary'
    ]

];

require_once __DIR__ . '/../includes/header.php';
?>


<!-- =========================
    Основной контент
========================= -->
<div class="wrapper">
    
    <!-- LEFT -->
    <div class="container">
        <!-- ======================
            БЛОК 1: Данные пациента
            ====================== -->
        <div class="card">
            <div class="section-title">Данные пациента</div>

            <div class="info-row">
                <div class="info-label">№ амбулаторной карты:</div>
                <div class="info-value"><?= e($data['medical_card_number']) ?></div>
            </div>
            
            <div class="info-row">
                <div class="info-label">ФИО:</div>
                <div class="info-value">
                    <?= e($data['last_name']) ?> 
                    <?= e($data['first_name']) ?> 
                    <?= e($data['middle_name']) ?>
                </div>
            </div>
            
            <div class="info-row">
                <div class="info-label">Дата рождения:</div>
                <div class="info-value"><?= formatDate($data['birth_date']) ?></div>
            </div>
            
            <div class="info-row">
                <div class="info-label">Пол:</div>
                <div class="info-value">
                    <?= $data['gender'] === 'male' ? 'Мужской' : 'Женский' ?>
                </div>
            </div>
            
            <div class="info-row">
                <div class="info-label">СНИЛС:</div>
                <div class="info-value"><?= e($data['snils']) ?></div>
            </div>
            
            <div class="info-row">
                <div class="info-label">Документ:</div>
                <div class="info-value">
                    <?= e(formatIdentityDocument($data)) ?>
                </div>
            </div>
            
            <div class="info-row">
                <div class="info-label">Телефон:</div>
                <div class="info-value"><?= e($data['phone_number']) ?></div>
            </div>
            
            <div class="info-row">
                <div class="info-label">Email:</div>
                <div class="info-value"><?= e($data['email']) ?></div>
            </div>
            
            <div class="info-row">
                <div class="info-label">Адрес проживания:</div>
                <div class="info-value">
                    <?= e(formatAddress($data)) ?>
                </div>
            </div>
        </div>

        <!-- ======================
            БЛОК 2: Данные медосмотра
            ====================== -->
        <div class="card">
            <div class="section-title">Данные медицинского осмотра</div>
            
            <div class="info-row">
                <div class="info-label">Дата медосмотра:</div>
                <div class="info-value"><?= formatDate($data['exam_date']) ?></div>
            </div>
            
            <div class="info-row">
                <div class="info-label">Вид медосмотра:</div>
                <div class="info-value">
                    <?php
                    $examTypes = [
                        'periodic' => 'Периодический',
                        'preliminary' => 'Предварительный',
                        'ad-hoc' => 'Внеочередной'
                    ];
                    echo $examTypes[$data['exam_type']] ?? e($data['exam_type']);
                    ?>
                </div>
            </div>
            
            <div class="info-row">
                <div class="info-label">Вредные факторы:</div>
                <div class="info-value"><?= e($data['hazard_factors']) ?></div>
            </div>
            
            <div class="info-row psychiatric-row">
                <div class="info-label">ОПО:</div>

                <div class="info-value">
                    <?= $data['psychiatric_exam'] ? 'Требуется' : 'Не требуется' ?>

                    <?php if ($data['psychiatric_exam']): ?>
                        <button
                            type="button"
                            class="print-btn psychiatric-print-btn"
                            onclick="printPsychiatricCertificate()"
                        >
                            Справка об оплате ПО
                        </button>
                    <?php endif; ?>
                </div>
            </div>
            
            <?php if ($data['psychiatric_exam']): ?>
            <div class="info-row">
                <div class="info-label">Пункт психиатрического освидетельствования: </div>
                <div class="info-value"><?= e($data['psychiatric_factors']) ?></div>
            </div>
            <?php endif; ?>
        </div>

        <!-- ======================
            БЛОК 3: Данные работодателя
            ====================== -->
        <div class="card">
            <div class="section-title">Данные работодателя</div>
            
            <div class="info-row">
                <div class="info-label">Место работы:</div>
                <div class="info-value"><?= e($data['organization_name']) ?></div>
            </div>
            
            <div class="info-row">
                <div class="info-label">Структурное подразделение:</div>
                <div class="info-value"><?= e($data['organization_department']) ?></div>
            </div>
            
            <div class="info-row">
                <div class="info-label">Профессия (должность):</div>
                <div class="info-value"><?= e($data['position']) ?></div>
            </div>
            
            <div class="info-row">
                <div class="info-label">ИНН:</div>
                <div class="info-value"><?= e($data['inn']) ?></div>
            </div>
            
            <div class="info-row">
                <div class="info-label">ОГРН (ОГРНИП):</div>
                <div class="info-value"><?= e($data['ogrn']) ?></div>
            </div>
            
            <div class="info-row">
                <div class="info-label">Телефон работодателя:</div>
                <div class="info-value"><?= e($data['employer_phone']) ?></div>
            </div>
            
            <div class="info-row">
                <div class="info-label">Email работодателя:</div>
                <div class="info-value"><?= e($data['employer_email']) ?></div>
            </div>
            
            <div class="info-row">
                <div class="info-label">Юридический адрес:</div>
                <div class="info-value">
                    <?= e(formatEmployerAddress($data)) ?>
                </div>
            </div>
        </div>
    </div>

    <!-- RIGHT -->
    <div class="sidebar">
        <!-- ======================
            БЛОК 4: Маршрутный лист
            ====================== -->
        <div class="card route-sheet-card" id="route-sheet">
            <div class="section-title">Маршрутный лист</div>

            <div class="route-actions">
                <button type="button" onclick="toggleAll(true)">Выбрать всё</button>
                <button type="button" onclick="toggleAll(false)">Снять всё</button>
                <button type="button" id="add-requirement">
                    + Добавить услугу
                </button>
                
            </div>
            <div id="add-requirement-form" hidden>
                <input
                    type="text"
                    id="requirement-search"
                    placeholder="Введите название услуги"
                    autocomplete="off"
                >

                <div id="requirement-results" class="requirement-results"></div>
            </div>

            <table class="route-table">
                <thead>
                    <tr>
                        <th>Каб.</th>
                        <th>Обследование</th>
                        <th>Цена</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($routeRequirements as $req): ?>
                        <tr
                            class="route-row"
                            data-req-id="<?= $req['id'] ?>"
                        >
                            <td>
                                <?php if ($req['is_added_manually']): ?>

                                     <div class="manual-requirement">
                                        <button
                                            type="button"
                                            class="delete-requirement no-print"
                                            data-req-id="<?= $req['id'] ?>"
                                            title="Удалить услугу"
                                        >×</button>

                                        <span class="route-room">
                                            <?= e($req['room'] ?: '—') ?>
                                        </span>
                                    </div>
                                <?php else: ?>

                                    <label class="route-check">
                                        <input
                                            type="checkbox"
                                            class="route-toggle"
                                            data-req-id="<?= $req['id'] ?>"
                                            <?= $req['is_selected'] ? 'checked' : '' ?>
                                        >
                                        <span class="route-room">
                                            <?= e($req['room'] ?: '—') ?>
                                        </span>
                                    </label>

                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="route-item-name"><?= e($req['name']) ?></div>
                                <?php if (!empty($req['comment'])): ?>
                                    <div class="route-comment"><?= e($req['comment']) ?></div>
                                <?php endif; ?>
                            </td>
                            <!-- Добавлен data-price чистого числа для корректного JS-расчета -->
                            <td class="route-price-cell" data-price="<?= (float)($req['price'] ?? 0) ?>">
                                <?= number_format($req['price'] ?? 0, 2, ',', ' ') ?> ₽
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="2">Итого</td>
                        <td><strong class="total-price-sum"><?= number_format($totalPrice, 2, ',', ' ') ?> ₽</strong></td>
                    </tr>
                </tfoot>
            </table>

            <br>
            <button class="print-btn" onclick="printRouteSheet(true)">Печать маршрутного листа с ценами</button>
            <p>

            <button class="print-btn" onclick="printRouteSheet(false)">
                Печать маршрутного листа без цен
            </button>
            <p>
             
            <div class="print-buttons">
            <button class="print-btn" onclick="printCertificate('medical')">
                Справка об оплате медосмотра
            </button>

            <button class="print-btn" onclick="printCertificate('lmk')">
                Справка об оплате ЛМК
            </button>

            <button class="print-btn" onclick="printCertificate('hygiene')">
                Справка об оплате гигиенического обучения
            </button>
            </div>
        </div>
                
        <!-- ======================
            БЛОК 5: Печать документов
            ====================== -->
        <div class="card">
            <div class="section-title">Печать документов</div>
            
            <div class="print-buttons">
                <button class="print-btn" onclick="printDocument('pack_without_psy')">
                    Пакет документов (обложка, согласия, заключение)
                </button>

                <button class="print-btn" onclick="printDocument('medical_record_extract')">
                    Выписка из медицинской карты
                </button>

                <button class="print-btn" onclick="printDocument('certificate')">
                    Заключение
                </button>
                
                <button class="print-btn" onclick="printDocument('ambulatory_card')">
                    Амбулаторная карта
                </button>

                <button class="print-btn" onclick="printDocument('contract')">
                    Договор об оказании платных медицинских услуг
                </button>
                
                <button class="print-btn" onclick="printDocument('medical_consent')">
                    Согласие на медицинское вмешательство
                </button>

                <button class="print-btn" onclick="printDocument('psyhiatric_certificate')">
                    Справка психиатра-нарколога
                </button>

                
            </div>
        </div>

        <!-- ======================
            БЛОК 6: Протоколы ВК
            ====================== -->
        <?php if (hasRole(['admin', 'doctor'])): ?>
            <div class="card">

            <div class="section-title">
                Протокол ВК
            </div>

            <?php if ($vkConclusion): ?>

                <div class="info-row">
                    <div class="info-label">
                        Протокол:
                    </div>

                    <div class="info-value">
                        № <?= e($vkConclusion['protocol_number']) ?>
                        от <?= formatDate($vkConclusion['protocol_date']) ?>
                    </div>
                </div>

                <div class="info-row">
                    <div class="info-label">
                        Решение:
                    </div>

                    <div class="info-value">

                        <?php
                        $decisionLabels = [
                            'fit' => 'Допущен',
                            'temporary' => 'Временные противопоказания',
                            'permanent' => 'Постоянные противопоказания'
                        ];

                        echo $decisionLabels[$vkConclusion['decision']]
                            ?? e($vkConclusion['decision']);
                        ?>

                    </div>
                </div>

                <a class="topbar-primary"
                href="/vk/vk.php?id=<?= $vkConclusion['id'] ?>">

                    Открыть заключение ВК

                </a>

            <?php else: ?>

                <div class="info-row">
                    <div class="info-value">
                        Заключение ВК не сформировано
                    </div>
                </div>

                <a class="topbar-primary"
                href="/vk/create.php?patient_id=<?= $data['patient_id'] ?>&visit_id=<?= $data['visit_id'] ?>">

                    Сформировать заключение

                </a>

                <div class="print-buttons">
                <button class="print-btn" onclick="printDocument('certificate_for_treatment')">
                    Справка на дообследование и лечение
                </button>

            <?php endif; ?>

            </div>
        <?php endif; ?>

    </div>
</div>

<script>
window.visitData = {
    visitId: <?= (int)$visitId ?>,
    priceMode: <?= json_encode($data['price_mode']) ?>,
    fixedPrice: <?= json_encode(
        $data['fixed_price'] !== null
            ? (float)$data['fixed_price']
            : null
    ) ?>,
    gender: <?= json_encode($data['gender']) ?>,

    patient: {
        lastName: <?= json_encode($data['last_name']) ?>,
        firstName: <?= json_encode($data['first_name']) ?>,
        middleName: <?= json_encode($data['middle_name']) ?>
    },

    psychiatricService: <?= json_encode($psychiatricService ?? null) ?>
};
</script>

<script src="/assets/js/route-sheet.js"></script>

<?php

require_once __DIR__ . '/../includes/footer.php';