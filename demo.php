<?php
/**
 * @copyright 2025 Nito T.M.
 * @license https://www.apache.org/licenses/LICENSE-2.0 Apache-2.0
 * @author Nito T.M. (https://github.com/nitotm)
 * @package nitotm/efficient-language-detector
 */

echo (PHP_SAPI === 'cli' ? '' : "<pre>") . PHP_EOL;

require_once __DIR__ . '/manual_loader.php';
// require __DIR__ . '/vendor/autoload.php';

use Nitotm\Eld\LanguageDetector;
use Nitotm\Eld\EldDataFile; // not mandatory
use Nitotm\Eld\EldScheme; // not mandatory
use Nitotm\Eld\EldMode; // not mandatory

// LanguageDetector(databaseFile: ?string, outputFormat: ?string, mode: string)
$eld = new LanguageDetector(EldDataFile::SMALL, EldScheme::ISO639_1, EldMode::MODE_ARRAY); // Default file and format
// Database files: 'small', 'medium', 'large', 'extralarge'. Check memory requirements at README
// Language schemess: 'ISO639_1', 'ISO639_2T', 'ISO639_1_BCP47', 'ISO639_2T_BCP47' and 'FULL_TEXT'
// Database modes: 'array', 'string', 'bytes', 'disk'.
// Argument constants are not mandatory, LanguageDetector('small', 'ISO639_1', 'array'); will also work
// outputFormat: parameter to be named outputScheme: on v4

/*
 detect() expects a UTF-8 string, returns an object with a 'language' property, with an ISO 639-1 code (or other
 selected scheme), or 'und' for undetermined language.
*/
var_dump($result = $eld->detect('Hola, cómo te llamas?'));
// object( language => string, scores() => array<string, float>, isReliable() => bool )
// ( language => 'es', scores() => ['es' => 0.25, 'nl' => 0.05], isReliable() => true )
// ( language => 'und', scores() => [], isReliable() => false )

var_dump($result->language); // 'es'

/*
 In array mode the first call takes longer as it creates a new database, if save enabled (default), it will be loaded
  next time we make the same subset.
 In modes string, bytes & disk, a "virtual" subset is created instantly, detect() will just remove unwanted languages
  before returning results.
 It accepts any ISO codes.
 langSubset(languages: [], save: true, encode: true)
*/
var_dump($eld->langSubset(['en', 'es', 'fr', 'it', 'nl', 'de'])); // returns subset file name if saved
// Object ( success => bool, languages => ?array, error => ?string, file => ?string )
// ( success => true, languages => ['en', 'es', ...], error => NULL, file => 'small_6_mfss5...' )

// to remove the subset
$eld->langSubset();

/*
 To load a subset with 0 overhead, we can feed the returned file by langSubset() in array mode, when creating the
  instance LanguageDetector(file)
 To make use of pre-built subsets in modes string, bytes & disk, getting lower memory usage and increased speed, it is
  possible by manually converting an array database, using BlobDataBuilder()
*/
$eld_subset = new Nitotm\Eld\LanguageDetector('small_6_mfss5z1t');

/*
 This is the complete list on languages for ELD v3, using ISO 639-1 codes:
 ['am', 'ar', 'az', 'be', 'bg', 'bn', 'ca', 'cs', 'da', 'de', 'el', 'en', 'es', 'et', 'eu', 'fa', 'fi', 'fr', 'gu',
 'he', 'hi', 'hr', 'hu', 'hy', 'is', 'it', 'ja', 'ka', 'kn', 'ko', 'ku', 'lo', 'lt', 'lv', 'ml', 'mr', 'ms', 'nl',
 'no', 'or', 'pa', 'pl', 'pt', 'ro', 'ru', 'sk', 'sl', 'sq', 'sr', 'sv', 'ta', 'te', 'th', 'tl', 'tr', 'uk', 'ur',
 'vi', 'yo', 'zh']
*/

// enableTextCleanup(true) Removes Urls, .com domains, emails, alphanumerical & numbers. Default is 'false'
// Not recommended as urls, domains, etc. may be related to a language, and ELD is trained without "cleaning"
$eld->enableTextCleanup(true); // Only needs to be set once to apply to all subsequent detect()

// We can also change the output scheme, returns true on success
$eld->setOutputScheme('FULL_TEXT');

// If needed, we can get some info of the ELD instance: languages, database type, etc.
var_dump($eld_subset->info());

echo(PHP_SAPI === 'cli' ? '' : "</pre>");
