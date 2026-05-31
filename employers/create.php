<?php

require_once __DIR__ . '/../includes/bootstrap.php';

// Контекст страницы для header (активные пункты, условия отображения)
$page = 'employer';

// ======================
// Настраиваем header
// ======================
$pageTitle = 'Новая организация';

$topbarLeft = [
    [
        'label' => '← Список организаций',
        'href' => '/employers/employers.php'
    ],
    [
        'type' => 'submit',
        'label' => 'Сохранить',
        'form' => 'employer-form',
        'class' => 'topbar-primary'
    ]
];

require_once __DIR__ . '/../includes/header.php';
?>

<!-- =========================
Основной контент
========================= -->
<div class="container">

    <h2>Новая организация</h2>

    <form action="save.php" id='employer-form' method="POST">

        <div class="section">

            <h3>Основные данные</h3>

            <div class="form-group">
                <label>Название организации</label>
                <input type="text" name="name" required>
            </div>

            <div class="row">
                <div class="form-group">
                <label>ИНН</label>
                <input type="text" name="inn" maxlength="12">
                </div>

                <div class="form-group">
                <label>ОГРН</label>
                <input type="text" name="ogrn" maxlength="15">
                </div>

                <div class="form-group">
                <label>ОКВЭД</label>
                <input type="text" name="okvd">
                </div>
            </div>

        </div>

        <div class="section">

            <h3>Контакты</h3>

            <div class="row">
                <div class="form-group">
                <label>Телефон</label>
                <input type="text" name="phone" id="phoneNumber" placeholder="+7 ___ ___ __ __">
                </div>

                <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" id="email" placeholder="example@mail.com">
                </div>
            </div>

        </div>

        <div class="section">

            <h3>Юридический адрес</h3>

            <div class="row">
                <div class="form-group">
                <label>Субъект РФ</label>
                <input type="text" name="region" value="Красноярский край">
                </div>

                <div class="form-group">
                <label>Район</label>
                <input
                    type="text" name="district">
                </div>

                <div class="form-group">
                <label>Населенный пункт</label>
                <input
                    type="text" name="locality" value="г. Красноярск">
                </div>
            </div>

            <div class="row">
                <div class="form-group">
                <label>Улица</label>
                <input type="text" name="street">
                </div>

                <div class="form-group small">
                <label>Дом</label>
                <input type="text" name="house">
                </div>

                <div class="form-group small">
                <label>Корпус</label>
                <input
                    type="text" name="building">
                </div>

                <div class="form-group small">
                <label>Квартира</label>
                <input type="text" name="flat">
                </div>
            </div>

        </div>

    </form>

</div>

<?php

require_once __DIR__ . '/../includes/footer.php';