<?php
/*
Copyright 2019 Nito T.M.
Author URL: https://github.com/nitotm

Licensed under the Apache License, Version 2.0 (the "License");
you may not use this file except in compliance with the License.
You may obtain a copy of the License at

    http://www.apache.org/licenses/LICENSE-2.0

Unless required by applicable law or agreed to in writing, software
distributed under the License is distributed on an "AS IS" BASIS,
WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
See the License for the specific language governing permissions and
limitations under the License.
*/
declare(strict_types=1);

namespace Nitotm\Eld;

class LanguageSubset
{
    protected $subset = false;
    protected $loadedSubset = false;
    protected $ngrams = [];
    protected $langCodes = [];
    private $defaultNgrams = false;

    /**
     * When active, detect() will filter the languages not included at $subset, from the scores, with filterLangSubset()
     *
     * @param array|bool $langs
     * @return array|false
     */
    public function dynamicLangSubset($langs)
    {
        if ($langs) {
            $this->subset = [];
            foreach ($langs as $lang) {
                $foundLang = array_search($lang, $this->langCodes, true);
                if ($foundLang !== false) {
                    $this->subset[] = $foundLang;
                }
            }
            sort($this->subset);
        } else {
            $this->subset = false;
        }

        return $this->subset;
    }


    /**
     * Removes the excluded languages form the ngrams database
     * if $save option is true, the new ngrams subset will be stored, and next loaded for the same language subset
     *
     * @param array|bool $langs
     * @return string|true
     */
    public function langSubset($langs, bool $save = true, bool $safe = false)
    {
        if (!$langs) {
            if ($this->loadedSubset) {
                $this->ngrams = $this->defaultNgrams;
                $this->loadedSubset = false;
            }

            return true;
        }

        $langs_array = $this->dynamicLangSubset($langs);
        if (!$langs_array) {
            return 'No languages found';
        }
        $this->subset = false; // We use dynamicLangSubset() to filter languages, but set dynamic subset to false

        if ($this->defaultNgrams === false) {
            $this->defaultNgrams = $this->ngrams;
        }

        $new_subset = hash('sha1', implode(',', $langs_array));
        $file_name = __DIR__ . '/ngrams/ngrams.' . $new_subset . '.php';

        if ($this->loadedSubset !== $new_subset) {
            $this->loadedSubset = $new_subset;

            if (file_exists($file_name)) {
                require $file_name;

                return true;
            }
            if ($this->ngrams !== $this->defaultNgrams) {
                $this->ngrams = $this->defaultNgrams;
            }

            foreach ($this->ngrams as $ngram => $langsID) {
                foreach ($langsID as $id => $value) {
                    if (!in_array($id, $langs_array, true)) {
                        unset($this->ngrams[$ngram][$id]);
                    }
                }
                if (!$this->ngrams[$ngram]) {
                    unset($this->ngrams[$ngram]);
                }
            }
        }

        if ($save) {
            if (!file_exists($file_name)) { // in case $this->loadedSubset !== $new_subset, and was previously saved
                file_put_contents(
                    $file_name,
                    '<?php' . "\r\n" . '// Do not edit unless you ensure you are using UTF-8 encoding' . "\r\n"
                    . '$this->ngrams=' . $this->ngramExport($this->ngrams, $safe) . ';'
                );
            }

            return $file_name;
        }

        return true;
    }

    /**
     * Filters languages not included in the subset, from the results scores
     */
    protected function filterLangSubset(array $results): array
    {
        foreach ($results as $langID => $score) {
            if (!in_array($langID, $this->subset, true)) {
                unset($results[$langID]);
            }
        }

        return $results;
    }

    /**
     * @param array|int $data
     */
    protected function ngramExport($data, bool $safe = false): ?string
    {
        if (is_array($data)) {
            $toImplode = array();
            foreach ($data as $key => $value) {
                $toImplode[] = ($safe === true ? '"\\x' . substr(chunk_split(bin2hex($key), 2, '\\x'), 0, -2) . '"'
                        : var_export($key, true)) . '=>' . $this->ngramExport($value);
            }

            return '[' . implode(',', $toImplode) . ']';
        }

        return var_export($data, true);
    }
}
