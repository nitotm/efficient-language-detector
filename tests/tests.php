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

require_once __DIR__ . '/TestRunner.php';

$tests = new Nitotm\Eld\Tests\TestRunner();

// Mostly functional testing, when functions are more mature I will add some more unit tests

if (!isset($GLOBALS['autoload_'])) {
    require_once __DIR__ . '/../src/LanguageDetector.php';
}

$tests->addTest('Load ELD and create instance', function () {
    new Nitotm\Eld\LanguageDetector();
}, true);

$tests->addTest('Simple language detection', function () {
    $eld = new Nitotm\Eld\LanguageDetector();

    $result = $eld->detect('Hola, cómo te llamas?');

    if (!isset($result->language) || $result->language !== 'es') {
        throw new Exception("Expected: 'es', but got: " . ($result->language ?? ''));
    }
});

$tests->addTest('Get scores of multiple languages', function () {
    $eld = new Nitotm\Eld\LanguageDetector();

    $result = $eld->detect('How are you? Bien, gracias');

    if (!isset($result->scores) || count($result->scores) < 2) {
        throw new Exception(
            "Expected: >1 scores, but got: " . count($result->scores ?? [])
        );
    }
});

$tests->addTest('Language detection, 2 bytes length', function () {
    $eld = new Nitotm\Eld\LanguageDetector();
    $result = $eld->detect('To');

    if (!isset($result->language) || $result->language !== 'en') {
        throw new Exception("Expected: 'en', but got: " . ($result->language ?? ''));
    }
    if ($result->isReliable() !== false) {
        throw new Exception("Expected: isReliable() = false, but got: " . json_encode($result->isReliable()));
    }
});

$tests->addTest('getCleanText function', function () {
    $eld = new Nitotm\Eld\LanguageDetector();

    $text = "https://www.google.com/\n" .
        "mail@gmail.com\n" .
        "google.com/search?q=search&source=hp\n" .
        "12345 A12345\n";

    $result = trim($eld->getCleanText($text));

    if ($result !== '') {
        throw new Exception("Expected: empty string, but got " . $result);
    }
});

$tests->addTest('Clean text option', function () {
    $eld = new Nitotm\Eld\LanguageDetector();
    $eld->cleanText(true);

    $text = "https://www.google.com/\n" .
        "mail@gmail.com\n" .
        "google.com/search?q=search&source=hp\n" .
        "12345 A12345\n";

    $result = $eld->detect($text);

    if ($result->language !== NULL) {
        throw new Exception("Expected: NULL, but got " . json_encode($result));
    }
});

$tests->addTest('Check minimum confidence', function () {
    $eld = new Nitotm\Eld\LanguageDetector('ngramsM60.php');

    $result = $eld->detect('zxz zcz zvz zbz znz zmz zlz zsz zdz zkz zjz pelo');

    if ($result->isReliable() !== false) {
        throw new Exception("Expected: isReliable() = false, but got: " . json_encode($result->isReliable()));
    }
});

$tests->addTest('Create dynamicLangSubset(), and detect', function () {
    $eld = new Nitotm\Eld\LanguageDetector();

    $langSubset = ['en'];
    $eld->dynamicLangSubset($langSubset);

    $result = $eld->detect('How are you? Bien, gracias');

    if (!isset($result->scores) || count($result->scores) !== 1) {
        throw new Exception(
            "Expected: 1 score, but got: " . count($result->scores ?? [])
        );
    }
});

$tests->addTest('Create dynamicLangSubset(), disable it, and detect', function () {
    $eld = new Nitotm\Eld\LanguageDetector();

    $langSubset = ['en'];
    $eld->dynamicLangSubset($langSubset);
    $eld->dynamicLangSubset();

    $result = $eld->detect('How are you? Bien, gracias');

    if (!isset($result->scores) || count($result->scores) < 2) {
        throw new Exception(
            "Expected: >1 scores, but got: " . count($result->scores ?? [])
        );
    }
});

$tests->addTest('Create langSubset(), and detect', function () {
    $eld = new Nitotm\Eld\LanguageDetector();

    $langSubset = ['en'];
    $eld->langSubset($langSubset);

    $result = $eld->detect('How are you? Bien, gracias');

    if (!isset($result->scores) || count($result->scores) !== 1) {
        throw new Exception(
            "Expected: 1 score, but got: " . count($result->scores ?? [])
        );
    }
});

$tests->addTest('Create langSubset(), disable it, and detect', function () {
    $eld = new Nitotm\Eld\LanguageDetector();

    $langSubset = ['en'];
    $eld->langSubset($langSubset);
    $eld->langSubset();

    $result = $eld->detect('How are you? Bien, gracias');

    if (!isset($result->scores) || count($result->scores) < 2) {
        throw new Exception(
            "Expected: >1 scores, but got: " . count($result->scores ?? [])
        );
    }
});

$tests->addTest('Check if langSubset() is able to save subset file', function () {
    $eld = new Nitotm\Eld\LanguageDetector();
    $file = __DIR__ . '/../resources/ngrams/subset/ngramsM60-1.2rrx014rx6yos0gkkogws8ksc0okcwk.php';

    // Should already exist
    if (!file_exists($file)) {
        throw new Exception("File resources/ngrams/subset/ngramsM60-1.2rrx... not found");
    }

    if (!unlink($file)) {
        throw new Exception("ABORTED: Unable to delete ngramsM60-1.2rrx... file; Not an ELD error");
    }

    $langSubset = ['en'];
    $eld->langSubset($langSubset);

    if (!file_exists($file)) {
        throw new Exception("File resources/ngrams/subset/ngramsM60-1.2rrx... not found");
    }
});

$tests->addTest('Create instance with diferent ngrams database, and detect', function () {
    $eld = new Nitotm\Eld\LanguageDetector('ngramsM60-6.5ijqhj4oecso0kwcok4k4kgoscwg80o.php');

    $result = $eld->detect('Hola, cómo te llamas?');

    if (!isset($result->language) || $result->language !== 'es') {
        throw new Exception("Expected: 'es', but got: " . ($result->language ?? ''));
    }
});

$tests->addTest("Testing accuracy: ngramsM60.php database, for big-test.txt", function () {
    $eld = new Nitotm\Eld\LanguageDetector('ngramsM60.php');
    $total = 0;
    $correct = 0;
    $handle = fopen(__DIR__ . '/../benchmark/big-test.txt', 'rb');
    if ($handle) {
        while (($line = fgets($handle)) !== false) {
            $values = explode("\t", trim($line));
            if ($eld->detect($values[1])->language === $values[0]) {
                $correct++;
            }
            $total++;
        }
        fclose($handle);
    }

    if ($total < 60000) {
        throw new Exception("ABORTED: Unable to load big-test.txt; Not an ELD error");
    }

    if (($correct / $total) * 100 < 99.4) { // a bit of margin, depending on tie scores order, avg. might change a bit
        throw new Exception("Accuracy too low. Expected 99.42%, but got: " . round(($correct / $total) * 100, 4) . '%');
    }
});

$tests->run();