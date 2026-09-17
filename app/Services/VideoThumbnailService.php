<?php

namespace App\Services;

class VideoThumbnailService
{
    /**
     * Generate a thumbnail for a video stored in storage/app/public/.
     *
     * @param  string  $relativeMediaPath  e.g. 'campaigns/hero/abc.mp4'
     * @return string|null Relative thumbnail path e.g. 'campaigns/hero/abc_thumb.jpg'
     */
    public static function generateThumbnail(string $relativeMediaPath): ?string
    {
        $fullPath = storage_path('app/public/'.$relativeMediaPath);
        if (! file_exists($fullPath)) {
            $fullPath = public_path($relativeMediaPath);
        }

        if (! file_exists($fullPath)) {
            return null;
        }

        $base = pathinfo($relativeMediaPath, PATHINFO_FILENAME);
        $dir = pathinfo($relativeMediaPath, PATHINFO_DIRNAME);
        $thumbRelative = ($dir === '.' ? '' : $dir.'/').$base.'_thumb.jpg';
        $thumbFullPath = storage_path('app/public/'.$thumbRelative);

        // Run python cv2 frame extractor
        $script = <<<PYTHON
import cv2
import os

cap = cv2.VideoCapture("$fullPath")
cap.set(cv2.CAP_PROP_POS_MSEC, 500)
ret, frame = cap.read()
if not ret:
    cap.set(cv2.CAP_PROP_POS_FRAMES, 0)
    ret, frame = cap.read()
if ret:
    os.makedirs(os.path.dirname("$thumbFullPath"), exist_ok=True)
    cv2.imwrite("$thumbFullPath", frame)
    print("SUCCESS")
cap.release()
PYTHON;

        $output = @shell_exec('python3 -c '.escapeshellarg($script));

        if ($output && str_contains($output, 'SUCCESS') && file_exists($thumbFullPath)) {
            return $thumbRelative;
        }

        return null;
    }
}
