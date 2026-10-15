<?php
/**
 * SocialSpec settings template.
 *
 * @package Socialspec
 */

use Socialspec\Socialspec;
use Socialspec\WP_Admin\Settings;

?>
<div class="wrap">
	<h1><?php echo \esc_html( \get_admin_page_title() ); ?></h1>

	<form method="post" action="options.php">
		<?php \settings_fields( 'socialspec' ); ?>
		<?php \do_settings_sections( 'socialspec' ); ?>
		<?php \submit_button(); ?>
	</form>

	<h2><?php \esc_html_e( 'Endpoints', 'socialspec' ); ?></h2>
	<ul>
		<?php foreach ( \array_keys( Socialspec::get_documents() ) as $socialspec_name ) : ?>
			<li>
				<a href="<?php echo \esc_url( \home_url( '/.well-known/' . $socialspec_name ) ); ?>"><code>/.well-known/<?php echo \esc_html( $socialspec_name ); ?></code></a>
				<?php if ( 'following.opml' === $socialspec_name ) : ?>
					<?php $socialspec_sources = Settings::get_following_sources(); ?>
					<?php if ( $socialspec_sources ) : ?>
						<?php /* translators: %s: Comma-separated list of plugin names. */ ?>
						&mdash; <?php echo \esc_html( \sprintf( \__( 'Feeds from: %s', 'socialspec' ), \implode( ', ', $socialspec_sources ) ) ); ?>
					<?php else : ?>
						&mdash; <?php \esc_html_e( 'No plugin adds the feeds you follow yet, so the list is empty.', 'socialspec' ); ?>
					<?php endif; ?>
				<?php endif; ?>
			</li>
		<?php endforeach; ?>
	</ul>
</div>
