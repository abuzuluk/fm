# MP3 STREAMING FROM PHP

This script streams a big MP3 file at an appropiate speed.
Also, it will synchronize several listeners to the same position in the mp3.

>`Quick usage`: Edit the 3 variables below in the settings section, host this script together with a big MP3 on a lamp server.

## Features
This script was optimized for Second Life music streaming, issues dodged:
- The mp3 is actively streamed, instead of sent as a whole file at once
- Listeners are synchronized, so they actually will hear the same song whilst being on the same parcel
- MP3 is nicely (instead of arbitrary) split in chunks to dodge 'skipping' errors that SL viewers tend to behave
- Insignificant server load (up to a reasonable amount of viewers, say, <20)
- Tested for over a year for proper functioning without raising issues

## Limitations
- Requires an MPEG1 LAYER III file as input (the most common MP3 format)
- Assumes a single big file (podcast). This script was not made to stream a playlist.
- The resolution of the synchronisation is not precise, though generally <1 second. As listeners join, slight offsets might accumulate
- This script is ment as example how to stream using proper timekeeping and chunking.
- It is missing features as streaming multiple files from a directory, though such behaviour is easily customized.

The file should be properly encoded to avoid issues with the SL player:

- VBR _might_ work but is not recommended due to the viewers' real-time broadcast skipping
- If the encoder supports 'bit buckets' - make sure they are disabled as they are incompatible with SL's player
- When streaming 'live' - SL player seems to understand if the whole file was downloaded instead of streamed.
- `Feature, not bug` With zero listeners, the stream will not progress, instead it'll 'wait' at current position untill at least 1 listener joins.  

## Issues
- If the mp3 exists but has zero length, the script may end in an endless loop eating 100% cpu time. please fix.

## Requirements:
- LAP(Linux, Apache, PHP) configuration
- (WAP(Windows) may work but untested, potentional issues revolve around microtime(), fastcgi and locked temporary files).
- PHP configuration that allows overriding script run time to infinite
- Writeable TMP directory
- Properly created MP3 stream (podcast), preferably several hours in length.

Simply concatting files may work for testing but is not recommended and may lead to audible glitches. The prefered way is to either: stream your playlist with an audio player like winamp, then output the stream as .mp3, or use an editor (like audacity) to create your podcast, output as single file. Any other means or software to create a podcast.
The intention of this script is to generate a glitch-free audio experience, using no more bandwidth than required

## CHANGE HISTORY
- 0.01 - initial version
- 0.02 - removed dependancy from external mp3 header library. please set bitrate manually. auto-detect wasn't reliable anyways
- 0.03 - documented some more and cleaned up code a tiny bit. might release it as GPL
