import os
import json

base_dir = os.path.dirname(__file__)
pages = ['page-001.json', 'page-002.json']

# Calibration parameters for Page 1 & 2 circular frame offset
SCALE_Y = 1.3435
OFFSET_Y = 320.0

for page_file in pages:
    file_path = os.path.join(base_dir, '../web/public/mushaf/coordinates', page_file)
    if not os.path.exists(file_path):
        print(f"⚠️ File {page_file} tidak ditemukan!")
        continue
        
    print(f"⏳ Mengkalibrasi ulang koordinat {page_file}...")
    with open(file_path, 'r', encoding='utf-8') as f:
        data = json.load(f)
        
    for verse in data.get('verses', []):
        for glyph in verse.get('glyphs', []):
            # Recalibrate vertical coordinates
            glyph['min_y'] = int(glyph['min_y'] * SCALE_Y + OFFSET_Y)
            glyph['max_y'] = int(glyph['max_y'] * SCALE_Y + OFFSET_Y)
            
    with open(file_path, 'w', encoding='utf-8') as f:
        json.dump(data, f, ensure_ascii=False, indent=2)

print("✅ Kalibrasi ulang koordinat halaman 1 dan 2 selesai!")
