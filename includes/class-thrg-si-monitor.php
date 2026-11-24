<?php
/**
 * Site-local inventory change monitoring.
 *
 * @package THRG_Site_Inspector
 */

defined( 'ABSPATH' ) || exit;

final class THRG_SI_Monitor {
    const HOOK    = 'thrg_si_monitor_daily';
    const LOCK    = 'thrg_si_monitor_lock';
    const MAX_LOG = 50;

    /** Grace period after the daily inventory becomes due. */
    const LATE_SECONDS = 21600;

    public static function boot() {
        add_action( 'admin_post_thrg_si_monitor', array( __CLASS__, 'save' ) );
        add_action( 'admin_init', array( __CLASS__, 'schedule' ) );
        add_action( self::HOOK, array( __CLASS__, 'daily' ) );
        add_action( 'add_user_role', array( __CLASS__, 'grant' ), 10, 2 );
    }

    /**
     * Normalize stored monitoring settings, or return null when off/invalid.
     *
     * @param mixed $raw Raw option value.
     * @return array|null
     */
    public static function normalize_settings( $raw ) {
        if ( ! is_array( $raw ) ) {
            return null;
        }
        return array(
            'users'     => THRG_SI_Email::recipient_ids( isset( $raw['users'] ) && is_array( $raw['users'] ) ? $raw['users'] : array() ),
            'immediate' => ! empty( $raw['immediate'] ),
            'digest'    => ! empty( $raw['digest'] ),
        );
    }

    /**
     * Compare current inventory against the previous inventory and return added entries.
     *
     * @param array $previous Previous inventory items by type.
     * @param array $current  Current inventory items by type.
     * @return string[]
     */
    public static function diff( array $previous, array $current ) {
        $changes = array();
        foreach ( $current as $type => $items ) {
            if ( ! is_array( $items ) ) {
                continue;
            }
            $prior = isset( $previous[ $type ] ) && is_array( $previous[ $type ] ) ? $previous[ $type ] : $items;
            foreach ( array_diff_key( $items, $prior ) as $name ) {
                $changes[] = $type . ' added: ' . $name;
            }
        }
        return $changes;
    }

    /**
     * Append new changes to the bounded change log.
     *
     * @param mixed    $existing Existing log array.
     * @param string[] $changes  New change lines.
     * @param int      $now      Unix timestamp.
     * @return array
     */
    public static function append_log( $existing, array $changes, $now ) {
        $log = is_array( $existing ) ? $existing : array();
        foreach ( $changes as $change ) {
            $log[] = array( (int) $now, (string) $change );
        }
        return array_slice( $log, -self::MAX_LOG );
    }

    /**
     * Detect a late scheduled event or an inventory that has stopped advancing.
     *
     * @param mixed     $inventory Stored inventory.
     * @param int|false $next      Next scheduled check.
     * @param int       $now       Current Unix time.
     * @return bool
     */
    public static function is_overdue( $inventory, $next, $now ) {
        $last = is_array( $inventory ) && isset( $inventory['time'] ) && is_numeric( $inventory['time'] ) ? (int) $inventory['time'] : 0;
        return ( $next && $now - (int) $next > self::LATE_SECONDS )
            || ( $last > 0 && $now - $last > DAY_IN_SECONDS + self::LATE_SECONDS );
    }

    public static function schedule() {
        if ( get_option( 'thrg_si_monitor' ) && ! wp_next_scheduled( self::HOOK ) ) {
            wp_schedule_event( time() + DAY_IN_SECONDS, 'daily', self::HOOK );
        }
    }

    private static function snapshot() {
        if ( ! function_exists( 'get_plugins' ) ) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        wp_clean_plugins_cache( false );
        $admins = array();
        foreach ( get_users(
            array(
				'role'   => 'administrator',
				'fields' => array( 'ID', 'user_login' ),
            )
        ) as $user ) {
            $admins[ $user->ID ] = $user->user_login;
        }
        $plugins = array();
        foreach ( get_plugins() as $file => $plugin ) {
            $plugins[ $file ] = $plugin['Name'] . ' (' . $file . ')';
        }
        $themes = array();
        foreach ( wp_get_themes() as $slug => $theme ) {
            $themes[ $slug ] = $theme->get( 'Name' ) . ' (' . $slug . ')';
        }
        return array(
			'Administrator' => $admins,
			'Plugin'        => $plugins,
			'Theme'         => $themes,
		);
    }

    private static function back( $flag ) {
        wp_safe_redirect( add_query_arg( 'thrg_si_done', $flag, admin_url( THRG_Site_Inspector::PAGE_URL ) ) . '#si-monitor' );
        exit;
    }

    public static function save() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You do not have permission to do that.', 'thrg-site-inspector' ), '', array( 'response' => 403 ) );
        }
        check_admin_referer( 'thrg_si_monitor' );
        if ( 'POST' !== ( isset( $_SERVER['REQUEST_METHOD'] ) ? $_SERVER['REQUEST_METHOD'] : '' ) ) {
            wp_die( esc_html__( 'Use the monitoring form.', 'thrg-site-inspector' ), '', array( 'response' => 405 ) );
        }
        // phpcs:disable WordPress.Security.NonceVerification.Missing -- nonce checked above.
        if ( isset( $_POST['enabled'] ) ) {
            $chosen = isset( $_POST['users'] ) && is_array( $_POST['users'] ) ? wp_parse_id_list( wp_unslash( $_POST['users'] ) ) : array();
            $users  = array_values( array_intersect( array_column( THRG_SI_Email::recipients(), 'id' ), $chosen ) );
            $alerts = isset( $_POST['immediate'] );
            $digest = isset( $_POST['digest'] );
            $clean  = self::normalize_settings(
                array(
                    'users'     => $users,
                    'immediate' => $alerts,
                    'digest'    => $digest,
                )
            );
            if ( ( $clean['immediate'] || $clean['digest'] ) && ! $clean['users'] ) {
                self::back( 'monitor-none' );
            }
            if ( ! self::normalize_settings( get_option( 'thrg_si_monitor' ) ) ) {
                update_option(
                    'thrg_si_inventory',
                    array(
                        'items' => self::snapshot(),
                        'time'  => time(),
                        'log'   => array(),
                    ),
                    false
                );
            }
            update_option( 'thrg_si_monitor', $clean, false );
            self::schedule();
            self::back( 'monitor-saved' );
        }
        // phpcs:enable WordPress.Security.NonceVerification.Missing
        delete_option( 'thrg_si_monitor' );
        wp_clear_scheduled_hook( self::HOOK );
        delete_option( 'thrg_si_inventory' );
        delete_option( 'thrg_si_monitor_mail' );
        self::back( 'monitor-off' );
    }

    public static function daily() {
        $settings = self::normalize_settings( get_option( 'thrg_si_monitor' ) );
        if ( ! $settings ) {
            return;
        }
        if ( get_transient( self::LOCK ) ) {
            return;
        }
        set_transient( self::LOCK, 1, 5 * MINUTE_IN_SECONDS );

        try {
            $previous   = get_option( 'thrg_si_inventory', array() );
            $prev_items = is_array( $previous ) && isset( $previous['items'] ) && is_array( $previous['items'] ) ? $previous['items'] : array();
            $current    = self::snapshot();
            $changes    = self::diff( $prev_items, $current );
            $now        = time();
            $log        = self::append_log( is_array( $previous ) && isset( $previous['log'] ) ? $previous['log'] : array(), $changes, $now );

            if ( function_exists( 'wp_cache_delete' ) ) {
                wp_cache_delete( 'thrg_si_monitor', 'options' );
            }
            $fresh = self::normalize_settings( get_option( 'thrg_si_monitor' ) );
            if ( ! $fresh ) {
                return;
            }

            update_option(
                'thrg_si_inventory',
                array(
                    'items' => $current,
                    'time'  => $now,
                    'log'   => $log,
                ),
                false
            );
            if ( $changes && $fresh['digest'] ) {
                self::notify( $fresh, 'Daily site changes', $changes );
            }
        } finally {
            delete_transient( self::LOCK );
        }
    }

    public static function grant( $id, $role ) {
        if ( 'administrator' !== $role ) {
            return;
        }
        $settings = self::normalize_settings( get_option( 'thrg_si_monitor' ) );
        $user     = get_userdata( $id );
        if ( $settings && $settings['immediate'] && $user && in_array( 'administrator', (array) $user->roles, true ) ) {
            self::notify( $settings, 'Administrator access granted', array( 'Administrator access granted to ' . $user->user_login ) );
        }
    }

    private static function notify( $settings, $title, $lines ) {
        $subject = '[' . THRG_SI_Email::site_domain() . '] [Site Inspector] ' . $title;
        $lines[] = '';
        $lines[] = 'Review these changes with your site team: ' . admin_url( THRG_Site_Inspector::PAGE_URL . '#si-monitor' );
        $sent    = 0;
        $failed  = 0;
        foreach ( THRG_SI_Email::recipients() as $recipient ) {
            if ( in_array( (int) $recipient['id'], $settings['users'], true ) ) {
                if ( wp_mail( $recipient['email'], $subject, implode( "\n", $lines ) ) ) {
                    ++$sent;
                } else {
                    ++$failed;
                }
            }
        }
        update_option(
            'thrg_si_monitor_mail',
            array(
                'time'   => time(),
                'title'  => $title,
                'sent'   => $sent,
                'failed' => $failed,
            ),
            false
        );
    }
}
