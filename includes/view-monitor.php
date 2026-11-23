<?php
/**
 * Monitoring settings and recent additions. Expects $admins, $thrg_si_fmt, $flag, $thrg_si_notices, $thrg_si_warn.
 *
 * @package THRG_Site_Inspector
 */

defined( 'ABSPATH' ) || exit;
$thrg_si_monitor        = THRG_SI_Monitor::normalize_settings( get_option( 'thrg_si_monitor' ) );
$thrg_si_inventory      = get_option( 'thrg_si_inventory', array() );
$thrg_si_admin_ids      = array_map( 'intval', array_column( $admins, 'id' ) );
$thrg_si_active_monitor = $thrg_si_monitor ? array_intersect( $thrg_si_monitor['users'], $thrg_si_admin_ids ) : array();
?>
<section id="si-monitor" class="si-block" aria-labelledby="si-monitor-title">
    <h2 class="si-section" id="si-monitor-title">
        <span>Monitor and report site changes <span class="si-chip si-chip--<?php echo $thrg_si_monitor ? 'observed' : 'attention'; ?>"><?php echo $thrg_si_monitor ? 'On' : 'Off'; ?></span></span>
    </h2>
    <div class="si-card">
    <p>Check daily for new administrators, plugins, and themes. The first check records what’s already on the site. Each subsequent check reports any additions since the previous check.</p>
    <?php if ( in_array( $flag, array( 'monitor-saved', 'monitor-off', 'monitor-none' ), true ) ) : ?>
        <div class="si-callout<?php echo in_array( $flag, $thrg_si_warn, true ) ? ' si-callout--warning' : ''; ?>" role="status"><p><?php echo esc_html( $thrg_si_notices[ $flag ] ); ?></p></div>
    <?php endif; ?>
    <?php if ( $thrg_si_monitor && ( $thrg_si_monitor['immediate'] || $thrg_si_monitor['digest'] ) && ! $thrg_si_active_monitor ) : ?>
        <div class="si-callout si-callout--warning" role="status"><p>None of the selected administrators currently have a valid administrator email address, so monitoring emails will not be sent until you update recipients.</p></div>
    <?php endif; ?>
    <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
        <input type="hidden" name="action" value="thrg_si_monitor" />
        <?php wp_nonce_field( 'thrg_si_monitor' ); ?>
        <p><label><input type="checkbox" name="enabled" <?php checked( (bool) $thrg_si_monitor ); ?> /> Monitor this site daily</label></p>
        <p><label><input type="checkbox" name="immediate" <?php checked( ! empty( $thrg_si_monitor['immediate'] ) ); ?> /> Email immediately when WordPress grants administrator access</label></p>
        <p><label><input type="checkbox" name="digest" <?php checked( ! empty( $thrg_si_monitor['digest'] ) ); ?> /> Email daily when the check finds additions</label></p>
        <fieldset><legend>Email these administrators</legend>
        <?php if ( ! $admins ) : ?>
            <p class="si-note">No administrator accounts with a valid email address were found, so monitoring emails cannot be sent.</p>
        <?php else : ?>
            <?php
            $thrg_si_selected_ids = $thrg_si_monitor ? $thrg_si_monitor['users'] : array();
            require __DIR__ . '/view-admin-recipients.php';
            ?>
        <?php endif; ?>
        </fieldset>
        <p>Turning monitoring off clears its inventory and recent changes.</p>
        <button class="button button-primary" type="submit">Save monitoring settings</button>
    </form>
    <?php if ( $thrg_si_monitor && is_array( $thrg_si_inventory ) && isset( $thrg_si_inventory['time'] ) ) : ?>
        <h3>Recent additions</h3>
        <p>Last inventory: <?php echo esc_html( wp_date( $thrg_si_fmt, (int) $thrg_si_inventory['time'] ) ); ?>.<?php echo empty( $thrg_si_inventory['log'] ) ? ' No additions recorded since monitoring began.' : ''; ?></p>
        <?php if ( ! empty( $thrg_si_inventory['log'] ) && is_array( $thrg_si_inventory['log'] ) ) : ?>
            <ul>
            <?php foreach ( array_reverse( $thrg_si_inventory['log'] ) as $thrg_si_change ) : ?>
                <li><?php echo esc_html( wp_date( $thrg_si_fmt, (int) $thrg_si_change[0] ) . ' — ' . $thrg_si_change[1] ); ?></li>
            <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    <?php endif; ?>
    </div>
</section>
