import os
from PIL import Image

# Check front features:
# For pants without leather patch (chino / cargo / track):
# Front has zipper fly / drawstring / button. Back has 2 welt/patch pockets.
# Let's check celana-15, celana-18, celana-21

def inspect_pant(p):
    d = Image.open(f"user/images/{p}-depan.webp")
    b = Image.open(f"user/images/{p}-belakang.webp")
    w, h = d.size
    # Center crotch/fly area (x: 45-55%, y: 15-40%)
    fly_d = d.crop((int(w*0.45), int(h*0.15), int(w*0.55), int(h*0.35)))
    fly_b = b.crop((int(w*0.45), int(h*0.15), int(w*0.55), int(h*0.35)))
    
    # Back pockets area (x: 20-40% and 60-80%, y: 15-35%)
    bp_d = d.crop((int(w*0.60), int(h*0.15), int(w*0.80), int(h*0.35)))
    bp_b = b.crop((int(w*0.60), int(h*0.15), int(w*0.80), int(h*0.35)))
    
    import statistics
    std_fly_d = statistics.pstdev([sum(px)//3 for px in fly_d.get_flattened_data()])
    std_fly_b = statistics.pstdev([sum(px)//3 for px in fly_b.get_flattened_data()])
    std_bp_d = statistics.pstdev([sum(px)//3 for px in bp_d.get_flattened_data()])
    std_bp_b = statistics.pstdev([sum(px)//3 for px in bp_b.get_flattened_data()])
    print(f"{p} -> Fly std (D vs B): {std_fly_d:.1f} vs {std_fly_b:.1f} | Pocket std (D vs B): {std_bp_d:.1f} vs {std_bp_b:.1f}")

for p in ["celana-15-track-pants-hitam", "celana-18-double-knee-canvas-cokelat", "celana-21-straight-leg-khaki"]:
    inspect_pant(p)

for b in ["baju-01-athletic-performance-hitam", "baju-10-polos-jet-black"]:
    d = Image.open(f"user/images/{b}-depan.webp")
    bk = Image.open(f"user/images/{b}-belakang.webp")
    w, h = d.size
    # Chest logo area (x: 40-75%, y: 20-35%)
    chest_d = d.crop((int(w*0.40), int(h*0.20), int(w*0.75), int(h*0.35)))
    chest_b = bk.crop((int(w*0.40), int(h*0.20), int(w*0.75), int(h*0.35)))
    import statistics
    std_d = statistics.pstdev([sum(px)//3 for px in chest_d.get_flattened_data()])
    std_b = statistics.pstdev([sum(px)//3 for px in chest_b.get_flattened_data()])
    print(f"{b} -> Chest std (D vs B): {std_d:.1f} vs {std_b:.1f}")
