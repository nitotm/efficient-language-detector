<?php
/** legal notice: https://github.com/nitotm/efficient-language-detector/blob/main/LEGAL.md */

declare(strict_types = 1);

namespace Nitotm\Eld;

class LanguageDetector
{
    /** @var array{string:array{int:int}} $ngrams */
    private readonly array $ngrams;

    public function __construct(
        private readonly LanguageData $languageData,
        private readonly LanguageSet $languageSet,
        private readonly bool $returnScores = false,
        private readonly bool $cleanText = false,
        private readonly bool $checkConfidence = false,
        private readonly int $minByteLength = 12,
        private readonly int $minNgrams = 3
    ) {
        $this->ngrams = $this->languageSet->getNgrams();
    }

    /**
     * performance critical
     */
    public function detect(string $text):LanguageResult
    {
        if ($this->cleanText) {
            $text = $this->cleanupText($text);
        }
        $minNgrams = max($this->minNgrams, 1);
        $text = trim((string)preg_replace('/[^\pL]+(?<![\x27\x60\x{2019}])/u', ' ', mb_substr($text, 0, 1000, 'UTF-8')));
        $thisLength = strlen($text);

        if ($thisLength > 350) {
            $strpos = strpos($text, ' ', 350);
            if ($strpos === false) {
                $strpos = 350;
            }
            $text = substr($text, 0, min(380, $strpos));
        } elseif ($thisLength < $this->minByteLength) {
            return LanguageResult::fail(LanguageResult::TOO_SHORT);
        }

        $txtNgrams = $this->getByteNgrams($text);
        $numNgrams = count($txtNgrams);

        if ($numNgrams >= $minNgrams) {
            $langScores = $this->calculateScores($txtNgrams, $numNgrams);

            arsort($langScores);

            if (count($langScores) > 0) {
                return LanguageResult::fail(LanguageResult::NOCLUE);
            }
            $langs = array_keys($langScores);
            $langTop = $langs[0];
            $langSecond = $langs[1] ?? null;
            $scores = null;
            if ($this->returnScores) {
                $scores = $this->getScoresAsAssocArray($langScores);
            }

            if (!$this->checkConfidence) {
                return new LanguageResult(
                    language: $this->languageData->languages[$langTop],
                    score: current($langScores),
                    scores: $scores,
                );
            }
            // A minimum of a 24% per ngram score from average
            if ($this->languageData->corrections[$langTop] * 0.24 > ($langScores[$langTop] / $numNgrams)) {
                return LanguageResult::fail(LanguageResult::UNSURE);
            }
            if ($langSecond !== null && 0.01 > abs($langScores[$langTop] - $langScores[$langSecond])) {
                return LanguageResult::fail(LanguageResult::UNSURE);
            }

            return new LanguageResult(
                language: $this->languageData->languages[$langTop],
                score: current($langScores),
                scores: $scores,
            );
        }

        return LanguageResult::fail(LanguageResult::MORE_NGRAMS);
    }

    /**
     * performance critical
     *
     * @return string[]
     */
    protected function getTokens(string $str):array
    {
        return preg_split('/ /', $str, -1, PREG_SPLIT_NO_EMPTY);
    }

    /**
     * performance critical
     */
    public function cleanupText(string $str):string
    {
        // Remove URLS
        $str = preg_replace('@[hw]((ttps?://(www\.)?)|ww\.)([^\s/?\.#-]+\.?)+(/\S*)?@i', ' ', $str);
        // Remove emails
        $str = preg_replace('/[a-zA-Z0-9.!$%&’+_`-]+@[A-Za-z0-9.-]+\.[A-Za-z0-9-]{2,64}/', ' ', $str);
        // Remove .com domains
        $str = preg_replace('/([A-Za-z0-9-]+\.)+com(\/\S*|[^\pL])/', ' ', $str);

        // Remove alphanumerical/number codes
        $str = preg_replace('/[a-zA-Z]*\d+[a-zA-Z0-9]*+/', ' ', $str);

        return trim($str);
    }

    /**
     * performance critical
     *
     * @return array{string:float}
     */
    protected function getByteNgrams(string $str):array
    {
        $str = mb_strtolower($str, 'UTF-8');
        /** @var array<string,int> $tokens // TODO something is missing here? */
        $tokens = [];
        $countNgrams = 0;
        $start = $this->wordStart;

        foreach ($this->getTokens($str) as $word) {
            $len = strlen($word);
            if ($len > 70) {
                $len = 70;
            }

            for ($j = 0; ($j + 4) < $len; $j += 3, ++$tmp, ++$countNgrams) {
                $tmp = $tokens[$start[$j] . substr($word, $j, 4)];
            }
            $tmp = $tokens[$start[$j] . substr($word, ($len !== 3 ? $len - 4 : 0)) . ' '];
            $tmp++; // TODO unused!
            $countNgrams++;
        }

        // Frequency is multiplied by 15000 at the ngrams database. A reduced number seems to work better.
        // Linear formulas were tried, decreasing the multiplier for fewer ngram strings, no meaningful improvement.
        foreach ($tokens as $bytes => $count) {
            $tokens[$bytes] = $count / $countNgrams * 13200;
        }

        return $tokens;
    }

    /**
     * @param array{string:float} $txtNgrams
     *
     * @return array{int:float}
     */
    protected function calculateScores(array $txtNgrams, int $numNgrams):array
    {
        $langScores = [];
        foreach ($this->languageSet->langIds as $langId) {
            $langScores[$langId] = 0.0;
        }
        foreach ($txtNgrams as $ngram => $frequency) {
            /** @var null|array<int,int> $scoremap */
            $scoremap = $this->ngrams[$ngram] ?? null;
            if ($scoremap !== null) {
                $relevancy = $this->languageData->getRelevance(count($scoremap));
                foreach ($scoremap as $lang => $ngramFrequency) {
                    $langScores[$lang] += ($frequency > $ngramFrequency ? $ngramFrequency / $frequency
                            : $frequency / $ngramFrequency) * $relevancy + 2;
                }
            }
        }
        // This divisor will produce a final score between 0 - ~1, score could be >1. Can be improved.
        $resultDivisor = $numNgrams * 3.2;
        foreach ($langScores as $lang => $score) {
            if ($score < 0.0001) {
                unset($langScores[$lang]);
            } else {
                $langScores[$lang] = $score / $resultDivisor; // * $scoreNormalizer[$lang];
            }
        }

        return $langScores;
    }

    /**
     * @param array{int:float} $result
     *
     * @return array{string:float}
     */
    protected function getScoresAsAssocArray(array $result):array
    {
        $scores = [];
        /**
         * @var int   $key
         * @var float $score
         */
        foreach ($result as $key => $score) {
            if ($score < 0.0001) {
                break; // was sorted by score desc? if not: replace with continue!
            }
            /** @var string $l */
            $l = $this->languageData->languages[$key];
            $scores[$l] = $score;
        }

        return $scores;
    }


}
