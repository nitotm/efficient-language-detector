<?php
/** legal notice: https://github.com/nitotm/efficient-language-detector/blob/main/legal.md */

declare(strict_types = 1);

namespace Nitotm\Eld;

use RuntimeException;

class LanguageSet
{
    private string $cachedir;
    /** @var int[] $langIds */
    public array $langIds;

    /**
     * @param null|string[] $limitTo
     */
    public function __construct(
        private readonly LanguageData $languageData,
        private ?array $limitTo = null,
        private readonly bool $usecache = true,
        private readonly bool $hardening = false,
        ?string $cachedir = null,
    ) {
        $this->cachedir = $cachedir ?? dirname(__DIR__, 2) . '/data/cache/';
    }

    /** @return array{string:array{int:int}} */
    public function getNgrams():array
    {
        if ($this->limitTo === null) {
            $this->langIds = $this->languageData->languages;

            return $this->languageData->getNgrams();
        }
        if (!is_dir($this->cachedir) && !mkdir($this->cachedir) && !is_dir($this->cachedir)) {
            throw new RuntimeException(sprintf('Directory "%s" was not created', $this->cachedir));
        }
        sort($this->limitTo);
        $hash = implode("-", $this->limitTo);
        if (strlen($hash) > 64) {
            $file = $this->cachedir . md5($hash) . '.php';
        } else {
            $file = $this->cachedir . $hash . '.php';
        }
        if ($this->usecache && file_exists($file)) {
            return include $file;
        }

        $ngrams = $this->languageData->getNgrams();
        $this->langIds = $this->languageData->getLangIds($this->limitTo);
        $ngrams = $this->hardenAndFilter($ngrams);
        if ($this->usecache) {
            file_put_contents(
                $file,
                "<?php // utf-8" . PHP_EOL
                . "/** legal notice: https://github.com/nitotm/efficient-language-detector/blob/main/legal.md */" . PHP_EOL
                . "/** languages: $hash */" . PHP_EOL
                . "return " . $this->export($ngrams) . ';' . PHP_EOL
            );
        }

        return $ngrams;
    }

    /**
     * @param array{string:array{int:int}} $var
     */
    protected function export(array $var):string
    {
        return var_export($var, true);
    }

    /**
     * @param array{string:array{int:int}} $var
     *
     * @return array{string:array{int:int}}
     */
    private function hardenAndFilter(array $var):array
    {
        foreach ($var as $key => $scoremap) {
            $hardkey = $key;
            $scoremap = $this->filterScoremap($scoremap);
            if ($scoremap !== null) {
                if ($this->hardening) {
                    $hardkey = '\\x' . substr(chunk_split(bin2hex($key), 2, '\\x'), 0, -2);
                }
                $var[$hardkey] = $scoremap;
            }
            if ($key !== $hardkey || $scoremap === null || $scoremap === []) {
                unset($var[$key]);
            }
        }

        return $var;
    }

    /**
     * @param array{int:int} $scoremap
     *
     * @return null|array{int:int}
     */
    private function filterScoremap(array $scoremap):?array
    {
        /** @var int $langId */
        foreach (array_keys($scoremap) as $langId) {
            if (!in_array($langId, $this->langIds, true)) {
                unset($scoremap[$langId]);
            }
        }
        if (count($scoremap) === 0) {
            return null;
        }

        return $scoremap;
    }
}
