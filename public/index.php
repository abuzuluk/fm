<?php
//GPL3

//MP3 STREAMING FROM PHP
//This script streams a big MP3 file at an appropiate speed
//Also, it will synchronize several listeners to the same position in the mp3

//Quick usage: Edit the 3 variables below in the 'settings' section, host this script together with a big MP3 on a lamp server.

//This script was optimized for Second Life music streaming, issues dodged:
//* The mp3 is actively streamed, instead of sent as a whole file at once
//* Listeners are synchronized, so they actually will hear the same song whilst being on the same parcel
//* MP3 is nicely (instead of arbitrary) split in chunks to dodge 'skipping' errors that SL viewers tend to behave
//* Insignificant server load(** up to a reasonable amount of viewers, say, <20)
//* Tested for over a year for proper functioning without raising issues

//Limitations
//Requires an MPEG1 LAYER III file as input (the most common MP3 format)
//Assumes a single big file (podcast). This script was not made to stream a playlist.
//The file should be properly encoded to avoid issues with the SL player:
//* VBR _might_ work but is not recommended due to the viewers' real-time broadcast skipping
//* If the encoder supports 'bit buckets' - make sure they are disabled as they are incompatible with SL's player(**)
//(**) When streaming 'live' - SL player seems to understand if the whole file was downloaded instead of streamed.
//The resolution of the synchronisation is not precise, though generally <1 second. As listeners join, slight offsets might accumulate
//This script is ment as example how to stream using proper timekeeping and chunking.
//It is missing features as streaming multiple files from a directory, though such behaviour is easily customized.

//Feature, not bug
//With zero listeners, the stream will not progress, instead it'll 'wait' at current position untill at least 1 listener joins

//Issues:
//If the mp3 exists but has zero length, the script may end in an endless loop eating 100% cpu time. please fix.

//Requirements:
//LAP(Linux, Apache, PHP) configuration
//(WAP(Windows) may work but untested, potentional issues revolve around microtime(), fastcgi and locked temporary files).
//PHP configuration that allows overriding script run time to infinite
//Writeable TMP directory
//Properly created MP3 stream (podcast), preferably several hours in length:
// * Simply concatting files may work for testing but is not recommended and may lead to audible glitches
// * The prefered way is to either: stream your playlist with an audio player like winamp, then output the stream as .mp3
// * Or use an editor (like audacity) to create your podcast, output as single file
// * Any other means or software to create a podcast
//The intention of this script is to generate a glitch-free audio experience, using no more bandwidth than required

//CHANGE HISTORY
//0.01 - initial version
//0.02 - removed dependancy from external mp3 header library. please set bitrate manually. auto-detect wasn't reliable anyways
//0.03 - documented some more and cleaned up code a tiny bit. might release it as GPL


//settings, please fill this variables with your own
if (microtime(true) % 2) {
    $mp3 = getenv('HEROKU_APP_DIR') . './public/mp3/128.mp3';
    $bitrate = 128;
} else {
    $mp3 = getenv('HEROKU_APP_DIR') . './public/mp3/320.mp3';
    $bitrate = 320;
}

error_log(print_r(compact('mp3', 'bitrate'), true));

//adjust for your system. use a unique lock file for each mp3 you serve.
$tmp = getenv('HEROKU_APP_DIR') . "./public/${mp3}.txt";

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
