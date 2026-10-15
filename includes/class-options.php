<?php
/**
 * Options file.
 *
 * @package Socialspec
 */

declare( strict_types = 1 );

namespace Socialspec;

/**
 * Registers and sanitizes the SocialSpec options.
 */
final class Options {

	/**
	 * Registers the hooks.
	 */
	public static function init(): void {
		\add_action( 'admin_init', array( self::class, 'register_settings' ) );
		\add_action( 'rest_api_init', array( self::class, 'register_settings' ) );
	}

	/**
	 * Registers the settings.
	 */
	public static function register_settings(): void {
		\register_setting(
			'socialspec',
			'socialspec_user',
			array(
				'type'              => 'integer',
				'description'       => \__( 'The user who represents the site, 0 for the site itself.', 'socialspec' ),
				'sanitize_callback' => array( self::class, 'sanitize_user' ),
				'show_in_rest'      => true,
				'default'           => 0,
			)
		);

		\register_setting(
			'socialspec',
			'socialspec_about_page',
			array(
				'type'              => 'integer',
				'description'       => \__( 'The page published as /.well-known/about.md, 0 for the /about page.', 'socialspec' ),
				'sanitize_callback' => array( self::class, 'sanitize_page' ),
				'show_in_rest'      => true,
				'default'           => 0,
			)
		);
	}

	/**
	 * Accepts users who can publish content, like the dropdown offers.
	 *
	 * @param mixed $value The submitted user ID.
	 *
	 * @return int The user ID, or 0 for the site.
	 */
	public static function sanitize_user( mixed $value ): int {
		$user = \get_userdata( \absint( $value ) );

		return $user && \user_can( $user, 'edit_posts' ) ? $user->ID : 0;
	}

	/**
	 * Accepts pages.
	 *
	 * @param mixed $value The submitted page ID.
	 *
	 * @return int The page ID, or 0 for the /about page.
	 */
	public static function sanitize_page( mixed $value ): int {
		$page_id = \absint( $value );

		// get_post_type( 0 ) would check the global post.
		return $page_id && 'page' === \get_post_type( $page_id ) ? $page_id : 0;
	}
}
