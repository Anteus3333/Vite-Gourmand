import os

base = os.path.join(os.path.dirname(__file__), '..', 'public', 'images')
os.makedirs(os.path.join(base, 'menus'), exist_ok=True)
os.makedirs(os.path.join(base, 'plats'), exist_ok=True)


def svg_menu(n, title, c1, c2, accent='#d4af37'):
    return f'''<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 800 500" role="img" aria-label="{title}">
  <defs><linearGradient id="g{n}" x1="0" y1="0" x2="1" y2="1"><stop offset="0%" stop-color="{c1}"/><stop offset="100%" stop-color="{c2}"/></linearGradient></defs>
  <rect width="800" height="500" fill="url(#g{n})"/>
  <circle cx="650" cy="120" r="80" fill="rgba(255,255,255,.12)"/>
  <circle cx="120" cy="380" r="60" fill="{accent}" fill-opacity=".25"/>
  <text x="40" y="440" font-family="Georgia,serif" font-size="42" fill="#fff" font-weight="bold">{title}</text>
</svg>'''


menus = [
    (1, 'Menu Noël', '#6b0f0f', '#8b1c1c'),
    (2, 'Menu Pâques', '#2d6a4f', '#40916c'),
    (3, 'Menu Gourmet', '#1a1a2e', '#4a4e69'),
    (4, 'Menu Végétarien', '#386641', '#6a994e'),
    (5, 'Menu Végan', '#0077b6', '#48cae4'),
    (6, 'Sans gluten', '#774936', '#a68a64'),
    (7, 'Dégustation', '#8b1c1c', '#b32424'),
    (8, 'Fruits de mer', '#023047', '#219ebc'),
]

vues = [
    ('', '', '#d4af37'),
    (' — vue 2', True, '#c9a227'),
    (' — vue 3', False, '#e8d5a3'),
    (' — vue 4', True, '#fff'),
]

for n, t, c1, c2 in menus:
    for i, (suffix, invert, accent) in enumerate(vues, start=1):
        title = t + suffix
        colors = (c2, c1) if invert else (c1, c2)
        filename = f'menu-{n}.svg' if i == 1 else f'menu-{n}-{i}.svg'
        with open(os.path.join(base, 'menus', filename), 'w', encoding='utf-8') as f:
            f.write(svg_menu(f'{n}{i}', title, colors[0], colors[1], accent))

with open(os.path.join(base, 'menus', 'default.svg'), 'w', encoding='utf-8') as f:
    f.write(svg_menu(0, 'Menu traiteur', '#666', '#999'))

banner = '''<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1600 400" aria-hidden="true">
  <defs><linearGradient id="bg" x1="0" y1="0" x2="1" y2="1"><stop offset="0%" stop-color="#6b0f0f"/><stop offset="50%" stop-color="#8b1c1c"/><stop offset="100%" stop-color="#4a1515"/></linearGradient></defs>
  <rect width="1600" height="400" fill="url(#bg)"/>
  <circle cx="1400" cy="80" r="160" fill="rgba(212,175,55,.15)"/>
  <circle cx="200" cy="320" r="100" fill="rgba(255,255,255,.06)"/>
  <circle cx="800" cy="200" r="220" fill="rgba(255,255,255,.04)"/>
</svg>'''
with open(os.path.join(base, 'banner-accueil.svg'), 'w', encoding='utf-8') as f:
    f.write(banner)

favicon = '''<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32"><rect width="32" height="32" rx="6" fill="#8b1c1c"/><text x="16" y="22" text-anchor="middle" font-family="Georgia,serif" font-size="18" fill="#d4af37">V</text></svg>'''
with open(os.path.join(base, 'favicon.svg'), 'w', encoding='utf-8') as f:
    f.write(favicon)

for i in range(1, 16):
    c = ['#8b1c1c', '#2d6a4f', '#774936', '#023047', '#6a994e'][i % 5]
    plat = f'''<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 400 300" role="img" aria-label="Plat">
  <rect width="400" height="300" fill="#f5f0eb"/>
  <ellipse cx="200" cy="160" rx="140" ry="90" fill="{c}" opacity=".85"/>
  <ellipse cx="200" cy="150" rx="100" ry="60" fill="#fff" opacity=".2"/>
  <circle cx="200" cy="130" r="35" fill="rgba(212,175,55,.6)"/>
</svg>'''
    with open(os.path.join(base, 'plats', f'plat-{i:02d}.svg'), 'w', encoding='utf-8') as f:
        f.write(plat)

print('Images générées dans', base)
