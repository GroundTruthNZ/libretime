<?php
/* Curl-less, Streamripper-less, ffmpeg & mplayer-less, low memory use,
simple native php CLI script which can be called from a cron job.
It avoids the re-encoding stuff that many solutions use, so loss of fidelity
and less resource load on the server. It also tries to recover from stream
 breaks. Questions/queries to John Chewter info@deprogrammedradio.com

Syntax in bash (cron) is: php /your/path/ripstream.php yourfilename minutes

 */

//$streamurl = 'http://x-pedia.org:8000/deprogrammedradio.mp3';
//$savepath = dirname(FILE).'/downloads';

$got_commands = true;

if (isset($argv[0])) {
    $streamurl = $argv[1];
} else {
    $got_commands = false;
    echo "Error - No stream specified.\n";
}

if (isset($argv[1])) {
    $filename = $argv[2];
} else {
    $got_commands = false;
    echo "Error - No file name specified.\n";
}

if (isset($argv[2])) {
    $seconds = $argv[3];
} else {
    $got_commands = false;
    echo "Error - No seconds specified.\n";
}

if (isset($argv[4])) {
    echo "Oops! Too many arguements. Have you spaces in your filename?\n";
    $got_commands = false;
}

if ($got_commands == false) {
    echo "Syntax is: php /your/path/recorder.php http://x.y.z:8000/yoursteam.mp3 yourfilename seconds\n";
    echo "NB. No spaces in filename are permitted.\n";
    exit(1);
}

function downloadStream($streamurl, $filename, $seconds)
{
    $retry = 0;
    $gotFile = false;
    if ($seconds > 6) {$seconds -= 6;}
    $end_time = microtime(true) + $seconds;
    $blocksize = 1024 * 8;
    $current_time = microtime(true);
    while ((microtime(true) < $end_time) && ($retry < 10)) {
        if ($streamhandle = fopen($streamurl, "rb")) {
            if ($streamhandle) {
                $newf = fopen($filename, "wb");
                if ($newf) {
                    while ($current_time < $end_time) {
                        $current_time = microtime(true);
                        fwrite($newf, fread($streamhandle, $blocksize), $blocksize);
                    }
                }
            }
            if ($newf) {
                fclose($newf);
                $gotFile = true;
            }
            $retry=100;
            break;
        } else {
            $retry=$retry+1;
            sleep(1);
        }
        if ($streamhandle) {
            fclose($streamhandle);
        }
    }
    if ($streamhandle) {
        fclose($streamhandle);
    }
    return $gotFile;
}

if (downloadStream($streamurl, $filename, $seconds)) {
    echo "Download OK\n";
    exit(0);
} else {
    echo "Download Failed\n";
    exit(1);
}
