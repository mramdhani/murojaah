param(
    [string]$TargetDirectory = "web/public/mushaf/madinah-classic"
)

$ErrorActionPreference = "Stop"
$workspace = (Resolve-Path (Join-Path $PSScriptRoot "..")).Path
$target = [System.IO.Path]::GetFullPath((Join-Path $workspace $TargetDirectory))
$publicRoot = [System.IO.Path]::GetFullPath((Join-Path $workspace "web/public"))

if (-not $target.StartsWith($publicRoot, [System.StringComparison]::OrdinalIgnoreCase)) {
    throw "Target directory must stay inside web/public"
}

New-Item -ItemType Directory -Path $target -Force | Out-Null
$progressFile = Join-Path $target "download-progress.json"
$downloaded = 0

function Write-DownloadProgress([string]$status, [int]$current, [string]$message = "") {
    $bytes = (Get-ChildItem -LiteralPath $target -Filter "page-*.jpg" -File -ErrorAction SilentlyContinue |
        Measure-Object -Property Length -Sum).Sum
    if ($null -eq $bytes) { $bytes = 0 }

    @{
        status = $status
        current = $current
        downloaded = $downloaded
        total = 604
        bytes = [long]$bytes
        message = $message
        updated_at = (Get-Date).ToString("o")
    } | ConvertTo-Json | Set-Content -LiteralPath $progressFile -Encoding UTF8
}

Write-DownloadProgress "running" 0

for ($page = 1; $page -le 604; $page++) {
    $destination = Join-Path $target ("page-{0:D3}.jpg" -f $page)
    if ((Test-Path -LiteralPath $destination) -and (Get-Item -LiteralPath $destination).Length -gt 1000) {
        $downloaded++
        Write-DownloadProgress "running" $page
        continue
    }

    $assetNumber = $page + 3
    $url = "https://www.searchtruth.com/quran/images/images3/{0:D4}.jpg" -f $assetNumber
    $temporary = "$destination.part"
    $lastError = ""

    for ($attempt = 1; $attempt -le 3; $attempt++) {
        try {
            Invoke-WebRequest -Uri $url -OutFile $temporary -UseBasicParsing -TimeoutSec 60
            if ((Get-Item -LiteralPath $temporary).Length -le 1000) {
                throw "Downloaded file is unexpectedly small"
            }
            Move-Item -LiteralPath $temporary -Destination $destination -Force
            $downloaded++
            $lastError = ""
            break
        }
        catch {
            $lastError = $_.Exception.Message
            Remove-Item -LiteralPath $temporary -Force -ErrorAction SilentlyContinue
            Start-Sleep -Seconds ([Math]::Min(2 * $attempt, 6))
        }
    }

    if ($lastError) {
        Write-DownloadProgress "failed" $page $lastError
        throw ("Failed downloading page " + $page + ": " + $lastError)
    }

    Write-DownloadProgress "running" $page
}

Write-DownloadProgress "complete" 604