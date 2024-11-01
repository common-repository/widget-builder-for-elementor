<?php

	namespace EWB\Modules\Custom_Widget;

	use Elementor\Core\Settings\Manager as SettingsManager;
	use Elementor\DB;
	use Elementor\Plugin;

	if(!defined('ABSPATH') || !defined('WPINC')) { exit; }

	class Source_EWB extends \Elementor\TemplateLibrary\Source_Local {

		/**
		 * get_id function
		 *
		 * @version 1.0
		 * @since   1.0
		 * @return string
		 */
		public function get_id() {
			return 'ewb-widget';
		}

		/**
		 * get_title function
		 *
		 * @version 1.0
		 * @since   1.0
		 * @return string
		 */
		public function get_title() {
			return 'Custom Widget';
		}

		/**
		 * register_data function
		 *
		 * @version 1.0
		 * @since   1.0
		 */
		public function register_data() {
		}

		/**
		 * get_items function
		 *
		 * @version 1.0
		 * @since   1.0
		 *
		 * @param array $args
		 *
		 * @return array
		 */
		public function get_items($args = []) {
			$q = new \WP_Query([
				'post_type'   => 'elementor_library',
				'post_status' => 'publish',
				'nopaging'    => true,
				'tax_query'   => [
					[
						'taxonomy' => 'elementor_library_type',
						'terms'    => ['ewb-widget'],
						'value'    => 'slug',
						'compare'  => 'IN',
					]
				],
				'fields'      => 'ids',
			]);
			$templates = [];
			foreach($q->posts as $post_id) {
				$templates[$post_id] = $this->get_item($post_id);
			}
			return $templates;
		}

		/**
		 * get_item function
		 *
		 * @version 1.0
		 * @since   1.0
		 *
		 * @param $template_id
		 *
		 * @return bool|mixed
		 */
		public function get_item($template_id) {
			$data = parent::get_item($template_id);
			return $data;
		}

		/**
		 * admin_title function
		 *
		 * @version 1.0
		 * @since   1.0
		 *
		 * @param $admin_title
		 * @param $title
		 *
		 * @return string|string[]
		 */
		public function admin_title($admin_title, $title) {

			return $this->get_library_title($admin_title);

		}

		/**
		 * save_item function
		 *
		 * @version 1.0
		 * @since   1.0
		 *
		 * @param $template_data
		 *
		 * @return mixed
		 */
		public function save_item($template_data) {
			return parent::save_item($template_data);
		}

		/**
		 * update_item function
		 *
		 * @version 1.0
		 * @since   1.0
		 *
		 * @param $new_data
		 *
		 * @return mixed
		 */
		public function update_item($new_data) {
			return parent::update_item($new_data);
		}

		/**
		 * Source_EWB constructor.
		 */
		public function __construct() {

			add_filter('admin_title', [$this, 'admin_title'], 20, 2);
			add_action('all_admin_notices', [$this, 'replace_admin_heading']);
			add_filter('post_row_actions', [$this, 'post_row_actions'], 20, 2);
			add_action('save_post', [$this, 'on_save_post'], 20, 2);

			add_filter('manage_' . self::CPT . '_posts_columns', [$this, 'admin_columns_headers'], 20);

		}

		/**
		 * admin_columns_headers function
		 *
		 * @version 1.0
		 * @since   1.0
		 *
		 * @param $posts_columns
		 *
		 * @return array|void
		 */
		public function admin_columns_headers($posts_columns) {

			if($this->is_current_screen() && $this->get_current_tab_group() === 'ewb-widget') {
				foreach(['instances', 'taxonomy-elementor_library_category', 'shortcode', 'elementor_library_type'] as $remove) {
					if(isset($posts_columns[$remove])) {
						unset($posts_columns[$remove]);
					}
				}
			}

			return $posts_columns;
		}

		/**
		 * Make sure that when we save settings such as category or skin, that we save the options BEFORE generating the widget class
		 *
		 * @version 1.0.1
		 * @since   1.0
		 *
		 * @param int      $post_id
		 * @param \WP_Post $post
		 */
		public function on_save_post($post_id, \WP_Post $post) {

			if(self::CPT !== $post->post_type || self::get_template_type($post_id) !== 'ewb-widget') {
				return;
			}

			$document = Plugin::instance()->documents->get($post_id);

			if($document->is_skin()) {
				update_post_meta($document->get_id(), '_is_ewb_skin', 'yes');
			} else {
				update_post_meta($document->get_id(), '_is_ewb_skin', '');
			}

			// Selected widget category
			$category = empty($_REQUEST['post_data']) || empty($_REQUEST['post_data']['ewb_widget_category']) ? false : $_REQUEST['post_data']['ewb_widget_category'];

			if(wp_doing_ajax() && !empty($_REQUEST['action']) && $_REQUEST['action'] == 'elementor_ajax') {

				try {

					$actions = json_decode(stripslashes($_REQUEST['actions']), true);

					foreach($actions as $action_type => $action) {
						if($action['action'] === 'save_builder' && isset($action['data']['settings']['ewb_widget_category'])) {
							$category = $action['data']['settings']['ewb_widget_category'];
						}
					}

				} catch (\Exception $e) {

					$actions = null;

				}
			}

			if($category !== false) {

				$document->set_widget_category($category);
				$document->set_settings('ewb_widget_category', $category);
				$document->delete_widget_class();
				$document->create_widget_class();

			}

		}

		/**
		 * post_row_actions function
		 *
		 * @version 1.0
		 * @since   1.0
		 *
		 * @param array   $actions
		 * @param WP_Post $post
		 *
		 * @return array|void
		 */
		public function post_row_actions($actions, \WP_Post $post) {

			if(!$this->is_current_screen() || $this->get_current_tab_group() !== 'ewb-widget') {
				return $actions;
			}

			foreach(['inline hide-if-no-js', 'view', 'edit'] as $remove) {
				if(!empty($actions[$remove])) {
					unset($actions[$remove]);
				}
			}

			if(!empty($actions['export-template'])) {
				$actions['export-template'] = sprintf('<a href="%1$s">%2$s</a>', $this->get_export_link($post->ID), "Export Widget");
			}

			return $actions;
		}

		/**
		 * get_export_link function
		 *
		 * @version 1.0
		 * @since   1.0
		 *
		 * @param int $template_id
		 *
		 * @return string
		 */
		private function get_export_link($template_id) {

			return add_query_arg(
				[
					'action'         => 'elementor_library_direct_actions',
					'library_action' => 'export_template',
					'source'         => $this->get_id(),
					'_nonce'         => \Elementor\Plugin::$instance->common->get_component('ajax')->create_nonce(),
					'template_id'    => $template_id,
				],
				admin_url('admin-ajax.php')
			);
		}

		/**
		 * replace_admin_heading function
		 *
		 * @version 1.0
		 * @since   1.0
		 * @return string|void
		 */
		public function replace_admin_heading() {
			global $post_type_object;

			if($this->is_current_screen() && $this->get_current_tab_group() === 'ewb-widget') {
				$post_type_object->labels->name = 'Custom Widgets';
			}
		}

		/**
		 * get_data function
		 *
		 * @version 1.0.1
		 * @since   1.0.1
		 *
		 * @param array $args
		 *
		 * @return array
		 */
		public function get_data(array $args) {

			$db = \Elementor\Plugin::$instance->db;

			$template_id = $args['template_id'];

			if(!empty($args['display'])) {
				$content = $db->get_builder($template_id);
			} else {
				$document = \Elementor\Plugin::$instance->documents->get($template_id);
				$content = $document ? $document->get_elements_data() : [];
			}

			$return = [
				'content' => $content
			];

			if(!empty($args['with_page_settings'])) {
				$page = SettingsManager::get_settings_managers('page')->get_model($args['template_id']);
				$return['page_settings'] = $page->get_data('settings');
			}

			return $return;

		}

		/**
		 * prepare_template_export function
		 *
		 * @version 1.0.1
		 * @since   1.0
		 *
		 * @param $template_id
		 *
		 * @return array|\WP_Error
		 */
		private function prepare_template_export($template_id) {

			$template_data = $this->get_data([
				'template_id' => $template_id,
			]);

			if(empty($template_data['content'])) {
				return new \WP_Error('empty_template', 'The template is empty');
			}

			if(get_post_meta($template_id, '_elementor_page_settings', true)) {
				$page = SettingsManager::get_settings_managers('page')->get_model($template_id);

				$page_settings_data = $this->process_element_export_import_content($page, 'on_export');

				if(!empty($page_settings_data['settings'])) {
					$template_data['page_settings'] = $page_settings_data['settings'];
				}
			}

			$template_data['page_settings']['_template_data'] = $template_data['content'];
			$template_data['page_settings']['ewb_plugin_ver'] = get_post_meta($template_id, '_ewb_plugin_version', true);

			$export_data = [
				'version'  => DB::DB_VERSION,
				'title'    => get_the_title($template_id),
				'type'     => self::get_template_type($template_id),
				'content' => $template_data['content'],
				'page_settings' => $template_data['page_settings'],
			];

			return [
				'name'    => 'elebuilder-custom-widget-' . $template_id . '-' . gmdate('Y-m-d') . '.json',
				'content' => wp_json_encode($export_data),
			];
		}

		/**
		 * send_file_headers function
		 *
		 * @version 1.0.1
		 * @since   1.0.1
		 *
		 * @param string $file_name
		 * @param int    $file_size
		 */
		private function send_file_headers($file_name, $file_size) {
			header( 'Content-Type: application/octet-stream' );
			header( 'Content-Disposition: attachment; filename=' . $file_name );
			header( 'Expires: 0' );
			header( 'Cache-Control: must-revalidate' );
			header( 'Pragma: public' );
			header( 'Content-Length: ' . $file_size );
		}

		/**
		 * export_template function
		 *
		 * @version 1.0.1
		 * @since   1.0.1
		 *
		 * @param int $template_id
		 *
		 * @return \WP_Error
		 */
		public function export_template($template_id) {

			$file_data = $this->prepare_template_export($template_id);

			if(is_wp_error($file_data)) {
				return $file_data;
			}

			$this->send_file_headers($file_data['name'], strlen($file_data['content']));

			@ob_end_clean();

			flush();

			echo $file_data['content'];

		}

		/**
		 * get_library_title function
		 *
		 * @version 1.0
		 * @since   1.0
		 * @return mixed|string
		 */
		private function get_library_title($title) {

			if($this->is_current_screen() && $this->get_current_tab_group() === 'ewb-widget') {
				$title = 'Custom Widgets';
			}

			return $title;

		}

		/**
		 * is_current_screen function
		 *
		 * @version 1.0
		 * @since   1.0
		 * @return bool
		 */
		private function is_current_screen() {

			$screen = get_current_screen();

			return $screen->post_type === 'elementor_library' && ((isset($_GET['tabs_group']) && $_GET['tabs_group'] === 'ewb-widget') || (isset($_GET['elementor_library_type']) && $_GET['elementor_library_type'] === 'ewb-widget'));

		}

		/**
		 * get_current_tab_group function
		 *
		 * @version 1.0
		 * @since   1.0
		 *
		 * @param string $default
		 *
		 * @return mixed|string
		 */
		private function get_current_tab_group($default = '') {

			$current_tabs_group = $default;
			$tabs_group = empty($_REQUEST['tabs_group']) || !is_string($_REQUEST['tabs_group']) ? "" : $_REQUEST['tabs_group'];
			$type_slug = empty($_REQUEST[self::TAXONOMY_TYPE_SLUG]) || !is_string($_REQUEST[self::TAXONOMY_TYPE_SLUG]) ? "" : $_REQUEST[self::TAXONOMY_TYPE_SLUG];

			if(!empty($type_slug)) {

				$doc_type = Plugin::$instance->documents->get_document_type($type_slug, '');

				if($doc_type) {
					$current_tabs_group = $doc_type::get_property('admin_tab_group');
				}

			} elseif(!empty($tabs_group)) {

				$current_tabs_group = $tabs_group;

			}

			return $current_tabs_group;

		}

	}
