const fs = require('fs');
const path = require('path');
const archiver = require('archiver');

const pluginRoot = path.resolve(__dirname, '..');
const downloadsDir = path.resolve(process.env.USERPROFILE || 'C:/Users/szasz', 'Downloads');
const outputZipPath = path.join(downloadsDir, 'rocktile-calculator.zip');

if (fs.existsSync(outputZipPath)) {
  fs.unlinkSync(outputZipPath);
}

const output = fs.createWriteStream(outputZipPath);
const archive = archiver('zip', {
  zlib: { level: 9 },
});

output.on('close', function () {
  console.log(`ZIP sikeresen elkészült! Méret: ${(archive.pointer() / 1024).toFixed(1)} KB`);
  console.log(`Helye: ${outputZipPath}`);
});

archive.on('error', function (err) {
  throw err;
});

archive.pipe(output);

// 1. Fő plugin fájl
archive.file(path.join(pluginRoot, 'rocktile-calculator.php'), { name: 'rocktile-calculator/rocktile-calculator.php' });

// 2. includes mappa
archive.directory(path.join(pluginRoot, 'includes'), 'rocktile-calculator/includes');

// 3. dist mappa
archive.directory(path.join(pluginRoot, 'dist'), 'rocktile-calculator/dist');

archive.finalize();
