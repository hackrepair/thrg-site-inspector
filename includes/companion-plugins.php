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
	wp_enqueue_style( 'thrg_si-plugin-details', plugins_url( 'assets/css/thrg_si-plugin-details.css', dirname( __DIR__ ) . '/index.php' ), array(), THRG_SI_VERSION );
	$result->version                        = THRG_SI_VERSION;
	$result->sections['other_THRG_plugins'] = '<div class="thrg_si-toolkit"><p>More tools from The Hack Repair Guy</p><p>Three focused ways to look after your WordPress site. Use only what you need.</p><div class="thrg_si-toolkit-card"><h3>Disable Other Plugin Notices</h3><p>Group plugin and theme notices into a collapsible panel while keeping WordPress core notices visible.</p><p><a href="https://github.com/hackrepair/disable-other-plugin-notices" target="_blank" rel="noopener noreferrer">Explore Disable Other Plugin Notices &rarr;</a></p></div><div class="thrg_si-toolkit-card"><h3>Group Password Reset</h3><p>Reset passwords by role, choose who to exclude, and send secure reset links when needed.</p><p><a href="https://github.com/hackrepair/group-password-reset" target="_blank" rel="noopener noreferrer">Explore Group Password Reset &rarr;</a></p></div><div class="thrg_si-toolkit-card"><h3>Plugin Detective</h3><p>Find the plugin behind a conflict, with recovery tools for when your dashboard is unavailable.</p><p><a href="https://github.com/hackrepair/hackrepair-plugin-detective" target="_blank" rel="noopener noreferrer">Explore Plugin Detective &rarr;</a></p></div><p>Each plugin works independently. Links open the project pages in a new tab.</p></div>';
	return $result;
}
add_filter( 'plugins_api', 'thrg_si_companion_plugin_info', 100, 3 );
