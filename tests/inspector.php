<?php
// Pure finding classification: no WordPress or network dependency.
define( 'ABSPATH', __DIR__ . '/' );
define( 'DAY_IN_SECONDS', 86400 );
function admin_url( $p = '' ) { return 'http://x/wp-admin/' . $p; }
function esc_url( $u ) { return $u; }
function esc_html( $t ) { return htmlspecialchars( (string) $t, ENT_QUOTES ); }
require __DIR__ . '/../includes/class-thrg-site-inspector.php';

$now  = strtotime( '2026-10-16 12:00:00 UTC' );
$base = array(
    'now' => $now, 'debug' => false, 'display' => '0', 'registration' => false, 'multisite' => false, 'role' => 'subscriber',
    'role_found' => true, 'role_caps' => array(), 'home_https' => true, 'site_https' => true, 'editor_disabled' => true,
    'config_exists' => true, 'config_writable' => false, 'config_mode' => 0440, 'php_version' => '8.3.12', 'weak_salts' => 0,
    'accounts' => array( 'items' => array( array( 'id' => 1, 'login' => 'owner', 'roles' => array( 'administrator' ), 'registered' => '2020-01-01 00:00:00', 'caps' => array( 'manage_options', 'edit_plugins' ) ) ), 'partial' => false, 'checked' => 3 ),
    'uploads' => array( 'items' => array(), 'placeholders' => array(), 'partial' => false, 'checked' => 10, 'rules' => array(), 'missing' => false ),
    'sensitive' => array(),
    'software' => array( 'updates' => array(), 'update_checked' => $now - 3600, 'inactive_plugins' => array(), 'inactive_themes' => array( 'Twenty Twenty-Five' ), 'plugin_count' => 4 ),
    'hidden_code' => array( 'mu' => array(), 'dropins' => array() ),
);
$cases = array(
    array( 'local HTTP is contextual', array( 'local_environment' => true, 'home_https' => false ), 'https-config', 'Review' ),
    array( 'one HTTP URL', array( 'site_https' => false ), 'https-config', 'Potential weakness' ),
    array( 'safe configuration observation', array(), 'config-permissions', 'No issue observed' ),
    array( 'world writable config', array( 'config_mode' => 0666 ), 'config-permissions', 'Potential weakness' ),
    array( 'group writable config', array( 'config_mode' => 0664 ), 'config-permissions', 'Review' ),
    array( 'PHP-writable 0644 is normal on cPanel', array( 'config_writable' => true, 'config_mode' => 0644 ), 'config-permissions', 'No issue observed' ),
    array( 'missing file is unknown', array( 'config_exists' => false, 'config_mode' => false ), 'config-permissions', 'Unable to verify' ),
    array( 'public privileged signup', array( 'registration' => true, 'role_caps' => array( 'manage_options' ) ), 'registration', 'Potential weakness' ),
    array( 'closed signup avoids false alarm', array( 'role_caps' => array( 'manage_options' ) ), 'registration', 'No issue observed' ),
    array( 'network policy unknown', array( 'multisite' => true ), 'registration', 'Unable to verify' ),
    array( 'runtime errors despite WP_DEBUG off', array( 'display' => '1' ), 'error-display', 'Potential weakness' ),
    array( 'debug without display', array( 'debug' => true, 'display' => 'Off' ), 'error-display', 'No issue observed' ),
    array( 'editor enabled is review', array( 'editor_disabled' => false ), 'file-editor', 'Review' ),
    array( 'editor disabled', array(), 'file-editor', 'No issue observed' ),
    array( 'long-standing admins are not noise', array(), 'account-access', 'No issue observed' ),
    array( 'recent admin flagged', array( 'accounts' => array( 'items' => array( array( 'id' => 9, 'login' => 'x', 'roles' => array( 'administrator' ), 'registered' => '2026-10-10 00:00:00', 'caps' => array( 'manage_options' ) ) ), 'partial' => false, 'checked' => 1 ) ), 'account-access', 'Review' ),
    array( 'admin caps without admin role flagged', array( 'accounts' => array( 'items' => array( array( 'id' => 5, 'login' => 'sub', 'roles' => array( 'subscriber' ), 'registered' => '2020-01-01 00:00:00', 'caps' => array( 'install_plugins' ) ) ), 'partial' => false, 'checked' => 1 ) ), 'account-access', 'Review' ),
    array( 'pending update', array( 'software' => array( 'updates' => array( array( 'type' => 'plugin', 'name' => 'X', 'installed' => '1', 'available' => '2' ) ), 'update_checked' => $now, 'inactive_plugins' => array(), 'inactive_themes' => array(), 'plugin_count' => 1 ) ), 'updates', 'Potential weakness' ),
    array( 'no update data', array( 'software' => array( 'updates' => array(), 'update_checked' => 0, 'inactive_plugins' => array(), 'inactive_themes' => array(), 'plugin_count' => 1 ) ), 'updates', 'Unable to verify' ),
    array( 'stale update data', array( 'software' => array( 'updates' => array(), 'update_checked' => $now - 20 * 86400, 'inactive_plugins' => array(), 'inactive_themes' => array(), 'plugin_count' => 1 ) ), 'updates', 'Review' ),
    array( 'current updates', array(), 'updates', 'No issue observed' ),
    array( 'one spare theme allowed', array(), 'inactive', 'No issue observed' ),
    array( 'inactive plugin flagged', array( 'software' => array( 'updates' => array(), 'update_checked' => $now, 'inactive_plugins' => array( 'Old' ), 'inactive_themes' => array(), 'plugin_count' => 1 ) ), 'inactive', 'Review' ),
    array( 'PHP 7.4 unsupported', array( 'php_version' => '7.4.33' ), 'php-version', 'Potential weakness' ),
    array( 'PHP 8.2 ending soon', array( 'php_version' => '8.2.20' ), 'php-version', 'Review' ),
    array( 'PHP 8.3 supported', array(), 'php-version', 'No issue observed' ),
    array( 'PHP 8.5 supported', array( 'php_version' => '8.5.1' ), 'php-version', 'No issue observed' ),
    array( 'unknown future PHP', array( 'php_version' => '8.6.0' ), 'php-version', 'No issue observed' ),
    array( 'placeholder salts', array( 'weak_salts' => 8 ), 'security-keys', 'Potential weakness' ),
    array( 'sensitive candidate', array( 'sensitive' => array( array( 'path' => 'error_log', 'size' => 10, 'modified' => $now ) ) ), 'sensitive-files', 'Potential weakness' ),
    array( 'uploads php file', array( 'uploads' => array( 'items' => array( array( 'path' => '2024/01/x.php', 'size' => 900, 'modified' => $now ) ), 'placeholders' => array(), 'partial' => false, 'checked' => 10, 'rules' => array(), 'missing' => false ) ), 'uploads-files', 'Potential weakness' ),
    array( 'placeholders only is clean', array( 'uploads' => array( 'items' => array(), 'placeholders' => array( 'wpforms/index.php' ), 'partial' => false, 'checked' => 10, 'rules' => array(), 'missing' => false ) ), 'uploads-files', 'No issue observed' ),
    array( 'mu-plugin listed for review', array( 'hidden_code' => array( 'mu' => array( array( 'path' => 'mu-plugins/x.php', 'modified' => $now ) ), 'dropins' => array() ) ), 'hidden-code', 'Review' ),
    array( 'missing collectors are unknown', array( 'software' => null ), 'updates', 'Unable to verify' ),
);
foreach ( $cases as $case ) {
    $rows = array_column( THRG_Site_Inspector::evaluate( array_merge( $base, $case[1] ) ), null, 'id' );
    if ( ! isset( $rows[ $case[2] ] ) || $rows[ $case[2] ]['status'] !== $case[3] ) {
        fwrite( STDERR, 'FAIL ' . $case[0] . ' (got ' . ( isset( $rows[ $case[2] ] ) ? $rows[ $case[2] ]['status'] : 'missing' ) . ')' . PHP_EOL );
        exit( 1 );
    }
    echo 'PASS ' . $case[0] . PHP_EOL;
}

// A clean baseline produces no attention or review items.
$noisy = array_filter( THRG_Site_Inspector::evaluate( $base ), static function ( $r ) { return 'No issue observed' !== $r['status']; } );
if ( $noisy ) {
    fwrite( STDERR, 'FAIL clean baseline is quiet: ' . implode( ', ', array_column( $noisy, 'id' ) ) . PHP_EOL );
    exit( 1 );
}
echo "PASS clean baseline is quiet\n";
if ( 'ow•••' !== THRG_Site_Inspector::mask( 'owner' ) ) {
    fwrite( STDERR, "FAIL mask\n" );
    exit( 1 );
}
echo "PASS username masking\n";

// Links: only admin pages and two fixed references resolve; everything else is plain text.
$ok = array( 'plugins.php?plugin_status=inactive', 'users.php', 'themes.php', 'update-core.php?force-check=1', 'user-edit.php?user_id=5', 'options-general.php', 'https://www.php.net/supported-versions.php', 'https://api.wordpress.org/secret-key/1.1/salt/' );
foreach ( $ok as $t ) {
    if ( ! THRG_Site_Inspector::href( $t ) ) { fwrite( STDERR, "FAIL href allows $t\n" ); exit( 1 ); }
}
foreach ( array( 'https://evil.example/', '//evil.example', '../wp-config.php', 'javascript:alert(1)', 'plugins.php?x=<script>', 'a/b.php', 'http://www.php.net/supported-versions.php' ) as $t ) {
    if ( THRG_Site_Inspector::href( $t ) ) { fwrite( STDERR, "FAIL href rejects $t\n" ); exit( 1 ); }
}
echo "PASS link allowlist\n";
$php85 = array_column( THRG_Site_Inspector::evaluate( array_merge( $base, array( 'php_version' => '8.5.1' ) ) ), null, 'id' );
if ( false === strpos( $php85['php-version']['evidence'][0], 'ends 2029-12-31' ) ) { fwrite( STDERR, "FAIL PHP 8.5 end date shown\n" ); exit( 1 ); }
echo "PASS PHP 8.5 end date shown\n";
$html = THRG_Site_Inspector::linkify( 'Open [[update-core.php|Dashboard > Updates]] or [[https://evil.example/|click]] <b>x</b>' );
if ( false === strpos( $html, '<a href="http://x/wp-admin/update-core.php" target="_blank" rel="noopener noreferrer">Dashboard &gt; Updates' ) || false !== strpos( $html, 'evil.example' ) || false !== strpos( $html, '<b>' ) ) {
    fwrite( STDERR, "FAIL linkify $html\n" ); exit( 1 );
}
echo "PASS linkify escapes and rejects unknown targets\n";
// Every token or action produced for a worst-case site resolves, and plugin names cannot inject tokens.
$worst = array_merge( $base, array(
    'registration' => true, 'role_caps' => array( 'manage_options' ), 'role' => 'x[[https://evil.example/|r]]', 'display' => '1', 'editor_disabled' => false,
    'home_https' => false, 'site_https' => false, 'weak_salts' => 8, 'php_version' => '7.4.33', 'config_mode' => 0666,
    'accounts' => array( 'items' => array( array( 'id' => 9, 'login' => 'x', 'roles' => array( 'subscriber' ), 'registered' => '2026-10-10 00:00:00', 'caps' => array( 'install_plugins', 'edit_plugins' ) ) ), 'partial' => false, 'checked' => 1 ),
    'software' => array( 'updates' => array( array( 'type' => 'plugin', 'name' => 'P[[https://evil.example/|p]]', 'installed' => '1', 'available' => '2' ) ), 'update_checked' => $now, 'inactive_plugins' => array( 'Old[[users.php|a]]' ), 'inactive_themes' => array( 'A', 'B' ), 'plugin_count' => 2 ),
    'hidden_code' => array( 'mu' => array( array( 'path' => 'mu-plugins/x.php', 'modified' => $now ) ), 'dropins' => array( array( 'path' => 'wp-content/db.php', 'modified' => $now ) ) ),
    'sensitive' => array( array( 'path' => 'error_log', 'size' => 1, 'modified' => $now ) ),
) );
$strings = 0;
foreach ( THRG_Site_Inspector::evaluate( $worst ) as $r ) {
    $texts = array_merge( array( $r['why'], $r['next'] ), $r['evidence'] );
    foreach ( $texts as $t ) {
        preg_match_all( '/\[\[([^|\]]+)\|([^\]]+)\]\]/', $t, $m, PREG_SET_ORDER );
        foreach ( $m as $tok ) {
            $strings++;
            if ( ! THRG_Site_Inspector::href( $tok[1] ) || false !== strpos( $tok[1] . $tok[2], 'evil' ) ) { fwrite( STDERR, 'FAIL token ' . $tok[0] . "\n" ); exit( 1 ); }
        }
    }
    foreach ( $r['actions'] as $a ) {
        $strings++;
        if ( ! THRG_Site_Inspector::href( $a[1] ) ) { fwrite( STDERR, 'FAIL action ' . $a[1] . "\n" ); exit( 1 ); }
    }
    if ( $r['table'] ) {
        foreach ( $r['table']['rows'] as $cells ) {
            foreach ( $cells as $c ) {
                if ( is_array( $c ) && ! THRG_Site_Inspector::href( $c[1] ) ) { fwrite( STDERR, 'FAIL cell ' . $c[1] . "\n" ); exit( 1 ); }
            }
        }
    }
}
if ( $strings < 15 ) { fwrite( STDERR, "FAIL too few links ($strings)\n" ); exit( 1 ); }
echo "PASS all generated links resolve ($strings) and names cannot inject links\n";
$txt = THRG_Site_Inspector::plain_text( THRG_Site_Inspector::evaluate( $worst ), 'x', 'now' );
if ( false !== strpos( $txt, '[[' ) ) { fwrite( STDERR, "FAIL plain text keeps tokens\n" ); exit( 1 ); }
if ( false !== strpos( $txt, 'Check again' ) ) { fwrite( STDERR, "FAIL plain text keeps link-only wording\n" ); exit( 1 ); }
echo "PASS plain-text copy has no link tokens or link-only wording\n";
