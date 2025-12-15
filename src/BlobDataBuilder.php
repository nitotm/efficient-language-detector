<?php
/**
 * @copyright 2025 Nito T.M.
 * @license https://www.apache.org/licenses/LICENSE-2.0 Apache-2.0
 * @author Nito T.M. (https://github.com/nitotm)
 * @package nitotm/efficient-language-detector
 */

namespace Nitotm\Eld;

use OutOfRangeException;
use RuntimeException;

/**
 * Builder for the low memory usage databases
 */
class BlobDataBuilder extends LanguageData
{
    public const NEW_LINE = PHP_SAPI === 'cli' ? PHP_EOL : '<br>';
    protected ?string $databaseInput;
    private string $blobFolder = 'blob/';
    private string $fileName;
    private bool $arrayReady;
    private bool $subsetFile = false;

    public function __construct(string $databaseFile, ?array $languages = null)
    {
        $this->databaseInput = $databaseFile;
        $this->loadData($databaseFile, null, EldMode::MODE_ARRAY);
        $this->fileName = $this->databaseName;
        if ($this->isSubset) {
            $this->subsetFile = true;
        }

        if ($languages) {
            $subset = $this->langSubset($languages);
            if ($subset->file) {
                echo 'Subset done for found languages: ' . json_encode($subset->languages) . self::NEW_LINE;
                $this->fileName = $subset->file;
                $this->subsetFile = true;
            } else {
                throw new RuntimeException(
                    'langSubset() did not return a subset file name.' .
                    ($subset->error ? ' Error: ' . $subset->error : '')
                );
            }
        }

        $this->arrayReady = true;
    }

    public function buildDatabase(): bool
    {
        if (!$this->arrayReady) {
            return false;
        }
        $saveFolder = $this->blobFolder;
        if ($this->subsetFile) {
            $saveFolder .= 'subset/';
        }

        /* Build data file
        *   File contains fixed size sequence of language id and language score, no separators, its position in the
        *    file links it to a ngram, position data is at index file. 3 bytes per data point.
        *   Language id:    Unsigned 8-bit integer   C   1 byte    0–255
        *   Language score: Unsigned 16-bit integer  n   2 bytes   0–65,535 Big-endian
        */
        $blobData = '';
        $countData = 0;
        // $dataSlotSize = 3;

        // $highestScore = max(array_map('max', $this->ngrams)); v3 XL: 26.48, L: 30.9, M: 6.57, S: 2.09
        // A variable could be stored to get a dynamic divisor, but for now max 31 -> 65535 / 31 ≈ 2100
        $floatMultiplier = 2100;

        // To make use of OPcache lets make it a PHP file for 'string' on RAM mode
        $nowDocHeader = "<?php\nreturn <<<'" . self::BLOB_DOC_ID . "'\n"; // make it long enough to avoid collisions

        if ((strlen($nowDocHeader) !== self::BLOB_HEAD_LEN)) {
            throw new RuntimeException(
                "Data file header length: " . strlen(
                    $nowDocHeader
                ) . " has to match established BLOB_HEAD_LEN: " . self::BLOB_HEAD_LEN
            );
        }

        foreach ($this->ngrams as $values) {
            foreach ($values as $language => $score) {
                $scoreInt = (int)round($score * $floatMultiplier);

                if ($language < 0 || $language > 255) {
                    throw new OutOfRangeException("language 8-bit value must be 0-255: " . $language);
                }
                if ($scoreInt < 0 || $scoreInt > 65535) {
                    throw new OutOfRangeException(
                        "score 16-bit value must be 0-65535: " . $scoreInt . " converted from: " . $score
                    );
                }
                $blobData .= pack('Cn', $language, $scoreInt);
                $countData++;
            }
        }

        $blobData = $nowDocHeader . $blobData . self::BLOB_CHECK . "\n" . self::BLOB_DOC_ID . ";";
        $saveSucc = file_put_contents($this->ngramsFolder . $saveFolder . $this->fileName . '.data.php', $blobData);

        if (!$saveSucc) {
            throw new RuntimeException("Failed to save {$this->fileName}.data.php");
        }

        echo 'Completed: ' . $saveFolder . $this->fileName . '.data.php' . self::NEW_LINE;

        /* Build index file
             File contains fixed size sequence of ngram identifier, data position, data length, its position in the
              file is decided by the ngram hash. Total of 8 bytes per index point.

             Ngram identifier: 4 raw bytes, better than 32bit int? seems safe enough since has already matched hash
             Data offset: Unsigned 24-bit integer      3 bytes   0–16,777,215 Big-endian
             Data points: Unsigned 8-bit integer   C   1 byte    0–255
         */
        if ($countData + self::BLOB_HEAD_LEN > 16777215) { // max 24-bit, we use 3 byte int for offset position
            throw new RuntimeException("Data file to big, max ~16M data-points");
        }

        $ngramsCount = count($this->ngrams);
        $filename = $this->ngramsFolder . $saveFolder . $this->fileName . '.index.php';
        $dataPosition = 0;

        if ($ngramsCount === 0) {
            throw new RuntimeException("No ngrams data found");
        }

        // Increase hash table capacity, for smaller databases, to reduce load factor and speed up linear probing
        if ($ngramsCount < 10000) {
            $M = $ngramsCount * 5;
        } elseif ($ngramsCount < 30000) {
            $M = $ngramsCount * 4;
        } elseif ($ngramsCount < 100000) {
            $M = $ngramsCount * 3;
        } else {
            // M = 2 * N  →  load factor ≈ 0.5
            // Expected avg. succ probes 1.5, unsucc probes 4, 'extralarge' v3 index size ~40mb
            $M = $ngramsCount * 2;
        }

        $slotSize = 8;
        $hashTableSize = $M * $slotSize;

        // Create file filled with zeros in one shot
        file_put_contents(
            $filename,
            $nowDocHeader . str_repeat("\0", $hashTableSize) . self::BLOB_CHECK . "\n" . self::BLOB_DOC_ID . ";"
        );
        $fp = fopen($filename, 'r+b');

        if (!$fp) {
            throw new RuntimeException("Unable to open $filename");
        }

        foreach ($this->ngrams as $ngram => $values) {
            $countScores = count($values);
            $hash32 = crc32($ngram);
            // crc32c distribution is better, so it might make it up for the speed
            $slot = $hash32 % $M;

            // Linear probing
            $startSlot = $slot;

            do {
                fseek($fp, $slot * $slotSize + self::BLOB_HEAD_LEN);
                $current = fread($fp, $slotSize);

                // Empty slot
                if ($current === "\0\0\0\0\0\0\0\0") {
                    // Write our two 32-bit values (little-endian, works everywhere)
                    $packed = str_pad(substr($ngram, 0, 4), 4, "\0") .
                        //pack('C3', $dataPosition>>16, $dataPosition>>8, $dataPosition).
                        substr(pack('N', $dataPosition), 1) .
                        pack('C', $countScores);

                    fseek($fp, $slot * $slotSize + self::BLOB_HEAD_LEN);
                    fwrite($fp, $packed);

                    $slot = null;
                    break;
                }

                $slot = ($slot + 1) % $M;
            } while ($slot !== $startSlot);

            $dataPosition += $countScores;

            // This should never happen with α = 0.5
            if ($slot === $startSlot) {
                throw new RuntimeException('Hash table full, increase size or rehash');
            }
        }

        echo 'Completed: ' . $saveFolder . $this->fileName . '.index.php' . self::NEW_LINE;

        /*
        * Build info file
        */
        $infoArray = [
            'type' => $this->dataType,
            'ngramLength' => $this->ngramLength,
            'ngramStride' => $this->ngramStride,
            'M' => $M,
            'languages' => $this->langCodes,
            'avgScore' => $this->avgScore,
            'blobVer' => 1
            // $this->isSubset = $ngramsData['isSubset'];
        ];
        $infoData = "<?php" . "\r\n"
            . "// Copyright 2025 Nito T.M. [ Apache 2.0 Licence https://www.apache.org/licenses/LICENSE-2.0 ]\r\n"
            . "return " . var_export($infoArray, true) . ';';

        $saveSucc = file_put_contents($this->ngramsFolder . $saveFolder . $this->fileName . '.php', $infoData);

        if (!$saveSucc) {
            throw new RuntimeException("Failed to save $this->fileName.php");
        }

        echo 'Completed: ' . $saveFolder . $this->fileName . '.php' . self::NEW_LINE .
            'The BLOB database was created successfully' . self::NEW_LINE . self::NEW_LINE .
            "Use file name '$this->fileName' to load ELD with it" . self::NEW_LINE .
            '$eld' . " = new LanguageDetector('$this->fileName', 'ISO639_1', 'bytes');";


        return true;
    }
}
