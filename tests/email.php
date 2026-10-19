<?php
// Email report builders: subject, narrative, change line, escaping, masking, plain text.
define( 'ABSPATH', __DIR__ . '/' );
define( 'DAY_IN_SECONDS', 86400 );
function home_url( $p = '' ) { return $GLOBALS['test_home_url'] ?? 'https://example.com/'; }
function site_url( $p = '' ) { return 'https://fallback.example/'; }
function wp_parse_url( $url, $part ) { return parse_url( $url, $part ); }
function admin_url( $p = '' ) { return 'http://x/wp-admin/' . $p; }
function esc_url( $u ) { return $u; }
function esc_html( $t ) { return htmlspecialchars( (string) $t, ENT_QUOTES ); }
require __DIR__ . '/../includes/class-thrg-site-inspector.php';

function check( $cond, $name ) {
    if ( ! $cond ) {
        fwrite( STDERR, "FAIL $name\n" );
        exit( 1 );
    }
    echo "PASS $name\n";
}
function row( $id, $status, $extra = array() ) {
    return array_merge( array( 'id' => $id, 'title' => ucfirst( $id ), 'status' => $status, 'why' => 'Why ' . $id . '.', 'evidence' => array(), 'limits' => '', 'next' => '', 'table' => null, 'actions' => array() ), $extra );
}

$fix   = array( row( 'updates', 'Potential weakness', array( 'next' => 'Apply them in [[update-core.php|Dashboard > Updates]].', 'evidence' => array( 'Data is fresh. [[update-core.php?force-check=1|Check again]]' ) ) ), row( 'php', 'Potential weakness' ), row( 'accounts', 'Review', array( 'table' => array( 'head' => array( 'ID', 'Username' ), 'rows' => array( array( array( '#1', 'user-edit.php?user_id=1' ), array( 'secretadmin', 'user-edit.php?user_id=1' ) ) ), 'mask' => 1 ) ) ), row( 'keys', 'No issue observed' ) );
$look  = array( row( 'accounts', 'Review' ), row( 'uploads', 'Partial inspection' ), row( 'keys', 'No issue observed' ) );
$clean = array( row( 'keys', 'No issue observed' ), row( 'php', 'No issue observed' ) );

check( '[example.com] [Site Inspector] Security Check: 2 issues need attention (1 new since last check)' === THRG_SI_Email::subject( $fix, 'example.com', array( 'first' => false, 'new' => 1, 'resolved' => 0 ) ), 'subject with issues and new count' );
check( '[example.com] [Site Inspector] Security Check: 1 issue needs attention' === THRG_SI_Email::subject( array( row( 'a', 'Potential weakness' ) ), 'example.com' ), 'singular subject' );
check( '[example.com] [Site Inspector] Security Check: nothing urgent, 2 items worth a look (3 fixed since last check)' === THRG_SI_Email::subject( $look, 'example.com', array( 'first' => false, 'new' => 0, 'resolved' => 3 ) ), 'review-only subject with fixes' );
check( '[example.com] [Site Inspector] Security Check: all clear on 2 checks' === THRG_SI_Email::subject( $clean, 'example.com', array( 'first' => true, 'new' => 0, 'resolved' => 0 ) ), 'all-clear subject' );

check( false !== strpos( THRG_SI_Email::narrative( $fix, 'example.com' ), "found 2 things that could make example.com easier to break into, plus 1 item worth a second look" ), 'narrative for issues' );
check( 0 === strpos( THRG_SI_Email::narrative( $clean, 'example.com' ), 'Good news' ), 'narrative for clean site' );
check( 'Since the previous check on Oct 1: 2 issues fixed, 1 new issue.' === THRG_SI_Email::change_line( array( 'first' => false, 'new' => 1, 'resolved' => 2 ), 'Oct 1' ), 'change line' );
check( 0 === strpos( THRG_SI_Email::change_line( array( 'first' => true ) ), 'This is the first saved report' ), 'first report line' );

$args = array( 'rows' => $fix, 'site' => 'example.com', 'when' => 'Oct 16', 'change' => null, 'older_when' => '', 'note' => "<script>alert(1)</script>\nSee below", 'sender' => 'Jim & Co', 'recipient' => 'Pat <admin>', 'view_url' => 'http://x/wp-admin/tools.php?page=thrg-site-inspector&thrg_report=abcdef123456' );
$html = THRG_SI_Email::html( $args );
check( false === strpos( $html, '<script>' ) && false !== strpos( $html, '&lt;script&gt;' ) && false !== strpos( $html, 'Jim &amp; Co' ) && false !== strpos( $html, 'Hi Pat &lt;admin&gt;,' ), 'note, sender and recipient are escaped' );
check( false === strpos( $html, '[[' ) && false === strpos( $html, 'Check again' ) && false !== strpos( $html, 'Apply them in Dashboard &gt; Updates.' ), 'link tokens become plain text' );
check( false === strpos( $html, 'secretadmin' ) && false !== strpos( $html, 'se••••••' ), 'usernames masked in email' );
check( false !== strpos( $html, 'Fix these first' ) && false !== strpos( $html, 'Worth a second look' ) && false !== strpos( $html, 'Looking good' ) && false !== strpos( $html, 'Open the full report' ), 'html sections present' );
$text = THRG_SI_Email::text( $args );
check( false === strpos( $text, 'secretadmin' ) && false === strpos( $text, '[[' ) && false !== strpos( $text, 'FIX THESE FIRST' ) && false !== strpos( $text, 'thrg_report=abcdef123456' ), 'plain-text alternative' );

check( false === strpos( $html, 'max-width:600px' ) && false === strpos( $html, 'width="600"' ) && false !== strpos( $html, 'width:100%;border-collapse:collapse;' ), 'full-width HTML email layout' );
check( 'example.com' === THRG_SI_Email::site_domain(), 'domain from configured home URL' );
$GLOBALS['test_home_url'] = 'http://localhost:8081/wordpress/';
check( 'localhost:8081' === THRG_SI_Email::site_domain(), 'local port retained, path omitted' );
check( 0 === strpos( THRG_SI_Email::subject( $fix, THRG_SI_Email::site_domain() ), '[localhost:8081] [Site Inspector] Security Check:' ), 'subject uses domain and plugin tags' );
$GLOBALS['test_home_url'] = 'https://Shop.Example.com/path/?query=1';
check( 'shop.example.com' === THRG_SI_Email::site_domain(), 'subdomain retained, scheme/path/query omitted' );
$GLOBALS['test_home_url'] = '/missing-host';
check( 'fallback.example' === THRG_SI_Email::site_domain(), 'fallback to configured site URL' );

check( 'Email accepted for sending: 1. Failed: 0. Acceptance does not confirm delivery.' === THRG_SI_Email::feedback( 1, 0 ), 'successful send acceptance message' );
check( 'Email accepted for sending: 1. Failed: 2. Acceptance does not confirm delivery.' === THRG_SI_Email::feedback( 1, 2 ), 'partial failure counts' );
check( 'Email accepted for sending: 0. Failed: 2. Acceptance does not confirm delivery.' === THRG_SI_Email::feedback( 0, 2 ), 'all-failed counts' );
