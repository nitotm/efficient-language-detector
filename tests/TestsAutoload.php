<?php
/* To execute: 
   new Nitotm\Eld\Tests\TestsAutoload();
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
