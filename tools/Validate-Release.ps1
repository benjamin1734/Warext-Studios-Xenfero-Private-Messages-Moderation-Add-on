[CmdletBinding()]
param(
    [string] $RepoRoot = ''
)

$ErrorActionPreference = 'Stop'
$utf8 = New-Object System.Text.UTF8Encoding($false)
if (-not $RepoRoot) {
    $RepoRoot = (Resolve-Path (Join-Path $PSScriptRoot '..')).Path
}

$addonRoot = Join-Path $RepoRoot 'src/addons/Warext/PrivateModeration'
$addonJsonPath = Join-Path $addonRoot 'addon.json'
$addon = [System.IO.File]::ReadAllText($addonJsonPath, $utf8) | ConvertFrom-Json
if ([string]::IsNullOrWhiteSpace([string]$addon.version_string)) { throw 'addon.json version_string is empty.' }
if ([int]$addon.version_id -le 0) { throw 'addon.json version_id must be positive.' }
if ([int]$addon.require.XF[0] -gt 2030130) { throw 'The add-on requires a XenForo version newer than the audited target.' }

$php = Get-Command php -ErrorAction SilentlyContinue
if ($php) {
    Get-ChildItem $addonRoot -Recurse -File -Filter '*.php' | ForEach-Object {
        $result = & $php.Source -l $_.FullName 2>&1
        if ($LASTEXITCODE -ne 0) { throw ('PHP syntax validation failed: ' + $_.FullName + [Environment]::NewLine + $result) }
    }
}

Get-ChildItem (Join-Path $addonRoot '_output') -Recurse -File | ForEach-Object {
    if ($_.Extension -eq '.json') {
        try { [void]([System.IO.File]::ReadAllText($_.FullName, $utf8) | ConvertFrom-Json) }
        catch { throw ('Invalid JSON: ' + $_.FullName + [Environment]::NewLine + $_.Exception.Message) }
    }
    if ($_.Extension -eq '.xml') {
        try { [void]([xml][System.IO.File]::ReadAllText($_.FullName, $utf8)) }
        catch { throw ('Invalid XML: ' + $_.FullName + [Environment]::NewLine + $_.Exception.Message) }
    }
}

& (Join-Path $RepoRoot 'tools/Build-Release.ps1') -RepoRoot $RepoRoot | Out-Host
$zipPath = Join-Path $RepoRoot ('_releases/Warext-PrivateModeration-' + $addon.version_string + '.zip')
if (-not (Test-Path -LiteralPath $zipPath)) { throw "Release archive was not generated: $zipPath" }

$inspect = Join-Path ([System.IO.Path]::GetTempPath()) ('warext-pm-validate-' + [guid]::NewGuid().ToString('N'))
try {
    Expand-Archive -LiteralPath $zipPath -DestinationPath $inspect -Force
    $packageAddon = Join-Path $inspect 'upload/src/addons/Warext/PrivateModeration'
    foreach ($required in @('addon.json', 'hashes.json', 'Setup.php', '_data/cron.xml', '_data/admin_navigation.xml', '_data/templates.xml')) {
        if (-not (Test-Path -LiteralPath (Join-Path $packageAddon $required))) { throw "Archive is missing required path: $required" }
    }
    [void]([System.IO.File]::ReadAllText((Join-Path $packageAddon 'addon.json'), $utf8) | ConvertFrom-Json)
    $cron = [xml][System.IO.File]::ReadAllText((Join-Path $packageAddon '_data/cron.xml'), $utf8)
    $cronEntry = $cron.cron.entry
    $rulesText = [string]$cronEntry.'#cdata-section'
    if (-not $cronEntry -or [string]::IsNullOrWhiteSpace($rulesText)) { throw 'cron.xml does not contain run_rules CDATA.' }
    $rules = $rulesText | ConvertFrom-Json
    if ($null -eq $rules) { throw 'cron run_rules is null.' }
    $hashes = [System.IO.File]::ReadAllText((Join-Path $packageAddon 'hashes.json'), $utf8) | ConvertFrom-Json
    if ($null -eq $hashes) { throw 'hashes.json is empty.' }
    Write-Output "PASS: $zipPath"
}
finally {
    if (Test-Path -LiteralPath $inspect) { Remove-Item -LiteralPath $inspect -Recurse -Force }
}

