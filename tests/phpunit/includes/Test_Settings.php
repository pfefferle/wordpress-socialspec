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
		\remove_all_filters( 'socialspec_following' );
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
