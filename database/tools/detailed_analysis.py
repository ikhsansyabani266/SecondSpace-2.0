import os
from PIL import Image

base_dir = "produk preloved"

def analyze_pair(folder, part1, part2):
    fpath = os.path.join(base_dir, folder)
    files = os.listdir(fpath)
    f1 = [f for f in files if part1 in f][0]
    f2 = [f for f in files if part2 in f][0]
    p1 = os.path.join(fpath, f1)
    p2 = os.path.join(fpath, f2)
    with Image.open(p1) as im1, Image.open(p2) as im2:
        c1 = im1.crop((750, 200, 1300, 600)).convert("L")
        c2 = im2.crop((750, 200, 1300, 600)).convert("L")
        v1 = sum((p - 128)**2 for p in c1.getdata()) / len(list(c1.getdata()))
        v2 = sum((p - 128)**2 for p in c2.getdata()) / len(list(c2.getdata()))
        ch1 = im1.crop((800, 700, 1250, 1100)).convert("L")
        ch2 = im2.crop((800, 700, 1250, 1100)).convert("L")
        cv1 = sum((p - 128)**2 for p in ch1.getdata()) / len(list(ch1.getdata()))
        cv2 = sum((p - 128)**2 for p in ch2.getdata()) / len(list(ch2.getdata()))
        return f1, f2, v1, v2, cv1, cv2

pairs = [
    ("Black athletic performance t-shirt", "4r4ms9", "bezr28"),
    ("Blue and white striped shirt (kemeja)", "76vrg4", "i8i0lt"),
    ("Gray cotton t-shirt", "mkf49d", "tj499k"),
    ("Heather gray heavyweight cotton t-shirt", "7k2190", "i9iz6e"),
    ("Khaki work shirt(kemeja)", "d3672k", "rquwee"),
    ("Navy blue striped t-shirt", "8rfmew", "cr67dn"),
    ("Navy piqué polo shirt", "2zctpj", "z8aw7f"),
    ("Olive green cotton t-shirt", "5ha9nr", "rphec1"),
    ("Oversized white cotton t-shirt with chest print", "oiozbe", "qjh2so"),
    ("Plain black cotton t-shirt", "dw3ob0", "j68y4p"),
    ("White cotton t-shirt", "cx6y6f", "ddv3q4"),
    ("White oxford button-down shirt (kemeja)", "8fa90e", "dpewm4"),
]

for folder, p1, p2 in pairs:
    f1, f2, v1, v2, cv1, cv2 = analyze_pair(folder, p1, p2)
    print(f"\n[{folder}]")
    print(f"  {f1} -> collar_var={v1:.1f}, chest_var={cv1:.1f}")
    print(f"  {f2} -> collar_var={v2:.1f}, chest_var={cv2:.1f}")
