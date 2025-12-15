<?php
/**
 * @copyright 2025 Nito T.M.
 * @license https://www.apache.org/licenses/LICENSE-2.0 Apache-2.0
 * @author Nito T.M. (https://github.com/nitotm)
 * @package nitotm/efficient-language-detector
 */

namespace Nitotm\Eld;

final class EldMode
{
    public const MODE_ARRAY   = 'array';  // compiled PHP file with an array
    public const MODE_STRING  = 'string'; // compiled PHP file with a string (blob)
    public const MODE_BYTES   = 'bytes';  // raw blob loaded with file_get_contents
    public const MODE_DISK    = 'disk';   // blob streamed directly from disk

    public static function values(): array
    {
        return [
            self::MODE_ARRAY,
            self::MODE_STRING,
            self::MODE_BYTES,
            self::MODE_DISK
        ];
    }
}
