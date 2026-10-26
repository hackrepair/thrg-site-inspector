<?php
// Update details are sanitized after the bundled Markdown renderer runs.
define( 'ABSPATH', __DIR__ . '/' );
$GLOBALS['kses_calls'] = array();
function wp_kses_allowed_html( $ctx ) { return array( 'a' => array( 'href' => true, 'class' => true ), 'code' => array( 'class' => true ), 'p' => array(), 'br' => array(), 'script' => false ); }
function wp_kses( $html, $allowed ) { $GLOBALS['kses_calls'][] = $allowed; return 'clean:' . $html; }
// The updater file imports the update-checker namespace; the aliases only need to parse.
require __DIR__ . '/../includes/class-thrg-si-updater.php';

function check( $cond, $name ) {
    if ( ! $cond ) {
        fwrite( STDERR, "FAIL $name\n" );
        exit( 1 );
    }
    echo "PASS $name\n";
}

$allowed = THRG_SI_Updater::allowed_html();
check( ! isset( $allowed['a']['class'] ) && ! isset( $allowed['code']['class'] ) && isset( $allowed['a']['href'] ), 'class attributes removed, other attributes kept' );
check( array_key_exists( 'p', $allowed ) && array() === $allowed['p'] && false === $allowed['script'], 'tags without attributes pass through unchanged' );

$info           = new stdClass();
$info->sections = array( 'description' => '<p>a</p>', 'changelog' => '<pre><code class="x"><script>1</script>', 'faq' => 5 );
$out            = THRG_SI_Updater::clean_info( $info );
check( 'clean:<p>a</p>' === $out->sections['description'] && 0 === strpos( $out->sections['changelog'], 'clean:' ), 'text sections are sanitized' );
check( 5 === $out->sections['faq'], 'non-string sections left alone' );
check( 'x' === THRG_SI_Updater::clean_info( 'x' ) && null === THRG_SI_Updater::clean_info( null ), 'non-object results pass through' );
$bare = new stdClass();
check( $bare === THRG_SI_Updater::clean_info( $bare ), 'object without sections passes through' );
