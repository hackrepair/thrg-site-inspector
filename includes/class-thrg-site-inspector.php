<?php
/**
 * Read-only, on-demand inspection. Each run is saved to the local report history (THRG_SI_History).
 *
 * @package THRG_Site_Inspector
 */

defined( 'ABSPATH' ) || exit;
require_once __DIR__ . '/class-thrg-inspection-evidence.php';
require_once __DIR__ . '/class-thrg-si-history.php';
require_once __DIR__ . '/class-thrg-si-email.php';

final class THRG_Site_Inspector {

    /** Screen-reader hint appended to every link that opens a new tab. */
    const NEW_TAB_HINT = '<span class="screen-reader-text"> (opens in a new tab)</span>';

    const STATUS_ORDER = array(
        'Potential weakness' => 0,
        'Review'             => 1,
        'Partial inspection' => 2,
        'Unable to verify'   => 2,
        'No issue observed'  => 3,
    );

    /** PHP branch => end of security support (php.net/supported-versions). Maintained policy, not a universal rule. */
    const PHP_SUPPORT = array(
        '7.4' => '2022-11-28',
        '8.0' => '2023-11-26',
        '8.1' => '2025-12-31',
        '8.2' => '2026-12-31',
        '8.3' => '2027-12-31',
        '8.4' => '2028-12-31',
    );

    public static function boot() {
        add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
        add_action( 'admin_enqueue_scripts', array( __CLASS__, 'assets' ) );
        add_action( 'admin_post_thrg_si_delete_report', array( __CLASS__, 'handle_delete' ) );
        add_action( 'admin_post_thrg_si_clear_history', array( __CLASS__, 'handle_clear' ) );
        add_action( 'admin_post_thrg_si_toggle_history', array( __CLASS__, 'handle_toggle' ) );
        add_action( 'admin_post_thrg_si_email_report', array( __CLASS__, 'handle_email' ) );
    }

    const PAGE_URL = 'tools.php?page=thrg-site-inspector';

    private static function guard( $nonce_action ) {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You do not have permission to do that.', 'thrg-site-inspector' ), '', array( 'response' => 403 ) );
        }
        check_admin_referer( $nonce_action );
    }

    private static function back( $flag ) {
        wp_safe_redirect( add_query_arg( 'thrg_si_done', $flag, admin_url( self::PAGE_URL ) ) );
        exit;
    }

    public static function handle_delete() {
        self::guard( 'thrg_si_delete_report' );
        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- guard() verifies this action's nonce before form data is read.
        $id = isset( $_POST['report'] ) ? sanitize_key( wp_unslash( $_POST['report'] ) ) : '';
        self::back( THRG_SI_History::delete( $id ) ? 'deleted' : 'missing' );
    }

    public static function handle_clear() {
        self::guard( 'thrg_si_clear_history' );
        THRG_SI_History::clear();
        self::back( 'cleared' );
    }

    /** Email a saved report to chosen administrators, then return to that report. */
    public static function handle_email() {
        self::guard( 'thrg_si_email_report' );
        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- guard() verifies this action's nonce before form data is read.
        $id    = isset( $_POST['report'] ) ? sanitize_key( wp_unslash( $_POST['report'] ) ) : '';
        $back  = static function ( $flag, $sent = 0, $failed = 0 ) use ( $id ) {
            $args = array( 'thrg_si_done' => $flag );
            if ( THRG_SI_History::valid_id( $id ) ) {
                $args['thrg_report'] = $id;
            }
            $args['thrg_si_sent']   = (int) $sent;
            $args['thrg_si_failed'] = (int) $failed;
            wp_safe_redirect( add_query_arg( $args, admin_url( self::PAGE_URL ) ) . '#si-share' );
            exit;
        };
        $entry = THRG_SI_History::find( $id );
        if ( ! $entry ) {
            $back( 'missing' );
        }
        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- guard() verifies this action's nonce before form data is read.
        $ids = isset( $_POST['recipients'] ) ? array_filter( array_map( 'absint', (array) wp_unslash( $_POST['recipients'] ) ) ) : array();
        if ( ! $ids ) {
            $back( 'mail-none' );
        }
        $wait_key = 'thrg_si_mail_' . get_current_user_id();
        if ( get_transient( $wait_key ) ) {
            $back( 'mail-wait' );
        }
        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- guard() verifies this action's nonce before form data is read.
        $note  = isset( $_POST['note'] ) ? sanitize_textarea_field( wp_unslash( $_POST['note'] ) ) : '';
        $note  = function_exists( 'mb_substr' ) ? mb_substr( $note, 0, THRG_SI_Email::NOTE_LENGTH ) : substr( $note, 0, THRG_SI_Email::NOTE_LENGTH );
        $older = null;
        $all   = THRG_SI_History::all();
        foreach ( $all as $i => $e ) {
            if ( $e['id'] === $entry['id'] ) {
                $older = isset( $all[ $i + 1 ] ) ? $all[ $i + 1 ] : null;
                break;
            }
        }
        $result = THRG_SI_Email::send( $entry, $older, $ids, $note );
        if ( $result['sent'] ) {
            set_transient( $wait_key, 1, THRG_SI_Email::WAIT_SECONDS );
        }
        $back( $result['sent'] ? ( $result['failed'] ? 'mail-partial' : 'emailed' ) : 'mail-failed', $result['sent'], $result['failed'] );
    }

    public static function handle_toggle() {
        self::guard( 'thrg_si_toggle_history' );
        $on = THRG_SI_History::enabled();
        THRG_SI_History::set_enabled( ! $on );
        self::back( $on ? 'paused' : 'resumed' );
    }

    public static function version() {
        return defined( 'THRG_SI_VERSION' ) ? THRG_SI_VERSION : 'dev';
    }

    public static function assets( $hook ) {
        if ( in_array( $hook, array( 'tools_page_thrg-site-inspector', 'settings_page_thrg-site-health-controls' ), true ) ) {
            wp_enqueue_style( 'thrg-si-brand', plugins_url( '../assets/brand/thrg-brand.css', __FILE__ ), array(), self::version() );
            wp_enqueue_style( 'thrg-inspector', plugins_url( '../assets/inspector.css', __FILE__ ), array(), self::version() );
        }
    }

    public static function menu() {
        $hook = add_management_page( "The Hack Repair Guy's Site Inspector", 'Site Inspector', 'manage_options', 'thrg-site-inspector', array( __CLASS__, 'render' ) );
        add_action( 'load-' . $hook, array( __CLASS__, 'authorize' ) );
    }

    public static function authorize() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You do not have permission to inspect this site.', 'thrg-site-inspector' ), '', array( 'response' => 403 ) );
        }
        if ( 'POST' === ( isset( $_SERVER['REQUEST_METHOD'] ) ? $_SERVER['REQUEST_METHOD'] : '' ) ) {
            check_admin_referer( 'thrg_si_inspect' );
        }
    }

    /** Observation keys with neutral defaults, so evaluation is total. */
    public static function defaults() {
        return array(
            'now'               => time(),
            'debug'             => false,
            'display'           => '0',
            'registration'      => false,
            'multisite'         => false,
            'role'              => 'subscriber',
            'role_found'        => true,
            'role_caps'         => array(),
            'local_environment' => false,
            'home_https'        => true,
            'site_https'        => true,
            'editor_disabled'   => true,
            'config_exists'     => true,
            'config_writable'   => false,
            'config_mode'       => 0440,
            'php_version'       => '8.3.0',
            'weak_salts'        => 0,
            'accounts'          => null,
            'uploads'           => null,
            'sensitive'         => null,
            'software'          => null,
            'hidden_code'       => null,
        );
    }

    /** Collect only bounded local observations. Never read configuration contents. */
    public static function collect() {
        $role        = (string) get_option( 'default_role', 'subscriber' );
        $role_object = get_role( $role );
        $privileged  = array();
        foreach ( array( 'manage_options', 'edit_users', 'promote_users', 'install_plugins', 'activate_plugins', 'edit_posts', 'upload_files' ) as $cap ) {
            if ( $role_object && $role_object->has_cap( $cap ) ) {
                $privileged[] = $cap;
            }
        }
        $config = ABSPATH . 'wp-config.php';
        if ( ! is_file( $config ) && ! is_file( dirname( ABSPATH ) . '/wp-settings.php' ) ) {
            $config = dirname( ABSPATH ) . '/wp-config.php';
        }
        $upload = wp_get_upload_dir(); // Unlike wp_upload_dir(), never creates directories.

        return array(
            'now'               => time(),
            'debug'             => defined( 'WP_DEBUG' ) && WP_DEBUG,
            'display'           => (string) ini_get( 'display_errors' ),
            'registration'      => (bool) get_option( 'users_can_register' ),
            'multisite'         => is_multisite(),
            'role'              => $role,
            'role_found'        => (bool) $role_object,
            'role_caps'         => $privileged,
            'local_environment' => in_array( wp_parse_url( home_url(), PHP_URL_HOST ), array( 'localhost', '127.0.0.1', '[::1]' ), true ) || in_array( wp_get_environment_type(), array( 'local', 'development' ), true ),
            'home_https'        => 'https' === wp_parse_url( home_url(), PHP_URL_SCHEME ),
            'site_https'        => 'https' === wp_parse_url( site_url(), PHP_URL_SCHEME ),
            'editor_disabled'   => ( defined( 'DISALLOW_FILE_EDIT' ) && DISALLOW_FILE_EDIT ) || ( defined( 'DISALLOW_FILE_MODS' ) && DISALLOW_FILE_MODS ),
            'config_exists'     => is_file( $config ),
            'config_writable'   => is_file( $config ) && is_writable( $config ), // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_is_writable
            'config_mode'       => is_file( $config ) ? @fileperms( $config ) : false, // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
            'php_version'       => PHP_VERSION,
            'weak_salts'        => THRG_Inspection_Evidence::weak_salts(),
            'accounts'          => THRG_Inspection_Evidence::accounts(),
            'uploads'           => empty( $upload['error'] ) ? THRG_Inspection_Evidence::uploads( $upload['basedir'] ) : null,
            'sensitive'         => THRG_Inspection_Evidence::sensitive(),
            'software'          => THRG_Inspection_Evidence::software(),
            'hidden_code'       => THRG_Inspection_Evidence::hidden_code(),
        );
    }

    /**
     * Pure evaluation: observations in, findings out. No WordPress calls.
     *
     * @param array $o Observations (missing keys use defaults()).
     * @return array Findings sorted by status.
     */
    public static function evaluate( array $o ) {
        $o    = array_merge( self::defaults(), $o );
        $rows = array();

        $rows[] = self::software_finding( $o );
        $rows[] = self::accounts_finding( $o );
        $rows[] = self::editor_finding( $o );
        $rows[] = self::php_finding( $o );
        $rows[] = self::salts_finding( $o );
        $rows[] = self::sensitive_finding( $o );
        $rows[] = self::uploads_finding( $o );
        $rows[] = self::hidden_code_finding( $o );
        $rows[] = self::inactive_finding( $o );

        // PHP error display.
        $display = ! in_array( strtolower( $o['display'] ), array( '', '0', 'off', 'false' ), true );
        $rows[]  = self::finding(
            'error-display',
            'PHP error display',
            $display ? 'Potential weakness' : 'No issue observed',
            $display ? 'Errors shown on screen can reveal file paths and plugin details that help an attacker.' : 'PHP errors are not being shown on screen.',
            array( 'WP_DEBUG: ' . ( $o['debug'] ? 'on' : 'off' ) . '; runtime display_errors: ' . ( $display ? 'on' : 'off' ) . '.' ),
            'This observes the admin request. Public requests and host overrides may differ.',
            $display ? 'Turn off error display and keep diagnostics in a protected log.' : ''
        );

        // Registration.
        $status = 'No issue observed';
        if ( $o['multisite'] || ! $o['role_found'] ) {
            $status = 'Unable to verify';
        } elseif ( $o['registration'] && $o['role_caps'] ) {
            $status = 'Potential weakness';
        }
        $rows[] = self::act(
            self::finding(
                'registration',
                'Public registration privileges',
                $status,
                'Potential weakness' === $status ? 'Anyone can register, and new accounts receive more than basic access.' : ( 'Unable to verify' === $status ? 'Registration policy could not be confirmed on this setup.' : 'New sign-ups do not receive elevated access.' ),
                array( '[[options-general.php|Anyone can register]]: ' . ( $o['registration'] ? 'yes' : 'no' ) . '; default role: ' . self::plain( $o['role'] ) . '; elevated capabilities: ' . ( $o['role_caps'] ? implode( ', ', $o['role_caps'] ) : 'none' ) . '.' ),
                'Custom registration forms, capability filters, and network signup policy are not verified.',
                $o['multisite'] ? 'Review network registration policy and custom signup flows separately.' : ( 'Potential weakness' === $status ? 'Set the default role to Subscriber under [[options-general.php|Settings > General]], or turn off open registration.' : '' )
            ),
            'No issue observed' === $status ? array() : array( array( 'Open General Settings', 'options-general.php' ) )
        );

        // HTTPS.
        $https_ok = $o['home_https'] && $o['site_https'];
        $rows[]   = self::act(
            self::finding(
                'https-config',
                'Configured HTTPS URLs',
                $https_ok ? 'No issue observed' : ( $o['local_environment'] ? 'Review' : 'Potential weakness' ),
                $https_ok ? 'Both site addresses are set to HTTPS.' : ( $o['local_environment'] ? 'This looks like a local copy, where HTTP is expected.' : 'Logins and cookies can travel unencrypted when the site address uses HTTP.' ),
                array( ( $o['local_environment'] ? 'Local/development environment detected. ' : '' ) . 'Site address HTTPS: ' . ( $o['home_https'] ? 'yes' : 'no' ) . '; WordPress address HTTPS: ' . ( $o['site_https'] ? 'yes' : 'no' ) . '.' ),
                'Settings do not prove the certificate is valid or that HTTP redirects to HTTPS.',
                $https_ok ? '' : ( $o['local_environment'] ? 'HTTP is normal on a local copy. Run this inspection on the live site to check its settings.' : 'Install a certificate and switch both addresses under [[options-general.php|Settings > General]] to https://.' )
            ),
            $https_ok ? array() : array( array( 'Open General Settings', 'options-general.php' ) )
        );

        // wp-config.php permissions. PHP being able to write it is normal on cPanel/PHP-FPM hosting.
        $mode   = $o['config_mode'];
        $known  = $o['config_exists'] && false !== $mode;
        $world  = $known && (bool) ( $mode & 0002 );
        $group  = $known && (bool) ( $mode & 0020 );
        $status = ! $known ? 'Unable to verify' : ( $world ? 'Potential weakness' : ( $group ? 'Review' : 'No issue observed' ) );
        $rows[] = self::finding(
            'config-permissions',
            'wp-config.php permissions',
            $status,
            $world ? 'Any account on the server can change your configuration file.' : ( $group ? 'Other accounts in the same server group can change your configuration file.' : ( $known ? 'Only the site owner can change the configuration file.' : 'wp-config.php was not found in a standard location.' ) ),
            array( 'Found: ' . ( $o['config_exists'] ? 'yes' : 'no' ) . '; permissions: ' . ( false === $mode ? 'unavailable' : sprintf( '%04o', $mode & 0777 ) ) . '; writable by PHP: ' . ( $o['config_writable'] ? 'yes (normal on most cPanel hosting)' : 'no' ) . '.' ),
            'Permission bits do not show ACLs, ownership, or container boundaries. Contents were not read.',
            $world || $group ? 'Set permissions to 0640 or 0600 (ask your host if unsure).' : ( $known ? '' : 'Check a nonstandard configuration location manually.' )
        );

        usort(
            $rows,
            static function ( $a, $b ) {
                return self::STATUS_ORDER[ $a['status'] ] <=> self::STATUS_ORDER[ $b['status'] ];
            }
        );
        return $rows;
    }

    private static function software_finding( array $o ) {
        $s = $o['software'];
        if ( ! is_array( $s ) ) {
            return self::act( self::finding( 'updates', 'Software updates', 'Unable to verify', 'Update information was not available.', array(), 'Update data comes from WordPress\'s own stored update checks.', 'Open [[update-core.php|Dashboard > Updates]] to refresh update information.' ), array( array( 'Open Updates', 'update-core.php' ) ) );
        }
        $age   = $s['update_checked'] ? (int) floor( ( $o['now'] - $s['update_checked'] ) / DAY_IN_SECONDS ) : null;
        $fresh = null === $age ? 'WordPress has not recorded an update check.' : ( 0 === $age ? 'Update data was refreshed today.' : 'Update data is ' . $age . ' day' . ( 1 === $age ? '' : 's' ) . ' old.' );
        $count = count( $s['updates'] );
        if ( $count ) {
            $status = 'Potential weakness';
            $why    = 'Outdated plugins, themes, and core are the most common way WordPress sites are broken into.';
        } elseif ( null === $age ) {
            $status = 'Unable to verify';
            $why    = 'WordPress has no update data, so pending updates could not be listed.';
        } elseif ( $age > 7 ) {
            $status = 'Review';
            $why    = 'No updates are recorded, but the update data is stale.';
        } else {
            $status = 'No issue observed';
            $why    = 'Everything installed is on the latest version WordPress knows about.';
        }
        $table = null;
        if ( $count ) {
            $table = array(
				'head' => array( 'Type', 'Name', 'Installed', 'Available' ),
				'rows' => array(),
			);
            $pages = array(
				'core'   => 'update-core.php',
				'plugin' => 'plugins.php',
				'theme'  => 'themes.php',
			);
            foreach ( $s['updates'] as $u ) {
                $table['rows'][] = array( $u['type'], array( self::plain( $u['name'] ), $pages[ $u['type'] ] ), $u['installed'], array( $u['available'], 'update-core.php' ) );
            }
        }
        return self::act(
            self::finding(
                'updates',
                $count ? sprintf( '%d update%s waiting', $count, 1 === $count ? '' : 's' ) : 'Software updates',
                $status,
                $why,
                array( $fresh . ' [[update-core.php?force-check=1|Check again]]' ),
                'An available update is not proof of a known vulnerability, and an up-to-date plugin can still be vulnerable or abandoned. Data comes from WordPress\'s stored update checks; this inspection sends no requests.',
                $count ? 'Back up, then apply these updates from [[update-core.php|Dashboard > Updates]]. Remove anything you no longer use instead of updating it.' : ( 'No issue observed' === $status ? '' : 'Open [[update-core.php|Dashboard > Updates]] to refresh update information.' ),
                $table
            ),
            array( array( 'Open Updates', 'update-core.php' ) )
        );
    }

    private static function accounts_finding( array $o ) {
        $a = $o['accounts'];
        if ( ! is_array( $a ) ) {
            return self::finding( 'account-access', 'Who can change this site?', 'Unable to verify', 'Accounts were not inspected.', array(), '', '' );
        }
        $admin_caps = array( 'manage_options', 'promote_users', 'edit_users', 'install_plugins' );
        $recent     = array();
        $unusual    = array();
        $table      = array(
			'head' => array( 'ID', 'Username', 'Role', 'Created', 'Note' ),
			'rows' => array(),
			'mask' => 1,
		);
        foreach ( $a['items'] as $u ) {
            $note    = array();
            $created = strtotime( $u['registered'] . ' UTC' );
            if ( $created && ( $o['now'] - $created ) < 30 * DAY_IN_SECONDS ) {
                $recent[] = $u['login'];
                $note[]   = 'created in the last 30 days';
            }
            if ( ! in_array( 'administrator', $u['roles'], true ) && array_intersect( $admin_caps, $u['caps'] ) ) {
                $unusual[] = $u['login'];
                $note[]    = 'admin-level access without the Administrator role: ' . implode( ', ', array_intersect( $admin_caps, $u['caps'] ) );
            }
            $edit            = 'user-edit.php?user_id=' . (int) $u['id'];
            $table['rows'][] = array( array( '#' . $u['id'], $edit ), array( $u['login'], $edit ), $u['roles'] ? implode( ', ', $u['roles'] ) : '(no role)', $created ? gmdate( 'Y-m-d', $created ) : 'unknown', $note ? implode( '; ', $note ) : '' );
        }
        if ( $unusual || $recent ) {
            $status = 'Review';
            $why    = $unusual
                ? 'Some accounts have administrator-level power without the Administrator role, a pattern often seen after a compromise.'
                : 'A powerful account was created recently. Make sure you know who created it.';
        } else {
            $status = $a['partial'] ? 'Partial inspection' : 'No issue observed';
            $why    = count( $a['items'] ) . ' account' . ( 1 === count( $a['items'] ) ? '' : 's' ) . ' can change site settings, users, or code. None look unusual.';
        }
        return self::act(
            self::finding(
                'account-access',
                'Who can change this site?',
                $status,
                $why,
                array( $a['checked'] . ' site accounts checked' . ( $a['partial'] ? ' (limit reached; more accounts exist)' : '' ) . '.' ),
                'Includes roles and direct capability grants. Network-only super administrators, context-dependent filters, and custom authorization paths may differ. At most 200 accounts are checked.',
                'Review' === $status ? 'Confirm you recognize each flagged account. Remove or downgrade any you do not in [[users.php|Users]], then change administrator passwords.' : 'Remove accounts that no longer need this access in [[users.php|Users]].',
                $table['rows'] ? $table : null
            ),
            array( array( 'Open Users', 'users.php' ) )
        );
    }

    private static function editor_finding( array $o ) {
        $editors = 0;
        if ( is_array( $o['accounts'] ) ) {
            foreach ( $o['accounts']['items'] as $u ) {
                if ( array_intersect( array( 'edit_plugins', 'edit_themes' ), $u['caps'] ) ) {
                    ++$editors;
                }
            }
        }
        $line = 'DISALLOW_FILE_EDIT or DISALLOW_FILE_MODS set: ' . ( $o['editor_disabled'] ? 'yes' : 'no' ) . '.';
        if ( ! $o['editor_disabled'] && is_array( $o['accounts'] ) ) {
            $line .= ' [[users.php|Accounts able to edit plugin or theme code]]: ' . $editors . '.';
        }
        return self::finding(
            'file-editor',
            'Built-in code editor',
            $o['editor_disabled'] ? 'No issue observed' : 'Review',
            $o['editor_disabled'] ? 'The plugin and theme code editor is switched off.' : 'If an administrator login is stolen, the built-in editor lets an attacker put code straight into a plugin or theme.',
            array( $line ),
            'This is an administrative capability, not an unauthenticated vulnerability. Other plugins or roles may already restrict it.',
            $o['editor_disabled'] ? '' : 'Add define( \'DISALLOW_FILE_EDIT\', true ); to wp-config.php unless you rely on the editor.'
        );
    }

    /**
     * @param string $version PHP version.
     * @param int    $now     Timestamp.
     * @return array{0:string,1:string|null} Status and end-of-support date.
     */
    public static function php_status( $version, $now ) {
        $branch = implode( '.', array_slice( explode( '.', (string) $version ), 0, 2 ) );
        if ( version_compare( $branch, '7.4', '<' ) ) {
            return array( 'Potential weakness', null );
        }
        if ( ! isset( self::PHP_SUPPORT[ $branch ] ) ) {
            return array( 'No issue observed', null );
        }
        $end = strtotime( self::PHP_SUPPORT[ $branch ] . ' 23:59:59 UTC' );
        if ( $now > $end ) {
            return array( 'Potential weakness', self::PHP_SUPPORT[ $branch ] );
        }
        return array( ( $end - $now ) < 180 * DAY_IN_SECONDS ? 'Review' : 'No issue observed', self::PHP_SUPPORT[ $branch ] );
    }

    private static function php_finding( array $o ) {
        list( $status, $end ) = self::php_status( $o['php_version'], $o['now'] );
        $why                  = 'Potential weakness' === $status
            ? 'This PHP version no longer receives security fixes.'
            : ( 'Review' === $status ? 'This PHP version stops receiving security fixes soon.' : 'This PHP version still receives security fixes.' );
        return self::act(
            self::finding(
                'php-version',
                'PHP version',
                $status,
                $why,
                array( 'PHP ' . $o['php_version'] . ( $end ? '; security support ' . ( 'Potential weakness' === $status ? 'ended ' : 'ends ' ) . $end : '' ) . '.' ),
                'Based on php.net support dates built into this release. Some hosts and distributions backport fixes to older versions.',
                'No issue observed' === $status ? '' : 'Test the site on a supported PHP version (8.3 or newer), then switch in cPanel > Select PHP Version or MultiPHP Manager.'
            ),
            array( array( 'PHP supported versions', 'https://www.php.net/supported-versions.php' ) )
        );
    }

    private static function salts_finding( array $o ) {
        $weak = (int) $o['weak_salts'];
        return self::act(
            self::finding(
                'security-keys',
                'Security keys and salts',
                $weak ? 'Potential weakness' : 'No issue observed',
                $weak ? 'Missing or placeholder keys make login cookies easier to forge.' : 'All eight security keys are set to unique-looking values.',
                array( $weak . ' of 8 keys missing, shorter than 32 characters, or still set to the sample placeholder.' ),
                'Only presence, length, and the sample placeholder are checked. Values are never displayed or stored. Keys copied from another site would not be detected.',
                $weak ? 'Generate fresh keys with the [[https://api.wordpress.org/secret-key/1.1/salt/|WordPress.org key generator]] and replace them in wp-config.php. Everyone will be logged out.' : ''
            ),
            $weak ? array( array( 'Generate new keys', 'https://api.wordpress.org/secret-key/1.1/salt/' ) ) : array()
        );
    }

    private static function sensitive_finding( array $o ) {
        $items = $o['sensitive'];
        if ( ! is_array( $items ) ) {
            return self::finding( 'sensitive-files', 'Exposed backups and logs', 'Unable to verify', 'Sensitive-file locations were not inspected.', array(), '', '' );
        }
        $table = null;
        if ( $items ) {
            $table = array(
				'head' => array( 'File', 'Size', 'Modified' ),
				'rows' => array(),
			);
            foreach ( $items as $f ) {
                $table['rows'][] = array( $f['path'], self::bytes( $f['size'] ), $f['modified'] ? gmdate( 'Y-m-d', $f['modified'] ) : '' );
            }
        }
        return self::finding(
            'sensitive-files',
            'Exposed backups and logs',
            $items ? 'Potential weakness' : 'No issue observed',
            $items ? 'Backups, logs, and leftover config copies in the web folder can often be downloaded by anyone who guesses the name.' : 'No backups, logs, or leftover config copies were found in the usual places.',
            array( $items ? count( $items ) . ' candidate file' . ( 1 === count( $items ) ? '' : 's' ) . ' found.' : 'Checked common names in the site root, error_log files, debug logs, and archives in the web root.' ),
            'Archives are checked in the web root only, not recursively. Contents were not read and download access was not tested; some files may be protected by server rules.',
            $items ? 'Delete files you do not need. Move needed backups outside the public folder, and keep debug logging off on live sites.' : '',
            $table
        );
    }

    private static function uploads_finding( array $o ) {
        $u = $o['uploads'];
        if ( ! is_array( $u ) || ! empty( $u['missing'] ) ) {
            return self::finding( 'uploads-files', 'PHP files in uploads', 'Unable to verify', 'The uploads folder could not be read.', array(), '', '' );
        }
        $table = null;
        if ( $u['items'] ) {
            $table = array(
				'head' => array( 'File', 'Size', 'Modified' ),
				'rows' => array(),
			);
            foreach ( $u['items'] as $f ) {
                $table['rows'][] = array( $f['path'], null === $f['size'] ? '' : self::bytes( $f['size'] ), $f['modified'] ? gmdate( 'Y-m-d', $f['modified'] ) : '' );
            }
        }
        $lines = array( number_format( $u['checked'] ) . ' entries examined' . ( $u['partial'] ? ' before a limit was reached (coverage incomplete)' : '' ) . '.' );
        if ( $u['placeholders'] ) {
            $lines[] = count( $u['placeholders'] ) . ' tiny index.php placeholder file' . ( 1 === count( $u['placeholders'] ) ? '' : 's' ) . ' (plugins add these to block folder listing) not counted.';
        }
        $lines[] = 'Server rule files in uploads: ' . ( $u['rules'] ? implode( ', ', $u['rules'] ) : 'none found' ) . '.';
        $status  = $u['items'] ? 'Potential weakness' : ( $u['partial'] ? 'Partial inspection' : 'No issue observed' );
        return self::finding(
            'uploads-files',
            'PHP files in uploads',
            $status,
            $u['items'] ? 'Uploads should hold media. A PHP file here is a common sign of a backdoor.' : ( $u['partial'] ? 'No PHP files found in the part of uploads that was checked.' : 'No PHP files found in uploads.' ),
            $lines,
            'Judged by filename only; contents, execution, and public access were not tested. Index.php files of 100 bytes or less are treated as placeholders. Limits: 20,000 entries, five seconds, 50 results. Symlinks are skipped.',
            $u['items'] ? 'Check each file\'s date against your own uploads. If you do not recognize it, treat the site as possibly compromised.' : '',
            $table
        );
    }

    private static function hidden_code_finding( array $o ) {
        $h = $o['hidden_code'];
        if ( ! is_array( $h ) ) {
            return self::finding( 'hidden-code', 'Code outside the plugin list', 'Unable to verify', 'Must-use plugins and drop-ins were not inspected.', array(), '', '' );
        }
        $all   = array_merge( $h['mu'], $h['dropins'] );
        $table = null;
        if ( $all ) {
            $table = array(
				'head' => array( 'File', 'Modified' ),
				'rows' => array(),
			);
            foreach ( $h['mu'] as $f ) {
                $table['rows'][] = array( array( $f['path'], 'plugins.php?plugin_status=mustuse' ), $f['modified'] ? gmdate( 'Y-m-d', $f['modified'] ) : '' );
            }
            foreach ( $h['dropins'] as $f ) {
                $table['rows'][] = array( array( $f['path'], 'plugins.php?plugin_status=dropins' ), $f['modified'] ? gmdate( 'Y-m-d', $f['modified'] ) : '' );
            }
        }
        $actions = array();
        if ( $h['mu'] ) {
            $actions[] = array( 'View must-use plugins', 'plugins.php?plugin_status=mustuse' );
        }
        if ( $h['dropins'] ) {
            $actions[] = array( 'View drop-ins', 'plugins.php?plugin_status=dropins' );
        }
        return self::act(
            self::finding(
                'hidden-code',
                'Code outside the plugin list',
                $all ? 'Review' : 'No issue observed',
                $all ? 'Must-use plugins and drop-ins run on every page but don\'t appear as normal plugins, which makes them a favorite hiding place for malware.' : 'No must-use plugins or drop-ins are installed.',
                array( '[[plugins.php?plugin_status=mustuse|' . count( $h['mu'] ) . ' must-use item(s)]], [[plugins.php?plugin_status=dropins|' . count( $h['dropins'] ) . ' drop-in(s)]].' ),
                'Listed by name only. Hosts and caching plugins often install legitimate ones (for example object-cache.php or advanced-cache.php).',
                $all ? 'Confirm you or your host recognize each file. Unexpected entries deserve a closer look.' : '',
                $table
            ),
            $actions
        );
    }

    private static function inactive_finding( array $o ) {
        $s = $o['software'];
        if ( ! is_array( $s ) ) {
            return self::finding( 'inactive', 'Unused plugins and themes', 'Unable to verify', 'Installed software was not inspected.', array(), '', '' );
        }
        $plugins = $s['inactive_plugins'];
        $themes  = $s['inactive_themes'];
        // Keeping one spare theme as a fallback is reasonable.
        $extra   = count( $plugins ) + max( 0, count( $themes ) - 1 );
        $lines   = array();
        $lines[] = '[[plugins.php?plugin_status=inactive|Inactive plugins]]: ' . ( $plugins ? self::plain( implode( ', ', $plugins ) ) : 'none' ) . '.';
        $lines[] = '[[themes.php|Inactive themes]]: ' . ( $themes ? self::plain( implode( ', ', $themes ) ) : 'none' ) . '.';
        return self::act(
            self::finding(
                'inactive',
                'Unused plugins and themes',
                $extra ? 'Review' : 'No issue observed',
                $extra ? 'Deactivated plugins and themes are still on the server, and their files can still be attacked.' : 'No unused software beyond one spare theme.',
                $lines,
                'A deactivated plugin\'s code can still be reached directly in some cases. Removing it removes that risk.',
                $extra ? 'Delete what you don\'t use from [[plugins.php?plugin_status=inactive|Plugins]] and [[themes.php|Themes]]. Keep one default theme as a fallback.' : ''
            ),
            $extra ? array( array( 'Manage plugins', 'plugins.php?plugin_status=inactive' ), array( 'Manage themes', 'themes.php' ) ) : array()
        );
    }

    /**
     * Build a finding.
     *
     * @param string     $id       Anchor ID.
     * @param string     $title    Title.
     * @param string     $status   Status label.
     * @param string     $why      Plain-language reason it matters.
     * @param string[]   $evidence Observation lines.
     * @param string     $limits   What the check cannot establish.
     * @param string     $next     Suggested next step ('' for none).
     * @param array|null $table    Optional table: head, rows, mask (username column index).
     * @return array
     */
    // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed -- compact() consumes all named parameters.
    private static function finding( $id, $title, $status, $why, $evidence, $limits, $next, $table = null ) {
        return compact( 'id', 'title', 'status', 'why', 'evidence', 'limits', 'next', 'table' ) + array( 'actions' => array() );
    }

    /** Attach button links (label, admin path or allowlisted URL) to a finding. */
    private static function act( array $row, array $actions ) {
        $row['actions'] = $actions;
        return $row;
    }

    /** Keep dynamic names (plugin, theme, role) from forming link tokens. */
    private static function plain( $text ) {
        return str_replace( array( '[[', ']]' ), array( '[ [', '] ]' ), (string) $text );
    }

    /**
     * Resolve a link target. Only wp-admin pages and two fixed external references are allowed.
     *
     * @param string $target Admin page such as plugins.php?plugin_status=inactive, or an allowlisted URL.
     * @return array{url:string,external:bool}|null
     */
    public static function href( $target ) {
        $external = array( 'https://www.php.net/supported-versions.php', 'https://api.wordpress.org/secret-key/1.1/salt/' );
        if ( in_array( $target, $external, true ) ) {
            return array(
				'url'      => $target,
				'external' => true,
			);
        }
        if ( preg_match( '/^[a-z0-9_-]+\.php(?:\?[a-z0-9_=.-]+)?$/i', (string) $target ) ) {
            return array(
				'url'      => admin_url( $target ),
				'external' => false,
			);
        }
        return null;
    }

    /** Escape text, then turn [[target|label]] tokens into links. */
    public static function linkify( $text ) {
        return preg_replace_callback(
            '/\[\[([^|\]]+)\|([^\]]+)\]\]/',
            static function ( $m ) {
                $href = self::href( $m[1] );
                if ( ! $href ) {
                    return $m[2];
                }
                return '<a href="' . esc_url( $href['url'] ) . '" target="_blank" rel="noopener noreferrer">' . $m[2] . self::NEW_TAB_HINT . '</a>';
            },
            esc_html( (string) $text )
        );
    }

    public static function strip_links( $text ) {
        return preg_replace( '/\[\[[^|\]]+\|([^\]]+)\]\]/', '$1', (string) $text );
    }

    public static function bytes( $size ) {
        $size  = (float) $size;
        $units = array( 'B', 'KB', 'MB', 'GB' );
        $i     = 0;
        while ( $size >= 1024 && $i < 3 ) {
            $size /= 1024;
            ++$i;
        }
        return ( $i ? number_format( $size, 1 ) : (string) (int) $size ) . ' ' . $units[ $i ];
    }

    /** Hide most of a username for printed reports. */
    public static function mask( $login ) {
        $login = (string) $login;
        return strlen( $login ) <= 2 ? str_repeat( '•', strlen( $login ) ) : substr( $login, 0, 2 ) . str_repeat( '•', min( 8, strlen( $login ) - 2 ) );
    }

    /** Plain-text report for records or a support ticket. */
    public static function plain_text( array $rows, $site, $when ) {
        $out = "Site Inspector report\nSite: {$site}\nInspected: {$when}\n";
        foreach ( $rows as $r ) {
            $out .= "\n[" . strtoupper( $r['status'] ) . '] ' . $r['title'] . "\n" . self::strip_links( $r['why'] ) . "\n";
            foreach ( $r['evidence'] as $line ) {
                $out .= '  - ' . self::strip_links( str_replace( ' [[update-core.php?force-check=1|Check again]]', '', $line ) ) . "\n";
            }
            if ( $r['table'] ) {
                foreach ( $r['table']['rows'] as $cells ) {
                    $texts = array_map(
                        static function ( $c ) {
							return is_array( $c ) ? (string) $c[0] : (string) $c;
						},
                        $cells
                    );
                    $out  .= '  * ' . implode( ' | ', array_filter( $texts, 'strlen' ) ) . "\n";
                }
            }
            if ( '' !== $r['next'] ) {
                $out .= 'Next: ' . self::strip_links( $r['next'] ) . "\n";
            }
        }
        return $out;
    }

    public static function render() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You do not have permission to inspect this site.', 'thrg-site-inspector' ), '', array( 'response' => 403 ) );
        }
        $rows      = null;
        $saved     = null;
        $flag      = '';
        $report_id = null;
        if ( 'POST' === ( isset( $_SERVER['REQUEST_METHOD'] ) ? $_SERVER['REQUEST_METHOD'] : '' ) ) {
            check_admin_referer( 'thrg_si_inspect' );
            $rows      = self::evaluate( self::collect() );
            $user      = wp_get_current_user();
            $report_id = THRG_SI_History::add( $rows, wp_parse_url( home_url(), PHP_URL_HOST ), self::version(), $user->ID, $user->user_login, time() );
        } elseif ( isset( $_GET['thrg_report'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only view, capability checked above.
            $saved = THRG_SI_History::find( sanitize_key( wp_unslash( $_GET['thrg_report'] ) ) );
            if ( $saved ) {
                $rows      = $saved['rows'];
                $report_id = $saved['id'];
            } else {
                $flag = 'missing';
            }
        }
        if ( '' === $flag && isset( $_GET['thrg_si_done'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- display-only flag after our own redirect.
            $flag = sanitize_key( wp_unslash( $_GET['thrg_si_done'] ) );
        }
        $history     = THRG_SI_History::all();
        $recipients  = null === $rows ? array() : THRG_SI_Email::recipients();
        $sent        = isset( $_GET['thrg_si_sent'] ) ? absint( $_GET['thrg_si_sent'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- display-only count after our own redirect.
        $mail_failed = isset( $_GET['thrg_si_failed'] ) ? absint( $_GET['thrg_si_failed'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Display-only count, never used to authorize a send.
        require __DIR__ . '/view-inspector.php';
    }
}
