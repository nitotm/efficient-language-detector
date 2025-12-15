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

    public const BLOB_CHECK = 'BLOB_DATA_END';
    public const BLOB_DOC_ID = 'ELD_BLOB_DATABASE';
    public const BLOB_HEAD_LEN = 36;
    private static array $fileContents = [];
    private static array $blobContents = [];
    /** @var array<string, array<int, float>> $ngrams */
    protected array $ngrams;
    /** @var array<int, float> $avgScore */
    protected array $avgScore;

    /** @var non-empty-array<int, float> $langScore */
    protected array $langScore;
    /** @var array<int, string> $langCodes */
    protected array $langCodes;
    /** @var array<int, string> $outputLanguages */
    protected array $outputLanguages;
    /** @var null|array<string, array<int, float>> $defaultNgrams */
    protected ?array $defaultNgrams = null;
    protected ?string $loadedSubset = null;
    protected string $ngramsFolder = __DIR__ . '/../resources/ngrams/';
    protected string $schemesFolder = __DIR__ . '/../resources/schemes/';
    protected string $dataType;
    protected int $ngramLength;
    protected int $ngramStride;
    protected ?string $outputScheme = null;
    protected bool $isSubset;
    /** @var array<int, bool> $dynamicSubset */
    protected ?array $dynamicSubset = null;
    protected bool $isInitialized = false;
    protected ?string $databaseInput;
    protected ?string $schemeInput;
    protected string $modeInput;
    protected string $databaseName;
    /** @var string|bool */
    protected $indexBlob = false;
    /** @var string|bool */
    protected $dataBlob = false;
    /** @var resource|bool */
    protected $indexStream = false;
    /** @var resource|bool */
    protected $dataStream = false;
    protected int $IndexM;
    protected string $databaseMode;

    protected function loadData(
        ?string $databaseInput = null,
        ?string $schemeInput = null,
        string $modeInput = 'array',
        bool $static = true
    ): void {
        $folder = $this->ngramsFolder;
        // Normalize file name or use default file
        $fileBaseName = ($databaseInput === null ? EldDataFile::SMALL : preg_replace(
            '/\.php$/',
            '',
            strtolower($databaseInput)
        ));

        $this->databaseMode = $this->getDatabaseMode($modeInput);

        if ($this->databaseMode !== EldMode::MODE_ARRAY) {
            $folder .= 'blob/';
        }
        // if file does not exist, check if it's a subset
        if (!file_exists($folder . $fileBaseName . '.php')) {
            $folder .= 'subset/';
            if (!file_exists($folder . $fileBaseName . '.php')) {
                if ($this->databaseMode !== EldMode::MODE_ARRAY && in_array(
                    $fileBaseName,
                    [EldDataFile::MEDIUM, EldDataFile::LARGE],
                    true
                )) {
                    throw new InvalidArgumentException(
                        "Database modes 'string', 'bytes', 'disk' do not ship with size "
                        . $fileBaseName . ", build with BlobDataBuilder()."
                    );
                }

                throw new InvalidArgumentException(sprintf('Database file "%s" not found', $fileBaseName));
            }
        }

        if ($this->databaseMode === EldMode::MODE_ARRAY) {
            // Send warning if OPcache is active and interned_strings_buffer is too low
            InternedWarning::checkAndSend($databaseInput);

            $memory_limit = $this->getMemoryLimit();
            // Basic memory limit check, so we don't assume necessary free memory, database could be cached
            if (($fileBaseName === EldDataFile::MEDIUM && $memory_limit < 256) // Approximate memory requirements
                || ($fileBaseName === EldDataFile::LARGE && $memory_limit < 1000)
                || ($fileBaseName === EldDataFile::EXTRALARGE && $memory_limit < 2000)
            ) {
                throw new RuntimeException(
                    'Database too large for memory_limit, increase, choose smaller database, or change mode.'
                );
            }
        }

        $ngramsData = $this->loadFileContents($folder . $fileBaseName . '.php', $static);
        if (empty($ngramsData['languages']) || ($this->databaseMode === 'array' && empty($ngramsData['ngrams']))) {
            throw new RuntimeException(sprintf('File "%s" data is invalid', $fileBaseName));
        }
        $this->databaseName = $fileBaseName;
        $this->langCodes = $ngramsData['languages'];
        $this->dataType = $ngramsData['type'];
        $this->ngramLength = $ngramsData['ngramLength'];
        $this->ngramStride = $ngramsData['ngramStride'];
        $this->isSubset = $ngramsData['isSubset'] ?? false;
        $this->avgScore = $ngramsData['avgScore'];
        /** @var int $maxLang Highest language index key */
        /** @psalm-suppress ArgumentTypeCoercion */
        $maxLang = max(array_keys($this->langCodes));
        $this->langScore = array_fill(0, $maxLang + 1, 1.0);

        if ($this->databaseMode === EldMode::MODE_ARRAY) {
            $this->ngrams = $ngramsData['ngrams'];
        } else {
            $this->IndexM = $ngramsData['M'];

            if ($this->databaseMode === EldMode::MODE_DISK) {
                // NOTE, DISK mode is almost 2x slower in PHP 8 vs 7.4, not a problem of stream_get_contents vs fread
                if ($this->indexStream) {
                    fclose($this->indexStream);
                    fclose($this->dataStream);
                }
                $this->indexStream = fopen($folder . $fileBaseName . '.index.php', 'rb');
                if ($this->indexStream === false) {
                    throw new RuntimeException("Failed to open file: " . $folder . $fileBaseName . '.index.php');
                }
                $this->dataStream = fopen($folder . $fileBaseName . '.data.php', 'rb');
                if ($this->dataStream === false) {
                    throw new RuntimeException("Failed to open file: " . $folder . $fileBaseName . '.data.php');
                }
            } elseif ($this->databaseMode === EldMode::MODE_STRING) {
                $this->indexBlob = $this->loadFileContents($folder . $fileBaseName . '.index.php', $static);
                $this->dataBlob = $this->loadFileContents($folder . $fileBaseName . '.data.php', $static);
            } elseif ($this->databaseMode === EldMode::MODE_BYTES) {
                $this->indexBlob = $this->loadBlobContents(
                    $folder . $fileBaseName . '.index.php',
                    self::BLOB_HEAD_LEN,
                    $static
                );
                $this->dataBlob = $this->loadBlobContents(
                    $folder . $fileBaseName . '.data.php',
                    self::BLOB_HEAD_LEN,
                    $static
                );
            }
            $indexCheck = false;
            $dataCheck = false;
            // Fast simple data check
            if ($this->databaseMode === EldMode::MODE_STRING) {
                $indexCheck = substr($this->indexBlob, -strlen(self::BLOB_CHECK)) === self::BLOB_CHECK;
                $dataCheck = substr($this->dataBlob, -strlen(self::BLOB_CHECK)) === self::BLOB_CHECK;
            } elseif ($this->databaseMode === EldMode::MODE_BYTES) {
                // -1 to include ; -> ELD_BLOB_DATABASE;
                $indexCheck = substr($this->indexBlob, -strlen(self::BLOB_DOC_ID) - 1) === self::BLOB_DOC_ID . ';';
                $dataCheck = substr($this->dataBlob, -strlen(self::BLOB_DOC_ID) - 1) === self::BLOB_DOC_ID . ';';
            } elseif ($this->databaseMode === EldMode::MODE_DISK) {
                // -1 to include ; -> ELD_BLOB_DATABASE;
                $end_block_size = strlen(self::BLOB_DOC_ID) + 1;
                fseek($this->indexStream, -$end_block_size, SEEK_END);
                $indexCheck = fread($this->indexStream, $end_block_size) === self::BLOB_DOC_ID . ';';
                //$indexCheck = stream_get_contents($this->indexStream, $end_block_size) === self::BLOB_DOC_ID . ';';
                fseek($this->dataStream, -$end_block_size, SEEK_END);
                $dataCheck = fread($this->dataStream, $end_block_size) === self::BLOB_DOC_ID . ';';
            }

            if (!$indexCheck) {
                throw new RuntimeException("Invalid data at file: " . $folder . $fileBaseName . '.index.php');
            }
            if (!$dataCheck) {
                throw new RuntimeException("Invalid data at file: " . $folder . $fileBaseName . '.data.php');
            }
        }

        $this->loadOutputScheme($schemeInput);

        $this->isInitialized = true;
    }

    /*
    * getFreeMemory() problem: Database could be cached, so we might not need free memory
    */
    // private function getFreeMemory() {

    private function getDatabaseMode(string $modeInput): string
    {
        $databaseMode = strtolower($modeInput);

        if (in_array($databaseMode, EldMode::values(), true)) {
            return $databaseMode;
        }

        throw new InvalidArgumentException("Invalid database mode: " . $databaseMode);
    }

    private function getMemoryLimit()
    {
        $v = trim(ini_get('memory_limit') ?: '-1');
        if ($v === '-1') {
            return 9999; // We could check system memory
        }

        $unit = strtolower(substr($v, -1));
        $num = (int)$v;
        return $num * ($unit === 'g' ? 1000 : 1); // we handle M and G
        //$current  = memory_get_usage(true) / 1024;
        //return $max-$current;
    }

    /**
     * Prevent including the same file multiple times. TODO for PHP8 :array|string
     */
    private function loadFileContents(string $file, bool $static = true)
    {
        if ($static) {
            return self::$fileContents[$file] ?? (self::$fileContents[$file] = require $file);
        }

        return require $file;
    }

    private function loadBlobContents(string $file, int $offset = 0, bool $static = true)
    {
        $contents = self::$blobContents[$file] ?? file_get_contents($file, false, null, $offset);
        if ($contents === false) {
            throw new RuntimeException("Failed to open file: " . $file);
        }
        if ($static) {
            return self::$blobContents[$file] ?? (self::$blobContents[$file] = $contents);
        }

        return $contents;
    }

    // TODO for PHP8 :bool|string
    protected function loadOutputScheme(?string $schemeInput, bool $isCritical = true): bool
    {
        // Normalize Scheme to avoid case sensitivity issues
        $normalizedScheme = str_replace('-', '_', strtoupper($schemeInput ?? EldFormat::ISO639_1));
        if ($normalizedScheme === $this->outputScheme) {
            return true;
        }
        if (in_array($normalizedScheme, EldFormat::values(), true)) {
            $this->outputLanguages = $this->loadFileContents(
                $this->schemesFolder . strtolower($normalizedScheme) . '.php'
            );
            $this->outputScheme = $normalizedScheme;
            return true;
        }

        if ($isCritical) {
            throw new InvalidArgumentException("Invalid scheme: $schemeInput");
        }
        return false;
    }
}
