<?php

declare(strict_types=1);

/**
 * Generate a standalone animated SVG of the King Cobra Contribution Predator.
 * Renders the user's authentic contribution calendar matrix without extraneous banners,
 * where an articulated hooded King Cobra actively hunts, eats, and empties fancy glowing meals
 * across the grid, leaving clean empty cells in its wake.
 *
 * @param array<int,array{date:string,count:int,level:int}> $days Array of daily contributions
 * @param array<string,mixed> $params Options
 * @return string Full SVG XML string
 */
function renderKingCobra(array $days, array $params = []): string
{
    $cellWidth = 11;
    $cellGap = 4;
    $step = $cellWidth + $cellGap; // 15px
    $gridX0 = 36;
    $gridY0 = 26;

    $svgWidth = 875;
    $svgHeight = 160;

    // Catmull-Rom spline interpolation
    $catmullRom = function (array $p0, array $p1, array $p2, array $p3, float $t): array {
        $t2 = $t * $t;
        $t3 = $t2 * $t;
        $x = 0.5 * ((2 * $p1[0]) +
                   (-$p0[0] + $p2[0]) * $t +
                   (2 * $p0[0] - 5 * $p1[0] + 4 * $p2[0] - $p3[0]) * $t2 +
                   (-$p0[0] + 3 * $p1[0] - 3 * $p2[0] + $p3[0]) * $t3);
        $y = 0.5 * ((2 * $p1[1]) +
                   (-$p0[1] + $p2[1]) * $t +
                   (2 * $p0[1] - 5 * $p1[1] + 4 * $p2[1] - $p3[1]) * $t2 +
                   (-$p0[1] + 3 * $p1[1] - 3 * $p2[1] + $p3[1]) * $t3);
        return [$x, $y];
    };

    // Full serpentine hunting sweep waypoints across the grid:
    // Forward sweep left-to-right (upper/mid rows), turnaround, backward sweep right-to-left (lower rows)
    $waypointsGrid = [
        [0, 1], [4, 3], [8, 1], [12, 0], [16, 2], [20, 3],
        [24, 1], [28, 0], [32, 2], [36, 3], [40, 1], [44, 0],
        [48, 2], [52, 2],
        [52, 4], [51, 6],
        [48, 5], [44, 6], [40, 4], [36, 6], [32, 5], [28, 4],
        [24, 6], [20, 5], [16, 4], [12, 6], [8, 5], [4, 4], [0, 6],
        [0, 4], [0, 1]
    ];

    $waypoints = [];
    foreach ($waypointsGrid as $wg) {
        $waypoints[] = [$gridX0 + $wg[0] * $step, $gridY0 + $wg[1] * $step];
    }

    $numWp = count($waypoints) - 1;
    $stepsPerSeg = 5;
    $rawPoints = [];

    for ($i = 0; $i < $numWp; $i++) {
        $p0 = $waypoints[($i - 1 + $numWp) % $numWp];
        $p1 = $waypoints[$i];
        $p2 = $waypoints[($i + 1) % $numWp];
        $p3 = $waypoints[($i + 2) % $numWp];
        for ($s = 0; $s < $stepsPerSeg; $s++) {
            $t = $s / (float) $stepsPerSeg;
            $rawPoints[] = $catmullRom($p0, $p1, $p2, $p3, $t);
        }
    }

    $totalFrames = count($rawPoints);
    $trajectory = [];

    // Calculate heading angle and sinuous lateral slither wave
    for ($i = 0; $i < $totalFrames; $i++) {
        $curr = $rawPoints[$i];
        $next = $rawPoints[($i + 1) % $totalFrames];
        $dx = $next[0] - $curr[0];
        $dy = $next[1] - $curr[1];
        $angle = rad2deg(atan2($dy, $dx));

        $waveAngle = 2 * M_PI * ($i / (float) $totalFrames) * 10;
        $slitherOffset = sin($waveAngle) * 3.0;

        $dist = hypot($dx, $dy);
        if ($dist > 0.001) {
            $perpX = (-$dy / $dist) * $slitherOffset;
            $perpY = ($dx / $dist) * $slitherOffset;
        } else {
            $perpX = 0.0;
            $perpY = 0.0;
        }

        $x = $curr[0] + $perpX;
        $y = $curr[1] + $perpY;
        $headSway = cos($waveAngle) * 7.0;
        $totalAngle = $angle + $headSway;

        $trajectory[] = [round($x, 1), round($y, 1), round($totalAngle, 1)];
    }

    // Identify all active meals eaten and emptied along the Cobra's hunting path
    $eatenCells = [];
    foreach ($trajectory as $frameIdx => $tPos) {
        $pct = round(($frameIdx / (float) $totalFrames) * 100, 2);
        [$px, $py] = [$tPos[0], $tPos[1]];

        foreach ($days as $i => $d) {
            $c = (int) ($i / 7);
            $r = $i % 7;
            if ($c > 52 || ($d["level"] ?? 0) === 0) {
                continue;
            }
            $cx = $gridX0 + $c * $step + 5.5;
            $cy = $gridY0 + $r * $step + 5.5;
            $dist = hypot($px - $cx, $py - $cy);
            $mid = "meal_{$c}_{$r}";
            if ($dist <= 11.5 && !isset($eatenCells[$mid])) {
                $eatenCells[$mid] = [$pct, $cx, $cy];
            }
        }
    }

    // CSS Keyframe Generation
    $numSegments = 8;
    $cssParts = [];

    // 1. King Cobra Head Motion Keyframes
    $headKf = ["@keyframes cobraHeadMove {"];
    for ($i = 0; $i <= $totalFrames; $i++) {
        $pct = round(($i / (float) $totalFrames) * 100, 2);
        $idx = $i % $totalFrames;
        [$x, $y, $ang] = $trajectory[$idx];
        $headKf[] = "  {$pct}% { transform: translate({$x}px, {y}px) rotate({$ang}deg); }";
    }
    $headKf[] = "}";
    $cssParts[] = implode("\n", $headKf);

    // 2. Trailing Vertebrae / Body Segments Keyframes
    for ($seg = 1; $seg <= $numSegments; $seg++) {
        $segKf = ["@keyframes cobraSeg{$seg}Move {"];
        $delayFrames = $seg * 2;
        for ($i = 0; $i <= $totalFrames; $i++) {
            $pct = round(($i / (float) $totalFrames) * 100, 2);
            $idx = ($i - $delayFrames + $totalFrames * 10) % $totalFrames;
            [$x, $y, $ang] = $trajectory[$idx];
            $segKf[] = "  {$pct}% { transform: translate({$x}px, {y}px) rotate({$ang}deg); }";
        }
        $segKf[] = "}";
        $cssParts[] = implode("\n", $segKf);
    }

    // 3. Devour & Emptying Keyframes for Eaten Meals
    foreach ($eatenCells as $mid => $info) {
        [$t0, $cx, $cy] = $info;
        $tBurst = round(min(100.0, $t0 + 0.3), 2);
        $tEmpty = round(min(100.0, $t0 + 0.7), 2);
        $cssParts[] = <<<CSS
        @keyframes eat_{$mid} {
            0%, {$t0}% { opacity: 1; transform: scale(1); }
            {$tBurst}% { opacity: 1; transform: scale(1.5); filter: brightness(2.5) drop-shadow(0 0 5px #ff0055); }
            {$tEmpty}%, 99.4% { opacity: 0; transform: scale(0); }
            100% { opacity: 1; transform: scale(1); }
        }
        #{$mid} {
            animation: eat_{$mid} 20s ease-in-out infinite;
            transform-origin: {$cx}px {$cy}px;
        }
CSS;
    }

    // Month Labels
    $months = ["Nov", "Dec", "Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov"];
    $monthSvg = [];
    foreach ($months as $i => $m) {
        $col = (int) round($i * (52.0 / 12.0));
        $mx = $gridX0 + $col * $step;
        $monthSvg[] = "<text x='{$mx}' y='16' fill='#8b949e' font-size='9' font-family='-apple-system,BlinkMacSystemFont,\"Segoe UI\",Helvetica,Arial,sans-serif'>{$m}</text>";
    }

    // Weekday Labels
    $wMonY = $gridY0 + 1 * $step + 9;
    $wWedY = $gridY0 + 3 * $step + 9;
    $wFriY = $gridY0 + 5 * $step + 9;
    $wLabelX = $gridX0 - 6;
    $weekdaySvg = [
        "<text x='{$wLabelX}' y='{$wMonY}' fill='#8b949e' font-size='9' font-family='-apple-system,BlinkMacSystemFont,\"Segoe UI\",Helvetica,Arial,sans-serif' text-anchor='end'>Mon</text>",
        "<text x='{$wLabelX}' y='{$wWedY}' fill='#8b949e' font-size='9' font-family='-apple-system,BlinkMacSystemFont,\"Segoe UI\",Helvetica,Arial,sans-serif' text-anchor='end'>Wed</text>",
        "<text x='{$wLabelX}' y='{$wFriY}' fill='#8b949e' font-size='9' font-family='-apple-system,BlinkMacSystemFont,\"Segoe UI\",Helvetica,Arial,sans-serif' text-anchor='end'>Fri</text>",
    ];

    // Grid Cells & Meals (with base empty tile underneath so emptied cells reveal the empty base)
    $cellsSvg = [];
    foreach ($days as $i => $d) {
        $col = (int) ($i / 7);
        $row = $i % 7;
        if ($col > 52) {
            break;
        }
        $cx = $gridX0 + $col * $step;
        $cy = $gridY0 + $row * $step;
        $lvl = $d["level"] ?? 0;
        $mid = "meal_{$col}_{$row}";

        // Always render base background cell
        $cellsSvg[] = "<rect x='{$cx}' y='{$cy}' width='{$cellWidth}' height='{$cellWidth}' rx='2.5' fill='#161b22' stroke='#21262d' stroke-width='0.6'/>";

        $cxMid = $cx + 5.5;
        $cyMid = $cy + 5.5;

        if ($lvl === 1) {
            $cellsSvg[] = "<g id='{$mid}'>
                <rect x='{$cx}' y='{$cy}' width='{$cellWidth}' height='{$cellWidth}' rx='2.5' fill='#0e4429' stroke='#00ff88' stroke-width='0.6'/>
                <circle cx='{$cxMid}' cy='{$cyMid}' r='2.5' fill='#00ff88' class='orb-pulse'/>
            </g>";
        } elseif ($lvl === 2) {
            $cellsSvg[] = "<g id='{$mid}'>
                <rect x='{$cx}' y='{$cy}' width='{$cellWidth}' height='{$cellWidth}' rx='2.5' fill='#003820' stroke='#00f5ff' stroke-width='0.8'/>
                <circle cx='{$cxMid}' cy='{$cyMid}' r='3' fill='#00f5ff' class='orb-pulse-cyan'/>
                <circle cx='{$cxMid}' cy='{$cyMid}' r='4.5' fill='none' stroke='#00f5ff' stroke-width='0.5' stroke-dasharray='1.5,1.5'/>
            </g>";
        } elseif ($lvl === 3) {
            $cellsSvg[] = "<g id='{$mid}'>
                <rect x='{$cx}' y='{$cy}' width='{$cellWidth}' height='{$cellWidth}' rx='2.5' fill='#1f1a3a' stroke='#c084fc' stroke-width='0.8'/>
                <circle cx='{$cxMid}' cy='{$cyMid}' r='3.2' fill='#c084fc' class='orb-pulse-purple'/>
                <circle cx='{$cxMid}' cy='{$cyMid}' r='4.8' fill='none' stroke='#f43f5e' stroke-width='0.6'/>
            </g>";
        } elseif ($lvl === 4) {
            $cellsSvg[] = "<g id='{$mid}'>
                <rect x='{$cx}' y='{$cy}' width='{$cellWidth}' height='{$cellWidth}' rx='2.5' fill='#2d1500' stroke='#ffd700' stroke-width='1'/>
                <circle cx='{$cxMid}' cy='{$cyMid}' r='3.8' fill='#ffd700' class='orb-pulse-gold'/>
                <circle cx='{$cxMid}' cy='{$cyMid}' r='5.2' fill='none' stroke='#ff3344' stroke-width='0.8' stroke-dasharray='2,1.5'/>
            </g>";
        }
    }

    // Segments Art
    $segRadii = [
        [6.0, 5.0],
        [5.8, 4.8],
        [5.5, 4.5],
        [5.0, 4.2],
        [4.6, 3.8],
        [4.0, 3.4],
        [3.5, 3.0],
        [2.8, 2.4],
    ];

    $segmentsSvg = [];
    for ($sIdx = $numSegments; $sIdx >= 1; $sIdx--) {
        [$rx, $ry] = $segRadii[$sIdx - 1];
        $isTail = ($sIdx === $numSegments);

        if ($isTail) {
            $negRx = -$rx;
            $p2X = -$rx - 6;
            $p3X = -$rx - 8;
            $segmentsSvg[] = "<g id='cobra-seg-{$sIdx}' class='cobra-seg-{$sIdx}'>
                <ellipse cx='0' cy='0' rx='{$rx}' ry='{$ry}' fill='url(#segGrad)' stroke='#ffaa00' stroke-width='0.8'/>
                <polygon points='{$negRx},0 {$p2X},-2 {$p3X},0 {$p2X},2' fill='#ffd700' stroke='#ff3344' stroke-width='0.5'/>
            </g>";
        } else {
            $spineX1 = -$rx * 0.7;
            $spineX2 = $rx * 0.7;
            $circleCy = -$ry * 0.4;
            $segmentsSvg[] = "<g id='cobra-seg-{$sIdx}' class='cobra-seg-{$sIdx}'>
                <ellipse cx='0' cy='0' rx='{$rx}' ry='{$ry}' fill='url(#segGrad)' stroke='#ff3344' stroke-width='0.8'/>
                <line x1='{$spineX1}' y1='0' x2='{$spineX2}' y2='0' stroke='#00ff88' stroke-width='1.2' stroke-linecap='round'/>
                <circle cx='0' cy='{$circleCy}' r='1' fill='#ffffff' opacity='0.4'/>
            </g>";
        }
    }

    $allCss = implode("\n", $cssParts);
    $monthsRendered = implode("", $monthSvg);
    $weekdaysRendered = implode("", $weekdaySvg);
    $cellsRendered = implode("", $cellsSvg);
    $segmentsRendered = implode("", $segmentsSvg);

    $legendX = $svgWidth - 120;
    $legendY = $svgHeight - 12;

    return <<<SVG
<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 {$svgWidth} {$svgHeight}' width='100%' height='auto' style='isolation: isolate; background: #0d1117; border-radius: 6px;'>
    <defs>
        <!-- Eye Glow Filter -->
        <filter id='eyeGlow' x='-50%' y='-50%' width='200%' height='200%'>
            <feGaussianBlur stdDeviation='2' result='blur'/>
            <feMerge>
                <feMergeNode in='blur'/>
                <feMergeNode in='SourceGraphic'/>
            </feMerge>
        </filter>
        
        <!-- Hood Metallic Gradient -->
        <linearGradient id='hoodGrad' x1='0%' y1='0%' x2='100%' y2='100%'>
            <stop offset='0%' stop-color='#1f2937'/>
            <stop offset='50%' stop-color='#0f172a'/>
            <stop offset='100%' stop-color='#020617'/>
        </linearGradient>

        <!-- Body Segments Gradient -->
        <linearGradient id='segGrad' x1='0%' y1='0%' x2='100%' y2='100%'>
            <stop offset='0%' stop-color='#1e293b'/>
            <stop offset='50%' stop-color='#0f172a'/>
            <stop offset='100%' stop-color='#000000'/>
        </linearGradient>
    </defs>

    <style>
        @keyframes tongueFlutter {
            0%, 100% { transform: scaleX(0.2); opacity: 0; }
            20%, 40% { transform: scaleX(1.3) scaleY(1.1); opacity: 1; }
            30% { transform: scaleX(1.4) translateY(-1px); opacity: 1; }
            50% { transform: scaleX(0.1); opacity: 0; }
        }
        .cobra-tongue {
            transform-origin: 18px 0px;
            animation: tongueFlutter 0.75s ease-in-out infinite;
        }
        @keyframes orbBreathe {
            0%, 100% { transform: scale(1); filter: drop-shadow(0 0 2px #00ff88); }
            50% { transform: scale(1.15); filter: drop-shadow(0 0 5px #00ff88); }
        }
        .orb-pulse {
            transform-origin: center;
            animation: orbBreathe 2s ease-in-out infinite;
        }
        @keyframes orbBreatheCyan {
            0%, 100% { transform: scale(1); filter: drop-shadow(0 0 2px #00f5ff); }
            50% { transform: scale(1.2); filter: drop-shadow(0 0 6px #00f5ff); }
        }
        .orb-pulse-cyan {
            transform-origin: center;
            animation: orbBreatheCyan 1.8s ease-in-out infinite 0.3s;
        }
        @keyframes orbBreathePurple {
            0%, 100% { transform: scale(1); filter: drop-shadow(0 0 2px #c084fc); }
            50% { transform: scale(1.25); filter: drop-shadow(0 0 7px #c084fc); }
        }
        .orb-pulse-purple {
            transform-origin: center;
            animation: orbBreathePurple 1.6s ease-in-out infinite 0.6s;
        }
        @keyframes orbBreatheGold {
            0%, 100% { transform: scale(1) rotate(0deg); filter: drop-shadow(0 0 3px #ffd700); }
            50% { transform: scale(1.3) rotate(180deg); filter: drop-shadow(0 0 8px #ffd700); }
        }
        .orb-pulse-gold {
            transform-origin: center;
            animation: orbBreatheGold 1.4s ease-in-out infinite;
        }
        .cobra-head {
            animation: cobraHeadMove 20s linear infinite;
        }
        .cobra-seg-1 { animation: cobraSeg1Move 20s linear infinite; }
        .cobra-seg-2 { animation: cobraSeg2Move 20s linear infinite; }
        .cobra-seg-3 { animation: cobraSeg3Move 20s linear infinite; }
        .cobra-seg-4 { animation: cobraSeg4Move 20s linear infinite; }
        .cobra-seg-5 { animation: cobraSeg5Move 20s linear infinite; }
        .cobra-seg-6 { animation: cobraSeg6Move 20s linear infinite; }
        .cobra-seg-7 { animation: cobraSeg7Move 20s linear infinite; }
        .cobra-seg-8 { animation: cobraSeg8Move 20s linear infinite; }

        {$allCss}
    </style>

    <!-- Month Labels -->
    <g id='months-layer'>
        {$monthsRendered}
    </g>

    <!-- Weekday Labels -->
    <g id='weekdays-layer'>
        {$weekdaysRendered}
    </g>

    <!-- Contribution Grid & Fancy Meals -->
    <g id='grid-cells'>
        {$cellsRendered}
    </g>

    <!-- ================= THE LIVING KING COBRA ================= -->
    <g id='king-cobra-group'>
        <!-- Body Segments (rendered back to front) -->
        {$segmentsRendered}
        
        <!-- Hooded Head with Glowing Eyes & Fangs -->
        <g id='cobra-head' class='cobra-head'>
            <!-- Flared Lateral Cobra Hood -->
            <path d='M -14,-3 C -12,-11 -5,-14 4,-10 C 9,-7 15,-4 18,0 C 15,4 9,7 4,10 C -5,14 -12,11 -14,3 Z' fill='url(#hoodGrad)' stroke='#ff3344' stroke-width='1.4' stroke-linejoin='round'/>
            
            <!-- Iconic Cobra Hood Chevron Spectacle Mark -->
            <path d='M -6,-5 Q -2,0 -6,5 M -2,-3 Q 2,0 -2,3' fill='none' stroke='#ffd700' stroke-width='1.5' stroke-linecap='round'/>
            
            <!-- Snout & Jaw Ridge -->
            <path d='M 4,-7 L 16,-3 L 19,0 L 16,3 L 4,7' fill='none' stroke='#00ff88' stroke-width='0.9'/>
            
            <!-- Glowing Ruby Predatory Eyes -->
            <circle cx='10' cy='-4.5' r='2.2' fill='#ff0044' filter='url(#eyeGlow)'/>
            <circle cx='10' cy='4.5' r='2.2' fill='#ff0044' filter='url(#eyeGlow)'/>
            <!-- Slit Pupils -->
            <line x1='10' y1='-5.8' x2='10' y2='-3.2' stroke='#110000' stroke-width='0.9' stroke-linecap='round'/>
            <line x1='10' y1='3.2' x2='10' y2='5.8' stroke='#110000' stroke-width='0.9' stroke-linecap='round'/>
            
            <!-- Dual Razor Fangs -->
            <polygon points='16,-2.5 20,-3 17.5,-1.2' fill='#ffffff'/>
            <polygon points='16,2.5 20,3 17.5,1.2' fill='#ffffff'/>
            
            <!-- Active Darting Bifurcated Venom Tongue -->
            <g class='cobra-tongue'>
                <path d='M 18,0 L 26,0 L 30,-3.5 M 26,0 L 30,3.5' fill='none' stroke='#ff0044' stroke-width='1.3' stroke-linecap='round'/>
            </g>
        </g>
    </g>

    <!-- ================= FOOTER LEGEND ================= -->
    <g transform='translate({$legendX}, {$legendY})'>
        <text x='-8' y='8' fill='#8b949e' font-size='9' font-family='-apple-system,BlinkMacSystemFont,"Segoe UI",Helvetica,Arial,sans-serif' text-anchor='end'>Less</text>
        <rect x='0' y='0' width='9' height='9' rx='2' fill='#161b22' stroke='#21262d' stroke-width='0.5'/>
        <g transform='translate(13, 0)'>
            <rect x='0' y='0' width='9' height='9' rx='2' fill='#0e4429' stroke='#00ff88' stroke-width='0.5'/>
            <circle cx='4.5' cy='4.5' r='1.8' fill='#00ff88'/>
        </g>
        <g transform='translate(26, 0)'>
            <rect x='0' y='0' width='9' height='9' rx='2' fill='#003820' stroke='#00f5ff' stroke-width='0.5'/>
            <circle cx='4.5' cy='4.5' r='2.2' fill='#00f5ff'/>
        </g>
        <g transform='translate(39, 0)'>
            <rect x='0' y='0' width='9' height='9' rx='2' fill='#1f1a3a' stroke='#c084fc' stroke-width='0.5'/>
            <circle cx='4.5' cy='4.5' r='2.5' fill='#c084fc'/>
        </g>
        <g transform='translate(52, 0)'>
            <rect x='0' y='0' width='9' height='9' rx='2' fill='#2d1500' stroke='#ffd700' stroke-width='0.5'/>
            <circle cx='4.5' cy='4.5' r='2.8' fill='#ffd700'/>
        </g>
        <text x='68' y='8' fill='#8b949e' font-size='9' font-family='-apple-system,BlinkMacSystemFont,"Segoe UI",Helvetica,Arial,sans-serif'>More</text>
    </g>
</svg>
SVG;
}
