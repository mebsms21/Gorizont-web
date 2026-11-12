#!/usr/bin/env python3
"""Загрузить только собственную тему и плагин по FTPS с проверкой сертификата."""
from __future__ import annotations

import argparse
import ftplib
import os
from pathlib import Path, PurePosixPath
import secrets
import ssl
import sys

PROJECT = Path(__file__).resolve().parents[1]
CONTENT = PROJECT / 'wp-content'
ALLOWED = ('themes/gorizont', 'plugins/gorizont-core')


def remote_root(value: str) -> PurePosixPath:
    parts = value.strip('/').split('/')
    if not parts or parts[-1] != 'htdocs' or any(
        not part or part in ('.', '..') or any(c in part for c in '\\\r\n\x00')
        for part in parts
    ):
        raise ValueError('FTP_ROOT должен указывать на htdocs или domain/htdocs без ..')
    return PurePosixPath('/', *parts, 'wp-content')


def manifest() -> list[tuple[Path, PurePosixPath]]:
    files = []
    for directory in ALLOWED:
        for local in sorted((CONTENT / directory).rglob('*')):
            if local.is_symlink():
                raise ValueError('Символические ссылки запрещены в пакете загрузки')
            if local.is_file():
                relative = PurePosixPath(local.relative_to(CONTENT).as_posix())
                if local.name.startswith('.') or local.suffix in ('.sql', '.log', '.env'):
                    raise ValueError('Служебный файл запрещён в пакете загрузки')
                files.append((local, relative))
    if not files:
        raise ValueError('Нет файлов темы и плагина')
    return files


def ensure_directory(ftp: ftplib.FTP_TLS, target: PurePosixPath) -> None:
    ftp.cwd('/')
    for part in target.parts[1:]:
        try:
            ftp.cwd(part)
        except ftplib.error_perm:
            ftp.mkd(part)
            ftp.cwd(part)


def deploy(ftp: ftplib.FTP_TLS, root: PurePosixPath, files) -> int:
    suffix = secrets.token_hex(8)
    for local, relative in files:
        target = root / relative
        ensure_directory(ftp, target.parent)
        temporary = target.name + '.gorizont-' + suffix + '.tmp'
        with local.open('rb') as stream:
            ftp.storbinary('STOR ' + temporary, stream)
        if ftp.size(temporary) != local.stat().st_size:
            raise RuntimeError('Размер загруженного файла не совпадает')
        ftp.rename(temporary, target.name)
        print('Загружено:', relative)
    return len(files)


def main() -> int:
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--dry-run', action='store_true')
    args = parser.parse_args()
    root = remote_root(os.environ.get('GORIZONT_FTP_ROOT', 'htdocs'))
    files = manifest()
    if args.dry_run:
        for _, relative in files:
            print(root / relative)
        print('План:', len(files), 'файлов. Подключение не выполнялось.')
        return 0
    required = ('GORIZONT_FTP_HOST', 'GORIZONT_FTP_USER', 'GORIZONT_FTP_PASSWORD')
    if any(not os.environ.get(name) for name in required):
        raise ValueError('Добавьте FTP_HOST, FTP_USER и FTP_PASSWORD в секреты GitHub')
    context = ssl.create_default_context()
    context.minimum_version = ssl.TLSVersion.TLSv1_2
    with ftplib.FTP_TLS(context=context, timeout=60) as ftp:
        ftp.connect(os.environ['GORIZONT_FTP_HOST'], 21)
        ftp.auth()
        ftp.login(os.environ['GORIZONT_FTP_USER'], os.environ['GORIZONT_FTP_PASSWORD'])
        ftp.prot_p()
        ftp.set_pasv(True)
        count = deploy(ftp, root, files)
    print('Готово:', count, 'файлов темы и плагина. База и медиатека сохранены.')
    return 0


if __name__ == '__main__':
    try:
        sys.exit(main())
    except (ValueError, RuntimeError) as error:
        print(str(error), file=sys.stderr)
        sys.exit(1)
    except (OSError, ftplib.Error) as error:
        # Не выводим ответ сервера: он может содержать реквизиты подключения.
        print('Ошибка FTPS: ' + type(error).__name__ + '. Проверьте доступ и TLS.', file=sys.stderr)
        sys.exit(1)
