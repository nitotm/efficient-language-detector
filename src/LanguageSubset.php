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

/* 
To reduce the languages to be detected, there are 3 different options, they only need to be executed once.

The fastest option to regularly use the same language subset, will be to add as an argument the file stored (and returned) by langSubset(), when creating an instance of the languageDetector class. In this case the subset ngrams database will be loaded directly, and not the default database. Also, you can use this option to load different ngram databases.
*/

namespace Nitotm\Eld;

class LanguageSubset
{
    protected $subset = false;
    protected $loadedSubset = false;
    private $defaultNgrams = false;

    // dynamicLangSubset() Will execute the detector normally, but at the end it will filter the excluded languages.
    public function dynamicLangSubset($langs)
    {
        if ($langs) {
            $this->subset = [];
            foreach ($langs as $value) {
                $lang = array_search($value, $this->langCodes);
                if ($lang !== false) {
                    $this->subset[] = $lang;
                }
            }
            sort($this->subset);
        } else {
            $this->subset = false;
        }

        return $this->subset;
    }

    // langSubset($langs,$save=true) Will previously remove the excluded languages form the ngrams database; for a single detection might be slower than dynamicLangSubset(), but for multiple strings will be faster. if $save option is true (default), the new ngrams subset will be stored, and next loaded for the same language subset, increasing startup speed.
    public function langSubset($langs, $save = true, $safe = false)
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
                    if (!in_array($id, $langs_array)) {
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
                file_put_contents($file_name,
                    '<?php' . "\r\n" . '// Do not edit unless you ensure you are using UTF-8 encoding' . "\r\n"
                    . '$this->ngrams=' . $this->ngram_export($this->ngrams, $safe) . ';'
                );
            }

            return $file_name;
        }

        return true;
    }

    protected function filterLangSubset($results)
    {
        foreach ($results as $key => $value) {
            if (!in_array($key, $this->subset)) {
                unset($results[$key]);
            }
        }

        return $results;
    }

    protected function ngram_export($var, $safe = false)
    {
        if (is_array($var)) {
            $toImplode = array();
            foreach ($var as $key => $value) {
                $toImplode[] = ($safe === true ? '"\\x' . substr(chunk_split(bin2hex($key), 2, '\\x'), 0, -2) . '"'
                        : var_export($key, true)) . '=>' . $this->ngram_export($value);
            }

            return '[' . implode(',', $toImplode) . ']';
        } else {
            return var_export($var, true);
        }
    }


}
