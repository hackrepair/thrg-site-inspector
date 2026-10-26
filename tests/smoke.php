<?php
/**
 * Standalone smoke test using minimal WordPress stubs.
 * Run: php tests/smoke.php
 * It checks the plugin logic only; verify behavior in a real WordPress install too.
 */

define( 'ABSPATH', __DIR__ . '/' );
define( 'THRG_SI_SKIP_UPDATER', true );

$GLOBALS['opts']    = array();
$GLOBALS['user']    = 0;
$GLOBALS['caps']    = array();
$GLOBALS['removed'] = array();
$GLOBALS['hooks']   = array();
$GLOBALS['redirect'] = null;

function add_action( $h, $cb, $p = 10, $a = 1 ) { $GLOBALS['hooks'][ $h ][] = $cb; }
function add_filter( $h, $cb, $p = 10, $a = 1 ) { $GLOBALS['hooks'][ $h ][] = $cb; }
function apply_filters( $h, $v ) { return $v; }
function get_option( $k, $d = false ) { return isset( $GLOBALS['opts'][ $k ] ) ? $GLOBALS['opts'][ $k ] : $d; }
function wp_parse_args( $a, $d ) { return array_merge( $d, $a ); }
function get_current_user_id() { return $GLOBALS['user']; }
function current_user_can( $c ) { return in_array( $c, $GLOBALS['caps'], true ); }
function is_super_admin( $id = 0 ) { return false; }
function absint( $n ) { return abs( (int) $n ); }
function plugin_basename( $f ) { return basename( dirname( $f ) ) . '/' . basename( $f ); }
function remove_submenu_page( $p, $s ) { $GLOBALS['removed'][] = "$p:$s"; }
function remove_meta_box( $id, $s, $c ) { $GLOBALS['removed'][] = "$id:$c"; }
function admin_url( $p = '' ) { return 'http://x/wp-admin/' . $p; }
function network_admin_url( $p = '' ) { return 'http://x/wp-admin/network/' . $p; }
function wp_safe_redirect( $u ) { $GLOBALS['redirect'] = $u; throw new Exception( 'exit' ); }

require __DIR__ . '/../index.php';

$fail = 0;
function check( $name, $cond ) {
    global $fail;
    echo ( $cond ? 'PASS' : 'FAIL' ) . " $name\n";
    if ( ! $cond ) { $fail++; }
}

// Defaults.
thrg_shc_hide_menu();
check( 'menu removed by default', array( 'tools.php:site-health.php' ) === $GLOBALS['removed'] );
check( 'tests disabled by default', array( 'direct' => array(), 'async' => array() ) === thrg_shc_disable_tests( array( 'a' ) ) );

// Existing explicit choices must win over the new defaults.
$GLOBALS['opts']['thrg_shc_options'] = array( 'disable_tests' => 0, 'disable_cron' => 0, 'block_rest' => 0, 'block_ajax' => 0 );
check( 'saved tests choice preserved', array( 'a' ) === thrg_shc_disable_tests( array( 'a' ) ) );
check( 'saved REST choice preserved', array( '/wp-site-health/v1/tests/x' => 1 ) === thrg_shc_filter_rest_endpoints( array( '/wp-site-health/v1/tests/x' => 1 ) ) );
check( 'saved cron choice preserved', null === thrg_shc_prevent_schedule( null, (object) array( 'hook' => 'wp_site_health_scheduled_check' ) ) );
check( 'saved AJAX choice preserved', ! thrg_shc_enabled( 'block_ajax' ) );
$GLOBALS['opts'] = array();

// Redirect.
try {
    thrg_shc_block_screen( (object) array( 'id' => 'site-health' ) );
    check( 'redirect fires', false );
} catch ( Exception $e ) {
    check( 'redirect fires to dashboard', 'http://x/wp-admin/index.php' === $GLOBALS['redirect'] );
}
thrg_shc_block_screen( (object) array( 'id' => 'dashboard' ) );
check( 'other screens untouched', true );

// Opt-in features.
$GLOBALS['opts']['thrg_shc_options'] = array( 'disable_tests' => 1, 'block_rest' => 1, 'disable_cron' => 1 );
check( 'tests emptied', array( 'direct' => array(), 'async' => array() ) === thrg_shc_disable_tests( array( 'a' ) ) );
check( 'rest routes removed', array( '/wp/v2/posts' => 1 ) === thrg_shc_filter_rest_endpoints( array( '/wp/v2/posts' => 1, '/wp-site-health/v1/tests/x' => 1 ) ) );
check( 'cron blocked', false === thrg_shc_prevent_schedule( null, (object) array( 'hook' => 'wp_site_health_scheduled_check' ) ) );
check( 'other cron allowed', null === thrg_shc_prevent_schedule( null, (object) array( 'hook' => 'other' ) ) );

// Exemptions.
$GLOBALS['user'] = 5;
$GLOBALS['opts']['thrg_shc_options']['exempt_users'] = '3, 5';
check( 'listed user exempt', thrg_shc_is_exempt() );
check( 'exempt keeps tests', array( 'a' ) === thrg_shc_disable_tests( array( 'a' ) ) );
$GLOBALS['opts']['thrg_shc_options']['exempt_users'] = '';
$GLOBALS['caps'] = array( 'manage_options' );
check( 'admin not exempt by default', ! thrg_shc_is_exempt() );
$GLOBALS['opts']['thrg_shc_options']['allow_admins'] = 1;
check( 'admin exempt when allowed', thrg_shc_is_exempt() );

// Sanitizer.
$clean = thrg_shc_sanitize_options( array( 'block_rest' => '1', 'exempt_users' => '4, 4, abc, 7' ) );
check( 'sanitize toggles', 1 === $clean['block_rest'] && 0 === $clean['hide_menu'] );
check( 'sanitize ids', '4,7' === $clean['exempt_users'] );

// View details renderer: tooltips on links, escaped.
if ( ! function_exists( 'esc_html' ) ) { function esc_html( $t ) { return htmlspecialchars( (string) $t, ENT_QUOTES ); } }
$html = thrg_shc_format_readme_inline( 'See [Help](https://hackrepair.com/ "Get help") and [plain](https://example.com/).' );
check( 'readme link tooltip becomes title attribute', false !== strpos( $html, '<a href="https://hackrepair.com/" title="Get help" target="_blank"' ) );
check( 'readme link without tooltip has no title', false !== strpos( $html, '<a href="https://example.com/" target="_blank"' ) );
$bad = thrg_shc_format_readme_inline( '[x](https://e.com/ "a\" onmouseover=\"b")' );
check( 'readme tooltip cannot break out of the attribute', 1 === preg_match( '/^<a href="https:\/\/e\.com\/" title="[^"]*" target="_blank" rel="noopener noreferrer">x<\/a>$/', $bad ) );

// View details compatibility comes from readme.txt and agrees with the plugin header.
$meta   = thrg_shc_readme_meta( file_get_contents( __DIR__ . '/../readme.txt' ) );
$header = file_get_contents( __DIR__ . '/../index.php', false, null, 0, 2000 );
preg_match( '/Requires at least:\s*(\S+)/', $header, $h_wp );
preg_match( '/Requires PHP:\s*(\S+)/', $header, $h_php );
check( 'readme compatibility fields parsed', isset( $meta['requires'], $meta['tested'], $meta['requires_php'] ) );
check( 'readme and header agree on requirements', $meta['requires'] === $h_wp[1] && $meta['requires_php'] === $h_php[1] );
check( 'readme fields ignored outside the header', array() === thrg_shc_readme_meta( "== Description ==\nTested up to: 9.9\n" ) );

exit( $fail ? 1 : 0 );
