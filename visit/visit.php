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
function printDocument(documentType) {
    const visitId = <?= $data['visit_id'] ?>;
    window.open(`/visit/print_document.php?visit_id=${visitId}&type=${documentType}`, '_blank');
}

function recalcTotal() {
    let total = 0;

    document.querySelectorAll('.route-row').forEach(row => {
        const checkbox = row.querySelector('.route-toggle');
        const priceCell = row.querySelector('.route-price-cell');

        if (!priceCell) return;

        const price = parseFloat(priceCell.dataset.price || 0);
        const selected = !checkbox || checkbox.checked;

        if (selected) {
            total += price;

            priceCell.textContent = price.toLocaleString('ru-RU', {
                minimumFractionDigits: 2
            }) + ' ₽';

            row.classList.remove('is-disabled-print');
        } else {
            priceCell.textContent = '0,00 ₽';
            row.classList.add('is-disabled-print');
        }
    });

    if (priceMode === 'fixed') {
        total = fixedPrice;
    }

    const totalRow = document.querySelector('.total-price-sum');

    if (totalRow) {
        totalRow.textContent = total.toLocaleString('ru-RU', {
            minimumFractionDigits: 2
        }) + ' ₽';
    }
}

document.querySelectorAll('.route-toggle').forEach(cb => {
    cb.addEventListener('change', function() {
        const row = this.closest('.route-row');
        if (row) row.style.opacity = this.checked ? 1 : 0.4;
        
        recalcTotal();

        fetch('/visit/update_completed.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                visit_id: <?= (int)$visitId ?>,
                requirement_id: this.dataset.reqId,
                completed: this.checked
            })
        }).catch(err => console.error('Ошибка сохранения:', err));
    });
});

const visitId = <?= (int)$visitId ?>;
const priceMode = <?= json_encode($data['price_mode']) ?>;
const fixedPrice = <?= json_encode((float)($data['fixed_price'] ?? 0)) ?>;
const exceptionRequirementIds = [39, 40, 41];
const lmkPhotoIds = [39, 40];
const hygieneId = 41;
const addButton = document.getElementById('add-requirement');
const addForm = document.getElementById('add-requirement-form');
const searchInput = document.getElementById('requirement-search');
const results = document.getElementById('requirement-results');

addButton.addEventListener('click', () => {
    addForm.hidden = false;
    searchInput.focus();
});

searchInput.addEventListener('input', async () => {
    const search = searchInput.value.trim();

    console.log('SEARCH:', search);

    if (search.length < 2) {
        results.innerHTML = '';
        return;
    }

    const response = await fetch(
        `/visit/search_requirements.php?search=${encodeURIComponent(search)}`
    );

    console.log('STATUS:', response.status);

    const requirements = await response.json();

    console.log('RESULT:', requirements);

    results.innerHTML = requirements.map(req => `
        <div
            class="requirement-option"
            data-id="${req.id}"
        >
            ${req.name}
        </div>
    `).join('');
});

results.addEventListener('click', async (event) => {
    const option = event.target.closest('.requirement-option');

    if (!option) return;

    const requirementId = option.dataset.id;

    const response = await fetch('/visit/add_requirement.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            visit_id: visitId,
            requirement_id: requirementId
        })
    });

    const data = await response.json();

    if (!data.success) {
        alert(data.error);
        return;
    }

    console.log('Добавлена услуга:', data.requirement);

    addForm.hidden = true;
    searchInput.value = '';
    results.innerHTML = '';

    location.reload();
});

document.addEventListener('click', async (event) => {
    const button = event.target.closest('.delete-requirement');

    if (!button) return;

    const requirementId = button.dataset.reqId;

    const response = await fetch('/visit/delete_requirement.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            visit_id: visitId,
            requirement_id: requirementId
        })
    });

    const data = await response.json();

    if (!data.success) {
        alert(data.error);
        return;
    }

    button.closest('.route-row').remove();
    recalcTotal();
});

function toggleAll(state) {
    document.querySelectorAll('.route-toggle').forEach(cb => {
        if (cb.checked !== state) {
            cb.checked = state;
            cb.dispatchEvent(new Event('change'));
        }
    });
}

// Печать через изолированный iframe
function printRouteSheet(showPrices = true) {
    const priceMode = <?= json_encode($data['price_mode']) ?>;

    const fixedPrice = <?= json_encode(
        $data['fixed_price'] !== null
            ? (float)$data['fixed_price']
            : null
    ) ?>;

    // Услуги, которые относятся к ЛМК,
    // а не к стоимости медицинского осмотра
    const lmkServices = [
        'Оформление ЛМК',
        'Фото 3х4',
        'Гигиеническое обучение'
    ];

    const boldServices = [
    'Исследование крови на брюшной тиф',
    'Анализ крови на HBs-Ag, анти-HBc-Ig (суммарные), анти-HCV-Ig (суммарные), ВИЧ'
    ];

    // ---------------------------------------------------------
    // 1. Создаём скрытый iframe
    // ---------------------------------------------------------

    const iframe = document.createElement('iframe');

    iframe.style.position = 'fixed';
    iframe.style.right = '0';
    iframe.style.bottom = '0';
    iframe.style.width = '0';
    iframe.style.height = '0';
    iframe.style.border = 'none';

    document.body.appendChild(iframe);

    const doc = iframe.contentWindow.document;

    // ---------------------------------------------------------
    // 2. Клонируем маршрутный лист
    // ---------------------------------------------------------

    const originalSheet = document.getElementById('route-sheet');
    const sheetClone = originalSheet.cloneNode(true);

    // Удаляем элементы интерфейса
    sheetClone
        .querySelectorAll('.route-actions, .print-btn, .section-title')
        .forEach(el => el.remove());

    // ---------------------------------------------------------
    // 3. Удаляем старое веб-итого
    //
    // Печатная версия должна сама формировать свои итоги.
    // ---------------------------------------------------------

    sheetClone
        .querySelectorAll('.total-price-sum')
        .forEach(el => {
            const row = el.closest('tr');

            if (row) {
                row.remove();
            }
        });

    // На случай, если итоговая строка имеет отдельный класс
    sheetClone
        .querySelectorAll('.total-price-row')
        .forEach(row => row.remove());

    // ---------------------------------------------------------
    // 4. Обрабатываем услуги
    // ---------------------------------------------------------

    sheetClone.querySelectorAll('.route-row').forEach(row => {
        const checkbox = row.querySelector('.route-toggle');
        const nameElement = row.querySelector('.route-item-name');
        const priceCell = row.querySelector('.route-price-cell');

        if (!nameElement) {
            return;
        }

        const serviceName = nameElement.textContent.trim();

        // Отключённые услуги в печать не попадают
        if (checkbox && !checkbox.checked) {
            row.remove();
            return;
        }

        // -----------------------------------------------------
        // Терапевт
        // -----------------------------------------------------

        if (serviceName === 'Терапевт') {
            // Отбивка перед терапевтом
            const separatorRow = document.createElement('tr');

            separatorRow.className =
                'separator-before-therapist';

            separatorRow.innerHTML = `
                <td colspan="3">&nbsp;</td>
            `;

            // Информационная строка
            const instructionRow = document.createElement('tr');

            instructionRow.className =
                'instruction-before-therapist';

            instructionRow.innerHTML = `
                <td colspan="3">
                    После прохождения кабинетов, перед терапевтом
                    карту сдать администратору
                </td>
            `;

            row.parentNode.insertBefore(
                separatorRow,
                row
            );

            row.parentNode.insertBefore(
                instructionRow,
                row
            );

            nameElement.style.fontWeight = 'bold';

            row.classList.add('therapist-row');
        }
        
        // -----------------------------------------------------
        // Профпатолог
        // -----------------------------------------------------
        if (serviceName === 'Профпатолог') {
            row.classList.add('profpathologist-row');
        }

        // -----------------------------------------------------
        // ЛМК / фото / обучение
        // -----------------------------------------------------

        if (lmkServices.includes(serviceName)) {
            row.classList.add('lmk-row');
        }

        // -----------------------------------------------------
        // Фиксированная цена
        //
        // Обычные услуги медосмотра цену не показывают.
        // У ЛМК / фото / обучения цены остаются.
        // -----------------------------------------------------

        if (
            priceMode === 'fixed' &&
            !lmkServices.includes(serviceName) &&
            priceCell
        ) {
            priceCell.textContent = '';
        }

        // ---------------------------------------------------------
        // Перемещаем профпатолога перед услугами ЛМК
        // ---------------------------------------------------------

        const profpathologistRow =
            sheetClone.querySelector('.profpathologist-row');

        const firstLmkRow =
            sheetClone.querySelector('.lmk-row');

        if (profpathologistRow && firstLmkRow) {
            firstLmkRow.parentNode.insertBefore(
                profpathologistRow,
                firstLmkRow
            );
        }

        if (boldServices.includes(serviceName)) {
        nameElement.style.fontWeight = 'bold';
        }

    });


    // ---------------------------------------------------------
    // 5. Убираем чекбоксы
    // ---------------------------------------------------------

    sheetClone
        .querySelectorAll('.route-check input')
        .forEach(el => el.remove());

    // ---------------------------------------------------------
    // 6. Считаем две отдельные суммы
    // ---------------------------------------------------------

    let medicalTotal = 0;
    let lmkTotal = 0;

    if (showPrices) {
        sheetClone
            .querySelectorAll('.route-row')
            .forEach(row => {
                const nameElement =
                    row.querySelector('.route-item-name');

                const priceCell =
                    row.querySelector('.route-price-cell');

                if (!nameElement || !priceCell) {
                    return;
                }

                const serviceName =
                    nameElement.textContent.trim();

                const price = parseFloat(
                    priceCell.dataset.price || 0
                );

                // ЛМК / фото / обучение
                if (lmkServices.includes(serviceName)) {
                    lmkTotal += price;
                    return;
                }

                // В fixed стоимость медосмотра
                // берём из сохранённого значения визита
                if (priceMode !== 'fixed') {
                    medicalTotal += price;
                }
            });

        // В fixed режиме стоимость медосмотра
        // = зафиксированная стоимость визита
        if (priceMode === 'fixed') {
            medicalTotal = fixedPrice || 0;
        }
    }

    // ---------------------------------------------------------
    // 7. Убираем колонку цен, если печать без цен
    // ---------------------------------------------------------

    // Третью колонку НЕ удаляем.
    // Сохраняем исходную структуру таблицы,
    // просто очищаем содержимое ценовых ячеек.
    // ---------------------------------------------------------

    if (!showPrices) {
        sheetClone
            .querySelectorAll('.route-price-cell')
            .forEach(cell => {
                cell.textContent = '';
            });
    }

    // ---------------------------------------------------------
    // 8. Первое Итого — сразу после профпатолога
    // ---------------------------------------------------------

    if (showPrices) {
        const profpathologistRow =
            sheetClone.querySelector('.profpathologist-row');

        if (profpathologistRow) {
            const medicalTotalRow =
                document.createElement('tr');

            medicalTotalRow.className =
                'medical-total-row';

            medicalTotalRow.innerHTML = `
                <td colspan="2" style="text-align: right;">
                    Итого
                </td>

                <td class="route-price-cell">
                    ${medicalTotal.toLocaleString('ru-RU', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    })} ₽
                </td>
            `;

            profpathologistRow.parentNode.insertBefore(
                medicalTotalRow,
                profpathologistRow.nextSibling
            );
        }
    }

    // ---------------------------------------------------------
    // 9. Отбивка перед ЛМК
    //
    // Она нужна и с ценами, и без цен.
    // ---------------------------------------------------------

    const firstLmkRow =
        sheetClone.querySelector('.lmk-row');

    if (firstLmkRow) {
        const separatorRow =
            document.createElement('tr');

        separatorRow.className =
            'separator-before-lmk';

        separatorRow.innerHTML = `
            <td colspan="3">
                &nbsp;
            </td>
        `;

        firstLmkRow.parentNode.insertBefore(
            separatorRow,
            firstLmkRow
        );
    }

    // ---------------------------------------------------------
    // 10. Второе Итого — после последней услуги ЛМК
    // ---------------------------------------------------------

    if (showPrices) {
        const lmkRows =
            sheetClone.querySelectorAll('.lmk-row');

        if (lmkRows.length > 0) {
            const lastLmkRow =
                lmkRows[lmkRows.length - 1];

            const lmkTotalRow =
                document.createElement('tr');

            lmkTotalRow.className =
                'lmk-total-row';

            lmkTotalRow.innerHTML = `
                <td colspan="2" style="text-align: right;">
                    Итого
                </td>

                <td class="route-price-cell">
                    ${lmkTotal.toLocaleString('ru-RU', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    })} ₽
                </td>
            `;

            lastLmkRow.parentNode.insertBefore(
                lmkTotalRow,
                lastLmkRow.nextSibling
            );
        }
    }

    // ---------------------------------------------------------
    // 11. Стили печати
    // ---------------------------------------------------------

    const style = doc.createElement('style');

    style.textContent = `
        @page {
            margin: 8mm;
        }

        body {
            margin: 0;
            padding: 0;
            background: #fff;
            color: #000;
            font-family: sans-serif;
        }

        .route-sheet-card {
            width: 100%;
            margin: 0;
            padding: 0;
            border: none;
            box-shadow: none;
        }

        .route-table {
            width: 95%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .route-table th,
        .route-table td {
            border: 1px solid grey;
            padding: 2px 2px;
            vertical-align: top;
            line-height: 1.15;
            font-size: 12px;
        }

        .route-table th:nth-child(1),
        .route-table td:nth-child(1) {
            width: 120px;
            text-align: center;
            white-space: nowrap;
        }

        .route-table th:nth-child(3),
        .route-table td:nth-child(3) {
            width: 75px;
            text-align: right;
            white-space: nowrap;
        }

        .route-table th:nth-child(2),
        .route-table td:nth-child(2) {
            width: auto;
        }

        .route-comment {
            font-size: 8px;
            margin-top: 1px;
            color: #333;
        }

        /* -----------------------------------------
           Отбивка перед терапевтом
        ----------------------------------------- */

        .separator-before-therapist td {
            border: none !important;
            height: 3px;
            padding: 0;
        }

        /* -----------------------------------------
           Информационная строка перед терапевтом
        ----------------------------------------- */

        .instruction-before-therapist td {
            border: none !important;
            padding: 0px 0px 0px;
            font-size: 14px;
            font-weight: bold;
            text-align: center;
        }

        /* -----------------------------------------
           Терапевт
        ----------------------------------------- */

        .route-table tr.therapist-row td {
            border-top: 1px solid #000000c2;
        }

        .route-table tr.therapist-row .route-item-name {
            font-weight: bold;
        }

        /* -----------------------------------------
           Отбивка перед ЛМК
        ----------------------------------------- */

        .separator-before-lmk td {
            border: none !important;
            height: 3px;
            padding: 0;
        }

        /* -----------------------------------------
           Итоги
        ----------------------------------------- */

        .medical-total-row td,
        .lmk-total-row td {
            font-weight: bold;
            border-top: 1px solid #000;
            padding-top: 3px;
            padding-bottom: 3px;
        }

        .medical-total-row td:last-child,
        .lmk-total-row td:last-child {
            text-align: right;
        }

        /* -----------------------------------------
           Не разрываем строки между страницами
        ----------------------------------------- */

        .route-table tr {
            break-inside: avoid;
            page-break-inside: avoid;
        }

        .no-print {
            display: none !important;
        }
    `;

    doc.head.appendChild(style);
    doc.body.appendChild(sheetClone);

    // ---------------------------------------------------------
    // 12. Масштаб печати
    //
    // 100% в диалоге печати ≈ нынешние 150%.
    // ---------------------------------------------------------

    const printedSheet =
        doc.getElementById('route-sheet');

    if (printedSheet) {
        const printScale = 0.90;

        printedSheet.style.transform =
            `scale(${printScale})`;

        printedSheet.style.transformOrigin =
            'top left';

        printedSheet.style.width =
            `${100 / printScale}%`;
    }

    // ---------------------------------------------------------
    // 13. Печать
    // ---------------------------------------------------------

    iframe.contentWindow.focus();
    iframe.contentWindow.print();

    // ---------------------------------------------------------
    // 14. Удаляем iframe
    // ---------------------------------------------------------

    setTimeout(() => {
        if (iframe.parentNode) {
            iframe.parentNode.removeChild(iframe);
        }
    }, 1000);
}

function printCertificate(type) {
    // 1. Создаём скрытый iframe для печати
    const iframe = document.createElement('iframe');

    iframe.style.position = 'fixed';
    iframe.style.right = '0';
    iframe.style.bottom = '0';
    iframe.style.width = '0';
    iframe.style.height = '0';
    iframe.style.border = 'none';

    document.body.appendChild(iframe);

    const doc = iframe.contentWindow.document;

    // 2. Создаём основной контейнер справки
    const certificate = doc.createElement('div');
    certificate.className = 'payment-certificate';

    // 3. Шапка справки
    certificate.innerHTML = `
        <h1>Общество с ограниченной ответственностью</h1>
        <h1>«Центр квантовой медицины №1»</h1>
        <p>660048, Россия, Красноярский край,</p>
        <p> г.Красноярск,ул.Калинина, 41</p>
        <p> ОГРН 1032401796250</h2>
        <p> ИНН 2460060098/КПП 246001001 тел: 296-511 </p>
        <p> факс: 2913-009</p>

        <p><style = text-align: left> г. Красноярск  </style></p>

        <h1>Справка об оплате услуг</h1>

        <div class="patient-info">
            <div>
                Выдана (Ф.И.О.)
                <strong><?= e($data['last_name']) ?>
                <?= e($data['first_name']) ?>
                <?= e($data['middle_name']) ?></strong>
            </div>

        </div>

        <h2>В том, что он (она) оплатил(а) медицинские услуги стоимостью </h2>
    `;

    // 4. Клонируем существующий маршрутный лист
    const originalSheet = document.getElementById('route-sheet');

    if (!originalSheet) {
        console.error('Не найден элемент #route-sheet');
        document.body.removeChild(iframe);
        return;
    }

    const sheetClone = originalSheet.cloneNode(true);

    // 5. Удаляем элементы интерфейса
    sheetClone
        .querySelectorAll('.route-actions, .print-btn, .section-title, .route-comment')
        .forEach(el => el.remove());

    // 6. Удаляем невыбранные исследования
    sheetClone
        .querySelectorAll('.route-row.is-disabled-print')
        .forEach(el => el.remove());

        sheetClone.querySelectorAll('.route-row').forEach(row => {
            const id = Number(row.dataset.reqId);

            if (
                (type === 'medical' && [39, 40, 41].includes(id)) ||
                (type === 'lmk' && ![39, 41].includes(id)) ||
                (type === 'hygiene' && id !== 40)
            ) {
                row.remove();
            }
        });
        sheetClone.querySelector('.route-table tfoot')?.remove();

        let total = 0;

        sheetClone.querySelectorAll('.route-row').forEach(row => {
            const priceCell = row.querySelector('.route-price-cell');

            if (!priceCell) return;

            total += parseFloat(priceCell.dataset.price || 0);
        });

        if (type === 'medical' && priceMode === 'fixed') {
            total = fixedPrice || 0;
        }

    // 7. Удаляем checkbox и label вокруг него,
    // но оставляем название кабинета на этом этапе
    sheetClone
        .querySelectorAll('.route-check input')
        .forEach(el => el.remove());

    // 8. Убираем колонку "Каб."
    sheetClone
        .querySelectorAll('.route-table tr')
        .forEach(row => {
            const firstCell = row.querySelector('th:first-child, td:first-child');

            if (firstCell) {
                firstCell.remove();
            }
        });

    // 9. После удаления первой колонки корректируем строку "Итого"
    const totalCell = sheetClone.querySelector('.route-table tfoot td:first-child');

    if (totalCell) {
        totalCell.setAttribute('colspan', '1');
    }

    // 10. Добавляем очищенный маршрутный лист в справку
    const totalRow = doc.createElement('div');

    totalRow.className = 'certificate-total';

    totalRow.textContent =
        `Итого: ${total.toLocaleString('ru-RU', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        })} ₽`;

    sheetClone.appendChild(totalRow);
    certificate.appendChild(sheetClone);

    // 11. Блок подписи и печати
    const signatureBlock = doc.createElement('div');

    signatureBlock.className = 'signature-block';

    signatureBlock.innerHTML = `
        <div class="signature-row">
            <span>Генеральный директор ООО «ЦКМ №1»</span>

            <span class="signature-line"></span>

            <span> Н. Н. Шломов</span>
        </div>

    `;

    certificate.appendChild(signatureBlock);

    // 12. Стили только для печатной версии
    const style = doc.createElement('style');

    style.textContent = `
        @page {
            size: A4;
            margin: 15mm;
        }

        body {
            margin: 0;
            padding: 0;
            background: #fff;
            color: #000;
            font-family: Times New Roman;
            font-size: 12px;
        }

        .payment-certificate {
            width: 100%;
        }

        /* Заголовок */

        h1 {
            text-align: center;
            font-size: 18px;
            margin: 0 0 10px 0;
        }

        p {
            text-align: center;
            font-size: 14px;
            margin: 0 0 2px 0;
        }

        h2 {
            text-align: center;
            font-size: 14px;
            margin: 20px 0 10px 0;
        }

        /* Данные пациента */

        .patient-info {
            font-size: 18px;
            margin-bottom: 15px;
            line-height: 1.6;
        }

        /* Карточка маршрутного листа */

        .route-sheet-card {
            width: 100%;
            margin: 0;
            padding: 0;
            border: none;
            box-shadow: none;
        }

        /* Таблица */

        .route-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .route-table th,
        .route-table td {
            border: 1px solid #000;
            padding: 4px 6px;
            vertical-align: top;
            line-height: 1.2;
        }

        /*
         * После удаления кабинета осталось две колонки:
         * 1 — обследование
         * 2 — цена
         */

        .route-table th:first-child,
        .route-table td:first-child {
            width: auto;
            text-align: left;
        }

        .route-table th:last-child,
        .route-table td:last-child {
            width: 90px;
            text-align: right;
            white-space: nowrap;
        }

        .route-comment {
            font-size: 10px;
            margin-top: 2px;
            color: #333;
        }

        .route-table tfoot td {
            font-weight: bold;
        }

        /*
         * Не разрывать отдельную услугу
         * между страницами
         */

        .route-table tr {
            break-inside: avoid;
            page-break-inside: avoid;
        }

        /*
         * Не разрывать блок подписи
         */

        .signature-block {
            
            margin-top: 35px;
            break-inside: avoid;
            page-break-inside: avoid;
        }

        .signature-row {
            
            display: flex;
            align-items: flex-end;
            gap: 8px;
            margin-bottom: 25px;
        }

        .signature-line {
            display: inline-block;
            width: 150px;
            border-bottom: 1px solid #000;
        }

        .stamp-area {
            text-align: right;
            margin-top: 20px;
            margin-right: 40px;
        }

        .stamp-area span {
            display: inline-block;
            width: 70px;
            height: 50px;
            text-align: center;
            padding-top: 20px;
        }

        .certificate-total {
            margin-top: 8px;
            text-align: right;
            font-size: 16px;
            font-weight: bold;
        }
    `;

    // 13. Добавляем стили и документ в iframe
    doc.head.appendChild(style);
    doc.body.appendChild(certificate);

    // 14. Даём браузеру немного времени построить DOM
    iframe.contentWindow.focus();

    setTimeout(() => {
        iframe.contentWindow.print();

        // 15. Удаляем iframe после печати
        setTimeout(() => {
            if (iframe.parentNode) {
                iframe.parentNode.removeChild(iframe);
            }
        }, 1000);

    }, 100);
}

function printPsychiatricCertificate() {
    // 1. Создаём скрытый iframe для печати
    const iframe = document.createElement('iframe');

    iframe.style.position = 'fixed';
    iframe.style.right = '0';
    iframe.style.bottom = '0';
    iframe.style.width = '0';
    iframe.style.height = '0';
    iframe.style.border = 'none';

    document.body.appendChild(iframe);

    const doc = iframe.contentWindow.document;

    // 2. Создаём справку
    const certificate = doc.createElement('div');
    certificate.className = 'payment-certificate';

    // 3. Шапка справки
    certificate.innerHTML = `
        <h1>Общество с ограниченной ответственностью</h1>
        <h1>«Центр квантовой медицины №1»</h1>

        <p>660048, Россия, Красноярский край,</p>
        <p>г. Красноярск, ул. Калинина, 41</p>
        <p>ОГРН 1032401796250</p>
        <p>ИНН 2460060098/КПП 246001001 тел: 296-511</p>
        <p>факс: 2913-009</p>

        <p class="city">г. Красноярск</p>

        <h1>Справка об оплате психиатрического освидетельствования</h1>

        <div class="patient-info">
            Выдана (Ф.И.О.)
            <strong>
                <?= e($data['last_name']) ?>
                <?= e($data['first_name']) ?>
                <?= e($data['middle_name']) ?>
            </strong>
        </div>

        <p class="description">
            В том, что он (она) оплатил(а) медицинскую услугу:
        </p>

        <table class="service-table">
            <thead>
                <tr>
                    <th>Наименование услуги</th>
                    <th>Стоимость</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><?= e($psychiatricService['name']) ?></td>
                    <td>
                        <?= number_format((float)$psychiatricService['price'], 2, ',', ' ') ?> руб.
                    </td>
                </tr>
            </tbody>
        </table>

        <div class="signature-block">
            <div class="signature-row">
                <span>Генеральный директор ООО «ЦКМ №1»</span>
                <span class="signature-line"></span>
                <span>Н. Н. Шломов</span>
            </div>
        </div>
    `;

    // 4. Стили только для печатной версии
    const style = doc.createElement('style');

    style.textContent = `
        @page {
            size: A4;
            margin: 15mm;
        }

        body {
            margin: 0;
            padding: 0;
            background: #fff;
            color: #000;
            font-family: "Times New Roman", serif;
            font-size: 14px;
        }

        .payment-certificate {
            width: 100%;
        }

        h1 {
            text-align: center;
            font-size: 18px;
            margin: 0 0 10px 0;
        }

        p {
            text-align: center;
            font-size: 14px;
            margin: 0 0 2px 0;
        }

        .city {
            text-align: left;
            margin-top: 20px;
        }

        .patient-info {
            font-size: 18px;
            margin: 25px 0;
            line-height: 1.6;
        }

        .description {
            text-align: left;
            margin: 0 0 15px 0;
        }

        .service-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .service-table th,
        .service-table td {
            border: 1px solid #000;
            padding: 6px;
            vertical-align: top;
        }

        .service-table th:first-child,
        .service-table td:first-child {
            text-align: left;
        }

        .service-table th:last-child,
        .service-table td:last-child {
            width: 100px;
            text-align: right;
            white-space: nowrap;
        }

        .signature-block {
            margin-top: 50px;
            break-inside: avoid;
            page-break-inside: avoid;
        }

        .signature-row {
            display: flex;
            align-items: flex-end;
            gap: 8px;
        }

        .signature-line {
            display: inline-block;
            width: 150px;
            border-bottom: 1px solid #000;
        }
    `;

    // 5. Добавляем стили и справку в iframe
    doc.head.appendChild(style);
    doc.body.appendChild(certificate);

    // 6. Печатаем
    iframe.contentWindow.focus();

    setTimeout(() => {
        iframe.contentWindow.print();

        setTimeout(() => {
            if (iframe.parentNode) {
                iframe.parentNode.removeChild(iframe);
            }
        }, 1000);
    }, 100);
}
</script>

<?php

require_once __DIR__ . '/../includes/footer.php';