<?php

	namespace EWB;

	use EWB\Modules\Custom_Widget\Module;

	if(!defined('ABSPATH') || !defined('WPINC')) { exit; }

	/**
	 * Class Plugin
	 *
	 * @version 1.0
	 * @since   1.0
	 * @package EWB
	 */
	final class Plugin {

		/**
		 * @var
		 */
		public $custom_widgets;

		/**
		 * @var
		 */
		private $admin;

		/**
		 * Minimum Elementor Version
		 */
		const MINIMUM_ELEMENTOR_VERSION = '2.9.0';

		/**
		 * @var object instanceof EWB || null
		 */
		private static $_instance = null;

		/**
		 * Ensures only one instance of EWB is loaded
		 *
		 * @version 1.0
		 * @since   1.0
		 * @return  object instanceof EWB
		 */
		public static function instance() {

			if(is_null(self::$_instance)) {
				self::$_instance = new self();
			}

			return self::$_instance;

		}

		/**
		 * __clone function
		 *
		 * @version 1.0
		 * @since   1.0
		 */
		public function __clone() {
			wc_doing_it_wrong(__FUNCTION__, 'Cloning is not allowed');
		}

		/**
		 * __wakeup function
		 *
		 * @version 1.0
		 * @since   1.0
		 */
		public function __wakeup() {
			wc_doing_it_wrong(__FUNCTION__, 'Unserializing instances of this class is not allowed');
		}

		/**
		 * Plugin constructor.
		 */
		private function __construct() {

			// Loads text domain
			//add_action('init', [$this, 'i18n']); // TODO LOAD TEXTDOMAIN

			// Init plugin
			add_action('plugins_loaded', [$this, 'init']);

			// Registers autoloader
			$this->register_autoloader();

		}

		/**
		 * i18n function
		 *
		 * @version 1.0
		 * @since   1.0
		 */
		public function i18n() {

			//load_plugin_textdomain('ewb');

		}

		/**
		 * Initiates our plugin
		 *
		 * @version 1.0
		 * @since   1.0
		 */
		public function init() {

			// Check if Elementor installed and activated
			if(!did_action('elementor/loaded')) {
				add_action('admin_notices', [$this, 'notice_missing_main_plugin']);
				return;
			}

			// Check Elementor version
			if(!version_compare(ELEMENTOR_VERSION, self::MINIMUM_ELEMENTOR_VERSION, '>=')) {
				add_action('admin_notices', [$this, 'notice_minimum_elementor_version']);
				return;
			}

			// Check folder write permissions
			if(!is_writable(EWB_DIR.'/includes/custom-widget/classes')) {
				add_action('admin_notices', [$this, 'notice_writeable_folder']);
				return;
			}

			// Initiates on elementor init
			add_action('elementor/init', [$this, 'on_elementor_init']);

		}

		/**
		 * notice_writeable_folder function
		 *
		 * @version 1.0.1
		 * @since   1.0.1
		 */
		public function notice_writeable_folder() {

			$message = sprintf(
				'<strong>EleBuilder does not have permissions to write to its folder!</strong><br> In order to be able to generate your widget classes the plugin must have write permissions to the folder "<strong>%s</strong>".<br>If you are unsure how to set write permissions, <a href="%s" target="_blank">we wrote some instructions how to do it.</a>',
				EWB_DIR.'/includes/custom-widget/classes',
				'https://docs.elebuilder.com/article/troubleshooting/folder-write-permissions/'
			);

			// Print message
			printf('<div class="notice notice-warning is-dismissible"><p>%1$s</p></div>', $message);

		}

		/**
		 * Once elementor has loaded loads our modules
		 *
		 * @version 1.0
		 * @since   1.0
		 */
		public function on_elementor_init() {

			$this->custom_widgets = new Module();

			$this->admin = new \EWB\Modules\Admin\Module();

		}

		/**
		 * Missing plugin notice function
		 *
		 * @version 1.0
		 * @since   1.0
		 */
		public function notice_missing_main_plugin() {

			if(isset($_GET['activate'])) {
				unset($_GET['activate']);
			}

			$message = sprintf(
				esc_html__('"%1$s" requires "%2$s" to be installed and activated.', 'ewb'),
				'<strong>'.esc_html__('EleBuilder', 'ewb' ) . '</strong>',
				'<strong>'.esc_html__('Elementor', 'ewb' ).'</strong>'
			);

			// Print message
			printf('<div class="notice notice-warning is-dismissible"><p>%1$s</p></div>', $message);

		}

		/**
		 * Minimum elementor version required function
		 *
		 * @version 1.0
		 * @since   1.0
		 */
		public function notice_minimum_elementor_version() {

			if(isset($_GET['activate'])) {
				unset($_GET['activate']);
			}

			$message = sprintf(
				esc_html__('"%1$s" requires "%2$s" version %3$s or greater.', 'ewb'),
				'<strong>'.esc_html__('EleBuilder', 'ewb' ) . '</strong>',
				'<strong>'.esc_html__('Elementor', 'ewb' ).'</strong>',
				self::MINIMUM_ELEMENTOR_VERSION
			);

			// Print message
			printf('<div class="notice notice-warning is-dismissible"><p>%1$s</p></div>', $message);

		}

		/**
		 * Registers our class autoloader
		 *
		 * @version 1.0
		 * @since   1.0
		 */
		private function register_autoloader() {
			require EWB_DIR . '/includes/autoloader.php';

			\EWB\Autoloader::run();
		}

		/**
		 * get_registered_custom_widgets function
		 *
		 * @version 1.0
		 * @since   1.0
		 */
		public function get_registered_custom_widgets() {

			// Queries them
			$q = new \WP_Query([
				'post_type' => 'elementor_library',
				'post_status' => 'publish',
				'nopaging' => true,
				'tax_query' => [
					[
						'taxonomy' => 'elementor_library_type',
						'terms' => ['ewb-widget'],
						'value' => 'slug',
						'compare' => 'IN',
					]
				],
				'fields' => 'ids',
			]);

			$docs = [];

			// Loops results
			foreach($q->posts as $post_id) {

				// Grabs document belonging to post
				$document = \Elementor\Plugin::instance()->documents->get($post_id);

				// Not a wifdget builder one
				if(strpos($document->get_name(), 'ewb-widget') === false)
					continue;

				// Method ensures the widget class file exists
				$document->ensure_class_exists();

				$docs[] = $document;
			}

			return $docs;
		}



	}