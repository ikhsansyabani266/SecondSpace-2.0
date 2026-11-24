import os
from PIL import Image

base_dir = "produk preloved"
folders = sorted([d for d in os.listdir(base_dir) if os.path.isdir(os.path.join(base_dir, d))])

for folder in folders:
    fpath = os.path.join(base_dir, folder)
    imgs = sorted(os.listdir(fpath))
    print(f"\n==========================================")
    print(f"Folder: {folder} ({len(imgs)} files)")
    print(f"==========================================")
    for img_name in imgs:
        p = os.path.join(fpath, img_name)
        with Image.open(p) as im:
            thumb = im.convert("RGB").resize((128, 128))
            pixels = list(thumb.getdata())
            # count non-white pixels
            non_white = sum(1 for r, g, b in pixels if not (r > 240 and g > 240 and b > 240))
            ratio = non_white / len(pixels)
            
            # Check bounding box
            # get rows and cols that have non-white
            rows_with_garment = set()
            cols_with_garment = set()
            for idx, (r, g, b) in enumerate(pixels):
                if not (r > 240 and g > 240 and b > 240):
                    row = idx // 128
                    col = idx % 128
                    rows_with_garment.add(row)
                    cols_with_garment.add(col)
                    
            min_r = min(rows_with_garment) if rows_with_garment else 0
            max_r = max(rows_with_garment) if rows_with_garment else 127
            min_c = min(cols_with_garment) if cols_with_garment else 0
            max_c = max(cols_with_garment) if cols_with_garment else 127
            h_span = max_r - min_r
            w_span = max_c - min_c
            
            # center crop color variance (to see texture/detail vs whole garment)
            center_pixels = [pixels[r * 128 + c] for r in range(48, 80) for c in range(48, 80)]
            avg_r = sum(p[0] for p in center_pixels) / len(center_pixels)
            avg_g = sum(p[1] for p in center_pixels) / len(center_pixels)
            avg_b = sum(p[2] for p in center_pixels) / len(center_pixels)

            print(f"  {img_name}:")
            print(f"    Garment Coverage: {ratio:.1%} | BBox: W={w_span}, H={h_span} (rows {min_r}-{max_r}, cols {min_c}-{max_c})")
            print(f"    Center RGB: ({avg_r:.0f}, {avg_g:.0f}, {avg_b:.0f})")
