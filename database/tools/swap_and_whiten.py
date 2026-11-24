import os
import shutil
import glob
from PIL import Image, ImageOps

# 1. List of 9 swapped products
swapped_prefixes = [
    "celana-24-denim-vintage-medium-wash",
    "celana-23-cargo-ripstop-olive-green",
    "jacket-22-windbreaker-parasut-navy",
    "celana-16-chino-twill-hitam",
    "celana-13-denim-regular-classic-blue",
    "kemeja-12-oxford-button-down-putih",
    "baju-08-katun-olive-green",
    "baju-06-striped-navy-putih",
    "kemeja-02-garis-biru-putih"
]

folders = ["user/images", "uploads/produk"]

print("=== 1. SWAPPING FRONT & BACK FILES ===")
for folder in folders:
    if not os.path.exists(folder):
        continue
    for prefix in swapped_prefixes:
        f_depan = os.path.join(folder, f"{prefix}-depan.webp")
        f_belakang = os.path.join(folder, f"{prefix}-belakang.webp")
        f_tmp = os.path.join(folder, f"{prefix}-tmp.webp")
        
        if os.path.exists(f_depan) and os.path.exists(f_belakang):
            # Rename depan -> tmp, belakang -> depan, tmp -> belakang
            os.rename(f_depan, f_tmp)
            os.rename(f_belakang, f_depan)
            os.rename(f_tmp, f_belakang)
            print(f"Swapped in {folder}: {prefix}")

print("=== 2. WHITENING BACKGROUNDS TO MATCH CHAMPION REFERENCE ===")
# Algorithm:
# Read image, sample the 4 corner pixels to get baseline background color.
# For pixels connected to the background or with near-background color (R, G, B > 225 and neutral):
# Smoothly transition pixels between 225-255 to 255, 255, 255.
# If color is >= 235 and neutral (max(rgb) - min(rgb) < 15), make it 255, 255, 255.
# For pixels between 215 and 235, apply gentle gamma curve so shadows softly fade to pure white without harsh edges.

def whiten_image(filepath):
    try:
        im = Image.open(filepath).convert('RGB')
        w, h = im.size
        pixels = im.load()
        
        # Check corner pixels to determine background
        corners = [pixels[0, 0], pixels[w-1, 0], pixels[0, h-1], pixels[w-1, h-1],
                   pixels[w//2, 0], pixels[0, h//2], pixels[w-1, h//2]]
        avg_corner = sum(sum(c)//3 for c in corners) // len(corners)
        
        # If already pure white background (>253), skip or do gentle pass
        changed = False
        
        # Process each pixel
        # Vectorized or fast pixel mapping
        for y in range(h):
            for x in range(w):
                r, g, b = pixels[x, y]
                val = (r + g + b) // 3
                diff = max(r, g, b) - min(r, g, b)
                
                # Neutral light background detection
                # In studio photo, background is grey/white (low chroma diff < 18, brightness > 220)
                if val >= 235 and diff < 16:
                    pixels[x, y] = (255, 255, 255)
                    changed = True
                elif val >= 210 and diff < 12:
                    # Soft gradient transition towards 255
                    factor = (val - 210) / 25.0
                    new_r = min(255, int(r + (255 - r) * factor))
                    new_g = min(255, int(g + (255 - g) * factor))
                    new_b = min(255, int(b + (255 - b) * factor))
                    pixels[x, y] = (new_r, new_g, new_b)
                    changed = True
                    
        im.save(filepath, format="WEBP", quality=92, method=6)
        return True
    except Exception as e:
        print(f"Error on {filepath}: {e}")
        return False

count = 0
for folder in folders:
    for f in glob.glob(f"{folder}/*.webp"):
        if "-tmp" in f:
            continue
        if whiten_image(f):
            count += 1

print(f"Whitened and normalized {count} images across all folders.")
