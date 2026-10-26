<?php
/**
 * Plugin Name:       The Hack Repair Guy's Site Inspector
 * Plugin URI:        https://github.com/hackrepair/thrg-site-inspector
 * Description:       Your friendly WordPress security inspector. Keeps a history of your reports and shows what changed. Includes optional Site Health controls.
 * Version:           2.3.1
 * Author:            The Hack Repair Guy
 * Author URI:        https://hackrepair.com/
 * Update URI:        https://github.com/hackrepair/thrg-site-inspector
 * Requires at least: 6.9.4
 * Requires PHP:      7.4
 * Text Domain:       thrg-site-inspector
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 *
 * Adapted from a 2019 plugin by Louis Fico, licensed GPLv2 or later.
 *
 * @package THRG_Site_Inspector
 */

defined( 'ABSPATH' ) || exit;

const THRG_SI_VERSION = '2.3.1';

/*
 * GitHub release updates. Deliberately NOT gated by is_admin(): WordPress's
 * update-check and auto-update machinery runs on cron, where is_admin() is false.
 * THRG_SI_SKIP_UPDATER is only defined by tests/smoke.php.
 */
if ( ! defined( 'THRG_SI_SKIP_UPDATER' ) ) {
    $thrg_shc_puc = plugin_dir_path( __FILE__ ) . 'plugin-update-checker/plugin-update-checker.php';
    if ( is_readable( $thrg_shc_puc ) ) {
        require_once $thrg_shc_puc;
    }
    unset( $thrg_shc_puc );

    require_once plugin_dir_path( __FILE__ ) . 'includes/class-thrg-si-updater.php';
    $thrg_shc_updater = new THRG_SI_Updater();
    $thrg_shc_updater->init( __FILE__ );
    unset( $thrg_shc_updater );
}

require_once __DIR__ . '/includes/class-thrg-site-inspector.php';
THRG_Site_Inspector::boot();

const THRG_SHC_OPTION = 'thrg_shc_options';

/**
 * The folder this plugin is installed in. It is the plugin's slug in WordPress
 * and stays whatever it was when the site installed the plugin.
 *
 * @return string
 */
function thrg_si_slug() {
    return dirname( plugin_basename( __FILE__ ) );
}

/**
 * Default settings disable both the interface and Site Health diagnostics.
 * Saved settings override these defaults; user exemptions are off by default.
 *
 * @return array
 */
function thrg_shc_defaults() {
    return array(
        'hide_menu'        => 1,
        'redirect'         => 1,
        'dashboard_widget' => 1,
        'disable_tests'    => 1,
        'disable_cron'     => 1,
        'block_rest'       => 1,
        'block_ajax'       => 1,
        'allow_admins'     => 0,
        'exempt_users'     => '',
        'delete_data'      => 0,
    );
}

/**
 * Get the saved settings merged with defaults.
 *
 * @return array
 */
function thrg_shc_get_options() {
    $saved = get_option( THRG_SHC_OPTION, array() );
    return wp_parse_args( is_array( $saved ) ? $saved : array(), thrg_shc_defaults() );
}

/**
 * Check whether a single feature toggle is enabled.
 *
 * @param string $key Option key.
 * @return bool
 */
function thrg_shc_enabled( $key ) {
    $options = thrg_shc_get_options();
    return ! empty( $options[ $key ] );
}

/**
 * Whether the current user is exempt from the restrictions.
 *
 * Exempt users are administrators (when "allow administrators" is enabled,
 * or THRG_SHC_ALLOW_ADMINS is true in wp-config.php) and any listed user IDs.
 *
 * @return bool
 */
function thrg_shc_is_exempt() {
    $user_id = (int) get_current_user_id();
    $exempt  = false;

    if ( $user_id ) {
        $options      = thrg_shc_get_options();
        $allow_admins = defined( 'THRG_SHC_ALLOW_ADMINS' ) ? (bool) THRG_SHC_ALLOW_ADMINS : ! empty( $options['allow_admins'] );

        if ( $allow_admins && ( current_user_can( 'manage_options' ) || is_super_admin( $user_id ) ) ) {
            $exempt = true;
        } else {
            $ids    = array_map( 'absint', preg_split( '/[\s,]+/', (string) $options['exempt_users'], -1, PREG_SPLIT_NO_EMPTY ) );
            $exempt = in_array( $user_id, $ids, true );
        }
    }

    /**
     * Filter whether the current user is exempt from Site Health restrictions.
     *
     * @param bool $exempt  Whether the user is exempt.
     * @param int  $user_id Current user ID (0 when logged out).
     */
    return (bool) apply_filters( 'thrg_shc_is_exempt', $exempt, $user_id );
}

/**
 * Load translations.
 *
 * @return void
 */
function thrg_shc_load_textdomain() {
    load_plugin_textdomain( 'thrg-site-inspector', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}
add_action( 'init', 'thrg_shc_load_textdomain' );

/**
 * Hide the Site Health submenu (Tools on sites, and on the network admin).
 *
 * @return void
 */
function thrg_shc_hide_menu() {
    if ( ! thrg_shc_enabled( 'hide_menu' ) || thrg_shc_is_exempt() ) {
        return;
    }
    remove_submenu_page( 'tools.php', 'site-health.php' );
}
add_action( 'admin_menu', 'thrg_shc_hide_menu', 999 );
add_action( 'network_admin_menu', 'thrg_shc_hide_menu', 999 );

/**
 * Redirect direct access to the Site Health administration screen.
 *
 * The current_screen hook runs after WordPress has identified the screen.
 * REST endpoints, cron checks and background tests are handled by their own
 * configurable features below.
 *
 * @param WP_Screen $screen Current screen object.
 * @return void
 */
function thrg_shc_block_screen( $screen ) {
    if ( ! is_object( $screen ) || ! isset( $screen->id ) || ! in_array( $screen->id, array( 'site-health', 'site-health-network' ), true ) ) {
        return;
    }
    if ( ! thrg_shc_enabled( 'redirect' ) || thrg_shc_is_exempt() ) {
        return;
    }

    $default = 'site-health-network' === $screen->id ? network_admin_url( 'index.php' ) : admin_url( 'index.php' );

    /**
     * Filter the URL visitors of the Site Health screen are sent to.
     *
     * @param string    $url    Redirect URL.
     * @param WP_Screen $screen Current screen object.
     */
    wp_safe_redirect( apply_filters( 'thrg_shc_redirect_url', $default, $screen ) );
    exit;
}
add_action( 'current_screen', 'thrg_shc_block_screen' );

/**
 * Remove the Site Health Status dashboard widget.
 *
 * @return void
 */
function thrg_shc_remove_dashboard_widget() {
    if ( ! thrg_shc_enabled( 'dashboard_widget' ) || thrg_shc_is_exempt() ) {
        return;
    }
    foreach ( array( 'normal', 'side', 'column3', 'column4' ) as $context ) {
        remove_meta_box( 'dashboard_site_health', 'dashboard', $context );
    }
}
add_action( 'wp_dashboard_setup', 'thrg_shc_remove_dashboard_widget', 999 );

/**
 * Empty the Site Health test list so no tests (including loopback and REST
 * self-requests) run. Exempt users keep the tests.
 *
 * @param array $tests Registered tests.
 * @return array
 */
function thrg_shc_disable_tests( $tests ) {
    if ( ! thrg_shc_enabled( 'disable_tests' ) || thrg_shc_is_exempt() ) {
        return $tests;
    }
    return array(
        'direct' => array(),
        'async'  => array(),
    );
}
add_filter( 'site_status_tests', 'thrg_shc_disable_tests', 999 );

/**
 * Prevent the weekly Site Health cron event from being scheduled.
 *
 * @param null|bool $pre   Short-circuit value.
 * @param object    $event Event being scheduled.
 * @return null|bool
 */
function thrg_shc_prevent_schedule( $pre, $event ) {
    if ( thrg_shc_enabled( 'disable_cron' ) && is_object( $event ) && isset( $event->hook ) && 'wp_site_health_scheduled_check' === $event->hook ) {
        return false;
    }
    return $pre;
}
add_filter( 'pre_schedule_event', 'thrg_shc_prevent_schedule', 10, 2 );

/**
 * Remove an already scheduled Site Health cron event.
 *
 * @return void
 */
function thrg_shc_clear_cron() {
    if ( thrg_shc_enabled( 'disable_cron' ) && wp_next_scheduled( 'wp_site_health_scheduled_check' ) ) {
        wp_clear_scheduled_hook( 'wp_site_health_scheduled_check' );
    }
}
add_action( 'init', 'thrg_shc_clear_cron' );

/**
 * Remove the Site Health REST routes.
 *
 * @param array $endpoints Registered REST endpoints.
 * @return array
 */
function thrg_shc_filter_rest_endpoints( $endpoints ) {
    if ( ! thrg_shc_enabled( 'block_rest' ) || thrg_shc_is_exempt() ) {
        return $endpoints;
    }
    foreach ( array_keys( $endpoints ) as $route ) {
        if ( 0 === strpos( $route, '/wp-site-health/v1' ) ) {
            unset( $endpoints[ $route ] );
        }
    }
    return $endpoints;
}
add_filter( 'rest_endpoints', 'thrg_shc_filter_rest_endpoints', 999 );

/**
 * Block the Site Health AJAX actions for non-exempt users.
 *
 * @return void
 */
function thrg_shc_block_ajax() {
    if ( ! thrg_shc_enabled( 'block_ajax' ) || ! wp_doing_ajax() || thrg_shc_is_exempt() ) {
        return;
    }

    $actions = array(
        'health-check-dotorg-communication',
        'health-check-background-updates',
        'health-check-loopback-requests',
        'health-check-get-sizes',
        'health-check-site-status-result',
    );

    // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- only reading the action name to deny it.
    $action = isset( $_REQUEST['action'] ) ? sanitize_key( wp_unslash( $_REQUEST['action'] ) ) : '';
    if ( in_array( $action, $actions, true ) ) {
        wp_send_json_error( 'forbidden', 403 );
    }
}
add_action( 'admin_init', 'thrg_shc_block_ajax', 1 );

/**
 * Sanitize the settings array.
 *
 * @param mixed $input Raw submitted settings.
 * @return array
 */
function thrg_shc_sanitize_options( $input ) {
    $input  = is_array( $input ) ? $input : array();
    $clean  = array();
    $toggle = array( 'hide_menu', 'redirect', 'dashboard_widget', 'disable_tests', 'disable_cron', 'block_rest', 'block_ajax', 'allow_admins', 'delete_data' );

    foreach ( $toggle as $key ) {
        $clean[ $key ] = empty( $input[ $key ] ) ? 0 : 1;
    }

    $ids                   = isset( $input['exempt_users'] ) ? preg_split( '/[\s,]+/', (string) $input['exempt_users'], -1, PREG_SPLIT_NO_EMPTY ) : array();
    $ids                   = array_unique( array_filter( array_map( 'absint', $ids ) ) );
    $clean['exempt_users'] = implode( ',', $ids );

    return $clean;
}

/**
 * Register the settings.
 *
 * @return void
 */
function thrg_shc_register_settings() {
    register_setting(
        'thrg_shc',
        THRG_SHC_OPTION,
        array(
            'type'              => 'array',
            'sanitize_callback' => 'thrg_shc_sanitize_options',
            'default'           => thrg_shc_defaults(),
        )
    );
}
add_action( 'admin_init', 'thrg_shc_register_settings' );

/**
 * Add the settings page under Settings.
 *
 * @return void
 */
function thrg_shc_add_settings_page() {
    add_options_page(
        __( 'Site Health Controls', 'thrg-site-inspector' ),
        __( 'Site Health Controls', 'thrg-site-inspector' ),
        'manage_options',
        'thrg-site-health-controls',
        'thrg_shc_render_settings_page'
    );
}
add_action( 'admin_menu', 'thrg_shc_add_settings_page' );

/**
 * Add a Settings link to the plugin row actions.
 *
 * @param string[] $links Existing action links.
 * @return string[]
 */
function thrg_shc_action_links( $links ) {
    $url = admin_url( 'options-general.php?page=thrg-site-health-controls' );
    array_unshift( $links, '<a href="' . esc_url( $url ) . '">' . esc_html__( 'Settings', 'thrg-site-inspector' ) . '</a>' );
    array_unshift( $links, '<a href="' . esc_url( admin_url( 'tools.php?page=thrg-site-inspector' ) ) . '" style="font-weight:600">' . esc_html__( 'Run inspection', 'thrg-site-inspector' ) . '</a>' );
    return $links;
}
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'thrg_shc_action_links' );

/**
 * Render the settings page.
 *
 * @return void
 */
function thrg_shc_render_settings_page() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    $options = thrg_shc_get_options();
    $groups  = array(
        __( 'Interface', 'thrg-site-inspector' ) => array(
            'hide_menu'        => __( 'Hide the Tools > Site Health menu item', 'thrg-site-inspector' ),
            'redirect'         => __( 'Redirect direct visits to the Site Health screen to the dashboard', 'thrg-site-inspector' ),
            'dashboard_widget' => __( 'Remove the Site Health Status dashboard widget', 'thrg-site-inspector' ),
        ),
        __( 'Background behavior (disables real diagnostics)', 'thrg-site-inspector' ) => array(
            'disable_tests' => __( 'Disable all Site Health tests (including loopback and REST self-checks)', 'thrg-site-inspector' ),
            'disable_cron'  => __( 'Unschedule the weekly Site Health cron check', 'thrg-site-inspector' ),
            'block_rest'    => __( 'Remove the wp-site-health/v1 REST routes', 'thrg-site-inspector' ),
            'block_ajax'    => __( 'Block the Site Health AJAX actions', 'thrg-site-inspector' ),
        ),
    );
    $mascot  = plugins_url( 'assets/brand/thrg-heart-128.png', __FILE__ );
    ?>
    <div class="wrap thrg-inspector thrg-shc-settings">
        <div class="si-head">
            <a class="si-mascot-link" href="<?php echo esc_url( admin_url( 'tools.php?page=thrg-site-inspector' ) ); ?>" title="<?php esc_attr_e( 'Back to Site Inspector', 'thrg-site-inspector' ); ?>"><img class="si-mascot thrg-brand-heart" src="<?php echo esc_url( $mascot ); ?>" alt="<?php esc_attr_e( 'Back to Site Inspector', 'thrg-site-inspector' ); ?>" width="56" height="56" /></a>
            <div>
                <p class="si-eyebrow">The Hack Repair Guy&rsquo;s</p>
                <h1><?php esc_html_e( 'Site Health Controls', 'thrg-site-inspector' ); ?></h1>
                <p class="si-byline"><?php esc_html_e( 'Choose which parts of WordPress Site Health to switch off. All seven are on until you save your own choices. Uncheck an option to restore that feature.', 'thrg-site-inspector' ); ?></p>
            </div>
        </div>
        <hr class="wp-header-end" />
        <form method="post" action="options.php">
            <?php settings_fields( 'thrg_shc' ); ?>
            <?php foreach ( $groups as $title => $fields ) : ?>
                <section class="si-block">
                    <h2 class="si-section"><?php echo esc_html( $title ); ?></h2>
                    <div class="si-card si-options">
                    <?php foreach ( $fields as $key => $label ) : ?>
                        <label>
                            <input type="checkbox" name="<?php echo esc_attr( THRG_SHC_OPTION . '[' . $key . ']' ); ?>" value="1" <?php checked( ! empty( $options[ $key ] ) ); ?> />
                            <?php echo esc_html( $label ); ?>
                        </label>
                    <?php endforeach; ?>
                    </div>
                </section>
            <?php endforeach; ?>

            <section class="si-block">
                <h2 class="si-section"><?php esc_html_e( 'Exceptions', 'thrg-site-inspector' ); ?></h2>
                <div class="si-card si-options">
                    <label>
                        <input type="checkbox" name="<?php echo esc_attr( THRG_SHC_OPTION . '[allow_admins]' ); ?>" value="1" <?php checked( ! empty( $options['allow_admins'] ) ); ?> />
                        <?php esc_html_e( 'Do not restrict administrators (restrict everyone else with access)', 'thrg-site-inspector' ); ?>
                    </label>
                    <?php if ( defined( 'THRG_SHC_ALLOW_ADMINS' ) ) : ?>
                        <p class="description"><?php esc_html_e( 'Overridden by the THRG_SHC_ALLOW_ADMINS constant in wp-config.php.', 'thrg-site-inspector' ); ?></p>
                    <?php endif; ?>
                    <p class="si-field">
                        <label for="thrg-shc-exempt-users"><?php esc_html_e( 'Exempt user IDs (comma separated)', 'thrg-site-inspector' ); ?></label><br />
                        <input type="text" id="thrg-shc-exempt-users" class="regular-text" name="<?php echo esc_attr( THRG_SHC_OPTION . '[exempt_users]' ); ?>" value="<?php echo esc_attr( $options['exempt_users'] ); ?>" />
                    </p>
                    <p class="description"><?php esc_html_e( 'Developers can also use the thrg_shc_is_exempt and thrg_shc_redirect_url filters.', 'thrg-site-inspector' ); ?></p>
                </div>
            </section>

            <section class="si-block">
                <h2 class="si-section"><?php esc_html_e( 'Uninstall', 'thrg-site-inspector' ); ?></h2>
                <div class="si-card si-options">
                    <label>
                        <input type="checkbox" name="<?php echo esc_attr( THRG_SHC_OPTION . '[delete_data]' ); ?>" value="1" <?php checked( ! empty( $options['delete_data'] ) ); ?> />
                        <?php esc_html_e( 'Delete all plugin data from the database when the plugin is deleted', 'thrg-site-inspector' ); ?>
                    </label>
                    <p class="description"><?php esc_html_e( 'Removes these settings, the saved Site Inspector report history, and the notice dismissal. Leave unchecked to keep everything in case you reinstall. Deactivating the plugin never deletes data.', 'thrg-site-inspector' ); ?></p>
                </div>
            </section>
            <?php submit_button(); ?>
        </form>

        <footer class="si-coverage">
            <div class="si-coverage-col">
                <p><?php esc_html_e( 'These options only hide or stop WordPress\'s own Site Health screens and checks. Uncheck any option to bring that feature back.', 'thrg-site-inspector' ); ?></p>
            </div>
            <div class="si-coverage-col si-help">
                <img class="si-mascot thrg-brand-heart" src="<?php echo esc_url( $mascot ); ?>" alt="" width="44" height="44" />
                <p>Need a deeper look?<br />Contact <a href="https://hackrepair.com/" title="The Hack Repair Guy: WordPress security and hack repair" target="_blank" rel="noopener noreferrer">The Hack Repair Guy<span class="screen-reader-text"> (opens in a new tab)</span></a>.</p>
            </div>
        </footer>
    </div>
    <?php
}

/**
 * Expose a native WordPress plugin-information dialog on the Plugins screen.
 * The installed readme supplies the dialog, so no network access is needed.
 *
 * @param string[] $links Existing row links.
 * @param string   $plugin_file Relative plugin entry point.
 * @return string[]
 */
function thrg_shc_plugin_row_meta( $links, $plugin_file ) {
    if ( plugin_basename( __FILE__ ) !== $plugin_file ) {
        return $links;
    }

    // Core or the update checker (which hooks in on admin_init, hence the later priority) already added a View details link.
    foreach ( $links as $existing ) {
        if ( is_string( $existing ) && false !== strpos( $existing, 'tab=plugin-information' ) ) {
            return $links;
        }
    }

    $name = "The Hack Repair Guy's Site Inspector";
    $url  = add_query_arg(
        array(
            'tab'       => 'plugin-information',
            'plugin'    => thrg_si_slug(),
            'TB_iframe' => 'true',
            'width'     => 600,
            'height'    => 550,
        ),
        admin_url( 'plugin-install.php' )
    );

    $links[] = sprintf(
        '<a href="%s" class="thickbox open-plugin-details-modal" aria-label="%s" data-title="%s">%s</a>',
        esc_url( $url ),
        esc_attr( sprintf( 'More information about %s', $name ) ),
        esc_attr( $name ),
        esc_html__( 'View details', 'thrg-site-inspector' )
    );

    return $links;
}
add_filter( 'plugin_row_meta', 'thrg_shc_plugin_row_meta', 20, 2 );

/**
 * Make sure the WordPress details dialog is available on the Plugins screen.
 *
 * @param string $hook Current administration page.
 * @return void
 */
function thrg_shc_enqueue_details( $hook ) {
    if ( 'plugins.php' === $hook ) {
        add_thickbox();
    }
}
add_action( 'admin_enqueue_scripts', 'thrg_shc_enqueue_details' );

/**
 * Format inline readme markup (**bold** and [text](https://link "tooltip")) after escaping.
 *
 * @param string $text One line or paragraph of readme text.
 * @return string Safe HTML.
 */
function thrg_shc_format_readme_inline( $text ) {
    $text = esc_html( $text );
    $text = preg_replace( '/\*\*(.+?)\*\*/', '<strong>$1</strong>', $text );
    return preg_replace_callback(
        '/\[([^\]]+)\]\((https?:\/\/[^\s)"\'<>]+)(?:\s+&quot;(.+?)&quot;)?\)/',
        static function ( $m ) {
            $title = isset( $m[3] ) && '' !== $m[3] ? ' title="' . $m[3] . '"' : '';
            return '<a href="' . $m[2] . '"' . $title . ' target="_blank" rel="noopener noreferrer">' . $m[1] . '</a>';
        },
        $text
    );
}

/**
 * Render basic WordPress readme markup safely in the information dialog.
 * Handles paragraphs, = headings =, * bullet lists, numbered steps, **bold**
 * and [text](https://link) links. Everything is escaped first.
 *
 * @param string $source Text from the locally installed readme.
 * @return string HTML for a plugin information tab.
 */
function thrg_shc_format_readme_section( $source ) {
    $lines     = preg_split( '/\r\n|\r|\n/', trim( $source ) );
    $output    = '';
    $paragraph = array();
    $list_type = '';

    $flush_paragraph = static function () use ( &$output, &$paragraph ) {
        if ( $paragraph ) {
            $output   .= wpautop( thrg_shc_format_readme_inline( implode( "\n", $paragraph ) ) );
            $paragraph = array();
        }
    };
    $close_list      = static function () use ( &$output, &$list_type ) {
        if ( $list_type ) {
            $output   .= '</' . $list_type . '>';
            $list_type = '';
        }
    };

    foreach ( $lines as $line ) {
        $line = trim( $line );
        if ( '' === $line ) {
            $flush_paragraph();
            $close_list();
        } elseif ( preg_match( '/^=\s*(.*?)\s*=$/', $line, $heading ) ) {
            $flush_paragraph();
            $close_list();
            $output .= '<h4>' . esc_html( $heading[1] ) . '</h4>';
        } elseif ( preg_match( '/^(?:\*\s+|([0-9]+)\.\s+)(.*)$/', $line, $item ) ) {
            $flush_paragraph();
            $type = isset( $item[1] ) && '' !== $item[1] ? 'ol' : 'ul';
            if ( $list_type !== $type ) {
                $close_list();
                $list_type = $type;
                $output   .= '<' . $list_type . '>';
            }
            $output .= '<li>' . thrg_shc_format_readme_inline( $item[2] ) . '</li>';
        } else {
            $close_list();
            $paragraph[] = $line;
        }
    }

    $flush_paragraph();
    $close_list();
    return $output;
}

/**
 * Read the compatibility fields from the readme header.
 *
 * @param string $readme Full readme.txt contents.
 * @return array<string,string> Keys requires, tested, requires_php (only those present).
 */
function thrg_shc_readme_meta( $readme ) {
    $fields = array(
        'requires'     => 'Requires at least',
        'tested'       => 'Tested up to',
        'requires_php' => 'Requires PHP',
    );
    $head   = preg_split( '/^==\s*Description\s*==\s*$/mi', (string) $readme, 2 );
    $meta   = array();
    foreach ( $fields as $key => $label ) {
        if ( preg_match( '/^' . preg_quote( $label, '/' ) . ':\s*([0-9][0-9.]*)\s*$/mi', $head[0], $m ) ) {
            $meta[ $key ] = $m[1];
        }
    }
    return $meta;
}

/**
 * Supply plugin details directly from the installed readme and local artwork.
 * Only intercept information requests for our unique slug, and only while no
 * newer release is pending (then the update checker describes that release).
 * Runs after the update checker so View details always matches what is installed.
 *
 * @param mixed  $result Previous API response.
 * @param string $action Requested API action.
 * @param object $args   API arguments.
 * @return mixed
 */
function thrg_shc_local_plugin_info( $result, $action, $args ) {
    if ( 'plugin_information' !== $action ||
        ! is_object( $args ) || ! isset( $args->slug ) ||
        thrg_si_slug() !== $args->slug ) {
        return $result;
    }

    // A newer release is waiting: let the update checker describe that release instead.
    $updates = get_site_transient( 'update_plugins' );
    $base    = plugin_basename( __FILE__ );
    if ( is_object( $updates ) && isset( $updates->response[ $base ]->new_version ) &&
        version_compare( (string) $updates->response[ $base ]->new_version, THRG_SI_VERSION, '>' ) ) {
        return $result;
    }

    $readme_path = plugin_dir_path( __FILE__ ) . 'readme.txt';
    if ( ! is_readable( $readme_path ) ) {
        return $result;
    }

    // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Reading a local bundled file, not a remote URL.
    $readme = file_get_contents( $readme_path );
    if ( false === $readme ) {
        return $result;
    }

    $parts    = preg_split( '/^==\s*([^\r\n]+?)\s*==\s*$/m', $readme, -1, PREG_SPLIT_DELIM_CAPTURE );
    $tabs     = array(
        'description'                => 'description',
        'installation'               => 'installation',
        'frequently asked questions' => 'faq',
        'changelog'                  => 'changelog',
    );
    $sections = array();

    $part_count = count( $parts );
    for ( $i = 1; $i + 1 < $part_count; $i += 2 ) {
        $key = strtolower( trim( $parts[ $i ] ) );
        if ( isset( $tabs[ $key ] ) ) {
            $sections[ $tabs[ $key ] ] = thrg_shc_format_readme_section( $parts[ $i + 1 ] );
        }
    }

    if ( ! isset( $sections['description'] ) ) {
        return $result;
    }

    $info           = new stdClass();
    $info->name     = "The Hack Repair Guy's Site Inspector";
    $info->slug     = thrg_si_slug();
    $info->version  = THRG_SI_VERSION;
    $info->author   = '<a href="https://hackrepair.com/" title="The Hack Repair Guy: WordPress security and hack repair">The Hack Repair Guy</a>';
    $info->homepage = 'https://github.com/hackrepair/thrg-site-inspector';
    $info->external = true;
    foreach ( thrg_shc_readme_meta( $readme ) as $key => $value ) {
        $info->$key = $value;
    }
    // No last_updated: the installed files only know when they were unpacked, not when the release was published.
    $info->sections = $sections;
    $asset_url      = plugin_dir_url( __FILE__ ) . 'assets/';
    $info->banners  = array(
        'low'  => $asset_url . 'banner-772x250.png',
        'high' => $asset_url . 'banner-1544x500.png',
    );
    $info->icons    = array(
        '1x' => $asset_url . 'icon-128x128.png',
        '2x' => $asset_url . 'icon-256x256.png',
    );

    return $info;
}
add_filter( 'plugins_api', 'thrg_shc_local_plugin_info', 99, 3 );

/**
 * One-time notice after installing or upgrading to 2.x: point to the inspector.
 *
 * @return void
 */
function thrg_shc_admin_notice() {
    if ( ! current_user_can( 'manage_options' ) || THRG_SI_VERSION === get_option( 'thrg_si_notice_dismissed' ) ) {
        return;
    }
    $screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
    if ( ! $screen || ! in_array( $screen->id, array( 'dashboard', 'plugins', 'settings_page_thrg-site-health-controls' ), true ) ) {
        return;
    }
    $dismiss = wp_nonce_url( add_query_arg( 'thrg_si_dismiss', '1' ), 'thrg_si_dismiss' );
    echo '<div class="notice notice-info"><p><strong>' . esc_html__( 'Site Inspector is ready.', 'thrg-site-inspector' ) . '</strong> ';
    echo esc_html__( 'Check updates, admin accounts, uploads, exposed backups and more in a few seconds.', 'thrg-site-inspector' ) . ' ';
    echo '<a href="' . esc_url( admin_url( 'tools.php?page=thrg-site-inspector' ) ) . '">' . esc_html__( 'Run your first inspection', 'thrg-site-inspector' ) . '</a></p>';
    echo '<p><a href="' . esc_url( $dismiss ) . '">' . esc_html__( 'Dismiss', 'thrg-site-inspector' ) . '</a></p></div>';
}
add_action( 'admin_notices', 'thrg_shc_admin_notice' );

/**
 * Handle the notice dismissal link.
 *
 * @return void
 */
function thrg_shc_dismiss_notice() {
    if ( empty( $_GET['thrg_si_dismiss'] ) || ! current_user_can( 'manage_options' ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- verified below.
        return;
    }
    check_admin_referer( 'thrg_si_dismiss' );
    update_option( 'thrg_si_notice_dismissed', THRG_SI_VERSION, false );
    wp_safe_redirect( remove_query_arg( array( 'thrg_si_dismiss', '_wpnonce' ) ) );
    exit;
}
add_action( 'admin_init', 'thrg_shc_dismiss_notice' );

require_once __DIR__ . '/includes/companion-plugins.php';
