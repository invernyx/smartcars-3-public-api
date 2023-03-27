<?php
if($_SERVER['REQUEST_METHOD'] !== 'POST')
{
    error(405, 'POST request method expected, received a ' . $_SERVER['REQUEST_METHOD'] . ' request instead.');
}
assertData($_POST, array('bidID' => 'integer', 'timeRemaining' => 'float', 'latitude' => 'latitude', 'longitude' => 'longitude', 'heading' => 'heading', 'altitude' => 'integer', 'groundSpeed' => 'integer', 'distanceRemaining' => 'float', 'route' => 'array', 'phase' => 'phase', 'network' => 'network'));
if($_POST['distanceRemaining'] < 0)
{
    error(400, 'Distance remaining must be above 0');
}
if($_POST['groundSpeed'] < 0)
{
    error(400, 'Ground speed must be above 0');
}

function phaseToStatus(string $phase): string {
    switch(strtolower($phase)) {
        case 'boarding':
            return 'BST';
        case 'push_back':
            return 'PBT';
        case 'taxi':
            return 'TXI';
        case 'take_off':
            return 'TOF';
        case 'rejected_take_off':
            return 'TXI';
        case 'climb_out':
            return 'TKO';
        case 'climb':
            return 'TKO';
        case 'cruise':
            return 'ENR';
        case 'descent':
            return 'TEN';
        case 'approach':
            return 'APR';
        case 'final':
            return 'FIN';
        case 'landed':
            return 'LAN';
        case 'go_around':
            return 'APR';
        case 'taxi_to_gate':
            return 'LAN';
        case 'deboarding':
            return 'ONB';
        case 'diverted':
            return 'DV';
    }
}

$flightID = $database->fetch('SELECT flight_id FROM ' . dbPrefix . 'bids WHERE id=? AND user_id=?', array($_POST['bidID'], $pilotID));
if($flightID === array())
{
    error(404, 'There is no flight with the specified bid ID');
}
$flightID = $flightID[0]['flight_id'];

$existingFlight = $database->fetch('SELECT id FROM ' . dbPrefix . 'acars WHERE id=?', array($flightID));
if($existingFlight === array())
{
    $flightDetails = $database->fetch('SELECT ' .
    dbPrefix . 'id as flight_id, ' .
    dbPrefix . 'airline_id, ' .
    dbPrefix . 'flight_number, ' .
    dbPrefix . 'route_code, ' .
    dbPrefix . 'route_leg, ' .
    dbPrefix . 'flight_type, ' .
    dbPrefix . 'dpt_airport_id, ' .
    dbPrefix . 'arr_airport_id, ' .
    dbPrefix . 'alt_airport_id, ' .
    dbPrefix . 'level, ' .
    dbPrefix . 'distance as planned_distance, ' .
    dbPrefix . 'flight_time as planned_flight_time
    FROM ' . dbPrefix . 'flights WHERE id=?', array($flightID));

    if($flightDetails === array())
    {
        error(404, 'There is no flight with the specified bid ID');
    }
    $flightDetails = $flightDetails[0];

    $database->execute('
    INSERT INTO ' . dbPrefix . 'pireps
    (id, user_id, airline_id, flight_id, flight_number, route_code, route_leg, flight_type, dpt_airport_id, arr_airport_id, alt_airport_id, level, planned_distance, planned_flight_time, route, source, source_name, status, created_at, updated_at)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, "smartCARS 3", 0, NOW(), NOW())',
    array($flightDetails['flight_id'], $pilotID, $flightDetails['airline_id'], $flightDetails['flight_id'], $flightDetails['flight_number'], $flightDetails['route_code'], $flightDetails['route_leg'], $flightDetails['flight_type'], $flightDetails['dpt_airport_id'], $flightDetails['arr_airport_id'], $flightDetails['alt_airport_id'], $flightDetails['level'], $flightDetails['planned_distance'], $flightDetails['planned_flight_time'], implode(' ', $_POST['route'])));

    $database->execute('INSERT INTO ' . dbPrefix . 'acars (id, pirep_id, type, status, lat, lon, distance, heading, altitude, gs, created_at, updated_at) VALUES (?, ?, 0, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())', array($flightDetails['flight_id'], $flightDetails['flight_id'], phaseToStatus($_POST['phase']), $_POST['latitude'], $_POST['longitude'], $_POST['distanceRemaining'], $_POST['heading'], $_POST['altitude'], $_POST['groundSpeed']));
}
else {
    $database->execute('UPDATE ' . dbPrefix . 'acars SET status = ?, lat = ?, lon = ?, distance = ?, heading = ?, altitude = ?, gs = ?, updated_at = NOW()', array(phaseToStatus($_POST['phase']), $_POST['latitude'], $_POST['longitude'], $_POST['distanceRemaining'], $_POST['heading'], $_POST['altitude'], $_POST['groundSpeed']));
}
?>