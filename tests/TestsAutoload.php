<?php
/*  To execute with autoload-dev:
        new Nitotm\Eld\Tests\TestsAutoload();
    In this document
        new TestsAutoload();
*/

namespace Nitotm\Eld\Tests;

class TestsAutoload
{
    public function __construct()
    {
        $GLOBALS['autoload_'] = true;
        require_once __DIR__ . '/tests.php';
    }
}

// new TestsAutoload();
