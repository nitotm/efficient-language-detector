<?php
/** @noinspection ThrowRawExceptionInspection */

/** legal notice: https://github.com/nitotm/efficient-language-detector/blob/main/LEGAL.md */

declare(strict_types = 1);

include __DIR__ . "/vendor/autoload.php";

use Nitotm\Eld\LanguageData;
use Nitotm\Eld\LanguageDetectorWithTools;
use Nitotm\Eld\LanguageResult;
use Nitotm\Eld\LanguageSet;
use Nitotm\EldTests\TestRunner;

if (PHP_SAPI !== 'cli') {
    echo "<pre>";
}

$tests = new TestRunner();

$tests->add('Load ELD and create instance', function () {
    $languageData = new LanguageData();
    $languageSubset = new LanguageSet($languageData);
    $languageDetector = new LanguageDetectorWithTools(
        languageData: $languageData,
        languageSubset: $languageSubset,
    );
}, true);

$tests->add('Simple language detection', function () {
    $languageData = new LanguageData();
    $languageSubset = new LanguageSet($languageData);
    $languageDetector = new LanguageDetectorWithTools(
        languageData: $languageData,
        languageSubset: $languageSubset,
    );

    $result = $languageDetector->detect('Hola, cómo te llamas?');

    if (!$result->isValid || $result->language !== "es") {
        $result->dump();
        throw new Exception("Expected: 'es'");
    }
});

$tests->add('Get scores of multiple languages', function () {
    $languageData = new LanguageData();
    $languageSubset = new LanguageSet($languageData);
    $languageDetector = new LanguageDetectorWithTools(
        languageData: $languageData,
        languageSubset: $languageSubset,
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
    $languageSubset = new LanguageSet($languageData);
    $languageDetector = new LanguageDetectorWithTools(
        languageData: $languageData,
        languageSubset: $languageSubset,
        checkConfidence: false,
        minByteLength: 0,
    );
    $result = $languageDetector->detect('To');

    if (!$result->isValid || $result->language !== "en") {
        $result->dump();
        throw new Exception("Expected: 'en'");
    }
});

$tests->add('Test minimum length error', function () {
    $languageData = new LanguageData();
    $languageSubset = new LanguageSet($languageData);
    $languageDetector = new LanguageDetectorWithTools(
        languageData: $languageData,
        languageSubset: $languageSubset,
        checkConfidence: false,
        minByteLength: 0,
    );
    $result = $languageDetector->detect('To');

    if ($result->isValid || $result->errorMessage !== LanguageResult::TOO_SHORT) {
        $result->dump();
        throw new Exception("Expected: too-short error message");
    }
});

$tests->add('Clean text', function () {
    $languageData = new LanguageData();
    $languageSubset = new LanguageSet($languageData);
    $languageDetector = new LanguageDetectorWithTools(
        languageData: $languageData,
        languageSubset: $languageSubset,
        checkConfidence: false,
        minByteLength: 0,
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
    $languageData = new LanguageData('ngrams-m.php');
    $languageSubset = new LanguageSet($languageData);
    $languageDetector = new LanguageDetectorWithTools(
        languageData: $languageData,
        languageSubset: $languageSubset,
        checkConfidence: true,
        minByteLength: 0,
        minNgrams: 1
    );
    $result = $languageDetector->detect('zxz zcz zvz zbz znz zmz zlz zsz zdz zkz zjz pelo');

    if ($result->isValid) {
        $result->dump();
        throw new Exception("Expected: invalid result");
    }
});

$tests->add('Create dynamicLangSubset(), and detect', function () {
    $languageData = new LanguageData('ngrams-m.php');
    $languageSubset = new LanguageSet($languageData, limitTo: ["en"]);
    $languageDetector = new LanguageDetectorWithTools(
        languageData: $languageData,
        languageSubset: $languageSubset,
        returnScores: true,
    );

    $result = $languageDetector->detect('How are you? Bien, gracias');

    if (!$result->isValid || count($result->scores ?? []) !== 1) {
        $result->dump();
        throw new Exception("Expected: 1 score!");
    }
});

/**
 * TODO please continue writing your tests
 * $tests->add('Create dynamicLangSubset(), disable it, and detect', function () {
 * $languageDetector = new Nitotm\Eld\LanguageDetector();
 * $languageDetector->returnScores = true;
 * $langSubset = ['en'];
 * $languageDetector->dynamicLangSubset($langSubset);
 * $languageDetector->dynamicLangSubset(false);
 * $result = $languageDetector->detect('How are you? Bien, gracias');
 * if (!isset($result['scores']) || count($result['scores']) < 2) {
 * throw new Exception(
 * "Expected: >1 scores, but got: " . (isset($result['scores']) ? count($result['scores']) : '0')
 * );
 * }
 * });
 * $tests->add('Create langSubset(), and detect', function () {
 * $languageDetector = new Nitotm\Eld\LanguageDetector();
 * $languageDetector->returnScores = true;
 * $langSubset = ['en'];
 * $languageDetector->langSubset($langSubset);
 * $result = $languageDetector->detect('How are you? Bien, gracias');
 * if (!isset($result['scores']) || count($result['scores']) !== 1) {
 * throw new Exception(
 * "Expected: 1 score, but got: " . (isset($result['scores']) ? count($result['scores']) : '0')
 * );
 * }
 * });
 * $tests->add('Create langSubset(), disable it, and detect', function () {
 * $languageDetector = new Nitotm\Eld\LanguageDetector();
 * $languageDetector->returnScores = true;
 * $langSubset = ['en'];
 * $languageDetector->langSubset($langSubset);
 * $languageDetector->langSubset(false);
 * $result = $languageDetector->detect('How are you? Bien, gracias');
 * if (!isset($result['scores']) || count($result['scores']) < 2) {
 * throw new Exception(
 * "Expected: >1 scores, but got: " . (isset($result['scores']) ? count($result['scores']) : '0')
 * );
 * }
 * });
 * $tests->add('Check if langSubset() is able to save subset file', function () {
 * $languageDetector = new Nitotm\Eld\LanguageDetector();
 * $file = __DIR__ . '/../src/ngrams/ngrams.17ba0791499db908433b80f37c5fbc89b870084b.php';
 * // Should already exist
 * if (!file_exists($file)) {
 * throw new Exception("File /src/ngrams/ngrams.17ba0791499... not found");
 * }
 * if (!unlink($file)) {
 * throw new Exception("ABORTED: Unable to delete ngrams.17ba0791... file; Not an ELD error");
 * }
 * $langSubset = ['en'];
 * $languageDetector->langSubset($langSubset);
 * if (!file_exists($file)) {
 * throw new Exception("File /src/ngrams/ngrams.17ba0791499... not found");
 * }
 * });
 * $tests->add('Create instance with diferent ngrams database, and detect', function () {
 * $languageDetector = new Nitotm\Eld\LanguageDetector('ngrams.2f37045c74780aba1d36d6717f3244dc025fb935.php');
 * $result = $languageDetector->detect('Hola, cómo te llamas?');
 * if (!isset($result['language']) || $result['language'] !== 'es') {
 * throw new Exception("Expected: 'es', but got: " . ($result['language'] ?? ''));
 * }
 * });
 * $tests->add("Testing accuracy: ngrams-m.php database, for big-test.txt", function () {
 * $languageDetector = new Nitotm\Eld\LanguageDetector('ngrams-m.php');
 * $total = 0;
 * $correct = 0;
 * $handle = fopen(__DIR__ . '/../benchmarks/big-test.txt', "r");
 * if ($handle) {
 * while (($line = fgets($handle)) !== false) {
 * $values = explode("\t", trim($line));
 * if ($languageDetector->detect($values[1], false, false, 0, 1)['language'] == $values[0]) {
 * $correct++;
 * }
 * $total++;
 * }
 * fclose($handle);
 * }
 * if ($total < 60000) {
 * throw new Exception("ABORTED: Unable to load big-test.txt; Not an ELD error");
 * }
 * if (($correct / $total) * 100 < 99.4) { // a bit of margin, depending on tie scores order, avg. might change a bit
 * throw new Exception("Accuracy too low. Expected 99.42%, but got: " . round(($correct / $total) * 100, 4) . '%');
 * }
 * });
 */
$tests->run();
