<?php
/**
 * @copyright 2023 Nito T.M.
 * @license https://www.apache.org/licenses/LICENSE-2.0 Apache-2.0
 * @author Nito T.M. (https://github.com/nitotm)
 * @package nitotm/efficient-language-detector
 */

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
    protected array $avgScore;
    protected string $ngramsFolder = __DIR__ . '/../resources/ngrams/';
    /*
    ISO 639-1 codes, for the 60 languages set.
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
        $this->avgScore = include __DIR__ . '/../resources/avgScore.php';
    }
}
