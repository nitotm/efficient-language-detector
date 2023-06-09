<?php
/**
 * @copyright 2023 Nito T.M.
 * @license https://www.apache.org/licenses/LICENSE-2.0 Apache-2.0
 * @author Nito T.M. (https://github.com/nitotm)
 * @package nitotm/efficient-language-detector
 */

namespace Nitotm\Eld;

require_once __DIR__ . '/LanguageData.php';
require_once __DIR__ . '/LanguageResult.php';

/**
 * Performance critical
 */
class LanguageDetector extends LanguageData
{
    public bool $cleanText = false;
    private array $wordStart;

    public function __construct(?string $ngramsFile = null)
    {
        parent::__construct($ngramsFile);
        $this->wordStart = [' '] + array_fill(1, 70, '');
    }

    /**
     * Returns the language detected for a given UTF-8 string, as an ISO 639-1 code
     *  LanguageResult object( language => 'es', scores => ['es' => 0.5, 'et' => 0.2], isReliable() => true )
     *  LanguageResult object( language => null|string, scores => null|array, isReliable() => bool )
     */
    public function detect(string $text): LanguageResult
    {
        if ($this->cleanText) {
            // Removes Urls, emails, alphanumerical & numbers
            $text = $this->getCleanText($text);
        }
        $text = $this->normalizeText($text);
        $textNgrams = $this->getByteNgrams($text);
        $numNgrams = count($textNgrams);

        if ($numNgrams) {
            $results = $this->calculateScores($textNgrams, $numNgrams);

            if ($results) {
                arsort($results);

                return new LanguageResult(key($results), $results, $numNgrams, $this->avgScore);
            }
        }
        return new LanguageResult();
    }

    /**
     * Removes parts of a string, that may be considered as "noise" for language detection
     */
    public function getCleanText(string $str): string
    {
        // Remove URLS
        $str = preg_replace('@[hw]((ttps?://(www\.)?)|ww\.)([^\s/?.#-]+\.?)+(/\S*)?@i', ' ', $str);
        // Remove emails
        $str = preg_replace('/[a-zA-Z0-9.!$%&’+_`-]+@[A-Za-z0-9.-]+\.[A-Za-z0-9-]{2,64}/u', ' ', $str ?? '');
        // Remove .com domains
        $str = preg_replace('/([A-Za-z0-9-]+\.)+com(\/\S*|[^\pL])/u', ' ', $str ?? '');

        // Remove alphanumerical/number codes
        return preg_replace('/[a-zA-Z]*\d+[a-zA-Z0-9]*+/', ' ', $str ?? '');
    }

    protected function normalizeText(string $text): string
    {
        // Normalize special characters/word separators
        $text = trim(preg_replace('/[^\pL]+(?<![\x27\x60\x{2019}])/u', ' ', $text)); // substr($text, 0, 1000)
        $thisLength = strlen($text);

        if ($thisLength > 350) {
            // Cut to first whitespace after 350 bytes offset, or 380 bytes
            $text = substr(
                $text,
                0,
                min(380, (strpos($text, ' ', 350) ?: 350))
            );
        }

        return mb_strtolower($text, 'UTF-8');
    }

    /**
     * Gets Ngrams from a given string.
     */
    protected function getByteNgrams(string $text): array
    {
        $byteNgrams = [];
        $countNgrams = 0;
        $start = $this->wordStart;

        foreach ($this->tokenizer($text) as $word) {
            $len = strlen($word);
            if ($len > 70) {
                $len = 70;
            }

            for ($j = 0; ($j + 4) < $len; $j += 3, ++$tmp, ++$countNgrams) {
                $tmp = &$byteNgrams[$start[$j] . substr($word, $j, 4)];
            }
            $tmp = &$byteNgrams[$start[$j] . substr($word, ($len !== 3 ? $len - 4 : 0)) . ' '];
            $tmp++;
            $countNgrams++;
        }

        // Frequency is multiplied by 15000 at the Ngrams database. A reduced number seems to work better.
        // Linear formulas were tried, decreasing the multiplier for fewer Ngram strings, no meaningful improvement.
        foreach ($byteNgrams as $bytes => $count) {
            $byteNgrams[$bytes] = $count / $countNgrams * 13200;
        }

        return $byteNgrams;
    }

    protected function tokenizer(string $str): array
    {
        return preg_split('/ /', $str, -1, PREG_SPLIT_NO_EMPTY);
    }

    /**
     * Calculate scores for each language from the given Ngrams
     */
    protected function calculateScores(array $textNgrams, int $numNgrams): array
    {
        $langScore = $this->langScore;
        $results = [];

        foreach ($textNgrams as $bytes => $currentFrequency) {
            if (isset($this->ngrams[$bytes])) {
                $langCount = count($this->ngrams[$bytes]);
                // Ngram score multiplier, the fewer languages found the more relevancy. Formula can be fine-tuned.
                // TODO consider make a formula that adapts for database language count, on subsets. Testing is needed
                if ($langCount === 1) {
                    $relevancy = 27;
                } elseif ($langCount < 16) {
                    $relevancy = (16 - $langCount) / 2 + 1;
                } else {
                    $relevancy = 1;
                }
                // Most time-consuming loop, do only the strictly necessary inside
                foreach ($this->ngrams[$bytes] as $lang => $globalFrequency) {
                    $langScore[$lang] += ($currentFrequency > $globalFrequency ?
                            $globalFrequency / $currentFrequency
                            : $currentFrequency / $globalFrequency
                        ) * $relevancy + 2;
                }
            }
        }
        // This divisor will produce a final score between 0 - ~1, score could be >1. Can be improved.
        $resultDivisor = $numNgrams * 3.2;
        // $scoreNormalizer = $this->scoreNormalizer; // local access improves speed

        if ($this->subset) {
            $langScore = $this->filterLangSubset($langScore);
        }

        $langCodes = $this->langCodes; // local access improves speed
        foreach ($langScore as $lang => $score) {
            if ($score) {
                $results[$langCodes[$lang]] = $score / $resultDivisor; // * $scoreNormalizer[$lang];
            }
        }

        return $results;
    }
}
