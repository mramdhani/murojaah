#!/usr/bin/env node
/**
 * Download QCF V2 Fonts (p1.woff2 — p604.woff2)
 * from jsDelivr CDN to web/public/fonts/qcf/
 *
 * Usage: node scripts/download-qcf-fonts.js
 * Estimated time: ~2–4 minutes depending on connection
 */

const https = require('https');
const fs = require('fs');
const path = require('path');

const OUTPUT_DIR = path.join(__dirname, '../web/public/fonts/qcf');
const TOTAL_PAGES = 604;
const CONCURRENCY = 10; // Download 10 fonts at a time
// Official CDN from Quran Foundation — confirmed working
const BASE_URLS = [
  (page) => `https://verses.quran.foundation/fonts/quran/hafs/v2/woff2/p${page}.woff2`,
];

// Ensure output directory exists
fs.mkdirSync(OUTPUT_DIR, { recursive: true });

function downloadFile(url, dest) {
  return new Promise((resolve, reject) => {
    // Skip if already downloaded
    if (fs.existsSync(dest)) {
      resolve(false); // false = skipped
      return;
    }

    const file = fs.createWriteStream(dest);
    const req = https.get(url, (res) => {
      if (res.statusCode === 302 || res.statusCode === 301) {
        // Follow redirect
        file.close();
        fs.unlinkSync(dest);
        downloadFile(res.headers.location, dest).then(resolve).catch(reject);
        return;
      }
      if (res.statusCode !== 200) {
        file.close();
        fs.unlink(dest, () => {});
        reject(new Error(`HTTP ${res.statusCode} for ${url}`));
        return;
      }
      res.pipe(file);
      file.on('finish', () => { file.close(); resolve(true); });
    });

    req.on('error', (err) => {
      file.close();
      fs.unlink(dest, () => {});
      reject(err);
    });

    req.setTimeout(30000, () => {
      req.destroy();
      reject(new Error(`Timeout for page ${dest}`));
    });
  });
}

async function downloadBatch(pages) {
  return Promise.allSettled(pages.map(async (page) => {
    const dest = path.join(OUTPUT_DIR, `p${page}.woff2`);
    // Generate all possible URLs for this page
    const urls = BASE_URLS.map(fn => fn(page));

    for (const url of urls) {
      try {
        const downloaded = await downloadFile(url, dest);
        return { page, status: downloaded ? 'downloaded' : 'skipped' };
      } catch (e) {
        // Try next URL
      }
    }
    return { page, status: 'failed' };
  }));
}

async function main() {
  console.log(`🕌 Downloading ${TOTAL_PAGES} QCF V2 font files...`);
  console.log(`   Output: ${OUTPUT_DIR}\n`);

  const allPages = Array.from({ length: TOTAL_PAGES }, (_, i) => i + 1);
  let downloaded = 0, skipped = 0, failed = [];

  // Process in batches
  for (let i = 0; i < allPages.length; i += CONCURRENCY) {
    const batch = allPages.slice(i, i + CONCURRENCY);
    const results = await downloadBatch(batch);

    for (const result of results) {
      if (result.status === 'fulfilled') {
        const { page, status } = result.value;
        if (status === 'downloaded') downloaded++;
        else if (status === 'skipped') skipped++;
        else failed.push(page);
      } else {
        failed.push(batch[results.indexOf(result)]);
      }
    }

    const done = Math.min(i + CONCURRENCY, TOTAL_PAGES);
    process.stdout.write(`\r   Progress: ${done}/${TOTAL_PAGES} — ✓ ${downloaded} new, ⊘ ${skipped} cached, ✗ ${failed.length} failed`);
  }

  console.log(`\n\n✅ Complete!`);
  console.log(`   Downloaded : ${downloaded}`);
  console.log(`   Skipped    : ${skipped} (already existed)`);
  console.log(`   Failed     : ${failed.length}${failed.length > 0 ? ' — pages: ' + failed.join(', ') : ''}`);

  if (failed.length > 0) {
    console.log('\n⚠️  Some fonts failed. Try running the script again to retry.');
    process.exit(1);
  }
}

main().catch(console.error);
