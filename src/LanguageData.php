<?php
/**
 * @copyright 2024 Nito T.M.
 * @license https://www.apache.org/licenses/LICENSE-2.0 Apache-2.0
 * @author Nito T.M. (https://github.com/nitotm)
 * @package nitotm/efficient-language-detector
 */

declare(strict_types=1);

namespace Nitotm\Eld;

use InvalidArgumentException;
use RuntimeException;

class LanguageData
{
    use LanguageSubsetTools;

    private static array $fileContents = [];
    /** @var array<string, array<int, int>> $ngrams */
    protected array $ngrams;
    /** @var array<int, string> $langCodes */
    protected array $langCodes;
    /** @var array<int, float> $avgScore */
    protected array $avgScore;
    /** @var array<int, float> $langScore */
    protected array $langScore;
    /** @var array<int, string> $outputLanguages */
    protected array $outputLanguages;
    /** @var null|array<string, array<int, int>> $defaultNgrams */
    protected ?array $defaultNgrams = null;
    protected ?string $loadedSubset = null;
    protected string $ngramsFolder = __DIR__ . '/../resources/ngrams/';
    protected string $dataType;
    protected int $ngramLength;
    protected int $ngramStride;
    protected string $outputFormat;
    protected bool $isSubset;

    protected function loadData(?string $databaseFile = null, ?string $outputFormat = null): void
    {
        $folder = $this->ngramsFolder;
        // Normalize file name or use default file
        $fileBaseName = (!$databaseFile ? EldDataFile::SMALL : preg_replace('/\.php$/','', strtolower($databaseFile)));
        // if file does not exist, check if it's a subset
        if (!file_exists($folder . $fileBaseName . '.php')) {
            $folder .= 'subset/';
            if (!file_exists($folder . $fileBaseName . '.php')) {
                throw new InvalidArgumentException(sprintf('Database file "%s" not found', $fileBaseName));
            }
        }

        /**
         * Some server APIs might not print "Warning Interned string buffer overflow", so we inform if memory is low
         * It's a problem since the server will hang out with no apparent reason unless you look at the server error log
         * Not a perfect solution, just warns in some cases of possible low memory. TODO research a better approach
         */
        $internedSizes = [
            EldDataFile::SMALL => 8,
            EldDataFile::MEDIUM => 16,
            EldDataFile::LARGE => 60,
            EldDataFile::EXTRALARGE => 116
        ]; // minimum; 170 to use all
        if (isset($internedSizes[$databaseFile])) {
            $opcacheStatus = (function_exists('opcache_get_status') ? opcache_get_status() : false);
            if ($opcacheStatus && $opcacheStatus['opcache_enabled']) {
                $internedStringsBuffer = ini_get('opcache.interned_strings_buffer');
                if ($internedStringsBuffer && $internedStringsBuffer < $internedSizes[$databaseFile]) {
                    trigger_error(
                        sprintf(
                            'interned_strings_buffer %smb is too low for this ELD database, recommended >= %smb',
                            $internedStringsBuffer,
                            $internedSizes[$databaseFile]
                        ),
                        E_USER_WARNING
                    );
                    // ob_flush(); flush(); Instant print, maybe too intrusive
                }
            }
        }

        $ngramsData = $this->loadFileContents($folder . $fileBaseName . '.php');
        if (empty($ngramsData['ngrams']) || empty($ngramsData['languages'])) {
            throw new RuntimeException(sprintf('File "%s" data is invalid', $fileBaseName));
        }

        $this->ngrams = $ngramsData['ngrams'];
        $this->langCodes = $ngramsData['languages'];
        $this->dataType = $ngramsData['type'];
        $this->ngramLength = $ngramsData['ngramLength'];
        $this->ngramStride = $ngramsData['ngramStride'];
        $this->isSubset = $ngramsData['isSubset'];
        $this->avgScore = $ngramsData['avgScore'];
        /** @var int $maxLang Highest language index key */
        /** @psalm-suppress ArgumentTypeCoercion */
        $maxLang = max(array_keys($this->langCodes));
        $this->langScore = array_fill(0, $maxLang + 1, 1.0);

        // Normalize format to lowercase to avoid case sensitivity issues
        $normalizedFormat = strtoupper($outputFormat ?? EldFormat::ISO639_1);
        $validFormats = [
            EldFormat::ISO639_1,
            EldFormat::ISO639_2T,
            EldFormat::ISO639_1_BCP47,
            EldFormat::ISO639_2T_BCP47,
            EldFormat::FULL_TEXT,
        ];
        if (in_array($normalizedFormat, $validFormats, true)) {
            $this->outputLanguages = include __DIR__ . '/../resources/formats/' . strtolower(
                    $normalizedFormat
                ) . '.php';
            $this->outputFormat = $normalizedFormat;
        } else {
            throw new InvalidArgumentException("Invalid format: $outputFormat");
        }
    }

    /**
     * Prevent including the same file multiple times
     */
    private function loadFileContents(string $file): array
    {
        return self::$fileContents[$file] ?? (self::$fileContents[$file] = require $file);
    }
}
