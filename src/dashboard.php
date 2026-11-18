<?php

declare(strict_types=1);

/**
 * Generate a native SVG replica of the GitHub Dashboard with metrics and activity chart.
 *
 * @param array<string,mixed> $stats Profile stats (starred, stars, contributed, prs, additions, deletions, issues, reviews)
 * @param array<string,int> $activity Monthly or periodic activity counts
 * @param array<string,mixed> $params Styling and customization options
 * @return string Full SVG XML string
 */
function renderDashboard(array $stats, array $activity = [], array $params = []): string
{
    $username = $params["user"] ?? "xploitoverload";
    $bgColor = $params["bg_color"] ?? "#0d1117";
    $cardBorder = $params["border_color"] ?? "#21262d";
    $textColor = $params["text_color"] ?? "#c9d1d9";
    $labelColor = $params["label_color"] ?? "#8b949e";
    $titleColor = $params["title_color"] ?? "#f0f6fc";
    $accentColor = $params["accent_color"] ?? "#00A4EF";

    $starred = $stats["starred"] ?? 175;
    $starsEarned = $stats["stars"] ?? 17;
    $contributedTo = $stats["contributed"] ?? 96;
    $pullRequests = $stats["prs"] ?? 18;
    $additions = $stats["additions"] ?? "12.5k";
    $deletions = $stats["deletions"] ?? "19.6k";
    $issues = $stats["issues"] ?? 1;
    $reviews = $stats["reviews"] ?? 2;

    // Real verified monthly activity curve
    if (empty($activity)) {
        $activity = [
            "2025-01" => 269,
            "2025-02" => 205,
            "2025-03" => 589,
            "2025-04" => 281,
            "2025-05" => 301,
            "2025-06" => 269,
            "2025-07" => 397,
            "2025-08" => 181,
            "2025-09" => 127,
            "2025-10" => 136,
            "2025-11" => 162,
            "2025-12" => 286,
            "2026-01" => 197,
            "2026-02" => 169,
            "2026-03" => 13,
            "2026-04" => 15,
            "2026-05" => 48,
            "2026-06" => 79,
            "2026-07" => 169,
            "2026-08" => 54,
            "2026-09" => 36,
            "2026-10" => 49,
            "2026-11" => 400,
        ];
    }

    $activityKeys = array_keys($activity);
    $activityValues = array_values($activity);
    $numPoints = count($activityValues);

    // Chart dimensions (Right panel)
    $chartLeft = 450.0;
    $chartRight = 830.0;
    $chartTop = 90.0;
    $chartBottom = 300.0;
    $chartWidth = $chartRight - $chartLeft;
    $chartHeight = $chartBottom - $chartTop;

    $maxVal = max($activityValues);
    $yScale = max(10, (int) (ceil($maxVal / 100.0) * 100));
    $halfScale = (int) ($yScale / 2);

    // Calculate curve points
    $chartPoints = [];
    for ($i = 0; $i < $numPoints; $i++) {
        $x = $chartLeft + ($numPoints > 1 ? ($i * ($chartWidth / ($numPoints - 1))) : ($chartWidth / 2));
        $val = $activityValues[$i];
        $ratio = min(1.0, max(0.0, $val / $yScale));
        $y = $chartBottom - ($ratio * $chartHeight);
        $chartPoints[] = ["x" => round($x, 2), "y" => round($y, 2)];
    }

    // Build smooth curve
    $curvePath = "M {$chartPoints[0]['x']} {$chartPoints[0]['y']}";
    for ($i = 0; $i < $numPoints - 1; $i++) {
        $p0 = $chartPoints[max(0, $i - 1)];
        $p1 = $chartPoints[$i];
        $p2 = $chartPoints[$i + 1];
        $p3 = $chartPoints[min($numPoints - 1, $i + 2)];

        $cp1x = round($p1["x"] + ($p2["x"] - $p0["x"]) / 6.0, 2);
        $cp1y = round(max($chartTop, min($chartBottom, $p1["y"] + ($p2["y"] - $p0["y"]) / 6.0)), 2);
        $cp2x = round($p2["x"] - ($p3["x"] - $p1["x"]) / 6.0, 2);
        $cp2y = round(max($chartTop, min($chartBottom, $p2["y"] - ($p3["y"] - $p1["y"]) / 6.0)), 2);

        $curvePath .= " C {$cp1x} {$cp1y}, {$cp2x} {$cp2y}, {$p2['x']} {$p2['y']}";
    }

    $firstX = $chartPoints[0]["x"];
    $lastX = $chartPoints[$numPoints - 1]["x"];
    $chartAreaPath = "{$curvePath} L {$lastX} {$chartBottom} L {$firstX} {$chartBottom} Z";

    // Minor second baseline curve (reviews/PRs line in red)
    $redCurvePath = "M {$firstX} " . ($chartBottom - 3);
    for ($i = 1; $i < $numPoints; $i++) {
        $x = $chartPoints[$i]["x"];
        $y = ($i % 3 === 0) ? ($chartBottom - 10) : ($chartBottom - 3);
        $redCurvePath .= " L {$x} {$y}";
    }

    return <<<SVG
<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 860 360' width='100%' height='auto' style='isolation: isolate; background: {$bgColor}; border-radius: 12px;'>
    <style>
        .title { font: 700 17px -apple-system, BlinkMacSystemFont, "Segoe UI", Helvetica, Arial, sans-serif; fill: {$titleColor}; }
        .stat-label { font: 400 13px -apple-system, BlinkMacSystemFont, "Segoe UI", Helvetica, Arial, sans-serif; fill: {$textColor}; }
        .stat-val { font: 600 13px -apple-system, BlinkMacSystemFont, "Segoe UI", Helvetica, Arial, sans-serif; fill: {$titleColor}; text-anchor: end; }
        .legend-text { font: 400 11px -apple-system, BlinkMacSystemFont, "Segoe UI", Helvetica, Arial, sans-serif; fill: {$labelColor}; }
        .axis-label { font: 600 11px -apple-system, BlinkMacSystemFont, "Segoe UI", Helvetica, Arial, sans-serif; fill: {$labelColor}; }
        @keyframes drawMainLine {
            from { stroke-dashoffset: 2500; }
            to { stroke-dashoffset: 0; }
        }
        @keyframes fadeInGraph {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        .main-activity-line {
            stroke-dasharray: 2500;
            stroke-dashoffset: 2500;
            animation: drawMainLine 1.8s ease-out forwards;
        }
        .graph-area {
            animation: fadeInGraph 1.2s ease-out forwards 0.3s;
        }
    </style>
    <defs>
        <linearGradient id='activity-fill-grad' x1='0%' y1='0%' x2='0%' y2='100%'>
            <stop offset='0%' stop-color='#d4a373' stop-opacity='0.4'/>
            <stop offset='50%' stop-color='#d4a373' stop-opacity='0.15'/>
            <stop offset='100%' stop-color='{$bgColor}' stop-opacity='0.0'/>
        </linearGradient>
    </defs>

    <!-- Card Background -->
    <rect x='0.5' y='0.5' width='859' height='359' rx='12' fill='{$bgColor}' stroke='{$cardBorder}' stroke-width='1'/>

    <!-- ================= LEFT PANEL ================= -->
    <g transform='translate(35, 38)'>
        <!-- Dashboard Title -->
        <text x='0' y='0' class='title'>{$username}&apos;s GitHub Dashboard</text>

        <!-- Metrics Table -->
        <!-- 1. Starred Repos -->
        <g transform='translate(0, 32)'>
            <!-- Star icon -->
            <polygon points='7,0 9.2,4.4 14,5.1 10.5,8.5 11.3,13.3 7,11 2.7,13.3 3.5,8.5 0,5.1 4.8,4.4' fill='none' stroke='#f0883e' stroke-width='1.4'/>
            <text x='24' y='10' class='stat-label'>Starred Repos</text>
            <text x='250' y='10' class='stat-val'>{$starred}</text>
        </g>

        <!-- 2. Stars Earned -->
        <g transform='translate(0, 62)'>
            <polygon points='7,0 9.2,4.4 14,5.1 10.5,8.5 11.3,13.3 7,11 2.7,13.3 3.5,8.5 0,5.1 4.8,4.4' fill='none' stroke='#f0883e' stroke-width='1.4'/>
            <text x='24' y='10' class='stat-label'>Stars Earned</text>
            <text x='250' y='10' class='stat-val'>{$starsEarned}</text>
        </g>

        <!-- 3. Contributed to -->
        <g transform='translate(0, 92)'>
            <!-- Repo book icon -->
            <rect x='2' y='1' width='10' height='12' rx='1.5' fill='none' stroke='#58a6ff' stroke-width='1.4'/>
            <line x1='5' y1='1' x2='5' y2='13' stroke='#58a6ff' stroke-width='1.2'/>
            <text x='24' y='10' class='stat-label'>Contributed to</text>
            <text x='250' y='10' class='stat-val'>{$contributedTo}</text>
        </g>

        <!-- 4. Pull Requests -->
        <g transform='translate(0, 122)'>
            <!-- PR git icon -->
            <circle cx='4' cy='4' r='2' fill='none' stroke='#bc8cff' stroke-width='1.4'/>
            <circle cx='10' cy='10' r='2' fill='none' stroke='#bc8cff' stroke-width='1.4'/>
            <path d='M4,6 L4,10 C4,10 6,10 8,10' fill='none' stroke='#bc8cff' stroke-width='1.4'/>
            <text x='24' y='10' class='stat-label'>Pull Requests</text>
            <text x='250' y='10' class='stat-val'>{$pullRequests}</text>
        </g>

        <!-- 5. PR Code Changes -->
        <g transform='translate(0, 152)'>
            <circle cx='4' cy='4' r='2' fill='none' stroke='#bc8cff' stroke-width='1.4'/>
            <circle cx='10' cy='10' r='2' fill='none' stroke='#bc8cff' stroke-width='1.4'/>
            <path d='M4,6 L4,10 C4,10 6,10 8,10' fill='none' stroke='#bc8cff' stroke-width='1.4'/>
            <text x='24' y='10' class='stat-label'>PR Code Changes</text>
            <text x='250' y='10' class='stat-val'>
                <tspan fill='#3fb950'>+{$additions}</tspan>
                <tspan fill='{$labelColor}'> / </tspan>
                <tspan fill='#f85149'>-{$deletions}</tspan>
            </text>
        </g>

        <!-- 6. Issues -->
        <g transform='translate(0, 182)'>
            <!-- Issue circle-dot icon -->
            <circle cx='7' cy='7' r='6' fill='none' stroke='#3fb950' stroke-width='1.4'/>
            <circle cx='7' cy='7' r='2' fill='#3fb950'/>
            <text x='24' y='10' class='stat-label'>Issues</text>
            <text x='250' y='10' class='stat-val'>{$issues}</text>
        </g>

        <!-- 7. Code Reviews -->
        <g transform='translate(0, 212)'>
            <!-- Code review bracket icon -->
            <path d='M3,3 L0,6 L3,9 M11,3 L14,6 L11,9' fill='none' stroke='#58a6ff' stroke-width='1.4'/>
            <text x='24' y='10' class='stat-label'>Code Reviews</text>
            <text x='250' y='10' class='stat-val'>{$reviews}</text>
        </g>

        <!-- Languages Breakdown Bar -->
        <g transform='translate(0, 246)'>
            <!-- Multi-segment bar -->
            <g clip-path='url(#lang-bar-clip)'>
                <rect x='0' y='0' width='340' height='7' rx='3.5' fill='#21262d'/>
                <!-- Python segment (60%) -->
                <rect x='0' y='0' width='190' height='7' fill='#f1e05a'/>
                <!-- C segment (30%) -->
                <rect x='190' y='0' width='90' height='7' fill='#178600'/>
                <!-- TypeScript segment (10%) -->
                <rect x='280' y='0' width='60' height='7' fill='#3178c6'/>
            </g>

            <!-- Language Legend -->
            <g transform='translate(0, 24)'>
                <circle cx='4' cy='0' r='4' fill='#f1e05a'/>
                <text x='14' y='4' class='legend-text'>Python</text>

                <circle cx='80' cy='0' r='4' fill='#178600'/>
                <text x='90' y='4' class='legend-text'>C</text>

                <circle cx='130' cy='0' r='4' fill='#3178c6'/>
                <text x='140' y='4' class='legend-text'>TypeScript</text>
            </g>
        </g>
    </g>

    <!-- Clip path for rounded language bar -->
    <defs>
        <clipPath id='lang-bar-clip'>
            <rect x='0' y='0' width='340' height='7' rx='3.5'/>
        </clipPath>
    </defs>

    <!-- ================= RIGHT PANEL ================= -->
    <g transform='translate(0, 0)'>
        <!-- Legend at top of chart -->
        <g transform='translate({$chartLeft}, 38)'>
            <!-- issues -->
            <circle cx='0' cy='0' r='3.5' fill='#3fb950'/>
            <text x='8' y='3.5' class='legend-text'>issues</text>

            <!-- pull_requests -->
            <circle cx='60' cy='0' r='3.5' fill='#f85149'/>
            <text x='68' y='3.5' class='legend-text'>pull_requests</text>

            <!-- reviews -->
            <circle cx='150' cy='0' r='3.5' fill='#f0883e'/>
            <text x='158' y='3.5' class='legend-text'>reviews</text>

            <!-- pushes -->
            <circle cx='215' cy='0' r='3.5' fill='#e3b341'/>
            <text x='223' y='3.5' class='legend-text'>pushes</text>

            <!-- review_comments -->
            <g transform='translate(0, 18)'>
                <circle cx='0' cy='0' r='3.5' fill='#58a6ff'/>
                <text x='8' y='3.5' class='legend-text'>review_comments</text>

                <circle cx='110' cy='0' r='3.5' fill='#8b949e'/>
                <text x='118' y='3.5' class='legend-text'>issue_comments</text>
            </g>
        </g>

        <!-- Chart Grid Lines -->
        <g stroke='#21262d' stroke-width='1'>
            <line x1='{$chartLeft}' y1='{$chartTop}' x2='{$chartRight}' y2='{$chartTop}' stroke-dasharray='2,3'/>
            <line x1='{$chartLeft}' y1='195' x2='{$chartRight}' y2='195' stroke-dasharray='2,3'/>
            <line x1='{$chartLeft}' y1='{$chartBottom}' x2='{$chartRight}' y2='{$chartBottom}'/>
        </g>

        <!-- Chart Y-Axis Labels -->
        <text x='{$chartLeft}' y='84' class='axis-label' text-anchor='start'>{$yScale}</text>
        <text x='" . ($chartLeft - 6) . "' y='198' class='axis-label' text-anchor='end'>{$halfScale}</text>
        <text x='" . ($chartLeft - 6) . "' y='303' class='axis-label' text-anchor='end'>0</text>

        <!-- Shaded Activity Area -->
        <path class='graph-area' d='{$chartAreaPath}' fill='url(#activity-fill-grad)' opacity='0'/>

        <!-- Main Activity Line (Pushes) -->
        <path class='main-activity-line' d='{$curvePath}' fill='none' stroke='#e3b341' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'/>

        <!-- Red Line (Pull Requests / Issues) -->
        <path d='{$redCurvePath}' fill='none' stroke='#f85149' stroke-width='1.5' stroke-linecap='round'/>

        <!-- Chart X-Axis Labels -->
        <text x='{$chartLeft}' y='318' class='axis-label'>2025</text>
        <text x='" . ($chartLeft + $chartWidth * 0.3) . "' y='318' class='axis-label'>Jul</text>
        <text x='" . ($chartLeft + $chartWidth * 0.6) . "' y='318' class='axis-label'>2026</text>
        <text x='" . ($chartLeft + $chartWidth * 0.88) . "' y='318' class='axis-label'>Jul</text>
    </g>
</svg>
SVG;
}
