#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
HEIC画像 → JPEG/WebP 変換スクリプト (Python版)
FFmpegで白ブランクになる問題に対応

【前提条件】
pip install pillow pillow-heif tqdm

【使用方法】
python convert_heic_python.py --source "C:\\path\\to\\uploads\\images" --format jpeg
python convert_heic_python.py --source "C:\\path\\to\\uploads\\images" --format webp --quality 85
python convert_heic_python.py --source "C:\\path\\to\\uploads\\images" --format both --threads 4
python convert_heic_python.py --source "C:\\path\\to\\uploads\\images" --format both --prefix "uploads/images/"

【オプション】
--source, -s    : 変換対象ディレクトリ（必須）
--format, -f    : 出力形式（jpeg/webp/both、デフォルト: jpeg）
--quality, -q   : 変換品質 1-100（デフォルト: 90）
--threads, -t   : 並列スレッド数（デフォルト: CPU数の最大4）
--prefix, -p    : CSVファイルパスのプレフィックス（デフォルト: uploads/images/）
"""

import os
import sys
import argparse
import csv
from pathlib import Path
from PIL import Image
import pillow_heif
from concurrent.futures import ThreadPoolExecutor, as_completed
from threading import Lock
import multiprocessing

try:
    from tqdm import tqdm
    TQDM_AVAILABLE = True
except ImportError:
    TQDM_AVAILABLE = False
    print("注意: tqdmがインストールされていません。プログレスバーは表示されません。")
    print("インストール: pip install tqdm\n")

# HEIF形式をPillowに登録
pillow_heif.register_heif_opener()


class HEICConverter:
    def __init__(self, source_dir, output_format='jpeg', quality=90, max_workers=None, prefix='uploads/images/'):
        self.source_dir = Path(source_dir)
        self.output_format = output_format.lower()
        self.quality = quality
        self.prefix = prefix.rstrip('/') + '/' if prefix else ''  # 末尾にスラッシュを追加
        self.mapping_data = []
        self.max_workers = max_workers or min(4, multiprocessing.cpu_count())

        # 統計（スレッドセーフ）
        self.lock = Lock()
        self.converted_count = 0
        self.skipped_count = 0
        self.error_count = 0
        self.total_files = 0

    def find_heic_files(self):
        """HEICファイルを検索"""
        heic_files = []
        for ext in ['*.heic', '*.HEIC', '*.heif', '*.HEIF']:
            heic_files.extend(self.source_dir.rglob(ext))
        return heic_files

    def convert_to_jpeg(self, input_path, output_path):
        """HEIC → JPEG 変換"""
        try:
            img = Image.open(input_path)

            # RGBモードに変換（HEIC -> JPEGでは必須）
            if img.mode in ('RGBA', 'LA', 'P'):
                # 透明チャンネルがある場合は白背景に合成
                background = Image.new('RGB', img.size, (255, 255, 255))
                if img.mode == 'P':
                    img = img.convert('RGBA')
                background.paste(img, mask=img.split()[-1] if img.mode == 'RGBA' else None)
                img = background
            elif img.mode != 'RGB':
                img = img.convert('RGB')

            # EXIF情報を保持
            exif = img.info.get('exif', None)

            # JPEG保存
            if exif:
                img.save(output_path, 'JPEG', quality=self.quality, exif=exif, optimize=True)
            else:
                img.save(output_path, 'JPEG', quality=self.quality, optimize=True)

            img.close()
            return True
        except Exception as e:
            print(f"  エラー: {e}")
            return False

    def convert_to_webp(self, input_path, output_path):
        """HEIC → WebP 変換"""
        try:
            img = Image.open(input_path)

            # WebPは透明チャンネルもサポート
            if img.mode == 'P':
                img = img.convert('RGBA')

            # EXIF情報を保持
            exif = img.info.get('exif', None)

            # WebP保存
            if exif:
                img.save(output_path, 'WEBP', quality=self.quality, exif=exif, method=6)
            else:
                img.save(output_path, 'WEBP', quality=self.quality, method=6)

            img.close()
            return True
        except Exception as e:
            print(f"  エラー: {e}")
            return False

    def process_file(self, file_path, show_progress=True):
        """個別ファイルを処理"""
        relative_path = file_path.relative_to(self.source_dir)

        base_name = file_path.stem
        directory = file_path.parent

        jpeg_path = directory / f"{base_name}.jpg"
        webp_path = directory / f"{base_name}.webp"

        # 処理済みチェック（早期リターン）
        need_jpeg = self.output_format in ['jpeg', 'both'] and not jpeg_path.exists()
        need_webp = self.output_format in ['webp', 'both'] and not webp_path.exists()

        if not need_jpeg and not need_webp:
            # 完全にスキップ
            with self.lock:
                self.skipped_count += 1

            jpeg_path_rel = self.prefix + str(jpeg_path.relative_to(self.source_dir)) if jpeg_path.exists() else ""
            webp_path_rel = self.prefix + str(webp_path.relative_to(self.source_dir)) if webp_path.exists() else ""

            result = {
                'original_filename': file_path.name,
                'original_path': self.prefix + str(relative_path),
                'jpeg_path': jpeg_path_rel,
                'webp_path': webp_path_rel,
                'status': 'skipped'
            }

            with self.lock:
                self.mapping_data.append(result)

            return result

        if show_progress and not TQDM_AVAILABLE:
            print(f"\n処理中: {relative_path}")

        jpeg_converted = False
        webp_converted = False
        jpeg_was_existing = False
        webp_was_existing = False
        status = ""
        converted_this_run = 0

        # JPEG変換
        if self.output_format in ['jpeg', 'both']:
            if jpeg_path.exists():
                jpeg_converted = True
                jpeg_was_existing = True
                if show_progress and not TQDM_AVAILABLE:
                    print("  JPEG: スキップ（既存）")
            else:
                if show_progress and not TQDM_AVAILABLE:
                    print("  JPEG: 変換中...")
                if self.convert_to_jpeg(file_path, jpeg_path):
                    file_size_mb = jpeg_path.stat().st_size / (1024 * 1024)
                    if show_progress and not TQDM_AVAILABLE:
                        print(f"  JPEG: 成功 ({file_size_mb:.2f} MB)")
                    jpeg_converted = True
                    converted_this_run += 1
                else:
                    if show_progress and not TQDM_AVAILABLE:
                        print("  JPEG: 失敗")
                    with self.lock:
                        self.error_count += 1
                    status = "jpeg_failed"

        # WebP変換
        if self.output_format in ['webp', 'both']:
            if webp_path.exists():
                webp_converted = True
                webp_was_existing = True
                if show_progress and not TQDM_AVAILABLE:
                    print("  WebP: スキップ（既存）")
            else:
                if show_progress and not TQDM_AVAILABLE:
                    print("  WebP: 変換中...")
                if self.convert_to_webp(file_path, webp_path):
                    file_size_mb = webp_path.stat().st_size / (1024 * 1024)
                    if show_progress and not TQDM_AVAILABLE:
                        print(f"  WebP: 成功 ({file_size_mb:.2f} MB)")
                    webp_converted = True
                    converted_this_run += 1
                else:
                    if show_progress and not TQDM_AVAILABLE:
                        print("  WebP: 失敗")
                    with self.lock:
                        self.error_count += 1
                    if not status:
                        status = "webp_failed"

        # 統計更新（スレッドセーフ）
        with self.lock:
            self.converted_count += converted_this_run

        # ステータス判定
        if not status:
            status = "success" if (jpeg_converted or webp_converted) else "skipped"

        # マッピングデータに追加（プレフィックス付き）
        jpeg_path_rel = self.prefix + str(jpeg_path.relative_to(self.source_dir)) if jpeg_converted else ""
        webp_path_rel = self.prefix + str(webp_path.relative_to(self.source_dir)) if webp_converted else ""

        result = {
            'original_filename': file_path.name,
            'original_path': self.prefix + str(relative_path),
            'jpeg_path': jpeg_path_rel,
            'webp_path': webp_path_rel,
            'status': status
        }

        with self.lock:
            self.mapping_data.append(result)

        return result

    def save_mapping_csv(self):
        """マッピングCSVを保存"""
        csv_path = self.source_dir / 'conversion_mapping.csv'

        with open(csv_path, 'w', newline='', encoding='utf-8') as f:
            writer = csv.DictWriter(f, fieldnames=[
                'original_filename', 'original_path', 'jpeg_path', 'webp_path', 'status'
            ])
            writer.writeheader()
            writer.writerows(self.mapping_data)

        print(f"\n変換マッピングファイル保存: {csv_path}")

    def run(self):
        """メイン処理"""
        print("\n=== HEIC画像変換スクリプト (Python版) ===")
        print(f"対象ディレクトリ: {self.source_dir}")
        print(f"出力形式: {self.output_format}")
        print(f"品質: {self.quality}")
        print(f"並列スレッド数: {self.max_workers}\n")

        # ディレクトリ存在確認
        if not self.source_dir.exists():
            print(f"エラー: ディレクトリが見つかりません: {self.source_dir}")
            sys.exit(1)

        # HEICファイルを検索
        heic_files = self.find_heic_files()

        if not heic_files:
            print("変換対象のHEIC/HEIFファイルが見つかりませんでした。")
            sys.exit(0)

        self.total_files = len(heic_files)
        print(f"変換対象ファイル: {self.total_files}件\n")

        # マルチスレッド処理
        if TQDM_AVAILABLE:
            # tqdmが利用可能な場合
            with ThreadPoolExecutor(max_workers=self.max_workers) as executor:
                futures = [executor.submit(self.process_file, file_path, False) for file_path in heic_files]

                # プログレスバー表示
                with tqdm(total=self.total_files, desc="変換進行中", unit="ファイル") as pbar:
                    for future in as_completed(futures):
                        try:
                            future.result()
                            pbar.update(1)
                        except Exception as e:
                            print(f"\nエラー: {e}")
                            with self.lock:
                                self.error_count += 1
                            pbar.update(1)
        else:
            # tqdmがない場合は従来の表示
            with ThreadPoolExecutor(max_workers=self.max_workers) as executor:
                futures = {executor.submit(self.process_file, file_path, True): file_path for file_path in heic_files}

                for idx, future in enumerate(as_completed(futures), 1):
                    file_path = futures[future]
                    try:
                        print(f"\n[{idx}/{self.total_files}]", end=" ")
                        future.result()
                    except Exception as e:
                        print(f"\nエラー ({file_path.name}): {e}")
                        with self.lock:
                            self.error_count += 1

        # マッピングCSVを保存
        self.save_mapping_csv()

        # 結果サマリ
        print("\n=== 変換完了 ===")
        print(f"変換成功: {self.converted_count} ファイル")
        print(f"スキップ: {self.skipped_count} ファイル")
        print(f"エラー: {self.error_count} ファイル")
        print(f"合計: {self.total_files} ファイル")

        print("\n次のステップ:")
        print("1. 変換されたファイルをサーバーの uploads/images/ ディレクトリにアップロード")
        print("2. conversion_mapping.csv もサーバーにアップロード")
        print("3. サーバー上で apply_converted_heic.php を実行")


def main():
    parser = argparse.ArgumentParser(
        description='HEIC画像をJPEG/WebPに変換するスクリプト（Python版）',
        formatter_class=argparse.RawDescriptionHelpFormatter,
        epilog="""
使用例:
  python convert_heic_python.py --source "C:\\KidSnaps\\uploads\\images" --format jpeg
  python convert_heic_python.py --source "C:\\KidSnaps\\uploads\\images" --format webp --quality 85
  python convert_heic_python.py --source "C:\\KidSnaps\\uploads\\images" --format both --threads 8
  python convert_heic_python.py --source "C:\\KidSnaps\\uploads\\images" --format both --prefix "uploads/images/"
        """
    )

    parser.add_argument('--source', '-s', required=True,
                        help='変換対象のディレクトリパス（必須）')
    parser.add_argument('--format', '-f', choices=['jpeg', 'webp', 'both'],
                        default='jpeg', help='出力形式（デフォルト: jpeg）')
    parser.add_argument('--quality', '-q', type=int, default=90,
                        help='変換品質 1-100（デフォルト: 90）')
    parser.add_argument('--threads', '-t', type=int, default=None,
                        help='並列処理スレッド数（デフォルト: CPU数の最大4）')
    parser.add_argument('--prefix', '-p', type=str, default='uploads/images/',
                        help='CSVファイルパスのプレフィックス（デフォルト: uploads/images/）')

    args = parser.parse_args()

    # 品質の検証
    if not (1 <= args.quality <= 100):
        print("エラー: 品質は1-100の範囲で指定してください")
        sys.exit(1)

    # スレッド数の検証
    if args.threads is not None and args.threads < 1:
        print("エラー: スレッド数は1以上で指定してください")
        sys.exit(1)

    # 変換実行
    converter = HEICConverter(args.source, args.format, args.quality, args.threads, args.prefix)
    converter.run()


if __name__ == '__main__':
    main()
