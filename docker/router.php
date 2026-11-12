<?php
/** Маршрутизация исключительно локального PHP dev server. */
$path = rawurldecode( parse_url( $_SERVER['REQUEST_URI'], PHP_URL_PATH ) ?: '/' );
if ( str_contains( $path, '..' ) || preg_match( '~(?:^|/)\.|^/wp-config\.php|^/xmlrpc\.php|\.(?:log|sql|bak|env)$|/uploads/.*\.php$~i', $path ) ) {
    http_response_code( 403 ); exit( 'Доступ запрещён' );
}
header( 'X-Content-Type-Options: nosniff' );
header( 'Referrer-Policy: strict-origin-when-cross-origin' );
header( 'X-Frame-Options: SAMEORIGIN' );
$file = realpath( '/var/www/html' . $path );
if ( $file && str_starts_with( $file, '/var/www/html/' ) && is_file( $file ) ) { return false; }
if ( is_dir( '/var/www/html' . $path ) && is_file( '/var/www/html' . rtrim( $path, '/' ) . '/index.php' ) ) {
    require '/var/www/html' . rtrim( $path, '/' ) . '/index.php'; return true;
}
require '/var/www/html/index.php';
