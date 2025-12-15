<?php
/**
 * @copyright 2024 Nito T.M.
 * @license https://www.apache.org/licenses/LICENSE-2.0 Apache-2.0
 * @author Nito T.M. (https://github.com/nitotm)
 * @package nitotm/efficient-language-detector
 */

namespace Nitotm\Eld;

use RuntimeException;

/**
 * Performance critical
 */
class LanguageDetector extends LanguageData
{
    protected ?string $databaseInput;
    protected ?string $schemeInput;
    protected string $modeInput;
    private bool $textCleanupEnabled = false;

     // TODO v4, ($mode: , $database: , $scheme: ) or ($database: , $mode: )
    public function __construct(
        ?string $databaseFile = null,
        ?string $outputFormat = null, // TODO v4, rename to $outputScheme / $scheme
        string $mode = EldMode::MODE_ARRAY
    ) {
        $this->databaseInput = $databaseFile;
        $this->schemeInput = $outputFormat;
        $this->modeInput = $mode;
    }

    /**
     * Returns the language detected for a given UTF-8 string, as ISO 639-1 code (default), or 'und' if undetermined
     *  LanguageResult object( language => string, scores() => array<string, float>, isReliable() => bool )
     *  ( language => 'es', scores() => ['es' => 0.5, 'et' => 0.2], isReliable() => true )
     *  ( language => 'und', scores() => [], isReliable() => false )
     */
    public function detect(string $text): LanguageResult
    {
        if (!$this->isInitialized) { // Lazy load
            $this->loadData($this->databaseInput, $this->schemeInput, $this->modeInput);
        }
        if ($this->textCleanupEnabled) {
            // Removes Urls, emails, alphanumerical & numbers
            $text = $this->cleanText($text);
        }

        $words = $this->getWords($text);
        $byteNgrams = $this->getByteNgrams($words);

        if ($this->databaseMode === EldMode::MODE_ARRAY) {
            $scores = $this->calculateScores($byteNgrams);
        } else {
            $scores = $this->calculateScoresBlob($byteNgrams);
        }

        $maxScore = max($scores);
        // scores start at 1
        if ($maxScore > 1) {
            return new LanguageResult(
                array_search($maxScore, $scores, true), // max score language key
                $scores,
                $byteNgrams,
                $this->outputLanguages,
                $this->avgScore
            );
        }

        return new LanguageResult();
    }

    /**
     * Removes parts of a string, that may be considered "noise" for language detection
     */
    public function cleanText(string $str): string
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

    protected function getWords(string $text): array
    {
        $text = mb_strtolower(
            trim(
                substr($text, 0, 1000)
            ),
            'UTF-8'
        );

        // Match words. Treat Chinese, Japanese & Korean logograms as words. Allows non-consecutive apostrophes in words
        preg_match_all(
            '/(?:\pL(?<![\p{Han}\p{Hiragana}\p{Katakana}\p{Hangul}]))+' .
            '(?:[\x27\x60\x{2019}](?:(?![\p{Han}\p{Hiragana}\p{Katakana}\p{Hangul}])\pL)+)*' .
            '|[\p{Han}\p{Hiragana}\p{Katakana}\p{Hangul}]/u',
            $text,
            $words
        );

        return $words[0];
    }

    /**
     * Gets Ngrams from the given words
     */
    protected function getByteNgrams(array $words): array
    {
        /** @var array<string, bool> $byteNgrams */
        $byteNgrams = [];
        $ngramLength = $this->ngramLength; // Local access is faster
        $ngramStride = $this->ngramStride;
        // $countNgrams = 0;

        foreach ($words as $word) {
            $len = strlen($word);
            // Processing whole-word n-grams separately improves speed measurably
            if ($len <= $ngramLength) {
                // fastest way to set and add to index key without checking if exist
                // $tmp = &$byteNgrams[' ' . $word . ' ']; // $countNgrams++; $tmp++;
                $byteNgrams[' ' . $word . ' '] = true;
            } else {
                // $tmp = &$byteNgrams[' ' . substr($word, 0, $ngramLength)]; // $tmp++;
                $byteNgrams[' ' . substr($word, 0, $ngramLength)] = true;

                for ($j = $ngramStride; ($j + $ngramLength) < $len; $j += $ngramStride) { // ++$countNgrams, ++$tmp
                    // $tmp = &$byteNgrams[substr($word, $j, $ngramLength)];
                    $byteNgrams[substr($word, $j, $ngramLength)] = true;
                }
                // $tmp = &$byteNgrams[substr($word, $len - $ngramLength) . ' '];
                // $countNgrams+=2; $tmp++; We would count at least 2 ngrams, start and ending ngram.
                $byteNgrams[substr($word, $len - $ngramLength) . ' '] = true;
            }
            // $tmp++; Unnecessary as long as we do not use $frequency at calculateScores()
            // if ( $countNgrams > 100) { break; } Unnecessary as long as we cut $text at <=1000 bytes
        }

        return $byteNgrams;
    }

    /**
     * Calculate scores from the given Ngrams for each language
     *
     * @param array<string, ?int> $byteNgrams
     * @return non-empty-array<int, float>
     */
    protected function calculateScores(array $byteNgrams): array
    {
        $langScore = $this->langScore;
        foreach ($byteNgrams as $bytes => $_) { // $frequency) {
            if (isset($this->ngrams[$bytes])) {
                // TODO: $frequency (count), not taken into account for now, more testing is needed
                foreach ($this->ngrams[$bytes] as $language => $score) {
                    $langScore[$language] *= $score;
                }
            }
        }

        return $langScore;
    }


    /**
     * Calculate scores from the given Ngrams for each language, using Blob database
     *
     * @param array<string, ?int> $byteNgrams
     * @return array<int, float>
     */
    protected function calculateScoresBlob(array $byteNgrams): array
    {
        $langScore = $this->langScore;
        $IndexM = $this->IndexM; // faster local access
        $isDisk = ($this->databaseMode === EldMode::MODE_DISK);
        $indexSlotLen = 8;
        $dataSlotLen = 3;

        foreach ($byteNgrams as $bytes => $_) { // $frequency) {
            $slot = crc32($bytes) % $IndexM;
            $startSlot = $slot;

            for (;;) {
                // 4 bytes Ngram identifier + 3 bytes data offset + 1 byte data points length
                // Performance critical, we repeat a bit of code to make it faster
                if ($isDisk) {
                    $index = stream_get_contents(
                        $this->indexStream,
                        $indexSlotLen,
                        $slot * $indexSlotLen + self::BLOB_HEAD_LEN
                    );
                } else {
                    $index = substr($this->indexBlob, $slot * $indexSlotLen, $indexSlotLen);
                }

                if ($index === "\0\0\0\0\0\0\0\0") {
                    break; // not found
                }
                if ($index === false) {
                    throw new RuntimeException('Incorrect Blob data');
                }
                // 4 byte ngram index "fingerprint", safe enough, faster than int hash
                if ($index[2] === $bytes[2] &&
                    $index[1] === $bytes[1] &&
                    $index[0] === $bytes[0] &&
                    $index[3] === ($bytes[3] ?? "\0") // min ngram size across all databases is 3 bytes
                ) {
                    // unpack('C', $index[7])[1];
                    $scoresLen = ord($index[7]);

                    // $index 4-6 is data offset, 3 bytes each, unpack('N', "\0".$index[4].$index[5].$index[6])[1] * 3
                    // $data: 1 byte language id + 1 byte score, repeated sequence
                    // Performance critical, we repeat a bit of code to make it faster
                    if ($isDisk) {
                        $data = stream_get_contents(
                            $this->dataStream,
                            $scoresLen * $dataSlotLen,
                            ((ord($index[4]) << 16) | (ord($index[5]) << 8) | ord($index[6]))
                                    * $dataSlotLen + self::BLOB_HEAD_LEN
                        );
                    } else {
                        $data = substr(
                            $this->dataBlob,
                            ((ord($index[4]) << 16) | (ord($index[5]) << 8) | ord($index[6])) * $dataSlotLen,
                            $scoresLen * $dataSlotLen
                        );
                    }

                    // This is a performance critical loop, a small change can improve greatly total time execution
                    for ($i = 0, $dataPos = 0; $i < $scoresLen; $i++, $dataPos += 3) {
                        // unpack('C') Language id 8-bit int, unpack('n') score 16-bit int
                        $langScore[ord($data[$dataPos])] *=
                            // unpack('n',  $data[$dataPos+1]. $data[$dataPos+2] )[1] / 2100
                            ((ord($data[$dataPos + 1]) << 8) | ord($data[$dataPos + 2])) * 0.00047619047619048;
                        // Scores are multiplied by 2100 at Blob DB, multiply here is much faster
                    }
                    break;
                }

                $slot = ($slot + 1) % $IndexM;
                if ($slot === $startSlot) {
                    throw new RuntimeException('Incorrect Blob data, stopping semi-infinite loop');
                }
            }
        }

        if ($this->dynamicSubset) {
            $langScore = array_intersect_key($langScore, $this->dynamicSubset);
        }

        return $langScore;
    }

    public function enableTextCleanup(bool $bool): void
    {
        $this->textCleanupEnabled = $bool;
    }

    public function setOutputScheme(string $scheme): bool
    {
        return $this->loadOutputScheme($scheme, false);
    }


    public function info(): array
    {
        if (!$this->isInitialized) {
            $this->loadData($this->databaseInput, $this->schemeInput, $this->modeInput);
        }
        return [
            'Database size' => $this->dataType,
            'Database mode' => $this->databaseMode,
            'Language count' => count($this->langCodes),
            'Loaded database' => $this->databaseName,
            'Languages in DB (ISO639-1)' => $this->langCodes,
            'Languages subset' => ($this->dynamicSubset ? array_intersect_key(
                $this->outputLanguages,
                $this->dynamicSubset
            ) : 'Unset or loaded DB languages match subset'),
            'Output scheme' => $this->outputScheme,
            'Languages (DB) in output scheme' => ($this->outputScheme !== EldFormat::ISO639_1 ? array_intersect_key(
                $this->outputLanguages,
                $this->langCodes
            ) : 'Same as ISO639-1'),
            'Text cleanup enabled' => ($this->textCleanupEnabled ? 'True' : 'False')
        ];
    }
}
