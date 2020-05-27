<?php
/**
 * @package  AppLe
 */

class rseedfeedAppActivate
{
	public static function activate() {
		flush_rewrite_rules();
	}
}
