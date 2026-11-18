<?php

declare(strict_types=1);

/**
 * Generate a standalone animated SVG of the King Cobra Strike Protocol.
 * Renders the user's authentic contribution calendar matrix where an articulated,
 * hooded King Cobra with glowing ruby eyes and darting forked tongue patrols the grid,
 * striking and devouring multi-tiered glowing energy meals (cyber venom pearls).
 *
 * @param array<int,array{date:string,count:int,level:int}> $days Array of daily contributions
 * @param array<string,mixed> $params Options (user, title, streak, etc.)
 * @return string Full SVG XML string
 */
function renderKingCobra(array $days, array $params = []): string
{
    $username = $params["user"] ?? "xploitoverload";
    $customTitle = $params["title"] ?? "KING COBRA // STRIKE PROTOCOL";
    $streakDays = $params["streak"] ?? 40;
    $totalCommits = $params["commits"] ?? 1723;

    $cellWidth = 11;
    $cellGap = 4;
    $step = $cellWidth + $cellGap; // 15px
    $gridX0 = 48;
    $gridY0 = 74;

    $svgWidth = 910;
    $svgHeight = 225;

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

    // Strategic hunting waypoints visiting major active contribution clusters
    $waypoints = [
        [$gridX0 + 2 * $step, $gridY0 + 1 * $step],
        [$gridX0 + 7 * $step, $gridY0 + 4 * $step],
        [$gridX0 + 12 * $step, $gridY0 + 0 * $step],
        [$gridX0 + 18 * $step, $gridY0 + 5 * $step],
        [$gridX0 + 24 * $step, $gridY0 + 2 * $step],
        [$gridX0 + 30 * $step, $gridY0 + 1 * $step],
        [$gridX0 + 36 * $step, $gridY0 + 5 * $step],
        [$gridX0 + 42 * $step, $gridY0 + 2 * $step],
        [$gridX0 + 47 * $step, $gridY0 + 0 * $step],
        [$gridX0 + 51 * $step, $gridY0 + 3 * $step],
        [$gridX0 + 51 * $step, $gridY0 + 6 * $step],
        [$gridX0 + 45 * $step, $gridY0 + 6 * $step],
        [$gridX0 + 38 * $step, $gridY0 + 3 * $step],
        [$gridX0 + 31 * $step, $gridY0 + 6 * $step],
        [$gridX0 + 22 * $step, $gridY0 + 4 * $step],
        [$gridX0 + 15 * $step, $gridY0 + 6 * $step],
        [$gridX0 + 8 * $step, $gridY0 + 3 * $step],
    ];

    $numWp = count($waypoints);
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

        // Lateral slither wave (9 undulation cycles along loop)
        $waveAngle = 2 * M_PI * ($i / (float) $totalFrames) * 9;
        $slitherOffset = sin($waveAngle) * 3.5;

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
        $headSway = cos($waveAngle) * 8.0;
        $totalAngle = $angle + $headSway;

        $trajectory[] = [round($x, 1), round($y, 1), round($totalAngle, 1)];
    }

    // Identify devoured meals at waypoint strikes
    $devourDict = [];
    for ($i = 0; $i < $numWp; $i++) {
        $frameIdx = $i * $stepsPerSeg;
        $pct = round(($frameIdx / (float) $totalFrames) * 100, 1);
        $wpX = $waypoints[$i][0];
        $wpY = $waypoints[$i][1];
        $col = (int) round(($wpX - $gridX0) / (float) $step);
        $row = (int) round(($wpY - $gridY0) / (float) $step);
        $mid = "meal_{$col}_{$row}";
        $devourDict[$mid] = ["pct" => $pct, "x" => $wpX, "y" => $wpY];
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
        $headKf[] = "  {$pct}% { transform: translate({$x}px, {$y}px) rotate({$ang}deg); }";
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
            $segKf[] = "  {$pct}% { transform: translate({$x}px, {$y}px) rotate({$ang}deg); }";
        }
        $segKf[] = "}";
        $cssParts[] = implode("\n", $segKf);
    }

    // 3. Devour Strike Keyframes for Targeted Meals
    foreach ($devourDict as $mid => $dinfo) {
        $t0 = $dinfo["pct"];
        $tBurst = round(min(100.0, $t0 + 0.6), 2);
        $tDone = round(min(100.0, $t0 + 1.2), 2);
        $originX = $dinfo["x"] + 5.5;
        $originY = $dinfo["y"] + 5.5;
        $cssParts[] = <<<CSS
        @keyframes eat_{$mid} {
            0%, {$t0}% { opacity: 1; transform: scale(1); }
            {$tBurst}% { opacity: 1; transform: scale(1.7); filter: brightness(3) drop-shadow(0 0 6px #ff0055); }
            {$tDone}%, 99.9% { opacity: 0.15; transform: scale(0.5); }
            100% { opacity: 1; transform: scale(1); }
        }
        #{$mid} {
            animation: eat_{$mid} 18s ease-in-out infinite;
            transform-origin: {$originX}px {$originY}px;
        }
CSS;
    }

    // Month Labels
    $months = ["Nov", "Dec", "Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov"];
    $monthSvg = [];
    foreach ($months as $i => $m) {
        $col = (int) round($i * (52.0 / 12.0));
        $mx = $gridX0 + $col * $step;
        $monthSvg[] = "<text x='{$mx}' y='62' fill='#8b949e' font-size='10' font-family='-apple-system,BlinkMacSystemFont,\"Segoe UI\",Helvetica,Arial,sans-serif'>{$m}</text>";
    }

    // Weekday Labels
    $wMonY = $gridY0 + 1 * $step + 9;
    $wWedY = $gridY0 + 3 * $step + 9;
    $wFriY = $gridY0 + 5 * $step + 9;
    $wLabelX = $gridX0 - 8;
    $weekdaySvg = [
        "<text x='{$wLabelX}' y='{$wMonY}' fill='#8b949e' font-size='9' font-family='-apple-system,BlinkMacSystemFont,\"Segoe UI\",Helvetica,Arial,sans-serif' text-anchor='end'>Mon</text>",
        "<text x='{$wLabelX}' y='{$wWedY}' fill='#8b949e' font-size='9' font-family='-apple-system,BlinkMacSystemFont,\"Segoe UI\",Helvetica,Arial,sans-serif' text-anchor='end'>Wed</text>",
        "<text x='{$wLabelX}' y='{$wFriY}' fill='#8b949e' font-size='9' font-family='-apple-system,BlinkMacSystemFont,\"Segoe UI\",Helvetica,Arial,sans-serif' text-anchor='end'>Fri</text>",
    ];

    // Grid Cells & Fancy Meals
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

        if ($lvl === 0) {
            $cellsSvg[] = "<rect x='{$cx}' y='{$cy}' width='{$cellWidth}' height='{$cellWidth}' rx='2.5' fill='#161b22' stroke='#21262d' stroke-width='0.6'/>";
        } elseif ($lvl === 1) {
            // Emerald Plasma Pellet
            $cxMid = $cx + 5.5;
            $cyMid = $cy + 5.5;
            $cellsSvg[] = "<g id='{$mid}'>
                <rect x='{$cx}' y='{$cy}' width='{$cellWidth}' height='{$cellWidth}' rx='2.5' fill='#0e4429' stroke='#00ff88' stroke-width='0.6'/>
                <circle cx='{$cxMid}' cy='{$cyMid}' r='2.5' fill='#00ff88' class='orb-pulse'/>
            </g>";
        } elseif ($lvl === 2) {
            // Cyan Hyper-Core
            $cxMid = $cx + 5.5;
            $cyMid = $cy + 5.5;
            $cellsSvg[] = "<g id='{$mid}'>
                <rect x='{$cx}' y='{$cy}' width='{$cellWidth}' height='{$cellWidth}' rx='2.5' fill='#003820' stroke='#00f5ff' stroke-width='0.8'/>
                <circle cx='{$cxMid}' cy='{$cyMid}' r='3' fill='#00f5ff' class='orb-pulse-cyan'/>
                <circle cx='{$cxMid}' cy='{$cyMid}' r='4.5' fill='none' stroke='#00f5ff' stroke-width='0.5' stroke-dasharray='1.5,1.5'/>
            </g>";
        } elseif ($lvl === 3) {
            // Amethyst Hyper-Sphere
            $cxMid = $cx + 5.5;
            $cyMid = $cy + 5.5;
            $cellsSvg[] = "<g id='{$mid}'>
                <rect x='{$cx}' y='{$cy}' width='{$cellWidth}' height='{$cellWidth}' rx='2.5' fill='#1f1a3a' stroke='#c084fc' stroke-width='0.8'/>
                <circle cx='{$cxMid}' cy='{$cyMid}' r='3.2' fill='#c084fc' class='orb-pulse-purple'/>
                <circle cx='{$cxMid}' cy='{$cyMid}' r='4.8' fill='none' stroke='#f43f5e' stroke-width='0.6'/>
            </g>";
        } else {
            // Solar Dragon Pearl
            $cxMid = $cx + 5.5;
            $cyMid = $cy + 5.5;
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

    $legendX = $svgWidth - 230;
    $legendY = $svgHeight - 16;
    $badgeX = $svgWidth - 25;

    return <<<SVG
<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 {$svgWidth} {$svgHeight}' width='100%' height='auto' style='isolation: isolate; background: #080c10; border-radius: 12px;'>
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

        <!-- HUD Border Gradient -->
        <linearGradient id='hudBorder' x1='0%' y1='0%' x2='100%' y2='0%'>
            <stop offset='0%' stop-color='#ff3344'/>
            <stop offset='50%' stop-color='#ffd700'/>
            <stop offset='100%' stop-color='#00ff88'/>
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
        @keyframes radarPulse {
            0% { opacity: 1; r: 3; }
            50% { opacity: 0.4; r: 6; }
            100% { opacity: 0; r: 9; }
        }
        .radar-ring {
            animation: radarPulse 1.8s ease-out infinite;
        }
        .cobra-head {
            animation: cobraHeadMove 18s linear infinite;
        }
        .cobra-seg-1 { animation: cobraSeg1Move 18s linear infinite; }
        .cobra-seg-2 { animation: cobraSeg2Move 18s linear infinite; }
        .cobra-seg-3 { animation: cobraSeg3Move 18s linear infinite; }
        .cobra-seg-4 { animation: cobraSeg4Move 18s linear infinite; }
        .cobra-seg-5 { animation: cobraSeg5Move 18s linear infinite; }
        .cobra-seg-6 { animation: cobraSeg6Move 18s linear infinite; }
        .cobra-seg-7 { animation: cobraSeg7Move 18s linear infinite; }
        .cobra-seg-8 { animation: cobraSeg8Move 18s linear infinite; }

        {$allCss}
    </style>

    <!-- Background Frame -->
    <rect x='0.5' y='0.5' width='909' height='224' rx='12' fill='#080c10' stroke='#1f2937' stroke-width='1'/>
    <rect x='1' y='1' width='908' height='3' fill='url(#hudBorder)' rx='1.5'/>

    <!-- ================= HEADER HUD ================= -->
    <g transform='translate(24, 28)'>
        <!-- King Cobra Crest -->
        <g transform='translate(0, 0)'>
            <path d='M -2,-8 C 3,-12 11,-10 14,-5 C 17,0 14,7 8,9 C 3,11 -1,7 -2,3 L -2,12 L -5,12 L -5,-8 Z' fill='#ff3344'/>
            <circle cx='6' cy='-3' r='1.5' fill='#ffd700'/>
        </g>

        <!-- Title & Subtitle -->
        <text x='24' y='3' font-size='15' font-weight='700' fill='#ff3344' font-family='-apple-system,BlinkMacSystemFont,"Segoe UI",Helvetica,Arial,sans-serif' letter-spacing='0.5'>
            {$customTitle}
        </text>
        <text x='24' y='18' font-size='11' font-weight='500' fill='#8b949e' font-family='-apple-system,BlinkMacSystemFont,"Segoe UI",Helvetica,Arial,sans-serif'>
            AUTONOMOUS CONTRIBUTION PREDATOR &#8226; {$totalCommits} COMMITS HARVESTED
        </text>
    </g>

    <!-- Right Telemetry Badge -->
    <g transform='translate({$badgeX}, 28)'>
        <text x='0' y='10' text-anchor='end' font-size='11' font-weight='600' fill='#00ff88' font-family='-apple-system,BlinkMacSystemFont,"Segoe UI",Helvetica,Arial,sans-serif'>
            STATUS: PREDATORY FLOW &#8226; CURRENT STREAK: {$streakDays} DAYS &#8226; VENOM ENGINE: ACTIVE
        </text>
        <circle cx='-430' cy='6' r='3.5' fill='#00ff88'/>
        <circle cx='-430' cy='6' r='3.5' fill='none' stroke='#00ff88' stroke-width='1.2' class='radar-ring'/>
    </g>

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
        <text x='-8' y='9' fill='#8b949e' font-size='10' font-family='-apple-system,BlinkMacSystemFont,"Segoe UI",Helvetica,Arial,sans-serif' text-anchor='end'>Less</text>
        <rect x='0' y='0' width='10' height='10' rx='2' fill='#161b22' stroke='#21262d' stroke-width='0.5'/>
        <g transform='translate(16, 0)'>
            <rect x='0' y='0' width='10' height='10' rx='2' fill='#0e4429' stroke='#00ff88' stroke-width='0.5'/>
            <circle cx='5' cy='5' r='2' fill='#00ff88'/>
        </g>
        <g transform='translate(32, 0)'>
            <rect x='0' y='0' width='10' height='10' rx='2' fill='#003820' stroke='#00f5ff' stroke-width='0.5'/>
            <circle cx='5' cy='5' r='2.5' fill='#00f5ff'/>
        </g>
        <g transform='translate(48, 0)'>
            <rect x='0' y='0' width='10' height='10' rx='2' fill='#1f1a3a' stroke='#c084fc' stroke-width='0.5'/>
            <circle cx='5' cy='5' r='2.8' fill='#c084fc'/>
        </g>
        <g transform='translate(64, 0)'>
            <rect x='0' y='0' width='10' height='10' rx='2' fill='#2d1500' stroke='#ffd700' stroke-width='0.5'/>
            <circle cx='5' cy='5' r='3.2' fill='#ffd700'/>
        </g>
        <text x='82' y='9' fill='#8b949e' font-size='10' font-family='-apple-system,BlinkMacSystemFont,"Segoe UI",Helvetica,Arial,sans-serif'>More</text>
    </g>

    <!-- Left Footer Note -->
    <text x='48' y='{$legendY}' fill='#484f58' font-size='10' font-family='-apple-system,BlinkMacSystemFont,"Segoe UI",Helvetica,Arial,sans-serif'>
        Authentic 368-Day Telemetry &#8226; Kinematic King Cobra Autonomous Engine
    </text>
</svg>
SVG;
}
