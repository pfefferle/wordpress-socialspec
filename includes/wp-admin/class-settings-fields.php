<?php
/**
 * Settings Fields file.
 *
 * @package Socialspec
 */

declare( strict_types = 1 );

namespace Socialspec\WP_Admin;

/**
 * SocialSpec Settings Fields class.
 */
final class Settings_Fields {

	/**
	 * Registers the hooks.
	 */
	public static function init(): void {
		\add_action( 'load-settings_page_socialspec', array( self::class, 'register_settings_fields' ) );
	}

	/**
	 * Registers the settings section and fields.
	 */
	public static function register_settings_fields(): void {
		\add_settings_section(
			'socialspec_profile',
			\__( 'Profile', 'socialspec' ),
			array( self::class, 'render_profile_section' ),
			'socialspec'
		);

		\add_settings_field(
			'socialspec_user',
			\__( 'Represented by', 'socialspec' ),
			array( self::class, 'render_user_field' ),
			'socialspec',
			'socialspec_profile',
			array( 'label_for' => 'socialspec_user' )
		);

		\add_settings_field(
			'socialspec_about_page',
			\__( 'About page', 'socialspec' ),
			array( self::class, 'render_about_page_field' ),
			'socialspec',
			'socialspec_profile',
			array( 'label_for' => 'socialspec_about_page' )
		);
	}

	/**
	 * Renders the profile section description.
	 */
	public static function render_profile_section(): void {
		?>
		<p>
			<?php
			\printf(
				/* translators: %s: Link to socialspec.org. */
				\esc_html__( 'Your site publishes its profile with %s: what you follow, who you are and how to reach you, ready for feed readers and social apps.', 'socialspec' ),
				'<a href="https://socialspec.org/">SocialSpec</a>'
			);
			?>
		</p>
		<?php
	}

	/**
	 * Renders the user field.
	 */
	public static function render_user_field(): void {
		\wp_dropdown_users(
			array(
				'name'              => 'socialspec_user',
				'id'                => 'socialspec_user',
				'selected'          => (int) \get_option( 'socialspec_user' ),
				'show_option_none'  => \esc_html__( 'Nobody, use the site', 'socialspec' ),
				'option_none_value' => 0,
				'capability'        => array( 'edit_posts' ),
			)
		);
		?>
		<p class="description">
			<?php
			\printf(
				/* translators: %s: /.well-known/contact.vcf */
				\esc_html__( 'The name, website, biographical info and profile picture of this user are published in %s instead of the site title, tagline and icon. The email address is not published.', 'socialspec' ),
				'<code>/.well-known/contact.vcf</code>'
			);
			?>
		</p>
		<?php
	}

	/**
	 * Renders the About page field.
	 */
	public static function render_about_page_field(): void {
		\wp_dropdown_pages(
			array(
				'name'              => 'socialspec_about_page',
				'id'                => 'socialspec_about_page',
				'selected'          => (int) \get_option( 'socialspec_about_page' ),
				'show_option_none'  => \esc_html__( 'Automatic: the /about page', 'socialspec' ),
				'option_none_value' => 0,
			)
		);
		?>
		<p class="description">
			<?php
			\printf(
				/* translators: 1: /.well-known/about.md, 2: /about, 3: Link to slashpages.net */
				\esc_html__( 'This page is published as Markdown in %1$s. By default it is the page with the slug %2$s, one of the %3$s. Without such a page, the biographical info of the user above is used.', 'socialspec' ),
				'<code>/.well-known/about.md</code>',
				'<code>/about</code>',
				'<a href="https://slashpages.net/#about">' . \esc_html__( 'slash pages', 'socialspec' ) . '</a>'
			);
			?>
		</p>
		<?php
	}
}
