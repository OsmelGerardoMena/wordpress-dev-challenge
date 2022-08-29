<?php

/**
 *
 * The plugin bootstrap file
 *
 * This file is responsible for starting the plugin using the main plugin class file.
 *
 * @since 0.0.1
 * @package Wordpress_dev_challenge
 *
 * @wordpress-plugin
 * Plugin Name:     Test wordpress test challenge
 * Description:     This plugin is to complete the weremote test.
 * Version:         0.0.1
 * Author:          Osmel Mena
 * Author URI:      https://www.example.com
 * License:         GPL-2.0+
 * License URI:     http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:     wordpress-dev-challenge
 * Domain Path:     /lang
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Direct access not permitted.' );
}

if ( ! class_exists( 'wordpress_dev_challenge' ) ) {

	/*
	 * main plugin_name class
	 *
	 * @class plugin_name
	 * @since 0.0.1
	 */
	class wordpress_dev_challenge {

		/*
		 * plugin_name plugin version
		 *
		 * @var string
		 */
		public $version = '4.7.5';

		/**
		 * The single instance of the class.
		 *
		 * @var plugin_name
		 * @since 0.0.1
		 */
		protected static $instance = null;

		/**
		 * Main plugin_name instance.
		 *
		 * @since 0.0.1
		 * @static
		 * @return plugin_name - main instance.
		 */
		public static function instance() {
			if ( is_null( self::$instance ) ) {
				self::$instance = new self();
			}
			return self::$instance;
		}

		/**
		 * plugin_name class constructor.
		 */
		public function __construct() {
			$this->load_plugin_textdomain();
			$this->define_constants();
			$this->includes();
			$this->define_actions();
		}

		public function load_plugin_textdomain() {
			load_plugin_textdomain( 'plugin-name', false, basename( dirname( __FILE__ ) ) . '/lang/' );
		}

		/**
		 * Include required core files
		 */
		public function includes() {
			// Load custom functions and hooks
			require_once __DIR__ . '/includes/includes.php';
		}

		/**
		 * Get the plugin path.
		 *
		 * @return string
		 */
		public function plugin_path() {
			return untrailingslashit( plugin_dir_path( __FILE__ ) );
		}


		/**
		 * Define plugin_name constants
		 */
		private function define_constants() {
			define( 'PLUGIN_NAME_PLUGIN_FILE', __FILE__ );
			define( 'PLUGIN_NAME_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
			define( 'PLUGIN_NAME_VERSION', $this->version );
			define( 'PLUGIN_NAME_PATH', $this->plugin_path() );
		}

		/**
		 * Define plugin_name actions
		 */
		public function define_actions() {
			//shortcode creation
			add_shortcode('mc-citacion', 'shortcode_view_citation');

			//Add results table
			register_activation_hook(__FILE__, 'menu_link_init');

			//ejecuting all results
			add_shortcode('menu_link_admin', 'menu_links_admin');

			//cron
			add_filter( 'cron_schedules', 'custom_cron' );

			//work cron
			add_action( 'bl_cron_hook', 'verified_url' );
			if ( ! wp_next_scheduled( 'bl_cron_hook' ) ) {
				wp_schedule_event( time(), 'five_seconds', 'bl_cron_hook' );
			}
			
		}

		/**
		 * Define plugin_name menus
		 */
		public function define_menus() {
            //
		}
	}

	$plugin_name = new wordpress_dev_challenge();
}
