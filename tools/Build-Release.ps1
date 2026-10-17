[CmdletBinding()]
param(
    [string] $RepoRoot = '',
    [string] $OutputDirectory = ''
)

$ErrorActionPreference = 'Stop'
$utf8 = New-Object System.Text.UTF8Encoding($false)
$scriptRoot = Split-Path -Parent $MyInvocation.MyCommand.Path
if (-not $RepoRoot) {
    $RepoRoot = (Resolve-Path (Join-Path $scriptRoot '..')).Path
}
if (-not $OutputDirectory) {
    $OutputDirectory = Join-Path $RepoRoot '_releases'
}
$addonSource = Join-Path $RepoRoot 'src/addons/Warext/PrivateModeration'
$addonJson = [System.IO.File]::ReadAllText((Join-Path $addonSource 'addon.json'), $utf8) | ConvertFrom-Json
$version = [string] $addonJson.version_string
$versionId = [string] $addonJson.version_id
$stage = Join-Path ([System.IO.Path]::GetTempPath()) ('warext-pm-release-' + [guid]::NewGuid().ToString('N'))
$packageAddon = Join-Path $stage 'src/addons/Warext/PrivateModeration'
$packageData = Join-Path $packageAddon '_data'
$zipPath = Join-Path $OutputDirectory ('Warext-PrivateModeration-' + $version + '.zip')

New-Item -ItemType Directory -Path $packageAddon -Force | Out-Null
New-Item -ItemType Directory -Path $packageData -Force | Out-Null
New-Item -ItemType Directory -Path $OutputDirectory -Force | Out-Null
Copy-Item (Join-Path $addonSource '*') $packageAddon -Recurse -Force
Remove-Item (Join-Path $packageAddon '_output') -Recurse -Force -ErrorAction SilentlyContinue

function New-XmlDocument([string] $rootName) {
    $doc = New-Object System.Xml.XmlDocument
    $decl = $doc.CreateXmlDeclaration('1.0', 'utf-8', $null)
    [void] $doc.AppendChild($decl)
    [void] $doc.AppendChild($doc.CreateElement($rootName))
    return $doc
}

function Add-Attribute($doc, $node, [string] $name, [string] $value) {
    $attr = $doc.CreateAttribute($name)
    $attr.Value = $value
    [void] $node.Attributes.Append($attr)
}

# Class extensions
$classDoc = New-XmlDocument 'class_extensions'
$classRoot = $classDoc.DocumentElement
Get-ChildItem (Join-Path $addonSource '_output/class_extensions/*.json') | ForEach-Object {
    $item = [System.IO.File]::ReadAllText($_.FullName, $utf8) | ConvertFrom-Json
    $node = $classDoc.CreateElement('extension')
    Add-Attribute $classDoc $node 'from_class' $item.from_class
    Add-Attribute $classDoc $node 'to_class' $item.to_class
    Add-Attribute $classDoc $node 'execute_order' ([string] $item.execute_order)
    Add-Attribute $classDoc $node 'active' ($(if ($item.active) { '1' } else { '0' }))
    [void] $classRoot.AppendChild($node)
}
$classDoc.Save((Join-Path $packageData 'class_extensions.xml'))

# Cron entries
$cronDoc = New-XmlDocument 'cron'
$cronRoot = $cronDoc.DocumentElement
Get-ChildItem (Join-Path $addonSource '_output/cron_entries/*.json') | ForEach-Object {
    $item = [System.IO.File]::ReadAllText($_.FullName, $utf8) | ConvertFrom-Json
    $node = $cronDoc.CreateElement('entry')
    foreach ($name in 'entry_id', 'cron_expression', 'callback_class', 'callback_method') {
        Add-Attribute $cronDoc $node $name ([string] $item.$name)
    }
    Add-Attribute $cronDoc $node 'active' ($(if ($item.active) { '1' } else { '0' }))
    [void] $cronRoot.AppendChild($node)
}
$cronDoc.Save((Join-Path $packageData 'cron.xml'))

# Forum permissions
$permissionDoc = New-XmlDocument 'permissions'
$permissionRoot = $permissionDoc.DocumentElement
Get-ChildItem (Join-Path $addonSource '_output/permissions/*.json') | ForEach-Object {
    $item = [System.IO.File]::ReadAllText($_.FullName, $utf8) | ConvertFrom-Json
    $permissionId = $_.BaseName -replace '^forum-', ''
    $node = $permissionDoc.CreateElement('permission')
    Add-Attribute $permissionDoc $node 'permission_group_id' 'forum'
    Add-Attribute $permissionDoc $node 'permission_id' $permissionId
    Add-Attribute $permissionDoc $node 'permission_type' $item.permission_type
    Add-Attribute $permissionDoc $node 'interface_group_id' $item.interface_group_id
    Add-Attribute $permissionDoc $node 'display_order' ([string] $item.display_order)
    if ($item.depend_permission_id) {
        Add-Attribute $permissionDoc $node 'depend_permission_id' $item.depend_permission_id
    }
    [void] $permissionRoot.AppendChild($node)
}
$permissionDoc.Save((Join-Path $packageData 'permissions.xml'))

# Admin CP navigation
$adminNavigationDoc = New-XmlDocument 'admin_navigation'
$adminNavigationRoot = $adminNavigationDoc.DocumentElement
Get-ChildItem (Join-Path $addonSource '_output/admin_navigation/*.json') | ForEach-Object {
    $item = [System.IO.File]::ReadAllText($_.FullName, $utf8) | ConvertFrom-Json
    $node = $adminNavigationDoc.CreateElement('navigation_entry')
    foreach ($name in 'navigation_id', 'parent_navigation_id', 'display_order', 'navigation_type_id') {
        if ($null -ne $item.$name -and [string] $item.$name) {
            Add-Attribute $adminNavigationDoc $node $name ([string] $item.$name)
        }
    }
    Add-Attribute $adminNavigationDoc $node 'enabled' ($(if ($item.enabled) { '1' } else { '0' }))
    $payload = [ordered] @{
        link = [string] $item.link
        display_condition = [string] $item.display_condition
        extra_attributes = $item.extra_attributes
    }
    [void] $node.AppendChild($adminNavigationDoc.CreateCDataSection(($payload | ConvertTo-Json -Compress -Depth 5)))
    [void] $adminNavigationRoot.AppendChild($node)
}
$adminNavigationDoc.Save((Join-Path $packageData 'admin_navigation.xml'))

# Admin navigation phrases
$phraseDoc = New-XmlDocument 'phrases'
$phraseRoot = $phraseDoc.DocumentElement
Get-ChildItem (Join-Path $addonSource '_output/phrases/*.txt') | ForEach-Object {
    $node = $phraseDoc.CreateElement('phrase')
    Add-Attribute $phraseDoc $node 'title' $_.BaseName
    Add-Attribute $phraseDoc $node 'version_id' $versionId
    Add-Attribute $phraseDoc $node 'version_string' $version
    [void] $node.AppendChild($phraseDoc.CreateCDataSection([System.IO.File]::ReadAllText($_.FullName, $utf8)))
    [void] $phraseRoot.AppendChild($node)
}
$phraseDoc.Save((Join-Path $packageData 'phrases.xml'))

# Public and Admin routes
$routeDoc = New-XmlDocument 'routes'
$routeRoot = $routeDoc.DocumentElement
Get-ChildItem (Join-Path $addonSource '_output/routes/*.xml') | ForEach-Object {
    $routeSource = [xml] ([System.IO.File]::ReadAllText($_.FullName, $utf8))
    foreach ($route in $routeSource.routes.route) {
        $node = $routeDoc.ImportNode($route, $true)
        [void] $routeRoot.AppendChild($node)
    }
}
$routeDoc.Save((Join-Path $packageData 'routes.xml'))

# Filesystem templates
$templateDoc = New-XmlDocument 'templates'
$templateRoot = $templateDoc.DocumentElement
Get-ChildItem (Join-Path $addonSource '_output/templates') -Recurse -File -Filter '*.html' | ForEach-Object {
    $relative = $_.FullName.Substring((Join-Path $addonSource '_output/templates').Length).TrimStart([char]92, [char]47)
    $parts = $relative -split '[\\/]'
    $type = if ($parts.Length -gt 1 -and $parts[0] -eq 'admin') { 'admin' } else { 'public' }
    $node = $templateDoc.CreateElement('template')
    Add-Attribute $templateDoc $node 'type' $type
    Add-Attribute $templateDoc $node 'title' $_.BaseName
    Add-Attribute $templateDoc $node 'version_id' $versionId
    Add-Attribute $templateDoc $node 'version_string' $version
    [void] $node.AppendChild($templateDoc.CreateCDataSection([System.IO.File]::ReadAllText($_.FullName, $utf8)))
    [void] $templateRoot.AppendChild($node)
}
$templateDoc.Save((Join-Path $packageData 'templates.xml'))

# Include documentation beside the uploadable add-on tree.
$noUpload = Join-Path $stage '_no_upload'
New-Item -ItemType Directory -Path $noUpload -Force | Out-Null
Copy-Item (Join-Path $RepoRoot 'README.md') $noUpload -Force
Copy-Item (Join-Path $RepoRoot 'docs') $noUpload -Recurse -Force

# XenForo file health hashes cover the uploadable package files, including _data.
$hashMap = [ordered] @{}
Get-ChildItem (Join-Path $stage 'src') -Recurse -File | ForEach-Object {
    $relative = $_.FullName.Substring($stage.Length + 1).Replace([string][char]92, '/')
    $hashMap[$relative] = (Get-FileHash $_.FullName -Algorithm SHA256).Hash.ToLowerInvariant()
}
$hashMap | ConvertTo-Json -Depth 3 | Set-Content (Join-Path $stage 'hashes.json') -Encoding UTF8

if (Test-Path $zipPath) {
    Remove-Item -LiteralPath $zipPath -Force
}
Compress-Archive -Path (Join-Path $stage '*') -DestinationPath $zipPath -CompressionLevel Optimal
Remove-Item -LiteralPath $stage -Recurse -Force

Write-Output $zipPath

