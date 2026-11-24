import os, glob
from PIL import Image

products = [
    ('baju-01', 'Kaos Athletic Performance Hitam'),
    ('kemeja-02', 'Kemeja Salur Biru Putih'),
    ('baju-03', 'Kaos Polos Abu-Abu Katun'),
    ('baju-04', 'Kaos Heavyweight Heather Gray'),
    ('kemeja-05', 'Kemeja Workwear Khaki'),
    ('baju-06', 'Kaos Salur Navy Putih'),
    ('baju-07', 'Kaos Polo Pique Navy'),
    ('baju-08', 'Kaos Polos Olive Green'),
    ('baju-09', 'Kaos Oversized Graphic Putih'),
    ('baju-10', 'Kaos Polos Jet Black'),
    ('baju-11', 'Kaos Polos Putih Classic'),
    ('kemeja-12', 'Kemeja Oxford Button-Down Putih'),
    ('celana-13', 'Celana Denim Regular Classic Blue'),
    ('jacket-14', 'Jacket Puffer Down Hitam'),
    ('celana-15', 'Celana Track Pants Hitam'),
    ('celana-16', 'Celana Chino Twill Hitam'),
    ('jacket-17', 'Jacket Denim Trucker Washed Black'),
    ('celana-18', 'Celana Double Knee Canvas Cokelat'),
    ('jacket-19', 'Jacket Chore Coat Canvas Cokelat'),
    ('jacket-20', 'Jacket Fleece Sherpa Forest Green'),
    ('celana-21', 'Celana Straight Leg Khaki'),
    ('jacket-22', 'Jacket Windbreaker Parasut Navy'),
    ('celana-23', 'Celana Cargo Ripstop Olive Green'),
    ('celana-24', 'Celana Denim Vintage Medium Wash')
]

for code, name in products:
    d_list = glob.glob(f'user/images/{code}-*-depan.webp')
    b_list = glob.glob(f'user/images/{code}-*-belakang.webp')
    if not d_list or not b_list:
        continue
    d_path = d_list[0]
    b_path = b_list[0]
    
    im_d = Image.open(d_path).convert('RGB')
    im_b = Image.open(b_path).convert('RGB')
    w, h = im_d.size
    category = code.split('-')[0]
    
    print(f"==========================================")
    print(f"Product: {code} ({name})")
    print(f"  Depan: {os.path.basename(d_path)}")
    print(f"  Belakang: {os.path.basename(b_path)}")
    
    if category == 'celana':
        def has_leather_patch(im):
            patch_pixels = 0
            for y in range(int(h * 0.08), int(h * 0.22)):
                for x in range(int(w * 0.50), int(w * 0.70)):
                    r, g, b = im.getpixel((x, y))
                    if r > 130 and g > 70 and b < 130 and (r - b) > 40:
                        patch_pixels += 1
            return patch_pixels

        def fly_contrast(im):
            # check center crotch vertical
            c = 0
            for y in range(int(h*0.14), int(h*0.32), 3):
                for x in range(int(w*0.48), int(w*0.53)):
                    r1 = im.getpixel((x, y))[0]
                    r2 = im.getpixel((x+2, y))[0]
                    c += abs(r1 - r2)
            return c

        patch_d = has_leather_patch(im_d)
        patch_b = has_leather_patch(im_b)
        fly_d = fly_contrast(im_d)
        fly_b = fly_contrast(im_b)
        print(f"  Patch: D={patch_d}, B={patch_b} | Fly: D={fly_d}, B={fly_b}")

    elif category == 'kemeja':
        def placket_var(im):
            v = 0
            prev = im.getpixel((int(w*0.5), int(h*0.2)))[0]
            for y in range(int(h*0.2), int(h*0.75), 3):
                cur = im.getpixel((int(w*0.5), y))[0]
                v += abs(cur - prev)
                prev = cur
            return v
        print(f"  Button Placket Variance: D={placket_var(im_d)}, B={placket_var(im_b)}")

    elif category == 'jacket':
        def zipper_var(im):
            v = 0
            prev = im.getpixel((int(w*0.5), int(h*0.2)))[0]
            for y in range(int(h*0.2), int(h*0.8), 3):
                cur = im.getpixel((int(w*0.5), y))[0]
                v += abs(cur - prev)
                prev = cur
            return v
        print(f"  Center Zipper Variance: D={zipper_var(im_d)}, B={zipper_var(im_b)}")

    elif category == 'baju':
        def collar_depth(im):
            for y in range(int(h*0.05), int(h*0.35)):
                r, g, b = im.getpixel((int(w*0.5), y))
                if r < 240 or g < 240 or b < 240:
                    return y
            return 0
        def chest_saturation(im):
            sat = 0
            for y in range(int(h*0.2), int(h*0.45), 5):
                for x in range(int(w*0.35), int(w*0.65), 5):
                    r, g, b = im.getpixel((x, y))
                    sat += max(r,g,b) - min(r,g,b)
            return sat
        print(f"  Collar Depth (deeper=front): D={collar_depth(im_d)}, B={collar_depth(im_b)} | Chest Sat: D={chest_saturation(im_d)}, B={chest_saturation(im_b)}")
