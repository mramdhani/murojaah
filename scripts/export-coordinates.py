import os
import sqlite3
import json

db_path = os.path.join(os.path.dirname(__file__), 'ayahinfo_1260.db')
target_dir = os.path.join(os.path.dirname(__file__), '../web/public/mushaf/coordinates')

# Create target directory if it doesn't exist
if not os.path.exists(target_dir):
    os.makedirs(target_dir)

print("⏳ Menghubungkan ke database SQLite...")
conn = sqlite3.connect(db_path)
cursor = conn.cursor()

print("⏳ Mengambil data koordinat...")
cursor.execute("""
    SELECT page_number, sura_number, ayah_number, line_number, min_x, max_x, min_y, max_y, position
    FROM glyphs
    ORDER BY page_number, sura_number, ayah_number, line_number, position
""")

rows = cursor.fetchall()
conn.close()

# Group data by page
pages_data = {}
for r in rows:
    page, sura, ayah, line, min_x, max_x, min_y, max_y, pos = r
    
    if page not in pages_data:
        pages_data[page] = {}
        
    verse_key = f"{sura}:{ayah}"
    if verse_key not in pages_data[page]:
        pages_data[page][verse_key] = {
            "sura": sura,
            "ayah": ayah,
            "glyphs": []
        }
        
    pages_data[page][verse_key]["glyphs"].append({
        "line": line,
        "min_x": min_x,
        "max_x": max_x,
        "min_y": min_y,
        "max_y": max_y
    })

print("⏳ Mengekspor data ke JSON per halaman...")
for page in range(1, 605):
    page_verses = []
    if page in pages_data:
        # Sort keys to ensure verses are in order
        sorted_keys = sorted(pages_data[page].keys(), key=lambda k: [int(x) for x in k.split(':')])
        for key in sorted_keys:
            page_verses.append(pages_data[page][key])
            
    page_filename = f"page-{str(page).zfill(3)}.json"
    dest_path = os.path.join(target_dir, page_filename)
    
    with open(dest_path, 'w', encoding='utf-8') as f:
        json.dump({
            "page": page,
            "verses": page_verses
        }, f, ensure_ascii=False, indent=2)

print("✅ Ekspor koordinat selesai! File disimpan di web/public/mushaf/coordinates/")
