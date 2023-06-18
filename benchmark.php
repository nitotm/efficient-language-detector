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

Results eurosat7/efficient-language-detector:main@2023-06-18, PHP 8.2.7, ngrams-m.php

tweets.txt - Correct ratio: 98.93% Duration: 0.29958343505859
big-test.txt - Correct ratio: 92.05% Duration: 2.1553561687469
sentences.txt - Correct ratio: 93.31% Duration: 1.957035779953
word-pairs.txt - Correct ratio: 73.43% Duration: 1.0882313251495
single-words.txt - Correct ratio: 55.94% Duration: 0.93544888496399

If correct ratio is vastly inferior, try with 'ngrams-m.safe.php'
*/
$languageData = new LanguageData(
    'ngrams-m.php' //  -> 'ngrams-m.safe.php'
);
$languageSubset = new LanguageSet(
    $languageData,
    usecache: true,
);
$languageDetector = new LanguageDetector(
    languageData: $languageData,
    languageSet: $languageSubset,
    minByteLength: 0,
    minNgrams: 0
);

$summary = [];
$perlang = [];
foreach ($files as $file) {
    $duration = 0;
    $correct = 0;
    $failed = 0;
    $fp = fopen($file, 'rb');
    if ($fp === false) {
        echo('cannot read from file ' . $file . PHP_EOL);
        continue;
    }
    echo "- " . basename($file) . PHP_EOL;
    while (($line = fgets($fp, 4096)) !== false) {
        [$lang, $text] = explode("\t", $line);
        $start = microtime(true);
        $result = $languageDetector->detect($text);
        $duration += microtime(true) - $start;
        if ($result->isValid && $result->language === $lang) {
            $correct++;
            $type = "correct";
        } else {
            $failed++;
            $type = $result->isValid ? "wrong" : "miss";
        }
        echo "- - " . $type . ": " . ($result->language ?? "none") . ", expected " . $lang . PHP_EOL;
        $perlang[$lang][$type] = ($perlang[$lang][$type] ?? 0) + 1;
    }
    fclose($fp);
    $total = $correct + $failed;
    $summary[] = basename($file) . ' - Correct ratio: ' . round(($correct / $total) * 100, 2) . '% Duration: ' . $duration;
}
echo PHP_EOL . PHP_EOL . "SUMMARY" . PHP_EOL;
ksort($perlang);
/** @noinspection ForgottenDebugOutputInspection */
dump($perlang);
echo implode(PHP_EOL, $summary) . PHP_EOL;
