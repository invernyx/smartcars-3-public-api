<?php
$aircrafts = $database->fetch('SELECT id,icao as code,fullname as name,cruise as serviceCeiling,registration,maxpax as maximumPassengers,maxcargo as maximumCargo,minrank as minimumRank FROM ' . dbPrefix . 'aircraft WHERE enabled = 1');
$ranks = $database->fetch('SELECT rankid, rank FROM ' . dbPrefix . 'ranks');
$i = 0;
foreach($aircrafts as $aircraft)
{
    switch($aircraft['minimumRank'])
    {
        case 0:
            $aircrafts[$i]['minimumRank'] = null;
            break;
        default:
            foreach($ranks as $rank)
            {
                if($rank['rankid'] == $aircraft['minimumRank'])
                {
                    $aircrafts[$i]['minimumRank'] = $rank['rank'];
                    break;
                }
            }
            break;
    }
    $i++;
}
echo(json_encode($aircrafts));
?>