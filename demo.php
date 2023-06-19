<?php
/*
Copyright 2019 Nito T.M.
Author URL: https://github.com/nitotm

Licensed under the Apache License, Version 2.0 (the "License");
you may not use this file except in compliance with the License.
You may obtain a copy of the License at

    http://www.apache.org/licenses/LICENSE-2.0

Unless required by applicable law or agreed to in writing, software
distributed under the License is distributed on an "AS IS" BASIS,
WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
See the License for the specific language governing permissions and
limitations under the License.
*/

print (PHP_SAPI === 'cli' ? '' : "<pre>") . PHP_EOL;

require_once 'src/LanguageDetector.php';
// use Nitotm\Eld\LanguageDetector;

$eld = new Nitotm\Eld\LanguageDetector;

// detect() expects a UTF-8 string, returns an array, with a value (ISO 639-1 code or false) named 'language'
var_dump($eld->detect('Hola, cómo te llamas?'));
// ['language' => 'es'];
// ['language' => false, 'error' => 'Some error', 'scores'=>[]];


// To get the best guess, turn off minimum length, confidence threshold; also used for benchmarking.
var_dump($eld->detect('To', false, false, 0, 1));

/*
 To improve readability moving forward, PHP8 Named Parameters can be used
 print_r($eld->detect(text: 'To', cleanText: false, checkConfidence: false, minByteLength: 12, minNgrams: 3));
 cleanText: true, Removes Urls, domains, emails, alphanumerical & numbers
*/

// To retrieve the whole list of languages detected and their score, we will set $returnScores to True, just once
$eld->returnScores = true;
var_dump($eld->detect('How are you? Bien, gracias'));
// ['language' => 'en', 'scores' => ['en' => 0.32, 'es' => 0.31, ...]];

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
// to remove the subset
$eld->dynamicLangSubset(false);

/*
 langSubset($langs, save: true, safe: false) Will previously remove the excluded languages form the Ngrams database;
 for a single detection might be slower than dynamicLangSubset(), but for several strings will be faster.
 If $save option is true, default, the new ngrams subset will be stored, and next loaded for the same language subset,
 increasing startup speed. Use $safe=true to store Ngram bytes hex encoded.
*/
$eld->langSubset($langSubset); // returns subset file name if saved
// to remove the subset
$eld->langSubset(false);

/*
 Finally the fastest option to regularly use the same language subset, will be to add as an argument the file stored
 (and returned) by langSubset(), when creating an instance of the class. In this case the subset Ngrams database will
 be loaded directly, and not the default database. Also, you can use this option to load different ngram databases
 stored at src/ngrams/
 */
$elds = new Nitotm\Eld\LanguageDetector('ngrams.2f37045c74780aba1d36d6717f3244dc025fb935.php');

print (PHP_SAPI === 'cli' ? '' : "</pre>");
