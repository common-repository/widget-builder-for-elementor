<?php

	namespace EWB\Modules\Admin;

	if(!defined('ABSPATH') || !defined('WPINC')) { exit; }

	/**
	 * Class Admin
	 *
	 * @version 1.0
	 * @since   1.0
	 * @package EWB\Modules\Admin
	 */
	class Module extends \Elementor\Core\Base\Module {

		const DISMISSED_WELCOME_MESSAGE_META_KEY = '_ewb_dismissed_welcome';

		/**
		 * @inheritDoc
		 */
		public function get_name() {
			return 'ewb-admin';
		}

		/**
		 * Admin constructor.
		 */
		public function __construct() {

			add_action('all_admin_notices', [$this, 'welcome_message']);

			add_action('wp_ajax_hide_welcome_message', [$this, 'hide_welcome_message']);

			add_filter('plugin_action_links_elebuilder/elebuilder.php', [$this, 'action_links']);

			add_action('admin_enqueue_scripts', [$this, 'scripts']);
			add_action('admin_enqueue_scripts', [$this, 'styles']);

			// enqueus in elementor editor
			add_action('elementor/editor/after_enqueue_scripts', [$this, 'editor_scripts']);
			add_action('elementor/editor/after_enqueue_styles', [$this, 'editor_styles']);

			add_filter('plugin_row_meta', [$this, 'plugin_meta'], 10, 4);

		}

		/**
		 * editor_scripts_styles function
		 *
		 * @version 1.0
		 * @since   1.0
		 */
		public function editor_scripts() {

			wp_register_script('ewb-editor', EWB_URL.'/assets/js/editor.min.js', ['elementor-editor'], EWB_VERSION, true);

			wp_localize_script('ewb-editor', 'EWBEditor', [
				'admin_url' => admin_url('admin-ajax.php'),
				'admin_edit_url' => admin_url('post.php?post=_postid_&action=elementor')
			]);

			wp_enqueue_script('ewb-editor');
		}

		/**
		 * editor_styles function
		 *
		 * @version 1.0
		 * @since   1.0
		 */
		public function editor_styles() {

			wp_register_style('ewb-editor-css', EWB_URL.'/assets/css/editor.min.css', [], EWB_VERSION);

			wp_enqueue_style('ewb-editor-css');

		}

		/**
		 * welcome_message function
		 *
		 * @version 1.0
		 * @since   1.0
		 */
		public function welcome_message() {

			$screen = get_current_screen();

			if($screen->id !== 'plugins') {
				return;
			}

			if(get_option(self::DISMISSED_WELCOME_MESSAGE_META_KEY)) {
				return;
			}

			include(EWB_DIR.'/includes/admin/views/welcome-message.php');

		}

		/**
		 * scripts function
		 *
		 * @version 1.0
		 * @since   1.0
		 */
		public function scripts() {

			wp_register_script('ewb-admin', EWB_URL.'/assets/js/admin.min.js', ['elementor-admin'], true);

			wp_localize_script('ewb-admin', 'EWBConfig', [
				'ajaxurl' => admin_url('admin-ajax.php'),
				'hide_welcome_notice_nonce' => wp_create_nonce('hide_welcome_notice_nonce'),
				'categories' => \Elementor\Plugin::instance()->elements_manager->get_categories()
			]);

			wp_enqueue_script('ewb-admin');

		}

		/**
		 * styles function
		 *
		 * @version 1.0
		 * @since   1.0
		 */
		public function styles() {

			wp_register_style('ewb-admin-css', EWB_URL.'/assets/css/admin.min.css', [], EWB_VERSION);

			wp_enqueue_style('ewb-admin-css');

		}

		/**
		 * hide_welcome_message function
		 *
		 * @version 1.0
		 * @since   1.0
		 */
		public function hide_welcome_message() {

			if(!current_user_can('edit_posts') || !wp_verify_nonce($_REQUEST['nonce'], 'hide_welcome_notice_nonce')) {
				die('Cheatin&#8217; uh?');
			}

			// Updates that we have dismissed this message
			update_option(self::DISMISSED_WELCOME_MESSAGE_META_KEY, true, false);

			// Success
			wp_send_json_success([]);

		}

		/**
		 * action_links function
		 *
		 * @version 1.0
		 * @since   1.0
		 *
		 * @param $actions
		 *
		 * @return array
		 */
		public function action_links($actions) {

			$actions[] = sprintf('<a href="'.admin_url('edit.php?post_type=elementor_library&tabs_group=ewb-widget&elementor_library_type=ewb-widget').'" target="_blank">%s</a>', __('Custom Widgets', 'ewb'));

			return $actions;

		}

		/**
		 * plugin_meta function
		 *
		 * @version 1.0
		 * @since   1.0
		 *
		 * @param $meta
		 * @param $file
		 * @param $data
		 * @param $status
		 *
		 * @return mixed
		 */
		public function plugin_meta($meta, $file, $data, $status) {

			if($file === 'widget-builder-for-elementor/elebuilder.php') {

				$meta[] = '<a href="https://docs.elebuilder.com/" target="_blank">Help &amp; Docs</a>';

			}

			return $meta;

		}

	}