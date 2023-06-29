<?php

require_once __DIR__ . '/../src/languageDetector.php';

use Nitotm\Eld\LanguageDetector;

$eld = new LanguageDetector('ngramsM60.php');
$files = ['tweets.txt', 'big-test.txt', 'sentences.txt', 'word-pairs.txt', 'single-words.txt'];
$times = [];
print (PHP_SAPI === 'cli' ? '' : "<pre>" . PHP_EOL);

foreach ($files as $key => $file) {
    $content = file_get_contents(__DIR__ . '/' . $file);
    $lines = explode("\n", trim($content));
    $texts = [];

    foreach ($lines as $line) {
        $values = explode("\t", $line);
        $texts[] = [$values[1], $values[0]];
    }

    $total = count($texts);
    $correct = 0;
    $duration = 0;

    // Add each detect() duration inside the loop gives higher times, contrary to "logic" this appears to be more stable
    $start = microtime(true);
    foreach ($texts as $text) {
        if ($eld->detect($text[0])->language === $text[1]) {
            $correct++;
        }
    }
    $duration = microtime(true) - $start;
    $times[] = $duration;
    print str_pad($file, 16) . ' - Correct ratio: ' . round(
            ($correct / $total) * 100,
            2
        ) . '%   Duration: ' . $duration . PHP_EOL;
}
print 'Average Duration: ' . (array_sum($times) / count($times));
print (PHP_SAPI === 'cli' ? '' : "</pre>");

/*
If correct ratio is inferior, use $eld = new languageDetector('ngramsM60.safe.php'); to see if it fixes the problem

Results for v1.0.0, PHP 7.4.4, ngramsM60.php
    tweets.txt       - Correct ratio: 99.28%   Duration: 0.30713295936584
    big-test.txt     - Correct ratio: 99.42%   Duration: 2.4928371906281
    sentences.txt    - Correct ratio: 98.78%   Duration: 2.1568570137024
    word-pairs.txt   - Correct ratio: 87.56%   Duration: 0.66023302078247
    single-words.txt - Correct ratio: 73.31%   Duration: 0.47791314125061
Average Duration: 1.2189946651458
*/
