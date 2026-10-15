<?php
/**
 * Tests for the Options class.
 *
 * @package Socialspec
 */

use Socialspec\Options;
use Socialspec\WP_Admin\Menu;

/**
 * Test class for Options.
 *
 * @coversDefaultClass \Socialspec\Options
 */
class Test_Options extends \WP_UnitTestCase {

	/**
	 * The settings are registered for the settings page and the REST API.
	 *
	 * @covers ::register_settings
	 */
	public function test_register_settings() {
		Options::register_settings();
		$settings = \get_registered_settings();

		foreach ( array( 'socialspec_user', 'socialspec_about_page' ) as $name ) {
			$this->assertArrayHasKey( $name, $settings );
			$this->assertSame( 'socialspec', $settings[ $name ]['group'] );
			$this->assertSame( 'integer', $settings[ $name ]['type'] );
			$this->assertSame( 0, $settings[ $name ]['default'] );
			$this->assertNotEmpty( $settings[ $name ]['show_in_rest'] );
		}
	}

	/**
	 * Only users who can publish represent the site.
	 *
	 * @covers ::sanitize_user
	 */
	public function test_sanitize_user() {
		$author     = self::factory()->user->create( array( 'role' => 'author' ) );
		$subscriber = self::factory()->user->create( array( 'role' => 'subscriber' ) );

		$this->assertSame( $author, Options::sanitize_user( (string) $author ) );
		$this->assertSame( 0, Options::sanitize_user( $subscriber ) );
		$this->assertSame( 0, Options::sanitize_user( 999999 ) );
		$this->assertSame( 0, Options::sanitize_user( 'abc' ) );
		$this->assertSame( 0, Options::sanitize_user( null ) );
	}

	/**
	 * Only pages are accepted as About page.
	 *
	 * @covers ::sanitize_page
	 */
	public function test_sanitize_page() {
		$page = self::factory()->post->create( array( 'post_type' => 'page' ) );
		$post = self::factory()->post->create();

		// A global post must not leak into the option.
		$GLOBALS['post'] = \get_post( $page ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

		$this->assertSame( $page, Options::sanitize_page( (string) $page ) );
		$this->assertSame( 0, Options::sanitize_page( $post ) );
		$this->assertSame( 0, Options::sanitize_page( 0 ) );
		$this->assertSame( 0, Options::sanitize_page( '' ) );
	}

	/**
	 * The plugin list links to the settings page.
	 *
	 * @covers \Socialspec\WP_Admin\Menu::plugin_action_links
	 */
	public function test_plugin_action_links() {
		$links = Menu::plugin_action_links( array( '<a href="#">Deactivate</a>' ) );

		$this->assertCount( 2, $links );
		$this->assertStringContainsString( 'options-general.php?page=socialspec', $links[0] );
	}
}
