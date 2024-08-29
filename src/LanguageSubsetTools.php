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
    /**
     * Sets a subset and removes the excluded languages form the ngrams database
     * if $save option is true, the new ngrams subset will be stored, and cached for next time
     *
     * @param null|string[] $languages
     */
    public function langSubset(?array $languages = null, bool $save = true, bool $encode = true): SubsetResult
    {
        if (!$languages) {
            if ($this->loadedSubset && $this->defaultNgrams) {
                $this->ngrams = $this->defaultNgrams;
                $this->loadedSubset = null;
            }
            return new SubsetResult(true); // if there was already no subset to disable, it also is successful
        }

        $langArray = $this->makeSubset($languages);
        if (!$langArray) {
            return new SubsetResult(false, null, 'No language matched this set');
        }

        if ($this->defaultNgrams === null) {
            $this->defaultNgrams = $this->ngrams;
        }

        $newSubset = $this->uniqueIntegersToString($langArray);
        $baseName = $this->dataType . '_' . count($langArray) . '_' . (!$encode ? 'd' : '') . $newSubset;
        $filePath = $this->ngramsFolder . 'subset/' . $baseName . '.php';
        // TODO: if default loaded ngrams are already a subset (and lack languages): send warning or load main database
        if ($this->loadedSubset !== $newSubset) {
            $this->loadedSubset = $newSubset;

            if (file_exists($filePath)) {
                $ngramsData = include $filePath;
                if (isset($ngramsData['ngrams'])) {
                    $this->ngrams = $ngramsData['ngrams'];

                    return new SubsetResult(true, $this->isoLanguages($langArray), null, $baseName);
                }
            }
            if ($this->ngrams !== $this->defaultNgrams) {
                $this->ngrams = $this->defaultNgrams;
            }

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
        }

        $saved = false;
        if ($save) {
            $saved = $this->saveNgrams($filePath, $langArray, $encode);
        }

        return new SubsetResult(true, $this->isoLanguages($langArray), null, ($saved ? $baseName : null));
    }

    /**
     * Validates an expected array of ISO 639-1 language code strings, given by the user, and creates a subset of the
     * valid languages compared against the current database available languages
     *
     * @param string[] $languages
     * @return null|int[]
     */
    protected function makeSubset(array $languages): ?array
    {
        $subset = [];
        if ($languages) {
            foreach ($languages as $language) {
                $normalizedLanguage = $this->normalizeLanguage($language);
                // $langCodes are always ISO 639-1
                $foundLang = array_search($normalizedLanguage, $this->langCodes, true);
                if ($foundLang === false && $this->outputFormat !== EldFormat::ISO639_1) {
                    // check if it has selected output format, which can be formatted
                    $foundMatch = array_search(
                        $this->normalizeLanguage($language),
                        array_map([$this, 'normalizeLanguage'], $this->outputLanguages),
                        true
                    );
                    if ($foundMatch !== false && isset($this->langCodes[$foundMatch])) {
                        $foundLang = $foundMatch;
                    }
                }
                if ($foundLang !== false) {
                    $subset[] = $foundLang;
                }
            }
            sort($subset);
        }

        return ($subset ?: null);
    }

    private function normalizeLanguage($string): string
    {
        $string = strtolower($string);
        // Replace all non-letter characters with a single space
        $string = preg_replace('/[^a-z]+/', ' ', $string);

        return trim($string);
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
                // Empty groups necessary to implode a unique string
                $base35Groups[] = '';
            }
        }
        return implode('z', $base35Groups);
    }

    /**
     * Converts ngram database language index (integer) to string output format
     *
     * @param int[] $langSet
     * @return array<int, string>
     */
    protected function isoLanguages(array $langSet): array
    {
        $newLangCodes = [];
        foreach ($langSet as $langID) {
            $newLangCodes[$langID] = $this->outputLanguages[$langID]; //$this->langCodes[$langID];
        }
        return $newLangCodes;
    }

    /**
     * @param int[] $langArray
     */
    protected function saveNgrams(string $filePath, array $langArray, bool $encode): bool
    {
        // in case $this->loadedSubset !== $newSubset, and was previously saved
        if (!file_exists($filePath) && !file_put_contents(
            $filePath,
            "<?php" . "\r\n" // Not using PHP_EOL, so the file is formatted for all SO
                . "// Copyright 2024 Nito T.M. [ Apache 2.0 Licence https://www.apache.org/licenses/LICENSE-2.0 ]\r\n"
                . (!$encode ? "// Editing this file could break the UTF-8 encoding\r\n" : '')
                . "return [\r\n"
                . "'type' => '" . $this->dataType . "',\r\n"
                . "'ngramLength' => " . $this->ngramLength .  ",\r\n"
                . "'ngramStride' => " . $this->ngramStride .  ",\r\n"
                . "'languages' => " . var_export($this->isoLanguages($langArray), true) . ",\r\n"
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
     * Generates a compact string representation of the ngram database for storage, reducing file size
     *
     * @param int|array<int, int>|array<string, array<int, int>> $data
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
