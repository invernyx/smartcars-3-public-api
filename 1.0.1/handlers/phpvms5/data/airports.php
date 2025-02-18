<?php
echo(json_encode($database->fetch('SELECT id,icao as code,name,lat as latitude,lng as longitude FROM ' . dbPrefix . 'airports')));
?>