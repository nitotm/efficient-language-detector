<?php
/**
 * @copyright 2025 Nito T.M.
 * @license https://www.apache.org/licenses/LICENSE-2.0 Apache-2.0
 * @author Nito T.M. (https://github.com/nitotm)
 * @package nitotm/efficient-language-detector
 */


/**
 * Helper development function loads all language format files and builds a reversed map
 * Detects dangerous collisions: different codes of same length mapping to same index
 */
function reversed_global_language_map(): array
{
    // repeat FULL_TEXT format with no alphabet variant, both options will be available
    // Must be FALSE if there are languages with multiple alphabets included
    $includeBaseForms = true;

    $formats_dir = __DIR__ . '/../resources/formats/';

    if (!is_dir($formats_dir)) {
        die("Error: Directory $formats_dir does not exist!\n");
    }

    $all_files = glob($formats_dir . '*.php');

    if (empty($all_files)) {
        die("Error: No PHP files found in $formats_dir\n");
    }

    $global_map = [];           // Final result: ['amh' => 0, 'ara' => 1, ...]
    $collision_tracker = [];    // Tracks: index => [length => [code1, code2, ...]]

    foreach ($all_files as $file) {
        $codes = require $file; // Each file must return an array of strings

        if (!is_array($codes)) {
            echo "Warning: File $file did not return an array. Skipped.\n";
            continue;
        }

        foreach ($codes as $index => $code) {
            $code = strtolower($code);
            $code = preg_replace('/[^a-z]+/', '-', $code);
            $code = trim($code, ' -');

            if ($includeBaseForms && strpos($file, 'full_text.php') !== false && strpos($code, '-') !== false) {
                $baseCode = substr($code, 0, strpos($code, '-'));

                if (isset($global_map[$baseCode])) {
                    // Should not happen, there might be multiple alphabets for one language or could be a
                    // false alarm collision with ISO code, check manually
                    echo "ERROR: Dangerous collision detected at $index, for base language no alphabet $code $file\n";
                } else {
                    $global_map[$baseCode] = $index;
                    $collision_tracker[$index][strlen($baseCode)][] = $baseCode;
                }
            }

            if ($code === '') {
                continue;
            }

            $length = strlen($code);

            if (isset($global_map[$code])) {
                // Already exists — this is normal if same code appears in multiple standards
                continue;
            }

            // Assign the index (first occurrence wins)
            $global_map[$code] = $index;

            // Track for collision detection
            $collision_tracker[$index][$length][] = $code;
        }
    }

    // Now check for dangerous collisions
    $has_error = false;
    foreach ($collision_tracker as $index => $by_length) {
        foreach ($by_length as $length => $codes_at_this_length) {
            if (count($codes_at_this_length) > 1) {
                // More than one code of same length mapped to same index
                echo "ERROR: Potentially dangerous collision detected at index $index (length $length):\n";
                foreach ($codes_at_this_length as $code) {
                    echo "    - '$code'\n";
                }
                echo "\n";
                $has_error = true;
            }
        }
    }

    if ($has_error) {
        echo "WARNING: One or more dangerous collisions found. Please review Manually.\n";
        // You can choose to exit or just warn
        // exit(1);
    } else {
        echo "All good! No dangerous collisions detected.\n";
    }

    // Sort by key for consistent output (optional)
    asort($global_map);

    return $global_map;
}

// Example usage:
$language_map = reversed_global_language_map();
var_export($language_map);
