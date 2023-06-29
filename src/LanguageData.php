<?php
/**
 * @copyright 2023 Nito T.M.
 * @license https://www.apache.org/licenses/LICENSE-2.0 Apache-2.0
 * @author Nito T.M. (https://github.com/nitotm)
 * @package nitotm/efficient-language-detector
 */
// This file matches the trained data of the Ngrams database, for new trained databases this file has to be updated.
declare(strict_types=1);

namespace Nitotm\Eld;

use RuntimeException;

require_once __DIR__ . '/LanguageSubset.php';

class LanguageData extends LanguageSubset
{
    protected array $ngrams;
    protected array $langCodes;
    protected array $langScore;
    protected string $dataType;
    protected string $ngramsFolder = __DIR__ . '/../resources/ngrams/';
    /*
       ISO 639-1 codes, for 60 languages set.
       $langCodes = [
             'am', 'ar', 'az', 'be', 'bg', 'bn', 'ca', 'cs', 'da', 'de', 'el', 'en', 'es', 'et', 'eu', 'fa', 'fi', 'fr',
             'gu', 'he', 'hi', 'hr', 'hu', 'hy', 'is', 'it', 'ja', 'ka', 'kn', 'ko', 'ku', 'lo', 'lt', 'lv', 'ml', 'mr',
             'ms', 'nl', 'no', 'or', 'pa', 'pl', 'pt', 'ro', 'ru', 'sk', 'sl', 'sq', 'sr', 'sv', 'ta', 'te', 'th', 'tl',
             'tr', 'uk', 'ur', 'vi', 'yo', 'zh'
            ];

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

    protected array $avgScore
        = [
            'am' => 0.0661, 'ar' => 0.0237, 'az' => 0.0269, 'be' => 0.0227, 'bg' => 0.0234, 'bn' => 0.1373,
            'ca' => 0.0246, 'cs' => 0.0242, 'da' => 0.0277, 'de' => 0.0275, 'el' => 0.0369, 'en' => 0.0378,
            'es' => 0.0252, 'et' => 0.0253, 'eu' => 0.0369, 'fa' => 0.0213, 'fi' => 0.026, 'fr' => 0.0253,
            'gu' => 0.1197, 'he' => 0.0402, 'hi' => 0.0578, 'hr' => 0.0201, 'hu' => 0.0208, 'hy' => 0.0439,
            'is' => 0.032, 'it' => 0.0251, 'ja' => 0.0375, 'ka' => 0.1383, 'kn' => 0.1305, 'ko' => 0.0222,
            'ku' => 0.0256, 'lo' => 0.3488, 'lt' => 0.0246, 'lv' => 0.0264, 'ml' => 0.1322, 'mr' => 0.0571,
            'ms' => 0.0251, 'nl' => 0.0342, 'no' => 0.0266, 'or' => 0.1269, 'pa' => 0.1338, 'pl' => 0.0275,
            'pt' => 0.0252, 'ro' => 0.0247, 'ru' => 0.0184, 'sk' => 0.024, 'sl' => 0.0253, 'sq' => 0.0353,
            'sr' => 0.0234, 'sv' => 0.033, 'ta' => 0.1513, 'te' => 0.1547, 'th' => 0.0882, 'tl' => 0.0368,
            'tr' => 0.0258, 'uk' => 0.0206, 'ur' => 0.0282, 'vi' => 0.0467, 'yo' => 0.0329, 'zh' => 0.0152
        ];

    public function __construct(?string $ngramsFile = null)
    {
        // Opcache needs to be active, so the load of the database array does not add overhead.
        $folder = $this->ngramsFolder;
        $file = ($ngramsFile ?? "ngramsM60.php");
        // Internal reference: _ngrams_newAddEnd4gramExtra_1-4_2824 + _ngrams_charUtf8_1-1_2291
        if ($ngramsFile && !file_exists($folder . $file)) {
            $folder .= 'subset/';
        }
        $ngramsData = include $folder . $file;
        if (empty($ngramsData['ngrams']) || empty($ngramsData['languages'])) {
            throw new RuntimeException(sprintf('File "%s" data is invalid', $file));
        }
        $this->ngrams = $ngramsData['ngrams']; // copy could use more memory on startup, but access is faster
        $this->langCodes = $ngramsData['languages'];
        $this->dataType = $ngramsData['type'];
        $this->langScore = array_fill(0, max(array_keys($this->langCodes)) + 1, 0);
    }
}
