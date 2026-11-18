<?php

declare(strict_types=1);

$root = dirname(__DIR__);

require_once "{$root}/vendor/autoload.php";
require_once "{$root}/src/stats.php";
require_once "{$root}/src/activity_graph.php";

$dotenv = \Dotenv\Dotenv::createImmutable($root);
$dotenv->safeLoad();

function readEnv(string $name): ?string
{
    $value = getenv($name);
    return $value === false || $value === "" ? null : $value;
}

$args = getopt("", ["user::", "days::", "path::", "title::"]);
$user = strval($args["user"] ?? (readEnv("INPUT_USER") ?? "xploitoverload"));
$days = isset($args["days"]) ? intval($args["days"]) : 31;
$outputPath = strval($args["path"] ?? (readEnv("INPUT_PATH") ?? "build/activity-graph.svg"));
$customTitle = $args["title"] ?? "KALPESH SOLANKI's Contribution Graph";

try {
    if (!isset($_SERVER["TOKEN"]) && readEnv("TOKEN") !== null) {
        $_SERVER["TOKEN"] = readEnv("TOKEN");
    }
    if (!isset($_SERVER["TOKEN"]) && readEnv("GITHUB_TOKEN") !== null) {
        $_SERVER["TOKEN"] = readEnv("GITHUB_TOKEN");
    }
    if (!isset($_SERVER["TOKEN"])) {
        throw new RuntimeException("Missing GitHub token. Pass the action token input or set TOKEN.");
    }

    $currentYear = intval(date("Y"));
    // Fetch contribution graphs for current and previous year to guarantee enough recent days
    $graphs = executeContributionGraphRequests($user, [$currentYear, $currentYear - 1]);
    $contributions = getContributionDates($graphs);

    $svg = renderActivityGraph($contributions, [
        "days" => $days,
        "custom_title" => $customTitle,
    ]);

    $outputDir = dirname($outputPath);
    if ($outputDir !== "." && !is_dir($outputDir) && !mkdir($outputDir, 0755, true)) {
        throw new RuntimeException("Failed to create output directory: {$outputDir}");
    }

    if (file_put_contents($outputPath, $svg) === false) {
        throw new RuntimeException("Failed to write output file: {$outputPath}");
    }

    fwrite(STDOUT, "Wrote {$outputPath}\n");
} catch (Throwable $error) {
    fwrite(STDERR, $error->getMessage() . "\n");
    exit(1);
}
