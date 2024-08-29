<?php
/**
 * @copyright 2024 Nito T.M.
 * @license https://www.apache.org/licenses/LICENSE-2.0 Apache-2.0
 * @author Nito T.M. (https://github.com/nitotm)
 * @package nitotm/efficient-language-detector
 */

namespace Nitotm\Eld;

/**
 * Performance critical
 */
final class LanguageResult
{
    public string $language;
    private int $languageIndex;
    /** @var null|array<string, float> $prettyScores */
    private ?array $prettyScores;
    /** @var null|array<int, float> $rawScores */
    private $rawScores;
    /** @var null|array<string, ?int> $byteNgrams */
    private $byteNgrams;
    /** @var null|array<int, string> $outputLanguages */
    private $outputLanguages;
    /** @var null|array<int, float> $avgScore */
    private $avgScore;


    /**
     * @param null|array<int, float> $rawScores
     * @param null|array<string, ?int> $byteNgrams
     * @param null|array<int, string> $outputLanguages
     * @param null|array<string, float> $avgScore
     */
    public function __construct(
        ?float $maxScore = null,
        ?array $rawScores = null,
        ?array $byteNgrams = null,
        ?array $outputLanguages = null,
        ?array $avgScore = null
    ) {
        if ($maxScore !== null) {
            $this->languageIndex = array_search($maxScore, $rawScores, true);
            $this->language = $outputLanguages[$this->languageIndex];
            $this->rawScores = $rawScores;
            $this->byteNgrams = $byteNgrams;
            $this->outputLanguages = $outputLanguages;
            $this->avgScore = $avgScore;
        } else {
            $this->language = 'und';
        }
    }

    public function __debugInfo()
    {
        return [
            'language' => $this->language,
            'scores()' => $this->scores(),
            'isReliable()' => $this->isReliable()
        ];
    }

    public function scores(): array
    {
        if (isset($this->prettyScores)) {
            return $this->prettyScores;
        }
        $scores = [];
        if ($this->language !== 'und') {
            $outputLanguages = $this->outputLanguages; // local access improves speed. Tested on PHP 7.4 & 8.2
            $fraction = 1 / count($this->byteNgrams);
            foreach ($this->rawScores as $key => $value) {
                if ($value > 1) {
                    // we get avg. score per ngram, then normalize score value to 0-1
                    $scores[$outputLanguages[$key]] = 1 - 1 / exp($fraction * log($value));
                }
            }
            arsort($scores);
        }

        return $this->prettyScores = $scores;
    }

    public function isReliable(): bool
    {
        // if undetermined language, or less than 3 ngrams
        if ($this->language === 'und' || count($this->byteNgrams) < 3) {
            return false;
        }

        $scores = $this->scores();

        // Reliable if score is >75% of average, and +5% higher than next score. Selected numbers after testing
        if ($this->avgScore[$this->languageIndex] * 0.75 > $scores[$this->language]
            || 0.05 > abs($scores[$this->language] - next($scores)) / $scores[$this->language]) {
            return false;
        }
        return true;
    }
}
