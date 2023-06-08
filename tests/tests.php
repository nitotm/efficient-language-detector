<?php
/*  You can execute this file directly on the command line (*update the path)
    $ php efficient-language-detector/tests/tests.php

    Or open the file through a server in the Browser
*/

require_once __DIR__.'/TestClass.php';

$tests = new TestClass();
//$tests = new Nitotm\Eld\Tests\TestClass();

$GLOBALS['autoload_'] = isset($GLOBALS['autoload_']);

$tests->addTest('Load ELD and create instance', function () {
    if ( ! $GLOBALS['autoload_']) {
        require_once __DIR__.'/../src/LanguageDetector.php';
    }
    $eld = new Nitotm\Eld\LanguageDetector();

    if ( ! $eld) {
        throw new Exception("LanguageDetector() instance not created");
    }
}, true);

$tests->addTest('Simple language detection', function () {
    if ( ! $GLOBALS['autoload_']) {
        require_once __DIR__.'/../src/LanguageDetector.php';
    }
    $eld = new Nitotm\Eld\LanguageDetector();

    $result = $eld->detect('Hola, cómo te llamas?');

    if ( ! isset($result['language']) || $result['language'] !== 'es') {
        throw new Exception("Expected: 'es', but got: ".($result['language'] ?? ''));
    }
});

$tests->addTest('Get scores of multiple languages', function () {
    if ( ! $GLOBALS['autoload_']) {
        require_once __DIR__.'/../src/LanguageDetector.php';
    }
    $eld               = new Nitotm\Eld\LanguageDetector();
    $eld->returnScores = true;

    $result = $eld->detect('How are you? Bien, gracias');

    if ( ! isset($result['scores']) || count($result['scores']) < 2) {
        throw new Exception(
            "Expected: >1 scores, but got: ".(isset($result['scores']) ? count($result['scores']) : '0')
        );
    }
});

$tests->addTest('Language detection, without minimum length', function () {
    if ( ! $GLOBALS['autoload_']) {
        require_once __DIR__.'/../src/LanguageDetector.php';
    }
    $eld = new Nitotm\Eld\LanguageDetector();

    $result = $eld->detect('To', false, false, 0, 1);

    if ( ! isset($result['language']) || $result['language'] !== 'en') {
        throw new Exception("Expected: 'en', but got: ".($result['language'] ?? ''));
    }
});

$tests->addTest('Detection, test error minimum length', function () {
    if ( ! $GLOBALS['autoload_']) {
        require_once __DIR__.'/../src/LanguageDetector.php';
    }
    $eld = new Nitotm\Eld\LanguageDetector();

    $result = $eld->detect('To');

    if ( ! isset($result['language']) || $result['language'] !== false) {
        throw new Exception("Expected: false, but got: ".($result['language'] ?? ''));
    }
});

$tests->addTest('Clean text', function () {
    if ( ! $GLOBALS['autoload_']) {
        require_once __DIR__.'/../src/LanguageDetector.php';
    }
    $eld = new Nitotm\Eld\LanguageDetector();

    $text = "https://www.google.com/\n".
        "mail@gmail.com\n".
        "google.com/search?q=search&source=hp\n".
        "12345 A12345\n";

    $result = trim($eld->cleanTxt($text));

    if ($result !== '') {
        throw new Exception("Expected: empty string, but got ".$result);
    }
});

$tests->addTest('Check minimum confidence', function () {
    if ( ! $GLOBALS['autoload_']) {
        require_once __DIR__.'/../src/LanguageDetector.php';
    }
    $eld = new Nitotm\Eld\LanguageDetector('ngrams-m.php');

    $result = $eld->detect('zxz zcz zvz zbz znz zmz zlz zsz zdz zkz zjz pelo', false, true, 0, 1);

    if ( ! isset($result['language']) || $result['language'] !== false) {
        throw new Exception("Expected: false, but got: ".($result['language'] ?? ''));
    }
});

$tests->addTest('Create dynamicLangSubset(), and detect', function () {
    if ( ! $GLOBALS['autoload_']) {
        require_once __DIR__.'/../src/LanguageDetector.php';
    }
    $eld               = new Nitotm\Eld\LanguageDetector();
    $eld->returnScores = true;

    $langSubset = ['en'];
    $eld->dynamicLangSubset($langSubset);

    $result = $eld->detect('How are you? Bien, gracias');

    if ( ! isset($result['scores']) || count($result['scores']) !== 1) {
        throw new Exception(
            "Expected: 1 score, but got: ".
            (isset($result['scores']) ? count($result['scores']) : '0')
        );
    }
});

$tests->addTest('Create dynamicLangSubset(), disable it, and detect', function () {
    if ( ! $GLOBALS['autoload_']) {
        require_once __DIR__.'/../src/LanguageDetector.php';
    }
    $eld               = new Nitotm\Eld\LanguageDetector();
    $eld->returnScores = true;

    $langSubset = ['en'];
    $eld->dynamicLangSubset($langSubset);
    $eld->dynamicLangSubset(false);

    $result = $eld->detect('How are you? Bien, gracias');

    if ( ! isset($result['scores']) || count($result['scores']) < 2) {
        throw new Exception(
            "Expected: >1 scores, but got: ".(isset($result['scores']) ? count($result['scores']) : '0')
        );
    }
});

$tests->addTest('Create langSubset(), and detect', function () {
    if ( ! $GLOBALS['autoload_']) {
        require_once __DIR__.'/../src/LanguageDetector.php';
    }
    $eld               = new Nitotm\Eld\LanguageDetector();
    $eld->returnScores = true;

    $langSubset = ['en'];
    $eld->langSubset($langSubset);

    $result = $eld->detect('How are you? Bien, gracias');

    if ( ! isset($result['scores']) || count($result['scores']) !== 1) {
        throw new Exception(
            "Expected: 1 score, but got: ".
            (isset($result['scores']) ? count($result['scores']) : '0')
        );
    }
});

$tests->addTest('Create langSubset(), disable it, and detect', function () {
    if ( ! $GLOBALS['autoload_']) {
        require_once __DIR__.'/../src/LanguageDetector.php';
    }
    $eld               = new Nitotm\Eld\LanguageDetector();
    $eld->returnScores = true;

    $langSubset = ['en'];
    $eld->langSubset($langSubset);
    $eld->langSubset(false);

    $result = $eld->detect('How are you? Bien, gracias');

    if ( ! isset($result['scores']) || count($result['scores']) < 2) {
        throw new Exception(
            "Expected: >1 scores, but got: ".(isset($result['scores']) ? count($result['scores']) : '0')
        );
    }
});

$tests->addTest('Check if langSubset() is able to save subset file', function () {
    if ( ! $GLOBALS['autoload_']) {
        require_once __DIR__.'/../src/LanguageDetector.php';
    }
    $eld  = new Nitotm\Eld\LanguageDetector();
    $file = __DIR__.'/../src/ngrams/ngrams.17ba0791499db908433b80f37c5fbc89b870084b.php';

    // Should already exist
    if ( ! file_exists($file)) {
        throw new Exception("File /src/ngrams/ngrams.17ba0791499... not found");
    }

    if ( ! unlink($file)) {
        throw new Exception("ABORTED: Unable to delete ngrams.17ba0791... file; Not an ELD error");
    }

    $langSubset = ['en'];
    $eld->langSubset($langSubset);

    if ( ! file_exists($file)) {
        throw new Exception("File /src/ngrams/ngrams.17ba0791499... not found");
    }
});

$tests->addTest('Create instance with diferent ngrams database, and detect', function () {
    if ( ! $GLOBALS['autoload_']) {
        require_once __DIR__.'/../src/LanguageDetector.php';
    }
    $eld = new Nitotm\Eld\LanguageDetector('ngrams.2f37045c74780aba1d36d6717f3244dc025fb935.php');

    $result = $eld->detect('Hola, cómo te llamas?');

    if ( ! isset($result['language']) || $result['language'] !== 'es') {
        throw new Exception("Expected: 'es', but got: ".($result['language'] ?? ''));
    }
});

$tests->addTest("Testing accuracy: ngrams-m.php database, for big-test.txt", function () {
    if ( ! $GLOBALS['autoload_']) {
        require_once __DIR__.'/../src/LanguageDetector.php';
    }

    $eld     = new Nitotm\Eld\LanguageDetector('ngrams-m.php');
    $total   = 0;
    $correct = 0;
    $handle  = fopen(__DIR__.'/../benchmarks/big-test.txt', "r");
    if ($handle) {
        while (($line = fgets($handle)) !== false) {
            $values = explode("\t", trim($line));
            if ($eld->detect($values[1], false, false, 0, 1)['language'] == $values[0]) {
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
        throw new Exception(
            "Accuracy too low. Expected 99.42%, but got: ".
            round(($correct / $total) * 100, 4).'%'
        );
    }
});

$tests->run();