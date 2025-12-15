<?php
/**
 * @copyright 2024 Nito T.M.
 * @license https://www.apache.org/licenses/LICENSE-2.0 Apache-2.0
 * @author Nito T.M. (https://github.com/nitotm)
 * @package nitotm/efficient-language-detector
 */

declare(strict_types=1);

namespace Nitotm\Eld;

trait LanguageSubsetTools
{
    protected ?string $firstDatabaseInput = null;

    /**
     * Sets a subset and removes the excluded languages form the ngrams database
     * if $save option is true, the new ngrams subset will be stored, and loaded next time
     *
     * @param null|string[] $languages
     */
    public function langSubset(?array $languages = null, bool $save = true, bool $encode = true): SubsetResult
    {
        if (!$languages) {
            // remove any subset
            if ($this->getDatabaseMode($this->modeInput) === EldMode::MODE_ARRAY
                && $this->loadedSubset && $this->defaultNgrams) {
                // defaultNgrams can only from initial databaseInput (firstDatabaseInput)
                $this->ngrams = $this->defaultNgrams;
                $this->loadedSubset = null;
            }

            if (!$this->isInitialized) {
                // Revert pending lazy load subset
                $this->databaseInput = ($this->firstDatabaseInput ?? $this->databaseInput);
                $this->firstDatabaseInput = null;
                // Initialize since we will send available languages
                $this->loadData($this->databaseInput, $this->schemeInput, $this->modeInput);
            } elseif ($this->firstDatabaseInput) {
                // We are initialized, we loaded a subset database that is not initial databaseInput, reload initial
                // TODO I could check if firstDatabaseInput is also a subset, but then how to proceed
                $this->loadData($this->firstDatabaseInput, $this->schemeInput, $this->modeInput);
                $this->databaseInput = $this->firstDatabaseInput;
                $this->firstDatabaseInput = null;
            }

            $this->dynamicSubset = null;
            return new SubsetResult(
                true,
                $this->indicesToStrings(array_keys($this->langCodes), $this->outputLanguages)
            );
        }

        $langArray = $this->makeSubset($languages); // subset with all possible ELD available languages
        if (!$langArray) {
            return new SubsetResult(false, null, 'No language matched this set');
        }
        $newSubset = $this->uniqueIntegersToString($langArray);
        $inputDBsize = $this->inputDatabaseSize($this->databaseInput);

        if (!$this->isInitialized) {
            // Get minimum data to check if an available cached matching subset is stored, before loading heavy data
            $this->databaseMode = $this->getDatabaseMode($this->modeInput);
            $this->loadoutputScheme($this->schemeInput);
        }

        // TODO v4, does it matter if matching subset is encoded or not? Should nod differentiate, but save as required
        $baseName = $inputDBsize . '_' . count(
            $langArray
        ) . '_' . (!$encode && $this->databaseMode === EldMode::MODE_ARRAY ? 'd' : '') . $newSubset;
        $filePath = $this->ngramsFolder .
            ($this->databaseMode === EldMode::MODE_ARRAY ? '' : 'blob/') .
            'subset/' . $baseName . '.php';

        // This will load a database potentially bigger than the databaseInput, but user is asking for more languages
        if (file_exists($filePath)) {
            if ($this->loadedSubset !== $newSubset) { // Size & mode are fix, all subsets loaded have same DB size
                if (!$this->isInitialized) {
                    // It would be much easier if we actually initialized data here, but lets to do it more lazy
                    $this->firstDatabaseInput = $this->databaseInput;
                    $this->databaseInput = $baseName;
                } elseif (array_values($langArray) === array_keys($this->langCodes)) {  // already ordered
                    // We are initialized & might not be on a subset, but current DB languages match selection
                    $this->dynamicSubset = null;
                    if ($this->defaultNgrams && $this->defaultNgrams !== $this->ngrams) { // for 'array' mode
                        $this->ngrams = $this->defaultNgrams;
                    }
                } else {
                    // If we are initialized, & data is loaded, we cannot keep loading pre-saved subsets into static
                    //  self::$fileContents or we would fill the RAM, so load locally for this case scenario
                    $this->loadData($baseName, $this->outputScheme, $this->databaseMode, false);
                }
                $this->loadedSubset = $newSubset;
            }
            return new SubsetResult(
                true,
                $this->indicesToStrings($langArray, $this->outputLanguages),
                null,
                $baseName // we can return this for non 'array' modes too, as it already is saved
            );
            // we don't need to do a dynamicSubset for non 'array' modes, langCodes will match when loaded
        }

        if (!$this->isInitialized) {
            // Just in case we had a pending lazy load subset, revert and use
            $this->databaseInput = ($this->firstDatabaseInput ?? $this->databaseInput);
            $this->firstDatabaseInput = null;
            // We have to initialize, we need to know available languages for input database
            $this->loadData($this->databaseInput, $this->schemeInput, $this->modeInput);
        }
        // We are Initialized, and pre-saved subset (from all possible languages) was not found

        if ($this->firstDatabaseInput) {
            // We have done a lazy load, of a subset database file, that was not initial databaseInput
            //  in this case we will load firstDatabaseInput in static mode
            // TODO I could check if firstDatabaseInput is also a subset, but then how to proceed
            $this->loadData($this->firstDatabaseInput, $this->schemeInput, $this->modeInput);
            $this->databaseInput = $this->firstDatabaseInput;
            $this->firstDatabaseInput = null;
        }

        /*
        * Make a subset only from available languages at database
        * This is an inconsistency, versus if we found an already build and saved subset with all possible languages
        * but if we load a full database, not being asked at databaseInput, the process could crash due to memory usage
        * If databaseInput is subset, you can load any pre-build subset or built one only with the available languages
        * at subset, and we can only build a new subset from all ELD available languages if full database is loaded
        */
        $langArray = $this->makeSubset($languages, $this->langCodes); // subset with available languages at database

        if (!$langArray) {
            return new SubsetResult(false, null, 'No language matched this set');
        }
        // count check is enough since $langArray is restricted to langCodes
        if (count($langArray) === count($this->langCodes)) {
            $this->dynamicSubset = null;
            return new SubsetResult(
                true,
                $this->indicesToStrings($langArray, $this->outputLanguages),
                'All languages from loaded DB selected, not a new subset'
            );
        }

        $newSubset = $this->uniqueIntegersToString($langArray);

        if ($this->loadedSubset === $newSubset) {
            // We have already loaded/set the current subset
            return new SubsetResult(
                true,
                $this->indicesToStrings($langArray, $this->outputLanguages),
                'Subset matches current subset'
            );
        }

        $this->dynamicSubset = array_flip($langArray);
        $this->loadedSubset = $newSubset;

        if ($this->databaseMode === EldMode::MODE_ARRAY) {
            // In array mode we make a ngram subset on the spot
            if ($this->defaultNgrams === null) {
                $this->defaultNgrams = $this->ngrams;
            }
            $baseName = $this->dataType . '_' . count($langArray) . '_' . (!$encode ? 'd' : '') . $newSubset;

            foreach ($this->ngrams as $ngram => $languagesIds) {
                foreach ($languagesIds as $id => $value) {
                    if (!in_array($id, $langArray, true)) {
                        unset($this->ngrams[$ngram][$id]);
                    }
                }
                if (!$this->ngrams[$ngram]) {
                    unset($this->ngrams[$ngram]);
                }
            }

            $saved = false;
            if ($save) {
                $filePath = $this->ngramsFolder . 'subset/' . $baseName . '.php';
                if (file_exists($filePath)) {
                    $saved = true;
                } else {
                    $saved = $this->saveNgrams($filePath, $langArray, $encode);
                }
            }

            return new SubsetResult(
                true,
                $this->indicesToStrings($langArray, $this->outputLanguages),
                null,
                ($saved ? $baseName : null)
            );
        }

        // Non 'array' modes don't build/save subsets, we set dynamicSubset and job is done
        // TODO if subset matches current languages, did we load a subset previously? We could return file name
        return new SubsetResult(true, $this->indicesToStrings($langArray, $this->outputLanguages));
    }

    /**
     * Maps integer indices to corresponding string values.
     * Main use: convert ngram database language index (integer) to string output scheme
     *
     * @param int[] $indices
     * @param array<int, string> $strings
     * @return array<int, string>
     */
    protected function indicesToStrings(array $indices, array $strings): array
    {
        return array_intersect_key($strings, array_flip($indices));
    }

    /**
     * Validates an array of ISO 639-1 language strings or other selected scheme, given by the user, and creates a
     * subset of the valid languages compared against the current database available languages
     *
     * @param string[] $languages
     * @return null|int[]
     */
    protected function makeSubset(array $languages, ?array $availibleLangs = null): ?array
    {
        $subset = [];

        if ($languages) {
            $allLanguageSchemes = $this->loadFileContents($this->schemesFolder . 'allLanguageSchemes.php');

            foreach ($languages as $language) {
                $normalizedLanguage = $this->normalizeLanguage($language);
                $language_index = $allLanguageSchemes[$normalizedLanguage] ?? false;

                // Language found & we don't have an available language list OR it is in the list
                if ($language_index !== false && (!$availibleLangs || isset($availibleLangs[$language_index]))) {
                    $subset[] = $language_index;
                }
            }
            sort($subset);
        }

        return ($subset ?: null);
    }

    private function normalizeLanguage($string): string
    {
        $string = strtolower($string);
        // Replace all non-letter characters with a -
        $string = preg_replace('/[^a-z]+/', '-', $string);

        return trim($string, ' -');
    }

    /**
     * Generate a short unique string for any combination of unordered unique integers
     *
     * @param int[] $integers
     */
    private function uniqueIntegersToString(array $integers): string
    {
        // Split integers into groups which their combinations fit a 32-bit int
        // A bit overkill, but to avoid the use of math extensions, or long hashes
        $groups = [];
        foreach ($integers as $integer) {
            $groupIndex = intdiv($integer, 31);
            $relativeValue = $integer % 31;
            if (!isset($groups[$groupIndex])) {
                $groups[$groupIndex] = [];
            }
            $groups[$groupIndex][] = $relativeValue;
        }

        $base35Groups = [];
        for ($i = 0; $i <= max(array_keys($groups)); $i++) {
            if (isset($groups[$i])) {
                $uniqueNumber = 0;
                foreach ($groups[$i] as $value) {
                    // Create a unique number, by treating it like a bitfield
                    $uniqueNumber += 2 ** $value;
                }
                // We use base35, to make a seamless union with 'z' later
                $base35Groups[] = base_convert((string)$uniqueNumber, 10, 35);
            } else {
                // Empty groups are necessary to implode a unique string
                $base35Groups[] = '';
            }
        }
        return implode('z', $base35Groups);
    }

    /**
     * Get database size before initialization, extract size from file name in case is language subset database
     */
    protected function inputDatabaseSize(?string $file): ?string
    {
        if (!$file) {
            return EldDataFile::SMALL;
        }

        $pos = strpos($file, '_');

        if ($pos !== false) {
            $file = substr($file, 0, $pos);
        }

        $size = strtolower(trim($file));

        if (in_array($size, EldDataFile::values(), true)) {
            return $size;
        }
        return null;
    }

    /**
     * @param int[] $langArray
     */
    private function saveNgrams(string $filePath, array $langArray, bool $encode): bool
    {
        // in case $this->loadedSubset !== $newSubset, and was previously saved
        if (!file_exists($filePath) && !file_put_contents(
            $filePath,
            "<?php" . "\r\n" // Not using PHP_EOL, so the file is formatted for all SO
                . "// Copyright 2025 Nito T.M. [ Apache 2.0 Licence https://www.apache.org/licenses/LICENSE-2.0 ]\r\n"
                . (!$encode ? "// Editing this file could break the UTF-8 encoding\r\n" : '')
                . "return [\r\n"
                . "'type' => '" . $this->dataType . "',\r\n"
                . "'ngramLength' => " . $this->ngramLength . ",\r\n"
                . "'ngramStride' => " . $this->ngramStride . ",\r\n"
                // $this->langCodes are all included in the loaded database, $langArray is subset
                . "'languages' => " . var_export($this->indicesToStrings($langArray, $this->langCodes), true) . ",\r\n"
                . "'isSubset' => true,\r\n"
                . "'avgScore' => " . var_export($this->avgScore, true) . ",\r\n"
                . "'ngrams' =>" . $this->ngramExport($this->ngrams, $encode) . "\r\n"
                . "];"
        )) {
            return false;
        }
        return true;
    }

    /**
     * Generates a compact string representation of the ngram data, for storage, reducing file size
     *
     * @param float|array<int, float>|array<string, array<int, float>> $data
     */
    private function ngramExport($data, bool $encode = false): ?string
    {
        if (is_array($data)) {
            $toImplode = array();
            foreach ($data as $key => $value) {
                $toImplode[] = ($encode === true && is_string($key) ?
                        '"' . $this->safeString($key) . '"'
                        : var_export($key, true)
                    ) . '=>' . $this->ngramExport($value);
            }

            return '[' . implode(',', $toImplode) . ']';
        }

        return var_export($data, true);
    }

    private function safeString($str): string
    {
        $result = '';
        $iMax = strlen($str);
        for ($i = 0; $i < $iMax; $i++) {
            $char = $str[$i];

            // Check if the character needs to be hex-encoded
            if (ord($char) > 126 || ord($char) < 32 || $char === '\\' || $char === '"') {
                $result .= '\\x' . bin2hex($char);
            } else {
                $result .= $char;
            }
        }

        return $result;
    }
}
