<?php
$query = 'SELECT id,
code,
flightnum as number,
flighttype as type,
depicao as departureAirport,
arricao as arrivalAirport,
route,
aircraft,
flightlevel as flightLevel,
distance,
deptime as departureTime,
arrtime as arrivalTime,
CAST(flighttime AS DECIMAL(4,2)) as flightTime,
daysofweek as daysOfWeek,
notes FROM ' . dbPrefix . 'schedules';

$whereInQuery = false;
$parameters = array();

if($_GET['departureAirport'] !== null)
{
    assertData($_GET, array('departureAirport' => 'airport'));
    if(!$whereInQuery)
    {
        $whereInQuery = true;
        $query .= ' WHERE ';
    }
    $query .= 'depicao = :departureAirport';
    $parameters[':departureAirport'] = $_GET['departureAirport'];
}
if($_GET['arrivalAirport'] !== null)
{
    assertData($_GET, array('arrivalAirport' => 'airport'));
    if(!$whereInQuery)
    {
        $whereInQuery = true;
        $query .= ' WHERE ';
    }
    else
    {
        $query .= ' AND ';
    }
    $query .= 'arricao = :arrivalAirport';
    $parameters[':arrivalAirport'] = $_GET['arrivalAirport'];
}
if($_GET['aircraft'] !== null)
{
    assertData($_GET, array('aircraft' => 'int'));
    if(!$whereInQuery)
    {
        $whereInQuery = true;
        $query .= ' WHERE ';
    }
    else
    {
        $query .= ' AND ';
    }
    $query .= 'aircraft IN (SELECT id FROM ' . dbPrefix . 'aircraft WHERE name LIKE (SELECT NAME from ' . dbPrefix . 'aircraft WHERE id = :aircraft))';
    $parameters[':aircraft'] = $_GET['aircraft'];
}
if($_GET['callsign'] !== null)
{
    assertData($_GET, array('callsign' => 'string'));
    if(!$whereInQuery)
    {
        $whereInQuery = true;
        $query .= ' WHERE ';
    }
    else
    {
        $query .= ' AND ';
    }
    $query .= 'id IN (SELECT id FROM ' . dbPrefix . 'schedules WHERE CONCAT(code, flightnum) LIKE :callsign)';
    $parameters[':callsign'] = '%' . $_GET['callsign'] . '%';
}
if($_GET['minimumFlightTime'] !== null)
{
    assertData($_GET, array('minimumFlightTime' => 'float'));
    if(!$whereInQuery)
    {
        $whereInQuery = true;
        $query .= ' WHERE ';
    }
    else
    {
        $query .= ' AND ';
    }
    $query .= 'CAST(flighttime AS DECIMAL(4,2)) >= :minimumFlightTime';
    $parameters[':minimumFlightTime'] = $_GET['minimumFlightTime'];
}
if($_GET['maximumFlightTime'] !== null)
{
    assertData($_GET, array('maximumFlightTime' => 'float'));
    if(!$whereInQuery)
    {
        $whereInQuery = true;
        $query .= ' WHERE ';
    }
    else
    {
        $query .= ' AND ';
    }
    $query .= 'CAST(flighttime AS DECIMAL(4,2)) <= :maximumFlightTime';
    $parameters[':maximumFlightTime'] = $_GET['maximumFlightTime'];
}
if($_GET['minimumDistance'] !== null)
{
    assertData($_GET, array('minimumDistance' => 'float'));
    if(!$whereInQuery)
    {
        $whereInQuery = true;
        $query .= ' WHERE ';
    }
    else
    {
        $query .= ' AND ';
    }
    $query .= 'distance >= :minimumDistance';
    $parameters[':minimumDistance'] = $_GET['minimumDistance'];
}
if($_GET['maximumDistance'] !== null)
{
    assertData($_GET, array('maximumDistance' => 'float'));
    if(!$whereInQuery)
    {
        $whereInQuery = true;
        $query .= ' WHERE ';
    }
    else
    {
        $query .= ' AND ';
    }
    $query .= 'distance <= :maximumDistance';
    $parameters[':maximumDistance'] = $_GET['maximumDistance'];
}

if(!$whereInQuery)
{
    $query .= ' WHERE enabled=1';
}
else
{
    $query .= ' AND enabled=1';
}

$query .= ' ORDER BY id DESC LIMIT 100';
$results = $database->fetch($query, $parameters);

foreach($results as $index=>$result)
{
    // Correct days of week to actual days
    $daysOfWeek = array();
    foreach(str_split($result['daysOfWeek']) as $day)
    {
        switch ($day) {
            case '0':
                array_push($daysOfWeek, 'Sunday');
                break;
            case '1':
                array_push($daysOfWeek, 'Monday');
                break;
            case '2':
                array_push($daysOfWeek, 'Tuesday');
                break;
            case '3':
                array_push($daysOfWeek, 'Wednesday');
                break;
            case '4':
                array_push($daysOfWeek, 'Thursday');
                break;
            case '5':
                array_push($daysOfWeek, 'Friday');
                break;
            case '6':
                array_push($daysOfWeek, 'Saturday');
                break;    
        }
    }
    $results[$index]['daysOfWeek'] = $daysOfWeek;
    // Correct route to be array
    if($result['route'] === '')
    {
        unset($results[$index]['route']);
    }
    else
    {
        $results[$index]['route'] = explode(' ', $result['route']);
    }
    // Departure time
    $departureTime = explode(':', $result['departureTime']);
    $results[$index]['departureTime'] = $departureTime[0] . ':' . $departureTime[1];
    // Arrival time
    $arrivalTime = explode(':', $result['arrivalTime']);
    $results[$index]['arrivalTime'] = $arrivalTime[0] . ':' . $arrivalTime[1];
    // Flight Level
    $results[$index]['flightLevel'] = intval($result['flightLevel']);
    // Flight Time
    $results[$index]['flightTime'] = floatval($result['flightTime']);
    // Aircraft
    $results[$index]['aircraft'] = intval($result['aircraft']);
}
echo(json_encode($results));
?>