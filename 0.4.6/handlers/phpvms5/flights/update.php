<?php
$database->createTable('smartCARS3_OngoingFlights', 'timestamp TIMESTAMP DEFAULT NOW() NOT NULL, pilotID INT(11) NOT NULL, bidID INT(11) NOT NULL, heading FLOAT NOT NULL, latitude FLOAT NOT NULL, longitude FLOAT NOT NULL, PRIMARY KEY (timestamp, pilotID, bidID)');
$database->execute('DELETE FROM smartCARS3_OngoingFlights WHERE timestamp < DATE_SUB(NOW(), INTERVAL 18 HOUR)');

if($_SERVER['REQUEST_METHOD'] !== 'POST')
{
    error(405, 'POST request method expected, received a ' . $_SERVER['REQUEST_METHOD'] . ' request instead.');
}
assertData($_POST, array('bidID' => 'integer', 'timeRemaining' => 'float', 'latitude' => 'latitude', 'longitude' => 'longitude', 'heading' => 'heading', 'altitude' => 'integer', 'groundSpeed' => 'integer', 'distanceRemaining' => 'integer', 'route' => 'array', 'phase' => 'phase', 'network' => 'network', 'aircraft' => 'integer'));
if($_POST['distanceRemaining'] < 0)
{
    error(400, 'Distance remaining must be above 0');
}
if($_POST['groundSpeed'] < 0)
{
    error(400, 'Ground speed must be above 0');
}

$flightDetails = $database->fetch('SELECT ' . dbPrefix . 'schedules.code, ' . dbPrefix . 'schedules.flightnum, ' . dbPrefix . 'schedules.aircraft, ' . dbPrefix . 'schedules.depicao, ' . dbPrefix . 'schedules.arricao, ' . dbPrefix . 'schedules.deptime, ' . dbPrefix . 'schedules.arrtime FROM ' . dbPrefix . 'schedules INNER JOIN ' . dbPrefix . 'bids ON ' . dbPrefix . 'bids.pilotid = ? AND ' . dbPrefix . 'bids.bidid = ? WHERE ' . dbPrefix . 'schedules.id = ' . dbPrefix . 'bids.routeid', array($pilotID, $_POST['bidID']));
if($flightDetails === array())
{
    error(404, 'There is flight with the specified bid ID');
}
$flightDetails = $flightDetails[0];

require_once('../core/common/NavData.class.php');
require_once('../core/common/ACARSData.class.php');

$flightUpdate = ACARSData::UpdateFlightData($pilotID, array(
    'pilotid' => $pilotID,
    'flightnum' => $flightDetails['code'] . $flightDetails['flightnum'],
    'aircraft' => $flightDetails['aircraft'],
    'lat' => $_POST['latitude'],
    'lng' => $_POST['longitude'],
    'heading' => $_POST['heading'],
    'alt' => $_POST['altitude'],
    'gs' => $_POST['groundSpeed'],
    'depicao' => $flightDetails['depicao'],
    'arricao' => $flightDetails['arricao'],
    'deptime' => $flightDetails['deptime'],
    'arrtime' => $flightDetails['arrtime'],
    'route' => implode(' ', $_POST['route']),
    'distremain' => $_POST['distanceRemaining'],
    'timeremaining' => sprintf('%01d:%02d', floor($_POST['timeRemaining']), round($_POST['timeRemaining'] * 60) % 60),
    'phasedetail' => ucfirst(strtolower(str_replace("_", " ", $_POST['phase']))),
    'online' => $_POST['network'],
    'client' => 'smartCARS 3'
));

if($flightUpdate === false)
{
    error(500, 'Failed updating flight data');
}

$database->execute('INSERT INTO smartCARS3_OngoingFlights (pilotID, bidID, heading, latitude, longitude) VALUES (?, ?, ?, ?, ?)', array($pilotID, $_POST['bidID'], $_POST['heading'], $_POST['latitude'], $_POST['longitude']));
?>