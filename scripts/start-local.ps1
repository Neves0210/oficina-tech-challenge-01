param([switch]$SkipBuild, [switch]$PrepareOnly)
$ErrorActionPreference = 'Stop'
Set-Location (Split-Path $PSScriptRoot -Parent)
if (-not (Test-Path -LiteralPath '.env')) { Copy-Item -LiteralPath '.env.example' -Destination '.env' }
$environmentText = [IO.File]::ReadAllText((Join-Path (Get-Location) '.env'))
function New-LocalSecret {
    $secretBytes = New-Object byte[] 32
    $generator = [Security.Cryptography.RandomNumberGenerator]::Create()
    try { $generator.GetBytes($secretBytes) } finally { $generator.Dispose() }
    return $secretBytes
}
foreach ($keyName in @('APP_KEY', 'JWT_SECRET')) {
    $keyPattern = '(?m)^' + $keyName + '=(.*)\r?$'
    $keyMatch = [regex]::Match($environmentText, $keyPattern)
    if (-not $keyMatch.Success -or [string]::IsNullOrWhiteSpace($keyMatch.Groups[1].Value.Trim('"', "'", "`r"))) {
        $newKey = if ($keyName -eq 'APP_KEY') { 'base64:' + [Convert]::ToBase64String((New-LocalSecret)) } else { ((New-LocalSecret) | ForEach-Object { $_.ToString('x2') }) -join '' }
        $newLine = $keyName + '=' + $newKey
        $environmentText = if ($keyMatch.Success) { [regex]::Replace($environmentText, $keyPattern, $newLine) } else { $environmentText + "`n" + $newLine + "`n" }
    }
}
[IO.File]::WriteAllText((Join-Path (Get-Location) '.env'), $environmentText, (New-Object Text.UTF8Encoding $false))
if ($PrepareOnly) { return }
docker compose config --quiet
if ($LASTEXITCODE -ne 0) { throw 'Configuração Docker inválida.' }
if ($SkipBuild) { docker compose up -d --wait } else { docker compose up -d --build --wait }
if ($LASTEXITCODE -ne 0) { throw 'Falha ao iniciar o Docker. Confira se o Docker Desktop está em execução.' }
docker compose exec -T --user root app chmod 1777 /var/www/html/build
if ($LASTEXITCODE -ne 0) { throw 'Falha ao preparar o diretorio de relatorios.' }
docker compose exec -T app php artisan migrate --force
if ($LASTEXITCODE -ne 0) { throw 'Falha nas migrations.' }
Write-Host 'Ambiente pronto. Porta padrao: http://127.0.0.1:8081/api/openapi'
Write-Host 'Crie o administrador: docker compose exec app php artisan app:create-admin'
