<?php
// Done by GPT-5
// Outputs an SVG table generated from the $table data below.
// Edit $table (headers, rows, colors, widths) to change the printed SVG.

header('Content-Type: image/svg+xml; charset=utf-8');

// ------------------ CONFIG (edit this) ------------------
// Table layout (widths in px)
$table_time = [
    'width' => 600,
    'labelWidth' => 126,                   // width of the left label column
    'colWidths' => [93, 93, 93, 93, 102],  // widths for the data columns (sum + labelWidth should equal width)
    'rowHeight' => 30,                     // each row rectangle height
    'headerHeight' => 30,                  // top header band height (contains column titles)
    'fontFamily' => 'Arial, Helvetica, sans-serif',
    'fontSize' => 17,
    'headerName' => 'Seconds',
    'headerTitles' => [                    // column titles (for data columns)
        'Tatoeba-50',
        'ELD test',
        'Sentences',
        'Word pairs',
        'Single words'
    ],
    // rows: each row has a 'label' and 'cells' array, each cell may have 'value' and optional 'fill'
    // If cell 'fill' is omitted, defaultFill will be used.
    'defaultFill' => '#dfd',
    'rows' => [
        [
            'label' => 'ELD-S-array',
            'cells' => [
                ['value' => '4.7"'],
                ['value' => '1.6"'],
                ['value' => '1.4"'],
                ['value' => '0.45"'],
                ['value' => '0.34"']
            ]
        ],
        [
            'label' => 'ELD-M-array',
            'cells' => [
                ['value' => '5.3"'],
                ['value' => '1.8"'],
                ['value' => '1.5"'],
                ['value' => '0.48"'],
                ['value' => '0.37"']
            ]
        ],
        [
            'label' => 'ELD-L-array',
            'cells' => [
                ['value' => '4.4"'],
                ['value' => '1.5"'],
                ['value' => '1.2"'],
                ['value' => '0.41"'],
                ['value' => '0.33"']
            ]
        ],
        [
            'label' => 'ELD-XL-array',
            'cells' => [
                ['value' => '4.7"'],
                ['value' => '1.6"'],
                ['value' => '1.3"'],
                ['value' => '0.43"'],
                ['value' => '0.34"']
            ]
        ],
        [
            'label' => 'ELD-XL-string',
            'cells' => [
                ['value' => '11"'],
                ['value' => '3.9"'],
                ['value' => '3.2"'],
                ['value' => '0.76"'],
                ['value' => '0.52"']
            ]
        ],
        [
            'label' => 'ELD-XL-bytes',
            'cells' => [
                ['value' => '11"'],
                ['value' => '3.9"'],
                ['value' => '3.2"'],
                ['value' => '0.76"'],
                ['value' => '0.52"']
            ]
        ],
        [
            'label' => 'ELD-XL-disk',
            'cells' => [
                ['value' => '63"', 'fill' => '#fec'],
                ['value' => '27"', 'fill' => '#fec'],
                ['value' => '22"', 'fill' => '#fec'],
                ['value' => '4.3"', 'fill' => '#ffc'],
                ['value' => '2.5"', 'fill' => '#ffc']
            ]
        ],
        [
            'label' => 'Lingua',
            'cells' => [
                ['value' => '98"', 'fill' => '#fec'],
                ['value' => '27"', 'fill' => '#fec'],
                ['value' => '24"', 'fill' => '#fec'],
                ['value' => '8.2"', 'fill' => '#fec'],
                ['value' => '5.9"', 'fill' => '#fec']
            ]
        ],
        [
            'label' => 'fasttext-subset',
            'cells' => [
                ['value' => '12"'],
                ['value' => '2.7"'],
                ['value' => '2.3"'],
                ['value' => '1.2"'],
                ['value' => '1.1"']
            ]
        ],
        [
            'label' => 'fasttext-all',
            'cells' => [
                ['value' => '--', 'fill' => '#fff'],
                ['value' => '2.4"'],
                ['value' => '2.0"'],
                ['value' => '0.91"'],
                ['value' => '0.73"']
            ]
        ],
        [
            'label' => 'CLD2',
            'cells' => [
                ['value' => '3.5"'],
                ['value' => '0.71"'],
                ['value' => '0.59"'],
                ['value' => '0.35"'],
                ['value' => '0.32"']
            ]
        ],
        [
            'label' => 'Lingua-low',
            'cells' => [
                ['value' => '37"', 'fill' => '#ffc'],
                ['value' => '13"', 'fill' => '#ffc'],
                ['value' => '11"', 'fill' => '#ffc'],
                ['value' => '3.0"', 'fill' => '#ffc'],
                ['value' => '2.3"', 'fill' => '#ffc']
            ]
        ],
        [
            'label' => 'patrickschur',
            'cells' => [
                ['value' => '227"', 'fill' => '#fee'],
                ['value' => '74"', 'fill' => '#fee'],
                ['value' => '63"', 'fill' => '#fee'],
                ['value' => '18"', 'fill' => '#fee'],
                ['value' => '11"', 'fill' => '#fee']
            ]
        ],
        [
            'label' => 'franc',
            'cells' => [
                ['value' => '43"', 'fill' => '#ffc'],
                ['value' => '10"', 'fill' => '#ffc'],
                ['value' => '9"', 'fill' => '#ffc'],
                ['value' => '4.1"', 'fill' => '#ffc'],
                ['value' => '3.2"', 'fill' => '#ffc']
            ]
        ],
        [
            'label' => 'ELD-S_JS',
            'cells' => [['value' => '-'], ['value' => '-'], ['value' => '-'], ['value' => '-'], ['value' => '-']]
        ],
        [
            'label' => 'ELD-S_PY',
            'cells' => [
                ['value' => '-', 'fill' => '#ffc'],
                ['value' => '-', 'fill' => '#ffc'],
                ['value' => '-', 'fill' => '#ffc'],
                ['value' => '-', 'fill' => '#ffc'],
                ['value' => '-', 'fill' => '#ffc']
            ]
        ],
    ],
];

$table_accuracy = [
    'width' => 600,
    'labelWidth' => 126,
    'colWidths' => [93, 93, 93, 93, 102],
    'rowHeight' => 30,
    'headerHeight' => 30,
    'fontFamily' => 'Arial, Helvetica, sans-serif',
    'fontSize' => 17,
    'headerName' => 'Accuracy',
    'headerTitles' => ['Tatoeba-50', 'ELD test', 'Sentences', 'Word pairs', 'Single words'],
    'defaultFill' => '#dfd',
    'rows' => [
        [
            'label' => 'ELD-S',
            'cells' => [
                ['value' => '96.8%', 'fill' => '#ffc'],
                ['value' => '99.7%', 'fill' => '#dfd'],
                ['value' => '99.2%', 'fill' => '#dfd'],
                ['value' => '90.9%', 'fill' => '#fec'],
                ['value' => '75.1%', 'fill' => '#fee'],
            ]
        ],
        [
            'label' => 'ELD-M',
            'cells' => [
                ['value' => '97.9%', 'fill' => '#ffc'],
                ['value' => '99.7%', 'fill' => '#dfd'],
                ['value' => '99.3%', 'fill' => '#dfd'],
                ['value' => '93.0%', 'fill' => '#fec'],
                ['value' => '80.1%', 'fill' => '#fee'],
            ]
        ],
        [
            'label' => 'ELD-L',
            'cells' => [
                ['value' => '98.3%', 'fill' => '#dfd'],
                ['value' => '99.8%', 'fill' => '#dfd'],
                ['value' => '99.4%', 'fill' => '#dfd'],
                ['value' => '94.8%', 'fill' => '#fec'],
                ['value' => '83.5%', 'fill' => '#fee'],
            ]
        ],
        [
            'label' => 'ELD-XL',
            'cells' => [
                ['value' => '98.5%', 'fill' => '#dfd'],
                ['value' => '99.8%', 'fill' => '#dfd'],
                ['value' => '99.5%', 'fill' => '#dfd'],
                ['value' => '95.4%', 'fill' => '#ffc'],
                ['value' => '85.1%', 'fill' => '#fee'],
            ]
        ],
        [
            'label' => 'Lingua',
            'cells' => [
                ['value' => '96.1%', 'fill' => '#ffc'],
                ['value' => '99.2%', 'fill' => '#dfd'],
                ['value' => '98.7%', 'fill' => '#dfd'],
                ['value' => '93.4%', 'fill' => '#fec'],
                ['value' => '80.7%', 'fill' => '#fee'],
            ]
        ],
        [
            'label' => 'fasttext-subset',
            'cells' => [
                ['value' => '94.1%', 'fill' => '#fec'],
                ['value' => '98.0%', 'fill' => '#dfd'],
                ['value' => '97.9%', 'fill' => '#ffc'],
                ['value' => '83.1%', 'fill' => '#fee'],
                ['value' => '67.8%', 'fill' => '#eee'],
            ]
        ],
        [
            'label' => 'fasttext-all',
            'cells' => [
                ['value' => '--', 'fill' => '#fff'],
                ['value' => '97.4%', 'fill' => '#ffc'],
                ['value' => '97.6%', 'fill' => '#ffc'],
                ['value' => '81.5%', 'fill' => '#fee'],
                ['value' => '65.7%', 'fill' => '#eee'],
            ]
        ],
        [
            'label' => 'CLD2*',
            'cells' => [
                ['value' => '92.1%*', 'fill' => '#fec'],
                ['value' => '98.1%', 'fill' => '#dfd'],
                ['value' => '97.4%', 'fill' => '#ffc'],
                ['value' => '85.6%', 'fill' => '#fee'],
                ['value' => '70.7%', 'fill' => '#fee'],
            ]
        ],
        [
            'label' => 'Lingua-low',
            'cells' => [
                ['value' => '89.3%', 'fill' => '#fee'],
                ['value' => '97.3%', 'fill' => '#ffc'],
                ['value' => '96.3%', 'fill' => '#ffc'],
                ['value' => '84.1%', 'fill' => '#fee'],
                ['value' => '68.6%', 'fill' => '#eee'],
            ]
        ],
        [
            'label' => 'patrickschur',
            'cells' => [
                ['value' => '84.1%', 'fill' => '#fee'],
                ['value' => '94.8%', 'fill' => '#fec'],
                ['value' => '93.6%', 'fill' => '#fec'],
                ['value' => '71.9%', 'fill' => '#fee'],
                ['value' => '57.1%', 'fill' => '#eee'],
            ]
        ],
        [
            'label' => 'franc',
            'cells' => [
                ['value' => '76.9%', 'fill' => '#fee'],
                ['value' => '93.8%', 'fill' => '#fec'],
                ['value' => '92.3%', 'fill' => '#fec'],
                ['value' => '67.0%', 'fill' => '#eee'],
                ['value' => '53.8%', 'fill' => '#eee'],
            ]
        ],
    ],
];

$table = &$table_time;
//$table = &$table_accuracy;

// ------------------ END CONFIG ------------------

// Basic derived values
$width = $table['width'];
$labelWidth = $table['labelWidth'];
$colWidths = $table['colWidths'];
$rowHeight = $table['rowHeight'];
$headerHeight = $table['headerHeight'];
$rowsCount = count($table['rows']);
$height = $headerHeight + $rowsCount * $rowHeight + 1; // small bottom margin
$fontFamily = $table['fontFamily'];
$fontSize = $table['fontSize'];
$defaultFill = $table['defaultFill'];

// cumulative X positions for columns
$cumXs = [];
$pos = $labelWidth;
foreach ($colWidths as $w) {
    $cumXs[] = $pos;
    $pos += $w;
}

// helper: escape text for XML
function xt($s): string
{
    return htmlspecialchars((string)$s, ENT_XML1 | ENT_QUOTES, 'UTF-8');
}

// Start SVG
echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
echo "<svg xmlns='http://www.w3.org/2000/svg' font-family='" . xt($fontFamily) . "' font-size='" . xt(
    $fontSize
) . "' viewBox='0 0 $width $height'>\n";

// background
echo "  <path d='M0 0h{$width}v{$height}H0z' fill='#fff'/>\n";

// small left top title "Seconds" (kept from original)
echo "  <text fill='#555' transform='translate(30 16)'>\n
      <tspan x='0' y='0'>" . $table['headerName'] . "</tspan>\n  </text>\n";

// Rows: rectangles and texts
for ($i = 0; $i < $rowsCount; $i++) {
    $row = $table['rows'][$i];
    $rectY = $headerHeight + $i * $rowHeight;      // rect top
    $textY = $rectY + 22;                          // cell text baseline (original used +22)
    // label text (left column)
    $labelX = 4;
    echo "  <text transform='translate($labelX " . xt($textY) . ")'>" . xt($row['label']) . "</text>\n";

    // cells
    $cells = $row['cells'];
    for ($c = 0; $c < count($colWidths); $c++) {
        $cellX = $cumXs[$c];
        $cellW = $colWidths[$c];
        $cell = $cells[$c] ?? ['value' => ''];
        $fill = $cell['fill'] ?? $defaultFill;
        // rectangle for the cell
        echo "    <path d='M$cellX {$rectY}h{$cellW}v{$rowHeight}H{$cellX}z' fill='" . xt($fill) . "'/>\n";
        // cell text (small left padding inside rect)
        $textX = $cellX + 6;
        $val = $cell['value'] ?? '';
        echo "    <text transform='translate($textX $textY)'>" . xt($val) . "</text>\n";
    }
}

// Horizontal separators across width: at y = multiples of rowHeight starting at 0 up to headerHeight + rows*rowHeight
echo "  <g fill='#ddd'>\n";
for ($y = 0; $y <= $headerHeight + $rowsCount * $rowHeight; $y += $rowHeight) {
    // draw a 1px-high rect for the separator
    echo "    <path d='M0 {$y}h{$width}v1H0z'/>\n";
}
// Add the final bottom line exactly at computed height (if not present)
$finalY = $headerHeight + $rowsCount * $rowHeight;
echo "    <path d='M0 {$finalY}h{$width}v1H0z'/>\n";
echo "  </g>\n";

// Column vertical separators
echo "  <path d='";
$vx = 0;
echo "M0 0h1v{$height}H0z";
foreach ($cumXs as $cx) {
    $cx_i = (int)$cx;
    echo "m" . ($cx_i - $vx) . " 0h1v{$height}h-1z"; // move then rect
    $vx = $cx_i;
}
$lastX = $width - 1; // final vertical at right border (thin)
$lastMove = $lastX - $vx;
echo "m$lastMove 0h1v{$height}h-1z";
echo "' fill='#ddd'/>\n";

// Column header titles (placed above rects inside header area)
echo "  <g font-size='16' font-weight='700'>\n";
for ($c = 0; $c < count($table['headerTitles']); $c++) {
    $title = $table['headerTitles'][$c];
    $rectX = $cumXs[$c];
    $titleX = $rectX + 4;
    $titleY = 21; // matches original
    echo "    <text " . ($title === 'Single words' ? 'font-size="15.4" ' : '') .
        "transform='translate($titleX $titleY)'><tspan>" . xt($title) . "</tspan></text>\n";
}
echo "  </g>\n";

// Final bottom border
echo "  <path d='M0 0h1v" . ($height - 1) . "H0zm$labelWidth 0h1v" . ($height - 1) . "h-1zm";
/*
foreach ($colWidths as $i => $w) {
    // we already printed labelWidth above; now print each separator step by step
    // compute positions cumulatively for a path-like string is cumbersome; simpler skip
}
*/
echo "0' fill='none' />\n";

echo "</svg>\n";
