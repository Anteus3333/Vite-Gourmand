# Regénère les SVG Mermaid puis docs/PARCOURS-SITE.pdf
$ErrorActionPreference = 'Stop'

$docsDir = Split-Path -Parent $MyInvocation.MyCommand.Path
$diagramsDir = Join-Path $docsDir 'parcours-diagrams'
$svgDir = Join-Path $diagramsDir 'svg'
$pngDir = Join-Path $diagramsDir 'png'
$configPath = Join-Path $diagramsDir 'mermaid-config.json'
$htmlPath = Join-Path $docsDir 'parcours-export.html'
$pdfPath = Join-Path $docsDir 'PARCOURS-SITE.pdf'

if (-not (Test-Path $svgDir)) {
    New-Item -ItemType Directory -Path $svgDir | Out-Null
}
if (-not (Test-Path $pngDir)) {
    New-Item -ItemType Directory -Path $pngDir | Out-Null
}

Write-Host "Generation des diagrammes SVG..."
$mmdFiles = Get-ChildItem -Path $diagramsDir -Filter '*.mmd' | Sort-Object Name
foreach ($mmd in $mmdFiles) {
    $svgOut = Join-Path $svgDir ($mmd.BaseName + '.svg')
    $pngOut = Join-Path $pngDir ($mmd.BaseName + '.png')
    Write-Host "  -> $($mmd.Name)"
    npx --yes @mermaid-js/mermaid-cli@11 `
        -i $mmd.FullName `
        -o $svgOut `
        -c $configPath `
        -b white `
        -w 900 | Out-Null

    npx --yes @mermaid-js/mermaid-cli@11 `
        -i $mmd.FullName `
        -o $pngOut `
        -c $configPath `
        -b white `
        -w 900 | Out-Null

    if (-not (Test-Path $pngOut)) {
        throw "PNG manquant : $pngOut"
    }
}

Write-Host "Inline des PNG dans le HTML..."
node (Join-Path $docsDir 'inline-parcours-png.cjs')
$printHtmlPath = Join-Path $docsDir 'parcours-export-print.html'
$htmlUri = 'file:///' + ($printHtmlPath -replace '\\', '/')

Write-Host "Generation du PDF..."
$chromeCandidates = @(
    "${env:ProgramFiles}\Google\Chrome\Application\chrome.exe",
    "${env:ProgramFiles(x86)}\Google\Chrome\Application\chrome.exe",
    "${env:ProgramFiles}\Microsoft\Edge\Application\msedge.exe"
)

$browser = $chromeCandidates | Where-Object { Test-Path $_ } | Select-Object -First 1
if (-not $browser) {
    throw "Chrome ou Edge introuvable. Installez Google Chrome pour generer le PDF."
}

& $browser `
    --headless=new `
    --disable-gpu `
    --no-pdf-header-footer `
    --run-all-compositor-stages-before-draw `
    --virtual-time-budget=5000 `
    --print-to-pdf="$pdfPath" `
    $htmlUri

if (-not (Test-Path $pdfPath)) {
    throw "Echec de generation du PDF."
}

Get-Item $pdfPath | Select-Object FullName, @{ N = 'Ko'; E = [math]::Round($_.Length / 1KB, 1) }, LastWriteTime
