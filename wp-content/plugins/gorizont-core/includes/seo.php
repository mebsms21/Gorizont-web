<?php
defined( 'ABSPATH' ) || exit;
function gorizont_has_seo_plugin() {
    return defined( 'WPSEO_VERSION' ) || defined( 'RANK_MATH_VERSION' ) || defined( 'AIOSEO_VERSION' ) || defined( 'SEOPRESS_VERSION' );
}
add_filter( 'pre_get_document_title', function ( $title ) {
    if ( ! gorizont_has_seo_plugin() && is_singular() ) {
        $custom = get_post_meta( get_queried_object_id(), 'gorizont_seo_title', true );
        if ( $custom ) { return $custom; }
    }
    return $title;
} );
add_action( 'wp_head', function () {
    if ( gorizont_has_seo_plugin() || is_404() || is_search() ) { return; }
    $id = is_singular() ? get_queried_object_id() : 0;
    $description = $id ? get_post_meta( $id, 'gorizont_seo_description', true ) : '';
    if ( ! $description ) { $description = $id ? wp_trim_words( wp_strip_all_tags( get_the_excerpt( $id ) ), 28, '…' ) : get_bloginfo( 'description' ); }
    $url = $id ? get_permalink( $id ) : ( is_tax() ? get_term_link( get_queried_object() ) : home_url( '/' ) );
    if ( is_wp_error( $url ) ) { $url = home_url( '/' ); }
    if ( ! is_singular() ) { echo '<link rel="canonical" href="' . esc_url( $url ) . '">' . "\n"; }
    echo '<meta name="description" content="' . esc_attr( $description ) . '">' . "\n";
    foreach ( array( 'og:type' => 'website', 'og:locale' => 'ru_RU', 'og:site_name' => get_bloginfo( 'name' ), 'og:title' => wp_get_document_title(), 'og:description' => $description, 'og:url' => $url ) as $property => $value ) {
        echo '<meta property="' . esc_attr( $property ) . '" content="' . esc_attr( $value ) . '">' . "\n";
    }
    if ( $id && has_post_thumbnail( $id ) ) { echo '<meta property="og:image" content="' . esc_url( get_the_post_thumbnail_url( $id, 'large' ) ) . '">' . "\n"; }
    if ( is_front_page() && ! get_option( 'gorizont_demo_contacts' ) ) {
        $contacts = get_option( 'gorizont_contacts', array() );
        $schema = array( '@context' => 'https://schema.org', '@type' => 'Organization', 'name' => get_bloginfo( 'name' ), 'url' => home_url( '/' ) );
        if ( ! empty( $contacts['phone'] ) ) { $schema['telephone'] = $contacts['phone']; }
        if ( ! empty( $contacts['email'] ) ) { $schema['email'] = $contacts['email']; }
        echo '<script type="application/ld+json">' . wp_json_encode( $schema, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT ) . '</script>' . "\n";
    }
}, 5 );
add_filter( 'wp_robots', function ( $robots ) {
    if ( isset( $_GET['category'] ) || isset( $_GET['gpage'] ) ) { $robots['noindex'] = true; unset( $robots['index'] ); }
    return $robots;
} );
add_action( 'update_option_gorizont_contacts', function () { delete_option( 'gorizont_demo_contacts' ); } );
