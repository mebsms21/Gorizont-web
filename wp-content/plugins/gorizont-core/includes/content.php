<?php
defined( 'ABSPATH' ) || exit;
function gorizont_fields( $type ) {
    $common = array( 'gorizont_seo_title' => 'SEO-заголовок', 'gorizont_seo_description' => 'SEO-описание' );
    if ( 'g_service' === $type ) {
        return array_merge( array( 'gorizont_price' => 'Стоимость (например: по запросу)', 'gorizont_duration' => 'Срок выполнения' ), $common );
    }
    if ( 'g_project' === $type ) {
        return array_merge( array( 'gorizont_location' => 'Тип сайта / подпись карточки', 'gorizont_area' => 'Состав работ', 'gorizont_year' => 'Год / статус проекта' ), $common );
    }
    return $common;
}
function gorizont_register_content() {
    foreach ( array( 'g_service' => array( 'Услуги', 'Услуга', 'usluga', 'dashicons-layout' ), 'g_project' => array( 'Проекты', 'Проект', 'proekt', 'dashicons-portfolio' ) ) as $type => $data ) {
        register_post_type( $type, array(
            'labels' => array( 'name' => $data[0], 'singular_name' => $data[1], 'add_new' => 'Добавить', 'add_new_item' => 'Добавить: ' . $data[1], 'edit_item' => 'Редактировать: ' . $data[1], 'new_item' => 'Новая запись', 'view_item' => 'Посмотреть', 'search_items' => 'Поиск', 'not_found' => 'Записей пока нет', 'all_items' => 'Все записи', 'featured_image' => 'Обложка', 'set_featured_image' => 'Выбрать обложку', 'remove_featured_image' => 'Удалить обложку', 'use_featured_image' => 'Использовать как обложку' ),
            'public' => true, 'show_in_rest' => true, 'has_archive' => false, 'rewrite' => array( 'slug' => $data[2] ),
            'menu_icon' => $data[3], 'menu_position' => 21, 'supports' => array( 'title', 'editor', 'excerpt', 'thumbnail', 'revisions', 'page-attributes', 'custom-fields' ),
        ) );
    }
    register_taxonomy( 'g_project_type', 'g_project', array( 'labels' => array( 'name' => 'Типы проектов', 'singular_name' => 'Тип проекта', 'add_new_item' => 'Добавить тип проекта', 'all_items' => 'Все типы', 'edit_item' => 'Редактировать тип', 'search_items' => 'Поиск типов' ), 'public' => true, 'hierarchical' => true, 'show_in_rest' => true, 'show_admin_column' => true, 'rewrite' => array( 'slug' => 'tip-proekta' ) ) );
    register_taxonomy( 'g_service_type', 'g_service', array( 'labels' => array( 'name' => 'Направления услуг', 'singular_name' => 'Направление', 'add_new_item' => 'Добавить направление', 'all_items' => 'Все направления', 'edit_item' => 'Редактировать направление', 'search_items' => 'Поиск направлений' ), 'public' => true, 'hierarchical' => true, 'show_in_rest' => true, 'show_admin_column' => true, 'rewrite' => array( 'slug' => 'napravlenie' ) ) );
    foreach ( array( 'page', 'post', 'g_service', 'g_project' ) as $type ) {
        foreach ( gorizont_fields( $type ) as $key => $label ) {
            register_post_meta( $type, $key, array( 'type' => 'string', 'single' => true, 'show_in_rest' => true, 'sanitize_callback' => 'sanitize_text_field', 'auth_callback' => function ( $allowed, $meta_key, $post_id ) { return current_user_can( 'edit_post', $post_id ); } ) );
        }
    }
}
add_action( 'init', 'gorizont_register_content' );
add_action( 'add_meta_boxes', function () {
    foreach ( array( 'page', 'post', 'g_service', 'g_project' ) as $type ) {
        add_meta_box( 'gorizont-fields', 'ГОРИЗОНТ — характеристики и SEO', 'gorizont_meta_box', $type, 'normal', 'default', array( '__block_editor_compatible_meta_box' => true ) );
    }
} );
function gorizont_meta_box( $post ) {
    wp_nonce_field( 'gorizont_save_meta', 'gorizont_meta_nonce' );
    foreach ( gorizont_fields( $post->post_type ) as $key => $label ) {
        echo '<p><label for="' . esc_attr( $key ) . '"><strong>' . esc_html( $label ) . '</strong></label><br><input class="widefat" type="text" id="' . esc_attr( $key ) . '" name="' . esc_attr( $key ) . '" value="' . esc_attr( get_post_meta( $post->ID, $key, true ) ) . '"></p>';
    }
    if ( in_array( $post->post_type, array( 'g_service', 'g_project' ), true ) ) { echo '<p><label for="gorizont-order"><strong>Порядок в каталоге</strong></label><br><input type="number" min="0" id="gorizont-order" name="gorizont_order" value="' . absint( $post->menu_order ) . '"></p>'; }
    echo '<p>Краткое описание карточки задаётся в «Отрывке», изображение — в «Обложке». Порядок карточек определяется полем «Порядок» (меньше — выше), затем заголовком.</p>';
}
add_action( 'save_post', function ( $post_id ) {
    if ( ! isset( $_POST['gorizont_meta_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['gorizont_meta_nonce'] ) ), 'gorizont_save_meta' ) || wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) || ! current_user_can( 'edit_post', $post_id ) ) { return; }
    foreach ( gorizont_fields( get_post_type( $post_id ) ) as $key => $label ) {
        if ( isset( $_POST[ $key ] ) && is_string( $_POST[ $key ] ) ) { update_post_meta( $post_id, $key, sanitize_text_field( wp_unslash( $_POST[ $key ] ) ) ); }
    }
} );
foreach ( array( 'g_service', 'g_project' ) as $gorizont_type ) {
    add_filter( 'manage_' . $gorizont_type . '_posts_columns', function ( $columns ) { $columns['gorizont_order'] = 'Порядок'; return $columns; } );
    add_action( 'manage_' . $gorizont_type . '_posts_custom_column', function ( $column, $post_id ) { if ( 'gorizont_order' === $column ) { echo esc_html( (string) get_post_field( 'menu_order', $post_id ) ); } }, 10, 2 );
}

add_filter( 'wp_insert_post_data', function ( $data, $postarr ) {
    if ( in_array( $data['post_type'], array( 'g_service', 'g_project' ), true ) && isset( $_POST['gorizont_order'], $_POST['gorizont_meta_nonce'] ) && is_string( $_POST['gorizont_meta_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['gorizont_meta_nonce'] ) ), 'gorizont_save_meta' ) && ! empty( $postarr['ID'] ) && current_user_can( 'edit_post', $postarr['ID'] ) ) {
        $data['menu_order'] = absint( $_POST['gorizont_order'] );
    }
    return $data;
}, 10, 2 );
