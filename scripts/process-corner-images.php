<?php

$files = [
    'upper-left.png',
    'upper-right.png',
    'lower-left.png',
    'lower-right.png',
];

$dir = __DIR__ . '/../public/images/';

foreach ($files as $file) {
    $path = $dir . $file;
    if (! file_exists($path)) {
        echo "Missing: {$file}\n";
        continue;
    }

    $img = imagecreatefrompng($path);
    if ($img === false) {
        echo "Failed to load: {$file}\n";
        continue;
    }

    imagesavealpha($img, true);
    imagealphablending($img, false);

    for ($i = 0; $i < 3; $i++) {
        imagefilter($img, IMG_FILTER_GAUSSIAN_BLUR);
    }

    $width = imagesx($img);
    $height = imagesy($img);

    for ($x = 0; $x < $width; $x++) {
        for ($y = 0; $y < $height; $y++) {
            $rgba = imagecolorat($img, $x, $y);
            $alpha = ($rgba >> 24) & 0x7F;

            if ($alpha >= 127) {
                continue;
            }

            $red = ($rgba >> 16) & 0xFF;
            $green = ($rgba >> 8) & 0xFF;
            $blue = $rgba & 0xFF;
            $luminance = (int) (($red + $green + $blue) / 3);

            if ($luminance > 190) {
                continue;
            }

            $coverage = 1 - ($alpha / 127);
            if ($coverage < 0.06) {
                continue;
            }

            // Nearly black charcoal with subtle grain.
            $baseGray = 4 + (int) ($coverage * 7);
            $noise = random_int(-2, 2);
            $gray = max(2, min(14, $baseGray + $noise));
            $warmth = random_int(-1, 1);
            $r = max(0, min(255, $gray + $warmth));
            $g = max(0, min(255, $gray));
            $b = max(0, min(255, max(0, $gray - 1)));
            $newAlpha = (int) max(22, min(90, $alpha * 0.65));

            $color = imagecolorallocatealpha($img, $r, $g, $b, $newAlpha);
            imagesetpixel($img, $x, $y, $color);
        }
    }

    imagepng($img, $path);
    imagedestroy($img);

    echo "Processed: {$file}\n";
}

echo "Done.\n";
