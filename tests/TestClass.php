<?php
/*
Copyright 2023 Nito T.M.
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

//namespace Nitotm\Eld\Tests;
class TestClass
{
    private $tests = [];
    private $passed = 0;
    private $failed = 0;

    public function addTest($testIdentifier, $test, $stopOnFail = false)
    {
        $this->tests[] = ['test' => $test, 'identifier' => $testIdentifier, 'stop' => $stopOnFail];
    }

    public function run()
    {
        echo(PHP_SAPI === 'cli' ? '' : "<pre>");
        $startTime = microtime(true);

        foreach ($this->tests as $test) {
            $testFunction   = $test['test'];
            $testIdentifier = $test['identifier'];
            $passed         = false;

            try {
                $testFunction();
                $this->passed++;
                $passed = true;
                $this->printResult("PASSED", $testIdentifier);
            } catch (Exception $e) {
                $this->failed++;
                $this->printResult(
                    "FAILED", $testIdentifier, $e->getMessage().PHP_EOL.
                    "         (".$e->getFile().':'.$e->getLine().')'
                );
            } catch (Error $e) {
                $this->failed++;
                $this->printResult(
                    "ERROR!", $testIdentifier, $e->getMessage().PHP_EOL.
                    "         (".$e->getFile().':'.$e->getLine().')'
                );
            }

            if ( ! $passed && $test['stop']) {
                echo PHP_EOL."    [ABORTED] Due to the last failure, the tests cannot continue successfully".PHP_EOL;
                break;
            }
        }

        $endTime = microtime(true);

        $this->printSummary($endTime - $startTime, memory_get_peak_usage());
        echo(PHP_SAPI === 'cli' ? '' : '</pre>');
    }

    private function printResult($status, $testIdentifier, $message = '')
    {
        echo PHP_EOL."[".$status."] ".$testIdentifier.($message === '' ? '' : ' -> ').$message.PHP_EOL;
    }

    private function printSummary($executionTime, $memoryUsage)
    {
        $total = count($this->tests);
        echo PHP_EOL;
        echo "========= Test Summary =========".PHP_EOL;
        echo " Tests : ".$total.PHP_EOL;
        echo " Passed: ".$this->passed." (".round($this->passed / $total * 100, 2)."%)".PHP_EOL;
        echo " Failed: ".$this->failed.PHP_EOL;
        echo " Time  : ".round($executionTime, ($executionTime < 0.01 ? 6 : 3))." seconds".PHP_EOL;
        echo " Memory: ".($memoryUsage < 1000000 ? round($memoryUsage / 1024, 2)." KB"
                : round($memoryUsage / pow(1024, 2), 2)." MB").PHP_EOL;
        echo ' PHP v.: '.phpversion().PHP_EOL;
        echo "================================".PHP_EOL;
    }
}
