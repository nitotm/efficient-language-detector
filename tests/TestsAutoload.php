<?php
/* 	To execute with autoload-dev: 
   		new Nitotm\Eld\Tests\TestsAutoload();
	In this document
   		new TestsAutoload();
*/

namespace Nitotm\Eld\Tests;

$GLOBALS['autoload_'] = true;

class TestsAutoload
{
    function __construct()
    {
        require_once __DIR__.'/tests.php';
    }
}
