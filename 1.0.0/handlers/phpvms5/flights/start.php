<?php
if($_SERVER['REQUEST_METHOD'] !== 'POST')
{
    error(405, 'POST request method expected, received a ' . $_SERVER['REQUEST_METHOD'] . ' request instead.');
    exit;
}
assertData($_POST, array('bidID'=>'int'));

$chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
$trackingID = '';
for ($i = 0; $i < 12; $i++) {
    $trackingID .= $chars[rand(0, strlen($chars) - 1)];
}

echo(json_encode(array('trackingID'=>$trackingID)));
?>