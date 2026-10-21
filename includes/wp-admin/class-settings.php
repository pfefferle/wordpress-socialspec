<?php
/**
 * Settings file.
 *
 * @package Socialspec
 */

declare( strict_types = 1 );

namespace Socialspec\WP_Admin;

/**
 * SocialSpec Settings class.
 */
final class Settings {

	/**
	 * Renders the settings page.
	 */
	public static function settings_page(): void {
		\load_template( SOCIALSPEC_PLUGIN_DIR . 'templates/settings.php' );
	}

	/**
	 * Adds the help tabs and the help sidebar to the settings page.
	 */
	public static function add_help_tabs(): void {
		$screen = \get_current_screen();

		if ( ! $screen ) {
			return;
		}

		$screen->add_help_tab(
			array(
				'id'      => 'socialspec-overview',
				'title'   => \__( 'Overview', 'socialspec' ),
				'content' =>
					'<p>' . \__( 'Your site probably has an About page already, maybe a contact page and a list of blogs you like to read. People can read these pages, but apps can not. SocialSpec publishes the same information in simple files that every feed reader and social app can understand.', 'socialspec' ) . '</p>' .
					'<p>' . \__( 'Choose who represents the site: a user for a personal site, or &#8220;Nobody, use the site&#8221; for a magazine or a company. Then choose your About page, or keep &#8220;Automatic&#8221;. Save, and open the links under &#8220;Endpoints&#8221; to check the result.', 'socialspec' ) . '</p>',
			)
		);

		$screen->add_help_tab(
			array(
				'id'      => 'socialspec-about',
				'title'   => \__( 'About', 'socialspec' ),
				'content' =>
					'<p>' . \__( 'The plugin looks for your About text in this order:', 'socialspec' ) . '</p>' .
					'<ol>' .
						'<li>' . \__( 'The page you chose in the settings.', 'socialspec' ) . '</li>' .
						'<li>' . \__( 'The page with the address <code>/about</code>.', 'socialspec' ) . '</li>' .
						'<li>' . \__( 'The &#8220;Biographical Info&#8221; of the user who represents the site, from their profile.', 'socialspec' ) . '</li>' .
					'</ol>' .
					'<p>' . \__( 'If none of these exists, there is no About file.', 'socialspec' ) . '</p>',
			)
		);

		$screen->add_help_tab(
			array(
				'id'      => 'socialspec-contact',
				'title'   => \__( 'Contact', 'socialspec' ),
				'content' =>
					'<p>' . \__( 'If you chose a user, the contact card shows their display name, first and last name, website, &#8220;Biographical Info&#8221; and profile picture. Otherwise it shows the site title, tagline and site icon.', 'socialspec' ) . '</p>' .
					'<p>' . \__( 'Neither the email address of a user nor the admin email of the site is published.', 'socialspec' ) . '</p>',
			)
		);

		$screen->add_help_tab(
			array(
				'id'      => 'socialspec-following',
				'title'   => \__( 'Following', 'socialspec' ),
				'content' =>
					'<p>' . \__( 'WordPress has no list of blogs you follow, so other plugins add them. The &#8220;Endpoints&#8221; section shows which plugins currently do. If you still use the old WordPress Link Manager, links with an RSS address are included too. Without any of these, the list is empty.', 'socialspec' ) . '</p>',
			)
		);

		$screen->add_help_tab(
			array(
				'id'      => 'socialspec-troubleshooting',
				'title'   => \__( 'Troubleshooting', 'socialspec' ),
				'content' =>
					'<p>' . \__( 'If the links show a &#8220;Not Found&#8221; page of your server, your server does not pass addresses that start with <code>/.well-known/</code> to WordPress. Ask your host to pass them on.', 'socialspec' ) . '</p>' .
					'<p>' . \__( 'SocialSpec expects the files at the root of your domain. If WordPress lives in a subfolder, apps will probably not find them.', 'socialspec' ) . '</p>',
			)
		);

		$screen->set_help_sidebar(
			'<p><strong>' . \__( 'For more information:', 'socialspec' ) . '</strong></p>' .
			'<p><a href="https://socialspec.org/" target="_blank" rel="noopener noreferrer">SocialSpec</a></p>' .
			'<p><a href="https://aboutspec.org/" target="_blank" rel="noopener noreferrer">AboutSpec</a></p>' .
			'<p><a href="https://contactspec.org/" target="_blank" rel="noopener noreferrer">ContactSpec</a></p>' .
			'<p><a href="https://followspec.org/" target="_blank" rel="noopener noreferrer">FollowSpec</a></p>'
		);
	}

	/**
	 * The sources of the feeds in /.well-known/following.opml.
	 *
	 * The Link Manager, if enabled, and the plugins hooked into the
	 * `socialspec_following` filter. Callbacks outside of plugins, e.g. in
	 * a theme or a must-use plugin, are listed as "Other code".
	 *
	 * @return string[] The source names.
	 */
	public static function get_following_sources(): array {
		global $wp_filter;

		$sources = array();

		if ( \get_option( 'link_manager_enabled' ) ) {
			$sources[] = \__( 'Link Manager', 'socialspec' );
		}

		if ( ! \has_filter( 'socialspec_following' ) ) {
			return $sources;
		}

		if ( ! \function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$plugins = \get_plugins();

		foreach ( $wp_filter['socialspec_following']->callbacks as $callbacks ) {
			foreach ( $callbacks as $callback ) {
				$file = self::get_callback_file( $callback['function'] );

				if ( null === $file ) {
					continue;
				}

				// E.g. "wordpress-blockroll/includes/class-opml.php", or the full path outside the plugin folders.
				// plugin_basename() also strips the must-use plugin folder, so must-use plugins keep the full path.
				$file     = \wp_normalize_path( $file );
				$basename = \str_starts_with( $file, \wp_normalize_path( WPMU_PLUGIN_DIR ) . '/' ) ? $file : \plugin_basename( $file );
				$source   = \__( 'Other code', 'socialspec' );

				foreach ( $plugins as $plugin_file => $plugin ) {
					// A single file plugin, or a file in the plugin's folder.
					if ( $plugin_file === $basename || \str_starts_with( $basename, \dirname( $plugin_file ) . '/' ) ) {
						$source = $plugin['Name'];
						break;
					}
				}

				$sources[] = $source;
			}
		}

		return \array_values( \array_unique( $sources ) );
	}

	/**
	 * The file a callback is defined in.
	 *
	 * @param mixed $callback The callback.
	 *
	 * @return string|null The file, or null if it is unknown.
	 */
	private static function get_callback_file( mixed $callback ): ?string {
		try {
			if ( \is_array( $callback ) || ( \is_string( $callback ) && \str_contains( $callback, '::' ) ) ) {
				$reflection = new \ReflectionMethod( ...( \is_array( $callback ) ? $callback : \explode( '::', $callback, 2 ) ) );
			} else {
				$reflection = new \ReflectionFunction( \Closure::fromCallable( $callback ) );
			}
		} catch ( \ReflectionException | \TypeError ) {
			return null;
		}

		return $reflection->getFileName() ?: null;
	}
}
