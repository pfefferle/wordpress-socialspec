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
