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
		$this->assertNull( ( new About() )->render() );
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
			( new About() )->render()
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

		$this->assertStringContainsString( 'Two', ( new About() )->render() );
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

		$this->assertNull( ( new About() )->render() );

		\wp_update_post(
			array(
				'ID'            => $page_id,
				'post_status'   => 'publish',
				'post_password' => 'secret',
			)
		);

		$this->assertNull( ( new About() )->render() );
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
		$markdown = ( new About() )->render();
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
		$this->assertSame( "# Hello\n\nSelected\n", ( new About() )->render() );

		// An unpublished selection falls back to the /about page.
		\wp_update_post(
			array(
				'ID'          => $selected,
				'post_status' => 'draft',
			)
		);
		$this->assertSame( "# About\n\nSlug\n", ( new About() )->render() );
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

		$this->assertNull( ( new About() )->render() );

		\update_option( 'socialspec_user', $user_id );
		$this->assertSame( "# Jordan & Doe\n\nEager explorer.\n\nLikes the **long way** home.\n", ( new About() )->render() );

		// A user without biographical info has nothing to publish.
		\update_user_meta( $user_id, 'description', '' );
		$this->assertNull( ( new About() )->render() );
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
		( new About() )->render();

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
		$this->assertNull( ( new About() )->render() );
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
		$this->assertStringStartsWith( "# One Two\n\n", ( new About() )->render() );
		\remove_filter( 'the_title', $title );
	}
}
