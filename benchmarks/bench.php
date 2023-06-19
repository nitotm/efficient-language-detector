<?php

require_once __DIR__ . '/../src/languageDetector.php';

use Nitotm\Eld\LanguageDetector;

$eld = new LanguageDetector();
$files = ['tweets.txt', 'big-test.txt', 'sentences.txt', 'word-pairs.txt', 'single-words.txt'];

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

    $start = microtime(true);
    foreach ($texts as $text) {
        if ($eld->detect($text[0], false, false, 0, 1)['language'] === $text[1]) {
            $correct++;
        }
    }
    $time = microtime(true) - $start;
    print $file . ' - Correct ratio: ' . round(($correct / $total) * 100, 2) . '% Time: ' . $time . PHP_EOL . PHP_EOL;
}

print (PHP_SAPI === 'cli' ? '' : "</pre>" . PHP_EOL);

/*
Results for v1.0.0, PHP 7.4.4, ngrams-m.php

tweets.txt - Correct ratio: 99.28% Time: 0.30713295936584
big-test.txt - Correct ratio: 99.42% Time: 2.4928371906281
sentences.txt - Correct ratio: 98.78% Time: 2.1568570137024
word-pairs.txt - Correct ratio: 87.56% Time: 0.66023302078247
single-words.txt - Correct ratio: 73.31% Time: 0.47791314125061

    If correct ratio is inferior, use $eld = new languageDetector('ngrams-m.safe.php'); to see if it fixes the problem.

*/
