<?php
require_once 'includes/helpers.php';

function test_format($ext) {
    echo "Testing $ext format...\n";
    $testFile = "test_image.$ext";
    
    // Create a dummy image
    $img = imagecreatetruecolor(100, 100);
    $color = imagecolorallocate($img, 255, 0, 0);
    imagefilledrectangle($img, 0, 0, 99, 99, $color);
    
    if ($ext == 'webp') {
        imagewebp($img, $testFile);
    } elseif ($ext == 'avif') {
        if (function_exists('imageavif')) {
            imageavif($img, $testFile);
        } else {
            echo "SKIPPED: imageavif not supported\n";
            imagedestroy($img);
            return;
        }
    }
    imagedestroy($img);

    // Mock $_FILES
    $file = [
        'name' => "original_name.$ext",
        'tmp_name' => $testFile
    ];

    $result = upload_material_image($file, 'frame');
    
    if ($result) {
        echo "SUCCESS: Uploaded to $result\n";
        // Check if file exists and has correct extension
        if (file_exists($result)) {
            echo "Verified: File exists at $result\n";
            unlink($result); // Clean up
        } else {
            echo "ERROR: File not found at expected path $result\n";
        }
    } else {
        echo "FAILURE: upload_material_image returned false\n";
    }

    unlink($testFile);
}

test_format('webp');
echo "-------------------\n";
test_format('avif');
?>
