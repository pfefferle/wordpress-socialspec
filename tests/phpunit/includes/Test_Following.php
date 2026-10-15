<?php
/**
 * Tests for the Following document.
 *
 * @package Socialspec
 */

use Socialspec\Documents\Following;

/**
 * Test class for Following.
 *
 * @coversDefaultClass \Socialspec\Documents\Following
 */
class Test_Following extends \WP_UnitTestCase {

	/**
	 * Removes the test filters.
	 */
	public function tear_down() {
		\remove_all_filters( 'socialspec_following' );
		parent::tear_down();
	}

	/**
	 * Without feeds the OPML has an empty body.
	 *
	 * @covers ::render
	 */
	public function test_render_without_feeds() {
		$xml = \simplexml_load_string( ( new Following() )->render() );

		$this->assertNotFalse( $xml, 'The OPML is well-formed XML.' );
		$this->assertCount( 0, $xml->body->outline );
	}

	/**
	 * Renders the filtered feeds as valid, escaped OPML.
	 *
	 * @covers ::render
	 */
	public function test_render_filtered_feeds() {
		\update_option( 'blogname', 'Jordan &amp; Co' );
		\add_filter(
			'socialspec_following',
			static fn () => array(
				array(
					'type'    => 'rss',
					'text'    => "A &amp; B <c> \"d\" &hellip;\x01",
					'htmlUrl' => 'https://example.com/?a=1&b=2',
					'xmlUrl'  => 'https://example.com/feed/',
				),
				array( 'text' => 'No feed URL' ),
				'not an array',
				array(
					'xmlUrl'      => 'https://example.org/feed/',
					'bad name'    => 'dropped',
					'description' => array( 'dropped' ),
				),
				array(
					'title'  => 'Title',
					'xmlUrl' => 'https://example.net/feed/',
				),
			)
		);

		$opml = ( new Following() )->render();
		$xml  = \simplexml_load_string( $opml );

		$this->assertNotFalse( $xml, 'The OPML is well-formed XML.' );
		$this->assertSame( '2.0', (string) $xml['version'] );
		$this->assertSame( 'Jordan & Co Follows', (string) $xml->head->title );
		$this->assertCount( 3, $xml->body->outline );

		$first = $xml->body->outline[0];
		$this->assertSame( "A & B <c> \"d\" \u{2026}", (string) $first['text'] );
		$this->assertSame( 'https://example.com/?a=1&b=2', (string) $first['htmlUrl'] );
		$this->assertSame( 'https://example.com/feed/', (string) $first['xmlUrl'] );

		// OPML 2.0 requires a text, the title or the host is used.
		$second = $xml->body->outline[1];
		$this->assertSame(
			array(
				'xmlUrl' => 'https://example.org/feed/',
				'text'   => 'example.org',
			),
			\current( (array) $second->attributes() )
		);
		$this->assertSame( 'Title', (string) $xml->body->outline[2]['text'] );
	}

	/**
	 * Unsafe feed URLs are dropped.
	 *
	 * @covers ::render
	 */
	public function test_render_drops_unsafe_urls() {
		\add_filter(
			'socialspec_following',
			static fn () => array(
				array(
					'text'    => 'Unsafe',
					'htmlUrl' => 'javascript:alert(1)',
					'xmlUrl'  => 'https://example.com/feed/',
				),
			)
		);

		$this->assertStringNotContainsString( 'javascript', ( new Following() )->render() );
	}

	/**
	 * Bookmarks with a feed are listed when the Link Manager is enabled.
	 *
	 * @covers ::render
	 */
	public function test_render_bookmarks() {
		\wp_insert_link(
			array(
				'link_name' => 'Kottke',
				'link_url'  => 'https://kottke.org/',
				'link_rss'  => 'https://feeds.kottke.org/main',
			)
		);
		\wp_insert_link(
			array(
				'link_name' => 'No feed',
				'link_url'  => 'https://example.org/',
			)
		);

		\update_option( 'link_manager_enabled', 0 );
		$this->assertCount( 0, \simplexml_load_string( ( new Following() )->render() )->body->outline );

		\update_option( 'link_manager_enabled', 1 );
		$xml = \simplexml_load_string( ( new Following() )->render() );

		$this->assertCount( 1, $xml->body->outline );
		$this->assertSame( 'Kottke', (string) $xml->body->outline[0]['text'] );
		$this->assertSame( 'rss', (string) $xml->body->outline[0]['type'] );
		$this->assertSame( 'https://feeds.kottke.org/main', (string) $xml->body->outline[0]['xmlUrl'] );
	}

	/**
	 * A filter can remove the document.
	 *
	 * @covers ::render
	 */
	public function test_render_filtered_away() {
		\add_filter( 'socialspec_following_opml', '__return_false' );
		$this->assertNull( ( new Following() )->render() );
		\remove_filter( 'socialspec_following_opml', '__return_false' );
	}
}
