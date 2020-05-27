<?php
/**
 * @package  AppLe
 */

class rseedfeedAppDeactivate
{
	public static function deactivate() {
		flush_rewrite_rules();
                wp_clear_scheduled_hook('ap_create_hourly_rss_cache'); 
	}

}
