/**
 * Sinkronkan font + CSS Bootstrap Icons dari node_modules ke assets/vendor,
 * sehingga aplikasi tetap jalan offline tanpa CDN.
 *
 * Jalankan:  npm run icons
 */
const fs = require('fs');
const path = require('path');

const root = path.join(__dirname, '..');
const src = path.join(root, 'node_modules', 'bootstrap-icons', 'font');
const dst = path.join(root, 'assets', 'vendor', 'bootstrap-icons-font');

if (!fs.existsSync(src)) {
  console.error('Tidak menemukan bootstrap-icons. Jalankan dulu: npm install');
  process.exit(1);
}

fs.mkdirSync(path.join(dst, 'fonts'), { recursive: true });
fs.copyFileSync(path.join(src, 'bootstrap-icons.min.css'), path.join(dst, 'bootstrap-icons.min.css'));
for (const file of fs.readdirSync(path.join(src, 'fonts'))) {
  fs.copyFileSync(path.join(src, 'fonts', file), path.join(dst, 'fonts', file));
}

console.log('Ikon Bootstrap Icons tersinkron ke ' + path.relative(root, dst));
