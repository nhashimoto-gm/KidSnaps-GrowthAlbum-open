#!/bin/bash
# HEIC変換サポート確認スクリプト

echo "========================================="
echo "HEIC変換サポート確認"
echo "========================================="
echo ""

# 1. FFmpegの確認
echo "1. FFmpeg確認"
echo "-------------------"
if command -v ffmpeg &> /dev/null; then
    echo "✓ FFmpegインストール済み"
    ffmpeg -version | head -n 1
    echo ""
    echo "HEVCコーデックサポート:"
    ffmpeg -codecs 2>/dev/null | grep hevc
    echo ""
    echo "HEIFフォーマットサポート:"
    ffmpeg -formats 2>/dev/null | grep heif
else
    echo "✗ FFmpegがインストールされていません"
fi
echo ""

# 2. ImageMagickの確認
echo "2. ImageMagick確認"
echo "-------------------"
if command -v convert &> /dev/null; then
    echo "✓ ImageMagickインストール済み"
    convert -version | head -n 1
    echo ""
    echo "デリゲート設定:"
    convert -list configure | grep DELEGATES
    echo ""
    echo "HEICフォーマットサポート:"
    convert -list format | grep -i heic
else
    echo "✗ ImageMagickがインストールされていません"
fi
echo ""

# 3. heif-convertの確認
echo "3. heif-convert確認"
echo "-------------------"
if command -v heif-convert &> /dev/null; then
    echo "✓ heif-convertインストール済み"
    heif-convert --version 2>&1
else
    echo "✗ heif-convertがインストールされていません"
fi
echo ""

# 4. PHP Imagick拡張の確認
echo "4. PHP Imagick拡張確認"
echo "-------------------"
if php -m | grep -i imagick &> /dev/null; then
    echo "✓ PHP Imagick拡張インストール済み"
    php -r "if (extension_loaded('imagick')) { \$imagick = new Imagick(); \$formats = \$imagick->queryFormats(); if (in_array('HEIC', \$formats)) { echo 'HEIC形式: サポート済\n'; } else { echo 'HEIC形式: 未サポート\n'; } }"
else
    echo "✗ PHP Imagick拡張がインストールされていません"
fi
echo ""

# 5. FFI拡張の確認
echo "5. PHP FFI拡張確認"
echo "-------------------"
if php -m | grep -i ffi &> /dev/null; then
    echo "✓ PHP FFI拡張インストール済み"
    if [ -f "/usr/lib/x86_64-linux-gnu/libheif.so" ] || [ -f "/usr/local/lib/libheif.so" ]; then
        echo "✓ libheifライブラリ検出"
    else
        echo "✗ libheifライブラリが見つかりません"
    fi
else
    echo "✗ PHP FFI拡張がインストールされていません"
fi
echo ""

echo "========================================="
echo "推奨事項"
echo "========================================="
echo "HEIC変換を有効にするには、以下のいずれかが必要:"
echo "1. FFmpeg + libx265 (HEVCコーデック)"
echo "2. ImageMagick + libheif delegate"
echo "3. PHP Imagick拡張 + libheif"
echo "4. heif-convert (libheif-examples)"
echo ""
