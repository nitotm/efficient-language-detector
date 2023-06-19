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
// This file matches the trained data of the Ngrams database, for new trained databases this file has to be updated.
declare(strict_types=1);

namespace Nitotm\Eld;

require_once __DIR__ . '/LanguageSubset.php';

class LanguageData extends LanguageSubset
{
    protected $ngrams;

    // ISO 639-1 codes
    protected $langCodes
        = [
            'am', 'ar', 'az', 'be', 'bg', 'bn', 'ca', 'cs', 'da', 'de', 'el', 'en', 'es', 'et', 'eu', 'fa', 'fi', 'fr',
            'gu', 'he', 'hi', 'hr', 'hu', 'hy', 'is', 'it', 'ja', 'ka', 'kn', 'ko', 'ku', 'lo', 'lt', 'lv', 'ml', 'mr',
            'ms', 'nl', 'no', 'or', 'pa', 'pl', 'pt', 'ro', 'ru', 'sk', 'sl', 'sq', 'sr', 'sv', 'ta', 'te', 'th', 'tl',
            'tr', 'uk', 'ur', 'vi', 'yo', 'zh'
        ];

    /*
      ['Amharic', 'Arabic', 'Azerbaijani (Latin)', 'Belarusian', 'Bulgarian', 'Bengali', 'Catalan', 'Czech', 'Danish',
      'German', 'Greek', 'English', 'Spanish', 'Estonian', 'Basque', 'Persian', 'Finnish', 'French', 'Gujarati',
      'Hebrew', 'Hindi', 'Croatian', 'Hungarian', 'Armenian', 'Icelandic', 'Italian', 'Japanese', 'Georgian',
      'Kannada', 'Korean', 'Kurdish (Arabic)', 'Lao', 'Lithuanian', 'Latvian', 'Malayalam', 'Marathi', 'Malay (Latin)',
      'Dutch', 'Norwegian', 'Oriya', 'Punjabi', 'Polish', 'Portuguese', 'Romanian', 'Russian', 'Slovak', 'Slovene',
      'Albanian', 'Serbian (Cyrillic)', 'Swedish', 'Tamil', 'Telugu', 'Thai', 'Tagalog', 'Turkish', 'Ukrainian',
      'Urdu', 'Vietnamese', 'Yoruba', 'Chinese'];
     */

    /* Deprecated for now.
      Some languages score higher with the same amount of text, this multiplier evens it out for multi-language strings
      protected $scoreNormalizer = [0.7, 1, 1, 1, 1, 0.6, 0.98, 1, 1, 1, 0.9, 1, 1, 1, 1, 1, 1, 1, 0.6, 1, 0.7, 1, 1,
      0.9, 1, 1, 0.8, 0.6, 0.6, 1, 1, 0.5, 1, 1, 0.6, 0.7, 1, 0.95, 1, 0.6, 0.6, 1, 1, 1, 1, 1, 1, 0.9, 1, 1, 0.6, 0.6,
      0.7, 0.9, 1, 1, 1, 0.8, 1, 1.7];
     */
    protected $langScore;
    protected $avgScore
        = [
            0.0661, 0.0237, 0.0269, 0.0227, 0.0234, 0.1373, 0.0246, 0.0242, 0.0277, 0.0275, 0.0369, 0.0378, 0.0252,
            0.0253, 0.0369, 0.0213, 0.026, 0.0253, 0.1197, 0.0402, 0.0578, 0.0201, 0.0208, 0.0439, 0.032, 0.0251,
            0.0375, 0.1383, 0.1305, 0.0222, 0.0256, 0.3488, 0.0246, 0.0264, 0.1322, 0.0571, 0.0251, 0.0342, 0.0266,
            0.1269, 0.1338, 0.0275, 0.0252, 0.0247, 0.0184, 0.024, 0.0253, 0.0353, 0.0234, 0.033, 0.1513, 0.1547,
            0.0882, 0.0368, 0.0258, 0.0206, 0.0282, 0.0467, 0.0329, 0.0152
        ];

    public function __construct(?string $ngramsFile = null)
    {
        // Opcache needs to be active, so the load of the database array does not add overhead.
        require __DIR__ . '/ngrams/' . ($ngramsFile ?? "ngrams-m.php");
        // Internal reference: _ngrams_newAddEnd4gramExtra_1-4_2824 + _ngrams_charUtf8_1-1_2291
        $this->langScore = array_fill(0, count($this->langCodes), 0);
    }
}
