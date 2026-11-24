import os
import glob
from PIL import Image

def analyze_pants(prefix):
    depan = f"user/images/{prefix}-depan.webp"
    belakang = f"user/images/{prefix}-belakang.webp"
    if not os.path.exists(depan) or not os.path.exists(belakang):
        return
    im_d = Image.open(depan).convert('RGB')
    im_b = Image.open(belakang).convert('RGB')
    
    # Check top right waist for leather patch (x: 55-80%, y: 5-25%)
    w, h = im_d.size
    box_patch = (int(w*0.55), int(h*0.08), int(w*0.80), int(h*0.25))
    crop_d = im_d.crop(box_patch)
    crop_b = im_b.crop(box_patch)
    
    # Leather patch has high red/brown values: R > 130 and R > G + 20 and R > B + 30
    def patch_score(crop):
        pixels = list(crop.getdata())
        return sum(1 for r,g,b in pixels if r > 120 and r > g + 15 and r > b + 25)
        
    p_d = patch_score(crop_d)
    p_b = patch_score(crop_b)
    
    # Check front fly (center x: 45-55%, y: 15-35%)
    # Front usually has a vertical seam/zipper fly line with edge contrast
    print(f"[Pants {prefix}] Patch score - D: {p_d}, B: {p_b}")

def analyze_tops(prefix):
    depan = f"user/images/{prefix}-depan.webp"
    belakang = f"user/images/{prefix}-belakang.webp"
    if not os.path.exists(depan) or not os.path.exists(belakang):
        return
    im_d = Image.open(depan).convert('RGB')
    im_b = Image.open(belakang).convert('RGB')
    w, h = im_d.size
    
    # Collar box (x: 40-60%, y: 8-22%)
    # In front view of tee/shirt, collar dips lower in front, revealing inside back collar tag
    # In back view, back collar line is high and straight across
    box_collar = (int(w*0.38), int(h*0.08), int(w*0.62), int(h*0.25))
    c_d = im_d.crop(box_collar)
    c_b = im_b.crop(box_collar)
    
    # Placket / zipper box for jackets/shirts (x: 46-54%, y: 20-75%)
    box_placket = (int(w*0.46), int(h*0.20), int(w*0.54), int(h*0.75))
    pl_d = im_d.crop(box_placket)
    pl_b = im_b.crop(box_placket)
    
    def contrast(crop):
        import statistics
        vals = [sum(p)//3 for p in crop.getdata()]
        return statistics.pstdev(vals) if vals else 0
        
    print(f"[Top {prefix}] Collar std - D: {contrast(c_d):.1f}, B: {contrast(c_b):.1f} | Placket std - D: {contrast(pl_d):.1f}, B: {contrast(pl_b):.1f}")

all_prefixes = sorted(list(set(os.path.basename(f).rsplit('-', 1)[0] for f in glob.glob("user/images/*-depan.webp"))))
print(f"Total products found: {len(all_prefixes)}")

for p in all_prefixes:
    if 'celana' in p:
        analyze_pants(p)
    else:
        analyze_tops(p)
