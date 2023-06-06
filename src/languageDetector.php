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

namespace Nitotm\ELD;

require_once __DIR__.'/LanguageData.php';

class LanguageDetector extends LanguageData
{
    public $returnScores = false;

    protected function tokenizer($str)
    {
        return preg_split('/ /', $str, -1, PREG_SPLIT_NO_EMPTY);
    }

    public function cleanTxt($str)
    {
        // Remove URLS
        $str = preg_replace('@[hw]((ttps?://(www\.)?)|ww\.)([^\s/?\.#-]+\.?)+(/\S*)?@i', ' ', $str);
        // Remove emails
        $str = preg_replace('/[a-zA-Z0-9.!$%&’+_`-]+@[A-Za-z0-9.-]+\.[A-Za-z0-9-]{2,64}/', ' ', $str);
        // Remove domains
        $str = preg_replace('/([A-Za-z0-9-]+\.)+com(\/\S*|[^\pL])/', ' ', $str);

        // Remove alphanumerical/number codes
        return preg_replace('/[a-zA-Z]*[0-9]+[a-zA-Z0-9]*+/', ' ', $str);
    }

    protected function getScores($array)
    {
        $scores = [];
        foreach ($array as $key => $value) {
            if ($value == 0) {
                break;
            }
            $scores[$this->langCodes[$key]] = $value;
        }

        return $scores;
    }

    protected function getByteNgrams($str)
    {
        $str         = mb_strtolower($str, 'UTF-8');
        $tokens      = [];
        $countNgrams = 0;
        // Word start. Local declaration improves speed. Much faster than ($j==0 ? ' ' : '')
        $start = [' ','','','','','','','','','','','','','','','','','','','','','','','','','','','','','','','','','',
            '','','','','','','','','','','','','','','','','','','','','','','','','','','','','','','','','','','',
            '','',''];

        foreach ($this->tokenizer($str) as $word) {
            $len = strlen($word);
            if ($len > 70) {
                $len = 70;
            }

            for ($j = 0; ($j + 4) < $len; $j += 3, ++$tmp, ++$countNgrams) {
                $tmp = &$tokens[$start[$j].substr($word, $j, 4)];
            }
            $tmp = &$tokens[$start[$j].substr($word, ($len != 3 ? $len - 4 : 0)).' '];
            $tmp++;
            $countNgrams++;
        }

        // Frequency is multiplied by 15000 at the ngrams database. A reduced number seems to work better. 
        // Linear formulas were tried, decreasing the multiplier for fewer ngram strings, no meaningful improvement.
        foreach ($tokens as $bytes => $count) {
            $tokens[$bytes] = $count / $countNgrams * 13200;
        }

        return $tokens;
    }

    protected function calcScores($txtNgrams, $numNgrams)
    {
        $langScore = $this->langScore;
        $results   = [];

        foreach ($txtNgrams as $bytes => $frequency) {
            if (isset($this->ngrams[$bytes])) {
                $num_langs = count($this->ngrams[$bytes]);
                // Ngram score multiplier, the fewer languages found the more relevancy. Formula can be fine-tuned.
                if ($num_langs === 1) {
                    $relevancy = 27;
                } elseif ($num_langs < 16) {
                    $relevancy = (16 - $num_langs) / 2 + 1;
                } else {
                    $relevancy = 1;
                }
                // Most time-consuming loop, do only the strictly necessary inside
                foreach ($this->ngrams[$bytes] as $lang => $ngramFrequency) {
                    $langScore[$lang] += ($frequency > $ngramFrequency ? $ngramFrequency / $frequency
                            : $frequency / $ngramFrequency) * $relevancy + 2;
                }
            }
        }
        // This divisor will produce a final score between 0 - ~1, score could be >1. Can be improved.
        $resultDivisor = $numNgrams * 3.2;
        // $scoreNormalizer = $this->scoreNormalizer; // local access improves speed
        foreach ($langScore as $lang => $score) {
            if ($score) {
                $results[$lang] = $score / $resultDivisor; // * $scoreNormalizer[$lang];
            }
        }

        return $results;
    }

    /*
      detect() returns an array, with a value named 'language', which will be either a ISO 639-1 code or false
      ['language' => 'en'];
      ['language' => false, 'error' => 'Some error', 'scores'=>[]];

      When returnScores = true;
      ['language' => 'en', 'scores' => ['en' => 0.6, 'es' => 0.2]]; 
    */

    public function detect($text, $cleanText = false, $checkConfidence = false, $minByteLength = 12, $minNgrams = 3)
    {
        if ($cleanText) {
            // Removes Urls, emails, alphanumerical & numbers
            $text = $this->cleanTxt($text);
        }
        $minNgrams = ($minNgrams > 0 ? $minNgrams : 1);
        // Normalize special characters/word separators
        $text       = trim(preg_replace('/[^\pL]+(?<![\x27\x60\x{2019}])/u', ' ', mb_substr($text, 0, 1000, 'UTF-8')));
        $thisLength = strlen($text);

        if ($thisLength > 350) {
            // Cut to first whitespace after 350 byte length offset
            $text = substr($text, 0, min(380, (strpos($text, ' ', 350) ?: 350)));
        } elseif ($thisLength < $minByteLength) {
            return ['language' => false, 'error' => 'Text to short', 'scores' => []];
        }

        $txtNgrams = $this->getByteNgrams($text);
        $numNgrams = count($txtNgrams);

        if ($numNgrams >= $minNgrams) {
            $results = $this->calcScores($txtNgrams, $numNgrams);

            if ($this->subset) {
                $results = $this->filterLangSubset($results);
            }
            arsort($results);

            if ($results) {
                $top_lang = key($results);

                if ($checkConfidence) {
                    // A minimum of a 17% per ngram score from average
                    if ($this->avgScore[$top_lang] * 0.17 > ($results[$top_lang] / $numNgrams)
                        || 0.01 > abs($results[$top_lang] - next($results))) {
                        return [
                            'language' => false,
                            'error'    => 'No language has been identified with sufficient confidence, set checkConfidence to false to avoid this error',
                            'scores'   => []
                        ];
                    }
                }

                if ( ! $this->returnScores) {
                    return ['language' => $this->langCodes[$top_lang]];
                } else {
                    return ['language' => $this->langCodes[$top_lang], 'scores' => $this->getScores($results)];
                }
            }

            return ['language' => false, 'error' => 'Language not detected', 'scores' => []];
        }

        return ['language' => false, 'error' => 'Not enough distinct ngrams', 'scores' => []];
    }
}
