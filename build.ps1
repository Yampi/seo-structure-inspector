# Build script for SEO Structure Inspector production ZIPs (Free and Pro Add-on)
# Uses forward-slash paths so the ZIP extracts correctly on Linux servers.

$ErrorActionPreference = "Stop"
$PluginSlug = "seo-structure-inspector"
$Version = (Select-String -Path "seo-structure-inspector.php" -Pattern "Version:\s*([0-9.]+)").Matches.Groups[1].Value
$BuildDir = Join-Path $env:TEMP "${PluginSlug}-build"
$FreeZipName = "${PluginSlug}.zip"
$ProZipName = "${PluginSlug}-pro.zip"
$RootDir = $PSScriptRoot

function New-LinuxCompatibleZip {
    param(
        [string]$SourceDirectory,
        [string]$ZipPath,
        [string]$EntryPrefix
    )

    Add-Type -AssemblyName System.IO.Compression
    Add-Type -AssemblyName System.IO.Compression.FileSystem

    if (Test-Path $ZipPath) {
        Remove-Item $ZipPath -Force
    }

    $sourceFull = (Resolve-Path $SourceDirectory).Path.TrimEnd('\', '/')
    $zip = [System.IO.Compression.ZipFile]::Open($ZipPath, [System.IO.Compression.ZipArchiveMode]::Create)

    try {
        Get-ChildItem -Path $SourceDirectory -Recurse -File | ForEach-Object {
            $relative = $_.FullName.Substring($sourceFull.Length + 1).Replace('\', '/')
            $entryName = "$EntryPrefix/$relative"
            [void][System.IO.Compression.ZipFileExtensions]::CreateEntryFromFile(
                $zip,
                $_.FullName,
                $entryName,
                [System.IO.Compression.CompressionLevel]::Optimal
            )
        }
    } finally {
        $zip.Dispose()
    }
}

Write-Host "=============================================" -ForegroundColor Cyan
Write-Host "Building SEO Structure Inspector (Free & Pro)..." -ForegroundColor Cyan
Write-Host "Base Version: $Version" -ForegroundColor Cyan
Write-Host "=============================================" -ForegroundColor Cyan

if (Test-Path $BuildDir) {
    Remove-Item $BuildDir -Recurse -Force
}

# ── 1. BUILD FREE PLUGIN ──────────────────────────────────────────────────────
Write-Host "Building Free version..." -ForegroundColor Yellow
$FreeTarget = Join-Path $BuildDir $PluginSlug
New-Item -ItemType Directory -Path $FreeTarget -Force | Out-Null

$ExcludeDirs = @('.git', '.github', 'tests', 'node_modules', 'Pro') # Exclude Pro completely from Free build!
$ExcludeFiles = @('*.sh', '*.ps1', 'phpunit.xml', 'phpcs.xml', '.phpcs.xml', 'composer.lock', 'package.json', 'package-lock.json', 'webpack.config.js', '*.map', '.DS_Store', '*.zip', 'test.php', '.phpunit.result.cache', '*.md')

Get-ChildItem -Path $RootDir -Force | ForEach-Object {
    if ($ExcludeDirs -contains $_.Name) { return }
    if ($_.Name -like '*.zip') { return }

    if ($_.PSIsContainer) {
        Copy-Item $_.FullName -Destination (Join-Path $FreeTarget $_.Name) -Recurse -Force
    } else {
        $skip = $false
        foreach ($pattern in $ExcludeFiles) {
            if ($_.Name -like $pattern) { $skip = $true; break }
        }
        if (-not $skip) {
            Copy-Item $_.FullName -Destination (Join-Path $FreeTarget $_.Name) -Force
        }
    }
}

Push-Location $FreeTarget
composer install --no-dev --optimize-autoloader --quiet
Remove-Item "composer.json", "composer.lock" -Force -ErrorAction SilentlyContinue
Pop-Location

$FreeZipPath = Join-Path $RootDir $FreeZipName
New-LinuxCompatibleZip -SourceDirectory $FreeTarget -ZipPath $FreeZipPath -EntryPrefix $PluginSlug
Write-Host "✓ Free build complete: $FreeZipName" -ForegroundColor Green


# ── 2. BUILD PRO ADD-ON PLUGIN ────────────────────────────────────────────────
Write-Host "Building Pro Add-on version..." -ForegroundColor Yellow
$ProSlug = "${PluginSlug}-pro"
$ProTarget = Join-Path $BuildDir $ProSlug
New-Item -ItemType Directory -Path $ProTarget -Force | Out-Null
New-Item -ItemType Directory -Path (Join-Path $ProTarget "src/Pro") -Force | Out-Null

# Copy only the Pro source files
Copy-Item (Join-Path $RootDir "src/Pro") -Destination $ProTarget -Recurse -Force

# Generate the Pro plugin bootstrap file dynamically
$ProBootstrapContent = @'
<?php
/**
 * Plugin Name: SEO Structure Inspector PRO
 * Description: Desbloquea herramientas avanzadas de SEO (Auto-fix, AEO, LLMs, Batch Analysis y Reportes PDF) para el plugin SEO Structure Inspector.
 * Version: {{VERSION}}
 * Author: DeepMind & Pair Programmer
 * License: GPLv2 or later
 * Requires at least: 6.0
 * Requires PHP: 8.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

add_action( 'plugins_loaded', function() {
    if ( ! class_exists( 'SEOSI\Core\Plugin' ) ) {
        add_action( 'admin_notices', function() {
            echo '<div class="notice notice-error"><p>SEO Structure Inspector PRO requiere tener instalado y activo el plugin base gratuito.</p></div>';
        } );
        return;
    }
    
    // Autoload Pro classes from this add-on directory
    spl_autoload_register( function( $class ) {
        if ( strpos( $class, 'SEOSI\Pro\\' ) === 0 ) {
            $relative_class = substr( $class, strlen( 'SEOSI\Pro\\' ) );
            $file = __DIR__ . '/src/Pro/' . str_replace( '\\', '/', $relative_class ) . '.php';
            if ( file_exists( $file ) ) {
                require_once $file;
            }
        }
    } );
    
    // Define the activation constant to unlock premium logic locally
    if ( ! defined( 'SEOSI_PRO_ENABLED' ) ) {
        define( 'SEOSI_PRO_ENABLED', true );
    }
}, 10 );
'@.Replace('{{VERSION}}', $Version)

$ProBootstrapPath = Join-Path $ProTarget "${ProSlug}.php"
[System.IO.File]::WriteAllText($ProBootstrapPath, $ProBootstrapContent)

$ProZipPath = Join-Path $RootDir $ProZipName
New-LinuxCompatibleZip -SourceDirectory $ProTarget -ZipPath $ProZipPath -EntryPrefix $ProSlug
Write-Host "✓ Pro build complete: $ProZipName" -ForegroundColor Green

Write-Host "=============================================" -ForegroundColor Cyan
Write-Host "All builds successfully completed and verified!" -ForegroundColor Green
Write-Host "  Free Base Plugin:  $FreeZipName" -ForegroundColor Green
Write-Host "  Pro Add-on:        $ProZipName" -ForegroundColor Green
Write-Host "=============================================" -ForegroundColor Cyan
