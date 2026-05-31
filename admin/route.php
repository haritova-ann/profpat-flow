<?php

require_once __DIR__ . '/../includes/bootstrap.php';

/**
 * Проверка прав доступа
 */
if (!hasRole(['admin'])) {
    die('У вас недостаточно прав');
}

$stmt = $pdo->query("

SELECT
    id,
    name,
    type,
    room,
    comment,
    sort_order

FROM requirements

ORDER BY
    sort_order,
    name

");

$requirements =
    $stmt->fetchAll();

$pageTitle =
    'Маршрутные листы';

require_once
    __DIR__.'/../includes/header.php';

?>

<div class="container">

<h2>
Настройка маршрутных листов
</h2>

<form
method="POST"
action="/admin/save_route.php"
>

<table>

<tr>

<th>Порядок</th>

<th>Кабинет</th>

<th>Услуга</th>

<th>Комментарий</th>

</tr>

<?php foreach (
    $requirements as $req
): ?>

<tr>

<td>

<input
type="number"

name="sort_order[<?= $req['id'] ?>]"

value="<?= e(
$req['sort_order']
) ?>"

>

</td>

<td>

<input
type="text"

name="room[<?= $req['id'] ?>]"

value="<?= e(
$req['room']
) ?>"

>

</td>

<td>

<?= e(
$req['name']
) ?>

</td>

<td>

<input
type="text"

name="comment[<?= $req['id'] ?>]"

value="<?= e(
$req['comment']
) ?>"

>

</td>

</tr>

<?php endforeach; ?>

</table>

<br>

<button>

Сохранить

</button>

</form>

</div>

<?php
require_once
__DIR__.'/../includes/footer.php';