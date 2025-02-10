<?php
$query = 'SELECT pirepid as id,
submitdate as submitDate,
code as airlineCode,
flightnum as number,
route,
distance,
flighttype as flightType,
depicao as departureAirport,
arricao as arrivalAirport,
aircraft,
CASE
    WHEN accepted=0 THEN "Pending"
    WHEN accepted=1 THEN "Accepted"
    WHEN accepted=2 THEN "Rejected"
END AS status,
flighttime as flightTime,
landingrate as landingRate,
fuelused as fuelUsed FROM ' . dbPrefix . 'pireps WHERE pilotid=:pilotid ORDER BY submitdate DESC LIMIT 1';
$parameters = array(':pilotid' => $pilotID);
$results = $database->fetch($query, $parameters);

foreach($results as $index=>$result)
{
    // Correct datetime to digit
    $flightTime = explode('.', $result['flightTime']);
    $flightTime = intval($flightTime[0]) + floatval(round($flightTime[1] / 60, 2));
    $results[$index]['flightTime'] = $flightTime;
    // Correct submission date format
    $results[$index]['submitDate'] = date(DATE_RFC3339, strtotime($result['submitDate']));

    if(is_numeric($result['aircraft'])) {
        $results[$index]['aircraft'] = intval($result['aircraft']);
    }
}
echo(json_encode($results));
?>