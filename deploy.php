<?php
/**
 * GitHub Deployment & Sync Script
 * -------------------------------------------------------------------------
 * This script downloads the latest codebase from your GitHub repository,
 * extracts it, updates all your files on the server, and runs the database
 * migration automatically.
 * 
 * Usage:
 *   1. Upload this file to your remote server's root directory.
 *   2. Visit: https://nutrify.freehosting.dev/deploy.php
 * -------------------------------------------------------------------------
 */
declare(strict_types=1);

header('Content-Type: text/plain; charset=utf-8');
ini_set('max_execution_time', '300'); // 5 minutes
ini_set('memory_limit', '256M');

$requestedRepo = $_GET['repo'] ?? 'nutrifydeployedsystem';
$repoZipUrls = [
    'https://github.com/dadonggg/nutrifydeployedsystem/archive/refs/heads/main.zip',
    'https://github.com/dadonggg/backup-prosposed/archive/refs/heads/main.zip',
];
if ($requestedRepo === 'backup-prosposed') {
    $repoZipUrls = array_reverse($repoZipUrls);
}

$tempZipFile = __DIR__ . '/github_temp_latest.zip';
$tempExtractDir = __DIR__ . '/github_temp_extract';

echo "🚀 Starting deployment from GitHub...\n\n";

// 1. Download the zip file from GitHub
echo "📥 Step 1: Downloading latest ZIP from GitHub...\n";
$zipData = false;
$activeUrl = '';
foreach ($repoZipUrls as $url) {
    echo "Trying: $url ...\n";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_USERAGENT, 'PHP-Downloader');
    $res = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($res !== false && $httpCode === 200) {
        $zipData = $res;
        $activeUrl = $url;
        echo "✅ Downloaded from $activeUrl\n";
        break;
    }
}

if ($zipData === false) {
    die("❌ Failed to download ZIP from GitHub repository URLs. Please verify repository is public.\n");
}

if (file_put_contents($tempZipFile, $zipData) === false) {
    die("❌ Failed to save ZIP file locally to: $tempZipFile\n");
}
echo "✅ ZIP file saved successfully (" . round(filesize($tempZipFile) / 1024 / 1024, 2) . " MB).\n\n";

// 2. Extract the zip file
echo "📦 Step 2: Extracting ZIP file...\n";
$zip = new ZipArchive();
if ($zip->open($tempZipFile) !== true) {
    @unlink($tempZipFile);
    die("❌ Failed to open ZIP file. The download might be corrupted or ZipArchive is not installed.\n");
}

if (!is_dir($tempExtractDir)) {
    mkdir($tempExtractDir, 0755, true);
}

if (!$zip->extractTo($tempExtractDir)) {
    $zip->close();
    @unlink($tempZipFile);
    die("❌ Failed to extract files to: $tempExtractDir\n");
}
$zip->close();
@unlink($tempZipFile);
echo "✅ ZIP file extracted.\n\n";

// 3. Find the extracted folder name (usually 'backup-prosposed-main')
$items = scandir($tempExtractDir);
$extractedFolder = '';
foreach ($items as $item) {
    if ($item !== '.' && $item !== '..' && is_dir($tempExtractDir . '/' . $item)) {
        $extractedFolder = $tempExtractDir . '/' . $item;
        break;
    }
}

if (!$extractedFolder) {
    die("❌ Could not find extracted folder inside temp directory.\n");
}

echo "📂 Extracted folder located: " . basename($extractedFolder) . "\n\n";

// Helper function to recursively copy files and folders
function recursiveCopy(string $src, string $dst): void {
    $dir = opendir($src);
    if (!is_dir($dst)) {
        mkdir($dst, 0755, true);
    }
    while (false !== ($file = readdir($dir))) {
        if ($file !== '.' && $file !== '..') {
            if (is_dir($src . '/' . $file)) {
                // Skip .git folder
                if ($file === '.git') continue;
                recursiveCopy($src . '/' . $file, $dst . '/' . $file);
            } else {
                copy($src . '/' . $file, $dst . '/' . $file);
            }
        }
    }
    closedir($dir);
}

// Helper function to recursively delete directory
function recursiveRemove(string $dir): void {
    if (!is_dir($dir)) return;
    $files = array_diff(scandir($dir), ['.', '..']);
    foreach ($files as $file) {
        (is_dir("$dir/$file")) ? recursiveRemove("$dir/$file") : unlink("$dir/$file");
    }
    rmdir($dir);
}

// 4. Copy files to the actual root directory
echo "🚚 Step 3: Syncing files to your web server root...\n";
recursiveCopy($extractedFolder, __DIR__);
echo "✅ Files copied.\n\n";

// 5. Clean up temp folder
echo "🧹 Step 4: Cleaning up temporary files...\n";
recursiveRemove($tempExtractDir);
echo "✅ Clean up completed.\n\n";

// 6. Run Database Migration
echo "⚙️ Step 5: Running database migrations...\n";
$migrationFile = __DIR__ . '/sql/run_full_migration.php';
if (is_file($migrationFile)) {
    echo "------------------ Migration Log ------------------\n";
    include $migrationFile;
    echo "\n----------------------------------------------------\n";
} else {
    echo "⚠️ Warning: sql/run_full_migration.php not found. Skipping migration step.\n";
}

echo "\n🎉 Deployment successful! All views, controllers, and database tables are now fully updated and connected.\n";
echo "You can now visit your website: https://nutrify.freehosting.dev/index.php?r=fitness/directory\n";
