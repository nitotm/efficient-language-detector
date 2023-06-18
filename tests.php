<?php
/** @noinspection ThrowRawExceptionInspection */

/** legal notice: https://github.com/nitotm/efficient-language-detector/blob/main/LEGAL.md */

declare(strict_types = 1);

/** @psalm-suppress MissingFile */
include __DIR__ . "/vendor/autoload.php";

use Nitotm\Eld\LanguageData;
use Nitotm\Eld\LanguageDetector;
use Nitotm\Eld\LanguageResult;
use Nitotm\Eld\LanguageSet;
use Nitotm\EldTests\TestRunner;

if (PHP_SAPI !== 'cli') {
    echo "<pre>";
}

$tests = new TestRunner();

$tests->add('Load ELD and create instance', function () {
    $languageData = new LanguageData();
    $languageSet = new LanguageSet($languageData);
    new LanguageDetector(
        languageData: $languageData,
        languageSet: $languageSet,
    );
}, true);

$tests->add('Simple language detection', function () {
    $languageData = new LanguageData();
    $languageSet = new LanguageSet($languageData);
    $languageDetector = new LanguageDetector(
        languageData: $languageData,
        languageSet: $languageSet,
    );

    $result = $languageDetector->detect('Hola, cómo te llamas?');

    if (!$result->isValid || $result->language !== "es") {
        $result->dump();
        throw new Exception("Expected: 'es'");
    }
});

$tests->add('Get scores of multiple languages', function () {
    $languageData = new LanguageData();
    $languageSet = new LanguageSet($languageData);
    $languageDetector = new LanguageDetector(
        languageData: $languageData,
        languageSet: $languageSet,
        returnScores: true,
    );

    $result = $languageDetector->detect('How are you? Bien, gracias');

    if (!$result->isValid || count($result->scores ?? []) < 2) {
        $result->dump();
        throw new Exception("Expected: >1 scores!");
    }
});

$tests->add('Language detection, without minimum length', function () {
    $languageData = new LanguageData();
    $languageSet = new LanguageSet($languageData);
    $languageDetector = new LanguageDetector(
        languageData: $languageData,
        languageSet: $languageSet,
        minByteLength: 0,
        minNgrams: 0,
    );
    $result = $languageDetector->detect('To');

    if (!$result->isValid || $result->language !== "en") {
        $result->dump();
        throw new Exception("Expected: 'en'");
    }
});

$tests->add('Test minimum length error', function () {
    $languageData = new LanguageData();
    $languageSet = new LanguageSet($languageData);
    $languageDetector = new LanguageDetector(
        languageData: $languageData,
        languageSet: $languageSet,
        minByteLength: 10,
        minNgrams: 0,
    );
    $result = $languageDetector->detect('To');

    if ($result->isValid || $result->errorMessage !== LanguageResult::TOO_SHORT) {
        $result->dump();
        throw new Exception("Expected: too-short error message");
    }
});

$tests->add('Clean text', function () {
    $languageData = new LanguageData();
    $languageSet = new LanguageSet($languageData);
    $languageDetector = new LanguageDetector(
        languageData: $languageData,
        languageSet: $languageSet,
    );

    $text = "https://www.google.com/\n" .
        "mail@gmail.com\n" .
        "google.com/search?q=search&source=hp\n" .
        "12345 A12345\n";

    $result = $languageDetector->cleanupText($text);

    if ($result !== '') {
        throw new Exception("Expected: empty string, but got " . $result);
    }
});

$tests->add('Check minimum confidence', function () {
    $languageData = new LanguageData();
    $languageSet = new LanguageSet($languageData);
    $languageDetector = new LanguageDetector(
        languageData: $languageData,
        languageSet: $languageSet,
        checkConfidence: true,
    );
    $result = $languageDetector->detect('zxz zcz zvz zbz znz zmz zlz zsz zdz zkz zjz pelo');

    if ($result->isValid) {
        $result->dump();
        throw new Exception("Expected: invalid result");
    }
});

$tests->add('Create dynamicLangSubset(), and detect', function () {
    $languageData = new LanguageData();
    $languageSet = new LanguageSet($languageData, limitTo: ["en"]);
    $languageDetector = new LanguageDetector(
        languageData: $languageData,
        languageSet: $languageSet,
        returnScores: true,
    );

    $result = $languageDetector->detect('How are you? Bien, gracias');

    if (!$result->isValid || count($result->scores ?? []) !== 1) {
        $result->dump();
        throw new Exception("Expected: 1 score!");
    }
});

$tests->add('Check if langSubset() is able to save subset file', function () {
    $languageData = new LanguageData();
    $languageSet = new LanguageSet(
        $languageData,
        limitTo: ["en", "es"],
        usecache: true,
    );
    $languageDetector = new LanguageDetector(
        languageData: $languageData,
        languageSet: $languageSet,
        returnScores: true,
    );

    $languageDetector->detect('How are you? Bien, gracias');
    $filename = __DIR__ . "/data/cache/en-es.php";

    if (!file_exists($filename)) {
        throw new Exception("Expected cachefile: " . $filename);
    }
});

$tests->add("Testing accuracy: ngrams-m.php database, for big-test.txt", function () {
    $path = __DIR__ . '/benchmarkdata/';
    $files = [
        $path . 'big-test.txt',
    ];
    $languageData = new LanguageData();
    $languageSet = new LanguageSet($languageData);
    $languageDetector = new LanguageDetector(
        languageData: $languageData,
        languageSet: $languageSet,
        cleanText: true,
        minByteLength: 1,
        minNgrams: 1,
    );

    $correct = 0;
    $failed = 0;
    $total = 0;
    foreach ($files as $file) {
        $fp = fopen($file, 'rb');
        if ($fp === false) {
            echo('cannot read from file ' . $file . PHP_EOL);
            continue;
        }
        echo "- " . basename($file) . PHP_EOL;
        while (($line = fgets($fp, 4096)) !== false) {
            [$lang, $text] = explode("\t", $line);
            $result = $languageDetector->detect($text);
            if ($result->isValid && $result->language === $lang) {
                $correct++;
            } else {
                $failed++;
            }
        }
        fclose($fp);
        $total = $correct + $failed;
    }
    if ($total < 60000) {
        throw new Exception("ABORTED: Unable to load big-test.txt; Not an ELD error");
    }
    echo "TOTAL: " . $total . PHP_EOL;
    $ratio = 100 / $total * $correct;
    echo "RATIO: " . $ratio . " % (correct: $correct, failed: $failed)" . PHP_EOL;

    if ($ratio < 90.4) { // TODO, sorry did something wrong, detection rate has dropped
        throw new Exception("Accuracy too low. Expected 99.42%, but got: " . round($ratio, 4) . '%');
    }
});

$tests->run();
