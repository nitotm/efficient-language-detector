<?php
/**
 * @copyright 2023 Nito T.M.
 * @license https://www.apache.org/licenses/LICENSE-2.0 Apache-2.0
 * @author Nito T.M. (https://github.com/nitotm)
 * @package nitotm/efficient-language-detector
 */

print (PHP_SAPI === 'cli' ? '' : "<pre>") . PHP_EOL;

require_once 'src/LanguageDetector.php';
// use Nitotm\Eld\LanguageDetector;

$eld = new Nitotm\Eld\LanguageDetector();

// detect() expects a UTF-8 string, returns an object, with a value (ISO 639-1 code or null) named 'language'
var_dump($result = $eld->detect('Hola, cómo te llamas?'));
// object( language => 'es', scores => ['es' => 0.5, 'et' => 0.2], isReliable() => true )
// object( language => null|string, scores => null|array, isReliable() => bool )
var_dump($result->language);

// When cleanText(true) Removes Urls, .com domains, emails, alphanumerical & numbers
$eld->cleanText(true); // Default is false

/*
 To reduce the languages to be detected, there are 3 different options, they only need to be executed once.

 This is the complete list on languages for ELD v1, using ISO 639-1 codes:
 ['am', 'ar', 'az', 'be', 'bg', 'bn', 'ca', 'cs', 'da', 'de', 'el', 'en', 'es', 'et', 'eu', 'fa', 'fi', 'fr', 'gu',
 'he', 'hi', 'hr', 'hu', 'hy', 'is', 'it', 'ja', 'ka', 'kn', 'ko', 'ku', 'lo', 'lt', 'lv', 'ml', 'mr', 'ms', 'nl',
 'no', 'or', 'pa', 'pl', 'pt', 'ro', 'ru', 'sk', 'sl', 'sq', 'sr', 'sv', 'ta', 'te', 'th', 'tl', 'tr', 'uk', 'ur',
 'vi', 'yo', 'zh']
*/
$langSubset = ['en', 'es', 'fr', 'it', 'nl', 'de'];

// dynamicLangSubset() Will execute the detector normally, but at the end will filter the excluded languages.
$eld->dynamicLangSubset($langSubset);
// Returns an object with an ?array property named 'languages', with the validated languages

// to remove the subset
$eld->dynamicLangSubset();

/*
 langSubset($langs, save: true, encode: true) Will previously remove the excluded languages form the Ngrams database
 for a single detection might be slower than dynamicLangSubset(), but for several strings will be faster.
 If $save option is true, default, the new ngrams subset will be stored and cached for next time.
 $encode=true, default, stores Ngram bytes hex encoded for safety.
*/
var_dump($eld->langSubset($langSubset)); // returns subset file name if saved
// Object ( success => bool, languages => null|[], error => null|string, file => null|string )

// to remove the subset
$eld->langSubset();

/*
 Finally the optimal way to regularly use the same language subset, will be to add as an argument the file stored
 (and returned) by langSubset(), when creating an instance of the class. In this case the subset Ngrams database will
 be loaded directly, and not the default database. Also, you can use this option to load different ngram databases
 stored at resources/
 */
$elds = new Nitotm\Eld\LanguageDetector('ngramsM60-6.5ijqhj4oecso0kwcok4k4kgoscwg80o.php');

print (PHP_SAPI === 'cli' ? '' : "</pre>");
