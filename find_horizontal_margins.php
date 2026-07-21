<?php
// find_horizontal_margins.php
// Finds the left and right coordinate of the inner frame.

$imagePath = 'C:\\Users\\muhdramdani\\Downloads\\pdf_pages\\quran-50-053.png';
$im = imagecreatefrompng($imagePath);
$width = imagesx($im);
$height = imagesy($im);

// Scan horizontally at y=300 (which is inside the surah banner / text area)
$y = 330;
$rowColors = [];
for ($x = 0; $x < $width; $x++) {
    $rgb = imagecolorat($im, $x, $y);
    $r = ($rgb >> 16) & 0xFF;
    $g = ($rgb >> 8) & 0xFF;
    $b = $rgb & 0xFF;
    $rowColors[$x] = ($r + $g + $b) / 3;
}

// Print values to see where background is white/cream (approx 248) vs where the border is (lower)
for ($x = 0; $x < $width; $x++) {
    if ($x % 5 == 0) {
        printf("x=%d: %.1f\n", $x, $rowColors[$x]);
    }
}
