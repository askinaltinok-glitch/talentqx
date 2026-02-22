<?php

namespace App\Helpers;

class RadarChartGenerator
{
    /**
     * Generate a radar/spider chart as a base64-encoded PNG data URI.
     *
     * @param array<string, int> $scores  ['communication' => 85, ...]
     * @param array<string, string> $labels  ['communication' => 'İletişim', ...]
     * @param int $size  Image dimensions (square)
     * @return string  data:image/png;base64,... URI
     */
    public static function generate(array $scores, array $labels, int $size = 640): string
    {
        $img = imagecreatetruecolor($size, $size);
        imagesavealpha($img, true);

        // Colors
        $white = imagecolorallocate($img, 255, 255, 255);
        $gridLine = imagecolorallocate($img, 203, 213, 225);
        $gridDash = imagecolorallocate($img, 226, 232, 240);
        $axisColor = imagecolorallocate($img, 226, 232, 240);
        $dataFill = imagecolorallocatealpha($img, 15, 76, 129, 90);
        $dataLine = imagecolorallocate($img, 15, 76, 129);
        $dataDot = imagecolorallocate($img, 15, 76, 129);
        $textColor = imagecolorallocate($img, 71, 85, 105);
        $scoreColor = imagecolorallocate($img, 15, 76, 129);
        $centerColor = imagecolorallocate($img, 15, 76, 129);

        // Fill background
        imagefilledrectangle($img, 0, 0, $size - 1, $size - 1, $white);

        $cx = $size / 2;
        $cy = $size / 2;
        $maxR = $size * 0.24;  // Smaller radius for maximum label room
        $n = count($scores);
        $keys = array_keys($scores);

        // Draw grid polygons (20%, 40%, 60%, 80%, 100%)
        $levels = [20, 40, 60, 80, 100];
        imagesetthickness($img, 1);
        foreach ($levels as $level) {
            $r = $maxR * ($level / 100);
            $pts = [];
            for ($i = 0; $i < $n; $i++) {
                $angle = -M_PI / 2 + ($i * 2 * M_PI / $n);
                $pts[] = (int)round($cx + $r * cos($angle));
                $pts[] = (int)round($cy + $r * sin($angle));
            }
            $color = ($level === 100) ? $gridLine : $gridDash;
            for ($i = 0; $i < $n; $i++) {
                $x1 = $pts[$i * 2];
                $y1 = $pts[$i * 2 + 1];
                $x2 = $pts[(($i + 1) % $n) * 2];
                $y2 = $pts[(($i + 1) % $n) * 2 + 1];
                if ($level === 100) {
                    imageline($img, $x1, $y1, $x2, $y2, $color);
                } else {
                    self::dashedLine($img, $x1, $y1, $x2, $y2, $color);
                }
            }
        }

        // Draw axis lines
        for ($i = 0; $i < $n; $i++) {
            $angle = -M_PI / 2 + ($i * 2 * M_PI / $n);
            $ax = (int)round($cx + $maxR * cos($angle));
            $ay = (int)round($cy + $maxR * sin($angle));
            imageline($img, (int)$cx, (int)$cy, $ax, $ay, $axisColor);
        }

        // Draw data polygon (filled)
        $dataPts = [];
        for ($i = 0; $i < $n; $i++) {
            $angle = -M_PI / 2 + ($i * 2 * M_PI / $n);
            $val = $scores[$keys[$i]] ?? 0;
            $r = $maxR * ($val / 100);
            $dataPts[] = (int)round($cx + $r * cos($angle));
            $dataPts[] = (int)round($cy + $r * sin($angle));
        }
        imagefilledpolygon($img, $dataPts, $dataFill);

        // Draw data polygon outline
        imagesetthickness($img, 3);
        for ($i = 0; $i < $n; $i++) {
            $x1 = $dataPts[$i * 2];
            $y1 = $dataPts[$i * 2 + 1];
            $x2 = $dataPts[(($i + 1) % $n) * 2];
            $y2 = $dataPts[(($i + 1) % $n) * 2 + 1];
            imageline($img, $x1, $y1, $x2, $y2, $dataLine);
        }
        imagesetthickness($img, 1);

        // Draw data points
        for ($i = 0; $i < $n; $i++) {
            $px = $dataPts[$i * 2];
            $py = $dataPts[$i * 2 + 1];
            imagefilledellipse($img, $px, $py, 12, 12, $dataDot);
            imagefilledellipse($img, $px, $py, 6, 6, $white);
        }

        // Font
        $font = self::findFont();

        // Draw labels + scores
        for ($i = 0; $i < $n; $i++) {
            $angle = -M_PI / 2 + ($i * 2 * M_PI / $n);
            $labelR = $maxR + ($size * 0.17);  // Extra padding for long Turkish labels
            $lx = $cx + $labelR * cos($angle);
            $ly = $cy + $labelR * sin($angle);

            $label = $labels[$keys[$i]] ?? $keys[$i];
            $scoreVal = $scores[$keys[$i]] ?? 0;

            if ($font) {
                $fontSize = $size * 0.02;
                $scoreFontSize = $size * 0.024;

                $bbox = imagettfbbox($fontSize, 0, $font, $label);
                $tw = $bbox[2] - $bbox[0];
                $th = $bbox[1] - $bbox[7];

                // Position based on angle quadrant
                if (cos($angle) < -0.3) {
                    $tx = $lx - $tw - 5;
                } elseif (cos($angle) > 0.3) {
                    $tx = $lx + 5;
                } else {
                    $tx = $lx - $tw / 2;
                }

                if (sin($angle) < -0.3) {
                    $ty = $ly - 5;
                } elseif (sin($angle) > 0.3) {
                    $ty = $ly + $th + 10;
                } else {
                    $ty = $ly + $th / 2;
                }

                // Clamp within canvas bounds
                $tx = max(8, min($tx, $size - $tw - 8));
                $ty = max($th + 4, min($ty, $size - 8));

                imagettftext($img, $fontSize, 0, (int)$tx, (int)$ty, $textColor, $font, $label);
                // Score below label
                $sBbox = imagettfbbox($scoreFontSize, 0, $font, (string)$scoreVal);
                $sw = $sBbox[2] - $sBbox[0];
                $sx = max(8, min($tx + ($tw - $sw) / 2, $size - $sw - 8));
                imagettftext($img, $scoreFontSize, 0, (int)$sx, (int)($ty + $th + 4), $scoreColor, $font, (string)$scoreVal);
            } else {
                $label = self::ascii($label);
                $tw = strlen($label) * imagefontwidth(3);
                imagestring($img, 3, (int)($lx - $tw / 2), (int)($ly - 7), $label, $textColor);
                $sw = strlen((string)$scoreVal) * imagefontwidth(4);
                imagestring($img, 4, (int)($lx - $sw / 2), (int)($ly + 7), (string)$scoreVal, $scoreColor);
            }
        }

        // Center score
        $avgScore = count($scores) > 0 ? (int)round(array_sum($scores) / count($scores)) : 0;
        if ($font) {
            $cFontSize = $size * 0.055;
            $cBbox = imagettfbbox($cFontSize, 0, $font, (string)$avgScore);
            $cw = $cBbox[2] - $cBbox[0];
            $ch = $cBbox[1] - $cBbox[7];
            imagettftext($img, $cFontSize, 0, (int)($cx - $cw / 2), (int)($cy + $ch / 3), $centerColor, $font, (string)$avgScore);
        } else {
            $text = (string)$avgScore;
            $tw = strlen($text) * imagefontwidth(5);
            imagestring($img, 5, (int)($cx - $tw / 2), (int)($cy - 8), $text, $centerColor);
        }

        // Output as PNG
        ob_start();
        imagepng($img);
        $data = ob_get_clean();
        imagedestroy($img);

        return 'data:image/png;base64,' . base64_encode($data);
    }

    private static function findFont(): ?string
    {
        $candidates = [
            '/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf',
            '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf',
            '/usr/share/fonts/TTF/DejaVuSans.ttf',
            '/usr/share/fonts/dejavu/DejaVuSans.ttf',
        ];
        foreach ($candidates as $f) {
            if (file_exists($f)) return $f;
        }
        return null;
    }

    private static function dashedLine($img, int $x1, int $y1, int $x2, int $y2, $color, int $dashLen = 5): void
    {
        $dx = $x2 - $x1;
        $dy = $y2 - $y1;
        $dist = sqrt($dx * $dx + $dy * $dy);
        if ($dist == 0) return;
        $steps = (int)($dist / $dashLen);
        for ($i = 0; $i < $steps; $i += 2) {
            $sx = (int)($x1 + $dx * $i / $steps);
            $sy = (int)($y1 + $dy * $i / $steps);
            $ex = (int)($x1 + $dx * min($i + 1, $steps) / $steps);
            $ey = (int)($y1 + $dy * min($i + 1, $steps) / $steps);
            imageline($img, $sx, $sy, $ex, $ey, $color);
        }
    }

    private static function ascii(string $text): string
    {
        $map = [
            'İ' => 'I', 'ı' => 'i', 'Ş' => 'S', 'ş' => 's',
            'Ğ' => 'G', 'ğ' => 'g', 'Ü' => 'U', 'ü' => 'u',
            'Ö' => 'O', 'ö' => 'o', 'Ç' => 'C', 'ç' => 'c',
        ];
        return strtr($text, $map);
    }
}
