@echo off
REM ===============================================
REM HEIC画像 → JPEG 変換スクリプト (ImageMagick版)
REM ===============================================
REM
REM 【前提条件】
REM ImageMagick for Windows がインストール済み
REM https://imagemagick.org/script/download.php
REM
REM 【使用方法】
REM convert_heic_imagemagick.bat "C:\path\to\uploads\images"
REM

setlocal enabledelayedexpansion

REM パラメータチェック
if "%~1"=="" (
    echo エラー: ディレクトリパスを指定してください
    echo 使用方法: convert_heic_imagemagick.bat "C:\path\to\uploads\images"
    exit /b 1
)

set "SOURCE_DIR=%~1"
set "QUALITY=90"

echo.
echo === HEIC画像変換スクリプト (ImageMagick版) ===
echo 対象ディレクトリ: %SOURCE_DIR%
echo 品質: %QUALITY%%%
echo.

REM ImageMagick存在確認
where magick >nul 2>&1
if %errorlevel% neq 0 (
    echo エラー: ImageMagickが見つかりません
    echo ImageMagickをインストールしてください: https://imagemagick.org/
    exit /b 1
)

echo ImageMagick: 検出成功
echo.

REM ディレクトリ存在確認
if not exist "%SOURCE_DIR%" (
    echo エラー: ディレクトリが見つかりません: %SOURCE_DIR%
    exit /b 1
)

REM CSVファイル準備
set "CSV_FILE=%SOURCE_DIR%\conversion_mapping.csv"
echo original_filename,original_path,jpeg_path,webp_path,status > "%CSV_FILE%"

REM 統計
set /a CONVERTED=0
set /a SKIPPED=0
set /a ERRORS=0
set /a TOTAL=0

REM HEICファイルを検索して変換
for /r "%SOURCE_DIR%" %%f in (*.heic *.HEIC *.heif *.HEIF) do (
    set /a TOTAL+=1
    set "HEIC_FILE=%%f"
    set "JPEG_FILE=%%~dpnf.jpg"
    set "FILENAME=%%~nxf"

    echo [!TOTAL!] !FILENAME!

    REM 既にJPEGが存在するかチェック
    if exist "!JPEG_FILE!" (
        echo   JPEG: スキップ（既存）
        set /a SKIPPED+=1
        set "STATUS=skipped"
    ) else (
        echo   JPEG: 変換中...

        REM ImageMagickで変換
        magick "!HEIC_FILE!" -quality %QUALITY% "!JPEG_FILE!" 2>nul

        if exist "!JPEG_FILE!" (
            echo   JPEG: 成功
            set /a CONVERTED+=1
            set "STATUS=success"
        ) else (
            echo   JPEG: 失敗
            set /a ERRORS+=1
            set "STATUS=failed"
        )
    )

    REM 相対パスを計算（簡易版）
    set "REL_PATH=!HEIC_FILE:%SOURCE_DIR%\=!"
    set "JPEG_REL=!JPEG_FILE:%SOURCE_DIR%\=!"

    REM CSVに追記
    echo !FILENAME!,"!REL_PATH!","!JPEG_REL!","",!STATUS! >> "%CSV_FILE%"
    echo.
)

if %TOTAL%==0 (
    echo 変換対象のHEIC/HEIFファイルが見つかりませんでした。
    exit /b 0
)

echo.
echo === 変換完了 ===
echo 変換成功: %CONVERTED% ファイル
echo スキップ: %SKIPPED% ファイル
echo エラー: %ERRORS% ファイル
echo.
echo 変換マッピングファイル保存: %CSV_FILE%
echo.
echo 次のステップ:
echo 1. 変換されたファイルをサーバーの uploads/images/ ディレクトリにアップロード
echo 2. conversion_mapping.csv もサーバーにアップロード
echo 3. サーバー上で apply_converted_heic.php を実行
echo.

endlocal
exit /b 0
