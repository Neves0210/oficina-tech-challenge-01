param(
    [Parameter(Mandatory=$true)][string]$VideoUrl,
    [string]$Commit = '',
    [string]$Branch = ''
)
$ErrorActionPreference = 'Stop'
Set-Location (Split-Path $PSScriptRoot -Parent)
$bundledPython = Join-Path $env:USERPROFILE '.cache/codex-runtimes/codex-primary-runtime/dependencies/python/python.exe'
$pythonCommand = if (Test-Path -LiteralPath $bundledPython) { $bundledPython } else { 'python' }
$arguments = @('scripts/build-delivery-pdfs.py', '--video-url', $VideoUrl, '--save-video')
if ($Commit) { $arguments += @('--commit', $Commit) }
if ($Branch) { $arguments += @('--branch', $Branch) }
& $pythonCommand @arguments
if ($LASTEXITCODE -ne 0) { throw 'Falha ao gerar PDFs. Instale reportlab e pypdf no Python utilizado.' }
Write-Host 'PDFs salvos em output/pdf. O video foi registrado em docs/entrega/video.md.'
Write-Host 'Publique as alteracoes de documentacao por PR e execute novamente para usar o hash final.'
