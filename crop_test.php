<?php
// crop_test.php
$im = imagecreatefrompng('C:/Users/muhdramdani/Downloads/pdf_pages/quran-50-053.png');
$cropped = imagecrop($im, [
    'x' => 339,
    'y' => 277,
    'width' => 790,
    'height' => 110
]);

if ($cropped !== false) {
    imagepng($cropped, 'C:/Users/muhdramdani/Downloads/pdf_pages/crop_ali_imran.png');
    echo "Saved cropped banner to Downloads/pdf_pages/crop_ali_imran.png\n";
} else {
    echo "Failed to crop image.\n";
}
