	<div class="wrap">
		<div id="icon-options-event" class="icon32"></div>
		<h2><?php esc_attr_e( 'Event Espresso - Volume Discounts', 'event_espresso' ); ?></h2>
		<form action="options.php" method="post">
			<?php settings_fields('espresso_volume_discounts'); ?>
			<?php do_settings_sections('volume-discounts'); ?>
			<br />			
			<p class="submit">  
				<input name="Submit" type="submit" class="button-primary" value="<?php esc_attr_e( 'Save Changes', 'event_espresso' ); ?>" />  
			</p>
		</form>
	</div>