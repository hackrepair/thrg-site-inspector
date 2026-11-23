<?php
/**
 * Administrator checkboxes shared by monthly email and change monitoring.
 *
 * @package THRG_Site_Inspector
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="si-email-list">
<?php foreach ( $admins as $thrg_si_r ) : ?>
    <label><input type="checkbox" name="users[]" value="<?php echo (int) $thrg_si_r['id']; ?>" <?php checked( in_array( (int) $thrg_si_r['id'], $thrg_si_selected_ids, true ) ); ?> /> <?php echo esc_html( $thrg_si_r['name'] ); ?> <span class="si-muted">&lt;<?php echo esc_html( $thrg_si_r['email'] ); ?>&gt;</span></label>
<?php endforeach; ?>
</div>
