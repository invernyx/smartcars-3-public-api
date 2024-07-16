<?php
// smartCARS 0.4.7 API
// This file must be processable by both PHP 5 and PHP 7

header('Content-type: application/json');
if(!function_exists("http_response_code"))
{
    function http_response_code($code)
    {
        header('X-PHP-Response-Code: ' . $code, true, $code);
    }
}

// Modify both of these to `1` to enable debugging
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);

header('Access-Control-Allow-Methods: GET, POST, OPTIONS, HEAD');  
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With"); 
header('Access-Control-Allow-Origin: *');

if($_SERVER['REQUEST_METHOD'] === 'OPTIONS' || $_SERVER['REQUEST_METHOD'] === 'HEAD')
    exit;

$urlNoQuery = explode('?', $_SERVER['REQUEST_URI']);
$requestURL = explode('/', $urlNoQuery[0]);
if($requestURL[0] === '')
    array_splice($requestURL, 0, 1);

$foundAPI = false;
for($i = 0; $i < count($requestURL);)
{
    $req = trim(strtolower($requestURL[$i]));
    if($foundAPI === false || $req == '' || $req == null)
        array_splice($requestURL, $i, 1);
    else
        $i++;
    if($req == 'api.php' || $req == 'api')
        $foundAPI = true;
}

function error($httpCode, $message, $exit = true)
{
    http_response_code($httpCode);
    echo(json_encode(array('message' => $message)));
    if($exit)
        exit;
}

function assertData($source, $data)
{
    $invalidData = array();
    foreach($data as $name => $type)
    {
        $valid = false;
        if(isset($source[$name]))
        {
            switch(strtolower($type))
            {
                case 'integer':
                case 'int':
                    if(is_int($source[$name]))
                        $valid = true;
                    if(is_numeric($source[$name]))
                        $valid = true;
                    break;
                case 'float':
                    if(is_float($source[$name]))
                        $valid = true;
                    if(is_numeric($source[$name]))
                        $valid = true;
                    break;
                case 'latitude':
                    if(is_numeric($source[$name]) && $source[$name] >= -90 && $source[$name] <= 90)
                        $valid = true;
                    break;
                case 'longitude':
                    if(is_numeric($source[$name]) && $source[$name] >= -180 && $source[$name] <= 180)
                        $valid = true;
                    break;
                case 'heading':
                    if(is_numeric($source[$name]) && $source[$name] >= 0 && $source[$name] <= 360)
                        $valid = true;
                    break;
                case 'date':
                    if(strtotime($source[$name]) !== false)
                        $valid = true;
                    break;
                case 'string':
                    if(is_string($source[$name]))
                        $valid = true;
                    break;
                case 'email':
                    if(filter_var($source[$name], FILTER_VALIDATE_EMAIL))
                        $valid = true;
                    break;
                case 'flight':
                case 'flightNumber':
                    if(is_string($source[$name]) && preg_match('/[A-Z]{3}[A-Z0-9]{1,}/mi', $source[$name]))
                        $valid = true;
                    break;
                case 'airport':
                    if(is_string($source[$name]) && preg_match('/[A-Z0-9]{3,4}/mi', $source[$name]))
                        $valid = true;
                    break;
                case 'airline':
                    if(is_string($source[$name]) && preg_match('/[A-Z]{3}/mi', $source[$name]))
                        $valid = true;
                    break;
                case 'route':
                case 'routePoint':
                case 'waypoint':
                    if(is_string($source[$name]) && preg_match('/((0?[1-9]|[1-2]\\d|3[0-6])[LCR]?)|([A-Z]{5})|([A-Z]{3})|([A-Z]{1-3})/mi', $source[$name]))
                        $valid = true;
                    break;
                case 'phase':
                    if(is_string($source[$name]) && preg_match('/boarding|push_back|taxi|take_off|rejected_take_off|climb_out|climb|cruise|descent|approach|final|landed|go_around|taxi_to_gate|deboarding|diverted/mi', $source[$name]))
                        $valid = true;
                    break;
                case 'network':
                    if(is_string($source[$name]) && preg_match('/offline|vatsim|pilotedge|ivao|poscon/mi', $source[$name]))
                        $valid = true;
                    break;
                case 'status':
                    if(is_string($source[$name]) && preg_match('/accepted|pending|denied/mi', $source[$name]))
                        $valid = true;
                    break;
                case 'array':
                    if(is_array($source[$name]))
                        $valid = true;
                    break;
                default:
                    $valid = true;
                    break;
            }
        }
        if(!$valid)
            array_push($invalidData, $name . ' (expected `' . $type . '` [Raw Type: `' . gettype($source[$name]) . '`])');
    }

    if(count($invalidData) > 0)
    {
        $message = 'Invalid ';
        if(count($invalidData) > 1)
            $message .= 'types for ';
        else
            $message .= 'type for ';

        $firstItem = true;
        foreach($invalidData as $data)
        {
            if($firstItem)
            {
                $message .= $data;
                $firstItem = false;
            }
            else
                $message .= ', ' . $data;
        }
        error(400, $message);
    }
}

if(count($requestURL) > 0)
{
    $defaultVersion = '0.4.7';
    $apiVersion = $defaultVersion;
    if(isset($_GET['v']) && $_GET['v'] !== null)
    {
        $apiVersion = $_GET['v'];
    }
    if(!file_exists($apiVersion . '/handlers/' . $requestURL[0] . '/environment.php')) {
        $apiVersion = $defaultVersion;
    }
    require_once($apiVersion . '/handlers/' . $requestURL[0] . '/environment.php');

    if(count($requestURL) === 1)
    {
        echo(json_encode(array('apiVersion' => $apiVersion, 'handler' => $requestURL[0])));
        exit;
    }

    require_once($apiVersion . '/handlers/' . $requestURL[0] . '/assets/database.php'); // Database import
    try
    {
        // I really don't like how these are constants and not variables
        $database = new Database(dbName, dbHost, dbUsername, dbPassword);
    }
    catch (Exception $e)
    {
        error(500, 'Database credentials could not be loaded');
    }

    if($_SERVER['REQUEST_METHOD'] === 'POST')
    {
        try
        {
            $json = json_decode(file_get_contents('php://input'), true);
            if($json !== null)
                $_POST = $json;
        } catch (Exception $e) {}
    }

    $authenticate = true;
    if(strtolower($requestURL[1]) === 'pilot')
    {
        if(strtolower($requestURL[2]) === 'login' || strtolower($requestURL[2] === 'resume' || strtolower($requestURL[2]) === 'verify'))
        {
            $authenticate = false;
        }
    }
    if($authenticate)
    {
        $sessionID = explode('Bearer ', $_SERVER['HTTP_AUTHORIZATION']);
        $sessionID = $sessionID[1];
        
        $jwt = explode('.', $sessionID);
        $payload = json_decode(base64_decode(str_replace('_', '/', str_replace('-','+',$jwt[1]))), true);
        $pilotID = $payload['sub'];
        $sessions = $database->fetch('SELECT sessionID FROM smartCARS3_Sessions WHERE pilotID=? AND sessionID=?', array($pilotID, $sessionID));
        if($sessions === array())
        {
            error(401, 'The session provided is not valid');
            exit;
        }
    }

    $requiredFile = '';
    foreach($requestURL as $fileLocation)
    {
        if($requiredFile !== '')
            $requiredFile .= '/';
        $requiredFile .= $fileLocation;
    }
    if(file_exists($apiVersion . '/handlers/' . $requiredFile . '.php'))
        require_once($apiVersion . '/handlers/' . $requiredFile . '.php');
    else
        error(404, 'The handler provided could not be found');
}
?>