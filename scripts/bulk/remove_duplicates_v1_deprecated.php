#!/usr/bin/env php
<?php
/**
 * ⚠️ このスクリプトは非推奨です
 * 代わりに remove_duplicates_v2.php を使用してください
 *
 * v2では以下の機能が追加されています:
 * - ファイル名+サイズによる検出
 * - EXIF撮影日時+サイズによる検出
 * - ファイルハッシュによる検出（最も正確）
 *
 * 使い方:
 *   php remove_duplicates_v2.php --method=hash --dry-run
 */

echo "\n";
echo "⚠️  このスクリプトは非推奨です\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
echo "代わりに remove_duplicates_v2.php を使用してください:\n";
echo "  php scripts/bulk/remove_duplicates_v2.php --method=hash --dry-run\n\n";
echo "v2では以下の機能が追加されています:\n";
echo "  ✓ ファイル名+サイズによる検出\n";
echo "  ✓ EXIF撮影日時+サイズによる検出\n";
echo "  ✓ ファイルハッシュによる検出（最も正確）\n\n";

exit(1);
?>
