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
        r.id, r.name, r.type, r.room, r.comment, r.sort_order,
        rp.price,
        MIN(fr.period_years) AS period_years
    FROM requirements r
    LEFT JOIN LATERAL (
        SELECT price FROM requirement_prices
        WHERE requirement_id = r.id
        ORDER BY valid_from DESC LIMIT 1
    ) rp ON TRUE
    LEFT JOIN factor_requirements fr ON fr.requirement_id = r.id
    LEFT JOIN visit_hazard_factors vhf ON vhf.hazard_factor_id = fr.hazard_factor_id AND vhf.visit_id = :visit_id
    WHERE (r.is_global = TRUE OR vhf.visit_id IS NOT NULL)
      AND (r.gender IS NULL OR r.gender = :gender)
      AND (r.min_age IS NULL OR r.min_age <= :age)
      AND (fr.exam_type IS NULL OR fr.exam_type = :exam_type)
    GROUP BY r.id, r.name, r.type, r.room, r.comment, r.sort_order, rp.price
    ORDER BY r.sort_order, r.name
");

$stmt->execute([
    'visit_id' => $visitId,
    'gender' => $data['gender'],
    'age' => $age,
    'exam_type' => $data['exam_type']
]);

$routeRequirements = $stmt->fetchAll(PDO::FETCH_ASSOC);

$totalPrice = 0;

foreach ($routeRequirements as $req) {
    $totalPrice += (float)($req['price'] ?? 0);
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
            
            <div class="info-row">
                <div class="info-label">ОПО: </div>
                <div class="info-value">
                    <?= $data['psychiatric_exam'] ? 'Требуется' : 'Не требуется' ?>
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
                        <tr class="route-row">
                            <td>
                                <label class="route-check">
                                    <!-- Добавлен data-req-id -->
                                    <input type="checkbox" class="route-toggle" data-req-id="<?= $req['id'] ?>" checked>
                                    <?= e($req['room'] ?: '—') ?>
                                </label>
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
            <button class="print-btn" onclick="printRouteSheet()">Печать маршрутного листа</button>
        </div>
                
        <!-- ======================
            БЛОК 5: Печать документов
            ====================== -->
        <div class="card">
            <div class="section-title">Печать документов</div>
            
            <div class="print-buttons">
                <button class="print-btn" onclick="printDocument('pack_with_psy')">
                    Пакет документов с ОПО
                </button>

                <button class="print-btn" onclick="printDocument('pack_without_psy')">
                    Пакет документов без ОПО
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
                
                <button class="print-btn" onclick="printDocument('personal_data_consent')">
                    Согласие на ОПД
                </button>

                <button class="print-btn" onclick="printDocument('psyhiatric_certificate')">
                    Справка психиатра-нарколога
                </button>

                <button class="print-btn" onclick="printDocument('medical_record_extract')">
                    Выписка из медицинской карты
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

        if (checkbox && checkbox.checked) {
            total += price;
            priceCell.textContent = price.toLocaleString('ru-RU', {minimumFractionDigits: 2}) + ' ₽';
            row.classList.remove('is-disabled-print');
        } else {
            priceCell.textContent = '0,00 ₽'; 
            row.classList.add('is-disabled-print');
        }
    });
    
    const totalRow = document.querySelector('.total-price-sum');
    if (totalRow) {
        totalRow.textContent = total.toLocaleString('ru-RU', {minimumFractionDigits: 2}) + ' ₽';
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

function toggleAll(state) {
    document.querySelectorAll('.route-toggle').forEach(cb => {
        if (cb.checked !== state) {
            cb.checked = state;
            cb.dispatchEvent(new Event('change'));
        }
    });
}

// Печать через изолированный iframe
function printRouteSheet() {
    // 1. Создаем скрытый элемент iframe
    const iframe = document.createElement('iframe');
    iframe.style.position = 'fixed';
    iframe.style.right = '0';
    iframe.style.bottom = '0';
    iframe.style.width = '0';
    iframe.style.height = '0';
    iframe.style.border = 'none';
    document.body.appendChild(iframe);

    const doc = iframe.contentWindow.document;

    // 2. Клонируем очищенную от интерфейса таблицу
    const originalSheet = document.getElementById('route-sheet');
    const sheetClone = originalSheet.cloneNode(true);

    // Удаляем ненужные кнопки и выключенные строки в копии
    sheetClone.querySelectorAll('.route-actions, .print-btn, .section-title').forEach(el => el.remove());
    sheetClone.querySelectorAll('.route-row.is-disabled-print').forEach(el => el.remove());
    sheetClone.querySelectorAll('.route-check input').forEach(el => el.remove());

    // 3. Стили для экстремального сжатия таблицы внутри А4 (ваша старая JS-логика)
    const style = doc.createElement('style');
    style.textContent = `
        body { margin: 0; padding: 0; background: #fff; font-family: sans-serif; }
        .route-table { width: 95%; border-collapse: collapse; table-layout: fixed; }
        .route-table th, .route-table td { border: 1px solid grey; padding: 1px 3px; vertical-align: top; line-height: 1; font-size: 10px; }
        .route-table th:nth-child(1), .route-table td:nth-child(1) { width: 120px; text-align: center; white-space: nowrap; }
        .route-table th:nth-child(3), .route-table td:nth-child(3) { width: 75px; text-align: right; white-space: nowrap; }
        .route-table th:nth-child(2), .route-table td:nth-child(2) { width: auto; }
        .route-comment { font-size: 8px; margin-top: 1px; color: #333; }
        .route-table tfoot td { font-weight: bold; }
    `;

    // 4. Наполняем iframe данными и стилями
    doc.head.appendChild(style);
    doc.body.appendChild(sheetClone);

    // 5. Логика масштабирования по высоте (если контента слишком много)
    const targetHeight = window.innerHeight * 0.5;
    const printedSheet = doc.getElementById('route-sheet');
    const actualHeight = printedSheet.scrollHeight;

    if (actualHeight > targetHeight) {
        const scale = targetHeight / actualHeight;
        printedSheet.style.transform = `scale(${scale})`;
        printedSheet.style.transformOrigin = 'top left';
        printedSheet.style.width = `${100 / scale}%`;
    }

    // 6. Вызываем печать внутри фрейма и удаляем его
    iframe.contentWindow.focus();
    iframe.contentWindow.print();
    
    // Удаляем временный фрейм через секунду после закрытия окна печати
    setTimeout(() => { document.body.removeChild(iframe); }, 1000);
}
</script>

<?php

require_once __DIR__ . '/../includes/footer.php';