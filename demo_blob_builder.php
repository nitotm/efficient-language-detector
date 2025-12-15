<?php
/**
 * @copyright 2025 Nito T.M.
 * @license https://www.apache.org/licenses/LICENSE-2.0 Apache-2.0
 * @author Nito T.M. (https://github.com/nitotm)
 * @package nitotm/efficient-language-detector
 */

require_once __DIR__ . '/manual_loader.php';
// require __DIR__ . '/vendor/autoload.php';

use Nitotm\Eld\BlobDataBuilder;
use Nitotm\Eld\EldDataFile;

$cliHelp = <<<TXT
Options:
  -d for database: small, medium, large, extralarge (& subsets)
  -l for languages: Optional, creates subset, comma-separated language codes
CLI example:
  php demo_blob_builder.php -d small -l en,es,de,it

TXT;

$database = EldDataFile::SMALL;
$languages = null;

if (PHP_SAPI === 'cli') {
    echo $cliHelp . PHP_EOL;

    $arguments = getopt("d:l:") ?: [];

    $database =  $arguments['d'] ?? $database;
    $languageString = $arguments['l'] ?? null;

    if ($languageString) {
        $languages = explode(',', $languageString);
    }
}


// Builds a low memory database for EDL modes 'string', 'bytes' & 'disk', from any 'array' database
// It will load an 'array' database, memory requirements for 'array' input database apply
$eldBuilder = new BlobDataBuilder($database, $languages);
// Database files: 'small', 'medium', 'large', 'extralarge' & subsets. Check memory requirements at README

// Language subset
// $eldBuilder = new BlobDataBuilder(EldDataFile::SMALL, ['en', 'es', 'de', 'it']);

$eldBuilder->buildDatabase();
