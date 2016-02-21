<?php

//settings, please fill this variables with your own
if (microtime(true) % 2) {
    $mp3 = __DIR__ . '/mp3/128.mp3';
    $bitrate = 128;
} else {
    $mp3 = __DIR__ . '/mp3/320.mp3';
    $bitrate = 320;
}

error_log(print_r(compact('mp3', 'bitrate'), true));

//adjust for your system. use a unique lock file for each mp3 you serve.
$tmp = "${mp3}.txt";

//Enable debug to get textual output in a browser. The stream is not listenable with debug enabled.
$debug = getenv('DEBUG');

//disable error reporting if you like
error_reporting(E_ALL);

error_log(print_r(compact('tmp', 'debug'), true));
// NO EDITING REQUIRED BELOW THIS LINE, EDIT AT OWN RESPONSABILITY //

//SUPPORT FUNCTIONS
function writePos()
{
    global $h, $timekeeper, $tmp;

    $fff = fopen($tmp, "w");
    fputs($fff, "" . ftell($h) . "\n");
    fputs($fff, $timekeeper . "\n");
    fclose($fff);
}

//global $buffcache;

function sendBuff($bff)
{
    print $bff;
    flush();

    return;
}

function readBuffs($fh)
{
    $b = "";
    for ($i = 0; $i < 22; $i++) {
        $b .= readBuff($fh);
    }

    return $b;
}

function readBuff($fh)
{
    global $framesize;

    //read from file untill we receive a $FF marker
    $m = "";
    do {
        $m = fread($fh, 4);
        if (feof($fh))
            return "";
        //requiring mpeg1 layer 3. Adjust for other containers / chunkmarkers / right here
    } while (
        ($m[0] != chr(255)) || !(($m[1] == chr(250)) || ($m[1] == chr(251)))
    );

    $bl = $framesize - 4;
    if (ord($m[2]) & 2) //padding
        $bl++;

    $m .= fread($fh, $bl);

    return $m;
}


//`main()`

$file = $mp3;
if (!file_exists($file))
    die();

$framesize = floor(144000.0 * $bitrate / 44100);
$blocksize = 16384;
$playspeed = 1000.0 * $bitrate / 8.0;
$sleeptime = (1.0 * $blocksize / ($playspeed));

if ($debug)
    header("Content-Type: text/plain");
else {
    header("Content-Type: audio/mpeg");
    header("Content-Transfer-Encoding: binary");
    header("Pragma: no-cache");
    header("icy-br: " . $bitrate);
}

//request unlimited runtime
set_time_limit(0);
if ($debug)
    print ("framesize: " . $framesize);

$h = fopen($file, "rb");
error_log(print_r(compact('h'), true));
if (file_exists($tmp)) {
    //read our position from file.
    $ff       = fopen($tmp, "r");
    $position = fgets($ff);
    $postime  = fgets($ff);
    fclose($ff);
}

//increase position by elapsed time for syncing listeners.
if ($postime) {
    $elapsed = microtime(true) - $postime;
    //we force to float to bypass a 32 bit integer limit
    //looping for multiple days if needed (its not an issue)
    $position += 1.0 * $elapsed * ($bitrate / 8);
    $fs = filesize($file);
    while ($position > $fs) {
        $position -= $fs;
    }
}
fseek($h, $position);

//send a reasonable buffer to start
for ($j = 0; $j < 3; $j++) {
    $buff = readBuffs($h) . readBuffs($h) . readBuffs($h);
    print $buff;
}

$buff       = readBuffs($h);
$timekeeper = microtime(true);
$counter    = 0;

//main loop:
while (($buff != "") && !connection_aborted()) {

    if ($debug) {
        echo "Buff size: " . strlen($buff)
            . " first chars: " . ord($buff[0])
            . " " . ord($buff[1])
            . " " . ord($buff[2])
            . " " . ord($buff[3])
            . " file pointer " . ftell($h) . "\n";
    } else
        echo($buff);

    flush();

    $counter++;
    if (!($counter % 30))
        writePos();

    $bwl  = strlen($buff);
    $buff = readBuffs($h);
    if ($buff == "") {
        fseek($h, 0);
        $buff = readBuff($h);
    }

    $timekeeper = $timekeeper + 1.0 * $bwl / $playspeed;
    time_sleep_until($timekeeper);
}

writePos();
fclose($h);
