<?php
/**
 * Menu file.
 *
 * @package Socialspec
 */

declare( strict_types = 1 );

namespace Socialspec\WP_Admin;

/**
 * SocialSpec Menu class.
 */
final class Menu {

	/**
	 * Registers the hooks.
	 */
	public static function init(): void {
		\add_action( 'admin_menu', array( self::class, 'admin_menu' ) );
		\add_filter( 'plugin_action_links_' . SOCIALSPEC_PLUGIN_BASENAME, array( self::class, 'plugin_action_links' ) );
	}

	/**
	 * Adds the settings page.
	 */
	public static function admin_menu(): void {
		\add_options_page(
			\_x( 'SocialSpec', 'page title', 'socialspec' ),
			'SocialSpec',
			'manage_options',
			'socialspec',
			array( Settings::class, 'settings_page' )
		);
	}

	/**
	 * Adds a link to the settings page to the plugin actions.
	 *
	 * @param string[] $links The plugin action links.
	 *
	 * @return string[] The filtered links.
	 */
	public static function plugin_action_links( array $links ): array {
		\array_unshift(
			$links,
			\sprintf(
				'<a href="%s">%s</a>',
				\esc_url( \admin_url( 'options-general.php?page=socialspec' ) ),
				\esc_html__( 'Settings', 'socialspec' )
			)
		);

		return $links;
	}
}
