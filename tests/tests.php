<?php
/**
 * @copyright 2023 Nito T.M.
 * @license https://www.apache.org/licenses/LICENSE-2.0 Apache-2.0
 * @author Nito T.M. (https://github.com/nitotm)
 * @package nitotm/efficient-language-detector
 */

/*  You can execute this file directly on the command line (*update the path)
    $ php efficient-language-detector/tests/tests.php

    Or open the file through a server in the Browser
*/
declare(strict_types=1);

require_once __DIR__ . '/../manual_loader.php';
require_once __DIR__ . '/TestRunner.php';

$tests = new Nitotm\Eld\Tests\TestRunner();

// Mostly functional testing, when functions are more mature I will add some more unit tests

$tests->addTest('Load ELD and create instance', function () {
    new Nitotm\Eld\LanguageDetector(Nitotm\Eld\EldDataFile::SMALL);
}, true);

$tests->addTest('Simple language detection', function () {
    $eld = new Nitotm\Eld\LanguageDetector('small');

    $result = $eld->detect('Hola, cómo te llamas?');

    if ($result->language !== 'es') {
        throw new RuntimeException("Expected: 'es', but got: " . var_export($result->language, true));
    }
});

$tests->addTest('Test detect() with ISO 639-2T return format', function () {
    $eld = new Nitotm\Eld\LanguageDetector('small', Nitotm\Eld\EldFormat::ISO639_2T);
    $result = $eld->detect('How are you today my friend');

    if ($result->language !== 'eng') {
        throw new RuntimeException("Expected 'eng', but got: ". var_export($result->language, true));
    }
});

$tests->addTest('Empty text', function () {
    $eld = new Nitotm\Eld\LanguageDetector('small');

    $result = $eld->detect('');

    if ($result->language !== 'und') {
        throw new RuntimeException("Expected: 'und', but got: " . var_export($result->language, true));
    }
});

$tests->addTest('Get scores of multiple languages', function () {
    $eld = new Nitotm\Eld\LanguageDetector('small');

    $result = $eld->detect('How are you? Bien, gracias');

    if (count($result->scores()) < 2) {
        throw new RuntimeException(
            "Expected: >1 scores, but got: " . count($result->scores())
        );
    }
});

$tests->addTest('Language detection, 2 bytes length', function () {
    $eld = new Nitotm\Eld\LanguageDetector('small');
    $result = $eld->detect('To');

    if ($result->language !== 'en') {
        throw new RuntimeException("Expected: 'en', but got: " . var_export($result->language, true));
    }
    if ($result->isReliable() !== false) {
        throw new RuntimeException(
            "Expected: isReliable() = false, but got: " . var_export($result->isReliable(), true)
        );
    }
});

$tests->addTest('cleanText() method', function () {
    $eld = new Nitotm\Eld\LanguageDetector('small');

    $text = "https://www.google.com/\n" .
        "mail@gmail.com\n" .
        "google.com/search?q=search&source=hp\n" .
        "12345 A12345\n";

    $result = trim($eld->cleanText($text));

    if ($result !== '') {
        throw new RuntimeException("Expected: empty string, but got " . $result);
    }
});

$tests->addTest('enableTextCleanup() option', function () {
    $eld = new Nitotm\Eld\LanguageDetector('small');
    $eld->enableTextCleanup(true);

    $text = "https://www.google.com/\n" .
        "mail@gmail.com\n" .
        "google.com/search?q=search&source=hp\n" .
        "12345 A12345\n";

    $result = $eld->detect($text);

    if ($result->language !== 'und') {
        throw new RuntimeException("Expected language 'und', but got: " . var_export($result->language, true));
    }
});

$tests->addTest('Check isReliable() threshold', function () {
    $eld = new Nitotm\Eld\LanguageDetector('small');

    $result = $eld->detect('zxb zxh zsf pelo');

    if ($result->isReliable() !== false) {
        throw new RuntimeException(
            "Expected: isReliable() = false, but got: " . var_export($result->isReliable(), true)
        );
    }
});

$tests->addTest('Create langSubset(), and detect', function () {
    $eld = new Nitotm\Eld\LanguageDetector('small');

    $langSubset = ['en'];
    $eld->langSubset($langSubset);

    $result = $eld->detect('How are you? Bien, gracias');

    if (count($result->scores()) !== 1) {
        throw new RuntimeException(
            "Expected: 1 score, but got: " . count($result->scores())
        );
    }
});

$tests->addTest('Create langSubset() with ISO 639-2T format', function () {
    $eld = new Nitotm\Eld\LanguageDetector('small', 'ISO639_2T');

    $langSubset = ['eng'];
    $subsetResult = $eld->langSubset($langSubset);

    if (!isset($subsetResult->languages) || $subsetResult->languages !== [11 => 'eng']) {
        throw new RuntimeException(
            "Expected: [11 => 'eng'], but got: " . var_export($subsetResult->languages, true)
        );
    }
});

$tests->addTest('Create langSubset(), disable it, and detect', function () {
    $eld = new Nitotm\Eld\LanguageDetector('small');

    $langSubset = ['en'];
    $eld->langSubset($langSubset);
    $eld->langSubset();

    $result = $eld->detect('How are you? Bien, gracias');

    if (count($result->scores()) < 2) {
        throw new RuntimeException(
            "Expected: >1 scores, but got: " . count($result->scores())
        );
    }
});

$tests->addTest('Check if langSubset() is able to save subset file', function () {
    $eld = new Nitotm\Eld\LanguageDetector('small');
    $file = __DIR__ . '/../resources/ngrams/subset/small_1_1ni.php';

    if (file_exists($file) && !unlink($file)) {
        throw new RuntimeException("ABORTED: Unable to delete small_1_1ni.php file; Not an ELD error");
    }

    $langSubset = ['en'];
    $eld->langSubset($langSubset);

    if (!file_exists($file)) {
        throw new RuntimeException("File resources/ngrams/subset/small_1_1ni.php not found");
    }
});

$tests->addTest('Create instance with different ngrams database, and detect', function () {
    $eld = new Nitotm\Eld\LanguageDetector('small_6_mfss5z1t');

    $result = $eld->detect('Hola, cómo te llamas?');

    if ($result->language !== 'es') {
        throw new RuntimeException("Expected: 'es', but got: " . var_export($result->language, true));
    }
});

$tests->addTest("Testing accuracy: 'small' database, for eld-test.txt", function () {
    $eld = new Nitotm\Eld\LanguageDetector('small');
    $total = 0;
    $correct = 0;
    $handle = fopen(__DIR__ . '/../benchmark/eld-test.txt', 'rb');
    if ($handle) {
        while (($line = fgets($handle)) !== false) {
            $values = explode("\t", trim($line));
            if (isset($values[1])) {
                if ($eld->detect($values[1])->language === $values[0]) {
                    $correct++;
                }
                $total++;
            }
        }
        fclose($handle);
    }

    if ($total < 59999) {
        throw new RuntimeException("ABORTED: Unable to load eld-test.txt; Not an ELD error");
    }

    if (($correct / $total) * 100 < 99.66) { // a bit of margin, depending on tie scores order, avg. might change a bit
        throw new RuntimeException(
            "Accuracy too low. Expected 99.67%, but got: " . round(($correct / $total) * 100, 4) . '%'
        );
    }
});

$tests->run();
