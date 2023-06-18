<?php
/** legal notice: https://github.com/nitotm/efficient-language-detector/blob/main/legal.md */

declare(strict_types = 1);

namespace Nitotm\Eld;

final readonly class LanguageData
{
    /** @var array<string> $languages */
    public array $languages;
    /** @var float[] $corrections */
    public array $corrections;
    public int $count;
    private string $ngramFile;

    public function __construct(
        ?string $ngramFile = null
    ) {
        /** @var array{string:float} $languageWeights */
        $languageWeights = include(dirname(__DIR__) . '/data/language_corrections.php');
        $this->languages = array_keys($languageWeights);
        $this->corrections = array_values($languageWeights);
        $this->count = count($this->languages);
        $this->ngramFile = dirname(__DIR__) . "/data/" . ($ngramFile ?? "ngrams-m.php");
    }

    /** @return array{string:array{int:int}} */
    public function getNgrams():array
    {
        return include($this->ngramFile);
    }

    /**
     * @param string[] $limitTo
     *
     * @return int[]
     */
    public function getLangIds(array $limitTo):array
    {
        $langids = [];
        foreach ($limitTo as $lang) {
            $index = array_search($lang, $this->languages, true);
            if ($index !== false && is_int($index)) {
                $langids[] = $index;
            }
        }

        return $langids;
    }

    /**
     * If an Ngram is existing in many languages it has less relevance.
     */
    public function getRelevance(int $count):int
    {
        if ($count === 1) {
            $relevancy = 27;
        } elseif ($count < 16) {
            $relevancy = (int)(round((16 - $count) / 2 + 1));
        } else {
            $relevancy = 1;
        }

        return $relevancy;
    }
}
