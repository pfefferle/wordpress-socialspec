<?php
/**
 * Tests for the Socialspec class.
 *
 * @package Socialspec
 */

use Socialspec\Document;
use Socialspec\Documents\About;
use Socialspec\Socialspec;

/**
 * Test class for Socialspec.
 *
 * @coversDefaultClass \Socialspec\Socialspec
 */
class Test_Socialspec extends \WP_UnitTestCase {

	/**
	 * The default documents.
	 *
	 * @covers ::get_documents
	 */
	public function test_get_documents() {
		$documents = Socialspec::get_documents();

		$this->assertSame( array( 'following.opml', 'about.md', 'contact.vcf' ), \array_keys( $documents ) );
		$this->assertSame( 'application/xml', $documents['following.opml']->get_content_type() );
		$this->assertSame( 'text/markdown', $documents['about.md']->get_content_type() );
		$this->assertSame( 'text/vcard', $documents['contact.vcf']->get_content_type() );
	}

	/**
	 * Documents can be removed, invalid ones are dropped.
	 *
	 * @covers ::get_documents
	 */
	public function test_documents_filter() {
		$filter = static fn (): array => array(
			'about.md' => new About(),
			'bad.txt'  => 'not a document',
		);

		\add_filter( 'socialspec_documents', $filter );
		$documents = Socialspec::get_documents();
		\remove_filter( 'socialspec_documents', $filter );

		$this->assertSame( array( 'about.md' ), \array_keys( $documents ) );
		$this->assertInstanceOf( Document::class, $documents['about.md'] );
	}

	/**
	 * A document removed with the filter is not routed, so another plugin can serve it.
	 *
	 * @covers ::add_rewrite_rules
	 */
	public function test_disabled_document_is_not_routed() {
		global $wp_rewrite;

		$filter = static function ( array $documents ): array {
			unset( $documents['following.opml'] );
			return $documents;
		};

		$wp_rewrite->extra_rules_top = array();
		\add_filter( 'socialspec_documents', $filter );
		Socialspec::add_rewrite_rules();
		\remove_filter( 'socialspec_documents', $filter );

		$this->assertSame(
			array( '^\.well-known/(about\.md|contact\.vcf)$' => 'index.php?well-known=$matches[1]' ),
			$wp_rewrite->extra_rules_top
		);

		Socialspec::add_rewrite_rules();
	}

	/**
	 * The documents are routed through the shared `well-known` query var.
	 *
	 * @covers ::add_rewrite_rules
	 * @covers ::query_vars
	 */
	public function test_routing() {
		// Serving the document would exit.
		\remove_action( 'parse_request', array( Socialspec::class, 'parse_request' ) );

		$this->set_permalink_structure( '/%postname%/' );

		$this->go_to( \home_url( '/.well-known/contact.vcf' ) );
		$this->assertSame( 'contact.vcf', \get_query_var( 'well-known' ) );

		$this->go_to( \home_url( '/.well-known/following.opml' ) );
		$this->assertSame( 'following.opml', \get_query_var( 'well-known' ) );

		// The dot is escaped.
		$this->go_to( \home_url( '/.well-known/aboutxmd' ) );
		$this->assertSame( '', \get_query_var( 'well-known' ) );

		$this->go_to( \home_url( '/.well-known/about.md/extra' ) );
		$this->assertSame( '', \get_query_var( 'well-known' ) );

		\add_action( 'parse_request', array( Socialspec::class, 'parse_request' ) );
	}

	/**
	 * If-None-Match uses the weak comparison.
	 *
	 * @dataProvider etag_provider
	 * @covers ::etag_matches
	 *
	 * @param string $header   The If-None-Match header.
	 * @param bool   $expected Whether it matches.
	 */
	public function test_etag_matches( string $header, bool $expected ) {
		$this->assertSame( $expected, Socialspec::etag_matches( '"abc"', $header ) );
	}

	/**
	 * If-None-Match headers.
	 *
	 * @return array[] Test cases.
	 */
	public function etag_provider(): array {
		return array(
			'empty'     => array( '', false ),
			'match'     => array( '"abc"', true ),
			'weak'      => array( 'W/"abc"', true ),
			'list'      => array( '"x", W/"abc" , "y"', true ),
			'wildcard'  => array( '*', true ),
			'no match'  => array( '"abcd"', false ),
			'unquoted'  => array( 'abc', false ),
			'only weak' => array( 'W/', false ),
		);
	}

	/**
	 * Without the rewrite rule the request path is matched.
	 *
	 * @covers ::get_requested_name
	 */
	public function test_get_requested_name() {
		$wp = new \WP();

		$wp->query_vars = array( 'well-known' => 'about.md' );
		$this->assertSame( 'about.md', Socialspec::get_requested_name( $wp ) );

		$wp->query_vars = array();

		$_SERVER['REQUEST_URI'] = '/.well-known/contact.vcf?x=1';
		$this->assertSame( 'contact.vcf', Socialspec::get_requested_name( $wp ) );

		$_SERVER['REQUEST_URI'] = '/blog/.well-known/contact.vcf';
		$this->assertNull( Socialspec::get_requested_name( $wp ) );

		$_SERVER['REQUEST_URI'] = '/about/';
		$this->assertNull( Socialspec::get_requested_name( $wp ) );

		// With WordPress in a subfolder.
		$home = static fn (): string => 'http://example.org/blog';
		\add_filter( 'home_url', $home );

		$_SERVER['REQUEST_URI'] = '/blog/.well-known/contact.vcf';
		$this->assertSame( 'contact.vcf', Socialspec::get_requested_name( $wp ) );
		\remove_filter( 'home_url', $home );
	}
}
