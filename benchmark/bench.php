<?php

set_time_limit(500);

require_once __DIR__ . '/../manual_loader.php';

use Nitotm\Eld\LanguageDetector;

// error_reporting(0);
// ini_set('display_errors', 0);

// opcache_reset(); // Avoid "interned string buffers overflow" testing multiple DB
// clearstatcache();

echo(PHP_SAPI === 'cli' ? '' : "<pre>" . PHP_EOL);

$help = <<<TXT
Options:
  database: small, medium, large, extralarge (& subsets)
  mode: array, string, bytes, disk  
URL:
  bench.php?database=small&mode=array
  bench.php?d=small&m=array
CLI:
  php bench.php -d small -m array
  php bench.php --database small --mode array

#### BENCHMARK ####


TXT;

echo (PHP_SAPI === 'cli' ? $help : htmlspecialchars($help));

if (in_array(PHP_SAPI, ['cli', 'phpdbg'], true)) {
    $arguments = getopt("d:m:", ["database:", "mode:"]) ?: [];
} else {
    $arguments = $_GET;
}
$database = $arguments['database'] ?? $arguments['d'] ?? 'small';
$databaseMode = $arguments['mode'] ?? $arguments['m'] ?? 'array';
// database: small, medium, large, extralarge (& subsets)
// databaseMode: array, string, bytes, disk

/*
$tatoeba50 = ['ar', 'az', 'be', 'bg', 'bn', 'ca', 'cs', 'da', 'de', 'el', 'en', 'es', 'et', 'eu', 'fa', 'fi', 'fr',
 'gu', 'he', 'hi', 'hr', 'hu', 'hy', 'is', 'it', 'ja', 'ka', 'ko', 'lt', 'lv', 'ms', 'nl', 'no', 'pl', 'pt', 'ro',
 'ru', 'sk', 'sl', 'sq', 'sv', 'ta', 'th', 'tl', 'tr', 'uk', 'ur', 'vi', 'yo', 'zh'];
*/
$loadStart = hrtime(true);
$eld = new LanguageDetector($database, null, $databaseMode);
// Now ELD is lazy load, so make sure the database is loaded by making the first detect
// $eld->langSubset(['de']);
// $eld->langSubset($tatoeba50);
$eld->detect('Hi, how are you');
$loadTime = (hrtime(true) - $loadStart) / 1e9; // nanoseconds to seconds
$loadedMemoryUsage = memory_get_usage();
$files = ['tatoeba-50.txt', 'eld-test.txt', 'sentences_v3.txt', 'word-pairs_v3.txt', 'single-words_v3.txt'];
$folder = __DIR__ . '/';
$times = [];
$average = [];
// Dev testing for isReliable() $reliable = ['correct'=>['true'=>0,'false'=>0], 'incorrect'=>['true'=>0,'false'=>0]];
// For use inside the loop: $reliable['correct'][$eld->detect($values[1])->isReliable() ? 'true' : 'false']++;

echo 'Database: ' . $database . PHP_EOL;
echo 'Database mode: ' . $databaseMode . PHP_EOL;

foreach ($files as $file) {
    $correct = 0;
    // $failed = [];
    $duration = 0;
    $total = 0;
    $file_stream = fopen($folder . $file, 'rb');
    if ($file_stream) {
        while (($line = fgets($file_stream, 65535)) !== false) {
            $values = explode("\t", $line);
            $start = hrtime(true);
            $result = $eld->detect($values[1])->language;
            $end = hrtime(true);
            if ($result === $values[0]) {
                $correct++;
            }
            $total++;
            $duration += $end - $start;
        }
        fclose($file_stream);
    } else {
        echo 'Error opening file: ' . $file . PHP_EOL;
    }

    $duration /= 1e9; // nanoseconds to seconds
    $times[] = $duration;
    $accuracy = ($correct / $total) * 100;
    $average[] = $accuracy;
    echo str_pad($file, 19) . ' - Correct ratio: ' . round($accuracy, 2) . '%   Duration: ' . $duration . PHP_EOL;
}

$opcacheStatus = (function_exists('opcache_get_status') ? opcache_get_status() : false);
$peakMemoryUsage = memory_get_peak_usage();
$opcacheEnabled = ($opcacheStatus !== false && $opcacheStatus['opcache_enabled'] ? 'YES' : 'NO');
if ($databaseMode === 'bytes' || $databaseMode === 'disk') {
    $opcacheHit = 'NOT possible';
} elseif ($opcacheEnabled === 'NO') {
    $opcacheHit = 'NO';
} elseif ($loadTime < 0.01) {
    $opcacheHit = 'Probably Yes';
} else {
    $opcacheHit = 'Probably No';
}

echo 'Average duration: ' . (array_sum($times) / count($times)) . PHP_EOL;
echo 'Sentences accuracy average: ' . round(array_sum(array_slice($average, 0, 3)) / 3, 3) . "%" . PHP_EOL;
echo 'Total accuracy average: ' . round(array_sum($average) / count($average), 3) . "%" . PHP_EOL;
echo 'Load & detect() time: ' . round($loadTime, 4) . ' sec' . PHP_EOL;
echo "Memory usage: " . round($loadedMemoryUsage / (1024 ** 2), 2) . " MB (All PHP loaded)" . PHP_EOL;
echo "Peak Memory used: " . round($peakMemoryUsage / (1024 ** 2), 2) . " MB" . PHP_EOL;
echo 'OPcache enabled: ' . $opcacheEnabled . PHP_EOL;
// NOTE: could check opcache_get_status(), scripts, file: hit & last_used_timestamp
echo 'OPcache DB Hit: ' . $opcacheHit . PHP_EOL;
echo 'PHP ver.: ' . PHP_VERSION . PHP_EOL;
echo 'PHP SAPI: ' . PHP_SAPI . PHP_EOL;
echo 'php.ini memory_limit: ' . (ini_get('memory_limit') ?: '?') . PHP_EOL;
echo 'php.ini opcache.interned_strings_buffer: ' . (ini_get('opcache.interned_strings_buffer') ?: '?') . ' MB' . PHP_EOL;
echo 'php.ini opcache.memory_consumption: ' . (ini_get('opcache.memory_consumption') ?: '?') . ' MB' . PHP_EOL;
// print_r($reliable);
// print_r(opcache_get_status());
echo(PHP_SAPI === 'cli' ? '' : "</pre>");
