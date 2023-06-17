<?php
/** legal notice: https://github.com/nitotm/efficient-language-detector/blob/main/LEGAL.md */

declare(strict_types = 1);

namespace Nitotm\Eld;

use JsonException;

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
     * @param array<string,float> $scores
     */
    public function __construct(
        public ?string $language = null,
        public ?float $score = null,
        public ?array $scores = null,
        public bool $isValid = true,
        public ?string $errorMessage = null,
    ) {
    }

    /**
     * @return array<mixed,mixed>
     */
    public function dump(bool $directly = false):array
    {
        try {
            $dump = (array)json_decode(json_encode($this, JSON_THROW_ON_ERROR), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            $dump = [
                "failed to dump",
                $e::class,
                $e->getMessage(),
            ];
        }
        if ($directly) {
            /** @noinspection ForgottenDebugOutputInspection */
            var_dump($dump);
        }

        return $dump;
    }
}
