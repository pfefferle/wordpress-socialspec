<?php
/**
 * Tests for the Settings class.
 *
 * @package Socialspec
 */

use Socialspec\WP_Admin\Settings;

/**
 * Test class for Settings.
 *
 * @coversDefaultClass \Socialspec\WP_Admin\Settings
 */
class Test_Settings extends \WP_UnitTestCase {

	/**
	 * Removes the test filters.
	 */
	public function tear_down() {
		global $wp_plugin_paths;

		\remove_all_filters( 'socialspec_following' );
		$wp_plugin_paths = array(); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		parent::tear_down();
	}

	/**
	 * Without the Link Manager and filters there are no sources.
	 *
	 * @covers ::get_following_sources
	 */
	public function test_no_sources() {
		$this->assertSame( array(), Settings::get_following_sources() );
	}

	/**
	 * Plugins hooked into the filter and the Link Manager are listed once.
	 *
	 * @covers ::get_following_sources
	 */
	public function test_sources() {
		global $wp_plugin_paths;

		// The checkout may be outside of the plugins folder, e.g. in CI, map it like a symlink.
		$wp_plugin_paths[ \wp_normalize_path( WP_PLUGIN_DIR ) . '/wordpress-socialspec' ] = \untrailingslashit( \wp_normalize_path( SOCIALSPEC_PLUGIN_DIR ) ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		\wp_cache_set( 'plugins', array( '' => array( 'wordpress-socialspec/socialspec.php' => array( 'Name' => 'SocialSpec' ) ) ), 'plugins' );

		// Callbacks from this plugin's folder, as closure and as method.
		\add_filter( 'socialspec_following', static fn ( array $outlines ): array => $outlines );
		\add_filter( 'socialspec_following', array( self::class, 'following' ) );
		// Callbacks without a plugin file are skipped.
		\add_filter( 'socialspec_following', 'array_values' );

		$this->assertSame( array( 'SocialSpec' ), Settings::get_following_sources() );

		\update_option( 'link_manager_enabled', 1 );
		$this->assertSame( array( 'Link Manager', 'SocialSpec' ), Settings::get_following_sources() );
	}

	/**
	 * A filter callback.
	 *
	 * @param array $outlines The outlines.
	 *
	 * @return array The outlines.
	 */
	public static function following( array $outlines ): array {
		return $outlines;
	}
}
