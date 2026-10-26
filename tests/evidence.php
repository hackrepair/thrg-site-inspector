<?php
// Filesystem collectors: run against a temporary directory tree.
define( 'ABSPATH', __DIR__ . '/' );
require __DIR__ . '/../includes/class-thrg-inspection-evidence.php';

function check( $cond, $name ) {
    if ( ! $cond ) {
        fwrite( STDERR, "FAIL $name\n" );
        exit( 1 );
    }
    echo "PASS $name\n";
}

$root = sys_get_temp_dir() . '/thrg-evidence-' . bin2hex( random_bytes( 6 ) );
mkdir( $root );
mkdir( $root . '/nested' );
mkdir( $root . '/wpforms' );
file_put_contents( $root . '/nested/example.php.jpg', '' );
file_put_contents( $root . '/photo.jpg', '' );
file_put_contents( $root . '/.htaccess', '' );
file_put_contents( $root . '/wpforms/index.php', '<?php // Silence is golden.' );
file_put_contents( $root . '/nested/index.php', str_repeat( 'x', 500 ) );

$site = sys_get_temp_dir() . '/thrg-site-' . bin2hex( random_bytes( 6 ) );
mkdir( $site );
mkdir( $site . '/wp-admin' );
file_put_contents( $site . '/error_log', 'x' );
file_put_contents( $site . '/wp-admin/error_log', 'x' );
file_put_contents( $site . '/site-backup.zip', 'x' );
file_put_contents( $site . '/wp-config.php', 'x' );

try {
    $q = THRG_Inspection_Evidence::account_query( 3 );
    check( 3 === $q['blog_id'] && THRG_Inspection_Evidence::watched_caps() === $q['capability__in'] && 'DESC' === $q['order'] && 'ID' === $q['orderby'] && THRG_Inspection_Evidence::ACCOUNT_LIMIT + 1 === $q['number'], 'account query: privileged accounts only, newest first' );
    $result = THRG_Inspection_Evidence::uploads( $root );
    $paths  = array_column( $result['items'], 'path' );
    sort( $paths );
    check( array( 'nested/example.php.jpg', 'nested/index.php' ) === $paths && ! $result['partial'] && array( '.htaccess' ) === $result['rules'], 'nested candidates and rule-file discovery' );
    check( array( 'wpforms/index.php' ) === $result['placeholders'], 'tiny index.php placeholder not flagged' );
    $limited = THRG_Inspection_Evidence::uploads( $root, 1 );
    check( $limited['partial'] && 1 === $limited['checked'], 'budget exhaustion is partial' );
    symlink( $root . '/nested', $root . '/link' );
    check( THRG_Inspection_Evidence::uploads( $root )['partial'], 'symlink skipped with incomplete coverage' );
    $missing = THRG_Inspection_Evidence::uploads( $root . '/missing' );
    check( $missing['partial'] && $missing['missing'], 'missing uploads is unknown' );

    $found = array_column( THRG_Inspection_Evidence::sensitive( $site ), 'path' );
    sort( $found );
    check( array( 'error_log', 'site-backup.zip', 'wp-admin/error_log' ) === $found, 'error_log files and root archives found; wp-config.php itself ignored' );
} finally {
    @unlink( $root . '/link' );
    foreach ( array( 'nested/example.php.jpg', 'nested/index.php', 'wpforms/index.php', 'photo.jpg', '.htaccess' ) as $f ) {
        unlink( $root . '/' . $f );
    }
    rmdir( $root . '/nested' );
    rmdir( $root . '/wpforms' );
    rmdir( $root );
    foreach ( array( 'error_log', 'wp-admin/error_log', 'site-backup.zip', 'wp-config.php' ) as $f ) {
        unlink( $site . '/' . $f );
    }
    rmdir( $site . '/wp-admin' );
    rmdir( $site );
}
