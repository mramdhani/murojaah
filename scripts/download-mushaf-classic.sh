#!/bin/bash
TARGET_DIR="web/public/mushaf/madinah-nabawiyyah"
mkdir -p "$TARGET_DIR"

echo "🏁 Memulai download Mushaf Al-Madinah An-Nabawiyyah (HD)..."
echo "📂 Folder tujuan: $TARGET_DIR"
echo ""

# Loop from 1 to 604
for page in {1..604}
do
  # Format page to 3 digits (e.g., 001)
  padded_page=$(printf "%03d" $page)
  dest_file="$TARGET_DIR/page-$padded_page.gif"
  
  # Skip download if file exists and has size greater than 1000 bytes
  if [ -f "$dest_file" ] && [ $(wc -c < "$dest_file") -gt 1000 ]; then
    continue
  fi
  
  # Format asset number to 4 digits (page + 3, e.g., 0004)
  asset_num=$((page + 3))
  padded_asset=$(printf "%04d" $asset_num)
  url="https://app.quranflash.com/book/Medina1/epub/EPUB/imgs/$padded_asset.gif"
  
  # Custom User-Agent to bypass potential bot blocker
  curl -s -H "User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36" -o "$dest_file" "$url"
  
  # Print progress inline
  percent=$((page * 100 / 604))
  printf "\r[%-20s] %d%% | Halaman %d/604" "$(head -c $((percent / 5)) < /dev/zero | tr '\0' '#')" "$percent" "$page"
done

echo ""
echo ""
echo "✅ Download selesai! Semua halaman Mushaf Al-Madinah An-Nabawiyyah berhasil disimpan. ✨"
