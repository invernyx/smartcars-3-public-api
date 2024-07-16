<?php
$database->createTable('smartCARS3_FlightData', 'pilotID int(11) NOT NULL, pirepID int(11) NOT NULL, locations blob NOT NULL, log blob NOT NULL, PRIMARY KEY (pilotID, pirepID)');

if($_SERVER['REQUEST_METHOD'] !== 'POST')
{
    error(405, 'POST request method expected, received a ' . $_SERVER['REQUEST_METHOD'] . ' request instead.');
    exit;
}
assertData($_POST, array('bidID' => 'int', 'aircraft' => 'int', 'remainingLoad' => 'int', 'flightTime' => 'double', 'landingRate' => 'int', 'fuelUsed' => 'float', 'route' => 'array', 'flightLog' => 'string', 'flightData' => 'string'));
$_POST['flightLog'] = base64_decode($_POST['flightLog']);
if($_POST['flightLog'] === false)
{
    error(400, 'Invalid flight log');
    exit;
}
$_POST['flightLog'] = explode("\n", $_POST['flightLog']);

$_POST['flightData'] = base64_decode($_POST['flightData']);
if($_POST['flightData'] === false)
{
    error(400, 'Invalid flight data');
    exit;
}
$_POST['flightData'] = json_decode($_POST['flightData'], true);
if($_POST['flightData'] === null)
{
    error(400, 'Invalid flight data');
    exit;
}

require_once('../core/common/NavData.class.php');
require_once('../core/common/ACARSData.class.php');

$flights = $database->fetch('SELECT id FROM ' . dbPrefix . 'acarsdata WHERE pilotid=?', array($pilotID));
if($flights === array())
{
    error(404, 'There is no ongoing flight');
    exit;
}
$bids = $database->fetch('SELECT routeid FROM ' . dbPrefix . 'bids WHERE bidid=? AND pilotid=?', array($_POST['bidID'], $pilotID));
if($bids === array())
{
    error(404, 'There is no ongoing flight');
    exit;
}
$route = $database->fetch('SELECT code, flightnum, depicao, arricao, notes FROM ' . dbPrefix . 'schedules WHERE id=?', array($bids[0]['routeid']));
if($route === array())
{
    error(404, 'There is no ongoing flight');
    exit;
}
$route = $route[0];

$data = array(
    'pilotid' => $pilotID,
    'code' => $route['code'],
    'flightnum' => $route['flightnum'],
    'depicao' => $route['depicao'],
    'arricao' => $route['arricao'],
    'route' => implode(' ', $_POST['route']),
    'aircraft' => $_POST['aircraft'],
    'flighttime' => sprintf('%01d:%02d', floor($_POST['flightTime']), round($_POST['flightTime'] * 60) % 60),
    'landingrate' => round($_POST['landingRate']),
    'submitdate' => date('Y-m-d H:i:s'),
    'fuelused' => $_POST['fuelUsed'],
    'source' => 'smartCARS 3',
    'log' => implode('*', $_POST['flightLog'])
);

if($_POST['comments'] !== null)
    $data['comment'] = $_POST['comments'];
else
    $data['comment'] = '';

$PIREPSubmission = ACARSData::FilePirep($pilotID, $data);
if($PIREPSubmission === false)
{
    error(500, 'Failed submitting PIREP');
    exit;
}

$pirepID = $database->fetch('SELECT pirepid FROM ' . dbPrefix . 'pireps WHERE pilotid=? ORDER BY submitdate DESC LIMIT 1', array($pilotID));
if($pirepID === array())
{
    error(500, 'Failed submitting PIREP');
    exit;
}
$pirepID = $pirepID[0]['pirepid'];

$locationData = $database->fetch('SELECT heading, latitude, longitude FROM smartCARS3_OngoingFlights WHERE pilotID=? AND bidID=? ORDER BY timestamp DESC', array($pilotID, $_POST['bidID']));
if($locationData === array())
{
    error(500, 'Failed fetching flight data');
    exit;
}

if($route['notes'] === 'smartCARS Charter Flight') {
    $database->execute('DELETE FROM ' . dbPrefix . 'schedules WHERE id=?', array($bids[0]['routeid']));
    $charterFlights = $database->fetch('SELECT id FROM ' . dbPrefix . 'schedules WHERE code=? AND notes="smartCARS Charter Flight"', array($route['code']));
    if($charterFlights === array())
    {
        $database->execute('DELETE FROM ' . dbPrefix . 'airlines WHERE code=? AND name="Charter" AND enabled=0', array($route['code']));
    }
}

$database->execute('INSERT INTO smartCARS3_FlightData (pilotID, pirepID, locations, log) VALUES (?, ?, ?, ?)', array($pilotID, $pirepID, gzencode(json_encode($locationData)), gzencode(json_encode($_POST['flightData']))));
$database->execute('DELETE FROM smartCARS3_OngoingFlights WHERE pilotID=? AND bidID=?', array($pilotID, $_POST['bidID']));
echo(json_encode(array('pirepID' => $pirepID)));
?>