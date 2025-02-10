<?php
$news = $database->fetch('SELECT subject as title, body, postdate as postedAt, postedby as postedBy FROM ' . dbPrefix . 'news ORDER BY postdate DESC LIMIT 1');
if($news !== array()) {
    $news = $news[0];
    echo(json_encode($news));
}
else {
    echo('{}');
}
?>