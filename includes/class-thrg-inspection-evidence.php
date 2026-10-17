<?php
/**
 * Bounded, read-only local evidence collectors.
 *
 * Collectors never read file contents, never follow symlinks, never write,
 * and never send network requests. Interpretation lives in
 * THRG_Site_Inspector::evaluate().
 *
 * @package THRG_Site_Inspector
 */

defined( 'ABSPATH' ) || exit;

final class THRG_Inspection_Evidence {

    const ACCOUNT_LIMIT  = 200;
    const UPLOAD_LIMIT   = 20000;
    const UPLOAD_SECONDS = 5;
    const RESULT_LIMIT   = 50;

    /** A placeholder index.php ("Silence is golden") is tiny; size is checked, contents are not read. */
    const PLACEHOLDER_BYTES = 100;

    /** Capabilities that let an account take over or reshape the site. */
    public static function watched_caps() {
        return array( 'manage_options', 'promote_users', 'edit_users', 'install_plugins', 'activate_plugins', 'edit_plugins', 'edit_themes' );
    }

    /**
     * Accounts holding any watched capability (role inheritance and direct grants).
     *
     * @return array
     */
    public static function accounts() {
        $users = get_users(
            array(
                'blog_id' => get_current_blog_id(),
                'number'  => self::ACCOUNT_LIMIT + 1,
                'orderby' => 'ID',
                'order'   => 'ASC',
            )
        );
        $items = array();
        foreach ( array_slice( $users, 0, self::ACCOUNT_LIMIT ) as $user ) {
            $caps = array();
            foreach ( self::watched_caps() as $cap ) {
                if ( user_can( $user, $cap ) ) {
                    $caps[] = $cap;
                }
            }
            if ( $caps ) {
                $items[] = array(
                    'id'         => (int) $user->ID,
                    'login'      => (string) $user->user_login,
                    'roles'      => array_values( (array) $user->roles ),
                    'registered' => (string) $user->user_registered,
                    'caps'       => $caps,
                );
            }
        }
        return array(
            'items'   => $items,
            'partial' => count( $users ) > self::ACCOUNT_LIMIT,
            'checked' => min( count( $users ), self::ACCOUNT_LIMIT ),
        );
    }

    /**
     * Walk uploads looking for executable-looking filenames.
     * Every directory entry counts against the budget. Symlinks are skipped.
     *
     * @param string $root    Uploads base directory.
     * @param int    $limit   Maximum entries examined.
     * @param int    $seconds Time budget.
     * @return array
     */
    public static function uploads( $root, $limit = self::UPLOAD_LIMIT, $seconds = self::UPLOAD_SECONDS ) {
        $result = array(
			'items'        => array(),
			'placeholders' => array(),
			'partial'      => false,
			'checked'      => 0,
			'rules'        => array(),
			'missing'      => false,
		);
        if ( ! is_dir( $root ) || is_link( $root ) || ! is_readable( $root ) ) {
            $result['partial'] = true;
            $result['missing'] = true;
            return $result;
        }
        $queue    = array( $root );
        $deadline = microtime( true ) + $seconds;
        while ( $queue ) {
            $dir    = array_shift( $queue );
            $handle = @opendir( $dir ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
            if ( false === $handle ) {
                $result['partial'] = true;
                continue;
            }
            while ( true ) {
                $name = readdir( $handle );
                if ( false === $name ) {
                    break;
                }
                if ( '.' === $name || '..' === $name ) {
                    continue;
                }
                if ( $result['checked'] >= $limit || microtime( true ) >= $deadline ) {
                    $result['partial'] = true;
                    closedir( $handle );
                    return $result;
                }
                ++$result['checked'];
                $path = $dir . '/' . $name;
                if ( is_link( $path ) ) {
                    $result['partial'] = true;
                    continue;
                }
                if ( is_dir( $path ) ) {
                    $queue[] = $path;
                    continue;
                }
                $rel = substr( $path, strlen( $root ) + 1 );
                if ( preg_match( '/\.(?:php[0-9]*|phtml|pht|phar)(?:\.|$)/i', $name ) ) {
                    $size = @filesize( $path ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
                    if ( 'index.php' === strtolower( $name ) && false !== $size && $size <= self::PLACEHOLDER_BYTES ) {
                        if ( count( $result['placeholders'] ) < self::RESULT_LIMIT ) {
                            $result['placeholders'][] = $rel;
                        }
                    } elseif ( count( $result['items'] ) < self::RESULT_LIMIT ) {
                        $result['items'][] = array(
							'path'     => $rel,
							'size'     => false === $size ? null : (int) $size,
								// phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- The file can disappear during this bounded read-only scan.
							'modified' => (int) @filemtime( $path ),
						);
                    } else {
                        $result['partial'] = true;
                    }
                }
                if ( in_array( strtolower( $name ), array( '.htaccess', 'web.config', '.user.ini' ), true ) && count( $result['rules'] ) < 20 ) {
                    $result['rules'][] = $rel;
                }
            }
            closedir( $handle );
        }
        return $result;
    }

    /**
     * Sensitive-file candidates: fixed names, root-level backups, PHP error logs.
     * Only names, sizes and dates are collected.
     *
     * @param string $root Installation root (ABSPATH).
     * @return array
     */
    public static function sensitive( $root = ABSPATH ) {
        $root  = rtrim( $root, '/\\' ) . '/';
        $found = array();
        $add   = static function ( $path, $label ) use ( &$found ) {
            if ( isset( $found[ $label ] ) || is_link( $path ) || ! is_file( $path ) ) {
                return;
            }
            $found[ $label ] = array(
                'path'     => $label,
                'size'     => (int) @filesize( $path ), // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
                'modified' => (int) @filemtime( $path ), // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
            );
        };

        $fixed = array(
            'wp-config.php.bak',
			'wp-config.php.old',
			'wp-config.php.orig',
			'wp-config.php.save',
			'wp-config.php~',
            'wp-config.php.swp',
			'.wp-config.php.swp',
			'wp-config.bak',
			'wp-config.txt',
            '.env',
			'.git/config',
			'backup.sql',
			'database.sql',
			'dump.sql',
            'phpinfo.php',
			'info.php',
			'adminer.php',
			'error_log',
			'php_errorlog',
        );
        foreach ( $fixed as $name ) {
            $add( $root . $name, $name );
        }

        // PHP on cPanel writes error_log beside the script that failed.
        foreach ( array( 'wp-admin', 'wp-includes', 'wp-content' ) as $dir ) {
            $add( $root . $dir . '/error_log', $dir . '/error_log' );
        }

        // Default and custom WordPress debug logs.
        $content = defined( 'WP_CONTENT_DIR' ) ? WP_CONTENT_DIR : $root . 'wp-content';
        $add( $content . '/debug.log', 'wp-content/debug.log' );
        if ( defined( 'WP_DEBUG_LOG' ) && is_string( WP_DEBUG_LOG ) && '' !== WP_DEBUG_LOG ) {
            $label = 0 === strpos( WP_DEBUG_LOG, $root ) ? substr( WP_DEBUG_LOG, strlen( $root ) ) : WP_DEBUG_LOG;
            $add( WP_DEBUG_LOG, $label . ' (WP_DEBUG_LOG)' );
        }

        // Archives and database dumps sitting in the web root (not recursive).
        $entries = @scandir( $root ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
        if ( is_array( $entries ) ) {
            foreach ( array_slice( $entries, 0, 2000 ) as $name ) {
                if ( preg_match( '/\.(?:sql|sql\.gz|zip|tar|tar\.gz|tgz|gz|bak|7z|rar)$/i', $name ) ) {
                    $add( $root . $name, $name );
                }
            }
        }

        return array_values( array_slice( $found, 0, self::RESULT_LIMIT ) );
    }

    /**
     * Installed software, pending updates (from WordPress's own stored update data) and inactive extensions.
     *
     * @return array
     */
    public static function software() {
        if ( ! function_exists( 'get_plugins' ) ) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        $plugins = get_plugins();
        $active  = (array) get_option( 'active_plugins', array() );
        if ( is_multisite() ) {
            $active = array_merge( $active, array_keys( (array) get_site_option( 'active_sitewide_plugins', array() ) ) );
        }

        $plugin_data = get_site_transient( 'update_plugins' );
        $theme_data  = get_site_transient( 'update_themes' );
        $core_data   = get_site_transient( 'update_core' );

        $updates = array();
        if ( is_object( $core_data ) && ! empty( $core_data->updates ) && is_array( $core_data->updates ) ) {
            foreach ( $core_data->updates as $offer ) {
                if ( isset( $offer->response, $offer->current ) && 'upgrade' === $offer->response ) {
                    $updates[] = array(
						'type'      => 'core',
						'name'      => 'WordPress',
						'installed' => get_bloginfo( 'version' ),
						'available' => (string) $offer->current,
					);
                    break;
                }
            }
        }
        if ( is_object( $plugin_data ) && ! empty( $plugin_data->response ) && is_array( $plugin_data->response ) ) {
            foreach ( $plugin_data->response as $file => $offer ) {
                if ( isset( $plugins[ $file ] ) && isset( $offer->new_version ) ) {
                    $updates[] = array(
                        'type'      => 'plugin',
                        'name'      => $plugins[ $file ]['Name'] . ( in_array( $file, $active, true ) ? '' : ' (inactive)' ),
                        'installed' => $plugins[ $file ]['Version'],
                        'available' => (string) $offer->new_version,
                    );
                }
            }
        }
        $themes = wp_get_themes();
        if ( is_object( $theme_data ) && ! empty( $theme_data->response ) && is_array( $theme_data->response ) ) {
            foreach ( $theme_data->response as $slug => $offer ) {
                if ( isset( $themes[ $slug ] ) && isset( $offer['new_version'] ) ) {
                    $updates[] = array(
						'type'      => 'theme',
						'name'      => $themes[ $slug ]->get( 'Name' ),
						'installed' => $themes[ $slug ]->get( 'Version' ),
						'available' => (string) $offer['new_version'],
					);
                }
            }
        }

        $checked = array();
        foreach ( array( $plugin_data, $theme_data, $core_data ) as $data ) {
            $checked[] = is_object( $data ) && ! empty( $data->last_checked ) ? (int) $data->last_checked : 0;
        }

        $inactive_plugins = array();
        foreach ( $plugins as $file => $data ) {
            if ( ! in_array( $file, $active, true ) ) {
                $inactive_plugins[] = $data['Name'];
            }
        }
        $inactive_themes = array();
        $stylesheet      = get_stylesheet();
        $template        = get_template();
        foreach ( $themes as $slug => $theme ) {
            if ( $slug !== $stylesheet && $slug !== $template ) {
                $inactive_themes[] = $theme->get( 'Name' );
            }
        }

        return array(
            'updates'          => $updates,
            'update_checked'   => min( $checked ),
            'inactive_plugins' => $inactive_plugins,
            'inactive_themes'  => $inactive_themes,
            'plugin_count'     => count( $plugins ),
        );
    }

    /**
     * Code that loads outside the normal plugin list: must-use plugins and drop-ins.
     *
     * @return array
     */
    public static function hidden_code() {
        if ( ! function_exists( 'get_dropins' ) ) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        $mu  = array();
        $dir = defined( 'WPMU_PLUGIN_DIR' ) ? WPMU_PLUGIN_DIR : '';
        if ( $dir && is_dir( $dir ) && ! is_link( $dir ) ) {
            $entries = @scandir( $dir ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
            foreach ( is_array( $entries ) ? array_slice( $entries, 0, 500 ) : array() as $name ) {
                if ( '.' === $name[0] ) {
                    continue;
                }
                $path = $dir . '/' . $name;
                if ( is_dir( $path ) ) {
                    $mu[] = array(
						'path'     => 'mu-plugins/' . $name . '/',
							// phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- The path can disappear during this bounded read-only scan.
						'modified' => (int) @filemtime( $path ),
					);
                } elseif ( preg_match( '/\.php[0-9]*$/i', $name ) ) {
                    $mu[] = array(
						'path'     => 'mu-plugins/' . $name,
							// phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- The file can disappear during this bounded read-only scan.
						'modified' => (int) @filemtime( $path ),
					);
                }
            }
        }
        $dropins = array();
        foreach ( array_keys( get_dropins() ) as $file ) {
            $dropins[] = array(
				'path'     => 'wp-content/' . $file,
					// phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- The drop-in can disappear during this bounded read-only scan.
				'modified' => (int) @filemtime( WP_CONTENT_DIR . '/' . $file ),
			);
        }
        return array(
			'mu'      => array_slice( $mu, 0, self::RESULT_LIMIT ),
			'dropins' => $dropins,
		);
    }

    /**
     * Count security keys/salts that are missing, empty, short, or the sample placeholder.
     * Values are never stored or displayed.
     *
     * @return int
     */
    public static function weak_salts() {
        $weak = 0;
        foreach ( array( 'AUTH_KEY', 'SECURE_AUTH_KEY', 'LOGGED_IN_KEY', 'NONCE_KEY', 'AUTH_SALT', 'SECURE_AUTH_SALT', 'LOGGED_IN_SALT', 'NONCE_SALT' ) as $name ) {
            $value = defined( $name ) ? (string) constant( $name ) : '';
            if ( strlen( $value ) < 32 || false !== stripos( $value, 'put your unique phrase here' ) ) {
                ++$weak;
            }
        }
        return $weak;
    }
}
