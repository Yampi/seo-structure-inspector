#!/bin/bash
set -e

PLUGIN_SLUG="seo-structure-inspector"
VERSION=$(grep "Version:" seo-structure-inspector.php | awk '{print $3}')
BUILD_DIR="/tmp/${PLUGIN_SLUG}-build"
FREE_ZIP_NAME="${PLUGIN_SLUG}.zip"
PRO_ZIP_NAME="${PLUGIN_SLUG}-pro.zip"
ROOT_DIR="$(cd "$(dirname "$0")" && pwd)"

echo "============================================="
echo "Building SEO Structure Inspector (Free & Pro)..."
echo "Base Version: ${VERSION}"
echo "============================================="

rm -rf "$BUILD_DIR"
mkdir -p "$BUILD_DIR"

# ── 1. BUILD FREE PLUGIN ──────────────────────────────────────────────────────
echo "Building Free version..."
mkdir -p "$BUILD_DIR/$PLUGIN_SLUG"

rsync -av --exclude='.git' \
  --exclude='.github' \
  --exclude='tests/' \
  --exclude='node_modules/' \
  --exclude='Pro' \
  --exclude='*.sh' \
  --exclude='*.ps1' \
  --exclude='*.log' \
  --exclude='phpunit.xml' \
  --exclude='phpcs.xml' \
  --exclude='.phpcs.xml' \
  --exclude='composer.lock' \
  --exclude='package.json' \
  --exclude='package-lock.json' \
  --exclude='webpack.config.js' \
  --exclude='*.map' \
  --exclude='*.zip' \
  --exclude='test.php' \
  --exclude='.phpunit.result.cache' \
  --exclude='*.md' \
  . "$BUILD_DIR/$PLUGIN_SLUG/"

cd "$BUILD_DIR/$PLUGIN_SLUG"
composer install --no-dev --optimize-autoloader --quiet
rm -f composer.json composer.lock
cd "$BUILD_DIR"

rm -f "$ROOT_DIR/$FREE_ZIP_NAME"
zip -rq "$ROOT_DIR/$FREE_ZIP_NAME" "$PLUGIN_SLUG/"
echo "✓ Free build complete: ${FREE_ZIP_NAME}"


# ── 2. BUILD PRO ADD-ON PLUGIN ────────────────────────────────────────────────
echo "Building Pro Add-on version..."
PRO_SLUG="${PLUGIN_SLUG}-pro"
mkdir -p "$BUILD_DIR/$PRO_SLUG/src/Pro"

# Copy Pro folder
cp -r "$ROOT_DIR/src/Pro/"* "$BUILD_DIR/$PRO_SLUG/src/Pro/"

# Write bootstrap file dynamically
cat << EOF > "$BUILD_DIR/$PRO_SLUG/${PRO_SLUG}.php"
<?php
/**
 * Plugin Name: SEO Structure Inspector PRO
 * Description: Desbloquea herramientas avanzadas de SEO (Auto-fix, AEO, LLMs, Batch Analysis y Reportes PDF) para el plugin SEO Structure Inspector.
 * Version: ${VERSION}
 * Author: DeepMind & Pair Programmer
 * License: GPLv2 or later
 * Requires at least: 6.0
 * Requires PHP: 8.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

add_action( 'plugins_loaded', function() {
    if ( ! class_exists( 'SEOSI\\Core\\Plugin' ) ) {
        add_action( 'admin_notices', function() {
            echo '<div class="notice notice-error"><p>SEO Structure Inspector PRO requiere tener instalado y activo el plugin base gratuito.</p></div>';
        } );
        return;
    }
    
    // Autoload Pro classes from this add-on directory
    spl_autoload_register( function( \$class ) {
        if ( strpos( \$class, 'SEOSI\\\\Pro\\\\' ) === 0 ) {
            \$relative_class = substr( \$class, strlen( 'SEOSI\\\\Pro\\\\' ) );
            \$file = __DIR__ . '/src/Pro/' . str_replace( '\\\\', '/', \$relative_class ) . '.php';
            if ( file_exists( \$file ) ) {
                require_once \$file;
            }
        }
    } );
    
    // Define the activation constant to unlock premium logic locally
    if ( ! defined( 'SEOSI_PRO_ENABLED' ) ) {
        define( 'SEOSI_PRO_ENABLED', true );
    }
}, 10 );
EOF

cd "$BUILD_DIR"
rm -f "$ROOT_DIR/$PRO_ZIP_NAME"
zip -rq "$ROOT_DIR/$PRO_ZIP_NAME" "$PRO_SLUG/"
echo "✓ Pro build complete: ${PRO_ZIP_NAME}"

echo "============================================="
echo "All builds successfully completed and verified!"
echo "  Free Base Plugin:  ${FREE_ZIP_NAME}"
echo "  Pro Add-on:        ${PRO_ZIP_NAME}"
echo "============================================="
