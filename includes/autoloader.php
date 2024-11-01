<?php

	namespace EWB;

	if(!defined('ABSPATH') || !defined('WPINC')) { exit; }

	/**
	 * Class Autoloader
	 *
	 * @version 1.0
	 * @since   1.0
	 * @package EWB
	 */
	class Autoloader {

		/**
		 * @var
		 */
		private static $classes_map;

		/**
		 * run function
		 *
		 * @version 1.0
		 * @since   1.0
		 */
		public static function run() {
			spl_autoload_register([__CLASS__, 'autoload']);
		}

		/**
		 * get_classes_map function
		 *
		 * @version 1.0
		 * @since   1.0
		 * @return mixed
		 */
		public static function get_classes_map() {
			if(!self::$classes_map) {
				self::init_classes_map();
			}
			return self::$classes_map;
		}

		/**
		 * init_classes_map function
		 *
		 * @version 1.0
		 * @since   1.0
		 */
		public static function init_classes_map() {
			self::$classes_map = [
				'Modules\Custom_Widget\Module' => 'includes/custom-widget/module.php',
				'Modules\Custom_Widget\Document' => 'includes/custom-widget/document.php',
				'Modules\Custom_Widget\Widget_CSS' => 'includes/custom-widget/widget-css.php',
				'Modules\Custom_Widget\Source_EWB' => 'includes/custom-widget/source.php',
				'Modules\Custom_Widget\Widget_CSS_Preview' => 'includes/custom-widget/widget-css-preview.php',
				'Modules\Admin\Module' => 'includes/admin/admin.php',
			];
		}

		/**
		 * Loads classes on demand
		 *
		 * @version 1.0
		 * @since   1.0
		 *
		 * @param $relative_class_name
		 */
		private static function load_class($relative_class_name) {
			$classes_map = self::get_classes_map();

			if(isset($classes_map[$relative_class_name])) {
				$filename = EWB_DIR.'/'.$classes_map[$relative_class_name];
			} else {
				$filename = strtolower(preg_replace(['/([a-z])([A-Z])/', '/_/', '/\\\/'], ['$1-$2', '-', "/"], $relative_class_name));
				$filename = EWB_DIR.$filename.'.php';
			}

			//TODO Autoload widgets classes

			if(is_readable($filename)) {
				require $filename;
			}
		}

		/**
		 * Auto load
		 *
		 * @version 1.0
		 * @since   1.0
		 *
		 * @param $class
		 */
		private static function autoload($class) {
			if(0 !== strpos($class, __NAMESPACE__.'\\')) {
				return;
			}

			$relative_class_name = preg_replace('/^'.__NAMESPACE__.'\\\/', '', $class);
			$final_class_name = __NAMESPACE__.'\\'.$relative_class_name;

			if(!class_exists($final_class_name)) {
				self::load_class($relative_class_name);
			}
		}

	}