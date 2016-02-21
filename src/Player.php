<?php

namespace Fm;

class Player
{
    public function debugLoop($stream, $buffer)
    {
        printf("\nBuffer size: %s", strlen($buffer));
        printf("\nFile pointer: %s", ftell($stream));
        vprintf("\nFirst 4 chars: %s %s %s %s", array_map('ord', str_split($buffer)));
    }

    /**
     * @param resource $stream     File handle of mp3 stream
     * @param float    $timeKeeper Timestamp of mp3 stream start time
     * @param string   $syncFile   Path to sync listeners file
     */
    public function rememberCursor($stream, $timeKeeper, $syncFile)
    {
        $fh = fopen($syncFile, 'w');
        fputs($fh, ftell($stream) . PHP_EOL);
        fputs($fh, $timeKeeper . PHP_EOL);
        fclose($fh);
    }

    /**
     * @param string $streamPath Path to  mp3 stream
     * @param int    $bitRate    Bit rate of mp3 stream
     * @param string $syncFile   Path to sync listeners file
     *
     * @return float
     */
    public function loadCursor($streamPath, $bitRate, $syncFile)
    {
        $offset = 0;

        if (file_exists($syncFile)) {
            $fh         = fopen($syncFile, 'r');
            $offset     = floatval(fgets($fh));
            $timeKeeper = floatval(fgets($fh));
            fclose($fh);

            //increase position by elapsed time for syncing listeners.
            //looping for multiple days if needed (its not an issue)
            if ($timeKeeper) {
                $elapsed = microtime(true) - $timeKeeper;
                $offset += $elapsed * $bitRate / 8;
                $offset = fmod($offset, filesize($streamPath));
            }
        }

        return $offset;
    }

    /**
     * @param resource $stream File handle of mp3 stream
     * @param int      $frameSize
     *
     * @return string
     */
    public function readBuffer8kb($stream, $frameSize)
    {
        $buffer = '';
        for ($i = 0; $i < 20; $i++) {
            $buffer .= $this->readBufferFrameSize($stream, $frameSize);
        }

        return $buffer;
    }

    /**
     * @param resource $stream File handle of mp3 stream
     * @param int      $frameSize
     *
     * @return string
     */
    public function readBufferFrameSize($stream, $frameSize)
    {
        //read from file until we receive a $FF marker
        do {
            $marker = fread($stream, 4);
            if (feof($stream)) {
                return '';
            }

            //requiring mpeg1 layer 3.
            //Adjust for other containers / chunk markers / right here
            $marker0 = ord($marker[0]);
            $marker1 = ord($marker[1]);

        } while (255 !== $marker0 || 250 !== $marker1 && 251 !== $marker1);

        $padding = ord($marker[2]) & 2 ? 3 : 4;
        $buffer  = $marker . fread($stream, $frameSize - $padding);

        return $buffer;
    }

    public function play(Song $song, $debug = false)
    {
        $songFile  = $song->file()->getRealPath();
        $syncFile  = $song->syncFilePath();
        $bitRate   = $song->bitrate();
        $frameSize = $song->frameSize();
        $playSpeed = $song->playSpeed();

        if ($debug) {
            header('Content-Type: text/plain');
            print_r(compact('frameSize', 'bitRate', 'playSpeed', 'syncFile'));
        } else {
            header('Content-Type: audio/mpeg');
            header('Content-Transfer-Encoding: binary');
            header('Pragma: no-cache');
            header('icy-br: ' . $bitRate);
        }

        $stream = fopen($songFile, 'rb');
        fseek($stream, $this->loadCursor($songFile, $bitRate, $syncFile));

        //send a reasonable buffer to start
        for ($i = 0; $i < 10; $i++) {
            print($this->readBuffer8kb($stream, $frameSize));
        }

        //main loop:
        $buffer     = $this->readBuffer8kb($stream, $frameSize);
        $counter    = 0;
        $timeKeeper = microtime(true);

        while (!empty($buffer) && !connection_aborted()) {
            if ($debug) {
                $this->debugLoop($stream, $buffer);
            } else {
                print($buffer);
            }

            flush();

            // sync listeners
            if (0 === ++$counter % 30) {
                $this->rememberCursor($stream, $timeKeeper, $syncFile);
            }

            $buffer = $this->readBuffer8kb($stream, $frameSize);
            if (empty($buffer)) {
                fseek($stream, 0);
                $buffer = $this->readBuffer8kb($stream, $frameSize);
            }

            $timeKeeper += strlen($buffer) / $playSpeed;
            time_sleep_until($timeKeeper);
        }

        $this->rememberCursor($stream, $timeKeeper, $syncFile);
        fclose($stream);
    }
}