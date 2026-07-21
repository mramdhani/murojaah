<?php
// find_lines.php
// Finds the exact vertical coordinates of the 15 lines on Quran page 50.

$imagePath = 'C:\\Users\\muhdramdani\\Downloads\\pdf_pages\\quran-50-053.png';
$im = imagecreatefrompng($imagePath);
$width = imagesx($im);
$height = imagesy($im);

// Define the margins (inside the green frame)
// Looking at the image, the frame is on left and right sides.
// Let's analyze between x = 300 and x = 1200.
$leftX = 300;
$rightX = 1200;

$rowBrightness = [];
for ($y = 0; $y < $height; $y++) {
    $sum = 0;
    for ($x = $leftX; $x < $rightX; $x++) {
        $rgb = imagecolorat($im, $x, $y);
        $r = ($rgb >> 16) & 0xFF;
        $g = ($rgb >> 8) & 0xFF;
        $b = $rgb & 0xFF;
        // Brightness (0 to 255)
        $sum += ($r + $g + $b) / 3;
    }
    $rowBrightness[$y] = $sum / ($rightX - $leftX);
}

// Write the profile to a file to examine
$out = "";
for ($y = 0; $y < $height; $y++) {
    $out .= "$y: {$rowBrightness[$y]}\n";
}
file_put_contents('profile.txt', $out);
echo "Written profile to profile.txt. Analyzing peaks and troughs...\n";

// A trough is text (darker, lower brightness), a peak is space between lines (brighter).
// But wait! A surah banner is a colored block, so it will have lower brightness than plain white background,
// but it is a solid block of color (usually taller than a single text line).
// Let's print out the brightness for y between 150 and 600.
for ($y = 150; $y < 600; $y++) {
    printf("y=%d: brightness=%.2f\n", $y, $rowBrightness[$y]);
}
