<?php
/**
 * Tests for the About document.
 *
 * @package Socialspec
 */

use Socialspec\Documents\About;

/**
 * Test class for About.
 *
 * @coversDefaultClass \Socialspec\Documents\About
 */
class Test_About extends \WP_UnitTestCase {

	/**
	 * Without an About page there is nothing to publish.
	 *
	 * @covers ::render
	 */
	public function test_render_without_page() {
		$this->assertNull( $this->render( new About() ) );
	}

	/**
	 * Renders the published About page as Markdown.
	 *
	 * @covers ::render
	 */
	public function test_render_page() {
		self::factory()->post->create(
			array(
				'post_type'    => 'page',
				'post_name'    => 'about',
				'post_title'   => 'About "Jordan"',
				'post_content' => "<!-- wp:paragraph -->\n<p>I am an <strong>outdoor</strong> enthusiast.</p>\n<!-- /wp:paragraph -->\n\n<!-- wp:list -->\n<ul class=\"wp-block-list\"><!-- wp:list-item -->\n<li><a href=\"https://example.com/\">Example</a></li>\n<!-- /wp:list-item --></ul>\n<!-- /wp:list -->",
			)
		);

		$this->assertSame(
			"# About \u{201c}Jordan\u{201d}\n\nI am an **outdoor** enthusiast.\n\n- [Example](https://example.com/)\n",
			$this->render( new About() )
		);
	}

	/**
	 * The more tag must not truncate the page.
	 *
	 * @covers ::render
	 */
	public function test_render_ignores_more_tag() {
		self::factory()->post->create(
			array(
				'post_type'    => 'page',
				'post_name'    => 'about',
				'post_title'   => 'About',
				'post_content' => "<p>One</p>\n<!--more-->\n<p>Two</p>",
			)
		);

		$this->assertStringContainsString( 'Two', $this->render( new About() ) );
	}

	/**
	 * Unpublished and protected pages are not published.
	 *
	 * @covers ::render
	 */
	public function test_render_private_pages() {
		$page_id = self::factory()->post->create(
			array(
				'post_type'   => 'page',
				'post_name'   => 'about',
				'post_status' => 'draft',
			)
		);

		$this->assertNull( $this->render( new About() ) );

		\wp_update_post(
			array(
				'ID'            => $page_id,
				'post_status'   => 'publish',
				'post_password' => 'secret',
			)
		);

		$this->assertNull( $this->render( new About() ) );
	}

	/**
	 * The page can be changed with a filter.
	 *
	 * @covers ::render
	 */
	public function test_about_page_filter() {
		$page_id = self::factory()->post->create(
			array(
				'post_type'    => 'page',
				'post_name'    => 'me',
				'post_title'   => 'Me',
				'post_content' => 'Hello',
			)
		);

		$filter   = static fn () => $page_id;
		\add_filter( 'socialspec_about_page', $filter );
		$markdown = $this->render( new About() );
		\remove_filter( 'socialspec_about_page', $filter );

		$this->assertSame( "# Me\n\nHello\n", $markdown );
	}

	/**
	 * The page selected in the settings wins over the /about page.
	 *
	 * @covers ::render
	 */
	public function test_selected_page() {
		self::factory()->post->create(
			array(
				'post_type'    => 'page',
				'post_name'    => 'about',
				'post_title'   => 'About',
				'post_content' => 'Slug',
			)
		);
		$selected = self::factory()->post->create(
			array(
				'post_type'    => 'page',
				'post_name'    => 'hello',
				'post_title'   => 'Hello',
				'post_content' => 'Selected',
			)
		);

		\update_option( 'socialspec_about_page', $selected );
		$this->assertSame( "# Hello\n\nSelected\n", $this->render( new About() ) );

		// An unpublished selection falls back to the /about page.
		\wp_update_post(
			array(
				'ID'          => $selected,
				'post_status' => 'draft',
			)
		);
		$this->assertSame( "# About\n\nSlug\n", $this->render( new About() ) );
	}

	/**
	 * Without an About page, the biographical info of the selected user is used.
	 *
	 * @covers ::render
	 */
	public function test_user_fallback() {
		$user_id = self::factory()->user->create(
			array(
				'role'         => 'author',
				'display_name' => 'Jordan & Doe',
				'description'  => "Eager explorer.\n\nLikes the <strong>long way</strong> home.",
			)
		);

		$this->assertNull( $this->render( new About() ) );

		\update_option( 'socialspec_user', $user_id );
		$this->assertSame( "# Jordan & Doe\n\nEager explorer.\n\nLikes the **long way** home.\n", $this->render( new About() ) );

		// A user without biographical info has nothing to publish.
		\update_user_meta( $user_id, 'description', '' );
		$this->assertNull( $this->render( new About() ) );
	}

	/**
	 * Rendering restores the global post.
	 *
	 * @covers ::render
	 */
	public function test_render_restores_global_post() {
		$post_id = self::factory()->post->create();
		self::factory()->post->create(
			array(
				'post_type' => 'page',
				'post_name' => 'about',
			)
		);

		$GLOBALS['post'] = \get_post( $post_id ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$this->render( new About() );

		$this->assertSame( $post_id, $GLOBALS['post']->ID );
	}

	/**
	 * A filter can remove the document.
	 *
	 * @covers ::render
	 */
	public function test_render_filtered_away() {
		self::factory()->post->create(
			array(
				'post_type' => 'page',
				'post_name' => 'about',
			)
		);

		\add_filter( 'socialspec_about_md', '__return_false' );
		$this->assertNull( $this->render( new About() ) );
		\remove_filter( 'socialspec_about_md', '__return_false' );
	}

	/**
	 * A line break in the title is a space.
	 *
	 * @covers ::render
	 */
	public function test_render_title_with_line_break() {
		self::factory()->post->create(
			array(
				'post_type'    => 'page',
				'post_name'    => 'about',
				'post_content' => 'Text',
			)
		);

		// kses removes the tag on save for users without unfiltered_html.
		$title = static fn (): string => 'One<br>Two';
		\add_filter( 'the_title', $title );
		$this->assertStringStartsWith( "# One Two\n\n", $this->render( new About() ) );
		\remove_filter( 'the_title', $title );
	}

	/**
	 * Prints a document and returns the output.
	 *
	 * @param \Socialspec\Document $document The document.
	 *
	 * @return string|null The output, or null if there is nothing to publish.
	 */
	private function render( \Socialspec\Document $document ): ?string {
		\ob_start();
		$published = $document->render();
		$output    = (string) \ob_get_clean();

		return $published ? $output : null;
	}
}
