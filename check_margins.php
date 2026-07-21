<?php
// check_margins.php
$im = imagecreatefrompng('C:/Users/muhdramdani/Downloads/pdf_pages/quran-50-053.png');
$height = imagesy($im);
$width = imagesx($im);

// Let's find the exact horizontal boundaries of the page frame.
// The frame has a black/dark border.
// We scan from x=100 to x=500 for the left frame boundary, and from x=1000 to x=1400 for the right.
$y = (int)($height / 2);

echo "Left side transitions:\n";
for ($x = 200; $x < 350; $x++) {
    $rgb = imagecolorat($im, $x, $y);
    $r = ($rgb >> 16) & 0xFF;
    $g = ($rgb >> 8) & 0xFF;
    $b = $rgb & 0xFF;
    if ($r < 200 || $g < 200 || $b < 200) {
        printf("Left border starts at x=%d (RGB: %d,%d,%d)\n", $x, $r, $g, $b);
        break;
    }
}

// Find inner boundary of left frame border
for ($x = 350; $x > 200; $x--) {
    $rgb = imagecolorat($im, $x, $y);
    $r = ($rgb >> 16) & 0xFF;
    $g = ($rgb >> 8) & 0xFF;
    $b = $rgb & 0xFF;
    if ($r < 200 || $g < 200 || $b < 200) {
        printf("Left frame inner edge is at x=%d (RGB: %d,%d,%d)\n", $x + 1, $r, $g, $b);
        break;
    }
}

echo "Right side transitions:\n";
for ($x = 1300; $x > 1100; $x--) {
    $rgb = imagecolorat($im, $x, $y);
    $r = ($rgb >> 16) & 0xFF;
    $g = ($rgb >> 8) & 0xFF;
    $b = $rgb & 0xFF;
    if ($r < 200 || $g < 200 || $b < 200) {
        printf("Right border starts at x=%d (RGB: %d,%d,%d)\n", $x, $r, $g, $b);
        break;
    }
}

// Find inner boundary of right frame border
for ($x = 1100; $x < 1300; $x++) {
    $rgb = imagecolorat($im, $x, $y);
    $r = ($rgb >> 16) & 0xFF;
    $g = ($rgb >> 8) & 0xFF;
    $b = $rgb & 0xFF;
    if ($r < 200 || $g < 200 || $b < 200) {
        printf("Right frame inner edge is at x=%d (RGB: %d,%d,%d)\n", $x - 1, $r, $g, $b);
        break;
    }
}
