<?php

	namespace EWB\Modules\Custom_Widget;

	use Elementor\Controls_Manager;
	use Elementor\DB;
	use Elementor\Modules\Library\Documents\Library_Document;
	use Elementor\Plugin;

	if (!defined('ABSPATH') || !defined('WPINC')) {
		exit;
	}

	/**
	 * Class Document
	 *
	 * @version 1.0
	 * @since   1.0
	 * @package EWB\Modules\Custom_Widget
	 */
	class Document extends Library_Document {

		const LOCATION_META_KEY = '_elementor_location';

		const CONTROL_SETTINGS_META_KEY = '_elementor_ewb_control_settings';

		const ICON_META_KEY = '_elementor_ewb_widget_icon';

		const REMOTE_WIDGET_ICON_REQUEST_KEY = 'elementor-new-template__form__ewb-widget-icon';

		const REMOTE_WIDGET_CATEGORY_REQUEST_KEY = 'elementor-new-template__form__ewb-widget-category';

		const WIDGET_CLASS_BASE_PATH = '/includes/custom-widget/classes';

		const REMOVE_WIDGET_ICON_CUSTOM_SVG_REQUEST_KEY = 'elementor-new-template__form__ewb-widget-icon-custom-svg';

		const ICON_CUSTOM_SVG_META_KEY = '_elementor_ewb_widget_custom_svg_icon';

		const DOCUMENT_PLUGIN_VERSION = '_ewb_plugin_version';

		private $widget_category;

		/**
		 * Get document properties.
		 *
		 * Retrieve the document properties.
		 *
		 * @since 1.0
		 * @access public
		 * @static
		 *
		 * @return array Document properties.
		 */
		public static function get_properties() {

			$properties = parent::get_properties();

			$properties['admin_tab_group'] = 'ewb-widget';
			$properties['location'] = 'ewb-widget';
			$properties['register_type'] = true;

			return $properties;
		}

		/**
		 * get_preview_as_default function
		 *
		 * @version 1.0
		 * @since   1.0
		 * @return string
		 */
		public static function get_preview_as_default() {
			return '';
		}

		/**
		 * Get element title.
		 *
		 * Retrieve the element title.
		 *
		 * @since 1.0
		 * @access public
		 * @static
		 *
		 * @return string Element title.
		 */
		public static function get_title() {
			return __( 'Custom Widget', 'ewb' );
		}

		/**
		 * _get_initial_config function
		 *
		 * @version 1.0
		 * @since   1.0
		 * @return array|void
		 */
		public function _get_initial_config() {
			$config = parent::get_initial_config();
			$config['widgetSettings'] = $this->get_widget_settings();
			return $config;
		}

		/**
		 * get_init_settings_  function
		 * *
		 * TODO Whys is this an alias??
		 *
		 * @version 1.0
		 * @since   1.0
		 * @return array|mixed
		 */
		public function get_init_settings_() {
			return $this->get_init_settings();
		}

		/**
		 * get_widget_settings function
		 *
		 * @version 1.0
		 * @since   1.0
		 * @return array
		 */
		public function get_widget_settings() {
			return [
				'controls' => $this->get_control_settings_data(),
				'tabs' => $this->get_available_tabs(),
				'widgetType' => $this->get_widget_type(),
			];
		}

		/**
		 * get_widget_type function
		 *
		 * @version 1.0
		 * @since   1.0
		 * @return string
		 */
		public function get_widget_type() {
			return sprintf('ewb-widget-%s', $this->get_base_name());
		}

		/**
		 * get_available_tabs function
		 *
		 * @version 1.0
		 * @since   1.0
		 * @return array
		 */
		public function get_available_tabs() {
			$tabs = [
				[
					'label' => 'Content',
					'icon' => 'eicon-pencil',
					'type' => \Elementor\Controls_Manager::TAB_CONTENT,
				],
				[
					'label' => 'Style',
					'icon' => 'eicon-adjust',
					'type' => \Elementor\Controls_Manager::TAB_STYLE,
				],
				[
					'label' => 'Advanced',
					'icon' => 'eicon-cog',
					'type' => \Elementor\Controls_Manager::TAB_ADVANCED,
				],
				[
					'label' => 'Responsive',
					'icon' => 'eicon-device-desktop',
					'type' => \Elementor\Controls_Manager::TAB_RESPONSIVE,
				],
				[
					'label' => 'Layout',
					'icon' => 'eicon-column',
					'type' => \Elementor\Controls_Manager::TAB_LAYOUT,
				]
			];
		}

		/**
		 * Get element name.
		 *
		 * Retrieve the element name.
		 *
		 * @since 1.0
		 * @access public
		 *
		 * @return string The name.
		 */
		public function get_name() {
			return 'ewb-widget';
		}

		/**
		 * @since 1.0
		 * @access public
		 */
		public function get_css_wrapper_selector() {
			return '#elementor-ewb-widget-'.$this->get_main_id();
		}

		/**
		 * Returns our control data prganised hierarhically
		 *
		 * @version 1.0
		 * @since   1.0
		 * @return array|mixed
		 */
		public function get_control_settings_data() {

			if(($cache = wp_cache_get('ewb_doc_control_data_'.$this->get_id())) !== false) {
				return $cache;
			}

			$_controls = $this->get_main_meta(self::CONTROL_SETTINGS_META_KEY);

			if(empty($_controls)) {
				return [];
			}

			$data = $_controls;

			foreach($_controls as $tab_key => $tab) {

				if(!empty($tab['sections'])) {

					foreach($tab['sections'] as $section_key => $section) {

						if(!empty($section['controls'])) {

							foreach($section['controls'] as $control_key => $control) {

								$data[$tab_key]['sections'][$section_key]['controls'][$control_key] = $this->fetch_updated_control($control);

							}

						}

					}

				}

			}

			$data = apply_filters('ewb/document/control_data', $data, $_controls, $this);

			wp_cache_set('ewb_doc_control_data_'.$this->get_id(), $data);

			return $data;

		}

		/**
		 * fetch_updated_control function
		 *
		 * @version 1.0
		 * @since   1.0
		 *
		 * @param $control
		 *
		 * @return mixed
		 */
		private function fetch_updated_control($control) {

			if(!empty($control['settings']) && isset($control['settings']['options']) && !empty($control['replaces'])) {

				$control_options = [];

				foreach($control['replaces'] as $replacement) {

					if(empty($replacement['control']))
						continue;

					$options = $this->get_widget_control_options($replacement['widgetType'], $replacement['control'], $replacement['type']);

					if(!empty($options)) {

						foreach($options as $value => $label) {

							if(!isset($control_options[$value])) {

								$control_options[$value] = $label;

							}

						}

					}

				}

				$control['settings']['options'] = $control_options;

			}

			return $control;

		}

		/**
		 * get_widget_control_options function
		 *
		 * @version 1.0
		 * @since   1.0
		 *
		 * @param $widget_type
		 * @param $control_name
		 *
		 * @return array
		 */
		private function get_widget_control_options($widget_type, $control_name, $control_type) {

			if($control_type === 'column' || $control_type === 'section') {
				$widget_types = Plugin::instance()->elements_manager->get_element_types();
				$widget_type = $control_type;
			} else {
				$widget_types = Plugin::instance()->widgets_manager->get_widget_types();
			}

			if(empty($widget_types[$widget_type]))
				return [];

			// Widgets class name
			$class_name = get_class($widget_types[$widget_type]);

			if(empty($class_name) || !class_exists($class_name)) {
				return [];
			}

			// Inits object so we can grab control
			$widget_obj = new $class_name();

			$controls = $widget_obj->get_controls();

			if(empty($controls[$control_name]) || empty($controls[$control_name]['options'])) {
				return [];
			}

			return $controls[$control_name]['options'];

		}

		/**
		 * save_control_settings_data function
		 *
		 * @version 1.0
		 * @since   1.0
		 *
		 * @param $display_settings_data
		 */
		public function save_control_settings_data($control_settings_data) {
			$this->update_main_meta(self::CONTROL_SETTINGS_META_KEY, $control_settings_data);
			$this->update_main_meta(self::DOCUMENT_PLUGIN_VERSION, EWB_VERSION);
		}

		/**
		 * Get frontend settings.
		 *
		 * @since 1.0.0
		 * @access public
		 *
		 * @return array Frontend settings.
		 */
		public function get_frontend_settings() {
			$settings = parent::get_frontend_settings();
			return $settings;
		}

		/**
		 * get_section_name function
		 *
		 * @version 1.0
		 * @since   1.0
		 *
		 * @param $section_title
		 *
		 * @return string
		 */
		public function get_section_name($section_title) {

			if(!isset($this->section_names)) {
				$this->section_names = [];
			}

			$name = sanitize_title($section_title);
			$counter = 1;

			while(in_array($name, $this->section_names)) {
				$name = sanitize_title($section_title).'-'.($counter+1);
				$counter++;
			}

			return $name;
		}

		/**
		 * _register_controls function
		 *
		 * @version 1.0
		 * @since   1.0
		 */
		protected function _register_controls() {

			$this->start_controls_section(
				'ewb_widget_settings',
				[
					'label' => __('Custom Widget Settings', 'elementor-pro'),
					'tab' => Controls_Manager::TAB_SETTINGS,
				]
			);

			$this->add_control(
				'ewb_widget_icon',
				[
					'type' => Controls_Manager::ICONS,
					'label' => 'Icon',
					'label_block' => true,
					'default' => [
						'value' => 'fas fa-star',
						'library' => 'solid',
					],
				]
			);

			$this->add_control(
				'ewb_widget_category',
				[
					'type' => Controls_Manager::SELECT,
					'label' => 'Category',
					'label_block' => false,
					'default' => '',
					'separator' => 'after',
					'options' => wp_list_pluck(Plugin::instance()->elements_manager->get_categories(), 'title'),
				]
			);

			$this->add_control(
				'default_advanced_setttings',
				[
					'type' => \Elementor\Controls_Manager::SWITCHER,
					'label' => 'Default Advanced Controls',
					'label_on' => 'SHOW',
					'label_off' => 'HIDE',

					'default' => apply_filters('ewb/document/default/default_advanced_settings', ''),
					'description' => 'Whether or not to include the default elementor "Advanced" tab controls such as "Background", "Margin" and "Padding".',
				]
			);

			$this->add_control('_ewb_controls', [
				'type' => Controls_Manager::HIDDEN,
				'default' => [],
			]);

			$this->end_controls_section();

			parent::_register_controls();

		}

		/**
		 * get_location function
		 *
		 * @version 1.0
		 * @since   1.0
		 * @return mixed
		 */
		public function get_location() {
			$value = self::get_property( 'location' );

			if(!$value) {
				$value = $this->get_main_meta(self::LOCATION_META_KEY);
			}

			return $value;
		}

		/**
		 * Save document type.
		 *
		 * Set new/updated document type.
		 *
		 * @since 2.0.0
		 * @access public
		 */
		public function save_template_type() {
			parent::save_template_type();

			$widget_category = !empty($_REQUEST[self::REMOTE_WIDGET_CATEGORY_REQUEST_KEY]) ? (string)strtolower($_REQUEST[self::REMOTE_WIDGET_CATEGORY_REQUEST_KEY]) : false;

			if(!empty($widget_category)) {
				$this->set_settings('ewb_widget_category', $widget_category);
			}
		}

		/**
		 * get_base_name function
		 *
		 * @version 1.0
		 * @since   1.0
		 * @return false|string
		 */
		public function get_base_name() {
			$name = sprintf('%s-%d', sanitize_title(strtolower($this->get_widget_title())), $this->get_main_id());

			while(!preg_match('/^[a-z]/', $name)) {
				$name = substr($name, 1);
				if(strlen($name) <= 0) {
					$name = "custom-widget-".$this->get_main_id();
				}
			}

			return $name;
		}

		/**
		 * get_widget_class_name function
		 *
		 * @version 1.0
		 * @since   1.0
		 * @return string|string[]
		 */
		public function get_widget_class_name() {
			return str_replace(' ', '_', ucwords(str_replace('-', ' ', $this->get_base_name())));
		}

		/**
		 * get_skin_class_name function
		 *
		 * @version 1.0
		 * @since   1.0
		 * @return string|string[]
		 */
		public function get_skin_class_name() {
			return str_replace(' ', '_', ucwords(str_replace('-', ' ', $this->get_base_name().'-skin')));
		}

		/**
		 * get_widget_icon function
		 *
		 * @version 1.0
		 * @since   1.0
		 * @return mixed
		 */
		public function get_widget_icon() {

			$icon = $this->get_settings_for_display('ewb_widget_icon');

			if(empty($icon)) {
				$icon = 'fas fa-star';
			} else {
				$icon = $icon['value'];
			}

			return $icon;

		}

		/**
		 * has_custom_icon function
		 *
		 * @version 1.0
		 * @since   1.0
		 * @return bool
		 */
		public function has_custom_icon() {
			$svg = $this->get_main_meta(self::ICON_CUSTOM_SVG_META_KEY);
			if(empty($svg)) {
				return false;
			}
			return true;
		}

		/**
		 * get_custom_icon_class function
		 *
		 * @version 1.0
		 * @since   1.0
		 * @return string|string[]
		 */
		public function get_custom_icon_class() {
			return str_replace('ewb-svg-icon-'.'_', '-', strtolower($this->get_base_name()));
		}

		/**
		 * get_widget_class_filename function
		 *
		 * @version 1.0
		 * @since   1.0
		 * @return string
		 */
		private function get_widget_class_filename() {

			$this->get_widget_class_name();

			return str_replace('_', '-', strtolower($this->get_base_name())).'.php';
		}

		/**
		 * get_skin_class_filename function
		 *
		 * @version 1.0
		 * @since   1.0
		 * @return string
		 */
		private function get_skin_class_filename() {

			$this->get_skin_class_name();

			return str_replace('_', '-', strtolower($this->get_base_name().'-skin')).'.php';
		}

		/**
		 * get_widget_title function
		 *
		 * @version 1.0
		 * @since   1.0
		 * @return string
		 */
		public function get_widget_title() {
			return get_the_title($this->get_main_id());
		}

		/**
		 * replace_tags function
		 *
		 * @version 1.0
		 * @since   1.0
		 *
		 * @param $data
		 *
		 * @return string|string[]
		 */
		private function replace_tags($data) {

			$tags = [
				'CLASS_NAME' => '<?php echo $this->get_widget_class_name() ?>',
				"'DOC_ID'" => '<?php echo $this->get_main_id() ?>',
				"ELEMENT_TYPE" => '<?php echo $this->get_settings_for_display(\'element_type\') == \'section\' ? $this->get_widget_type() : \'widget\' ?>',
				'CLASS_NAME_SKIN' => '<?php echo $this->get_skin_class_name() ?>',
			];

			return str_replace(array_keys($tags), array_values($tags), $data);

		}

		/**
		 * create_widget_class function
		 *
		 * @version 1.0
		 * @since   1.0
		 */
		public function create_widget_class() {

			$filedata = $this->replace_tags(ltrim(file_get_contents(EWB_DIR.self::WIDGET_CLASS_BASE_PATH.'/base.php'), "<"));

			if(file_exists(EWB_DIR.self::WIDGET_CLASS_BASE_PATH.'/temp-class.php')) {
				unlink(EWB_DIR.self::WIDGET_CLASS_BASE_PATH.'/temp-class.php');
			}

			file_put_contents(EWB_DIR.self::WIDGET_CLASS_BASE_PATH.'/temp-class.php', $filedata);

			ob_start();

			if(file_exists(EWB_DIR.self::WIDGET_CLASS_BASE_PATH.'/temp-class.php')) {
				include EWB_DIR.self::WIDGET_CLASS_BASE_PATH.'/temp-class.php';
			}

			$classdata = ob_get_clean();

			file_put_contents($this->get_widget_class_filepath(), '<'.$classdata);
		}

		/**
		 * get_skin_temp_class_filepath function
		 *
		 * @version 1.0
		 * @since   1.0
		 * @return mixed|void
		 */
		public function get_skin_temp_class_filepath() {

			return apply_filters('ewb/skins/temp_class_filepath', EWB_DIR.self::WIDGET_CLASS_BASE_PATH.'/temp-class.php', $this);

		}

		/**
		 * create_skin_class function
		 *
		 * @version 1.0
		 * @since   1.0
		 */
		public function create_skin_class() {

			$filedata = $this->replace_tags(apply_filters('ewb/skins/base_class', '', $this));

			if(empty($filedata)) {
				return;
			}

			if(file_exists($this->get_skin_temp_class_filepath())) {
				unlink($this->get_skin_temp_class_filepath());
			}

			file_put_contents($this->get_skin_temp_class_filepath(), $filedata);

			ob_start();

			if(file_exists($this->get_skin_temp_class_filepath())) {
				include $this->get_skin_temp_class_filepath();
			}

			$classdata = ob_get_clean();

			file_put_contents($this->get_skin_class_filepath(), '<'.$classdata);

		}

		/**
		 * delete_widget_class function
		 *
		 * @version 1.0
		 * @since   1.0
		 */
		public function delete_widget_class() {
			if(file_exists($this->get_widget_class_filepath())) {
				unlink($this->get_widget_class_filepath());
			}
			if($this->is_skin() && file_exists($this->get_skin_class_filepath())) {
				unlink($this->get_skin_class_filepath());
			}
		}

		/**
		 * delete_widget_class function
		 *
		 * @version 1.0
		 * @since   1.0
		 */
		private function delete_skin_class() {
			if(file_exists($this->get_skin_class_filepath())) {
				unlink($this->get_skin_class_filepath());
			}
			if($this->is_skin() && file_exists($this->get_skin_class_filepath())) {
				unlink($this->get_skin_class_filepath());
			}
		}

		/**
		 * get_widget_class_filepath function
		 *
		 * @version 1.0
		 * @since   1.0
		 * @return string
		 */
		public function get_widget_class_filepath() {
			return EWB_DIR.self::WIDGET_CLASS_BASE_PATH.'/'.$this->get_widget_class_filename();
		}

		/**
		 * get_skin_class_filepath function
		 *
		 * @version 1.0
		 * @since   1.0
		 * @return string
		 */
		public function get_skin_class_filepath() {
			return apply_filters('ewb/skins/skin_class_filepath', EWB_DIR.self::WIDGET_CLASS_BASE_PATH.'/'.$this->get_skin_class_filename(), $this);
		}

		/**
		 * has_skin function
		 *
		 * @version 1.0
		 * @since   1.0
		 * @return bool
		 */
		public function has_skin() {

			if(!did_action('ewbpro_loaded')) {
				return false;
			}

			return $this->get_settings_for_display('_as_skin') === 'yes';
		}

		/**
		 * set_widget_category function
		 *
		 * @version 1.0
		 * @since   1.0
		 *
		 * @param $category
		 */
		public function set_widget_category($category) {
			$this->widget_category = $category;
		}

		/**
		 * get_widget_category function
		 *
		 * @version 1.0
		 * @since   1.0
		 * @return mixed|string
		 */
		public function get_widget_category() {

			if(!empty($this->widget_category)) {
				return $this->widget_category;
			}

			$category = $this->get_settings_for_display('ewb_widget_category');

			if(empty($category)) {
				return 'basic';
			}

			return $category;
		}

		/**
		 * is_skin function
		 *
		 * @version 1.0
		 * @since   1.0
		 * @return bool
		 */
		public function is_skin() {
			return $this->has_skin();
		}

		/**
		 * ensure_class_exists function
		 *
		 * @version 1.0
		 * @since   1.0
		 */
		public function ensure_class_exists() {

			if(!file_exists($this->get_widget_class_filepath())) {

				$this->create_widget_class();

				return $this->ensure_class_exists()
					;
			} elseif(!class_exists($this->get_widget_class_name())) {

				if(version_compare($this->get_main_meta(self::DOCUMENT_PLUGIN_VERSION), EWB_VERSION, '!=')) {

					$this->delete_widget_class();

					$this->update_main_meta(self::DOCUMENT_PLUGIN_VERSION, EWB_VERSION);

					$this->create_widget_class();

					return $this->ensure_class_exists();

				} else {

					include $this->get_widget_class_filepath();

				}

			}

			if($this->is_skin() && did_action('ewbpro_loaded')) {

				if(!file_exists($this->get_skin_class_filepath())) {

					$this->create_skin_class();

					return $this->ensure_class_exists();

				} else if(!class_exists($this->get_skin_class_name())) {

					if(version_compare($this->get_main_meta(self::DOCUMENT_PLUGIN_VERSION), EWB_VERSION, '!=')) {

						$this->delete_skin_class();

						$this->update_main_meta(self::DOCUMENT_PLUGIN_VERSION, EWB_VERSION);

						$this->create_skin_class();

						return $this->ensure_class_exists();

					} else {

						include $this->get_skin_class_filepath();

					}

				}

			}

			return true;

		}

		/**
		 * get_elements_data function
		 *
		 * @version 1.0
		 * @since   1.0
		 *
		 * @param string $status
		 *
		 * @return array
		 */
		public function get_elements_data($status = DB::STATUS_PUBLISH) {
			$data = parent::get_elements_data($status);

			return apply_filters('ewb/document/elements_data', $data, $this->get_id());
		}
	}
