<?php
/**
 * Plugin Name: ГОРИЗОНТ — Содержимое
 * Description: Услуги, портфолио, контакты, SEO и демонстрационное наполнение. Не зависит от темы.
 * Version: 1.0.0
 * Requires at least: 6.6
 * Requires PHP: 8.2
 * Text Domain: gorizont-core
 * License: GPL-2.0-or-later
 */
defined( 'ABSPATH' ) || exit;
define( 'GORIZONT_CORE_DIR', plugin_dir_path( __FILE__ ) );
require_once GORIZONT_CORE_DIR . 'includes/content.php';
require_once GORIZONT_CORE_DIR . 'includes/settings.php';
require_once GORIZONT_CORE_DIR . 'includes/blocks.php';
require_once GORIZONT_CORE_DIR . 'includes/seo.php';
require_once GORIZONT_CORE_DIR . 'includes/demo.php';
register_activation_hook( __FILE__, function () { gorizont_register_content(); flush_rewrite_rules(); } );
register_deactivation_hook( __FILE__, function () { flush_rewrite_rules(); } );
