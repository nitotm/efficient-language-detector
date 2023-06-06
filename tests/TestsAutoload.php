<?php
/* To execute: 
   new Nitotm\ELD\Tests\TestsAutoload();
*/

namespace Nitotm\ELD\Tests;

$GLOBALS['autoload_'] = true;

class TestsAutoload
{
    function __construct()
    {
        require_once __DIR__.'/tests.php';
    }
}

