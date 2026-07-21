<?php
// crop_600.php
$im = imagecreatefrompng('C:/Users/muhdramdani/Downloads/pdf_pages/quran-50-600-053.png');
$width = imagesx($im);
$height = imagesy($im);
echo "600 DPI Image size: {$width}x{$height}\n";

// Adjust coordinates to be exact for 600 DPI
$crop = imagecrop($im, [
    'x' => 1344,
    'y' => 1112,
    'width' => 3160,
    'height' => 440
]);

if ($crop) {
    imagepng($crop, 'C:/Users/muhdramdani/Downloads/pdf_pages/crop_600_ali_imran.png');
    echo "Saved 600 DPI cropped banner (3160x440px) to Downloads/pdf_pages/crop_600_ali_imran.png\n";
} else {
    echo "Failed to crop 600 DPI image.\n";
}
