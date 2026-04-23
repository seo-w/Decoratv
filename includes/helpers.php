<?php
/**
 * UI and Image Helpers for DecoraTV
 */

/**
 * Optimizes and saves an uploaded image.
 * Resizes if necessary and converts to JPEG/PNG for performance.
 */
function upload_material_image($file, $type) {
    $targetDir = __DIR__ . "/../uploads/$type/";
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0755, true);
    }

    $info = getimagesize($file["tmp_name"]);
    if($info === false) return false;
    $mime = $info['mime'];

    // Load image based on MIME type
    $src = false;
    if ($mime == "image/jpeg") {
        $src = imagecreatefromjpeg($file["tmp_name"]);
        $ext = "jpg";
    } elseif ($mime == "image/png") {
        $src = imagecreatefrompng($file["tmp_name"]);
        $ext = "png";
    } elseif ($mime == "image/webp") {
        $src = imagecreatefromwebp($file["tmp_name"]);
        $ext = "webp";
    } elseif ($mime == "image/avif" && function_exists('imagecreatefromavif')) {
        $src = imagecreatefromavif($file["tmp_name"]);
        $ext = "avif";
    }

    if (!$src) {
        return false;
    }

    // Capture the base name and prepare final filename
    $baseName = pathinfo($file["name"], PATHINFO_FILENAME);
    
    // Check if we can actually save in the desired format
    $saveType = $ext;
    if ($ext == "avif" && !function_exists('imageavif')) {
        $saveType = "jpg";
    }

    $fileName = time() . '_' . $baseName . '.' . $saveType;
    $targetFile = $targetDir . $fileName;

    // Optimization: Resize if width > 1200px
    $width = imagesx($src);
    $height = imagesy($src);
    $newWidth = $width;
    $newHeight = $height;

    if ($width > 1200) {
        $newWidth = 1200;
        $newHeight = ($height / $width) * $newWidth;
    }

    $dst = imagecreatetruecolor($newWidth, $newHeight);
    
    // Preserve transparency for PNG, WebP, and AVIF
    if (in_array($saveType, ["png", "webp", "avif"])) {
        imagealphablending($dst, false);
        imagesavealpha($dst, true);
        $transparent = imagecolorallocatealpha($dst, 255, 255, 255, 127);
        imagefilledrectangle($dst, 0, 0, $newWidth, $newHeight, $transparent);
    }

    imagecopyresampled($dst, $src, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

    // Save
    if ($saveType == "png") {
        imagepng($dst, $targetFile, 6);
    } elseif ($saveType == "webp") {
        imagewebp($dst, $targetFile, 85);
    } elseif ($saveType == "avif") {
        imageavif($dst, $targetFile, 85);
    } else {
        imagejpeg($dst, $targetFile, 85);
    }

    imagedestroy($src);
    imagedestroy($dst);

    return "uploads/$type/" . $fileName;
}
