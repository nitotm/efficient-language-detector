<?php
/** legal notice: https://github.com/nitotm/efficient-language-detector/blob/main/legal.md */

declare(strict_types = 1);

namespace Nitotm\Eld;

final class LanguageData
{
    /** @var string[] $langCodes */
    public readonly array $langCodes;
    /** @var float[] $avgScore */
    public readonly array $avgScore;
    /** @var array<int,float> $langScore */
    public array $langScore;
    /** @var array<string|int,array<int,int>> $ngrams */ // TODO is key int or string? code is unclear, I cannot construct type
    public array $ngrams;
    public readonly int $languagesTotal;

    public function __construct(
        string $ngramsFile = 'ngrams-m.php'
    ) {
        $this->ngrams = include self::getFullFilenameForNgramFile($ngramsFile);
        $languageWeights = [
            // ISO 639-1 codes
            'am' => 0.0661,
            'ar' => 0.0237,
            'az' => 0.0269,
            'be' => 0.0227,
            'bg' => 0.0234,
            'bn' => 0.1373,
            'ca' => 0.0246,
            'cs' => 0.0242,
            'da' => 0.0277,
            'de' => 0.0275,
            'el' => 0.0369,
            'en' => 0.0378,
            'es' => 0.0252,
            'et' => 0.0253,
            'eu' => 0.0369,
            'fa' => 0.0213,
            'fi' => 0.026,
            'fr' => 0.0253,
            'gu' => 0.1197,
            'he' => 0.0402,
            'hi' => 0.0578,
            'hr' => 0.0201,
            'hu' => 0.0208,
            'hy' => 0.0439,
            'is' => 0.032,
            'it' => 0.0251,
            'ja' => 0.0375,
            'ka' => 0.1383,
            'kn' => 0.1305,
            'ko' => 0.0222,
            'ku' => 0.0256,
            'lo' => 0.3488,
            'lt' => 0.0246,
            'lv' => 0.0264,
            'ml' => 0.1322,
            'mr' => 0.0571,
            'ms' => 0.0251,
            'nl' => 0.0342,
            'no' => 0.0266,
            'or' => 0.1269,
            'pa' => 0.1338,
            'pl' => 0.0275,
            'pt' => 0.0252,
            'ro' => 0.0247,
            'ru' => 0.0184,
            'sk' => 0.024,
            'sl' => 0.0253,
            'sq' => 0.0353,
            'sr' => 0.0234,
            'sv' => 0.033,
            'ta' => 0.1513,
            'te' => 0.1547,
            'th' => 0.0882,
            'tl' => 0.0368,
            'tr' => 0.0258,
            'uk' => 0.0206,
            'ur' => 0.0282,
            'vi' => 0.0467,
            'yo' => 0.0329,
            'zh' => 0.0152,
        ];
        $this->langCodes = array_keys($languageWeights);
        $this->avgScore = array_values($languageWeights);
        $this->languagesTotal = count($this->langCodes);

        $this->langScore = array_fill(0, count($this->langCodes), 0.0);
    }

    public static function getFullFilenameForNgramFile(string $filename):string
    {
        return dirname(__DIR__) . '/data/' . $filename;
    }
}
