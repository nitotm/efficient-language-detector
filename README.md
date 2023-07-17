# Efficient Language Detector

<div align="center">
	
![supported PHP versions](https://img.shields.io/badge/PHP-%3E%3D%207.4-blue)
[![license](https://img.shields.io/badge/license-Apache%202.0-blue.svg)](https://www.apache.org/licenses/LICENSE-2.0)
[![supported languages](https://img.shields.io/badge/supported%20languages-60-brightgreen.svg)](#languages)
	
</div>

Efficient language detector (*Nito-ELD* or *ELD*) is a fast and accurate natural language detection software, written in PHP, with a speed comparable to existent fast C++ compiled detectors, and accuracy within the range of the heaviest and slowest detectors.

It has no dependencies, 100% PHP, easy installation, all it's needed is PHP with the **mb** extension.  
ELD is also available in [Javascript](https://github.com/nitotm/efficient-language-detector-js) and [Python](https://github.com/nitotm/efficient-language-detector-py).

1. [Installation](#installation)
2. [How to use](#how-to-use)
3. [Benchmarks](#benchmarks)
4. [Testing](#testing)
5. [Languages](#languages)

## Installation

```bash
$ composer require nitotm/efficient-language-detector
```
Alternatively, download / clone the files will work just fine.

## How to use?

`detect()` expects a UTF-8 string, returns an object, with a value (*ISO 639-1 code* or `null`) named `language`
```php
// require_once 'src/LanguageDetector.php'; To load ELD without composer/autoload. Update path.
$eld = new Nitotm\Eld\LanguageDetector();

var_dump($eld->detect('Hola, cómo te llamas?'));
// object( language => 'es', scores => ['es' => 0.5, 'et' => 0.2], isReliable() => true )
// object( language => null|string, scores => null|array, isReliable() => bool )

print $eld->detect('Hola, cómo te llamas?')->language;
// 'es'

// cleanText(true): Removes Urls, .com domains, emails, alphanumerical & numbers; from input text
$eld->cleanText(true); // Default is false
```

- To reduce the languages to be detected, there are 3 different options, they only need to be executed once. (Check available [languages](#languages) below)
```php
$langSubset = ['en','es','fr','it','nl','de'];

/* Option #1
 With dynamicLangSubset() the detector executes normally, and then filters excluded languages
*/
$eld->dynamicLangSubset($langSubset);
// Returns an object with an ?array property named 'languages', with the validated languages

/* Option #2
 langSubset($langs, save: true) removes excluded languages from the Ngrams database
 For a single detection is slower than dynamicLangSubset(), but for several will be faster
 If $save option is true (default), the new Ngrams subset will be stored, and loaded next call
*/
var_dump($eld->langSubset($langSubset)); // returns the subset file name if saved
// Object ( success => bool, languages => null|[], error => null|string, file => null|string )

// To remove either dynamicLangSubset() or langSubset(), call the methods without any argument
$eld->langSubset(); 

/* Option #3
 Finally the optimal way to regularly use a language subset: we create the instance with a file
 The file in the argument can be a subset by langSubset() or another database like ngramsL60.php
*/
$eld_l = new Nitotm\Eld\LanguageDetector('ngramsL60.php');
```

## Benchmarks

I compared *ELD* with a different variety of detectors, since the interesting part is the algorithm.

| URL                                                      | Version       | Language     |
|:---------------------------------------------------------|:--------------|:-------------|
| https://github.com/nitotm/efficient-language-detector/   | 1.0.0         | PHP          |
| https://github.com/pemistahl/lingua-py                   | 1.3.2         | Python       |
| https://github.com/CLD2Owners/cld2                       | Aug 21, 2015  | C++          |
| https://github.com/google/cld3                           | Aug 28, 2020  | C++          |
| https://github.com/wooorm/franc                          | 6.1.0         | Javascript   |
| https://github.com/patrickschur/language-detection       | 5.2.0         | PHP          |

Benchmarks: **Tweets**: *760KB*, short sentences of 140 chars max.; **Big test**: *10MB*, sentences in all 60 languages supported; **Sentences**: *8MB*, this is the *Lingua* sentences test, minus unsupported languages.  
Short sentences is what *ELD* and most detectors focus on, as very short text is unreliable, but I included the *Lingua* **Word pairs** *1.5MB*, and **Single words** *880KB* tests to see how they all compare beyond their reliable limits.

These are the results, first, execution time and then accuracy.

<!--- Time table
|                     | Tweets       | Big test     | Sentences    | Word pairs   | Single words |
|:--------------------|:------------:|:------------:|:------------:|:------------:|:------------:|
| **Nito-ELD**        |     0.31"    |      2.5"    |      2.2"    |     0.66"    |     0.48"    |
| **Nito-ELD-L**      |     0.33"    |      2.6"    |      2.3"    |     0.68"    |     0.50"    |
| **Lingua**          |  4790"       |  24000"      |  18700"      |  8450"       |  6700"       |
| **CLD2**            |     0.35"    |      2"      |      1.7"    |     0.98"    |     0.8"     |
| **Lingua low**      |    64"       |    370"      |    308"      |   108"       |    85"       |
| **CLD3**            |     3.9"     |     29"      |     26"      |    12"       |    11"       |
| **franc**           |     1.2"     |      8"      |      7.8"    |     2.8"     |     2"       |
| **patrickschur**    |    15"       |     93"      |     82"      |    40"       |    35"       |
-->
<img alt="time table" width="800" src="https://raw.githubusercontent.com/nitotm/efficient-language-detector/main/misc/table_time.svg">

<!-- Accuracy table
|                     | Tweets       | Big test     | Sentences    | Word pairs   | Single words |
|:--------------------|:------------:|:------------:|:------------:|:------------:|:------------:|
| **Nito-ELD**        | 99.3%        | 99.4%        | 98.8%        | 87.6%        | 73.3%        |
| **Nito-ELD-L**      | 99.4%        | 99.4%        | 98.7%        | 89.4%        | 76.1%        |
| **Lingua**          | 98.8%        | 99.1%        | 98.6%        | 93.1%        | 80.0%        |
| **CLD2**            | 93.8%        | 97.2%        | 97.2%        | 87.7%        | 69.6%        |
| **Lingua low**      | 96.0%        | 97.2%        | 96.3%        | 83.7%        | 68.0%        |
| **CLD3**            | 92.2%        | 95.8%        | 94.7%        | 69.0%        | 51.5%        |
| **franc**           | 89.8%        | 92.0%        | 90.5%        | 65.9%        | 52.9%        |
| **patrickschur**    | 89.7%        | 82.0%        | 87.4%        | 66.7%        | 52.9%        |
-->
<img alt="accuracy table" width="800" src="https://raw.githubusercontent.com/nitotm/efficient-language-detector/main/misc/table_accuracy.svg">

<sup style="color:#08e">1.</sup> <sup style="color:#777">Lingua could have a small advantage as it participates with 54 languages, 6 less.</sup>  
<sup style="color:#08e">2.</sup> <sup style="color:#777">CLD2 and CLD3, return a list of languages, the ones not included in this test where discarded, but usually they return one language, I believe they have a disadvantage. 
Also, I confirm the results of CLD2 for short text are correct, contrary to the test on the *Lingua* page, they did not use the parameter "bestEffort = True", their benchmark for CLD2 is unfair.

*Lingua* is the average accuracy winner, but at what cost, the same test that in *ELD* or *CLD2* lasts 2 seconds, in Lingua takes more than 5 hours! It acts like a brute-force software. 
Also, its lead comes from single and pair words, which are unreliable regardless.

I added *ELD-L* for comparison, which has a 2.3x bigger database, but only increases execution time marginally, a testament to the efficiency of the algorithm. *ELD-L* is not the main database as it does not improve language detection in sentences.

Here is the average, per benchmark, of Tweets, Big test & Sentences.

![Sentences tests average](https://raw.githubusercontent.com/nitotm/efficient-language-detector/main/misc/sentences-tests-average.png)
<!--- Sentences average
|                     | Time         | Accuracy     |
|:--------------------|:------------:|:------------:|
| **Nito-ELD**        |      1.65"   | 99.16%       |
| **Lingua**          |  15800"      | 98.84%       |
| **CLD2**            |      1.35"   | 96.08%       |
| **Lingua low**      |    247"      | 96.51%       |
| **CLD3**            |     19.6"    | 94.19%       |
| **franc**           |      5.7"    | 90.79%       |
| **patrickschur**    |     63"      | 86.36%       |
-->

## Testing

- To make sure everything works on your setup, you can execute the following file:
```bash
$ php efficient-language-detector/tests/tests.php # Update path
```
- Also, for composer "autoload-dev", the following line will also execute the tests

```php
new Nitotm\Eld\Tests\TestsAutoload();
```
- To run the accuracy benchmarks run the `benchmark/bench.php` file .

## Languages

These are the *ISO 639-1 codes* of the 60 supported languages for *Nito-ELD* v1

> am, ar, az, be, bg, bn, ca, cs, da, de, el, en, es, et, eu, fa, fi, fr, gu, he, hi, hr, hu, hy, is, it, ja, ka, kn, ko, ku, lo, lt, lv, ml, mr, ms, nl, no, or, pa, pl, pt, ro, ru, sk, sl, sq, sr, sv, ta, te, th, tl, tr, uk, ur, vi, yo, zh


Full name languages:

> Amharic, Arabic, Azerbaijani (Latin), Belarusian, Bulgarian, Bengali, Catalan, Czech, Danish, German, Greek, English, Spanish, Estonian, Basque, Persian, Finnish, French, Gujarati, Hebrew, Hindi, Croatian, Hungarian, Armenian, Icelandic, Italian, Japanese, Georgian, Kannada, Korean, Kurdish (Arabic), Lao, Lithuanian, Latvian, Malayalam, Marathi, Malay (Latin), Dutch, Norwegian, Oriya, Punjabi, Polish, Portuguese, Romanian, Russian, Slovak, Slovene, Albanian, Serbian (Cyrillic), Swedish, Tamil, Telugu, Thai, Tagalog, Turkish, Ukrainian, Urdu, Vietnamese, Yoruba, Chinese


## Future improvements

- Train from bigger datasets, and more languages.
- The tokenizer could separate characters from languages that have their own alphabet, potentially improving accuracy and reducing the N-grams database. Retraining and testing is needed.

**Donate / Hire**   
If you wish to Donate for open source improvements, Hire me for private modifications / upgrades, or to Contact me, use the following link: https://linktr.ee/nitotm