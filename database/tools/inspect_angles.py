import os
from PIL import Image
import numpy as np

# We can analyze the images by looking at bounding boxes of the garment, color distributions, etc.
base_dir = "produk preloved"
folders = sorted([d for d in os.listdir(base_dir) if os.path.isdir(os.path.join(base_dir, d))])

for folder in folders:
    fpath = os.path.join(base_dir, folder)
    imgs = sorted(os.listdir(fpath))
    print(f"\n==========================================")
    print(f"Folder: {folder} (Total: {len(imgs)})")
    print(f"==========================================")
    for img_name in imgs:
        p = os.path.join(fpath, img_name)
        with Image.open(p) as im:
            # im is 2048x2048 RGB
            # resize to 256x256 for quick analysis
            thumb = im.convert("RGB").resize((256, 256))
            arr = np.array(thumb)
            
            # Estimate white background mask (pixels near 255, 255, 255)
            # usually flatlay on pure white has R>240, G>240, B>240
            bg_mask = (arr[:, :, 0] > 235) & (arr[:, :, 1] > 235) & (arr[:, :, 2] > 235)
            bg_ratio = np.mean(bg_mask)
            garment_ratio = 1.0 - bg_ratio
            
            # Look at top collar area (rows 20-70, cols 80-176)
            collar_area = arr[20:70, 80:176]
            
            print(f"File: {img_name}")
            print(f"  Garment Ratio: {garment_ratio:.2%}")
