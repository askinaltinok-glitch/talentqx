<?php

namespace App\Helpers;

class LineChartGenerator
{
    /**
     * Generate a line chart comparing current vs previous month as base64 PNG.
     *
     * @param array<int,int> $currentData  [1 => 5, 2 => 8, ...] day => count
     * @param array<int,int> $previousData [1 => 3, 2 => 6, ...] day => count
     * @param string $title  Chart title
     * @param string $currentLabel  Label for current month line
     * @param string $previousLabel  Label for previous month line
     * @param int $width
     * @param int $height
     * @return string data:image/png;base64,... URI
     */
    public static function generate(
        array $currentData,
        array $previousData,
        string $title = 'Daily Trend',
        string $currentLabel = 'Bu Ay',
        string $previousLabel = 'Geçen Ay',
        int $width = 700,
        int $height = 300,
    ): string {
        $img = imagecreatetruecolor($width, $height);
        imagesavealpha($img, true);

        // Colors
        $white = imagecolorallocate($img, 255, 255, 255);
        $bg = imagecolorallocate($img, 248, 250, 252);
        $gridColor = imagecolorallocate($img, 226, 232, 240);
        $textColor = imagecolorallocate($img, 100, 116, 139);
        $titleColor = imagecolorallocate($img, 15, 23, 42);
        $currentLineColor = imagecolorallocate($img, 15, 76, 129);     // #0f4c81
        $currentFill = imagecolorallocatealpha($img, 15, 76, 129, 100);
        $previousLineColor = imagecolorallocate($img, 156, 163, 175);  // gray
        $dotColor = imagecolorallocate($img, 15, 76, 129);
        $legendBg = imagecolorallocate($img, 241, 245, 249);

        imagefilledrectangle($img, 0, 0, $width - 1, $height - 1, $white);

        // Chart area
        $padLeft = 50;
        $padRight = 20;
        $padTop = 45;
        $padBottom = 35;
        $chartW = $width - $padLeft - $padRight;
        $chartH = $height - $padTop - $padBottom;

        // Fill chart bg
        imagefilledrectangle($img, $padLeft, $padTop, $padLeft + $chartW, $padTop + $chartH, $bg);

        // Determine max value
        $allValues = array_merge(array_values($currentData), array_values($previousData));
        $maxVal = max(1, max($allValues ?: [1]));
        $maxVal = (int) ceil($maxVal * 1.15); // 15% headroom

        // Days
        $days = max(count($currentData), count($previousData), 28);
        $days = min($days, 31);

        // Grid lines (5 horizontal)
        $gridSteps = 5;
        $font = self::findFont();
        $fontSize = 8;

        for ($i = 0; $i <= $gridSteps; $i++) {
            $y = $padTop + $chartH - ($chartH * $i / $gridSteps);
            $val = (int) round($maxVal * $i / $gridSteps);
            imageline($img, $padLeft, (int)$y, $padLeft + $chartW, (int)$y, $gridColor);

            if ($font) {
                imagettftext($img, $fontSize, 0, 5, (int)($y + 4), $textColor, $font, (string)$val);
            } else {
                imagestring($img, 1, 5, (int)($y - 6), (string)$val, $textColor);
            }
        }

        // X-axis labels (every 5 days)
        for ($d = 1; $d <= $days; $d++) {
            $x = $padLeft + ($chartW * ($d - 1) / max($days - 1, 1));
            if ($d === 1 || $d % 5 === 0 || $d === $days) {
                if ($font) {
                    imagettftext($img, $fontSize, 0, (int)($x - 4), $padTop + $chartH + 15, $textColor, $font, (string)$d);
                } else {
                    imagestring($img, 1, (int)($x - 4), $padTop + $chartH + 5, (string)$d, $textColor);
                }
            }
        }

        // Draw previous month line (gray, behind)
        self::drawLine($img, $previousData, $days, $maxVal, $padLeft, $padTop, $chartW, $chartH, $previousLineColor, null, 1);

        // Draw current month line (blue, with fill)
        self::drawLine($img, $currentData, $days, $maxVal, $padLeft, $padTop, $chartW, $chartH, $currentLineColor, $currentFill, 2);

        // Draw dots for current data
        foreach ($currentData as $day => $val) {
            if ($day < 1 || $day > $days) continue;
            $x = $padLeft + ($chartW * ($day - 1) / max($days - 1, 1));
            $y = $padTop + $chartH - ($chartH * $val / $maxVal);
            imagefilledellipse($img, (int)$x, (int)$y, 8, 8, $dotColor);
            imagefilledellipse($img, (int)$x, (int)$y, 4, 4, $white);
        }

        // Title
        if ($font) {
            imagettftext($img, 11, 0, $padLeft, 20, $titleColor, $font, $title);
        } else {
            imagestring($img, 4, $padLeft, 5, self::ascii($title), $titleColor);
        }

        // Legend
        $legendX = $width - 220;
        $legendY = 10;
        imagefilledrectangle($img, $legendX, $legendY, $legendX + 200, $legendY + 22, $legendBg);

        // Current month legend
        imagesetthickness($img, 3);
        imageline($img, $legendX + 8, $legendY + 11, $legendX + 30, $legendY + 11, $currentLineColor);
        imagesetthickness($img, 1);
        if ($font) {
            imagettftext($img, 8, 0, $legendX + 35, $legendY + 15, $titleColor, $font, $currentLabel);
        } else {
            imagestring($img, 2, $legendX + 35, $legendY + 4, self::ascii($currentLabel), $titleColor);
        }

        // Previous month legend
        imageline($img, $legendX + 108, $legendY + 11, $legendX + 130, $legendY + 11, $previousLineColor);
        if ($font) {
            imagettftext($img, 8, 0, $legendX + 135, $legendY + 15, $textColor, $font, $previousLabel);
        } else {
            imagestring($img, 2, $legendX + 135, $legendY + 4, self::ascii($previousLabel), $textColor);
        }

        // Output
        ob_start();
        imagepng($img);
        $data = ob_get_clean();
        imagedestroy($img);

        return 'data:image/png;base64,' . base64_encode($data);
    }

    private static function drawLine(
        $img,
        array $data,
        int $days,
        int $maxVal,
        int $padLeft,
        int $padTop,
        int $chartW,
        int $chartH,
        $lineColor,
        $fillColor,
        int $thickness = 2,
    ): void {
        if (empty($data)) return;

        $points = [];
        for ($d = 1; $d <= $days; $d++) {
            $val = $data[$d] ?? 0;
            $x = $padLeft + ($chartW * ($d - 1) / max($days - 1, 1));
            $y = $padTop + $chartH - ($chartH * $val / $maxVal);
            $points[] = [(int)$x, (int)$y];
        }

        // Fill area under curve
        if ($fillColor !== null && count($points) >= 2) {
            $fillPts = [];
            foreach ($points as $p) {
                $fillPts[] = $p[0];
                $fillPts[] = $p[1];
            }
            // Close to bottom
            $fillPts[] = $points[count($points) - 1][0];
            $fillPts[] = $padTop + $chartH;
            $fillPts[] = $points[0][0];
            $fillPts[] = $padTop + $chartH;
            imagefilledpolygon($img, $fillPts, $fillColor);
        }

        // Draw line segments
        imagesetthickness($img, $thickness);
        for ($i = 0; $i < count($points) - 1; $i++) {
            imageline($img, $points[$i][0], $points[$i][1], $points[$i + 1][0], $points[$i + 1][1], $lineColor);
        }
        imagesetthickness($img, 1);
    }

    /**
     * Generate a horizontal bar chart as base64 PNG.
     */
    public static function barChart(
        array $data,
        string $title = '',
        int $width = 500,
        int $barHeight = 28,
    ): string {
        if (empty($data)) {
            $data = ['No data' => 0];
        }

        $count = count($data);
        $padLeft = 140;
        $padRight = 60;
        $padTop = $title ? 35 : 10;
        $padBottom = 10;
        $gap = 6;
        $height = $padTop + ($count * ($barHeight + $gap)) + $padBottom;

        $img = imagecreatetruecolor($width, $height);
        $white = imagecolorallocate($img, 255, 255, 255);
        $barColor = imagecolorallocate($img, 15, 76, 129);
        $barBg = imagecolorallocate($img, 226, 232, 240);
        $textColor = imagecolorallocate($img, 71, 85, 105);
        $titleColor = imagecolorallocate($img, 15, 23, 42);
        $valColor = imagecolorallocate($img, 15, 76, 129);

        imagefilledrectangle($img, 0, 0, $width - 1, $height - 1, $white);

        $font = self::findFont();
        $maxVal = max(1, max(array_values($data)));

        if ($title && $font) {
            imagettftext($img, 10, 0, 10, 22, $titleColor, $font, $title);
        }

        $i = 0;
        foreach ($data as $label => $val) {
            $y = $padTop + $i * ($barHeight + $gap);
            $barW = (int)(($width - $padLeft - $padRight) * min($val / $maxVal, 1));

            // Background bar
            imagefilledrectangle($img, $padLeft, $y, $width - $padRight, $y + $barHeight - 2, $barBg);
            // Value bar
            if ($barW > 0) {
                imagefilledrectangle($img, $padLeft, $y, $padLeft + $barW, $y + $barHeight - 2, $barColor);
            }

            // Label
            $label = mb_substr((string)$label, 0, 20);
            if ($font) {
                imagettftext($img, 8, 0, 5, $y + $barHeight - 8, $textColor, $font, $label);
                imagettftext($img, 9, 0, $width - $padRight + 8, $y + $barHeight - 7, $valColor, $font, (string)$val);
            } else {
                imagestring($img, 2, 5, $y + 5, self::ascii($label), $textColor);
                imagestring($img, 2, $width - $padRight + 8, $y + 5, (string)$val, $valColor);
            }

            $i++;
        }

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
