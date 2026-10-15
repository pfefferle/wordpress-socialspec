<?php
/**
 * Removes the settings on uninstall.
 *
 * @package Socialspec
 */

\defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

\delete_option( 'socialspec_user' );
\delete_option( 'socialspec_about_page' );
