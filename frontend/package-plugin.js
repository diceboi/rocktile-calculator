import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';

const archiverModule = await import('archiver');
const archiver = archiverModule.default || archiverModule;

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

const pluginRoot = path.resolve(__dirname, '..');
const downloadsDir = path.resolve(process.env.USERPROFILE || 'C:/Users/szasz', 'Downloads');
const outputZipPath = path.join(downloadsDir, 'rocktile-calculator.zip');

if (fs.existsSync(outputZipPath)) {
  fs.unlinkSync(outputZipPath);
}

const output = fs.createWriteStream(outputZipPath);
const archive = archiver('zip', {
  zlib: { level: 9 }, // Maximum compression
});

output.on('close', function () {
  console.log(`ZIP sikeresen elkészült! Méret: ${(archive.pointer() / 1024).toFixed(1)} KB`);
  console.log(`Helye: ${outputZipPath}`);
});

archive.on('error', function (err) {
  throw err;
});

archive.pipe(output);

// 1. Fő plugin fájl és leírás
archive.file(path.join(pluginRoot, 'rocktile-calculator.php'), { name: 'rocktile-calculator/rocktile-calculator.php' });
if (fs.existsSync(path.join(pluginRoot, 'readme.txt'))) {
  archive.file(path.join(pluginRoot, 'readme.txt'), { name: 'rocktile-calculator/readme.txt' });
}

// 2. includes mappa
archive.directory(path.join(pluginRoot, 'includes'), 'rocktile-calculator/includes');

// 3. dist mappa (összes buildelt asset és manifest)
archive.directory(path.join(pluginRoot, 'dist'), 'rocktile-calculator/dist');

archive.finalize();
