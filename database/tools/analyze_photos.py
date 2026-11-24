import os
import glob
from PIL import Image

base_dir = "produk preloved"
results = []

for root, dirs, files in os.walk(base_dir):
    for f in sorted(files):
        path = os.path.join(root, f)
        folder = os.path.basename(root)
        size_bytes = os.path.getsize(path)
        is_corrupt = False
        img_format = ""
        width = 0
        height = 0
        try:
            with Image.open(path) as img:
                img_format = img.format
                width, height = img.size
                img.verify()
            # Reopen to test actual loading
            with Image.open(path) as img:
                img.load()
        except Exception as e:
            is_corrupt = True
            img_format = str(e)
            
        results.append({
            "folder": folder,
            "filename": f,
            "path": path,
            "format": img_format,
            "width": width,
            "height": height,
            "size_kb": round(size_bytes / 1024, 2),
            "size_mb": round(size_bytes / (1024 * 1024), 2),
            "corrupt": is_corrupt
        })

print(f"Total files found: {len(results)}")
print(f"Total folders: {len(set(r['folder'] for r in results))}")

for r in results:
    status = "OK" if not r["corrupt"] else "CORRUPT"
    print(f"[{status}] {r['folder']} | {r['filename']} | {r['format']} | {r['width']}x{r['height']} | {r['size_mb']} MB")
