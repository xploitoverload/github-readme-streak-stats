<?php

declare(strict_types=1);

/**
 * Generate a standalone, animated SVG Activity Graph matching the Microsoft Dark theme.
 *
 * @param array<string,int> $contributions Y-M-D date => count
 * @param array<string,mixed> $params Options (days, title, theme, colors)
 * @return string Full SVG XML string
 */
function renderActivityGraph(array $contributions, array $params = []): string
{
    $numDays = isset($params["days"]) ? max(7, min(90, intval($params["days"]))) : 31;
    $customTitle = !empty($params["custom_title"]) ? strval($params["custom_title"]) : "KALPESH SOLANKI's Contribution Graph";
    
    // Theme colors (Microsoft Dark default)
    $bgColor = $params["bg_color"] ?? "#000000";
    $borderColor = $params["border_color"] ?? "#737373";
    $titleColor = $params["title_color"] ?? "#F25022";
    $lineColor = $params["line"] ?? "#00A4EF";
    $pointColor = $params["point"] ?? "#FFB900";
    $peakColor = $params["peak"] ?? "#F25022";
    $gridColor = $params["color"] ?? "#7FBA00";
    $textColor = $params["color"] ?? "#7FBA00";
    $areaColor = $params["area_color"] ?? "#00A4EF";

    // Slice last N days
    $recent = array_slice($contributions, -$numDays, $numDays, true);
    if (empty($recent)) {
        // Fallback demo data if empty
        $today = new DateTime();
        for ($i = $numDays - 1; $i >= 0; $i--) {
            $d = (clone $today)->modify("-{$i} day")->format("Y-m-d");
            $recent[$d] = 0;
        }
    }

    $dates = array_keys($recent);
    $counts = array_values($recent);
    $n = count($counts);

    $totalContributions = array_sum($counts);
    $maxCount = max($counts);
    // Round max Y to nice scale (minimum 10)
    $yMax = max(10, (int) (ceil(($maxCount <= 0 ? 10 : $maxCount) / 10.0) * 10));

    // Graph viewport coordinates
    $width = 1200;
    $height = 420;
    $left = 90.0;
    $right = 1150.0;
    $top = 85.0;
    $bottom = 350.0;
    $plotWidth = $right - $left;
    $plotHeight = $bottom - $top;

    // Calculate coordinates for each point
    $points = [];
    $peakIndex = 0;
    $highestCount = -1;

    for ($i = 0; $i < $n; $i++) {
        $x = $left + ($n > 1 ? ($i * ($plotWidth / ($n - 1))) : ($plotWidth / 2));
        $count = $counts[$i];
        $ratio = min(1.0, max(0.0, $count / $yMax));
        $y = $bottom - ($ratio * $plotHeight);

        $dateParts = explode("-", $dates[$i]);
        $dayLabel = isset($dateParts[2]) ? strval(intval($dateParts[2])) : strval($i + 1);

        if ($count > $highestCount) {
            $highestCount = $count;
            $peakIndex = $i;
        }

        $points[] = [
            "x" => round($x, 2),
            "y" => round($y, 2),
            "count" => $count,
            "date" => $dates[$i],
            "day" => $dayLabel,
        ];
    }

    // Generate smooth cubic bezier path
    $linePath = "";
    if ($n > 0) {
        $linePath = "M {$points[0]['x']} {$points[0]['y']}";
        for ($i = 0; $i < $n - 1; $i++) {
            $p0 = $points[max(0, $i - 1)];
            $p1 = $points[$i];
            $p2 = $points[$i + 1];
            $p3 = $points[min($n - 1, $i + 2)];

            $cp1x = round($p1["x"] + ($p2["x"] - $p0["x"]) / 6.0, 2);
            $cp1y = round(max($top, min($bottom, $p1["y"] + ($p2["y"] - $p0["y"]) / 6.0)), 2);

            $cp2x = round($p2["x"] - ($p3["x"] - $p1["x"]) / 6.0, 2);
            $cp2y = round(max($top, min($bottom, $p2["y"] - ($p3["y"] - $p1["y"]) / 6.0)), 2);

            $linePath .= " C {$cp1x} {$cp1y}, {$cp2x} {$cp2y}, {$p2['x']} {$p2['y']}";
        }
    }

    $firstX = $points[0]["x"];
    $lastX = $points[$n - 1]["x"];
    $areaPath = "{$linePath} L {$lastX} {$bottom} L {$firstX} {$bottom} Z";

    // Build Y-axis grid lines (5 steps)
    $gridLinesSvg = "";
    $ySteps = 5;
    for ($s = 0; $s <= $ySteps; $s++) {
        $val = round(($yMax / $ySteps) * $s);
        $yPos = round($bottom - ($s / $ySteps) * $plotHeight, 2);
        $gridLinesSvg .= "<line x1='{$left}' y1='{$yPos}' x2='{$right}' y2='{$yPos}' stroke='{$gridColor}' stroke-opacity='0.15' stroke-dasharray='3,3'/>\n";
        $gridLinesSvg .= "<text x='" . ($left - 12) . "' y='" . ($yPos + 4.5) . "' text-anchor='end' fill='{$textColor}' font-size='12px' font-family='\"Segoe UI\", Ubuntu, sans-serif' font-weight='600'>{$val}</text>\n";
    }

    // Build X-axis ticks and labels
    $xLabelsSvg = "";
    for ($i = 0; $i < $n; $i++) {
        $pt = $points[$i];
        $x = $pt["x"];
        $day = $pt["day"];
        // Subtle vertical guideline
        $xLabelsSvg .= "<line x1='{$x}' y1='{$top}' x2='{$x}' y2='{$bottom}' stroke='{$gridColor}' stroke-opacity='0.08'/>\n";
        // Label at bottom
        $xLabelsSvg .= "<text x='{$x}' y='" . ($bottom + 22) . "' text-anchor='middle' fill='{$textColor}' font-size='11px' font-family='\"Segoe UI\", Ubuntu, sans-serif' font-weight='600'>{$day}</text>\n";
    }

    // Build Points & Peak Beacon
    $pointsSvg = "";
    for ($i = 0; $i < $n; $i++) {
        $pt = $points[$i];
        $x = $pt["x"];
        $y = $pt["y"];
        $cnt = $pt["count"];

        if ($i === $peakIndex && $cnt > 0) {
            // Special animated peak flame beacon
            $pointsSvg .= "
            <g class='peak-group'>
                <circle class='peak-halo' cx='{$x}' cy='{$y}' r='10' fill='none' stroke='{$peakColor}' stroke-width='2'/>
                <circle class='peak-dot' cx='{$x}' cy='{$y}' r='5.5' fill='{$peakColor}' stroke='#FFFFFF' stroke-width='2'/>
                <!-- Tooltip badge for peak -->
                <g transform='translate({$x}, " . ($y - 14) . ")'>
                    <rect x='-18' y='-18' width='36' height='16' rx='4' fill='#151515' stroke='{$peakColor}' stroke-width='1'/>
                    <text x='0' y='-6' text-anchor='middle' fill='#FFFFFF' font-size='10px' font-family='\"Segoe UI\", Ubuntu, sans-serif' font-weight='700'>{$cnt}</text>
                </g>
            </g>";
        } elseif ($cnt > 0) {
            $pointsSvg .= "<circle class='data-dot' cx='{$x}' cy='{$y}' r='3.8' fill='{$pointColor}' stroke='{$peakColor}' stroke-width='1.5'/>\n";
        } else {
            $pointsSvg .= "<circle cx='{$x}' cy='{$y}' r='2' fill='{$gridColor}' fill-opacity='0.4'/>\n";
        }
    }

    $titleEscaped = htmlspecialchars($customTitle, ENT_QUOTES | ENT_SUBSTITUTE | ENT_XML1, "UTF-8");
    $avgDaily = $n > 0 ? round($totalContributions / $n, 1) : 0;

    return <<<SVG
<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 {$width} {$height}' width='100%' height='auto' style='isolation: isolate; background: {$bgColor}; border-radius: 8px;'>
    <style>
        @keyframes drawLine {
            0% { stroke-dashoffset: 4000; }
            100% { stroke-dashoffset: 0; }
        }
        @keyframes fadeInArea {
            0% { opacity: 0; }
            100% { opacity: 0.85; }
        }
        @keyframes pulsePeak {
            0% { r: 5; opacity: 1; stroke-width: 2.5; }
            50% { r: 13; opacity: 0.3; stroke-width: 1.5; }
            100% { r: 18; opacity: 0; stroke-width: 0.5; }
        }
        @keyframes popPoint {
            0% { transform: scale(0); }
            80% { transform: scale(1.3); }
            100% { transform: scale(1); }
        }
        .activity-curve {
            stroke-dasharray: 4000;
            stroke-dashoffset: 4000;
            animation: drawLine 1.8s cubic-bezier(0.25, 1, 0.5, 1) forwards 0.2s;
            filter: drop-shadow(0 0 5px rgba(0, 164, 239, 0.75));
        }
        .activity-area {
            animation: fadeInArea 1.4s ease-out forwards 0.5s;
        }
        .peak-halo {
            animation: pulsePeak 1.8s ease-out infinite;
            transform-origin: center;
        }
        .data-dot {
            transition: transform 0.2s;
        }
    </style>
    <defs>
        <linearGradient id='activity-area-grad' x1='0%' y1='0%' x2='0%' y2='100%'>
            <stop offset='0%' stop-color='{$areaColor}' stop-opacity='0.45'/>
            <stop offset='60%' stop-color='{$gridColor}' stop-opacity='0.15'/>
            <stop offset='100%' stop-color='{$bgColor}' stop-opacity='0.0'/>
        </linearGradient>
        <linearGradient id='activity-line-grad' x1='0%' y1='0%' x2='100%' y2='0%'>
            <stop offset='0%' stop-color='{$lineColor}'/>
            <stop offset='70%' stop-color='#00F0FF'/>
            <stop offset='100%' stop-color='{$lineColor}'/>
        </linearGradient>
    </defs>

    <!-- Card Background -->
    <rect x='0.5' y='0.5' width='{$width}' height='{$height}' rx='8' fill='{$bgColor}' stroke='{$borderColor}' stroke-width='1'/>

    <!-- Header Section -->
    <g transform='translate(90, 42)'>
        <text x='0' y='0' fill='{$titleColor}' font-size='22px' font-family='\"Segoe UI\", Ubuntu, sans-serif' font-weight='700' letter-spacing='-0.3px' style='filter: drop-shadow(0 0 6px rgba(242, 80, 34, 0.35));'>
            {$titleEscaped}
        </text>
    </g>

    <!-- Telemetry Stats Pill Badge -->
    <g transform='translate(1150, 42)'>
        <text x='0' y='0' text-anchor='end' fill='{$pointColor}' font-size='13px' font-family='\"Segoe UI\", Ubuntu, sans-serif' font-weight='600'>
            Last {$numDays} Days &bull; Peak: {$highestCount}/day &bull; Total: {$totalContributions} &bull; Avg: {$avgDaily}/day
        </text>
    </g>

    <!-- Grids & Axis Ticks -->
    <g id='grid-layer'>
        {$gridLinesSvg}
        {$xLabelsSvg}
    </g>

    <!-- Glowing Area Fill -->
    <path class='activity-area' d='{$areaPath}' fill='url(#activity-area-grad)' opacity='0'/>

    <!-- Electric Activity Curve -->
    <path class='activity-curve' d='{$linePath}' fill='none' stroke='url(#activity-line-grad)' stroke-width='3.6' stroke-linecap='round' stroke-linejoin='round'/>

    <!-- Data Points & Peak Pulse -->
    <g id='points-layer'>
        {$pointsSvg}
    </g>

    <!-- Axis Labels -->
    <text x='620' y='398' text-anchor='middle' fill='{$textColor}' font-size='13px' font-family='\"Segoe UI\", Ubuntu, sans-serif' font-weight='600' letter-spacing='0.5px'>Days</text>
    <text x='28' y='215' text-anchor='middle' fill='{$textColor}' font-size='13px' font-family='\"Segoe UI\", Ubuntu, sans-serif' font-weight='600' transform='rotate(-90, 28, 215)' letter-spacing='0.5px'>Contributions</text>
</svg>
SVG;
}
