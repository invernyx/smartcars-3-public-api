<?php
if($_SERVER['REQUEST_METHOD'] !== 'POST')
{
    error(405, 'POST request method expected, received a ' . $_SERVER['REQUEST_METHOD'] . ' request instead.');
    exit;
}

assertData($_POST, array('bidID' => 'integer', 'aircraftID' => 'integer'));    
$database->execute('REPLACE INTO smartCARS3_BidAircraft (bidID, aircraftID) VALUES (?, ?)', array($_POST['bidID'], $_POST['aircraftID']));
echo(json_encode(array('bidID'=>intval($_POST['bidID']))));
?>