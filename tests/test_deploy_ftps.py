"""Проверки границ загрузки: база, секреты и чужие темы не затрагиваются."""
import importlib.util
from pathlib import Path
import tempfile
import unittest

spec = importlib.util.spec_from_file_location('deploy_ftps', Path(__file__).resolve().parents[1] / 'scripts/deploy_ftps.py')
deploy_ftps = importlib.util.module_from_spec(spec)
spec.loader.exec_module(deploy_ftps)


class DeployBoundaries(unittest.TestCase):
    def test_reject_traversal_and_control_characters(self):
        for value in ('../htdocs', 'domain/../htdocs', 'uploads', 'htdocs\n', 'bad\\path/htdocs'):
            with self.subTest(value=value), self.assertRaises(ValueError):
                deploy_ftps.remote_root(value)

    def test_allow_primary_and_addon_webroot(self):
        self.assertEqual(str(deploy_ftps.remote_root('htdocs')), '/htdocs/wp-content')
        self.assertEqual(str(deploy_ftps.remote_root('test.example/htdocs')), '/test.example/htdocs/wp-content')

    def test_upload_only_owned_theme_and_plugin(self):
        for local, relative in deploy_ftps.manifest():
            self.assertTrue(str(relative).startswith(('themes/gorizont/', 'plugins/gorizont-core/')))
            self.assertNotEqual(local.name, '.env')
            self.assertNotIn('uploads', relative.parts)

    def test_size_mismatch_does_not_replace_existing_file(self):
        class Server:
            def cwd(self, _): pass
            def storbinary(self, command, stream): self.data = stream.read()
            def size(self, _): return 0
            def rename(self, *_): raise AssertionError('Исходный файл не должен заменяться')
        with tempfile.TemporaryDirectory() as directory:
            source = Path(directory) / 'functions.php'
            source.write_text('<?php // example')
            with self.assertRaises(RuntimeError):
                deploy_ftps.deploy(Server(), deploy_ftps.remote_root('htdocs'), [(source, Path('themes/gorizont/functions.php'))])


if __name__ == '__main__':
    unittest.main()
