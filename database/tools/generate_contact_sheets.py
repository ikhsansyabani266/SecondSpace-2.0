import os
from PIL import Image

base_dir = "produk preloved"
out_dir = "database/tools/crops"
os.makedirs(out_dir, exist_ok=True)

# Generate a 3-in-1 contact sheet for each product
for folder in sorted(os.listdir(base_dir)):
    fpath = os.path.join(base_dir, folder)
    if not os.path.isdir(fpath): continue
    files = sorted(os.listdir(fpath))
    
    # create canvas: width = 300 * len(files), height = 300
    canvas = Image.new("RGB", (300 * len(files), 340), "white")
    
    for idx, f in enumerate(files):
        with Image.open(os.path.join(fpath, f)) as im:
            thumb = im.resize((300, 300))
            canvas.paste(thumb, (idx * 300, 0))
            
    clean_name = folder.replace(" ", "_").replace("(", "").replace(")", "")
    canvas.save(os.path.join(out_dir, f"{clean_name}.jpg"), quality=85)

print("Contact sheets generated in database/tools/crops")
