Add-Type -AssemblyName System.IO.Compression.FileSystem

$source = "c:\Users\szasz\Local Sites\rocktile\app\public\wp-content\plugins\rocktile-calculator"
$downloads = "$env:USERPROFILE\Downloads"
$zipFile = "$downloads\rocktile-calculator.zip"
$tempFolder = "$env:TEMP\rt_pkg_build"

if (Test-Path $zipFile) { Remove-Item $zipFile -Force }
if (Test-Path $tempFolder) { Remove-Item $tempFolder -Recurse -Force }

$dest = "$tempFolder\rocktile-calculator"
New-Item -ItemType Directory -Path $dest -Force | Out-Null

Copy-Item "$source\rocktile-calculator.php" -Destination $dest -Force
Copy-Item "$source\includes" -Destination $dest -Recurse -Force
Copy-Item "$source\dist" -Destination $dest -Recurse -Force

[System.IO.Compression.ZipFile]::CreateFromDirectory($tempFolder, $zipFile, [System.IO.Compression.CompressionLevel]::Optimal, $false)

Remove-Item $tempFolder -Recurse -Force

Write-Host "KÉSZ: $zipFile $((Get-Item $zipFile).Length / 1KB) KB"
