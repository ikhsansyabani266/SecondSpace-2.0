import os
from PIL import Image

# Function to check the depth of collar dip
# We scan column 64 from row 10 to row 40.
# The row where color changes from white to garment color tells us where the collar begins.
# Front collar begins lower (higher row number) than back collar!
def get_collar_start_row(im):
    thumb = im.convert("RGB").resize((128, 128))
    for r in range(5, 50):
        px = thumb.getpixel((64, r))
        # if not white
        if not (px[0] > 235 and px[1] > 235 and px[2] > 235):
            return r
    return 50

base_dir = "produk preloved"
for folder in sorted(os.listdir(base_dir)):
    fpath = os.path.join(base_dir, folder)
    if not os.path.isdir(fpath): continue
    print(f"\nFolder: {folder}")
    for f in sorted(os.listdir(fpath)):
        with Image.open(os.path.join(fpath, f)) as im:
            row = get_collar_start_row(im)
            print(f"  {f}: collar top starts at row {row}")
