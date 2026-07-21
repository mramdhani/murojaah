<?php
// analyze_page.php
// Analyzes a rendered page image to find the vertical coordinates of the surah banner.

$imagePath = 'C:\\Users\\muhdramdani\\Downloads\\pdf_pages\\quran-50-053.png';
if (!file_exists($imagePath)) {
    die("File not found: $imagePath\n");
}

$im = imagecreatefrompng($imagePath);
$width = imagesx($im);
$height = imagesy($im);

echo "Image dimensions: {$width}x{$height}\n";

// We will scan along the horizontal middle of the page (from left to right) 
// to find where the green border of the page starts and ends.
// And scan vertically down the middle to see color variations.

$centerX = (int)($width / 2);
$nonWhiteRows = [];

for ($y = 0; $y < $height; $y++) {
    $rgb = imagecolorat($im, $centerX, $y);
    $r = ($rgb >> 16) & 0xFF;
    $g = ($rgb >> 8) & 0xFF;
    $b = $rgb & 0xFF;
    
    // Check if the color is significantly different from white/creamy page background (e.g., r < 240, g < 240, b < 240)
    // Or if it's the green banner color.
    if ($r < 235 || $g < 235 || $b < 235) {
        $nonWhiteRows[] = [
            'y' => $y,
            'r' => $r,
            'g' => $g,
            'b' => $b
        ];
    }
}

echo "Found " . count($nonWhiteRows) . " non-white rows.\n";

// Let's print the first 20 and last 20 non-white rows to understand boundaries
echo "\nTop non-white rows:\n";
for ($i = 0; $i < min(50, count($nonWhiteRows)); $i++) {
    $row = $nonWhiteRows[$i];
    echo "y={$row['y']}: RGB({$row['r']}, {$row['g']}, {$row['b']})\n";
}

echo "\nBottom non-white rows:\n";
for ($i = max(0, count($nonWhiteRows) - 50); $i < count($nonWhiteRows); $i++) {
    $row = $nonWhiteRows[$i];
    echo "y={$row['y']}: RGB({$row['r']}, {$row['g']}, {$row['b']})\n";
}
