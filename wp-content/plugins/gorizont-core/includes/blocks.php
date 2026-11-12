<?php
defined( 'ABSPATH' ) || exit;
add_action( 'init', function () {
    wp_register_script( 'gorizont-editor', plugins_url( 'blocks/editor.js', GORIZONT_CORE_DIR . 'gorizont-core.php' ), array( 'wp-blocks', 'wp-element', 'wp-components', 'wp-block-editor', 'wp-server-side-render' ), '1.1.0', true );
    register_block_type( 'gorizont/collection', array(
        'api_version' => 3, 'editor_script' => 'gorizont-editor', 'render_callback' => 'gorizont_collection',
        'attributes' => array( 'kind' => array( 'type' => 'string', 'default' => 'g_project' ), 'count' => array( 'type' => 'number', 'default' => 3 ), 'filters' => array( 'type' => 'boolean', 'default' => false ), 'pagination' => array( 'type' => 'boolean', 'default' => false ) ),
        'supports' => array( 'align' => array( 'wide', 'full' ), 'html' => false ),
    ) );
    register_block_type( 'gorizont/contacts', array( 'api_version' => 3, 'editor_script' => 'gorizont-editor', 'render_callback' => 'gorizont_contacts_block', 'supports' => array( 'align' => array( 'wide' ), 'html' => false ) ) );
    register_block_type( 'gorizont/details', array( 'api_version' => 3, 'editor_script' => 'gorizont-editor', 'render_callback' => 'gorizont_details_block', 'uses_context' => array( 'postId', 'postType' ), 'supports' => array( 'html' => false ) ) );
} );
function gorizont_collection( $attrs ) {
    $type = 'g_service' === ( $attrs['kind'] ?? '' ) ? 'g_service' : 'g_project';
    $taxonomy = 'g_project' === $type ? 'g_project_type' : 'g_service_type';
    $count = max( 1, min( 24, absint( $attrs['count'] ?? 3 ) ) );
    $filters = ! empty( $attrs['filters'] );
    $paginate = ! empty( $attrs['pagination'] );
    $selected = $filters && isset( $_GET['category'] ) && is_string( $_GET['category'] ) ? sanitize_title( wp_unslash( $_GET['category'] ) ) : '';
    $page = $paginate && isset( $_GET['gpage'] ) ? max( 1, absint( $_GET['gpage'] ) ) : 1;
    $args = array( 'post_type' => $type, 'post_status' => 'publish', 'posts_per_page' => $count, 'paged' => $page, 'orderby' => array( 'menu_order' => 'ASC', 'title' => 'ASC' ), 'no_found_rows' => ! $paginate );
    if ( $selected ) { $args['tax_query'] = array( array( 'taxonomy' => $taxonomy, 'field' => 'slug', 'terms' => $selected ) ); }
    $query = new WP_Query( $args );
    ob_start();
    echo '<div ' . get_block_wrapper_attributes( array( 'class' => 'g-collection' ) ) . '>';
    $base = get_permalink( get_queried_object_id() );
    if ( ! $base ) { $base = home_url( '/' ); }
    if ( $filters ) {
        $terms = get_terms( array( 'taxonomy' => $taxonomy, 'hide_empty' => true ) );
        echo '<nav class="g-filters" aria-label="Фильтр каталога"><a href="' . esc_url( $base ) . '"' . ( '' === $selected ? ' aria-current="page"' : '' ) . '>Все</a>';
        if ( ! is_wp_error( $terms ) ) {
            foreach ( $terms as $term ) { echo '<a href="' . esc_url( add_query_arg( 'category', $term->slug, $base ) ) . '"' . ( $selected === $term->slug ? ' aria-current="page"' : '' ) . '>' . esc_html( $term->name ) . '</a>'; }
        }
        echo '</nav>';
    }
    echo '<div class="g-grid">';
    $number = ( $page - 1 ) * $count;
    while ( $query->have_posts() ) {
        $query->the_post(); ++$number;
        $id = get_the_ID();
        $service = 'g_service' === $type;
        echo '<article class="g-card' . ( $service ? ' g-service-card' : ' g-project-card' ) . '"><a href="' . esc_url( get_permalink() ) . '">';
        if ( ! $service && has_post_thumbnail() ) { echo get_the_post_thumbnail( $id, 'gorizont-card', array( 'class' => 'g-thumb', 'loading' => 'lazy', 'decoding' => 'async' ) ); }
        if ( $service && has_post_thumbnail( $id ) ) {
            $url = get_the_post_thumbnail_url( $id, 'full' );
            $sprite = str_contains( $url, 'service-illustrations' );
            $cells = array( 'corporate' => 0, 'shop' => 1, 'webapps' => 2, 'design' => 3, 'support' => 4 );
            $cell = $cells[ get_post_field( 'post_name', $id ) ] ?? 0;
            $bounds = array( array(0,340), array(340,280), array(620,300), array(920,310), array(1230,306) );
            $art_style = '--art-scale:' . (1536 / $bounds[$cell][1] * 100) . '%;--art-left:' . (-$bounds[$cell][0] / $bounds[$cell][1] * 100) . '%';
            echo '<span class="service-art' . ( $sprite ? ' service-art-sprite' : '' ) . '" style="' . esc_attr( $art_style ) . '" aria-hidden="true"><img src="' . esc_url( $url ) . '" alt="" loading="lazy" decoding="async"></span>';
        }
        if ( $service && ! has_post_thumbnail( $id ) ) { $icons = array( 'corporate' => 'panels-top-left', 'shop' => 'shopping-cart', 'webapps' => 'blocks', 'design' => 'panels-left-bottom', 'support' => 'chart-no-axes-combined' ); $icon = $icons[ get_post_field( 'post_name', $id ) ] ?? 'panels-top-left'; echo '<img class="service-icon" src="' . esc_url( get_theme_file_uri( 'assets/icons/' . $icon . '.svg' ) ) . '" alt="" width="58" height="58">'; }
        echo '<h3>' . esc_html( get_the_title() ) . '</h3></a><p>' . esc_html( wp_strip_all_tags( get_the_excerpt() ) ) . '</p><div class="g-card-meta"><span>';
        echo esc_html( get_post_meta( $id, $service ? 'gorizont_price' : 'gorizont_location', true ) );
        echo '</span></div></article>';
    }
    wp_reset_postdata();
    echo '</div>';
    if ( ! $query->post_count ) { echo '<p role="status">Материалов пока нет. Выберите другую категорию или вернитесь позже.</p>'; }
    if ( $paginate && $query->max_num_pages > 1 ) {
        $page_base = $selected ? add_query_arg( 'category', $selected, $base ) : $base;
        echo '<nav class="g-pagination" aria-label="Страницы каталога">' . wp_kses_post( paginate_links( array( 'base' => add_query_arg( 'gpage', '%#%', $page_base ), 'format' => '', 'current' => $page, 'total' => $query->max_num_pages, 'prev_text' => 'Назад', 'next_text' => 'Далее' ) ) ) . '</nav>';
    }
    echo '</div>';
    return ob_get_clean();
}
function gorizont_contacts_block() {
    $contacts = get_option( 'gorizont_contacts', array() );
    $labels = gorizont_contact_fields();
    $html = '<dl ' . get_block_wrapper_attributes( array( 'class' => 'g-contacts' ) ) . '>';
    foreach ( $labels as $key => $label ) {
        if ( empty( $contacts[ $key ] ) || ( get_option( 'gorizont_demo_contacts' ) && in_array( $key, array( 'phone', 'email', 'telegram' ), true ) ) ) { continue; }
        $value = $contacts[ $key ];
        $display = esc_html( $value );
        if ( 'phone' === $key ) { $display = '<a href="' . esc_attr( 'tel:' . preg_replace( '/[^0-9+]/', '', $value ) ) . '">' . $display . '</a>'; }
        if ( 'email' === $key ) { $display = '<a href="' . esc_attr( 'mailto:' . sanitize_email( $value ) ) . '">' . $display . '</a>'; }
        if ( 'telegram' === $key ) { $label = 'Телеграм'; $display = '<a href="' . esc_url( $value, array( 'https' ) ) . '">Написать в Телеграм</a>'; }
        $html .= '<div><dt>' . esc_html( $label ) . '</dt><dd>' . $display . '</dd></div>';
    }
    return $html . '</dl>';
}
function gorizont_details_block( $attrs, $content, $block ) {
    $id = $block->context['postId'] ?? get_the_ID();
    $type = get_post_type( $id );
    $html = '';
    foreach ( gorizont_fields( $type ) as $key => $label ) {
        if ( str_starts_with( $key, 'gorizont_seo_' ) ) { continue; }
        $value = get_post_meta( $id, $key, true );
        if ( $value ) { $html .= '<div><dt>' . esc_html( $label ) . '</dt><dd>' . esc_html( $value ) . '</dd></div>'; }
    }
    return $html ? '<dl ' . get_block_wrapper_attributes( array( 'class' => 'g-details' ) ) . '>' . $html . '</dl>' : '';
}
