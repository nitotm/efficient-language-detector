<?php

set_time_limit(80);

require_once __DIR__ . '/../manual_autoload.php';

use Nitotm\Eld\LanguageDetector;

// opcache_reset(); // Avoid "interned string buffers overflow" testing multiple DB
// clearstatcache();

$database = 'small';
$files = ['tatoeba-50.txt', 'eld-test.txt', 'sentences_v3.txt', 'word-pairs_v3.txt', 'single-words_v3.txt'];
// $tatoeba50 = ['ar', 'az', 'be', 'bg', 'bn', 'ca', 'cs', 'da', 'de', 'el', 'en', 'es', 'et', 'eu', 'fa', 'fi', 'fr',
// 'gu', 'he', 'hi', 'hr', 'hu', 'hy', 'is', 'it', 'ja', 'ka', 'ko', 'lt', 'lv', 'ms', 'nl', 'no', 'pl', 'pt', 'ro',
// 'ru', 'sk', 'sl', 'sq', 'sv', 'ta', 'th', 'tl', 'tr', 'uk', 'ur', 'vi', 'yo', 'zh'];

$loadStart = microtime(true);
$eld = new LanguageDetector($database);
// $eld->langSubset($tatoeba50); //without saving ->($tatoeba50,false) but benchmarks below are done with DB file cached
$loadTime = microtime(true) - $loadStart;

$folder = __DIR__ . '/';
$times = [];
$average = [];
// Dev testing for isReliable() $reliable = ['correct'=>['true'=>0,'false'=>0], 'incorrect'=>['true'=>0,'false'=>0]];
// For use inside the loop: $reliable['correct'][$eld->detect($values[1])->isReliable() ? 'true' : 'false']++;
echo(PHP_SAPI === 'cli' ? '' : "<pre>" . PHP_EOL);
echo 'Database: ' . $database . PHP_EOL;

foreach ($files as $key => $file) {
    $correct = 0;
    $failed = [];
    $duration = 0;
    $total = 0;
    $file_stream = fopen($folder . $file, 'rb');
    while (($line = fgets($file_stream, 65535)) !== false) {
        $values = explode("\t", $line);
        $start = microtime(true);
        $result = $eld->detect($values[1])->language;
        $end = microtime(true);
        if ($result === $values[0]) {
            $correct++;
        }
        $total++;
        $duration += $end - $start;
    }
    fclose($file_stream);
    $times[] = $duration;
    $accuracy = ($correct / $total) * 100;
    $average[] = $accuracy;
    echo str_pad($file, 17) . ' - Correct ratio: ' . round($accuracy, 2) . '%   Duration: ' . $duration . PHP_EOL;
} //                                                                                                print_r($failed);

$opcacheStatus = (function_exists('opcache_get_status') ? opcache_get_status() : false);
$memoryUsage = memory_get_peak_usage();
$opcacheEnabled = ($opcacheStatus !== false && $opcacheStatus['opcache_enabled'] ? 'YES' : 'NO');

echo 'Average duration: ' . (array_sum($times) / count($times)) . PHP_EOL;
echo 'Sentences accuracy average: ' . round(array_sum(array_slice($average, 0, 3)) / 3, 3) . "%" . PHP_EOL;
echo 'Total accuracy average: ' . round(array_sum($average) / count($average), 3) . "%" . PHP_EOL;
echo 'Load time: ' . round($loadTime, 4) . ' sec' . PHP_EOL;
echo 'OPcache enabled: ' . $opcacheEnabled . PHP_EOL;
// NOTE: could check opcache_get_status(), scripts, file: hit & last_used_timestamp
echo 'OPcache DB Hit: ' . ($opcacheEnabled === 'NO' ? 'NO' : 'Probably ' . ($loadTime < 0.01 ? 'Yes' : 'No')) . PHP_EOL;
echo 'PHP ver.: ' . PHP_VERSION . PHP_EOL;
echo 'PHP SAPI: ' . PHP_SAPI . PHP_EOL;
echo "Memory used: " . ($memoryUsage < 1000000 ? round($memoryUsage / 1024, 2) . " KB"
        : round($memoryUsage / (1024 ** 2), 2) . " MB") . PHP_EOL;
echo 'opcache.interned_strings_buffer: ' . ini_get('opcache.interned_strings_buffer') . ' MB' . PHP_EOL;
echo 'opcache.memory_consumption : ' . ini_get('opcache.memory_consumption') . ' MB' . PHP_EOL;
// print_r($reliable);
// print_r(opcache_get_status());
echo(PHP_SAPI === 'cli' ? '' : "</pre>");

/*

Database: small_50_...
tatoeba-50.txt    - Correct ratio: 96.83%   Duration: 4.7486884593964

Database: small
tatoeba-50.txt    - Correct ratio: 96.59%   Duration: 4.7741844654083
eld-test.txt      - Correct ratio: 99.68%   Duration: 1.6845591068268
sentences_v3.txt  - Correct ratio: 99.16%   Duration: 1.3878071308136
word-pairs_v3.txt - Correct ratio: 90.94%   Duration: 0.44905996322632
single-words_v3.txt - Correct ratio: 75.13%   Duration: 0.34028124809265
Average duration: 1.7271783828735
Sentences accuracy average: 98.476%
Total accuracy average: 92.3%
Load time: 0.0002 sec
OPcache enabled: YES
OPcache DB Hit: Probably Yes
PHP ver.: 7.4.4
PHP SAPI: apache2handler
Memory used: 478.18 KB
opcache.interned_strings_buffer: 150 MB
opcache.memory_consumption : 1200 MB

------

Database: medium_50_...
tatoeba-50.txt    - Correct ratio: 97.9%   Duration: 5.2319552898407

Database: medium
tatoeba-50.txt    - Correct ratio: 97.66%   Duration: 5.2481808662415
eld-test.txt      - Correct ratio: 99.74%   Duration: 1.8495428562164
sentences_v3.txt  - Correct ratio: 99.3%   Duration: 1.5454864501953
word-pairs_v3.txt - Correct ratio: 93.02%   Duration: 0.47096991539001
single-words_v3.txt - Correct ratio: 80.1%   Duration: 0.35583901405334
Average duration: 1.8940038204193
Sentences accuracy average: 98.9%
Total accuracy average: 93.964%
Load time: 0.0002 sec
OPcache enabled: YES
OPcache DB Hit: Probably Yes
PHP ver.: 7.4.4
PHP SAPI: apache2handler
Memory used: 478.18 KB
opcache.interned_strings_buffer: 150 MB
opcache.memory_consumption : 1200 MB

------

Database: large_50_...
tatoeba-50.txt    - Correct ratio: 98.28%   Duration: 4.3491470813751

Database: large
tatoeba-50.txt    - Correct ratio: 98.12%   Duration: 4.3894712924957
eld-test.txt      - Correct ratio: 99.8%   Duration: 1.4891726970673
sentences_v3.txt  - Correct ratio: 99.41%   Duration: 1.223405122757
word-pairs_v3.txt - Correct ratio: 94.81%   Duration: 0.40193748474121
single-words_v3.txt - Correct ratio: 83.49%   Duration: 0.31607866287231
Average duration: 1.5640130519867
Sentences accuracy average: 99.108%
Total accuracy average: 95.124%
Load time: 0.0002 sec
OPcache enabled: YES
OPcache DB Hit: Probably Yes
PHP ver.: 7.4.4
PHP SAPI: apache2handler
Memory used: 478.18 KB
opcache.interned_strings_buffer: 150 MB
opcache.memory_consumption : 1200 MB

------

Database: extralarge_50_...
tatoeba-50.txt    - Correct ratio: 98.54%   Duration: 4.6160507202148

Database: extralarge
tatoeba-50.txt    - Correct ratio: 98.39%   Duration: 4.651153087616
eld-test.txt      - Correct ratio: 99.81%   Duration: 1.6114253997803
sentences_v3.txt  - Correct ratio: 99.45%   Duration: 1.336784362793
word-pairs_v3.txt - Correct ratio: 95.4%   Duration: 0.42377209663391
single-words_v3.txt - Correct ratio: 85.08%   Duration: 0.33033514022827
Average duration: 1.6706940174103
Sentences accuracy average: 99.215%
Total accuracy average: 95.627%
Load time: 0.0002 sec
OPcache enabled: YES
OPcache DB Hit: Probably Yes
PHP ver.: 7.4.4
PHP SAPI: apache2handler
Memory used: 478.18 KB
opcache.interned_strings_buffer: 150 MB
opcache.memory_consumption : 1200 MB

*/
