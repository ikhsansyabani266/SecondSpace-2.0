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

# We want to inspect each product and identify visual indicators:
# For shirts / jackets / tees / pants
results = []
for code, name in products:
    d_list = glob.glob(f'user/images/{code}-*-depan.webp')
    b_list = glob.glob(f'user/images/{code}-*-belakang.webp')
    det_list = glob.glob(f'user/images/{code}-*-detail.webp')
    if not d_list or not b_list or not det_list:
        continue
    d_file = d_list[0]
    b_file = b_list[0]
    det_file = det_list[0]
    results.append({
        'code': code,
        'name': name,
        'depan': d_file.replace('\\', '/'),
        'belakang': b_file.replace('\\', '/'),
        'detail': det_file.replace('\\', '/')
    })

with open('database/tools/all_pairs.json', 'w') as f:
    import json
    json.dump(results, f, indent=2)

print(f"Dumped {len(results)} product pairs to all_pairs.json")
