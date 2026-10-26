<?php
// Uninstall honors the "Delete all plugin data" option.
define( 'WP_UNINSTALL_PLUGIN', true );
function is_multisite() { return false; }
function get_option( $k, $d = false ) { return array_key_exists( $k, $GLOBALS['opts'] ) ? $GLOBALS['opts'][ $k ] : $d; }
function delete_option( $k ) { unset( $GLOBALS['opts'][ $k ] ); return true; }
function delete_site_option( $k ) { $GLOBALS['site_deleted'][] = $k; return true; }
function delete_site_transient( $k ) { $GLOBALS['site_deleted'][] = 'transient:' . $k; return true; }
function check( $c, $n ) { if ( ! $c ) { fwrite( STDERR, "FAIL $n\n" ); exit( 1 ); } echo "PASS $n\n"; }
$all = array( 'thrg_shc_options' => array( 'delete_data' => 0 ), 'thrg_si_notice_dismissed' => 1, 'thrg_si_history' => array( 1 ), 'thrg_si_history_paused' => 1, 'other' => 1 );
$GLOBALS['opts'] = $all;
$GLOBALS['site_deleted'] = array();
include __DIR__ . '/../uninstall.php';
check( $all === $GLOBALS['opts'] && array() === $GLOBALS['site_deleted'], 'data kept when option is off' );
$GLOBALS['opts'] = $all;
$GLOBALS['opts']['thrg_shc_options']['delete_data'] = 1;
include __DIR__ . '/../uninstall.php';
check( array( 'other' => 1 ) === $GLOBALS['opts'], 'all plugin data deleted when option is on; other options untouched' );
check( array( 'external_updates-' . basename( dirname( __DIR__ ) ), 'transient:thrg_si_github_limit' ) === $GLOBALS['site_deleted'], 'update-check leftovers deleted when option is on' );
$GLOBALS['opts'] = array( 'thrg_si_history' => array( 1 ) );
include __DIR__ . '/../uninstall.php';
check( array( 'thrg_si_history' => array( 1 ) ) === $GLOBALS['opts'], 'never-saved settings mean keep' );
