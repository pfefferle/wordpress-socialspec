<?php
/**
 * Plugin Name: SocialSpec
 * Plugin URI: https://github.com/pfefferle/wordpress-socialspec
 * Description: Publishes the SocialSpec APIs (FollowSpec, AboutSpec, ContactSpec) at /.well-known/.
 * Version: 1.0.0
 * Requires at least: 6.7
 * Requires PHP: 8.1
 * Author: Matthias Pfefferle
 * Author URI: https://notiz.blog/
 * License: MIT
 * License URI: https://opensource.org/licenses/MIT
 * Text Domain: socialspec
 * Update URI: https://github.com/pfefferle/wordpress-socialspec
 *
 * @package Socialspec
 */

declare( strict_types = 1 );

namespace Socialspec;

\defined( 'ABSPATH' ) || exit;

\define( 'SOCIALSPEC_VERSION', '1.0.0' );

\define( 'SOCIALSPEC_PLUGIN_DIR', \plugin_dir_path( __FILE__ ) );
\define( 'SOCIALSPEC_PLUGIN_BASENAME', \plugin_basename( __FILE__ ) );

require_once SOCIALSPEC_PLUGIN_DIR . 'includes/class-autoloader.php';
Autoloader::register_path( __NAMESPACE__ . '\\', SOCIALSPEC_PLUGIN_DIR . 'includes/' );

\add_action( 'plugins_loaded', array( Socialspec::class, 'init' ) );
\add_action( 'plugins_loaded', array( Options::class, 'init' ) );

\add_action( 'init', array( WP_Admin\Menu::class, 'init' ) );
\add_action( 'admin_init', array( WP_Admin\Settings_Fields::class, 'init' ) );

\register_activation_hook( __FILE__, array( Socialspec::class, 'activate' ) );
\register_deactivation_hook( __FILE__, array( Socialspec::class, 'deactivate' ) );
