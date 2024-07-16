<?php
$database->createTable('smartCARS3_OngoingFlights', 'timestamp TIMESTAMP DEFAULT NOW() NOT NULL, pilotID INT(11) NOT NULL, bidID INT(11) NOT NULL, heading FLOAT NOT NULL, latitude FLOAT NOT NULL, longitude FLOAT NOT NULL, PRIMARY KEY (timestamp, pilotID, bidID)');
$database->execute('DELETE FROM smartCARS3_OngoingFlights WHERE timestamp < DATE_SUB(NOW(), INTERVAL 18 HOUR)');

if($_SERVER['REQUEST_METHOD'] !== 'POST')
{
    error(405, 'POST request method expected, received a ' . $_SERVER['REQUEST_METHOD'] . ' request instead.');
    exit;
}
assertData($_POST, array('bidID' => 'integer'));

if($database->fetch('SELECT id FROM ' . dbPrefix . 'bids WHERE bidid=? AND pilotid=?', array($_POST['bidID'], $pilotID)) === array())
{
    error(404, 'There is no ongoing flight');
}

$database->execute('DELETE FROM smartCARS3_OngoingFlights WHERE pilotID = ? AND bidID = ?', array($pilotID, $_POST['bidID']));
?>
