<?php
/** Запуск: ./bin/wp eval-file /tests/integration.php. Только локальная среда. */
if ( 'local' !== wp_get_environment_type() ) { WP_CLI::error( 'Проверки разрешены только в локальной среде.' ); }
$passed = 0;
$assert = function ( $condition, $label ) use ( &$passed ) {
    if ( ! $condition ) { throw new RuntimeException( $label ); }
    ++$passed; WP_CLI::log( 'PASS: ' . $label );
};
$ids = array();
$original_contacts = get_option( 'gorizont_contacts' );
$original_demo_flag = get_option( 'gorizont_demo_contacts' );
try {
    $assert( wp_get_theme()->is_block_theme(), 'Активна блочная тема WordPress' );
    foreach ( array( 'g_project', 'g_service' ) as $type ) {
        $obj = get_post_type_object( $type );
        $assert( $obj && $obj->show_in_rest && post_type_supports( $type, 'editor' ), 'Gutenberg включён: ' . $type );
    }
    $before = (int) wp_count_posts( 'g_project' )->publish + (int) wp_count_posts( 'g_service' )->publish + (int) wp_count_posts( 'page' )->publish;
    $home = get_post( get_option( 'page_on_front' ) );
    $result = gorizont_seed_demo();
    $after = (int) wp_count_posts( 'g_project' )->publish + (int) wp_count_posts( 'g_service' )->publish + (int) wp_count_posts( 'page' )->publish;
    $assert( ! is_wp_error( $result ) && $before === $after, 'Повторный демоимпорт не создаёт дубликатов' );
    $assert( $home->post_content === get_post_field( 'post_content', $home->ID ), 'Повторный импорт сохраняет тексты страниц' );
    $assert( $original_contacts === get_option( 'gorizont_contacts' ), 'Импорт сохраняет контакты' );
    $id = wp_insert_post( array( 'post_type' => 'g_project', 'post_status' => 'publish', 'post_title' => 'Тест доступа', 'post_name' => 'qa-access-fixture' ) ); $ids[] = $id;
    wp_set_current_user( 0 );
    $request = new WP_REST_Request( 'POST', '/wp/v2/g_project/' . $id );
    $request->set_param( 'title', 'Недопустимое изменение' );
    $response = rest_do_request( $request );
    $assert( in_array( $response->get_status(), array( 401, 403 ), true ), 'Анонимный посетитель не может изменять проекты через REST' );
    $request = new WP_REST_Request( 'POST', '/wp/v2/settings' );
    $request->set_param( 'gorizont_contacts', array( 'email' => 'intruder@example.com' ) );
    $assert( in_array( rest_do_request( $request )->get_status(), array( 401, 403 ), true ), 'Настройки недоступны для анонимной записи' );
    wp_set_current_user( 1 );
    $request = new WP_REST_Request( 'POST', '/wp/v2/g_project/' . $id );
    $request->set_param( 'meta', array( 'gorizont_location' => '<script>alert(1)</script>Москва' ) );
    $response = rest_do_request( $request );
    $assert( 200 === $response->get_status() && ! str_contains( get_post_meta( $id, 'gorizont_location', true ), '<script>' ), 'REST сохраняет характеристики и удаляет HTML' );
    $clean = gorizont_sanitize_contacts( array( 'phone' => '<img src=x onerror=alert(1)>+7 000', 'email' => 'broken-address', 'telegram' => 'javascript:alert(1)' ) );
    $assert( '' === $clean['email'] && '' === $clean['telegram'] && ! str_contains( $clean['phone'], '<' ), 'Контакты очищаются от некорректной почты, HTML и опасных URL' );
    $meta = get_post_meta( $id, 'gorizont_location', true );
    $_POST = array( 'gorizont_location' => 'Изменение без nonce' );
    wp_update_post( array( 'ID' => $id, 'post_title' => 'Новое название' ) );
    $assert( $meta === get_post_meta( $id, 'gorizont_location', true ), 'Метаполя не меняются без nonce' );
    $_POST = array();
    $term = wp_insert_term( 'Тестовая категория', 'g_project_type', array( 'slug' => 'qa-category' ) );
    if ( is_wp_error( $term ) ) { throw new RuntimeException( 'Не создана тестовая категория' ); }
    wp_set_object_terms( $id, array( $term['term_id'] ), 'g_project_type' );
    $_GET['category'] = 'qa-category';
    $html = render_block( array( 'blockName' => 'gorizont/collection', 'attrs' => array( 'kind' => 'g_project', 'count' => 1, 'filters' => true, 'pagination' => true ), 'innerBlocks' => array(), 'innerHTML' => '', 'innerContent' => array() ) );
    $assert( str_contains( $html, 'Новое название' ) && ! str_contains( $html, 'Ателье /' ), 'Фильтр выводит только выбранную категорию' );
    $_GET['gpage'] = 99;
    $html = do_blocks( '<!-- wp:gorizont/collection {"kind":"g_project","count":1,"filters":true,"pagination":true} /-->' );
    $assert( str_contains( $html, 'Материалов пока нет' ), 'Для пустой страницы каталога есть понятное состояние' );
    unset( $_GET['category'], $_GET['gpage'] );
    wp_delete_term( $term['term_id'], 'g_project_type' );
    WP_CLI::success( $passed . ' интеграционных проверок пройдено.' );
} finally {
    foreach ( $ids as $id ) { wp_delete_post( $id, true ); }
    unset( $_GET['category'], $_GET['gpage'] );
    $_POST = array();
    wp_set_current_user( 1 );
}
