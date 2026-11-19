<?php

declare(strict_types=1);

/**
 * Generate a standalone animated SVG of the King Cobra Contribution Predator.
 * Renders the user's authentic contribution calendar matrix without extraneous text banners,
 * where an authentic, hooded, banded King Cobra glides horizontally along each row
 * with zero vertical wobble, eating and completely emptying every single active contribution box
 * until the entire board is 100% cleared.
 *
 * @param array<int,array{date:string,count:int,level:int}> $days Array of daily contributions
 * @param array<string,mixed> $params Options
 * @return string Full SVG XML string
 */
function renderKingCobra(array $days, array $params = []): string
{
    $cell = 16;
    $cellSize = 12;
    $gridX0 = 32;
    $gridY0 = 32;

    $svgWidth = 890;
    $svgHeight = 175;
    $duration = 24.0; // 24 seconds per complete sweep

    // Orthogonal 7-row serpentine sweep:
    // Snake has 8 segments (head s0 + body s1..s7).
    // Approach: head starts at (-8 * cell, 0) and glides into col 0 along row 0.
    $pathSegments = [
        // Approach row 0
        [-8 * $cell, 0, 0, 0, 0],
        // Row 0: right (cols 0 -> 52)
        [0, 0, 52 * $cell, 0, 0],
        [52 * $cell, 0, 52 * $cell, 1 * $cell, 90],
        // Row 1: left (cols 52 -> 0)
        [52 * $cell, 1 * $cell, 0, 1 * $cell, 180],
        [0, 1 * $cell, 0, 2 * $cell, 90],
        // Row 2: right (cols 0 -> 52)
        [0, 2 * $cell, 52 * $cell, 2 * $cell, 0],
        [52 * $cell, 2 * $cell, 52 * $cell, 3 * $cell, 90],
        // Row 3: left (cols 52 -> 0)
        [52 * $cell, 3 * $cell, 0, 3 * $cell, 180],
        [0, 3 * $cell, 0, 4 * $cell, 90],
        // Row 4: right (cols 0 -> 52)
        [0, 4 * $cell, 52 * $cell, 4 * $cell, 0],
        [52 * $cell, 4 * $cell, 52 * $cell, 5 * $cell, 90],
        // Row 5: left (cols 52 -> 0)
        [52 * $cell, 5 * $cell, 0, 5 * $cell, 180],
        [0, 5 * $cell, 0, 6 * $cell, 90],
        // Row 6: right (cols 0 -> 52)
        [0, 6 * $cell, 52 * $cell, 6 * $cell, 0],
        // Exit off right edge of row 6
        [52 * $cell, 6 * $cell, 60 * $cell, 6 * $cell, 0],
    ];

    $activeDist = 0;
    foreach ($pathSegments as $seg) {
        $activeDist += abs($seg[2] - $seg[0]) + abs($seg[3] - $seg[1]);
    }

    // 92% active sweep time, 8% loop reset interval
    $totalDist = $activeDist / 0.92;

    $timedSegments = [];
    $cum = 0.0;
    foreach ($pathSegments as $seg) {
        $d = abs($seg[2] - $seg[0]) + abs($seg[3] - $seg[1]);
        $t1 = round(($cum / (float) $totalDist) * 100.0, 3);
        $cum += $d;
        $t2 = round(($cum / (float) $totalDist) * 100.0, 3);
        $timedSegments[] = [
            'x1' => $seg[0],
            'y1' => $seg[1],
            'x2' => $seg[2],
            'y2' => $seg[3],
            't1' => $t1,
            't2' => $t2,
            'ang' => $seg[4],
        ];
    }

    // Calculate transit/eat timestamp for every grid cell (col, row)
    $cellEatTimes = [];
    for ($r = 0; $r < 7; $r++) {
        $segIdx = 1 + $r * 2;
        $seg = $timedSegments[$segIdx];
        $isLeftToRight = ($r % 2 === 0);
        for ($c = 0; $c < 53; $c++) {
            $frac = $isLeftToRight ? ($c / 52.0) : ((52 - $c) / 52.0);
            $tCell = round($seg['t1'] + $frac * ($seg['t2'] - $seg['t1']), 2);
            $cellEatTimes["{$c}_{$r}"] = $tCell;
        }
    }

    // Build King Cobra @keyframes
    $kfLines = ["@keyframes cobraMove {"];
    foreach ($timedSegments as $seg) {
        $x1 = $seg['x1'];
        $y1 = $seg['y1'];
        $x2 = $seg['x2'];
        $y2 = $seg['y2'];
        $ang = $seg['ang'];
        $rot = "rotate({$ang}deg)";
        $kfLines[] = "  {$seg['t1']}% { transform: translate({$x1}px, {$y1}px) {$rot}; }";
        $kfLines[] = "  {$seg['t2']}% { transform: translate({$x2}px, {$y2}px) {$rot}; }";
    }
    // Hold offscreen during reset
    $kfLines[] = "  92.1%, 100% { transform: translate(-128px, 0px) rotate(0deg); }";
    $kfLines[] = "}";
    $cobraMoveKf = implode("\n", $kfLines);

    // Follow-the-leader animation delay for 8 body segments
    $stepTime = ($duration * 0.92) / (float) ($activeDist / $cell);
    $numSegments = 8;
    $sRules = [];
    for ($s = 0; $s < $numSegments; $s++) {
        $phase = round(fmod($duration - $s * $stepTime, $duration), 4);
        $sRules[] = ".s{$s} { transform-origin: 8px 8px; animation: cobraMove {$duration}s linear infinite -{$phase}s; }";
    }

    // Contribution cell styling: 100% eating and emptying
    $levelColors = [
        0 => "#161b22",
        1 => "#0e4429",
        2 => "#006d32",
        3 => "#26a641",
        4 => "#39d353",
    ];

    $cellCss = [];
    $cellRects = [];
    foreach ($days as $i => $d) {
        $col = intdiv($i, 7);
        $row = $i % 7;
        if ($col > 52) {
            break;
        }
        $cx = $col * $cell;
        $cy = $row * $cell;
        $lvl = intval($d['level'] ?? 0);
        $cid = "c_{$col}_{$row}";

        if ($lvl > 0) {
            $tEat = $cellEatTimes["{$col}_{$row}"] ?? 50.0;
            $tFlash = round(min(100.0, $tEat + 0.12), 2);
            $tEmpty = round(min(100.0, $tEat + 0.35), 2);
            $activeColor = $levelColors[$lvl] ?? "#006d32";

            $cellCss[] = "
            @keyframes kf_{$cid} {
                0%, {$tEat}% { fill: {$activeColor}; opacity: 1; }
                {$tFlash}% { fill: #56ff78; opacity: 1; }
                {$tEmpty}%, 99.4% { fill: #161b22; opacity: 0.35; }
                100% { fill: {$activeColor}; opacity: 1; }
            }
            .{$cid} { fill: {$activeColor}; animation: kf_{$cid} {$duration}s linear infinite; }
            ";
            $cellRects[] = "<rect class='c {$cid}' x='" . ($cx + 2) . "' y='" . ($cy + 2) . "' width='{$cellSize}' height='{$cellSize}' rx='2.5' ry='2.5'/>";
        } else {
            $cellRects[] = "<rect class='c' x='" . ($cx + 2) . "' y='" . ($cy + 2) . "' width='{$cellSize}' height='{$cellSize}' rx='2.5' ry='2.5' fill='#161b22' opacity='0.35'/>";
        }
    }

    // Month labels
    $months = ["Nov", "Dec", "Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov"];
    $monthSvg = [];
    foreach ($months as $i => $m) {
        $col = (int) round($i * (52.0 / 12.0));
        $mx = $gridX0 + $col * $cell;
        $my = $gridY0 - 10;
        $monthSvg[] = "<text x='{$mx}' y='{$my}' fill='#8b949e' font-size='9' font-family='-apple-system,BlinkMacSystemFont,\"Segoe UI\",Helvetica,Arial,sans-serif'>{$m}</text>";
    }

    // Weekday labels
    $weekdaySvg = [
        "<text x='" . ($gridX0 - 6) . "' y='" . ($gridY0 + 1 * $cell + 10) . "' fill='#8b949e' font-size='9' font-family='-apple-system,BlinkMacSystemFont,\"Segoe UI\",Helvetica,Arial,sans-serif' text-anchor='end'>Mon</text>",
        "<text x='" . ($gridX0 - 6) . "' y='" . ($gridY0 + 3 * $cell + 10) . "' fill='#8b949e' font-size='9' font-family='-apple-system,BlinkMacSystemFont,\"Segoe UI\",Helvetica,Arial,sans-serif' text-anchor='end'>Wed</text>",
        "<text x='" . ($gridX0 - 6) . "' y='" . ($gridY0 + 5 * $cell + 10) . "' fill='#8b949e' font-size='9' font-family='-apple-system,BlinkMacSystemFont,\"Segoe UI\",Helvetica,Arial,sans-serif' text-anchor='end'>Fri</text>",
    ];

    // Real King Cobra Head
    $cobraHead = <<<HEAD
    <g class='s s0'>
        <!-- Flared Hood Body (Olive & Deep Forest Scales) -->
        <path d='M 1,2 C 4,-2 9,-2 13,1 C 15,4 17,6 19,8 C 17,10 15,12 13,15 C 9,18 4,18 1,14 C -1,10 -1,6 1,2 Z' fill='#134e4a' stroke='#0f766e' stroke-width='1.2'/>
        
        <!-- King Cobra Dorsal Spectacle Chevron marking -->
        <path d='M 4,5 Q 8,8 4,11 M 7,6 Q 9,8 7,10' fill='none' stroke='#ffd700' stroke-width='1.3' stroke-linecap='round'/>
        <circle cx='8' cy='8' r='1' fill='#ffd700'/>

        <!-- Predatory Snake Eyes with amber iris & black vertical slit pupil -->
        <circle cx='13' cy='5.5' r='1.8' fill='#f59e0b'/>
        <circle cx='13' cy='10.5' r='1.8' fill='#f59e0b'/>
        <line x1='13' y1='4.2' x2='13' y2='6.8' stroke='#000000' stroke-width='1' stroke-linecap='round'/>
        <line x1='13' y1='9.2' x2='13' y2='11.8' stroke='#000000' stroke-width='1' stroke-linecap='round'/>

        <!-- White Venom Fangs -->
        <polygon points='16,6.5 19,6 18,7.5' fill='#ffffff'/>
        <polygon points='16,9.5 19,10 18,8.5' fill='#ffffff'/>

        <!-- Bifurcated Darting Red Tongue -->
        <g class='cobra-tongue'>
            <path d='M 18,8 L 24,8 L 28,5.5 M 24,8 L 28,10.5' fill='none' stroke='#ef4444' stroke-width='1.2' stroke-linecap='round'/>
        </g>
    </g>
HEAD;

    // Tapered muscle segments (7 body segments trailing behind head)
    $segSpecs = [
        [14, 12, 5.0, "#134e4a", "#0f766e", true],   // s1 (muscular neck)
        [14, 12, 5.0, "#115e59", "#134e4a", false],  // s2
        [13, 11, 4.5, "#134e4a", "#0f766e", true],   // s3 (torso band)
        [13, 11, 4.5, "#115e59", "#134e4a", false],  // s4
        [12, 10, 4.0, "#134e4a", "#0f766e", true],   // s5 (mid body)
        [11, 9,  3.5, "#0f766e", "#134e4a", false],  // s6
        [9,  7,  3.0, "#0f766e", "#134e4a", true],   // s7 (tail tip)
    ];

    $cobraBodySegments = [];
    foreach ($segSpecs as $idx => [$w, $h, $rx, $fillCol, $strokeCol, $hasBand]) {
        $segNum = $idx + 1;
        $yOff = (16 - $h) / 2.0;
        $xOff = (16 - $w) / 2.0;
        $bandPath = $hasBand
            ? "<path d='M " . ($xOff + 3) . ",{$yOff} Q " . ($xOff + 7) . "," . ($yOff + $h / 2.0) . " " . ($xOff + 3) . "," . ($yOff + $h) . "' fill='none' stroke='#ffd700' stroke-width='1.3' stroke-linecap='round'/>"
            : "";

        $cobraBodySegments[] = "
        <g class='s s{$segNum}'>
            <rect x='{$xOff}' y='{$yOff}' width='{$w}' height='{$h}' rx='{$rx}' ry='{$rx}' fill='{$fillCol}' stroke='{$strokeCol}' stroke-width='1'/>
            {$bandPath}
        </g>";
    }

    $legendX = $svgWidth - 120;
    $legendY = $svgHeight - 12;

    $sRulesCss = implode("\n    ", $sRules);
    $cellCssBlock = implode("\n", $cellCss);
    $monthSvgBlock = implode("\n        ", $monthSvg);
    $weekdaySvgBlock = implode("\n        ", $weekdaySvg);
    $cellRectsBlock = implode("\n        ", $cellRects);
    $reversedSegments = implode("\n        ", array_reverse($cobraBodySegments));
    $gridW = 53 * $cell;
    $gridH = 7 * $cell;

    return <<<SVG
<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 {$svgWidth} {$svgHeight}' width='100%' height='auto' style='isolation: isolate; background: #0d1117; border-radius: 6px;'>
    <defs>
        <!-- Clip path to keep cobra strictly within the active contribution grid -->
        <clipPath id='grid-boundary-clip'>
            <rect x='0' y='0' width='{$gridW}' height='{$gridH}'/>
        </clipPath>
    </defs>

    <style>
        @keyframes tongueFlutter {
            0%, 100% { transform: scaleX(0.2); opacity: 0; }
            25%, 50% { transform: scaleX(1.3); opacity: 1; }
            35% { transform: scaleX(1.4) translateY(-0.5px); opacity: 1; }
            60% { transform: scaleX(0.1); opacity: 0; }
        }
        .cobra-tongue {
            transform-origin: 18px 8px;
            animation: tongueFlutter 0.45s ease-in-out infinite;
        }
        .c {
            shape-rendering: geometricPrecision;
            stroke-width: 1px;
            stroke: #1b1f230a;
        }
        .s {
            shape-rendering: geometricPrecision;
        }
        {$sRulesCss}
        {$cobraMoveKf}
        {$cellCssBlock}
    </style>

    <!-- Month Labels -->
    <g id='months-layer'>
        {$monthSvgBlock}
    </g>

    <!-- Weekday Labels -->
    <g id='weekdays-layer'>
        {$weekdaySvgBlock}
    </g>

    <!-- Grid Cells (Contribution Boxes) -->
    <g id='grid-cells' transform='translate({$gridX0}, {$gridY0})'>
        {$cellRectsBlock}
    </g>

    <!-- THE REAL KING COBRA (Clipped cleanly to the grid arena) -->
    <g id='king-cobra-arena' transform='translate({$gridX0}, {$gridY0})' clip-path='url(#grid-boundary-clip)'>
        {$reversedSegments}
        {$cobraHead}
    </g>

    <!-- Legend -->
    <g transform='translate({$legendX}, {$legendY})'>
        <text x='-8' y='8' fill='#8b949e' font-size='9' font-family='-apple-system,BlinkMacSystemFont,"Segoe UI",Helvetica,Arial,sans-serif' text-anchor='end'>Less</text>
        <rect x='0' y='0' width='9' height='9' rx='2' ry='2' fill='#161b22'/>
        <rect x='13' y='0' width='9' height='9' rx='2' ry='2' fill='#0e4429'/>
        <rect x='26' y='0' width='9' height='9' rx='2' ry='2' fill='#006d32'/>
        <rect x='39' y='0' width='9' height='9' rx='2' ry='2' fill='#26a641'/>
        <rect x='52' y='0' width='9' height='9' rx='2' ry='2' fill='#39d353'/>
        <text x='68' y='8' fill='#8b949e' font-size='9' font-family='-apple-system,BlinkMacSystemFont,"Segoe UI",Helvetica,Arial,sans-serif'>More</text>
    </g>
</svg>
SVG;
}
