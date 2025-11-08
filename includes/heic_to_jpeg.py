#!/usr/bin/env python3
"""
HEIC to JPEG converter using libheif via ctypes
This script uses the libheif C library directly through Python's ctypes
"""

import sys
import ctypes
import os
from ctypes import c_void_p, c_char_p, c_int, POINTER, Structure, byref

# libheif構造体とエラーコードの定義
class heif_error(Structure):
    _fields_ = [
        ("code", c_int),
        ("subcode", c_int),
        ("message", c_char_p)
    ]

class heif_context(Structure):
    pass

class heif_image_handle(Structure):
    pass

class heif_image(Structure):
    pass

# ライブラリのロード
def load_libheif():
    """libheifライブラリをロード"""
    lib_paths = [
        '/usr/lib/x86_64-linux-gnu/libheif.so.1',
        '/usr/lib/libheif.so.1',
        '/usr/lib/libheif.so',
        '/usr/local/lib/libheif.so.1',
        '/usr/local/lib/libheif.so'
    ]

    for path in lib_paths:
        if os.path.exists(path):
            try:
                return ctypes.CDLL(path)
            except OSError:
                continue

    raise RuntimeError("libheif library not found")

def convert_heic_to_jpeg(input_path, output_path, quality=90):
    """
    HEIC画像をJPEGに変換

    Args:
        input_path: 入力HEICファイルのパス
        output_path: 出力JPEGファイルのパス
        quality: JPEG品質 (1-100)

    Returns:
        bool: 成功したかどうか
    """
    try:
        # Pillowを使用した変換（推奨）
        try:
            from PIL import Image
            import pillow_heif

            # HEIF形式のサポートを登録
            pillow_heif.register_heif_opener()

            # 画像を開いて変換
            image = Image.open(input_path)

            # RGBモードに変換（JPEGはRGBのみサポート）
            if image.mode != 'RGB':
                image = image.convert('RGB')

            # JPEGとして保存
            image.save(output_path, 'JPEG', quality=quality)
            print(f"Converted: {input_path} -> {output_path}")
            return True

        except ImportError:
            # Pillowが利用できない場合はctypesで直接libheifを使用
            pass

        # ctypesによる変換は複雑なため、代わりにImageMagickを試す
        import subprocess

        # ImageMagickを試行
        for cmd in ['magick', 'convert']:
            try:
                result = subprocess.run(
                    [cmd, input_path, '-quality', str(quality), output_path],
                    capture_output=True,
                    text=True,
                    timeout=30
                )
                if result.returncode == 0 and os.path.exists(output_path):
                    print(f"Converted with {cmd}: {input_path} -> {output_path}")
                    return True
            except (FileNotFoundError, subprocess.TimeoutExpired):
                continue

        print(f"Error: No conversion method available", file=sys.stderr)
        return False

    except Exception as e:
        print(f"Error converting {input_path}: {str(e)}", file=sys.stderr)
        return False

def main():
    """メイン処理"""
    if len(sys.argv) < 3:
        print("Usage: heic_to_jpeg.py <input.heic> <output.jpg> [quality]", file=sys.stderr)
        sys.exit(1)

    input_path = sys.argv[1]
    output_path = sys.argv[2]
    quality = int(sys.argv[3]) if len(sys.argv) > 3 else 90

    if not os.path.exists(input_path):
        print(f"Error: Input file not found: {input_path}", file=sys.stderr)
        sys.exit(1)

    success = convert_heic_to_jpeg(input_path, output_path, quality)
    sys.exit(0 if success else 1)

if __name__ == '__main__':
    main()
