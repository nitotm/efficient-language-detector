<?php
/**
 * @copyright 2025 Nito T.M.
 * @license https://www.apache.org/licenses/LICENSE-2.0 Apache-2.0
 * @author Nito T.M. (https://github.com/nitotm)
 * @package nitotm/efficient-language-detector
 */

namespace Nitotm\Eld;

final class EldScheme
{
    // Language return scheme options
    public const ISO639_1 = 'ISO639_1';
    public const ISO639_2T = 'ISO639_2T';
    public const ISO639_1_BCP47 = 'ISO639_1_BCP47';
    public const ISO639_2T_BCP47 = 'ISO639_2T_BCP47';
    public const FULL_TEXT = 'FULL_TEXT';

    public static function values(): array
    {
        return [
            self::ISO639_1,
            self::ISO639_2T,
            self::ISO639_1_BCP47,
            self::ISO639_2T_BCP47,
            self::FULL_TEXT
        ];
    }
}
