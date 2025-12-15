<?php
declare(strict_types=1);
// CLI wrapper done by GPT-5, BETA version

/**
 * bin/eld
 *
 * Simplified, consistent CLI wrapper for Nitotm\Eld\LanguageDetector
 *
 * Options:
 *   -d, --database <name>    Database file/preset (small|medium|large|extralarge). Default: extralarge
 *   -s, --scheme   <scheme>  Language scheme (ISO639_1 ISO639_2T, ISO639_1_BCP47, ISO639_2T_BCP47, FULL_TEXT).
 *   -m, --mode     <mode>    Database mode (array|string|bytes|disk).Default varies, but never array.
 *   -l, --languages <csv>    Comma-separated codes to build/use subset (calls langSubset(..., save=true, encode=true))
 *   -F, --file <path>        Path to input file — processed line-by-line
 *   -f, --format <format>    Output serialization. (text, json, jsonl) Default: text
 *       --each               Read stdin line-by-line (interactive or piped)
 *       --full               Return full result (language, scores, isReliable). Default: minimal (language only)
 *       --help               Show this help
 *
 * Default scheme: ISO639_1
 *
 * Behaviors (priority):
 *  1) If --file is provided: process that file line-by-line.
 *  2) Else if --each is provided: read stdin line-by-line. If stdin is a TTY, run interactive prompt
 *  3) Else: use the last positional argument (non-option) as the text to detect.
 *
 * Examples:
 *   ./bin/eld "Hola, ¿qué tal?"
 *   ./bin/eld -s ISO639_1 -f json "Hola mundo"
 *   ./bin/eld -F sentences.txt --format=jsonl --full
 *   type sentences.txt | eld --each --format=jsonl --full      # Windows cmd
 *   Get-Content sentences.txt -Raw | .\bin\eld --each --format=jsonl --full  # PowerShell
 *   ./bin/eld --each --format=json --full      # interactive: type lines
 *
 * composer.json "bin": ["bin/eld", "bin/eld-php"]
 */

// require __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../manual_loader.php';

use Nitotm\Eld\LanguageDetector;
use Nitotm\Eld\EldMode;

$shortopts = "d:s:m:l:F:f:eh";
$longopts  = [
    "database::", "scheme::", "mode::", "languages::", "file::", "format::",
    "each", "full", "help"
];

$opts = getopt($shortopts, $longopts);

// Help
if (isset($opts['h']) || isset($opts['help'])) {
    $help = <<<'USAGE'
Usage: eld [options] [text]
Options:
  -d, --database <name>         Database file/preset. Default: extralarge
  -s, --scheme <scheme>         Language scheme (ISO639_1 | ISO639_2T | ISO639_1_BCP47 | ISO639_2T_BCP47 | FULL_TEXT).
  -m, --mode <mode>             Database mode (array|string|bytes|disk). Default varies, but never array.
  -l, --languages <csv>         Comma-separated language codes to build/use subset
  -F, --file <path>             Path to input file; processed line-by-line
  -f, --format <format>         Output serialization. (text, json, jsonl) Default: text
      --each                    Read stdin line-by-line (interactive or piped)
      --full                    Return full result (language, scores, isReliable)
      --help                    Show this help
      
Default scheme: ISO639_1      
Examples:
  eld "Hi, how are you?"
  eld -d extralarge -m disk -s ISO639_1 -f text "Hi, how are you?"
  eld --database=extralarge --mode=disk --scheme=ISO639_1 --format==text "Hi, how are you?"
  php bin/eld --help
  eld --scheme=ISO639_1_BCP47 --languages=es,it,pt,fr "Hi, how are you?"
  ./bin/eld -F sentences.txt --format=jsonl --full
  echo "Bonjour\nHallo\n" | eld --each --format=jsonl --full
  cat sentences.txt | eld --each --format=jsonl --full
USAGE;
    echo $help . PHP_EOL;
    exit(0);
}

// Defaults
$database = $opts['d'] ?? $opts['database'] ?? 'extralarge';
$scheme   = $opts['s'] ?? $opts['scheme'] ?? 'ISO639_1';
$langsCsv = $opts['l'] ?? $opts['languages'] ?? null;
$filePath = $opts['F'] ?? $opts['file'] ?? null;
$format   = $opts['f'] ?? $opts['format'] ?? 'text';
$each     = isset($opts['each']);
$wantFull = isset($opts['full']);
$mode     = $opts['m'] ?? $opts['mode'] ?? false;

if (!$mode) {
    if ($each) {
        // multiple detections
        $opcacheStatus = (function_exists('opcache_get_status') ? opcache_get_status() : false);
        $opcacheEnabled = $opcacheStatus !== false && $opcacheStatus['opcache_enabled'];
        if ($opcacheEnabled) {
            // faster cached load time
            $mode = EldMode::MODE_STRING;
        } else {
            // faster uncached load time
            $mode = EldMode::MODE_BYTES;
        }
    } else {
        // single detection, disk mode is the fastest for 1 detection
        $mode = EldMode::MODE_DISK;
    }
}

// Collect positional arguments and take the last non-option as text
$positional = [];
foreach (array_slice($GLOBALS['argv'], 1) as $arg) {
    if ($arg === '') {
        continue;
    }
    if ($arg[0] === '-') {
        // Skip option tokens and their immediate separate values — getopt values, this check is for raw argv safety
        continue;
    }
    $positional[] = $arg;
}
$lastPositional = count($positional) ? end($positional) : null;

// Validate format quickly (keep simple)
$allowedFormats = ['text','json','jsonl'];
if (!in_array($format, $allowedFormats, true)) {
    fwrite(STDERR, "Invalid --format value: $format. Allowed: " . implode(', ', $allowedFormats) . PHP_EOL);
    exit(1);
}

// Instantiate detector (delegate scheme/mode validation to library)
try {
    $eld = new LanguageDetector($database, $scheme, $mode);
} catch (Throwable $e) {
    fwrite(STDERR, "Failed to initialize LanguageDetector: " . $e->getMessage() . PHP_EOL);
    exit(2);
}

// If --languages specified, attempt langSubset(languages, save=true, encode=true) and re-load returned file
if ($langsCsv !== null) {
    $langs = array_values(array_filter(array_map('trim', explode(',', (string)$langsCsv))));
    if (count($langs) > 0) {
        try {
            $res = $eld->langSubset($langs);
            if (!$res->success) {
                fwrite(STDERR, "Warning: failed to load subset file '$res->file': " . $res->error . PHP_EOL);
            }
        } catch (Throwable $e) {
            fwrite(STDERR, "Warning: langSubset() failed: " . $e->getMessage() . PHP_EOL);
        }
    }
}

// Helper: convert detect() result to array
function detect_to_array($res, bool $full): array
{
    $out = [];
    if (is_object($res) && property_exists($res, 'language')) {
        $out['language'] = $res->language;
    } else {
        $out['language'] = is_string($res) ? $res : (string)$res;
    }

    if ($full) {
        $out['scores'] = [];
        $out['isReliable'] = false;
        if (is_object($res) && method_exists($res, 'scores')) {
            try {
                $out['scores'] = (array)$res->scores();
            } catch (Throwable $ex) {
                $out['scores'] = [];
            }
        }
        if (is_object($res) && method_exists($res, 'isReliable')) {
            try {
                $out['isReliable'] = (bool)$res->isReliable();
            } catch (Throwable $ex1) {
                $out['isReliable'] = false;
            }
        }
    }
    return $out;
}

// Rendering helper
function render_result(array $arr, string $format, bool $pretty): void
{
    if ($format === 'json') {
        echo json_encode($arr, JSON_UNESCAPED_UNICODE | ($pretty ? JSON_PRETTY_PRINT : 0)) . PHP_EOL;
    } elseif ($format === 'jsonl') {
        echo json_encode($arr, JSON_UNESCAPED_UNICODE) . PHP_EOL;
    } elseif (isset($arr['scores']) || isset($arr['isReliable'])) { // text
        $lang = $arr['language'] ?? '(unknown)';
        $reli = (isset($arr['isReliable']) && $arr['isReliable']) ? 'true' : 'false';
        $scores = isset($arr['scores']) ? json_encode($arr['scores'], JSON_UNESCAPED_UNICODE) : '[]';
        echo "$lang reliability=$reli scores=$scores" . PHP_EOL;
    } else { // text
        echo ($arr['language'] ?? '(unknown)') . PHP_EOL;
    }
    // Ensure immediate emission in interactive/piped usage
    if (function_exists('fflush')) {
        fflush(STDOUT);
    }
}

// MODE 1: --file provided -> process file line-by-line
if ($filePath !== null) {
    $filePath = (string)$filePath;
    if (!is_readable($filePath)) {
        fwrite(STDERR, "Cannot read file: $filePath\n");
        exit(1);
    }
    $fh = fopen($filePath, 'rb');
    if ($fh === false) {
        fwrite(STDERR, "Failed to open file: $filePath\n");
        exit(1);
    }
    try {
        while (!feof($fh)) {
            $line = fgets($fh);
            if ($line === false) {
                break;
            }
            $line = trim($line);
            if ($line === '') {
                continue;
            }
            try {
                $res = $eld->detect($line);
                $arr = detect_to_array($res, $wantFull);
                render_result($arr, $format, false);
            } catch (Throwable $e) {
                fwrite(STDERR, "Detection failed for line: " . $e->getMessage() . PHP_EOL);
            }
        }
    } finally {
        fclose($fh);
    }
    exit(0);
}

// MODE 2: --each provided -> read stdin line-by-line (interactive if TTY)
if ($each) {
    // If stdin is a TTY, run interactive prompt; otherwise read piped input line-by-line
    $isTty = function_exists('posix_isatty') ?
        posix_isatty(STDIN)
        : (function_exists('stream_isatty') && stream_isatty(STDIN));
    // Fallback: if PHP can't detect TTY, assume interactive when STDIN is not coming from a pipe/file
    // if ($isTty === false) { // ftell returns false for pipes
        // best-effort fallback: check if STDIN has data available; if not, still treat as interactive prompt
        // we'll proceed with fgets loop which works for both piped and interactive input
        // TODO
    // }

    // Interactive prompt if STDIN is a TTY (user types lines)
    if ($isTty) {
        // Prompt loop. Note: not using readline to avoid optional dependency.
        while (true) {
            // Show a prompt
            echo "> ";
            // Read a line
            $line = fgets(STDIN);
            if ($line === false) {
                // EOF (Ctrl+D on Unix, Ctrl+Z on Windows)
                break;
            }
            $line = trim($line);
            if ($line === '') {
                continue;
            }
            try {
                $res = $eld->detect($line);
                $arr = detect_to_array($res, $wantFull);
                render_result($arr, $format, false);
            } catch (Throwable $e) {
                fwrite(STDERR, "Detection failed: " . $e->getMessage() . PHP_EOL);
            }
        }
        exit(0);
    }

// Non-tty: read piped input line-by-line (fgets will return lines as they arrive and on EOF)
    while (!feof(STDIN)) {
        $line = fgets(STDIN);
        if ($line === false) {
            break;
        }
        $line = trim($line);
        if ($line === '') {
            continue;
        }
        try {
            $res = $eld->detect($line);
            $arr = detect_to_array($res, $wantFull);
            render_result($arr, $format, false);
        } catch (Throwable $e) {
            fwrite(STDERR, "Detection failed: " . $e->getMessage() . PHP_EOL);
        }
    }
    exit(0);
}

// MODE 3: single text detection using last positional argument
if ($lastPositional === null) {
    fwrite(STDERR, "No input text provided. Pass text as the last argument, use --file=<path>," .
    "or --each with piped/interactive input. See --help\n");
    exit(1);
}

$text = $lastPositional;
try {
    $res = $eld->detect($text);
    $arr = detect_to_array($res, $wantFull);
    // Pretty JSON for single-result JSON output
    render_result($arr, $format, true);
    exit(0);
} catch (Throwable $e) {
    fwrite(STDERR, "Detection failed: " . $e->getMessage() . PHP_EOL);
    exit(3);
}
