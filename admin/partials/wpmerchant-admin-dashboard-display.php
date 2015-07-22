<?php

/**
 * Provide a admin area view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @link       wpmerchant.com/team
 * @since      1.0.0
 *
 * @package    Wpmerchant
 * @subpackage Wpmerchant/admin/partials
 */
?>
<!-- This file should primarily consist of HTML with a little bit of PHP. -->
<div class="wrap">
				<div id="no-data-view">
					<?php settings_errors(); ?>  
		            <?php 
		            if( $active_slide == 'payments' ) {  
						$image_class = 'payments';
						$header = 'Payments';
						$description = 'You haven\'t linked any payment processors. <a href="https://stripe.com/docs/tutorials/sending-transfers" target="_blank" class="arrow">Learn more</a>';
						$btn = '';
					    $this->dashboard_slide_contents($image_class, $header, $description, $btn);
		            } elseif( $active_slide == 'newsletters' ) {
						$image_class = 'newsletters';
						$header = 'Newsletters';
						$description = 'You haven\'t linked any newsletter providers. <a href="https://stripe.com/docs/tutorials/sending-transfers" target="_blank" class="arrow">Learn more</a>';
						$btn = '';
					    $this->dashboard_slide_contents($image_class, $header, $description, $btn);
		            }
		            ?>
				</div>
				<div class="wpm_bullets">
		            <a class="<?= ( $active_slide == 'payments' ) ? 'active' : ''; ?>" href="/wp-admin/admin.php?page=wpmerchant&slide=payments"></a>
		            <a class="<?= ( $active_slide == 'newsletters' ) ? 'active' : ''; ?>" href="/wp-admin/admin.php?page=wpmerchant&slide=newsletters"></a>
				</div>
</div>