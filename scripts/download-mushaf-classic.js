const fs = require('fs');
const path = require('path');
const https = require('https');

const TARGET_DIR = path.join(__dirname, '../web/public/mushaf/madinah-nabawiyyah');
const PROGRESS_FILE = path.join(TARGET_DIR, 'download-progress.json');
const TOTAL_PAGES = 604;
const CONCURRENCY = 5; // Number of parallel downloads

// Ensure target directory exists
if (!fs.existsSync(TARGET_DIR)) {
  fs.mkdirSync(TARGET_DIR, { recursive: true });
}

let downloaded = 0;

function writeProgress(status, current, message = '') {
  // Calculate total bytes currently downloaded
  let bytes = 0;
  try {
    const files = fs.readdirSync(TARGET_DIR);
    files.forEach(file => {
      if (file.startsWith('page-') && file.endsWith('.gif')) {
        const stats = fs.statSync(path.join(TARGET_DIR, file));
        bytes += stats.size;
      }
    });
  } catch (err) {
    // Ignore read errors
  }

  const progress = {
    status,
    current,
    downloaded,
    total: TOTAL_PAGES,
    bytes,
    message,
    updated_at: new Date().toISOString()
  };

  fs.writeFileSync(PROGRESS_FILE, JSON.stringify(progress, null, 2), 'utf8');
}

// Helper to pad numbers (e.g. 1 -> 001)
const pad = (num, size) => String(num).padStart(size, '0');

function downloadPage(page) {
  return new Promise((resolve, reject) => {
    const destName = `page-${pad(page, 3)}.gif`;
    const destination = path.join(TARGET_DIR, destName);

    // If file already exists and is valid size, skip
    if (fs.existsSync(destination) && fs.statSync(destination).size > 1000) {
      downloaded++;
      resolve(true);
      return;
    }

    const assetNumber = page + 3;
    const url = `https://app.quranflash.com/book/Medina1/epub/EPUB/imgs/${pad(assetNumber, 4)}.gif`;
    const tempDest = `${destination}.part`;

    const options = {
      headers: {
        'User-Agent': 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36'
      }
    };

    const request = https.get(url, options, (response) => {
      if (response.statusCode !== 200) {
        reject(new Error(`Server returned status code ${response.statusCode}`));
        return;
      }

      const fileStream = fs.createWriteStream(tempDest);
      response.pipe(fileStream);

      fileStream.on('finish', () => {
        fileStream.close();
        if (fs.statSync(tempDest).size <= 1000) {
          fs.unlinkSync(tempDest);
          reject(new Error('Downloaded file is unexpectedly small'));
          return;
        }
        fs.renameSync(tempDest, destination);
        downloaded++;
        resolve(false); // false means actually downloaded (not skipped)
      });

      fileStream.on('error', (err) => {
        fs.unlinkSync(tempDest);
        reject(err);
      });
    });

    request.on('error', (err) => {
      if (fs.existsSync(tempDest)) fs.unlinkSync(tempDest);
      reject(err);
    });

    request.setTimeout(60000, () => {
      request.destroy();
      if (fs.existsSync(tempDest)) fs.unlinkSync(tempDest);
      reject(new Error('Download timeout'));
    });
  });
}

// Download coordinator with concurrency limit
async function startDownload() {
  console.log('🏁 Memulai download Mushaf Al-Madinah An-Nabawiyyah (HD)...');
  console.log(`📂 Folder tujuan: ${TARGET_DIR}\n`);
  writeProgress('running', 0);

  const queue = Array.from({ length: TOTAL_PAGES }, (_, i) => i + 1);
  const activeDownloads = new Set();
  
  // Progress print helper
  const printProgress = (currentPage) => {
    const percent = Math.round((downloaded / TOTAL_PAGES) * 100);
    const progressBar = '█'.repeat(Math.floor(percent / 5)) + '░'.repeat(20 - Math.floor(percent / 5));
    process.stdout.write(`\r[${progressBar}] ${percent}% | Halaman ${downloaded}/${TOTAL_PAGES} (Sedang memproses hal ${currentPage})`);
  };

  while (queue.length > 0 || activeDownloads.size > 0) {
    while (activeDownloads.size < CONCURRENCY && queue.length > 0) {
      const page = queue.shift();
      const promise = (async () => {
        let success = false;
        let attempts = 0;
        let lastError = '';

        while (!success && attempts < 3) {
          attempts++;
          try {
            await downloadPage(page);
            success = true;
          } catch (err) {
            lastError = err.message;
            // Backoff wait
            await new Promise(r => setTimeout(r, Math.min(2000 * attempts, 6000)));
          }
        }

        if (!success) {
          throw new Error(`Gagal download halaman ${page}: ${lastError}`);
        }
      })();

      activeDownloads.add(promise);
      
      // Update CLI print
      printProgress(page);

      promise.then(() => {
        activeDownloads.delete(promise);
        writeProgress('running', page);
      }).catch((err) => {
        writeProgress('failed', page, err.message);
        console.error('\n❌ Error:', err.message);
        process.exit(1);
      });
    }

    // Wait for at least one download to complete
    if (activeDownloads.size > 0) {
      await Promise.race(activeDownloads);
    }
  }

  writeProgress('complete', TOTAL_PAGES);
  console.log('\n\n✅ Download selesai! Semua halaman Mushaf Al-Madinah An-Nabawiyyah berhasil disimpan. ✨');
}

startDownload();
