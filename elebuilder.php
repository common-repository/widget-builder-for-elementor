<?php
	/**
	 * @package           EWB
	 * @author            Guilherme Salum
	 * @copyright         2020 EleBuilder
	 * @license           GPL-2.0-or-later
	 * @wordpress-plugin
	 *
	 * Plugin Name:       EleBuilder - Widget Builder & Creator for Elementor
	 * Plugin URI:        https://elebuilder.com
	 * Description:       Create and build your own Elementor widgets without writing any code.
	 * Version:           1.0.3.1
	 * Requires at least: 5.3
	 * Requires PHP:      5.6
	 * Author:            elebuilder.com
	 * Text Domain:       ewb
	 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
	 * License:     GPL2

	 * EleBuilder is free software: you can redistribute it and/or modify
	 * it under the terms of the GNU General Public License as published by
	 * the Free Software Foundation, either version 2 of the License, or
	 * any later version.

	 * EleBuilder is distributed in the hope that it will be useful,
	 * but WITHOUT ANY WARRANTY; without even the implied warranty of
	 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
	 * GNU General Public License for more details.

	 * You should have received a copy of the GNU General Public License
	 * along with EleBuilder. If not, see http://www.gnu.org/licenses/gpl-2.0.txt.
    **/


	if(!defined('ABSPATH') || !defined('WPINC')) { exit; }

	// Constants
	define('EWB_VERSION', '1.0.3.1');
	define('EWB_DIR', rtrim(plugin_dir_path(__FILE__), "/"));
	define('EWB_URL', rtrim(plugin_dir_url(__FILE__), "/"));

	// Main plugin class
	require EWB_DIR.'/includes/plugin.php';

	// Alias function
	if(!function_exists('EWB')) {
		/**
		 * EWB function
		 * Returns the EWB instance
		 *
		 * @version 1.0
		 * @since   1.0
		 * @return object
		 */
		function EWB() {
			return \EWB\Plugin::instance();
		}
	}

	EWB();

?>