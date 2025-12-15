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

/*

v 3.1


Database: small
Database mode: array
tatoeba-50.txt      - Correct ratio: 96.59%   Duration: 4.7218963 / tatoeba-50.txt  - Correct ratio: 96.83%
eld-test.txt        - Correct ratio: 99.68%   Duration: 1.6463217
sentences_v3.txt    - Correct ratio: 99.16%   Duration: 1.3756491
word-pairs_v3.txt   - Correct ratio: 90.94%   Duration: 0.4460386
single-words_v3.txt - Correct ratio: 75.13%   Duration: 0.3366261
Average duration: 1.70530636
Sentences accuracy average: 98.476%
Total accuracy average: 92.3%
Load & detect() time: 0.0007 sec
Memory usage: 0.35 MB (All PHP loaded)
Peak Memory used: 0.43 MB
OPcache enabled: YES
OPcache DB Hit: Probably Yes
PHP ver.: 7.4.4
PHP SAPI: apache2handler
php.ini memory_limit: 7144M
php.ini opcache.interned_strings_buffer: 150 MB
php.ini opcache.memory_consumption: 1200 MB
#### UNCACHED
Load & detect() time: 0.1362 sec
Memory usage: 45.01 MB (All PHP loaded)
Peak Memory used: 76.65 MB
OPcache enabled: YES
OPcache DB Hit: Probably No


Database: medium
Database mode: array
tatoeba-50.txt      - Correct ratio: 97.66%   Duration: 5.3032791 / tatoeba-50.txt  - Correct ratio: 97.9%
eld-test.txt        - Correct ratio: 99.74%   Duration: 1.8290292
sentences_v3.txt    - Correct ratio: 99.3%   Duration: 1.5424927
word-pairs_v3.txt   - Correct ratio: 93.02%   Duration: 0.4775051
single-words_v3.txt - Correct ratio: 80.1%   Duration: 0.3663317
Average duration: 1.90372756
Sentences accuracy average: 98.9%
Total accuracy average: 93.964%
Load & detect() time: 0.0002 sec
Memory usage: 0.35 MB (All PHP loaded)
Peak Memory used: 0.43 MB
OPcache enabled: YES
OPcache DB Hit: Probably Yes
PHP ver.: 7.4.4
PHP SAPI: apache2handler
php.ini memory_limit: 7144M
php.ini opcache.interned_strings_buffer: 150 MB
php.ini opcache.memory_consumption: 1200 MB
#### UNCACHED
Load & detect() time: 0.4981 sec
Memory usage: 135.47 MB (All PHP loaded)
Peak Memory used: 281.88 MB
OPcache enabled: YES
OPcache DB Hit: Probably No


Database: large
Database mode: array
tatoeba-50.txt      - Correct ratio: 98.12%   Duration: 4.4425181  / tatoeba-50.txt  - Correct ratio: 98.28%
eld-test.txt        - Correct ratio: 99.8%   Duration: 1.4869054
sentences_v3.txt    - Correct ratio: 99.41%   Duration: 1.2293885
word-pairs_v3.txt   - Correct ratio: 94.81%   Duration: 0.4103281
single-words_v3.txt - Correct ratio: 83.49%   Duration: 0.3268392
Average duration: 1.57919586
Sentences accuracy average: 99.108%
Total accuracy average: 95.124%
Load & detect() time: 0.0002 sec
Memory usage: 0.35 MB (All PHP loaded)
Peak Memory used: 0.44 MB
OPcache enabled: YES
OPcache DB Hit: Probably Yes
PHP ver.: 7.4.4
PHP SAPI: apache2handler
php.ini memory_limit: 7144M
php.ini opcache.interned_strings_buffer: 150 MB
php.ini opcache.memory_consumption: 1200 MB
#### UNCACHED
Load & detect() time: 1.4686 sec
Memory usage: 554.34 MB (All PHP loaded)
Peak Memory used: 976.98 MB
OPcache enabled: YES
OPcache DB Hit: Probably No



Database: extralarge
Database mode: array
tatoeba-50.txt      - Correct ratio: 98.39%   Duration: 4.7430288 / tatoeba-50.txt  - Correct ratio: 98.54%
eld-test.txt        - Correct ratio: 99.81%   Duration: 1.6045767
sentences_v3.txt    - Correct ratio: 99.45%   Duration: 1.3455058
word-pairs_v3.txt   - Correct ratio: 95.4%   Duration: 0.4311581
single-words_v3.txt - Correct ratio: 85.08%   Duration: 0.3397596
Average duration: 1.6928058
Sentences accuracy average: 99.215%
Total accuracy average: 95.627%
Load & detect() time: 0.0002 sec
Memory usage: 0.35 MB (All PHP loaded)
Peak Memory used: 0.43 MB
OPcache enabled: YES
OPcache DB Hit: Probably Yes
PHP ver.: 7.4.4
PHP SAPI: apache2handler
php.ini memory_limit: 7144M
php.ini opcache.interned_strings_buffer: 150 MB
php.ini opcache.memory_consumption: 1200 MB
#### UNCACHED
Load & detect() time: 3.3387 sec
Memory usage: 1188.85 MB (All PHP loaded)
Peak Memory used: 2083.27 MB
OPcache enabled: YES
OPcache DB Hit: Probably No


Database: small
Database mode: string
tatoeba-50.txt      - Correct ratio: 96.59%   Duration: 11.1032318
eld-test.txt        - Correct ratio: 99.68%   Duration: 4.3687681
sentences_v3.txt    - Correct ratio: 99.16%   Duration: 3.6349242
word-pairs_v3.txt   - Correct ratio: 90.95%   Duration: 0.9040978
single-words_v3.txt - Correct ratio: 75.13%   Duration: 0.5992888
Average duration: 4.12206214
Sentences accuracy average: 98.476%
Total accuracy average: 92.301%
Load & detect() time: 0.0174 sec
Memory usage: 0.35 MB (All PHP loaded)
Peak Memory used: 8.42 MB
OPcache enabled: YES
OPcache DB Hit: Probably No
PHP ver.: 7.4.4
PHP SAPI: apache2handler
php.ini memory_limit: 7144M
php.ini opcache.interned_strings_buffer: 150 MB
php.ini opcache.memory_consumption: 1200 MB
#### UNCACHED
Load & detect() time: 0.0163 sec
Memory usage: 4.88 MB (All PHP loaded)
Peak Memory used: 8.39 MB
OPcache enabled: YES
OPcache DB Hit: Probably No


Database: extralarge
Database mode: string
tatoeba-50.txt      - Correct ratio: 98.39%   Duration: 10.5794357 / tatoeba-50.txt - Correct ratio: 98.54%
eld-test.txt        - Correct ratio: 99.81%   Duration: 3.9384139
sentences_v3.txt    - Correct ratio: 99.45%   Duration: 3.2674317
word-pairs_v3.txt   - Correct ratio: 95.4%   Duration: 0.7582831
single-words_v3.txt - Correct ratio: 85.09%   Duration: 0.5249078
Average duration: 3.81369444
Sentences accuracy average: 99.215%
Total accuracy average: 95.627%
Load & detect() time: 0.0004 sec
Memory usage: 0.35 MB (All PHP loaded)
Peak Memory used: 0.43 MB
OPcache enabled: YES
OPcache DB Hit: Probably Yes
PHP ver.: 7.4.4
PHP SAPI: apache2handler
php.ini memory_limit: 7144M
php.ini opcache.interned_strings_buffer: 150 MB
php.ini opcache.memory_consumption: 1200 MB
#### UNCACHED
Load & detect() time: 0.2548 sec
Memory usage: 52.37 MB (All PHP loaded)
Peak Memory used: 80.39 MB
OPcache enabled: YES
OPcache DB Hit: Probably No


Database: extralarge
Database mode: bytes
tatoeba-50.txt      - Correct ratio: 98.39%   Duration: 10.584865 / tatoeba-50.txt  - Correct ratio: 98.54%
eld-test.txt        - Correct ratio: 99.81%   Duration: 3.9294314
sentences_v3.txt    - Correct ratio: 99.45%   Duration: 3.2720903
word-pairs_v3.txt   - Correct ratio: 95.4%   Duration: 0.7554301
single-words_v3.txt - Correct ratio: 85.09%   Duration: 0.5268347
Average duration: 3.8137303
Sentences accuracy average: 99.215%
Total accuracy average: 95.627%
Load & detect() time: 0.0415 sec
Memory usage: 52.35 MB (All PHP loaded)
Peak Memory used: 52.43 MB
OPcache enabled: YES
OPcache DB Hit: NOT possible
PHP ver.: 7.4.4
PHP SAPI: apache2handler
php.ini memory_limit: 7144M
php.ini opcache.interned_strings_buffer: 150 MB
php.ini opcache.memory_consumption: 1200 MB
#### UNCACHED
Load & detect() time: 0.0439 sec
Memory usage: 52.36 MB (All PHP loaded)
Peak Memory used: 52.44 MB
OPcache enabled: YES
OPcache DB Hit: NOT possible


Database: extralarge
Database mode: disk
tatoeba-50.txt      - Correct ratio: 98.39%   Duration: 62.5551364 / tatoeba-50.txt  - Correct ratio: 98.54%
eld-test.txt        - Correct ratio: 99.81%   Duration: 26.6389848
sentences_v3.txt    - Correct ratio: 99.45%   Duration: 21.6145688
word-pairs_v3.txt   - Correct ratio: 95.4%   Duration: 4.2603607
single-words_v3.txt - Correct ratio: 85.09%   Duration: 2.5104538
Average duration: 23.5159009
Sentences accuracy average: 99.215%
Total accuracy average: 95.627%
Load & detect() time: 0.0011 sec
Memory usage: 0.37 MB (All PHP loaded)
Peak Memory used: 0.45 MB
OPcache enabled: YES
OPcache DB Hit: NOT possible
PHP ver.: 7.4.4
PHP SAPI: apache2handler
php.ini memory_limit: 7144M
php.ini opcache.interned_strings_buffer: 150 MB
php.ini opcache.memory_consumption: 1200 MB
#### UNCACHED
Load & detect() time: 0.0012 sec
Memory usage: 0.38 MB (All PHP loaded)
Peak Memory used: 0.55 MB
OPcache enabled: YES
OPcache DB Hit: NOT possible


*/
