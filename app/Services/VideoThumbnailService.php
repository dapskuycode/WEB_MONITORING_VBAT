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
        try {
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

            // Check if shell_exec or exec is available and not disabled by php.ini (e.g. on aaPanel)
            $disabledFunctions = explode(',', ini_get('disable_functions') ?: '');
            $disabledFunctions = array_map('trim', $disabledFunctions);

            // If ffmpeg CLI is available via exec
            if (function_exists('exec') && ! in_array('exec', $disabledFunctions)) {
                @exec('ffmpeg -y -ss 00:00:01 -i '.escapeshellarg($fullPath).' -vframes 1 -q:v 2 '.escapeshellarg($thumbFullPath).' 2>&1', $out, $ret);
                if ($ret === 0 && file_exists($thumbFullPath)) {
                    return $thumbRelative;
                }
            }

            // Fallback to python3 cv2 if shell_exec is allowed
            if (function_exists('shell_exec') && ! in_array('shell_exec', $disabledFunctions)) {
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

                $output = @\shell_exec('python3 -c '.escapeshellarg($script));

                if ($output && str_contains($output, 'SUCCESS') && file_exists($thumbFullPath)) {
                    return $thumbRelative;
                }
            }
        } catch (\Throwable $e) {
            // Silently report and ignore so video upload never crashes
            report($e);
        }

        return null;
    }
}
