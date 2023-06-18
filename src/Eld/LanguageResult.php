<?php
/** legal notice: https://github.com/nitotm/efficient-language-detector/blob/main/LEGAL.md */

declare(strict_types = 1);

namespace Nitotm\Eld;

final readonly class LanguageResult
{
    public const TOO_SHORT = "Text to short for detection";
    public const MORE_NGRAMS = 'Not enough distinct ngrams';
    public const UNSURE = 'No language has been identified with sufficient confidence, bypass with LanguageDetector::checkConfidence=false';
    public const NOCLUE = 'Language not detected';

    public static function fail(string $errorMessage):self
    {
        return new LanguageResult(
            isValid: false,
            errorMessage: $errorMessage,
        );
    }

    /**
     * @param null|array<string,float> $scores
     */
    public function __construct(
        public ?string $language = null,
        public ?float $score = null,
        public ?array $scores = null,
        public bool $isValid = true,
        public ?string $errorMessage = null,
    ) {
    }

    public function dump():void
    {
        /** @noinspection ForgottenDebugOutputInspection */
        @dump($this);
    }
}
