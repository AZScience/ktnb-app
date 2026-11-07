$ErrorActionPreference = "Stop"

# Get all modified, added, or untracked files from git
$changedFiles = git status --porcelain | ForEach-Object {
    if ($_ -match '^(M | M|A |\?\?)\s+(.*)$') {
        $matches[2].Trim('"')
    }
}

# Add public/build to the list since it's generated
$filesToZip = @()
foreach ($file in $changedFiles) {
    if (Test-Path $file) {
        $filesToZip += $file
    }
}

# Add public/build explicitly
if (Test-Path "public/build") {
    $filesToZip += "public/build"
}

# Remove duplicates
$filesToZip = $filesToZip | Select-Object -Unique

# Prepare a temporary folder
$tempDir = "deploy\_temp_update"
if (Test-Path $tempDir) {
    Remove-Item -Recurse -Force $tempDir
}
New-Item -ItemType Directory -Path $tempDir | Out-Null

# Copy files to temp folder keeping structure
foreach ($file in $filesToZip) {
    $dest = Join-Path $tempDir $file
    $destDir = Split-Path $dest -Parent
    if (!(Test-Path $destDir)) {
        New-Item -ItemType Directory -Path $destDir | Out-Null
    }
    Copy-Item -Path $file -Destination $dest -Recurse -Force
}

# Zip the temp folder
$zipPath = "deploy\ktnb-app-delta-update.zip"
if (Test-Path $zipPath) {
    Remove-Item -Force $zipPath
}

# Use Compress-Archive
Compress-Archive -Path "$tempDir\*" -DestinationPath $zipPath -Force

# Clean up
Remove-Item -Recurse -Force $tempDir

Write-Output "Zip created successfully at: $zipPath"
