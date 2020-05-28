<?php

/**
 * @package RSS Raw Seed Feed AppLe
 */

/*
 Plugin Name: RSS RAW Seed Feed App
 Plugin URI: http://userspace.org
 Description: This app gathers RSS feed data from selected site and prioritizes sites has no presentation , just update cache and requires the AppLepie project plugin.
 Version: 0.7.1
 Author: Daniel Yount IcarusFactor
 Author URI: http://userspace.org
 License: GPLv2 or later
 Text Domain: rseedfeed-appLe
 */

/*
 This program is free software; you can redistribute it and/or
 modify it under the terms of the GNU General Public License
 as published by the Free Software Foundation; either version 2
 of the License, or (at your option) any later version.
 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.
 You should have received a copy of the GNU General Public License
 along with this program; if not, write to the Free Software
 Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 */
defined('ABSPATH')or die('Hey, what are you doing here? You silly human!');
if(!class_exists('rseedfeedAppLe')&& class_exists('AppLePiePlugin')&& class_exist('RAWseed') ) {

    class rseedfeedAppLe {
        public $plugin;

        function __construct() {
            $this->plugin = plugin_basename(__FILE__);
        }

        function register() {
            //Hook our function , ap_create_rss_cache(), into the action ap_create_hourly_rss_cache
            add_action('ap_create_hourly_rss_cache', array($this, 'ap_create_rss_cache'));
        }

        function ap_create_rss_cache() {
            //Run code to create backup.
            //Launch page and put RAW RSS shortcode in it to update cache and update datetime. 
            //You can run each shortcode manually ,but here I put in all of them to be updated by wpcron.
            //PAGE - UPDATECACHE
            do_shortcode('[rseedApp section="Linux News Feeds" media="APTEXT"]');
            do_shortcode('[rseedApp section="Linux News Videos" media="APVIDEO"]');
            do_shortcode('[rseedApp section="AUDIO FEED SOURCES" media="APAUDIO"]');
            do_shortcode('[rseedApp section="INFOSEC SOURCES" media="APTEXT"]');
            return 1;
        }

        function activate() {
            // Require parent plugin
            if(!is_plugin_active('applepie_plugin/applepie-plugin.php')and current_user_can('activate_plugins')) {
                // Stop activation redirect and show error
                wp_die('Sorry, but this plugin requires the Parent Plugin to be installed and active. <br><a href="' . 
                       admin_url('plugins.php'). 
                                 '">&laquo; Return to Plugins</a>');
            }
            require_once plugin_dir_path(__FILE__). 'inc/feed-app-activate.php';
            rseedfeedAppActivate::activate();
        }
        // Place modification scripts here for Applepie plugin. Hardcoded to first item of each feed.
        public static function ap_create_hourly_rss_caching_schedule() {
            //Use wp_next_scheduled to check if the event is already scheduled
            $timestamp = wp_next_scheduled('ap_create_hourly_rss_cache');
            //If $timestamp == false schedule hourly rss caching since it hasn't been done previously
            if($timestamp == false) {
                //Schedule the event for right now, then to repeat daily using the hook 'wi_create_daily_backup'
                wp_schedule_event(time(), 'hourly', 'ap_create_hourly_rss_cache');
                //error_log("NOTICE: AP_CREATE_HOURLY_RSS_CACHE"); 
            }
            //error_log("NOTICE: AP_CREATE_HOURLY_RSS_CACHING_SCHEDULE"); 
        }

        function start_up($atts) {
            $a2b =[[]];
            //Create RAWseed prioity instance.
            $RAWseed = new RAWseed();
            // Working on APTEXT 
            $a = shortcode_atts(array('section' => 'Linux News Feeds', 'media' => 'APTEXT'), $atts);
            // Loop DB items , grab feed data to update cache and timestamp and loop check so it does not go wild. Max items 100
            $IDnotempty = 1;
            $loopID = 1;
            while($IDnotempty == 1 || $loopID <= 100) {
                //Grab RSS feed data and priority from the ID and section name.
                // This will return one row with the id priority based on date.
                $a2b = $RAWseed->priority_cast($loopID, $a['section']);
                //Test if empty , if so end 
                if(empty($a2b['rss'])) {
                    $IDnotempty = 0;
                    $loopID ++;
                    break;
                }
                //rss feed data exist , let continue.
                $ApplepiePlugin = new AppLePiePlugin();
                list($permrss, $titlerss, $daterss, $contentrss)= $ApplepiePlugin->feed_generate_process($a2b['rss'], 2, $a['media'], $a2b['id']);              
                //Error check return of feed data.
                if(empty($permrss)|| empty($titlerss)) {
                    $dat = array();
                    if(empty($permrss)) {
                        $dat[0] = 1;
                    }
                    if(empty($titlerss)) {
                        $dat[1] = 1;
                    }
                    if(empty($daterss)) {
                        $dat[2] = 1;
                    }
                    if(empty($contentrss)) {
                        $dat[3] = 1;
                    }
                    // Give feed back to the front end so we know which feed is causing the problem.
                    $Content = "NO DATA FROM " . $a2b['site'];
                    return $Content;
                }
                // No check, just update, so priority setting can be aligned with current one.                              
                $RAWseed->update_timestamp($a2b['id'], $daterss[1]);
                // Loop until content is empty
                $loopID ++;
            }
            // If gotten to this point cache is filled for section and we can return.
            return "RSS CACHE/DATE UPDATE FINISHED";
        }
    }
    $rseedfeedApp = new rseedfeedAppLe();
    $rseedfeedApp->register();
    // activation
    register_activation_hook(__FILE__, array($rseedfeedApp, 'activate'));
    //On plugin activation schedule our hourly database and rss cache datetime update 
    register_activation_hook(__FILE__, array($rseedfeedApp, 'ap_create_hourly_rss_caching_schedule'));
    // deactivation
    require_once plugin_dir_path(__FILE__). 'inc/feed-app-deactivate.php';
    register_deactivation_hook(__FILE__, array('rseedfeedAppDeactivate', 'deactivate'));
    //Use hooks from parent plugin.  
    add_shortcode('rseedApp', array($rseedfeedApp, 'start_up'));
}
