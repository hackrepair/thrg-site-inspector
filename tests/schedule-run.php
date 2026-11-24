<?php
// Exercise the actual monthly run with settings changed during inspection.
define( 'ABSPATH', __DIR__ . '/' );
define( 'MINUTE_IN_SECONDS', 60 );
function get_option( $key, $default = false ) { return $GLOBALS['options'][$key] ?? $default; }
function update_option( $key, $value, $autoload = null ) { $GLOBALS['options'][$key] = $value; }
function get_transient( $key ) { return false; }
function set_transient( $key, $value, $ttl ) {}
function delete_transient( $key ) { $GLOBALS['released'] = true; }
function wp_cache_delete( $key, $group ) { $GLOBALS['invalidated'][] = $key; }
function wp_clear_scheduled_hook( $hook ) { $GLOBALS['next'] = null; }
function wp_schedule_single_event( $time, $hook ) { $GLOBALS['next'] = $time; }
function wp_timezone() { return new DateTimeZone( 'UTC' ); }
function home_url() { return 'https://example.test'; }
function wp_parse_url( $url, $part ) { return parse_url( $url, $part ); }
function wp_list_pluck( $rows, $key ) { return array_column( $rows, $key ); }
function get_bloginfo( $key ) { return 'Test'; }
function wp_specialchars_decode( $value, $flags ) { return $value; }
class THRG_Site_Inspector {
    static function collect() { $GLOBALS['options']['thrg_si_schedule'] = $GLOBALS['during']; return array(); }
    static function evaluate( $value ) { return array(); }
    static function version() { return '2.5.1'; }
}
class THRG_SI_History {
    static function add( ...$args ) { return null; }
    static function all() { return array(); }
}
class THRG_SI_Email {
    const MAX_RECIPIENTS = 25;
    const NOTE_LENGTH = 1000;
    // Normalization itself is covered against the real helper in email.php.
    static function recipient_ids( $value ) { return $value; }
    static function recipients() { return array( array( 'id' => 1 ), array( 'id' => 2 ) ); }
    static function groups( $rows ) { return array( 'fix' => array(), 'look' => array() ); }
    static function send( $entry, $older, $ids, $note, $sender, $scheduled ) {
        $GLOBALS['sent'][] = array( $ids, $note );
        return array( 'sent' => count( $ids ), 'failed' => 0 );
    }
}
require __DIR__ . '/../includes/class-thrg-si-schedule.php';
$initial = array( 'enabled' => 1, 'who' => 'chosen', 'users' => array( 1 ), 'note' => 'Before' );
foreach ( array( 'disabled', 'changed', 'skipped' ) as $case ) {
    $GLOBALS['options'] = array( 'thrg_si_schedule' => $initial );
    $GLOBALS['during'] = array_merge( $initial, array( 'users' => array( 2 ), 'note' => 'After' ) );
    if ( 'disabled' === $case ) $GLOBALS['during']['enabled'] = 0;
    if ( 'skipped' === $case ) $GLOBALS['during']['only_issues'] = 1;
    $GLOBALS['sent'] = array();
    $GLOBALS['invalidated'] = array();
    $GLOBALS['released'] = false;
    $result = THRG_SI_Schedule::run();
    $expected = 'changed' === $case ? array( array( array( 2 ), 'After' ) ) : array();
    if ( $GLOBALS['sent'] !== $expected || ! $GLOBALS['released'] || ! in_array( 'alloptions', $GLOBALS['invalidated'], true ) || ( 'disabled' === $case && ( 'disabled' !== $result['result'] || null !== $GLOBALS['next'] ) ) ) {
        fwrite( STDERR, "FAIL $case\n" ); exit( 1 );
    }
    echo "PASS monthly run respects $case settings after inspection\n";
}
