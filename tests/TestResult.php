<?php
/** legal notice: https://github.com/nitotm/efficient-language-detector/blob/main/LEGAL.md */

declare(strict_types = 1);

namespace Nitotm\EldTests;

readonly class TestResult
{
    public function __construct(
        public \Closure $test,
        public string $identifier,
        public bool $stop,
    ) {
    }
}
