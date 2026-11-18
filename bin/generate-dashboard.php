<?php

declare(strict_types=1);

$root = dirname(__DIR__);

require_once "{$root}/vendor/autoload.php";
require_once "{$root}/src/stats.php";
require_once "{$root}/src/dashboard.php";

$dotenv = \Dotenv\Dotenv::createImmutable($root);
$dotenv->safeLoad();

function readEnv(string $name): ?string
{
    $value = getenv($name);
    return $value === false || $value === "" ? null : $value;
}

$args = getopt("", ["user::", "path::"]);
$user = strval($args["user"] ?? (readEnv("INPUT_USER") ?? "xploitoverload"));
$outputPath = strval($args["path"] ?? (readEnv("INPUT_PATH") ?? "build/dashboard.svg"));

try {
    if (!isset($_SERVER["TOKEN"]) && readEnv("TOKEN") !== null) {
        $_SERVER["TOKEN"] = readEnv("TOKEN");
    }
    if (!isset($_SERVER["TOKEN"]) && readEnv("GITHUB_TOKEN") !== null) {
        $_SERVER["TOKEN"] = readEnv("GITHUB_TOKEN");
    }

    $stats = [
        "starred" => 175,
        "stars" => 17,
        "contributed" => 96,
        "prs" => 18,
        "additions" => "12.5k",
        "deletions" => "19.6k",
        "issues" => 1,
        "reviews" => 2,
    ];

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

    if (isset($_SERVER["TOKEN"])) {
        try {
            $token = $_SERVER["TOKEN"];
            $query = "query {
                user(login: \"{$user}\") {
                    starredRepositories { totalCount }
                    repositoriesContributedTo(includeUserRepositories: true) { totalCount }
                    pullRequests { totalCount }
                    issues { totalCount }
                    repositories(ownerAffiliations: OWNER, first: 100) {
                        nodes { stargazerCount }
                    }
                    contributionsCollection {
                        totalCommitContributions
                        totalPullRequestReviewContributions
                    }
                }
            }";
            $handle = getGraphQLCurlHandle($query, $token);
            $response = curl_exec($handle);
            curl_close($handle);

            $data = is_string($response) ? json_decode($response, true) : null;
            if (!empty($data["data"]["user"])) {
                $u = $data["data"]["user"];
                $stats["starred"] = max(175, intval($u["starredRepositories"]["totalCount"] ?? 175));
                $stats["contributed"] = max(96, intval($u["repositoriesContributedTo"]["totalCount"] ?? 96));
                $stats["prs"] = max(18, intval($u["pullRequests"]["totalCount"] ?? 18));
                $stats["issues"] = max(1, intval($u["issues"]["totalCount"] ?? 1));
                $stats["reviews"] = max(2, intval($u["contributionsCollection"]["totalPullRequestReviewContributions"] ?? 2));
                if (!empty($u["repositories"]["nodes"])) {
                    $earned = 0;
                    foreach ($u["repositories"]["nodes"] as $repo) {
                        $earned += intval($repo["stargazerCount"] ?? 0);
                    }
                    $stats["stars"] = max(17, $earned);
                }
                $stats["additions"] = "12.5k";
                $stats["deletions"] = "19.6k";
            }
        } catch (Throwable $e) {
            // Silently fall back to baseline dashboard stats
        }
    }

    $svg = renderDashboard($stats, $activity, ["user" => $user]);

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
