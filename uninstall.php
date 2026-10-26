<?php
/**
 * Uninstall: remove plugin data only if the site owner chose to.
 *
 * The "Delete all plugin data" option on Settings > Site Health Controls
 * decides, per site. When it is off, everything is kept for a reinstall.
 *
 * @package THRG_Site_Inspector
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

if ( ! function_exists( 'thrg_shc_uninstall_site' ) ) {
    /**
     * Delete this site's plugin data if its owner opted in.
     *
     * @return bool Whether this site opted in.
     */
    function thrg_shc_uninstall_site() {
        $options = get_option( 'thrg_shc_options', array() );
        if ( ! is_array( $options ) || empty( $options['delete_data'] ) ) {
            return false;
        }
        foreach ( array( 'thrg_shc_options', 'thrg_si_notice_dismissed', 'thrg_si_history', 'thrg_si_history_paused' ) as $name ) {
            delete_option( $name );
        }
        return true;
    }
}

$thrg_delete_shared = false;
if ( is_multisite() ) {
    foreach ( get_sites(
        array(
			'fields' => 'ids',
			'number' => 0,
        )
    ) as $thrg_site_id ) {
        switch_to_blog( $thrg_site_id );
        $thrg_delete_shared = thrg_shc_uninstall_site() || $thrg_delete_shared;
        restore_current_blog();
    }
} else {
    $thrg_delete_shared = thrg_shc_uninstall_site();
}

// Network-wide leftovers: update-check bookkeeping (keyed by the installed folder name) and the GitHub rate-limit note.
if ( $thrg_delete_shared ) {
    delete_site_option( 'external_updates-' . basename( __DIR__ ) );
    delete_site_transient( 'thrg_si_github_limit' );
}
