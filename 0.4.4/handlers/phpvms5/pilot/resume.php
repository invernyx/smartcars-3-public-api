<?php
$database->createTable('smartCARS3_Sessions', 'pilotID int(11) NOT NULL, sessionID varchar(256) NOT NULL, expiry int(11) NOT NULL, PRIMARY KEY(pilotID, sessionID)');
$database->execute('DELETE FROM smartCARS3_Sessions WHERE expiry < ?', array(time()));

function getURL() {
    return sprintf(
        "%s://%s",
        isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off' ? 'https' : 'http',
        $_SERVER['SERVER_NAME']
    );
}

if($_SERVER['REQUEST_METHOD'] !== 'POST')
{
    error(405, 'POST request method expected, received a ' . $_SERVER['REQUEST_METHOD'] . ' request instead.');
}
if($_POST['session'] === null)
{
    error(400, 'Session is a required field (type `string`)');
}
assertData($_POST, array('session' => 'string'));

$session = explode('.', $_POST['session']);
if(count($session) !== 3)
{
    error(400, 'The session provided was not in valid JWT format');
}
$session[0] = json_decode(base64_decode(str_replace(array('-', '_', ''), array('+', '/', '='), $session[0])), true);
$session[1] = json_decode(base64_decode(str_replace(array('-', '_', ''), array('+', '/', '='), $session[1])), true);
if($session[0] === null || $session[1] === null)
{
    error(400, 'The session provided was not in valid JWT format');
}
if($session[0]['alg'] !== 'HS256' || $session[0]['typ'] !== 'JWT')
{
    error(401, 'The session given was not signed by this website');
}
if($session[1]['sub'] === null || $session[1]['exp'] === null)
{
    error(401, 'The session given was not signed by this website');
}
$validSessions = $database->fetch('SELECT sessionID FROM smartCARS3_Sessions WHERE pilotID=? AND expiry=? AND sessionID=?', array($session[1]['sub'], $session[1]['exp'], $_POST['session']));
if(count($validSessions) === 0)
{
    error(401, 'The session given was not valid');
}
$result = $database->fetch('SELECT code, pilotid, firstname, lastname, email, rankid FROM ' . dbPrefix . 'pilots WHERE pilotid=?', array($session[1]['sub']));
if($result === array())
{
    error(500, 'The session was found, but there was no valid pilot. Please report this to the VA');
}
$result = $result[0];

$expiry = time() + 604800;
$JWTHeader = json_encode(array('typ' => 'JWT', 'alg' => 'HS256'));
$JWTPayload = json_encode(array('sub' => $result['pilotid'], 'exp' => $expiry));
$JWTHeader = str_replace(array('+', '/', '='), array('-', '_', ''), base64_encode($JWTHeader));
$JWTPayload = str_replace(array('+', '/', '='), array('-', '_', ''), base64_encode($JWTPayload));
$JWTSignature = hash_hmac('sha256', $JWTHeader . '.' . $JWTPayload, uniqid('', true), true);
$JWTSignature = str_replace(array('+', '/', '='), array('-', '_', ''), base64_encode($JWTSignature));
$jwt = $JWTHeader . '.' . $JWTPayload . '.' . $JWTSignature;

$database->insert('smartCARS3_Sessions', array('pilotID' => $result['pilotid'], 'sessionID' => $jwt, 'expiry' => $expiry));

$dbid = intval($result['pilotid']);
$pilotid = $result['code'];
$pilotnum = (string)($dbid + intval(pilotOffset));
while(strlen($pilotnum) < pilotIDLength)
{
    $pilotnum = '0' . $pilotnum;
}
$pilotid .= $pilotnum;

$rank = $database->fetch('SELECT rank as name, rankimage FROM ' . dbPrefix . 'ranks WHERE rankid=?', array($result['rankid']));
if($rank === array())
{
    error(500, 'The rank for this pilot does not exist');
}
$rank = $rank[0];

$rankImage = null;
if(strpos($rank['rankimage'], '/') === 0)
{
    if(file_exists(webRoot . $rank['rankimage']))
    {
        $rankImage = getURL() . $rank['rankimage'];
    }
}
else if ($rank['rankimage'] !== '') {
    $rankImage = $rank['rankimage'];
}

$avatar = null;
$avatarFile = '/lib/avatars/' . $pilotid . '.png';
if(file_exists(webRoot . $avatarFile))
{
    $avatar = getURL() . $avatarFile;
}

echo(json_encode(array(
    'dbID' => $dbid,    
    'pilotID' => $pilotid,
    'firstName' => $result['firstname'],
    'lastName' => $result['lastname'],
    'email' => $result['email'],
    'rank' => $rank['name'],
    'rankImage' => $rankImage,
    'rankLevel' => intval($result['ranklevel']),
    'avatar' => $avatar,
    'session' => $jwt,
    'expiry' => $expiry,
)));
?>