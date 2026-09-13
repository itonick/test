<?php
declare(strict_types=1);

/**
 * ファイル保存（JSON）の限界を実測する
 *
 * 「1件だけ検索したいのに、全件を読み込んでいる」という
 * 第3部の実装が、件数に対してどう劣化するかを測る。
 *
 * 使い方: ブラウザで開く、または `php benchmark.php`
 */

/**
 * 指定件数のダミーデータを作り、「1件検索」にかかる時間を測る
 *
 * @return array<string, string>
 */
function benchmark(int $count): array
{
    $path = __DIR__ . "/bench.json";

    // ---------- データを作る ----------
    $rows = [];
    for ($i = 0; $i < $count; $i++) {
        $rows[] = [
            "id"         => bin2hex(random_bytes(8)),
            "name"       => "ユーザー{$i}",
            "body"       => str_repeat("これはテスト投稿です。", 5),
            "created_at" => time() - $i,
        ];
    }

    file_put_contents($path, json_encode($rows));
    $file_size = (int) filesize($path);

    // 最後の1件を検索対象にする（最悪ケース＝全件走査が必要）
    $target = $rows[$count - 1]["id"];

    // 生成したデータはメモリから外す（測定を汚さないため）
    unset($rows);

    // ---------- 1件検索を10回行い、平均を取る ----------
    $start = hrtime(true);

    for ($n = 0; $n < 10; $n++) {
        $loaded = json_decode((string) file_get_contents($path), true);
        foreach ($loaded as $row) {
            if ($row["id"] === $target) {
                break;
            }
        }
        unset($loaded);
    }

    $elapsed_ms = (hrtime(true) - $start) / 1_000_000 / 10;

    unlink($path);

    return [
        "count"  => number_format($count),
        "size"   => number_format((int) round($file_size / 1024)) . " KB",
        "search" => number_format($elapsed_ms, 2) . " ms",
        "memory" => number_format((int) round(memory_get_peak_usage(true) / 1024 / 1024)) . " MB",
    ];
}

// ==========================================================
// 実行
// ==========================================================

$is_cli = PHP_SAPI === "cli";

$results = [];
foreach ([100, 1000, 10000, 50000] as $count) {
    $results[] = benchmark($count);
}

/**
 * 表示幅を考慮して右側にスペースを詰める。
 * printf の %-10s はバイト数で数えるため、日本語を含むと桁が揃わない
 * （第3部 3-2「mb_ 系関数」で扱った話と同じ理由）。
 */
function pad(string $text, int $width): string
{
    // 全角文字は表示幅2としてざっくり計算する
    $display_width = 0;
    foreach (preg_split("//u", $text, -1, PREG_SPLIT_NO_EMPTY) ?: [] as $char) {
        $display_width += strlen($char) > 1 ? 2 : 1;
    }
    return $text . str_repeat(" ", max(0, $width - $display_width));
}

$lines = [];
$lines[] = pad("件数", 12) . pad("ファイルsize", 16) . pad("1件検索", 12) . "メモリ";
$lines[] = str_repeat("-", 48);
foreach ($results as $r) {
    $lines[] = pad($r["count"], 12) . pad($r["size"], 16)
             . pad($r["search"], 12) . $r["memory"];
}
$lines[] = "";
$lines[] = "※ 件数が10倍になると、時間もおよそ10倍になる（O(n)）";
$lines[] = "※ memory_limit を超えると Fatal error で停止する";

$output = implode("\n", $lines);

if ($is_cli) {
    echo $output . "\n";
} else {
    echo "<meta charset=\"UTF-8\">";
    echo "<pre style=\"font-family:monospace;font-size:14px;line-height:1.7\">";
    echo htmlspecialchars($output, ENT_QUOTES, "UTF-8");
    echo "</pre>";
}
