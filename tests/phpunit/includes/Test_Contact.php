<?php
/**
 * Tests for the Contact document.
 *
 * @package Socialspec
 */

use Socialspec\Documents\Contact;

/**
 * Test class for Contact.
 *
 * @coversDefaultClass \Socialspec\Documents\Contact
 */
class Test_Contact extends \WP_UnitTestCase {

	/**
	 * Removes the test filters.
	 */
	public function tear_down() {
		\remove_all_filters( 'socialspec_vcard_properties' );
		parent::tear_down();
	}

	/**
	 * Renders the site as vCard 3.0, without an email address.
	 *
	 * @covers ::render
	 */
	public function test_render_defaults() {
		\update_option( 'blogname', 'Jordan &amp; Co' );
		\update_option( 'blogdescription', 'Explorer, optimist; walker' );

		$vcard = ( new Contact() )->render();
		$host  = \wp_parse_url( \home_url(), PHP_URL_HOST );

		$this->assertStringStartsWith( "BEGIN:VCARD\r\nVERSION:3.0\r\n", $vcard );
		$this->assertStringEndsWith( "\r\nEND:VCARD\r\n", $vcard );
		$this->assertStringContainsString( "\r\nFN:Jordan & Co\r\n", $vcard );
		$this->assertStringContainsString( "\r\nN:;;;;\r\n", $vcard );
		$this->assertStringContainsString( "\r\nNICKNAME:@{$host}\r\n", $vcard );
		$this->assertStringContainsString( "\r\nNOTE:Explorer\\, optimist\\; walker\r\n", $vcard );
		$this->assertStringContainsString( "\r\nURL:" . \home_url( '/' ) . "\r\n", $vcard );
		$this->assertStringContainsString( "\r\nSOURCE:" . \home_url( '/.well-known/contact.vcf' ) . "\r\n", $vcard );
		$this->assertStringNotContainsString( 'EMAIL', $vcard );
		$this->assertStringNotContainsString( \get_option( 'admin_email' ), $vcard );
	}

	/**
	 * The selected user is described instead of the site, without the email.
	 *
	 * @covers ::render
	 */
	public function test_render_user() {
		$user_id = self::factory()->user->create(
			array(
				'role'         => 'author',
				'display_name' => 'Jordan Doe',
				'first_name'   => 'Jordan',
				'last_name'    => 'Doe',
				'user_url'     => 'https://jordandoe.example/',
				'user_email'   => 'jordan@example.org',
				'description'  => 'Explorer, <em>optimist</em>',
			)
		);
		\update_option( 'socialspec_user', $user_id );
		\update_option( 'show_avatars', 1 );

		$received = null;
		$filter   = static function ( array $properties, $user ) use ( &$received ): array {
			$received = $user;
			return $properties;
		};
		\add_filter( 'socialspec_vcard_properties', $filter, 10, 2 );

		$vcard = ( new Contact() )->render();
		$host  = \wp_parse_url( \home_url(), PHP_URL_HOST );

		$this->assertStringContainsString( "\r\nFN:Jordan Doe\r\n", $vcard );
		$this->assertStringContainsString( "\r\nN:Doe;Jordan;;;\r\n", $vcard );
		$this->assertStringContainsString( "\r\nURL:https://jordandoe.example/\r\n", $vcard );
		$this->assertStringContainsString( "\r\nNOTE:Explorer\\, optimist\r\n", $vcard );
		$this->assertStringContainsString( "\r\nPHOTO;VALUE=uri:", $vcard );
		$this->assertStringContainsString( "\r\nNICKNAME:@{$host}\r\n", $vcard );
		$this->assertStringNotContainsString( 'jordan@example.org', $vcard );
		$this->assertStringNotContainsString( 'EMAIL', $vcard );
		$this->assertSame( $user_id, $received->ID );

		// No avatar where the site hides avatars.
		\update_option( 'show_avatars', 0 );
		$this->assertStringNotContainsString( 'PHOTO', ( new Contact() )->render() );
	}

	/**
	 * A deleted user falls back to the site.
	 *
	 * @covers ::render
	 */
	public function test_render_deleted_user() {
		\update_option( 'blogname', 'The Site' );
		\update_option( 'socialspec_user', 999999 );

		$this->assertStringContainsString( "\r\nFN:The Site\r\n", ( new Contact() )->render() );
	}

	/**
	 * Properties can be added, invalid ones are dropped.
	 *
	 * @covers ::render
	 */
	public function test_vcard_properties_filter() {
		\add_filter(
			'socialspec_vcard_properties',
			static function ( array $properties ): array {
				$properties[] = array( 'EMAIL;TYPE=INTERNET,PREF', 'hi@example.com' );
				$properties[] = array( 'item1.URL', 'https://example.com/a,b;c' );
				$properties[] = array( 'ADR;TYPE=WORK', array( '', '', 'Street 1, Floor 2', 'City', '', '12345', 'Country' ) );
				$properties[] = array( 'NOTE', "Line 1\nLine 2" );
				$properties[] = array( 'VERSION', '4.0' );
				$properties[] = array( "X-EVIL:\r\nFN", 'x' );
				$properties[] = array( 'X-ARRAY', new \stdClass() );
				$properties[] = 'not an array';
				return $properties;
			}
		);

		$vcard = ( new Contact() )->render();

		$this->assertStringContainsString( "\r\nEMAIL;TYPE=INTERNET,PREF:hi@example.com\r\n", $vcard );
		$this->assertStringContainsString( "\r\nitem1.URL:https://example.com/a,b;c\r\n", $vcard );
		$this->assertStringContainsString( "\r\nADR;TYPE=WORK:;;Street 1\\, Floor 2;City;;12345;Country\r\n", $vcard );
		$this->assertStringContainsString( "\r\nNOTE:Line 1\\nLine 2\r\n", $vcard );
		$this->assertSame( 1, \substr_count( $vcard, 'VERSION' ) );
		$this->assertStringNotContainsString( 'X-EVIL', $vcard );
		$this->assertStringNotContainsString( 'X-ARRAY', $vcard );
	}

	/**
	 * Long lines are folded at 75 octets without splitting characters.
	 *
	 * @covers ::render
	 */
	public function test_folding() {
		$note = \str_repeat( "Gr\u{fc}\u{df}e ", 40 );
		\add_filter( 'socialspec_vcard_properties', static fn (): array => array( array( 'NOTE', $note ) ) );

		$vcard = ( new Contact() )->render();

		foreach ( \explode( "\r\n", $vcard ) as $line ) {
			$this->assertLessThanOrEqual( 75, \strlen( $line ) );
			$this->assertTrue( \wp_check_invalid_utf8( $line ) === $line, 'Lines are valid UTF-8.' );
		}

		// Unfolding (RFC 2425, section 5.8.1) restores the line.
		$this->assertStringContainsString( 'NOTE:' . $note, \str_replace( "\r\n ", '', $vcard ) );
	}

	/**
	 * A filter can remove the document.
	 *
	 * @covers ::render
	 */
	public function test_render_filtered_away() {
		\add_filter( 'socialspec_vcard', '__return_false' );
		$this->assertNull( ( new Contact() )->render() );
		\remove_filter( 'socialspec_vcard', '__return_false' );
	}

	/**
	 * Angle brackets the user wrote as text are kept.
	 *
	 * @covers ::render
	 */
	public function test_render_bio_with_angle_brackets() {
		$user_id = self::factory()->user->create(
			array(
				'role'        => 'author',
				'description' => 'I write about &lt;code&gt; and <b>tags</b>',
			)
		);
		\update_option( 'socialspec_user', $user_id );

		$this->assertStringContainsString( "\r\nNOTE:I write about <code> and tags\r\n", ( new Contact() )->render() );
	}
}
