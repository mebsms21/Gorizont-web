<?php
defined( 'ABSPATH' ) || exit;
function gorizont_contact_fields() {
    return array( 'phone' => 'Телефон', 'email' => 'Электронная почта', 'address' => 'Адрес', 'hours' => 'Часы работы', 'telegram' => 'Ссылка на Telegram (https://t.me/...)' );
}
function gorizont_sanitize_contacts( $input ) {
    $clean = array();
    foreach ( gorizont_contact_fields() as $key => $label ) {
        $value = isset( $input[ $key ] ) && is_string( $input[ $key ] ) ? $input[ $key ] : '';
        $clean[ $key ] = 'email' === $key ? sanitize_email( $value ) : ( 'telegram' === $key ? esc_url_raw( $value, array( 'https' ) ) : sanitize_text_field( $value ) );
    }
    return $clean;
}
add_action( 'admin_init', function () {
    register_setting( 'gorizont_contacts', 'gorizont_contacts', array( 'type' => 'array', 'sanitize_callback' => 'gorizont_sanitize_contacts', 'default' => array() ) );
} );
add_action( 'admin_menu', function () {
    add_menu_page( 'ГОРИЗОНТ', 'ГОРИЗОНТ', 'manage_options', 'gorizont', 'gorizont_settings_page', 'dashicons-admin-site-alt3', 20 );
} );
function gorizont_settings_page() {
    if ( ! current_user_can( 'manage_options' ) ) { return; }
    $contacts = get_option( 'gorizont_contacts', array() );
    echo '<div class="wrap"><h1>ГОРИЗОНТ — управление сайтом</h1><p>Страницы и тексты: «Страницы». Услуги и проекты: соответствующие разделы. Шапка, подвал, цвета и шрифты: «Внешний вид → Редактор».</p><h2>Контактные данные</h2><form method="post" action="options.php">';
    settings_fields( 'gorizont_contacts' );
    echo '<table class="form-table" role="presentation">';
    foreach ( gorizont_contact_fields() as $key => $label ) {
        $type = 'email' === $key ? 'email' : ( 'telegram' === $key ? 'url' : 'text' );
        echo '<tr><th><label for="g-' . esc_attr( $key ) . '">' . esc_html( $label ) . '</label></th><td><input class="regular-text" type="' . esc_attr( $type ) . '" id="g-' . esc_attr( $key ) . '" name="gorizont_contacts[' . esc_attr( $key ) . ']" value="' . esc_attr( $contacts[ $key ] ?? '' ) . '"></td></tr>';
    }
    echo '</table>';
    submit_button( 'Сохранить контакты' );
    echo '</form><hr><h2>Демонстрационное наполнение</h2><p>Создаёт только отсутствующие демозаписи. Существующие страницы с теми же адресами пропускаются. Изображения иллюстрируют концепцию и не являются реальными работами бюро. Повторный запуск не изменяет отредактированные материалы.</p><form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '"><input type="hidden" name="action" value="gorizont_demo">';
    wp_nonce_field( 'gorizont_demo' );
    submit_button( 'Добавить демоматериалы', 'secondary' );
    echo '</form></div>';
}
add_action( 'admin_post_gorizont_demo', function () {
    if ( ! current_user_can( 'manage_options' ) ) { wp_die( 'Недостаточно прав.', 403 ); }
    check_admin_referer( 'gorizont_demo' );
    $result = gorizont_seed_demo();
    if ( is_wp_error( $result ) ) { wp_die( esc_html( $result->get_error_message() ) ); }
    wp_safe_redirect( admin_url( 'admin.php?page=gorizont&demo=done' ) ); exit;
} );
add_action( 'admin_notices', function () {
    if ( isset( $_GET['page'], $_GET['demo'] ) && 'gorizont' === $_GET['page'] && 'done' === $_GET['demo'] && current_user_can( 'manage_options' ) ) {
        echo '<div class="notice notice-success is-dismissible"><p>Демонстрационные материалы добавлены. Существующие записи сохранены.</p></div>';
    }
} );
