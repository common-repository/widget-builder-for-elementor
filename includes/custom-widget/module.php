<?php

	namespace EWB\Modules\Custom_Widget;

	use Elementor\Controls_Manager;
	use Elementor\Plugin as Elementor_Plugin;
	use Elementor\TemplateLibrary\Source_Local;

	if(!defined('ABSPATH') || !defined('WPINC')) { exit; }

	/**
	 * Custom widget module
	 *
	 * @version 1.0
	 * @since   1.0
	 * @package EWB\Modules\Custom_Widget
	 */
	class Module extends \Elementor\Core\Base\Module {

		const DOCUMENT_TYPE = 'ewb-widget';

		public $current_class_name;

		/**
		 * Module name
		 *
		 * @version 1.0
		 * @since   1.0
		 * @return string
		 */
		public function get_name() {
			return 'ewb-widget';
		}

		/**
		 * Module constructor.
		 */
		public function __construct() {

			// Register document
			add_action('elementor/documents/register', [$this, 'register_documents']);

			// Location
			add_action('elementor/theme/register_locations', [$this, 'register_location']);

			// AJAX Aactions
			add_action('elementor/ajax/register_actions', [$this, 'register_ajax_actions']);

			// Ensutes we show up on menu
			add_action('admin_menu', [$this, 'admin_menu']);
			add_action('admin_menu', [$this, 'current_admin_menu'], 100);

			// Template fields
			// TODO REMOVE THIS
			add_action('admin_footer', [$this, 'print_new_template_fields']);

			// TODO EXTRA FIELD IN DIALOG REMOVE THIS
			add_action('elementor/template-library/create_new_dialog_fields', [$this, 'print_widget_fields'], 20);

			// TODO Move this to source
			add_action('delete_post', [$this, 'delete_post']);
			add_action('save_post', [$this, 'save_post']);

			// Adds out document preferences in editor
			// TODO Move this to document maybe?
			add_action('elementor/element/editor-preferences/preferences/after_section_end', [$this, 'builder_preferences']);

			// Register source
			Elementor_Plugin::instance()->templates_manager->register_source('\EWB\Modules\Custom_Widget\Source_EWB');

			// Registers custom widgets
			add_action('elementor/widgets/widgets_registered', [$this, 'register_widgets']);

			add_action( 'elementor/template-library/after_save_template', [$this, 'maybe_import_ewb_control_data'], 10, 2);

		}

		/**
		 * Registers our document type
		 *
		 * @version 1.0
		 * @since   1.0
		 *
		 * @param $document_manager
		 */
		public function register_documents($document_manager) {
			$document_manager->register_document_type(self::DOCUMENT_TYPE, '\EWB\Modules\Custom_Widget\Document');
		}

		/**
		 * Registers our theme location function
		 *
		 * @version 1.0
		 * @since   1.0
		 *
		 * @param $location_manager
		 */
		public function register_location($location_manager) {
			$location_manager->register_location('ewb-widget', [
				'label' => __('Custom Widget', 'ewb'),
				'multiple' => true,
				'public' => false,
				'edit_in_content' => false
			]);
		}

		/**
		 * print_widgets function
		 *
		 * @version 1.0
		 * @since   1.0
		 */
		public function print_widgets() {
			elementor_theme_do_location( 'ewb-widget' );
		}

		/**
		 * admin_menu function
		 *
		 * @version 1.0
		 * @since   1.0
		 */
		public function admin_menu() {
			add_submenu_page(Source_Local::ADMIN_MENU_SLUG, '', __('Custom Widgets', 'ewb'), 'publish_posts', $this->get_admin_url(true));
		}

		/**
		 * current_admin_menu function
		 *
		 * @version 1.0
		 * @since   1.0
		 */
		public function current_admin_menu() {

			global $submenu, $pagenow;

			if($pagenow == 'edit.php' && !empty($_GET['post_type']) && $_GET['post_type'] == 'elementor_library' && !empty($_GET['tabs_group']) && $_GET['tabs_group'] == 'ewb-widget' && !empty($submenu['edit.php?post_type=elementor_library'])) {

				foreach($submenu['edit.php?post_type=elementor_library'] as $key => $value) {

					if(strpos($value[2], 'edit.php?post_type=elementor_library&tabs_group=ewb-widget') !== false) {
						$value[4] = 'current';
					}

					$submenu['edit.php?post_type=elementor_library'][$key] = $value;
				}

			}

		}

		/**
		 * register_ajax_actions function
		 *
		 * @version 1.0
		 * @since   1.0
		 *
		 * @param $ajax
		 */
		public function register_ajax_actions($ajax) {

			$ajax->register_ajax_action('ewb_save_controls_data', [$this, 'ajax_save_controls_data']);
			$ajax->register_ajax_action('ewb_get_widget_for_elements_panel', [$this, 'get_widget_for_elements_panel']);
			$ajax->register_ajax_action('ewb_get_widgets_config', [$this, 'ewb_get_widgets_config']);

		}

		/**
		 * ajax_save_controls_data function
		 *
		 * @version 1.0
		 * @since   1.0
		 *
		 * @param $data
		 */
		public function ajax_save_controls_data($data) {

			$document = Elementor_Plugin::instance()->documents->get($data['editor_post_id']);

			$document->save_control_settings_data(apply_filters('ewb/module/save_controls_data', $data['controls']));

		}

		/**
		 * Get widget config function
		 *
		 * @version 1.0
		 * @since   1.0
		 *
		 * @param $data
		 *
		 * @return array
		 */
		public function ewb_get_widgets_config($data) {

			$config = [];

			foreach(EWB()->get_registered_custom_widgets() as $document) {

				$widgetClass = $document->get_widget_class_name();

				$widget = new $widgetClass();

				$config[$widget->get_name()] = [

					'controls' => $widget->get_stack(false)['controls'],
					'tabs_controls' => $widget->get_tabs_controls(),
					'type' => $widget->get_type(),
					'icon' => $widget->get_icon(),
					'elType' => $widget->get_type(),
					'categories' => $widget->get_categories(),
					'keywords' => $widget->get_keywords(),
					'widget_type' => $widget->get_name(),
					'widgetType' => $widget->get_name(),
					'title' => $widget->get_title(),
					'custom' => '',
					'name' => $widget->get_name(),
					'help_url' => 'https://elebuilder.com/docs/',
					'reload_preview' => false,
					'show_in_panel' => true,
					'html_wrapper_class' => $widget->_get_html_wrapper_class(),

				];

			}

			return $config;
		}

		/**
		 * get_widget_for_elements_panel function
		 *
		 * @version 1.0
		 * @since   1.0
		 *
		 * @param $data
		 *
		 * @return array
		 * @throws \Exception
		 */
		public function get_widget_for_elements_panel($data) {

			$document = Elementor_Plugin::instance()->documents->get($data['widget_id']);

			if(!$document) {
				throw new \Exception('Could not instantiate widget document');
			}

			if($document->ensure_class_exists()) {

				$classname = $document->get_widget_class_name();

				$widget = new $classname();

				return $widget->get_config_for_editor_panel();

			}

			throw new \Exception('Could load widget class.');

		}

		/**
		 * Gets the url of custom widgets in the admin
		 *
		 * @version 1.0
		 * @since   1.0
		 *
		 * @param bool $relative
		 *
		 * @return string
		 */
		private function get_admin_url($relative = false) {

			// base url
			$baseurl = admin_url('edit.php');

			// if reltive
			if($relative) {
				$baseurl = 'edit.php';
			}

			return add_query_arg([
				'post_type' => 'elementor_library',
				'tabs_group' => 'ewb-widget',
				'elementor_library_type' => 'ewb-widget',
			], $baseurl);
		}

		/**
		 * print_widget_icon_field function
		 *
		 * @version 1.0
		 * @since   1.0
		 */
		public function print_widget_fields() {
			?>
			<div id="elementor-new-template__form__ewb-widget-category__wrapper" class="elementor-form-field"></div>
			<?php
		}



		/**
		 * print_templates function
		 *
		 * @version 1.0
		 * @since   1.0
		 */
		public function print_new_template_fields() {
			?>
			<script type="text/template" id="tmpl_elementor_new_template_widget_category">
				<label><?php _e('Select a category for your custom widget', 'ewb') ?></label>
				<div class="elementor-form-field__widget-category__wrapper"></div>
			</script>
			<?php
		}

		/**
		 * On delete post  function
		 *
		 * @version 1.0
		 * @since   1.0
		 *
		 * @param $post_id
		 */
		public function delete_post($post_id) {

			// TODO Move this to source, not module

			// Elementor library posts only
			if('elementor_library' !== get_post_type($post_id)) {
				return;
			}

			// Gets document
			$document = Elementor_Plugin::$instance->documents->get($post_id);

			// Is it a ewb document?
			if($document->get_name() === 'ewb-widget') {
				$document->delete_widget_class();
			}
		}

		/**
		 * save function
		 *
		 * @version 1.0
		 * @since   1.0
		 *
		 * @param $post_id
		 */
		public function save_post($post_id) {

			// TODO move this to source, not module

			// ELementor library oinly
			if('elementor_library' !== get_post_type($post_id)) {
				return;
			}

			// Get doc
			$document = Elementor_Plugin::$instance->documents->get($post_id);

			// Is it a EWB doc?
			if($document->get_name() === 'ewb-widget') {
				$document->delete_widget_class();
				$document->create_widget_class();
			}

		}

		/**
		 * builder_preferences function
		 *
		 * @version 1.0
		 * @since   1.0
		 *
		 * @param \Elementor\Core\Settings\EditorPreferences\Model $model
		 */
		public function builder_preferences($model) {

			$model->start_controls_section('ewb_preferences', [
				'tab' => Controls_Manager::TAB_SETTINGS,
				'label' => 'Widget Builder Preferences',
			]);

			$model->add_control('ewb_sections_gap', [
				'label' => 'Section Default Column Gap Setting',
				'type' => Controls_Manager::SELECT,
				'default' => 'no',
				'label_block' => true,
				'description' => 'Set the default column gap setting for sections when editing or converting custom widgets.  This setting only applies if you are editing or converting a custom widget template.',
				'options' => [
					'default' => 'Default',
					'no' => 'No Gap',
					'narrow' => 'Narrow',
					'extended' => 'Extended',
					'wide' => 'Wide',
					'wider' => 'Wider',
				],
			]);

			$model->add_control('ewb_sections_full_width', [
				'label' => 'Default Full Width Sections',
				'type' => Controls_Manager::SWITCHER,
				'default' => 'yes',
				'description' => 'Set sections width to full width when editing or converting custom widgets. This setting only applies if you are editing or converting a custom widget template.',
			]);

			$model->add_control('ewb_elements_manager_data', [
				'type' => Controls_Manager::HIDDEN,
				'default' => array(
					'elements' => [],
					'categories' => [],
				)
			]);

			$model->end_controls_section();

		}

		/**
		 * register_widgets function
		 *
		 * @version 1.0
		 * @since   1.0
		 */
		public function register_widgets() {

			// Gets widgets
			foreach(EWB()->get_registered_custom_widgets() as $document) {

				// Class of this widget
				$classname = $document->get_widget_class_name();

				// Ensures it exists prior to registering
				if($document->ensure_class_exists() && class_exists($classname)) {
					Elementor_Plugin::instance()->widgets_manager->register_widget_type(new $classname());
				}
			}

		}

		/**
		 * post_states function
		 *
		 * @version 1.0
		 * @since   1.0
		 *
		 * @param $states
		 *
		 * @param $post
		 *
		 * @return mixed
		 */
		public function post_states($states, $post) {

			if('elementor_library' === $post->post_type) {

				if(get_post_meta($post->ID, '_is_ewb_skin', true) === 'yes') {

					$states['ewb_skin'] = "Skin";

				}

			}

			return $states;

		}

		/**
		 * maybe_import_ewb_control_data function
		 *
		 * @version 1.0.1
		 * @since   1.0.1
		 *
		 * @param $template_id
		 * @param $template_data
		 */
		public function maybe_import_ewb_control_data($template_id, $template_data) {

			$document = \Elementor\Plugin::$instance->documents->get($template_id);

			if(!empty($template_data['page_settings']['_template_data'])) {
				$document->save([
					'elements' => $template_data['page_settings']['_template_data'],
				]);
			}

			if(!empty($template_data['page_settings']['_ewb_controls'])) {
				update_post_meta($template_id, '_elementor_ewb_control_settings', $template_data['page_settings']['_ewb_controls']);
			}

			if(!empty($template_data['page_settings']['ewb_plugin_ver'])) {
				update_post_meta($template_id, '_ewb_plugin_version', $template_data['page_settings']['ewb_plugin_ver']);
			}
			
		}

	}