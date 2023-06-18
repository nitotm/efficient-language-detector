<?php
/** legal notice: https://github.com/nitotm/efficient-language-detector/blob/main/LEGAL.md */

declare(strict_types = 1);

namespace Nitotm\Eld;

/**
 * This is not the nicest way of doing things,
 * but it's easy to setup a kind of profiling
 * and good enough to find most problems.
 * For compareable real time measurents this is not perfectly suited!
 */
class LanguageDetectorWithTools extends LanguageDetector
{
    private float $startedTime;

    public function __construct(
        LanguageData $languageData,
        LanguageSet $languageSubset,
        bool $returnScores = true,
        bool $cleanText = false,
        bool $checkConfidence = false,
        int $minByteLength = 12,
        int $minNgrams = 3
    ) {
        $this->startedTime = microtime(true);
        $this->outstat(__METHOD__, "start");
        parent::__construct(
            $languageData, $languageSubset, $returnScores, $cleanText, $checkConfidence, $minByteLength, $minNgrams
        );
        $this->outstat(__METHOD__, "end");
    }

    public function detect(string $text):LanguageResult
    {
        $this->outstat(__METHOD__, "start");
        $result = parent::detect($text);
        $this->outstat(__METHOD__, "end");
        $result->dump(true);

        return $result;
    }

    protected function getTokens(string $str):array
    {
        $this->outstat(__METHOD__, "start");
        $tokens = parent::getTokens($str);
        $this->outstat(__METHOD__, "end");

        return $tokens;
    }

    public function cleanupText(string $str):string
    {
        $this->outstat(__METHOD__, "start");
        $cleanupText = parent::cleanupText($str);
        $this->outstat(__METHOD__, "end");

        return $cleanupText;
    }

    protected function getByteNgrams(string $str):array
    {
        $this->outstat(__METHOD__, "start");
        $byteNgrams = parent::getByteNgrams($str);
        $this->outstat(__METHOD__, "end");

        return $byteNgrams;
    }

    protected function calculateScores(array $txtNgrams, int $numNgrams):array
    {
        $this->outstat(__METHOD__, "start");
        $calculateScores = parent::calculateScores($txtNgrams, $numNgrams);
        $this->outstat(__METHOD__, "end");

        return $calculateScores;
    }

    protected function getScoresAsAssocArray(array $result):array
    {
        $this->outstat(__METHOD__, "start");
        $scoresAsAssocArray = parent::getScoresAsAssocArray($result);
        $this->outstat(__METHOD__, "end");

        return $scoresAsAssocArray;
    }

    protected function outstat(string $method, string $event):void
    {
        $this->out($method . ":" . $event);
        $duration = microtime(true) - $this->startedTime;
        $memory = memory_get_peak_usage();
        $this->out('- time in ms since start', ceil($duration * 1000 * 100) / 100);
        $this->out('- memory peak', ceil($memory / 1024 / 1024));
    }

    /**
     * @param string           $msg
     * @param string|int|float ...$meta
     *
     * @return void
     */
    protected function out(string $msg, ...$meta):void
    {
        if ($meta !== []) {
            $msg .= ": " . implode(": ", $meta);
        }
        echo self::class . "> " . $msg . "\r\n";
    }

}
