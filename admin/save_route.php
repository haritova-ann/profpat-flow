<?php

require_once
__DIR__.'/../includes/bootstrap.php';

foreach (
$_POST['sort_order']
as $id => $sort
) {

$stmt =
$pdo->prepare("

UPDATE requirements

SET

sort_order=
:sort,

room=
:room,

comment=
:comment

WHERE id=:id

");

$stmt->execute([

'id'=>$id,

'sort'=>
$sort,

'room'=>
$_POST['room'][$id],

'comment'=>
$_POST['comment'][$id]

]);

}

header('Location: /admin/route.php');

exit;