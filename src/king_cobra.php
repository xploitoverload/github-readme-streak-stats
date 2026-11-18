<?php

declare(strict_types=1);

/**
 * Generate a standalone animated SVG of the King Cobra Contribution Predator.
 * Renders the user's authentic contribution calendar matrix without extraneous banners,
 * where a thick, realistic, hooded King Cobra glides in a majestic flowing prowl,
 * devours fancy glowing meals, visibly grows longer and bigger as it feeds,
 * and leaves cleanly emptied contribution cells in its wake.
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

    // Majestic flowing prowl path (continuous natural serpentine sweeps, no frantic up-and-down)
    $waypointsGrid = [
        // Top sweep across cols 0 -> 52 along rows 1.5 - 2.0
        [1.0, 1.8], [8.0, 1.5], [16.0, 2.0], [24.0, 1.6], [32.0, 2.0], [40.0, 1.6], [46.0, 2.0], [50.0, 1.8],
        // Wide majestic turnaround at right edge
        [52.5, 2.8], [53.0, 4.0], [51.0, 5.2],
        // Bottom sweep across cols 50 -> 0 along rows 4.6 - 5.0
        [46.0, 5.0], [38.0, 4.6], [30.0, 5.0], [22.0, 4.6], [14.0, 5.0], [6.0, 4.6], [2.0, 5.0],
        // Wide majestic turnaround at left edge
        [0.0, 4.2], [-0.5, 2.8], [1.0, 1.8],
    ];

    $waypoints = [];
    foreach ($waypointsGrid as $wg) {
        $waypoints[] = [$gridX0 + $wg[0] * $step, $gridY0 + $wg[1] * $step];
    }

    $numWp = count($waypoints) - 1;
    $stepsPerSeg = 6;
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

    // Calculate heading angle and fluid lateral slither wave
    for ($i = 0; $i < $totalFrames; $i++) {
        $curr = $rawPoints[$i];
        $next = $rawPoints[($i + 1) % $totalFrames];
        $dx = $next[0] - $curr[0];
        $dy = $next[1] - $curr[1];
        $angle = rad2deg(atan2($dy, $dx));

        // Lateral slither wave (8 undulation cycles)
        $waveAngle = 2 * M_PI * ($i / (float) $totalFrames) * 8;
        $slitherOffset = sin($waveAngle) * 3.2;

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
        $headSway = cos($waveAngle) * 6.0;
        $totalAngle = $angle + $headSway;

        $trajectory[] = [round($x, 1), round($y, 1), round($totalAngle, 1)];
    }

    // Identify all active meals eaten and emptied along the Cobra's path
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
            if ($dist <= 12.0 && !isset($eatenCells[$mid])) {
                $eatenCells[$mid] = [$pct, $cx, $cy];
            }
        }
    }

    // 14 Vertebrae Segments with Progressive Growth (Getting bigger like flow!)
    $numSegments = 14;
    $growthTimes = [
        7 => 18.0,
        8 => 24.0,
        9 => 38.0,
        10 => 45.0,
        11 => 58.0,
        12 => 66.0,
        13 => 76.0,
        14 => 82.0,
    ];

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

    // 2. Trailing Vertebrae / Body Segments Keyframes with Dynamic Growth
    for ($seg = 1; $seg <= $numSegments; $seg++) {
        $segKf = ["@keyframes cobraSeg{$seg}Move {"];
        $delayFrames = (int) round($seg * 1.3);
        $tGrow = $growthTimes[$seg] ?? 0.0;

        for ($i = 0; $i <= $totalFrames; $i++) {
            $pct = round(($i / (float) $totalFrames) * 100, 2);
            $idx = ($i - $delayFrames + $totalFrames * 10) % $totalFrames;
            [$x, $y, $ang] = $trajectory[$idx];

            if ($tGrow > 0) {
                if ($pct < $tGrow - 1.0) {
                    $segKf[] = "  {$pct}% { transform: translate({$x}px, {$y}px) rotate({$ang}deg) scale(0.01); opacity: 0; }";
                } elseif ($pct < $tGrow) {
                    $scaleVal = round($pct - ($tGrow - 1.0), 2);
                    $segKf[] = "  {$pct}% { transform: translate({$x}px, {$y}px) rotate({$ang}deg) scale({$scaleVal}); opacity: {$scaleVal}; }";
                } elseif ($pct <= 99.4) {
                    $segKf[] = "  {$pct}% { transform: translate({$x}px, {$y}px) rotate({$ang}deg) scale(1); opacity: 1; }";
                } else {
                    $segKf[] = "  {$pct}% { transform: translate({$x}px, {$y}px) rotate({$ang}deg) scale(0.01); opacity: 0; }";
                }
            } else {
                $segKf[] = "  {$pct}% { transform: translate({$x}px, {$y}px) rotate({$ang}deg); opacity: 1; }";
            }
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

    // 14 Muscular Banded Segments (Heavy overlap, thick serpent flow)
    $segRadii = [
        [12.0, 9.5], // 1 (Neck)
        [11.5, 9.0], // 2
        [11.0, 8.5], // 3
        [10.5, 8.2], // 4
        [10.0, 8.0], // 5
        [9.5, 7.8],  // 6
        [9.0, 7.5],  // 7
        [8.5, 7.0],  // 8
        [8.0, 6.5],  // 9
        [7.5, 6.0],  // 10
        [7.0, 5.5],  // 11
        [6.2, 5.0],  // 12
        [5.5, 4.4],  // 13
        [4.5, 3.6],  // 14 (Rattle Tail)
    ];

    $segmentsSvg = [];
    for ($sIdx = $numSegments; $sIdx >= 1; $sIdx--) {
        [$rx, $ry] = $segRadii[$sIdx - 1];
        $isTail = ($sIdx === $numSegments);
        $isBanded = ($sIdx % 2 === 0);

        $bandSvg = "";
        if ($isBanded && !$isTail) {
            $bandX = -$rx * 0.5;
            $bandY = $ry * 0.75;
            $negBandY = -$ry * 0.75;
            $bandSvg = "<path d='M {$bandX}, {$negBandY} Q 0,0 {$bandX}, {$bandY}' fill='none' stroke='#ffd700' stroke-width='2' stroke-linecap='round'/>";
        }

        if ($isTail) {
            $negRx = -$rx;
            $p2X = -$rx - 7;
            $p3X = -$rx - 10;
            $segmentsSvg[] = "<g id='cobra-seg-{$sIdx}' class='cobra-seg-{$sIdx}'>
                <ellipse cx='0' cy='0' rx='{$rx}' ry='{$ry}' fill='url(#segGrad)' stroke='#ffd700' stroke-width='0.9'/>
                <polygon points='{$negRx},0 {$p2X},-2.5 {$p3X},0 {$p2X},2.5' fill='#ffd700' stroke='#ff3344' stroke-width='0.6'/>
            </g>";
        } else {
            $spineX1 = -$rx * 0.6;
            $spineX2 = $rx * 0.6;
            $segmentsSvg[] = "<g id='cobra-seg-{$sIdx}' class='cobra-seg-{$sIdx}'>
                <ellipse cx='0' cy='0' rx='{$rx}' ry='{$ry}' fill='url(#segGrad)' stroke='#ff3344' stroke-width='0.9'/>
                {$bandSvg}
                <line x1='{$spineX1}' y1='0' x2='{$spineX2}' y2='0' stroke='#00ff88' stroke-width='1.4' stroke-linecap='round'/>
            </g>";
        }
    }

    $segCssRules = [];
    for ($s = 1; $s <= $numSegments; $s++) {
        $segCssRules[] = ".cobra-seg-{$s} { animation: cobraSeg{$s}Move 20s linear infinite; }";
    }

    $allCss = implode("\n", $cssParts);
    $segRules = implode("\n", $segCssRules);
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
            transform-origin: 26px 0px;
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
        {$segRules}

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
        <!-- Body Segments (rendered back to front for scale layering) -->
        {$segmentsRendered}
        
        <!-- Authentic Flared King Cobra Hood & Head (34px wide) -->
        <g id='cobra-head' class='cobra-head'>
            <path d='M -16,-6 C -14,-15 -6,-18 5,-12 C 13,-8 22,-4 26,0 C 22,4 13,8 5,12 C -6,18 -14,15 -16,6 Z' fill='url(#hoodGrad)' stroke='#ff3344' stroke-width='1.5' stroke-linejoin='round'/>
            
            <!-- Iconic Chevron Spectacle Hood Emblem -->
            <path d='M -8,-7 Q -2,0 -8,7 M -3,-5 Q 3,0 -3,5' fill='none' stroke='#ffd700' stroke-width='2' stroke-linecap='round'/>
            <circle cx='0' cy='0' r='1.8' fill='#ffd700'/>

            <!-- Snout & Crown Ridge -->
            <path d='M 6,-9 L 22,-4 L 27,0 L 22,4 L 6,9' fill='none' stroke='#00ff88' stroke-width='1.2'/>

            <!-- Glowing Ruby Predatory Eyes with Slit Pupils -->
            <circle cx='14' cy='-5.5' r='2.5' fill='#ff0044' filter='url(#eyeGlow)'/>
            <circle cx='14' cy='5.5' r='2.5' fill='#ff0044' filter='url(#eyeGlow)'/>
            <line x1='14' y1='-7' x2='14' y2='-4' stroke='#000000' stroke-width='1.2' stroke-linecap='round'/>
            <line x1='14' y1='4' x2='14' y2='7' stroke='#000000' stroke-width='1.2' stroke-linecap='round'/>

            <!-- Dual Razor Fangs -->
            <polygon points='22,-3 27,-4 24,-1.5' fill='#ffffff'/>
            <polygon points='22,3 27,4 24,1.5' fill='#ffffff'/>

            <!-- Darting Bifurcated Venom Tongue -->
            <g class='cobra-tongue'>
                <path d='M 26,0 L 36,0 L 42,-4 M 36,0 L 42,4' fill='none' stroke='#ff0044' stroke-width='1.6' stroke-linecap='round'/>
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
