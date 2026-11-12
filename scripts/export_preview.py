#!/usr/bin/env python3
"""Экспорт публичных страниц WordPress для GitHub Pages без базы и админки."""
import html
import json
import os
from pathlib import Path
import re
import shutil
from urllib.parse import urlparse, urljoin, quote, unquote
from urllib.request import urlopen

ROOT = Path(__file__).resolve().parents[1]
SOURCE = os.getenv('PREVIEW_SOURCE', 'http://localhost:8080').rstrip('/')
TARGET = os.getenv('PREVIEW_TARGET', 'https://mebsms21.github.io/Gorizont-web').rstrip('/')
BASE = urlparse(TARGET).path.rstrip('/')
OUT = ROOT / 'preview'
ROUTES = ['/', '/uslugi/', '/proekty/', '/o-studii/', '/kontakty/', '/privacy/']
ASSETS = set()


def fetch(path):
    with urlopen(SOURCE + quote(path, safe='/%?=&:+'), timeout=30) as response:
        return response.read()


def api(route):
    return json.loads(fetch('/wp-json/wp/v2/' + route))


def rewrite(text):
    text = text.replace(SOURCE, TARGET)
    text = re.sub(r'((?:href|src|action)=["\'])/(?!/)', lambda m: m[1] + BASE + '/', text)
    return text


def collect_assets(text):
    for match in re.findall(re.escape(SOURCE) + r'(/(?:wp-content|wp-includes)/[^\s"\'<>\)]+)', html.unescape(text)):
        ASSETS.add(urlparse(match).path)


def main():
    if OUT.exists(): shutil.rmtree(OUT)
    OUT.mkdir()
    routes = {path: path for path in ROUTES}
    for kind, prefix in [('g_service', '/usluga/'), ('g_project', '/proekt/')]:
        for post in api(kind + '?per_page=100'):
            path = prefix + post['slug'] + '/'
            routes[path] = path
    for taxonomy, prefix in [('g_project_type', '/proekty/'), ('g_service_type', '/uslugi/')]:
        for term in api(taxonomy + '?per_page=100&hide_empty=true'):
            routes[prefix + '?category=' + term['slug']] = prefix + 'filtr/' + term['slug'] + '/'
    replacements = {SOURCE + source: TARGET + destination for source, destination in routes.items() if '?' in source}
    for source, destination in routes.items():
        text = fetch(source).decode()
        text = re.sub(r'<link[^>]*dns-prefetch[^>]*>', '', text, flags=re.I)
        text = re.sub(r'<script\b[^>]*>.*?</script>', '', text, flags=re.S | re.I)
        text = re.sub(r'<link\b[^>]*(?:application/(?:rss\+xml|json)|api\.w\.org|shortlink|oembed)[^>]*>', '', text, flags=re.I)
        collect_assets(text)
        for old, new in replacements.items(): text = text.replace(old, new)
        text = rewrite(text)
        text = text.replace('</body>', '<script src="' + BASE + '/preview.js" defer></script></body>')
        output = OUT / unquote(destination).lstrip('/') / 'index.html'
        output.parent.mkdir(parents=True, exist_ok=True)
        output.write_text(text)
    visited = set()
    while ASSETS - visited:
        path = sorted(ASSETS - visited)[0]
        visited.add(path)
        if Path(path).suffix.lower() not in ('.css', '.js', '.woff', '.woff2', '.ttf', '.svg', '.webp', '.png', '.jpg', '.jpeg', '.gif', '.ico'):
            raise ValueError('Недопустимый тип ресурса: ' + path)
        data = fetch(path)
        if path.endswith('.css'):
            text = data.decode()
            collect_assets(text)
            for _, value in re.findall(r'url\(\s*(["\']?)(.*?)\1\s*\)', text):
                if value.startswith(('data:', '#')): continue
                resolved = urlparse(urljoin(SOURCE + path, value))
                if resolved.netloc != urlparse(SOURCE).netloc: raise ValueError('Внешний ресурс CSS')
                ASSETS.add(resolved.path)
            data = rewrite(text).encode()
        output = OUT / path.lstrip('/')
        output.parent.mkdir(parents=True, exist_ok=True)
        output.write_bytes(data)
    shutil.copy2(ROOT / 'scripts/preview.js', OUT / 'preview.js')
    (OUT / '.nojekyll').write_text('')
    (OUT / 'robots.txt').write_text('User-agent: *\nDisallow: /\n')
    (OUT / '404.html').write_text('<!doctype html><html lang="ru"><meta charset="utf-8"><meta name="viewport" content="width=device-width"><title>Страница не найдена — ГОРИЗОНТ</title><body style="background:#f5f3ef;color:#27282e;font-family:Arial;padding:10vw"><h1>Эта страница за горизонтом</h1><p><a href="' + BASE + '/">На главную</a></p></body></html>')
    print('Экспортировано страниц:', len(routes), 'ресурсов:', len(visited))


if __name__ == '__main__': main()
