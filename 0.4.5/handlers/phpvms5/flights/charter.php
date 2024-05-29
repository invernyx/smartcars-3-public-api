<?php
// CHARTER FLIGHT PRICE COMPONENTS

// PRICE PER PASSENGER
// This is the price per passenger for a passenger charter flight.
// Example: $10
$pricePerPassenger = 10;

// PRICE PER POUND
// This is the price per pound for a cargo charter flight.
// Example: $0.10
$pricePerPound = 0.10;

// WARNING: DO NOT edit any code beyond this point unless you are technically able.


if($_SERVER['REQUEST_METHOD'] !== 'POST')
{
    error(405, 'POST request method expected, received a ' . $_SERVER['REQUEST_METHOD'] . ' request instead.');
    exit;
}

// TODO: Rank/aircraft restriction applied here
assertData($_POST, array(
    'number' => 'string',
    'departure' => 'airport',
    'arrival' => 'airport',
    'route' => 'array',
    'aircraft' => 'int',
    'cruise' => 'int',
    'type' => 'string',
    'departureTime' => 'string',
    'arrivalTime' => 'string'
));

switch($_POST['type']) {
    case 'P':
    case 'C':
        break;
    default:
        error(400, 'Invalid type for type (expected `C` or `P` [Raw Type: `string`])');
        exit;
}

function coordinatesToNM($latitudeFrom, $longitudeFrom, $latitudeTo, $longitudeTo, $earthRadius = 3443.92)
{
    // convert from degrees to radians
    $latFrom = deg2rad($latitudeFrom);
    $lonFrom = deg2rad($longitudeFrom);
    $latTo = deg2rad($latitudeTo);
    $lonTo = deg2rad($longitudeTo);
  
    $latDelta = $latTo - $latFrom;
    $lonDelta = $lonTo - $lonFrom;
  
    $angle = 2 * asin(sqrt(pow(sin($latDelta / 2), 2) + cos($latFrom) * cos($latTo) * pow(sin($lonDelta / 2), 2)));
    return $angle * $earthRadius;
}

$code = substr($_POST['number'], 0, 3);
$airlines = $database->fetch('SELECT code FROM ' . dbPrefix . 'airlines WHERE code=?', array($code));
if($airlines === array())
{
    $database->execute('INSERT INTO ' . dbPrefix . 'airlines (code, name, enabled) VALUES (?, "Charter", 0)', array($code));
}

$departure = $database->fetch('SELECT lat, lng FROM ' . dbPrefix . 'airports WHERE icao=?', array($_POST['departure']));
$arrival = $database->fetch('SELECT lat, lng FROM ' . dbPrefix . 'airports WHERE icao=?', array($_POST['arrival']));
if($departure === array() || $arrival === array())
{
    error(400, 'Departure or arrival airport not found.');
    exit;
}
$departure = $departure[0];
$arrival = $arrival[0];

$distance = coordinatesToNM($departure['lat'], $departure['lng'], $arrival['lat'], $arrival['lng']);
$price;
switch($_POST['type']) {
    case 'P':
        $price = $pricePerPassenger;
        break;
    case 'C':
        $price = $pricePerPound;
        break;
}

$database->execute('INSERT INTO ' . dbPrefix . 'schedules
(code,
flightnum,
depicao,
arricao,
route,
route_details,
aircraft,
flightlevel,
distance,
deptime,
arrtime,
flighttime,
price,
flighttype,
timesflown,
notes,
enabled) VALUES
(:code, :number, :dep, :arr, :route, "", :aircraft, :level, :distance, :deptime, :arrtime, :flighttime, :price, :type, 0, "smartCARS Charter Flight", 0)',
array(
    'code' => $code,
    'number' => substr($_POST['number'], 3),
    'dep' => $_POST['departure'],
    'arr' => $_POST['arrival'],
    'route' => implode(' ', $_POST['route']),
    'aircraft' => $_POST['aircraft'],
    'level' => $_POST['cruise'],
    'distance' => $distance,
    'price' => $price,
    'type' => $_POST['type'],
    'deptime' => $_POST['departureTime'],
    'arrtime' => $_POST['arrivalTime'],
    'flighttime' => abs(strtotime($_POST['arrivalTime']) - strtotime($_POST['departureTime'])) / 3600
));

$database->execute('INSERT INTO ' . dbPrefix . 'bids (pilotid, routeid, dateadded) VALUES (?, ?, NOW())', array($pilotID, $database->getLastInsertID('id')));
echo(json_encode(array('bidID'=>intval($database->getLastInsertID('bidid')))));
?>