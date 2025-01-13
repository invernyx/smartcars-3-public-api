<?php
$database->createTable('smartCARS3_BidAircraft', 'bidID int(11) NOT NULL, aircraftID int(11) NOT NULL, PRIMARY KEY(bidID)');

$schedules = $database->fetch(
'SELECT bids.bidid as bidID, schedules.code, schedules.flightnum AS number, schedules.flighttype AS type, schedules.depicao AS departureAirport, schedules.arricao AS arrivalAirport, schedules.route, schedules.aircraft,schedules.flightlevel AS flightLevel,schedules.deptime AS departureTime,schedules.arrtime AS arrivalTime,CAST(schedules.flighttime AS DECIMAL(4,2)) AS flightTime, schedules.distance, schedules.daysofweek AS daysOfWeek, schedules.notes FROM ' . dbPrefix . 'bids AS bids
INNER JOIN ' . dbPrefix . 'schedules AS schedules ON schedules.id = bids.routeid
WHERE pilotid = ? ORDER BY bids.dateadded ASC, bids.bidid ASC', array($pilotID));

foreach($schedules as $idx=>$schedule)
{
    // Correct days of week to actual days
    $daysOfWeek = array();
    foreach(str_split($schedules[$idx]['daysOfWeek']) as $day)
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
    $schedules[$idx]['daysOfWeek'] = $daysOfWeek;
    // Correct route to be array
    if($schedules[$idx]['route'] === '')
    {
        unset($schedules[$idx]['route']);
    }
    else
    {
        $schedules[$idx]['route'] = explode(' ', $schedules[$idx]['route']);
    }
    // Departure time
    $departureTime = explode(':', $schedules[$idx]['departureTime']);
    $schedules[$idx]['departureTime'] = $departureTime[0] . ':' . $departureTime[1];
    // Arrival time
    $arrivalTime = explode(':', $schedules[$idx]['arrivalTime']);
    $schedules[$idx]['arrivalTime'] = $arrivalTime[0] . ':' . $arrivalTime[1];
    // Flight Level
    $schedules[$idx]['flightLevel'] = intval($schedules[$idx]['flightLevel']);
    // Flight Time
    $duration = $schedules[$idx]['flightTime'];
    $hours = floor($duration);
    $minutes = ($duration - $hours) * (5 / 3);
    $schedules[$idx]['flightTime'] = floatval($hours + $minutes);
    // Aircraft

    $bidAircraft = $database->fetch('SELECT aircraftID FROM smartCARS3_BidAircraft WHERE bidID = ?', array($schedules[$idx]['bidID']));
    if(count($bidAircraft) > 0)
        $schedules[$idx]['aircraft'] = intval($bidAircraft[0]['aircraftID']);
    else
        $schedules[$idx]['aircraft'] = intval($schedules[$idx]['aircraft']);
}

echo(json_encode($schedules));
?>