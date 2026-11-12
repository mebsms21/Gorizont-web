#!/bin/sh
set -eu
cd /var/www/html
if [ ! -f wp-includes/version.php ]; then
  wp core download --version=7.1.4 --skip-content
fi
if [ ! -f wp-config.php ]; then
  cp /opt/gorizont/wp-config.local.php wp-config.php
fi
if [ ! -f .gorizont-salts.php ]; then
  php -r '$s="<?php\n";foreach(["AUTH_KEY","SECURE_AUTH_KEY","LOGGED_IN_KEY","NONCE_KEY","AUTH_SALT","SECURE_AUTH_SALT","LOGGED_IN_SALT","NONCE_SALT"] as $k){$s.="define(".var_export($k,true).", ".var_export(bin2hex(random_bytes(48)),true).");\n";}file_put_contents(".gorizont-salts.php",$s);chmod(".gorizont-salts.php",0600);'
fi
exec php -d upload_max_filesize=32M -d post_max_size=36M -d memory_limit=256M -d expose_php=0 -S 0.0.0.0:8080 /opt/gorizont/router.php
