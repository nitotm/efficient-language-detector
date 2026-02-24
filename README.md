# Efficient Language Detector

<div align="center">
	
![supported PHP versions](https://img.shields.io/badge/PHP-%3E%3D%207.4-blue)
[![license](https://img.shields.io/badge/license-Apache%202.0-blue.svg)](https://www.apache.org/licenses/LICENSE-2.0)
[![supported languages](https://img.shields.io/badge/supported%20languages-60-brightgreen.svg)](#languages)
![version](https://img.shields.io/badge/ver.-3.0-blue)
	
</div>

Efficient language detector (*Nito-ELD* or *ELD*) is a fast and accurate natural language detection software, written 100% in PHP, with a speed comparable to fast C++ compiled detectors, and accuracy rivaling the best detectors to date.

It has no dependencies, easy installation, all it's needed is PHP with the **mb** extension.  
ELD scales perfectly with database size.  
ELD is also available (outdated versions) in [Javascript](https://github.com/nitotm/efficient-language-detector-js) and [Python](https://github.com/nitotm/efficient-language-detector-py).

1. [Installation](#installation)
2. [How to use](#how-to-use)
3. [Benchmarks](#benchmarks)
4. [Databases](#databases)
5. [Testing](#testing)
6. [Languages](#languages)

## Installation

```bash
$ composer require nitotm/efficient-language-detector
```
- `--prefer-dist` will omit *tests/*, *misc/* & *benchmark/*, or use `--prefer-source` to include everything  
- Install `nitotm/efficient-language-detector:dev-main` to try the last unstable changes  
- Alternatively, download / clone the files can work just fine.  
(Only *small* DB install under construction)  


### ELD execution options

- ELD has database execution Modes: `array`, `string`, `bytes`, `disk`  
- ELD database Sizes: `small`, `medium`, `large`, `extralarge` 
- Confused? No worries, use default with no arguments. [How to use](#how-to-use)

**Low memory Modes**  
For Modes `string`, `bytes` and `disk`, all size databases can run with *128MB* PHP default setting  
`disk` mode with `extralarge` size, can run with almost no RAM as it only uses 0.5MB  
`string` and `bytes` are a great choice for general use, as they are just ~2x slower than `array`  

**Fastest Mode: Array** (higher memory usage)  
For `array` Mode it is recommended to use OPcache, specially for the larger databases to reduce load times  
We need to set `opcache.interned_strings_buffer`, `opcache.memory_consumption` high enough for each database  

Check [Databases](#databases) for more info.  

## How to use?

`detect()` expects a UTF-8 string and returns an object with a `language` property, containing an *ISO 639-1* code (or other selected scheme), or `'und'` for undetermined language.
```php
use Nitotm\Eld\LanguageDetector;

$eld = new LanguageDetector(); // Default Size: 'large', Mode: 'string' (since v3.2)

$eld->detect('Hola, cómo te llamas?');
// object( language => string, scores() => array<string, float>, isReliable() => bool )
// ( language => 'es', scores() => ['es' => 0.25, 'nl' => 0.05], isReliable() => true )

$eld->detect('Hola, cómo te llamas?')->language;
// 'es'
```
To select database **Size** and **Mode**, or language output scheme. We can import `Eld...` constants to see avalible options.
```php
use Nitotm\Eld\{LanguageDetector, EldDataFile, EldScheme, EldMode};

// LanguageDetector(databaseFile: ?string, outputFormat: ?string, mode: string)
$eld = new LanguageDetector(EldDataFile::SMALL, EldScheme::ISO639_1, EldMode::MODE_ARRAY);

// Database Size files: 'small', 'medium', 'large', 'extralarge'.
// Schemes: 'ISO639_1', 'ISO639_2T', 'ISO639_1_BCP47', 'ISO639_2T_BCP47' and 'FULL_TEXT'
// Database Modes: 'array', 'string', 'bytes', 'disk'. Check memory requirements for 'array'
// Constants are not mandatory, LanguageDetector('small'); will also work
```

#### Languages subsets

Calling `langSubset()` once, will set the subset.  
- In `array` mode the first call takes longer as it creates a new database, if save enabled (default), it will be loaded next time we make the same subset.  
- In modes `string`, `bytes` & `disk`, a "virtual" subset is created instantly, `detect()` will just remove unwanted languages before returning results.  
- To load a subset with 0 overhead, we can feed the returned file by `langSubset()` in `array` mode, when creating the instance `LanguageDetector(file)`  
  - To make use of pre-built subsets in modes `string`, `bytes` & `disk`, getting lower memory usage and increased speed, it is possible by manually converting an `array` database, using `BlobDataBuilder()` 
- Check available [Languages](#languages) below.
```php
// It accepts any ISO codes. In "array" mode it will return a subset file name, if saved
// langSubset(languages: [], save: true, encode: true);
$eld->langSubset(['en', 'es', 'fr', 'it', 'nl', 'de']);
// Object ( success => bool, languages => ?array, error => ?string, file => ?string )
// ( success => true, languages => ['en', 'es'...], error => NULL, file => 'small_6_mfss...' )

// to remove the subset
$eld->langSubset();

// Load pre-saved subset directly, just like a default database
$eld_subset = new Nitotm\Eld\LanguageDetector('small_6_mfss5z1t', null, 'array');

// Build a binary database for modes 'string', 'bytes' & 'disk', from any 'array' database
// Memory requirements for 'array' database input apply 
$eldBuilder = new Nitotm\Eld\BlobDataBuilder('large'); // or subset 'small_6_mfss5z1t'
// Create subset directly: new BlobDataBuilder('extralarge', ['en', 'es', 'de', 'it']);
$eldBuilder->buildDatabase();
```

#### Other Functions

```php
// if enableTextCleanup(True), detect() removes Urls, .com domains, emails, alphanumerical...
// Not recommended, as urls & domains contain hints of a language, which might help accuracy
$eld->enableTextCleanup(true); // Default is false

// If needed, we can get info of the ELD instance: languages, database type, etc.
$eld->info();

// Change output scheme on demand
// 'ISO639_1', 'ISO639_2T', 'ISO639_1_BCP47', 'ISO639_2T_BCP47', 'FULL_TEXT'
$eld->setOutputScheme('ISO639_2T'); // returns bool true on success
```
There is a CLI wrapper (BETA version)  
`>./bin/eld --help` on Linux.  
`>php bin/eld --help` on Windows.

## Benchmarks

I compared *ELD* with a different variety of detectors, as there are not many in PHP.

| URL                                                      | Version      | Language     |
|:---------------------------------------------------------|:-------------|:-------------|
| https://github.com/nitotm/efficient-language-detector/   | 3.1.0        | PHP          |
| https://github.com/pemistahl/lingua-py                   | 2.0.2        | Python       |
| https://github.com/facebookresearch/fastText             | 0.9.2        | C++          |
| https://github.com/CLD2Owners/cld2                       | Aug 21, 2015 | C++          |
| https://github.com/patrickschur/language-detection       | 5.3.0        | PHP          |
| https://github.com/wooorm/franc                          | 7.2.0        | Javascript   |


Benchmarks:
* **Tatoeba**: *20MB*, short sentences from Tatoeba, 50 languages supported by all contenders, up to 10k lines each.  
> * For Tatoeba, I limited all detectors to the 50 languages subset, making the comparison as fair as possible.  
> * Also, Tatoeba is not part of **ELD** training dataset (nor tuning), but it is for **fasttext**  
* **ELD Test**: *10MB*, sentences from the 60 languages supported by ELD, 1000 lines each. Extracted from the 60GB of ELD training data.    
* **Sentences**: *8MB*, sentences from *Lingua* benchmark, minus unsupported languages and Yoruba which had broken characters.  
* **Word pairs** *1.5MB*, and **Single words** *870KB*, also from Lingua, same 53 languages.

<!--- Time table
|                       | Tatoeba-50   | ELD test     | Sentences    | Word pairs   | Single words |
|:----------------------|:------------:|:------------:|:------------:|:------------:|:------------:|
| **Nito-ELD-S-array**  |     4.7"     |      1.6"    |      1.4"    |     0.44"    |     0.34"    |
| **Nito-ELD-M-array**  |     5.2"     |      1.8"    |      1.5"    |     0.47"    |     0.37"    |
| **Nito-ELD-L-array**  |     4.4"     |      1.5"    |      1.2"    |     0.41"    |     0.33"    |
| **Nito-ELD-L-string** |     9.5"     |      3.5"    |      2.9"    |     0.68"    |     0.48"    |
| **Nito-ELD-L-bytes**  |     9.5"     |      3.5"    |      2.9"    |     0.68"    |     0.48"    |
| **Nito-ELD-L-disk**   |    58"       |     25"      |     20"      |     3.9"     |     2.3"     |
| **Nito-ELD-XL-array** |     4.7"     |      1.6"    |      1.3"    |     0.44"    |     0.34"    |
| **Nito-ELD-XL-string**|    11"       |      4.0"    |      3.4"    |     0.77"    |     0.53"    |
| **Nito-ELD-XL-bytes** |    11"       |      4.0"    |      3.4"    |     0.77"    |     0.53"    |
| **Nito-ELD-XL-disk**  |    65"       |     27"      |     22"      |     4.4"     |     2.6"     |
| **Lingua**            |    98"       |     27"      |     24"      |     8.2"     |     5.9"     |
| **fasttext-subset**   |    12"       |      2.7"    |      2.3"    |     1.2"     |     1.1"     |
| **fasttext-all**      |     --       |      2.4"    |      2.0"    |     0.91"    |     0.73"    |
| **CLD2**              |     3.5"     |      0.71"   |      0.59"   |     0.35"    |     0.32"    |
| **Lingua-low**        |    37"       |     13"      |     11"      |     3.0"     |     2.3"     |
| **patrickschur**      |   227"       |     74"      |     63"      |    18"       |    11"       |
| **franc**             |    43"       |     10"      |      9"      |     4.1"     |     3.2"     |
-->
Time execution benchmark for ELD size `large` ( check others sizes at <a href="https://raw.githubusercontent.com/nitotm/efficient-language-detector/main/misc/table_time_extra_v3.svg" target="_blank">more benchmarks</a> )  
<img alt="timetable" width="800" src="https://raw.githubusercontent.com/nitotm/efficient-language-detector/main/misc/table_time_v3.svg">

<!-- Accuracy table
|                     | Tatoeba-50 | ELD test     | Sentences    | Word pairs   | Single words |
|:--------------------|:----------:|:------------:|:------------:|:------------:|:------------:|
| **Nito-ELD-S**      |   97.2%    | 99.7%        | 99.2%        | 91.1%        | 75.5%        |
| **Nito-ELD-M**      |   98.0%    | 99.7%        | 99.3%        | 93.1%        | 80.4%        |
| **Nito-ELD-L**      |   98.7%    | 99.8%        | 99.4%        | 94.7%        | 83.4%        |
| **Nito-ELD-XL**     |   98.8%    | 99.8%        | 99.5%        | 95.3%        | 85.0%        |
| **Lingua**          |   96.1%    | 99.2%        | 98.7%        | 93.4%        | 80.7%        |
| **fasttext-subset** |   94.1%    | 98.0%        | 97.9%        | 83.1%        | 67.8%        |
| **fasttext-all**    |     --     | 97.4%        | 97.6%        | 81.5%        | 65.7%        |
| **CLD2** *          |   92.1% *  | 98.1%        | 97.4%        | 85.6%        | 70.7%        |
| **Lingua-low**      |    89.3    | 97.3%        | 96.3%        | 84.1%        | 68.6%        |
| **patrickschur**    |   84.1%    | 94.8%        | 93.6%        | 71.9%        | 57.1%        |
| **franc**           |   76.9%    | 93.8%        | 92.3%        | 67.0%        | 53.8%        |
-->
<img alt="accuracy table" width="800" src="https://raw.githubusercontent.com/nitotm/efficient-language-detector/main/misc/table_accuracy_v3.svg">  

* **Lingua** participates with 54 languages, **Franc** with 58, **patrickschur** with 54.  
* **fasttext** does not have a built-in subset option, so to show its accuracy and speed potential I made two benchmarks, fasttext-all not being limited by any subset at any test  
* <sup style="color:#08e">*</sup> Google's **CLD2** also lacks subset option, and it's difficult to make a subset even with its option `bestEffort = True`, as usually returns only one language, so it has a comparative disadvantage.
* Time is normalized: (total lines * time) / processed lines


## Databases

### Low memory database modes 

Modes `'bytes'` and `'string'` are very similar, they differ on how they are load, and are just 2x slower than **Array**  
Mode `'string'` can be OPcache'd, more expensive compilation, but then instant load, `'bytes'` has always a steady ~fast load  
Special mention to `'disk'` mode, while slower, is the fastest uncached load & detect for the larger databases

| Mode                     | Disk          | Bytes        | String      | Bytes        | String      |
|--------------------------|---------------|--------------|-------------|--------------|-------------|
| Database Size option     | Extralarge    | Extralarge   | Extralarge  | Large        | Large       |
| File size                | 39 MB         | 39 MB        | 39 MB       | 20 MB        | 20 MB       |
| Memory usage             | 0.4 MB        | 40 MB        | 40 MB       | 22 MB        | 22 MB       |
| Memory usage Cached      | 0.4 MB        | 40 MB        | 0.4 MB + OP | 22 MB        | 0.4 MB + OP |
| Memory peak              | 0.4 MB        | 40 MB        | 56 MB       | 22 MB        | 32 MB       |
| Memory peak Cached       | 0.4 MB        | 40 MB        | 0.4 MB + OP | 22 MB        | 0.4 MB + OP |
| OPcache used memory      | -             | -            | 39 MB       | -            | 20 MB       |
| OPcache used interned    | -             | -            | 0.4 MB      | -            | 0.4 MB      |
| Load & detect() Uncached | 0.0012 sec    | 0.04 sec     | 0.25 sec    | 0.02 sec     | 0.11 sec    |
| Load & detect() Cached   | 0.0011 sec    | 0.04 sec     | 0.0003 sec  | 0.02 sec     | 0.0003 sec  |

| Mode                     | Bytes         | String        | Bytes         | String        |
|--------------------------|---------------|---------------|---------------|---------------|
| Database Size option     | Medium        | Medium        | Small         | Small         |
| File size                | 6 MB          | 6 MB          | 2 MB          | 2 MB          |
| Memory usage             | 8 MB          | 8 MB          | 2 MB          | 2 MB          |
| Memory usage Cached      | 8 MB          | 0.4 MB + OP   | 2 MB          | 0.4 MB + OP   |
| Memory peak              | 8 MB          | 12 MB         | 2 MB          | 3 MB          |
| Memory peak Cached       | 8 MB          | 0.4 MB + OP   | 2 MB          | 0.4 MB + OP   |
| OPcache used memory      | -             | 0 MB          | -             | 0 MB          |
| OPcache used interned    | -             | 6 MB          | -             | 2 MB          |
| Load & detect() Uncached | 0.006 sec     | 0.04 sec      | 0.003 sec     | 0.016 sec     |
| Load & detect() Cached   | 0.006 sec     | 0.0003 sec    | 0.002 sec     | 0.0003 sec    |

### Fastest mode *Array*, but memory hungry

| Array Mode, Size:          | Small           | Medium             | Large         | Extralarge     |
|----------------------------|-----------------|--------------------|---------------|----------------|
| Pros                       | Lowest memory   | Equilibrated       | Fastest       | Most accurate  |
| Cons                       | Least accurate  | Slowest (but fast) | High memory   | Highest memory |
| File size                  | 3 MB            | 9 MB               | 28 MB         | 64 MB          |
| Memory usage               | 46 MB           | 137 MB             | 547 MB        | 1143 MB        |
| Memory usage Cached        | 0.4 MB + OP     | 0.4 MB + OP        | 0.4 MB + OP   | 0.4 MB + OP    |
| Memory peak                | 78 MB           | 287 MB             | 969 MB        | 2047 MB        |
| Memory peak Cached         | 0.4 MB + OP     | 0.4 MB + OP        | 0.4 MB + OP   | 0.4 MB + OP    |
| OPcache used memory        | 21 MB           | 70 MB              | 242 MB        | 516 MB         |
| OPcache used interned      | 4 MB            | 10 MB              | 45 MB         | 91 MB          |
| Load & detect() Uncached   | 0.13 sec        | 0.5 sec            | 1.4 sec       | 3.2 sec        |
| Load & detect() Cached     | 0.0003 sec      | 0.0003 sec         | 0.0003 sec    | 0.0003 sec     |
| **Settings** (Recommended) |                 |                    |               |                |
| `memory_limit`             | \>= 128         | \>= 340            | \>= 1060      | \>= 2200       |
| `opcache.interned...`\*    | \>= 8      (16) | \>= 16        (32) | \>= 60   (70) | \>= 116  (128) |
| `opcache.memory`           | \>= 64    (128) | \>= 128      (230) | \>= 360 (450) | \>= 750  (820) |

* \* I recommend using more than enough `interned_strings_buffer` as *buffers overflow* error might delay server response.  
To use *all* databases `opcache.interned_strings_buffer` should be a minimum of 160MB (170MB).  
* When choosing the amount of memory keep in mind `opcache.memory_consumption` includes `opcache.interned_strings_buffer`.  
  * If OPcache memory is 230MB, interned_strings is 32MB, and medium DB is 69MB cached, we have a total of (230 -32 -69) = 129MB of OPcache for everything else.  
* Also, if you are going to use a subset of languages in addition to the main database, or multiple subsets, increase `opcache.memory` accordingly if you want them to be loaded instantly.
To cache all default databases comfortably you would want to set it at 1200MB.

## Testing
Default composer install might not include these files. Use `--prefer-source` to include them.
- For *dev* environment with composer *"autoload-dev"* (root only), the following will execute the tests
```php
new Nitotm\Eld\Tests\TestsAutoload();
```
- Or, you can also run the tests executing the following file:
```bash
$ php efficient-language-detector/tests/tests.php # Update path
```
- To run the accuracy benchmarks run the `benchmark/bench.php` file.

## Languages

* These are the *ISO 639-1 codes* that include the 60 languages. Plus `'und'` for undetermined  
It is the default ELD language scheme. `outputScheme: 'ISO639_1'`

> am, ar, az, be, bg, bn, ca, cs, da, de, el, en, es, et, eu, fa, fi, fr, gu, he, hi, hr, hu, hy, is, it, ja, ka, kn, ko, ku, lo, lt, lv, ml, mr, ms, nl, no, or, pa, pl, pt, ro, ru, sk, sl, sq, sr, sv, ta, te, th, tl, tr, uk, ur, vi, yo, zh

* These are the 60 supported languages for *Nito-ELD*. `outputScheme: 'FULL_TEXT'`

> Amharic, Arabic, Azerbaijani (Latin), Belarusian, Bulgarian, Bengali, Catalan, Czech, Danish, German, Greek, English, Spanish, Estonian, Basque, Persian, Finnish, French, Gujarati, Hebrew, Hindi, Croatian, Hungarian, Armenian, Icelandic, Italian, Japanese, Georgian, Kannada, Korean, Kurdish (Arabic), Lao, Lithuanian, Latvian, Malayalam, Marathi, Malay (Latin), Dutch, Norwegian, Oriya, Punjabi, Polish, Portuguese, Romanian, Russian, Slovak, Slovene, Albanian, Serbian (Cyrillic), Swedish, Tamil, Telugu, Thai, Tagalog, Turkish, Ukrainian, Urdu, Vietnamese, Yoruba, Chinese

* *ISO 639-1 codes* with IETF BCP 47 script name tag. `outputScheme: 'ISO639_1_BCP47'`

> am, ar, az-Latn, be, bg, bn, ca, cs, da, de, el, en, es, et, eu, fa, fi, fr, gu, he, hi, hr, hu, hy, is, it, ja, ka, kn, ko, ku-Arab, lo, lt, lv, ml, mr, ms-Latn, nl, no, or, pa, pl, pt, ro, ru, sk, sl, sq, sr-Cyrl, sv, ta, te, th, tl, tr, uk, ur, vi, yo, zh

* *ISO 639-2/T* codes (which are also valid *639-3*) `outputScheme: 'ISO639_2T'`. Also available with BCP 47 `ISO639_2T_BCP47`

> amh, ara, aze, bel, bul, ben, cat, ces, dan, deu, ell, eng, spa, est, eus, fas, fin, fra, guj, heb, hin, hrv, hun, hye, isl, ita, jpn, kat, kan, kor, kur, lao, lit, lav, mal, mar, msa, nld, nor, ori, pan, pol, por, ron, rus, slk, slv, sqi, srp, swe, tam, tel, tha, tgl, tur, ukr, urd, vie, yor, zho
  
***

#### Donations and suggestions

If you wish to donate for open source improvements, hire me for private modifications, request alternative dataset training, or contact me, please use the following link: https://linktr.ee/nitotm