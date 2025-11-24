#!/usr/bin/env python3
import os
import re
from pathlib import Path

# Zozbieraj všetky obrázky v priečinku images
images_dir = Path("images")
all_images = set(f.name for f in images_dir.iterdir() if f.is_file())

# Zozbieraj všetky používané obrázky z HTML a CSS súborov
used_images = set()

# HTML súbory
html_files = ["index.html", "cennik.html", "kontakt.html", "o-nas.html", 
              "ochrana-osobnych-udajov.html", "sluzby.html", "404.html"]

for html_file in html_files:
    if os.path.exists(html_file):
        with open(html_file, 'r', encoding='utf-8') as f:
            content = f.read()
            # Nájdeme všetky odkazy na obrázky - rôzne formáty
            patterns = [
                r'images/([^\s"\'<>)]+)',  # základný pattern
                r'"images/([^"]+)"',        # v úvodzovkách
                r"'images/([^']+)'",       # v apostrofoch
                r'images/([^\s<>)]+)',     # bez úvodzoviek
            ]
            for pattern in patterns:
                matches = re.findall(pattern, content)
                used_images.update(matches)

# CSS súbory
css_files = ["css/custom.css"]
for css_file in css_files:
    if os.path.exists(css_file):
        with open(css_file, 'r', encoding='utf-8') as f:
            content = f.read()
            # Nájdeme všetky odkazy na obrázky v CSS
            patterns = [
                r"images/([^\s'\"<>)]+)",
                r"url\(['\"]?\.\./images/([^'\"\s)]+)",
            ]
            for pattern in patterns:
                matches = re.findall(pattern, content)
                used_images.update(matches)

# Normalizuj názvy (odstráň query stringy, fragmenty, whitespace)
used_images_normalized = set()
for img in used_images:
    # Odstráň query stringy a fragmenty
    img = img.split('?')[0].split('#')[0]
    # Odstráň whitespace na začiatku a konci
    img = img.strip()
    if img:
        used_images_normalized.add(img)

# Nájdeme nepoužívané obrázky
unused_images = all_images - used_images_normalized

print("=" * 60)
print(f"Celkovo obrázkov v priečinku: {len(all_images)}")
print(f"Používaných obrázkov: {len(used_images_normalized)}")
print(f"Nepoužívaných obrázkov: {len(unused_images)}")
print("=" * 60)

if unused_images:
    print("\nNEPOUŽÍVANÉ OBRÁZKY (na odstránenie):")
    print("-" * 60)
    for img in sorted(unused_images):
        print(f"  images/{img}")
else:
    print("\nVšetky obrázky sa používajú!")

print("\n" + "=" * 60)
print("Používané obrázky (pre kontrolu):")
print("-" * 60)
for img in sorted(used_images_normalized):
    print(f"  {img}")

