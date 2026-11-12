<?php
/** Тема отвечает только за представление; данные принадлежат плагину. */
defined( 'ABSPATH' ) || exit;
add_action( 'after_setup_theme', function () {
    add_theme_support( 'title-tag' );
    add_theme_support( 'post-thumbnails' );
    add_theme_support( 'responsive-embeds' );
    add_theme_support( 'editor-styles' );
    add_theme_support( 'wp-block-styles' );
    add_editor_style( 'assets/site.css' );
    add_image_size( 'gorizont-card', 960, 720, true );
} );
add_action( 'wp_enqueue_scripts', function () {
    wp_enqueue_script( 'gorizont-motion', get_theme_file_uri( 'assets/motion.js' ), array(), filemtime( get_theme_file_path( 'assets/motion.js' ) ), true );
    wp_enqueue_style( 'gorizont', get_theme_file_uri( 'assets/site.css' ), array(), filemtime( get_theme_file_path( 'assets/site.css' ) ) );
} );
add_action( 'init', function () {
    register_block_pattern_category( 'gorizont', array( 'label' => 'ГОРИЗОНТ — разделы' ) );
} );
add_action( 'wp_head', function () {
    echo '<link rel="preload" href="' . esc_url( get_theme_file_uri( 'assets/fonts/manrope.woff2' ) ) . '" as="font" type="font/woff2" crossorigin>' . "\n";
}, 1 );

add_action( 'wp_body_open', function () {
    echo '<div class="horizon-intro"><div class="intro-scene" aria-hidden="true"><div class="intro-sun-window"><div class="intro-sun"></div></div><div class="intro-horizon"></div><span class="intro-title">ГОРИЗОНТ</span><span class="intro-caption">ЗА ПРЕДЕЛАМИ ПРИВЫЧНОГО</span></div><button class="intro-skip" type="button" tabindex="-1">Пропустить</button></div>';
} );
