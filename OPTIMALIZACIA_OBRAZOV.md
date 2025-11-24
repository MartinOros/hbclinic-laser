# Optimalizácia obrázkov - Dôležité!

## Kritické obrázky na kompresiu:

### Veľmi veľké obrázky (potrebujú kompresiu):
1. **new_secon_section.png** - 9.8 MB → Cieľ: < 500 KB
2. **o-nas.png** - 6.4 MB → Cieľ: < 300 KB  
3. **team.png** - 4.4 MB → Cieľ: < 400 KB
4. **7d9ea3_6ab8012b5e1c47db8d464149d598b6f4~mv2 1.png** - 3.8 MB → Cieľ: < 300 KB
5. **diamanty.png** - 2.7 MB → Cieľ: < 200 KB

### Veľké obrázky (odporúčaná kompresia):
- podpazusie_8osetreni 1.png - 2.0 MB
- brazil_8osetreni 1.png - 2.0 MB
- krk.png - 1.8 MB
- lica.png - 1.6 MB
- landing_laserove_odstranenie-chlpkov_zeny 1.png - 1.6 MB
- brada.png - 1.5 MB
- stehna.png - 1.2 MB
- sedacia_cast.png - 1.2 MB
- ramena.png - 1.2 MB
- plecia.png - 1.2 MB
- podpazusie.png - 1.1 MB
- brucho.png - 1.1 MB

## Odporúčania:

### 1. Konvertovať na WebP formát
- WebP poskytuje o 25-35% lepšiu kompresiu ako PNG
- Použiť: `cwebp -q 80 input.png -o output.webp`
- Alebo online nástroj: https://squoosh.app/

### 2. Optimalizovať PNG obrázky
- Použiť: `pngquant --quality=65-80 input.png`
- Alebo: `optipng -o7 input.png`

### 3. Zmenšiť rozlíšenie
- Pre web stačí max 1920px šírka
- Pre mobilné zariadenia max 800px

### 4. Použiť responsive obrázky
```html
<picture>
  <source srcset="image-large.webp" media="(min-width: 1200px)">
  <source srcset="image-medium.webp" media="(min-width: 768px)">
  <img src="image-small.webp" alt="..." loading="lazy">
</picture>
```

### 5. Online nástroje:
- https://squoosh.app/ - Najlepší nástroj
- https://tinypng.com/ - Jednoduchá kompresia
- https://imageoptim.com/ - Pre Mac

## Očakávané zlepšenie:
- **Pred**: ~50 MB obrázkov
- **Po**: ~5-8 MB obrázkov
- **Zlepšenie**: 80-90% zmenšenie veľkosti
- **LCP zlepšenie**: Z 70s na < 3s

