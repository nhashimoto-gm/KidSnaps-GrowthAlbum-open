# ===============================================
# HEIC画像 → JPEG/WebP 変換スクリプト (Windows用)
# ===============================================
#
# 【前提条件】
# 1. FFmpegがインストールされている（https://ffmpeg.org/download.html）
# 2. FFmpegのパスが通っている、またはこのスクリプトと同じフォルダに配置
#
# 【使用方法】
# PowerShell> .\convert_heic_windows.ps1 -SourceDir "C:\path\to\uploads\images" -OutputFormat "jpeg"
# PowerShell> .\convert_heic_windows.ps1 -SourceDir "C:\path\to\uploads\images" -OutputFormat "webp"
# PowerShell> .\convert_heic_windows.ps1 -SourceDir "C:\path\to\uploads\images" -OutputFormat "both"
# PowerShell> .\convert_heic_windows.ps1 -SourceDir "C:\path\to\uploads\images" -OutputFormat "both" -PathPrefix "uploads/images/"
#
# 【出力】
# - 変換されたファイルは同じディレクトリに.jpgまたは.webp拡張子で保存
# - 変換マッピングCSVファイル（conversion_mapping.csv）を生成
#

param(
    [Parameter(Mandatory=$true)]
    [string]$SourceDir,

    [Parameter(Mandatory=$false)]
    [ValidateSet("jpeg", "webp", "both")]
    [string]$OutputFormat = "jpeg",

    [Parameter(Mandatory=$false)]
    [int]$Quality = 90,

    [Parameter(Mandatory=$false)]
    [string]$FFmpegPath = "ffmpeg",

    [Parameter(Mandatory=$false)]
    [string]$PathPrefix = "uploads/images/"
)

# カラー出力用関数
function Write-ColorOutput {
    param(
        [string]$Message,
        [string]$Color = "White"
    )
    Write-Host $Message -ForegroundColor $Color
}

# FFmpegの存在確認
function Test-FFmpeg {
    try {
        $null = & $FFmpegPath -version 2>&1
        return $true
    } catch {
        return $false
    }
}

# HEIC → JPEG 変換
function Convert-ToJPEG {
    param(
        [string]$InputPath,
        [string]$OutputPath,
        [int]$Quality
    )

    try {
        & $FFmpegPath -i $InputPath -q:v $Quality $OutputPath -y 2>&1 | Out-Null
        return Test-Path $OutputPath
    } catch {
        Write-ColorOutput "  エラー: $_" "Red"
        return $false
    }
}

# HEIC → WebP 変換
function Convert-ToWebP {
    param(
        [string]$InputPath,
        [string]$OutputPath,
        [int]$Quality
    )

    try {
        & $FFmpegPath -i $InputPath -c:v libwebp -quality $Quality $OutputPath -y 2>&1 | Out-Null
        return Test-Path $OutputPath
    } catch {
        Write-ColorOutput "  エラー: $_" "Red"
        return $false
    }
}

# メイン処理
Write-ColorOutput "`n=== HEIC画像変換スクリプト (Windows) ===" "Cyan"
Write-ColorOutput "対象ディレクトリ: $SourceDir" "Cyan"
Write-ColorOutput "出力形式: $OutputFormat" "Cyan"
Write-ColorOutput "品質: $Quality" "Cyan"
Write-ColorOutput "パスプレフィックス: $PathPrefix" "Cyan"
Write-ColorOutput ""

# FFmpeg確認
if (-not (Test-FFmpeg)) {
    Write-ColorOutput "エラー: FFmpegが見つかりません。" "Red"
    Write-ColorOutput "FFmpegをインストールするか、-FFmpegPathパラメータでパスを指定してください。" "Red"
    exit 1
}

Write-ColorOutput "FFmpeg: 検出成功" "Green"

# ディレクトリ存在確認
if (-not (Test-Path $SourceDir)) {
    Write-ColorOutput "エラー: ディレクトリが見つかりません: $SourceDir" "Red"
    exit 1
}

# HEICファイルを検索
$heicFiles = Get-ChildItem -Path $SourceDir -Filter "*.heic" -Recurse
$heifFiles = Get-ChildItem -Path $SourceDir -Filter "*.heif" -Recurse
$allHeicFiles = $heicFiles + $heifFiles

if ($allHeicFiles.Count -eq 0) {
    Write-ColorOutput "変換対象のHEIC/HEIFファイルが見つかりませんでした。" "Yellow"
    exit 0
}

Write-ColorOutput "変換対象ファイル: $($allHeicFiles.Count)件`n" "Green"

# 変換マッピングCSVの準備
$mappingFile = Join-Path $SourceDir "conversion_mapping.csv"
$mappingData = @()
$mappingData += "original_filename,original_path,jpeg_path,webp_path,status"

# 統計
$convertedCount = 0
$skippedCount = 0
$errorCount = 0

# 各ファイルを変換
foreach ($file in $allHeicFiles) {
    $relativePath = $file.FullName.Substring($SourceDir.Length).TrimStart('\')
    Write-ColorOutput "[$($convertedCount + $skippedCount + $errorCount + 1)/$($allHeicFiles.Count)] $relativePath" "White"

    $baseName = [System.IO.Path]::GetFileNameWithoutExtension($file.Name)
    $directory = $file.DirectoryName

    $jpegPath = Join-Path $directory "$baseName.jpg"
    $webpPath = Join-Path $directory "$baseName.webp"

    $jpegConverted = $false
    $webpConverted = $false
    $status = ""

    # JPEG変換
    if ($OutputFormat -eq "jpeg" -or $OutputFormat -eq "both") {
        if (Test-Path $jpegPath) {
            Write-ColorOutput "  JPEG: スキップ（既存）" "Yellow"
            $jpegConverted = $true
            $skippedCount++
        } else {
            Write-ColorOutput "  JPEG: 変換中..." "Gray"
            if (Convert-ToJPEG -InputPath $file.FullName -OutputPath $jpegPath -Quality $Quality) {
                Write-ColorOutput "  JPEG: 成功" "Green"
                $jpegConverted = $true
                $convertedCount++
            } else {
                Write-ColorOutput "  JPEG: 失敗" "Red"
                $errorCount++
                $status = "jpeg_failed"
            }
        }
    }

    # WebP変換
    if ($OutputFormat -eq "webp" -or $OutputFormat -eq "both") {
        if (Test-Path $webpPath) {
            Write-ColorOutput "  WebP: スキップ（既存）" "Yellow"
            $webpConverted = $true
            $skippedCount++
        } else {
            Write-ColorOutput "  WebP: 変換中..." "Gray"
            if (Convert-ToWebP -InputPath $file.FullName -OutputPath $webpPath -Quality $Quality) {
                Write-ColorOutput "  WebP: 成功" "Green"
                $webpConverted = $true
                $convertedCount++
            } else {
                Write-ColorOutput "  WebP: 失敗" "Red"
                $errorCount++
                if ($status -eq "") { $status = "webp_failed" }
            }
        }
    }

    # ステータス判定
    if ($status -eq "") {
        if ($jpegConverted -or $webpConverted) {
            $status = "success"
        } else {
            $status = "skipped"
        }
    }

    # マッピングデータに追加（プレフィックス付き）
    $jpegPathRelative = if ($jpegConverted) { $PathPrefix + $jpegPath.Substring($SourceDir.Length).TrimStart('\').Replace('\', '/') } else { "" }
    $webpPathRelative = if ($webpConverted) { $PathPrefix + $webpPath.Substring($SourceDir.Length).TrimStart('\').Replace('\', '/') } else { "" }
    $originalPathWithPrefix = $PathPrefix + $relativePath.Replace('\', '/')

    $mappingData += "$($file.Name),`"$originalPathWithPrefix`",`"$jpegPathRelative`",`"$webpPathRelative`",$status"

    Write-ColorOutput ""
}

# マッピングCSVを保存
$mappingData | Out-File -FilePath $mappingFile -Encoding UTF8
Write-ColorOutput "変換マッピングファイル保存: $mappingFile" "Cyan"

# 結果サマリ
Write-ColorOutput "`n=== 変換完了 ===" "Cyan"
Write-ColorOutput "変換成功: $convertedCount ファイル" "Green"
Write-ColorOutput "スキップ: $skippedCount ファイル" "Yellow"
Write-ColorOutput "エラー: $errorCount ファイル" "Red"
Write-ColorOutput "`n次のステップ:" "Cyan"
Write-ColorOutput "1. 変換されたファイルをサーバーの uploads/images/ ディレクトリにアップロード" "White"
Write-ColorOutput "2. conversion_mapping.csv もサーバーにアップロード" "White"
Write-ColorOutput "3. サーバー上で apply_converted_heic.php を実行" "White"

exit 0
