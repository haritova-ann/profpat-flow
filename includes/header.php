<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title><?= e($pageTitle ?? 'МИС') ?></title>

    <link rel="stylesheet" href="/assets/css/app.css">
</head>

<body>

<div class="topbar">

    <div class="topbar-left">

        <?php foreach ($topbarLeft ?? [] as $item): ?>

            <?php if (($item['type'] ?? 'link') === 'submit'): ?>

                <button
                    type="submit"
                    form="<?= e($item['form']) ?>"
                    class="<?= e($item['class'] ?? 'topbar-btn') ?>"
                >
                    <?= e($item['label']) ?>
                </button>

            <?php elseif (($item['type'] ?? '') === 'button'): ?>

                <button
                    type="button"
                    id="<?= e($item['id'] ?? '') ?>"
                    class="<?= e($item['class'] ?? 'topbar-btn') ?>"
                    <?php if (!empty($item['data-action'])): ?>
                        data-action="<?= e($item['data-action']) ?>"
                    <?php endif; ?>
                >
                    <?= e($item['label']) ?>
                </button>

            <?php else: ?>

                <a
                    class="<?= e($item['class'] ?? 'topbar-btn') ?>"
                    href="<?= e($item['href'] ?? '#') ?>"
                >
                    <?= e($item['label']) ?>
                </a>

            <?php endif; ?>

        <?php endforeach; ?>

    </div>

    <div class="topbar-right">

        <?php foreach ($topbarRight ?? [] as $item): ?>

            <?php if (($item['type'] ?? 'link') === 'submit'): ?>

                <button
                    type="submit"
                    form="<?= e($item['form']) ?>"
                    class="<?= e($item['class'] ?? 'topbar-btn') ?>"
                >
                    <?= e($item['label']) ?>
                </button>

            <?php else: ?>

                <a
                    class="<?= e($item['class'] ?? 'topbar-btn') ?>"
                    href="<?= e($item['href']) ?>"
                >
                    <?= e($item['label']) ?>
                </a>

            <?php endif; ?>

        <?php endforeach; ?>

        <div class="user-menu">

            <button class="user-menu-btn" type="button">
                <div class="user-menu-name">
                    <?= e($_SESSION['user']['full_name']) ?>
                </div>

                <span class="user-menu-arrow">▾</span>
            </button>

            <div class="user-dropdown">
                <a href="/profile/doctor.php">
                    Профиль
                </a>

                <a href="/auth/logout.php">
                    Выйти
                </a>
            </div>

        </div>

    </div>

</div>

<script>

const userMenu = document.querySelector('.user-menu');
const userMenuBtn = document.querySelector('.user-menu-btn');

if (userMenu && userMenuBtn) {

    userMenuBtn.addEventListener('click', () => {
        userMenu.classList.toggle('open');
    });

    document.addEventListener('click', (e) => {

        if (!userMenu.contains(e.target)) {
            userMenu.classList.remove('open');
        }

    });

}

</script>