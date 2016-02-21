<?php

require __DIR__ . '/../vendor/autoload.php';

error_reporting(-1);
set_time_limit(0);

if (microtime(true) % 2) {
    $song = new \Fm\Song(['bitrate' => 128, 'file' => __DIR__ . '/../mp3/128.mp3']);
} else {
    $song = new \Fm\Song(['bitrate' => 320, 'file' => __DIR__ . '/../mp3/320.mp3']);
}

$player = new \Fm\Player();
$player->play($song, getenv('DEBUG'));
