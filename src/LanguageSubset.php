<?php
/** legal notice: https://github.com/nitotm/efficient-language-detector/blob/main/legal.md */

declare(strict_types = 1);

namespace Nitotm\Eld;

class LanguageSubset
{
    /** @var null|string[] */
    public ?array $subset = null;
    protected ?string $loadedSubset = null;
    /** @var array<string|int,array<int,int>> $defaultNgrams */ // TODO is key int or string? code is unclear, I cannot construct type
    private ?array $defaultNgrams = null;

    public function __construct(
        private readonly LanguageData $languageData,
    ) {
    }

    /**
     * @param string[] $langs
     *
     * @return string[]
     */
    public function limitTo(array $langs = []):array
    {
        $this->subset = [];
        foreach ($langs as $lang) {
            if (in_array($lang, $this->languageData->langCodes, true)) {
                $this->subset[] = $lang;
            }
        }
        sort($this->subset);

        return $this->subset;
    }

    /**
     * @param null|string[] $langs
     */
    public function langSubset(?array $langs = null, bool $save = true, bool $safe = false):bool|string
    {
        if ($langs === null) {
            if ($this->loadedSubset !== null && $this->defaultNgrams !== null) {
                $this->languageData->ngrams = $this->defaultNgrams;
                $this->loadedSubset = null;
            }

            return true;
        }

        $languages = $this->limitTo($langs);
        if ($languages === []) {
            return 'No languages found';
        }
        $this->subset = null; // We use dynamicLangSubset() to filter languages, but set dynamic subset to false

        if ($this->defaultNgrams === null) {
            $this->defaultNgrams = $this->languageData->ngrams;
        }

        $newsubset = hash('sha1', implode(',', $languages));
        $filename = LanguageData::getFullFilenameForNgramFile('ngrams.' . $newsubset . '.php');

        if ($this->loadedSubset !== $newsubset) {
            $this->loadedSubset = $newsubset;

            if (file_exists($filename)) {
                $this->languageData->ngrams = include $filename;

                return true;
            }
            if ($this->languageData->ngrams !== $this->defaultNgrams) {
                $this->languageData->ngrams = $this->defaultNgrams;
            }

            /**
             * @var array<int,int> $langsID
             */
            foreach ($this->languageData->ngrams as $ngram => $langsID) {
                foreach (array_keys($langsID) as $id) {
                    if (!in_array($id, $languages, true)) {
                        unset($this->languageData->ngrams[$ngram][$id]);
                    }
                }
                if (isset($this->languageData->ngrams[$ngram])) {
                    unset($this->languageData->ngrams[$ngram]);
                }
            }
        }

        if ($save) {
            if (!file_exists($filename)) { // in case $this->loadedSubset !== $newsubset, and was previously saved
                file_put_contents(
                    $filename,
                    "<?php // utf-8 \r\n"
                    . "/** legal notice: https://github.com/nitotm/efficient-language-detector/blob/main/legal.md */\r\n"
                    . "return " . $this->ngram_export($this->languageData->ngrams, $safe) . ';'
                );
            }

            return $filename;
        }

        return true;
    }

    /**
     * @param array<string,int> $results
     *
     * @return array<string,float>
     */
    public function filterLangSubset(array $results):array
    {
        if ($this->subset !== null) {
            foreach (array_keys($results) as $key) {
                if (!in_array($key, $this->subset, true)) {
                    unset($results[$key]);
                }
            }
        }

        return $results;
    }

    /** todo: should not be part of this class */
    protected function ngram_export(mixed $var, bool $safe = false):string
    {
        if (is_array($var)) {
            $toImplode = [];
            /**
             * @var string $key
             * @var string $value
             */
            foreach ($var as $key => $value) {
                $toImplode[] = ($safe ? '"\\x' . substr(chunk_split(bin2hex($key), 2, '\\x'), 0, -2) . '"'
                        : var_export($key, true)) . '=>' . $this->ngram_export($value);
            }

            return '[' . implode(',', $toImplode) . ']';
        }

        return var_export($var, true);
    }

}
