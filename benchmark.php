<?php
/** legal notice: https://github.com/nitotm/efficient-language-detector/blob/main/LEGAL.md */

declare(strict_types = 1);

namespace Nitotm\Eld;

include __DIR__ . "/vendor/autoload.php";

if (PHP_SAPI !== 'cli') {
    echo "<pre>";
}

$path = __DIR__ . '/benchmarkdata/';
$files = [
    $path . 'tweets.txt',
    $path . 'big-test.txt',
    $path . 'sentences.txt',
    $path . 'word-pairs.txt',
    $path . 'single-words.txt',
];

/*
Results for v1.0.0, PHP 7.4.4, ngrams-m.php

tweets.txt - Correct ratio: 99.28% Time: 0.30713295936584
big-test.txt - Correct ratio: 99.42% Time: 2.4928371906281
sentences.txt - Correct ratio: 98.78% Time: 2.1568570137024
word-pairs.txt - Correct ratio: 87.56% Time: 0.66023302078247
single-words.txt - Correct ratio: 73.31% Time: 0.47791314125061

If correct ratio is vastly inferior, try with 'ngrams-m.safe.php'
*/
$languageData = new LanguageData(
    'ngrams-m.php' //  -> 'ngrams-m.safe.php'
);
$languageSubset = new LanguageSubset($languageData);
$languageDetector = new LanguageDetector(
    languageData: $languageData,
    languageSubset: $languageSubset,
);

foreach ($files as $file) {
    $duration = 0;
    $correct = 0;
    $failed = 0;
    $fp = fopen($file, 'rb');
    if ($fp === false) {
        echo('cannot read from file ' . $file . PHP_EOL);
        continue;
    }
    while (($line = fgets($fp, 4096)) !== false) {
        [$lang, $text] = explode("\t", $line);
        $start = microtime(true);
        $result = $languageDetector->detect($text);
        if ($result->isValid && $result->language === $lang) {
            $correct++;
        } else {
            $failed++;
            $result->dump(true);
        }
        $duration += microtime(true) - $start;
    }
    $total = $correct + $failed;
    print $file . ' - Correct ratio: ' . round(($correct / $total) * 100, 2) . '% Duration: ' . $duration . PHP_EOL;
}
