<?php
/** Первичная установка: секреты читаются из окружения и не выводятся. */
$url = getenv( 'WP_URL' );
if ( ! filter_var( $url, FILTER_VALIDATE_URL ) || ! getenv( 'WP_ADMIN_PASSWORD' ) ) { fwrite( STDERR, "Проверьте WP_URL и WP_ADMIN_PASSWORD в .env\n" ); exit( 1 ); }
define( 'WP_INSTALLING', true );
define( 'WP_SITEURL', $url );
define( 'WP_HOME', $url );
require '/var/www/html/wp-load.php';
require_once ABSPATH . 'wp-admin/includes/upgrade.php';
if ( is_blog_installed() ) { echo "WordPress уже установлен.\n"; exit; }
add_filter( 'pre_wp_mail', '__return_true' ); // Локальная установка не отправляет писем.
$result = wp_install( 'ГОРИЗОНТ', getenv( 'WP_ADMIN_USER' ), getenv( 'WP_ADMIN_EMAIL' ), false, '', getenv( 'WP_ADMIN_PASSWORD' ) );
if ( is_wp_error( $result ) ) { fwrite( STDERR, $result->get_error_message() . "\n" ); exit( 1 ); }
foreach ( get_posts( array( 'post_type' => array( 'post', 'page' ), 'post_status' => 'any', 'numberposts' => -1 ) ) as $sample ) {
    if ( in_array( $sample->post_title, array( 'Hello world!', 'Sample Page', 'Privacy Policy' ), true ) ) { wp_delete_post( $sample->ID, true ); }
}
update_option( 'siteurl', $url );
update_option( 'home', $url );
echo "WordPress установлен. Пароль сохранён только в .env.\n";
