<?php

require __DIR__ . '/../vendor/autoload.php';

error_reporting(-1);
set_time_limit(0);

$tz = isset($_GET['tz']) ? $_GET['tz'] : 'UTC';
if (!in_array($tz, DateTimeZone::listIdentifiers(), true)) {
    $tz = 'UTC';
}

$now = new DateTime(null, new DateTimeZone($tz));
$schedule = \Fm\Schedule::fromYaml(__DIR__ . '/mp3/schedule.yaml');
$song = $schedule->search($now);

$player = new \Fm\Player();
$player->play($song, getenv('DEBUG'));
