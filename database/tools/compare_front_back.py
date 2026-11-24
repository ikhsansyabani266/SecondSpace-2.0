import os
from PIL import Image

base_dir = "produk preloved"
folders = sorted([d for d in os.listdir(base_dir) if os.path.isdir(os.path.join(base_dir, d))])

for folder in folders:
    fpath = os.path.join(base_dir, folder)
    imgs = sorted(os.listdir(fpath))
    print(f"\n==========================================")
    print(f"Folder: {folder}")
    print(f"==========================================")
    for img_name in imgs:
        p = os.path.join(fpath, img_name)
        with Image.open(p) as im:
            thumb = im.convert("RGB").resize((128, 128))
            
            # Neck hole analysis:
            # Look at row 15 to 35, column 54 to 74
            # In the back view, the back collar is fabric (non-white).
            # In the front view, the collar drops down, showing the inside back neck (often label/tag or darker inside shadow) or lower collar drop.
            
            # Let's check pixel lightness in neck area (rows 15-28, cols 58-70)
            neck_box = []
            for r in range(16, 26):
                for c in range(58, 70):
                    px = thumb.getpixel((c, r))
                    # luminance
                    lum = 0.299*px[0] + 0.587*px[1] + 0.114*px[2]
                    neck_box.append(lum)
            avg_neck_lum = sum(neck_box) / len(neck_box)
            
            # Chest area (rows 40-55, cols 50-78)
            chest_box = []
            for r in range(40, 55):
                for c in range(50, 78):
                    px = thumb.getpixel((c, r))
                    lum = 0.299*px[0] + 0.587*px[1] + 0.114*px[2]
                    chest_box.append(lum)
            avg_chest_lum = sum(chest_box) / len(chest_box)
            
            # Standard deviation / variance in chest (graphics/buttons have higher variance)
            chest_var = sum((x - avg_chest_lum)**2 for x in chest_box) / len(chest_box)

            print(f"  {img_name}:")
            print(f"    Neck Lum (rows 16-25): {avg_neck_lum:.1f} | Chest Var: {chest_var:.1f}")
