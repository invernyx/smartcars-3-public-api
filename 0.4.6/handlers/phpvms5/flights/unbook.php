<?php
if($_SERVER['REQUEST_METHOD'] !== 'POST')
{
    error(405, 'POST request method expected, received a ' . $_SERVER['REQUEST_METHOD'] . ' request instead.');
    exit;
}
assertData($_POST, array('bidID'=>'int'));

$bid = $database->fetch('SELECT routeid FROM ' . dbPrefix . 'bids WHERE pilotid=? AND bidid=?', array($pilotID, $_POST['bidID']));
if($bid !== array())
{
    $flight = $database->fetch('SELECT id FROM ' . dbPrefix . 'schedules WHERE id=? AND enabled=0 AND notes="smartCARS Charter Flight"');
    if($flight !== array())
    {
        $database->execute('DELETE FROM ' . dbPrefix . 'schedules WHERE id=?', array($flight['id']));
    }
    $database->execute('DELETE FROM ' . dbPrefix . 'bids WHERE pilotid=? AND bidid=?', array($pilotID, $_POST['bidID']));
}
else
{
    error(404, 'No bids with the given ID were found');
    exit;
}
?>