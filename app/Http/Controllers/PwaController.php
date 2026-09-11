<?php

namespace App\Http\Controllers;

use App\Support\AppVersion;
use App\Support\Pwa;
use Illuminate\Http\Response;
use Illuminate\View\View;

class PwaController extends Controller
{
    public function manifest(): Response
    {
        return response()
            ->json(Pwa::manifest(), 200, [
                'Content-Type' => 'application/manifest+json; charset=utf-8',
                'Cache-Control' => 'no-cache, no-store, must-revalidate',
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    public function icon(int $size): Response
    {
        $size = max(48, min(512, $size));

        if (! extension_loaded('gd')) {
            $svg = public_path('icons/app-icon.svg');
            if (is_file($svg)) {
                return response(file_get_contents($svg), 200, [
                    'Content-Type' => 'image/svg+xml',
                    'Cache-Control' => 'public, max-age=86400',
                ]);
            }

            abort(404);
        }

        $image = imagecreatetruecolor($size, $size);
        imagesavealpha($image, true);
        $transparent = imagecolorallocatealpha($image, 0, 0, 0, 127);
        imagefill($image, 0, 0, $transparent);

        $bgTop = imagecolorallocate($image, 47, 95, 140);
        $bgBottom = imagecolorallocate($image, 79, 134, 190);
        for ($y = 0; $y < $size; $y++) {
            $ratio = $y / max(1, $size - 1);
            $r = (int) (47 + (79 - 47) * $ratio);
            $g = (int) (95 + (134 - 95) * $ratio);
            $b = (int) (140 + (190 - 140) * $ratio);
            $line = imagecolorallocate($image, $r, $g, $b);
            imageline($image, 0, $y, $size, $y, $line);
        }

        $white = imagecolorallocate($image, 255, 255, 255);
        $cx = (int) ($size / 2);
        $cy = (int) ($size * 0.46);
        $heart = (int) ($size * 0.22);
        imagefilledellipse($image, $cx - (int) ($heart * 0.55), $cy - (int) ($heart * 0.15), $heart, $heart, $white);
        imagefilledellipse($image, $cx + (int) ($heart * 0.55), $cy - (int) ($heart * 0.15), $heart, $heart, $white);
        $points = [
            $cx, $cy + (int) ($heart * 1.35),
            $cx - (int) ($heart * 1.15), $cy - (int) ($heart * 0.05),
            $cx + (int) ($heart * 1.15), $cy - (int) ($heart * 0.05),
        ];
        imagefilledpolygon($image, $points, $white);

        $eyeY = (int) ($size * 0.72);
        $eyeW = (int) ($size * 0.34);
        $eyeH = (int) ($size * 0.12);
        imagefilledellipse($image, $cx, $eyeY, $eyeW, $eyeH, $white);
        $pupil = imagecolorallocate($image, 47, 95, 140);
        imagefilledellipse($image, $cx, $eyeY, (int) ($eyeW * 0.35), (int) ($eyeH * 0.85), $pupil);

        ob_start();
        imagepng($image);
        $png = ob_get_clean();
        imagedestroy($image);

        return response($png, 200, [
            'Content-Type' => 'image/png',
            'Cache-Control' => 'public, max-age=604800',
        ]);
    }

    public function offline(): View
    {
        return view('pwa.offline');
    }

    public function serviceWorker(): Response
    {
        $version = AppVersion::current();
        $content = view('pwa.service-worker', ['version' => $version])->render();

        return response($content, 200, [
            'Content-Type' => 'application/javascript; charset=utf-8',
            'Service-Worker-Allowed' => '/',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
        ]);
    }
}
