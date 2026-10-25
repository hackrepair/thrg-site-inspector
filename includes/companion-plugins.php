<?php
/**
 * Companion plugins for the native View Details dialog.
 *
 * @package THRG_Site_Inspector
 */

defined( 'ABSPATH' ) || exit;

/**
 * Append the companion guide only to this plugin's information.
 *
 * @param mixed  $result Plugin information or the original API result.
 * @param string $action Requested API action.
 * @param object $args API arguments.
 * @return mixed
 */
function thrg_si_companion_plugin_info( $result, $action, $args ) {
	if ( 'plugin_information' !== $action || ! is_object( $args ) || ! isset( $args->slug ) || 'thrg-site-inspector' !== $args->slug || ! is_object( $result ) || ! isset( $result->sections ) || ! is_array( $result->sections ) ) {
		return $result;
	}
	wp_enqueue_style( 'thrg-si-brand', plugins_url( 'assets/brand/thrg-brand.css', dirname( __DIR__ ) . '/index.php' ), array(), THRG_SI_VERSION );
	wp_enqueue_style( 'thrg-si-brand-details', plugins_url( 'assets/brand/thrg-details.css', dirname( __DIR__ ) . '/index.php' ), array( 'thrg-si-brand' ), THRG_SI_VERSION );
	wp_enqueue_style( 'thrg_si-plugin-details', plugins_url( 'assets/css/thrg_si-plugin-details.css', dirname( __DIR__ ) . '/index.php' ), array(), THRG_SI_VERSION );
	$result->version                        = THRG_SI_VERSION;
	$result->sections['other_THRG_plugins'] = '<div class="thrg_si-toolkit"><p class="thrg_si-toolkit-intro">More tools from The Hack Repair Guy</p><p>Five focused ways to look after your WordPress site. Use only what you need.</p><div class="thrg_si-toolkit-card"><h3>Group Password Reset</h3><p>Reset passwords by role, choose who to exclude, and optionally send secure reset links.</p><p><a href="https://github.com/hackrepair/group-password-reset" target="_blank" rel="noopener noreferrer">Explore Group Password Reset &rarr;</a></p></div><div class="thrg_si-toolkit-card"><h3>Disable Other Plugin Notices</h3><p>Group plugin and theme notices into a collapsible panel while keeping WordPress core notices visible.</p><p><a href="https://github.com/hackrepair/disable-other-plugin-notices" target="_blank" rel="noopener noreferrer">Explore Disable Other Plugin Notices &rarr;</a></p></div><div class="thrg_si-toolkit-card"><h3>Plugin Detective</h3><p>Find the plugin behind a conflict, with recovery tools for when your dashboard is unavailable.</p><p><a href="https://github.com/hackrepair/hackrepair-plugin-detective" target="_blank" rel="noopener noreferrer">Explore Plugin Detective &rarr;</a></p></div><div class="thrg_si-toolkit-card"><h3>Post Update Email Notifier</h3><p>Notify selected team members when published content changes, with configurable update emails.</p><p><a href="https://github.com/tvcnet/hackrepair-post-update-email-notifier" target="_blank" rel="noopener noreferrer">Explore Post Update Email Notifier &rarr;</a></p></div><div class="thrg_si-toolkit-card"><h3>Plugin Archiver</h3><p>Move unused plugins out of the Plugins list without deleting them, and restore their files when needed.</p><p><a href="https://wordpress.org/plugins/hackrepair-plugin-archiver/" target="_blank" rel="noopener noreferrer">Explore Plugin Archiver &rarr;</a></p></div><p class="thrg_si-toolkit-note">Each plugin works independently. Links open in a new tab.</p></div>';
	return $result;
}
add_filter( 'plugins_api', 'thrg_si_companion_plugin_info', 100, 3 );
