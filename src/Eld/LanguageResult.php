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

    /**
     * @param null|array<string,float> $scores
     * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
     */
    public function __construct(
        public ?string $language = null,
        public ?float $score = null,
        public ?array $scores = null,
        public bool $isValid = true,
        public ?string $errorMessage = null,
    ) {
    }

    /** @noinspection ForgottenDebugOutputInspection */
    public function dump():void
    {
        if (function_exists('dump')) {
            dump($this);

            return;
        }

        /** @psalm-suppress ForbiddenCode */
        var_dump($this);
    }
}
