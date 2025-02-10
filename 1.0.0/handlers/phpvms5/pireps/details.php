<?php
if(!function_exists('gzdecode'))
{
    function gzdecode($data)
    {
        return gzinflate(substr($data, 10, -8));
    }
}

$database->createTable('smartCARS3_FlightData', 'pilotID int(11) NOT NULL, pirepID int(11) NOT NULL, locations blob NOT NULL, log blob NOT NULL, PRIMARY KEY (pilotID, pirepID)');
assertData($_GET, array('id'=>'int'));

$pirep = $database->fetch('SELECT log as flightLog FROM ' . dbPrefix . 'pireps WHERE pirepid=? AND pilotid=?', array($_GET['id'], $pilotID));
if($pirep === array())
{
    error(404, 'A PIREP with this ID was not found');
    exit;
}
$pirep = $pirep[0];

$flightData = $database->fetch('SELECT locations, log FROM smartCARS3_FlightData WHERE pilotID=? AND pirepID=?', array($pilotID, $_GET['id']));
if($flightData === array()) {
    $pirep['locationData'] = null;
    $pirep['flightData'] = null;
}
else {
    $pirep['locationData'] = json_decode(gzdecode($flightData[0]['locations']));
    $pirep['flightData'] = json_decode(gzdecode($flightData[0]['log']));
}

$pirep['flightLog'] = str_replace('*', '\n', $pirep['flightLog']);
$pirep['flightLog'] = explode('\n', $pirep['flightLog']);
echo(json_encode($pirep));
?>