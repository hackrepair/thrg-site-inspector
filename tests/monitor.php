<?php
// Tests for THRG_SI_Monitor: settings normalization, inventory diffing, log cap, immediate alerts, and daily cron.
define( 'ABSPATH', __DIR__ . '/' );
define( 'DAY_IN_SECONDS', 86400 );
define( 'MINUTE_IN_SECONDS', 60 );

$GLOBALS['hooks']      = array();
$GLOBALS['options']    = array();
$GLOBALS['transients'] = array();
$GLOBALS['mails']      = array();
$GLOBALS['users_map']  = array();
$GLOBALS['admins']     = array();
$GLOBALS['plugins']    = array();
$GLOBALS['themes']     = array();
$GLOBALS['on_cache']   = null;

function add_action( $hook, $cb, $priority = 10, $args = 1 ) {
    $GLOBALS['hooks'][ $hook ] = array( $cb, $priority, $args );
}
function get_option( $k, $default = false ) {
    return array_key_exists( $k, $GLOBALS['options'] ) ? $GLOBALS['options'][ $k ] : $default;
}
function update_option( $k, $v, $autoload = null ) {
    $GLOBALS['options'][ $k ] = $v;
    return true;
}
function delete_option( $k ) {
    unset( $GLOBALS['options'][ $k ] );
    return true;
}
function get_transient( $k ) {
    return array_key_exists( $k, $GLOBALS['transients'] ) ? $GLOBALS['transients'][ $k ] : false;
}
function set_transient( $k, $v, $ttl = 0 ) {
    $GLOBALS['transients'][ $k ] = $v;
    return true;
}
function delete_transient( $k ) {
    unset( $GLOBALS['transients'][ $k ] );
    return true;
}
function wp_cache_delete( $key, $group = '' ) {
    if ( is_callable( $GLOBALS['on_cache'] ) ) {
        call_user_func( $GLOBALS['on_cache'], $key, $group );
    }
    return true;
}
function wp_clean_plugins_cache( $clear = true ) {}
function get_users( $args = array() ) {
    return $GLOBALS['admins'];
}
function get_plugins() {
    return $GLOBALS['plugins'];
}
function wp_get_themes() {
    return $GLOBALS['themes'];
}
function get_userdata( $id ) {
    return isset( $GLOBALS['users_map'][ $id ] ) ? $GLOBALS['users_map'][ $id ] : false;
}
function admin_url( $path = '' ) {
    return 'https://example.com/wp-admin/' . $path;
}
function wp_mail( $to, $subject, $message ) {
    $GLOBALS['mails'][] = array(
        'to'      => $to,
        'subject' => $subject,
        'message' => $message,
    );
    return $GLOBALS['mail_results'][ $to ] ?? true;
}

class Fake_Theme {
    private $name;
    public function __construct( $name ) {
        $this->name = $name;
    }
    public function get( $field ) {
        return 'Name' === $field ? $this->name : '';
    }
}

final class THRG_SI_Email {
    const MAX_RECIPIENTS = 25;
    public static function site_domain() {
        return 'example.com';
    }
    public static function recipients() {
        return array(
            array( 'id' => 1, 'name' => 'Alice', 'email' => 'alice@example.com' ),
            array( 'id' => 2, 'name' => 'Bob', 'email' => 'bob@example.com' ),
        );
    }
}

final class THRG_Site_Inspector {
    const PAGE_URL = 'tools.php?page=thrg-site-inspector';
}

require __DIR__ . '/../includes/class-thrg-si-monitor.php';

function check( $ok, $label ) {
    if ( ! $ok ) {
        fwrite( STDERR, "FAIL $label\n" );
        exit( 1 );
    }
    echo "PASS $label\n";
}

// 1. Hook registration.
THRG_SI_Monitor::boot();
check( isset( $GLOBALS['hooks']['add_user_role'] ) && 2 === $GLOBALS['hooks']['add_user_role'][2], 'boot hooks add_user_role with 2 args' );
check( ! isset( $GLOBALS['hooks']['set_user_role'] ), 'boot does not double-hook set_user_role (WP 6.9+ fires add_user_role inside set_role)' );
check( isset( $GLOBALS['hooks'][ THRG_SI_Monitor::HOOK ] ), 'boot hooks daily cron event' );

// 2. Settings normalization.
check( null === THRG_SI_Monitor::normalize_settings( false ), 'false option normalizes to null' );
check( null === THRG_SI_Monitor::normalize_settings( 'bad' ), 'scalar option normalizes to null' );
$norm = THRG_SI_Monitor::normalize_settings(
    array(
        'users'     => array_merge( array( -1, 0, 'x', 2, 2 ), range( 1, 30 ) ),
        'immediate' => 1,
    )
);
check(
    is_array( $norm )
    && THRG_SI_Email::MAX_RECIPIENTS === count( $norm['users'] )
    && true === $norm['immediate']
    && false === $norm['digest'],
    'normalize_settings deduplicates, drops bad IDs, caps at 25, and normalizes booleans'
);

// 3. Diffing.
$prev = array(
    'Administrator' => array( 1 => 'alice' ),
    'Plugin'        => array( 'a/a.php' => 'Alpha (a/a.php)' ),
    'Theme'         => array( 'twentytwentyfive' => 'Twenty Twenty-Five (twentytwentyfive)' ),
);
$curr = array(
    'Administrator' => array( 1 => 'alice', 3 => 'carol' ),
    'Plugin'        => array( 'a/a.php' => 'Alpha 2.0 (a/a.php)', 'b/b.php' => 'Beta (b/b.php)' ),
    'Theme'         => array(),
);
$diff = THRG_SI_Monitor::diff( $prev, $curr );
check(
    array( 'Administrator added: carol', 'Plugin added: Beta (b/b.php)' ) === $diff,
    'diff reports additions and stays quiet on updates and removals'
);
check(
    array() === THRG_SI_Monitor::diff( array( 'Plugin' => 'corrupt' ), array( 'Plugin' => array( 'a/a.php' => 'Alpha' ) ) ),
    'diff handles corrupt prior category without false flood'
);

// 4. Bounded log.
$log = THRG_SI_Monitor::append_log( 'corrupt', array_map( static function ( $n ) { return "Plugin added: P$n"; }, range( 1, 60 ) ), 1700000000 );
check(
    50 === count( $log ) && 'Plugin added: P11' === $log[0][1] && 'Plugin added: P60' === $log[49][1],
    'append_log handles corrupt input and keeps latest 50 entries'
);

// 5. Immediate administrator alerts.
$GLOBALS['options']['thrg_si_monitor'] = array(
    'users'     => array( 1 ),
    'immediate' => true,
    'digest'    => false,
);
$GLOBALS['users_map'][5] = (object) array(
    'ID'         => 5,
    'user_login' => 'newadmin',
    'roles'      => array( 'administrator' ),
);
$GLOBALS['mails'] = array();
THRG_SI_Monitor::grant( 5, 'administrator' );
check(
    1 === count( $GLOBALS['mails'] )
    && 'alice@example.com' === $GLOBALS['mails'][0]['to']
    && '[example.com] [Site Inspector] Administrator access granted' === $GLOBALS['mails'][0]['subject']
    && false !== strpos( $GLOBALS['mails'][0]['message'], "Administrator access granted to newadmin\n\nReview these changes" ),
    'add_user_role for administrator emails selected recipient with blank-line spacing'
);

THRG_SI_Monitor::grant( 5, 'editor' );
check( 1 === count( $GLOBALS['mails'] ), 'non-administrator role does not alert' );

$GLOBALS['options']['thrg_si_monitor']['immediate'] = false;
THRG_SI_Monitor::grant( 5, 'administrator' );
check( 1 === count( $GLOBALS['mails'] ), 'no immediate alert when immediate option is off' );

// 6. Daily run: lock, diff, digest, and mid-run disable.
$GLOBALS['admins']  = array( (object) array( 'ID' => 1, 'user_login' => 'alice' ) );
$GLOBALS['plugins'] = array(
    'a/a.php' => array( 'Name' => 'Alpha' ),
    'b/b.php' => array( 'Name' => 'Beta' ),
);
$GLOBALS['themes']  = array( 't1' => new Fake_Theme( 'Theme One' ) );
$GLOBALS['options'] = array(
    'thrg_si_monitor'   => array(
        'users'     => array( 2 ),
        'immediate' => false,
        'digest'    => true,
    ),
    'thrg_si_inventory' => array(
        'items' => array(
            'Administrator' => array( 1 => 'alice' ),
            'Plugin'        => array( 'a/a.php' => 'Alpha (a/a.php)' ),
            'Theme'         => array( 't1' => 'Theme One (t1)' ),
        ),
        'time'  => 1000,
        'log'   => array(),
    ),
);

$GLOBALS['mails'] = array();
$GLOBALS['transients'][ THRG_SI_Monitor::LOCK ] = 1;
THRG_SI_Monitor::daily();
check( 0 === count( $GLOBALS['mails'] ) && 1000 === $GLOBALS['options']['thrg_si_inventory']['time'], 'daily skips run while lock is held' );

unset( $GLOBALS['transients'][ THRG_SI_Monitor::LOCK ] );
THRG_SI_Monitor::daily();
check(
    ! isset( $GLOBALS['transients'][ THRG_SI_Monitor::LOCK ] )
    && 1 === count( $GLOBALS['mails'] )
    && 'bob@example.com' === $GLOBALS['mails'][0]['to']
    && false !== strpos( $GLOBALS['mails'][0]['message'], 'Plugin added: Beta (b/b.php)' )
    && 1 === count( $GLOBALS['options']['thrg_si_inventory']['log'] ),
    'daily records addition, sends digest, and releases lock'
);

$GLOBALS['mails'] = array();
THRG_SI_Monitor::daily();
check( 0 === count( $GLOBALS['mails'] ), 'daily sends no digest when nothing was added' );

// Mid-run disable aborts saving inventory and sending digest.
$GLOBALS['plugins']['c/c.php'] = array( 'Name' => 'Gamma' );
$GLOBALS['on_cache']           = static function ( $key ) {
    if ( 'thrg_si_monitor' === $key ) {
        unset( $GLOBALS['options']['thrg_si_monitor'], $GLOBALS['options']['thrg_si_inventory'] );
    }
};
THRG_SI_Monitor::daily();
$GLOBALS['on_cache'] = null;
check(
    ! isset( $GLOBALS['options']['thrg_si_inventory'] ) && 0 === count( $GLOBALS['mails'] ),
    'daily aborts saving inventory and emailing if monitoring was disabled mid-run'
);


// Latest monitoring email status must reflect the actual transport result.
$GLOBALS['options']['thrg_si_monitor'] = array( 'users' => array( 1, 2 ), 'immediate' => true );
foreach ( array(
    array( array(), 2, 0 ),
    array( array( 'bob@example.com' => false ), 1, 1 ),
    array( array( 'alice@example.com' => false, 'bob@example.com' => false ), 0, 2 ),
) as $case ) {
    $GLOBALS['mail_results'] = $case[0];
    THRG_SI_Monitor::grant( 5, 'administrator' );
    $status = $GLOBALS['options']['thrg_si_monitor_mail'];
    check( $status['sent'] === $case[1] && $status['failed'] === $case[2] && $status['time'] > 0 && $status['title'] === 'Administrator access granted', 'latest monitoring attempt records accepted and failed counts' );
}
$GLOBALS['options']['thrg_si_monitor']['users'] = array( 99 );
THRG_SI_Monitor::grant( 5, 'administrator' );
$status = $GLOBALS['options']['thrg_si_monitor_mail'];
check( 0 === $status['sent'] && 0 === $status['failed'], 'no eligible recipients replaces stale success counts' );
