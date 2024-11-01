<?php

	use Elementor\Controls_Manager;
	use Elementor\Plugin;
	use Elementor\Widget_Base;
	use Ewb\Cache;
	use EWB\Modules\Custom_Widget\Document;
	use EWB\Modules\Custom_Widget\Widget_CSS;
	use EWB\Modules\Custom_Widget\Widget_CSS_Preview;

	class CLASS_NAME extends Widget_Base {

		private $document_id = 'DOC_ID';

		protected $element_instance;

		protected $parsed_dynamic_settings;

		protected $is_rendering_skin = false;

		/**
		 * @var array
		 */
		private $section_names = [];

		private $current_tab_group = null;

		/**
		 * Get element name.
		 *
		 * Retrieve the element name.
		 *
		 * @since 1.4.0
		 * @access public
		 * @abstract
		 *
		 * @return string The name.
		 */
		public function get_name() {
			return '<?php echo $this->get_widget_type(); ?>';
		}

		/**
		 * Get element title.
		 *
		 * Retrieve the element title.
		 *
		 * @since 1.0.0
		 * @access public
		 *
		 * @return string Element title.
		 */
		public function get_title() {
			return '<?php echo addslashes($this->get_widget_title()) ?>';
		}

		/**
		 * Get widget icon.
		 *
		 * Retrieve the widget icon.
		 *
		 * @since 1.0.0
		 * @access public
		 *
		 * @return string Widget icon.
		 */
		public function get_icon() {
			return $this->get_document()->get_widget_icon();
		}

		/**
		 * Get widget categories.
		 *
		 * Retrieve the widget categories.
		 *
		 * @since 1.0.10
		 * @access public
		 *
		 * @return array Widget categories.
		 */
		public function get_categories() {
			return ['<?php echo $this->get_widget_category(); ?>'];
		}

		/**
		 * Get element type.
		 *
		 * Retrieve the element type, in this case `widget`.
		 *
		 * @since 1.0.0
		 * @access public
		 * @static
		 *
		 * @return string The type.
		 */
		public static function get_type() {
			return 'ELEMENT_TYPE';
		}


		/**
		 * filter_common_controls function
		 *
		 * @version 1.0
		 * @since   1.0
		 *
		 * @param $stack
		 *
		 * @return mixed
		 */
		protected function filter_common_controls($stack) {

			$common_widget = \Elementor\Plugin::$instance->widgets_manager->get_widget_types('common');

			// removes controls
			foreach($common_widget->get_controls() as $key => $value) {
				if(isset($stack['controls'][$key])) {
					unset($stack['controls'][$key]);
				}
			}

			if($stack['tabs'][Controls_Manager::TAB_ADVANCED]) {

				$has_advanced = false;

				// Checks empty tab
				foreach ($stack['controls'] as $control) {
					if (!empty($control['tab']) && $control['tab'] === Controls_Manager::TAB_ADVANCED) {
						$has_advanced = true;
						break;
					}
				}

				if (!$has_advanced) {
					unset($stack['tabs'][Controls_Manager::TAB_ADVANCED]);
				}

			}

			return $stack;

		}


		/**
		* Get stack.
		*
		* Retrieve the widget stack of controls.
		*
		* @since 1.9.2
		* @access public
		*
		* @param bool $with_common_controls Optional. Whether to include the common controls. Default is true.
		*
		* @return array Widget stack of controls.
		*/
		public function get_stack($with_common_controls = true) {

			$stack = parent::get_stack();

			if($this->get_document()->get_settings_for_display('element_type') == 'section' || $this->get_document()->get_settings_for_display('default_advanced_setttings') !== 'yes') {
				$stack = $this->filter_common_controls($stack);
			}

			if(isset($stack['controls'])) {
				foreach($stack['controls'] as $index => $control) {
					$stack['controls'][$index]['render_type'] = 'template';
				}
			}

			return $stack;
		}

		/**
		 * get_document function
		 *
		 * @version 1.0
		 * @since   1.0
		 * @return \Elementor\Core\Base\Document|false
		 */
		public function get_document() {

			if(!isset($this->document)) {
				$document = \Elementor\Plugin::instance()->documents->get($this->document_id);
				$this->document = $document;
			}

			return $this->document;

		}

		/**
		 * get_section_name function
		 *
		 * @version 1.0
		 * @since   1.0
		 *
		 * @param $label
		 *
		 * @return string
		 */
		private function get_section_name($label) {

			$namebase = 'section_'.str_pad(str_replace('-', '_', sanitize_title($label)), 8, 'section');
			$name = $namebase;

			$counter = 2;

			while(in_array($name, $this->section_names)) {
				$name = $namebase.'_'.$counter;
				$counter++;
			}

			$this->section_names[] = $name;

			return $name;

		}

		/**
		 * start_control_tabs_group function
		 *
		 * @version 1.0
		 * @since   1.0
		 *
		 * @param $control
		 */
		private function start_control_tabs_group($control) {

			$this->current_tab_group = $control;

			// Starts the actual tab
			$this->start_controls_tabs('tabs_'.$control['id']);

		}

		/**
		 * start_new_control_tab function
		 *
		 * @version 1.0
		 * @since   1.0
		 *
		 * @param $tab_id
		 *
		 * @return bool
		 */
		private function start_new_control_tab($tab_id) {

			if(!$this->current_tab_group) {
				return false;
			}

			// Does tab exist
			$tab = null;

			foreach($this->current_tab_group['settings']['tabs'] as $key => $_tab) {
				if($_tab['id'] === $tab_id) {
					$tab = $_tab;
					break;
				}
			}

			if(!$tab) {
				return false;
			}

			// Do we need to close a previous one?
			if($this->get_currently_active_tab()) {

				$this->end_controls_tab();
				$this->current_tab_group['settings']['tabs'][$this->get_currently_active_tab_index()]['currently_active'] = false;
			}

			$this->start_controls_tab('tab_'.$tab['id'], [
				'label' => $tab['label'],
			]);


			$this->set_currently_active_tab($tab['id']);

		}

		/**
		 * set_currently_active_tab function
		 *
		 * @version 1.0
		 * @since   1.0
		 *
		 * @param $id
		 *
		 * @return bool
		 */
		private function set_currently_active_tab($id) {

			if(empty($this->current_tab_group)) {
				return false;
			}

			foreach($this->current_tab_group['settings']['tabs'] as $key => $tab) {
				if($tab['id'] === $id) {
					$this->current_tab_group['settings']['tabs'][$key]['currently_active'] = true;
					break;
				}
			}

			return true;

		}

		/**
		 * end_control_tabs_group function
		 *
		 * @version 1.0
		 * @since   1.0
		 */
		private function end_control_tabs_group() {

			if(empty($this->current_tab_group)) {
				return;
			}

			if($this->get_currently_active_tab()) {
				$this->end_controls_tab();
			}

			$this->end_controls_tabs();
			$this->current_tab_group = null;

		}

		/**
		 * get_currently_active_tab function
		 *
		 * @version 1.0
		 * @since   1.0
		 * @return bool
		 */
		private function get_currently_active_tab() {
			if(empty($this->current_tab_group)) {
				return false;
			}

			foreach($this->current_tab_group['settings']['tabs'] as $tab) {
				if(!empty($tab['currently_active'])) {
					return $tab['id'];
				}
			}

			return false;
		}

		/**
		 * get_currently_active_tab_index function
		 *
		 * @version 1.0
		 * @since   1.0
		 * @return bool
		 */
		private function get_currently_active_tab_index() {
			if(empty($this->current_tab_group)) {
				return false;
			}

			foreach($this->current_tab_group['settings']['tabs'] as $index => $tab) {
				if(!empty($tab['currently_active'])) {
					return $index;
				}
			}

			return false;
		}

		/**
		 * _register_controls function
		 *
		 * @version 1.0
		 * @since   1.0
		 */
		public function _register_controls() {

			if(empty($this->get_document()->get_control_settings_data())) {

				$this->start_controls_section('section_no_controls', [
					'tab' => Controls_Manager::TAB_CONTENT,
					'label' => 'Empty',
				]);

				$this->add_control('no_controls_message', [
					'type' => Controls_Manager::RAW_HTML,
					'raw' => '<i class="ewb-icon-no-control"></i><h5>'.__('No Controls Found').'</h5><p>'.__('This widget does not have any controls added from its template. Start adding controls to view and edit them here.', 'ewb').'</p>',
				]);

				$this->end_controls_section();

			} else {

				foreach($this->get_document()->get_control_settings_data() as $tab) {

					foreach($tab['sections'] as $section) {

						$this->start_controls_section($this->get_section_name($section['label']), [
							'label' => $section['label'],
							'tab'   => $tab['name'],
						]);

						foreach($section['controls'] as $control) {

							// Tab Controls
							if(!empty($control['valueType']) && $control['valueType'] === 'control_tabs') {

								$this->start_control_tabs_group($control);

								continue;

							}

							if(!empty($control['settings']['control_tab']) && $control['settings']['control_tab'] !== $this->get_currently_active_tab()) {
								$this->start_new_control_tab($control['settings']['control_tab']);
							}

							if(!empty($this->current_tab_group) && empty($control['settings']['control_tab'])) {
								$this->end_control_tabs_group();
							}

							$control_options = $control['settings'];
							$control_options['label'] = $control['label'];
							$control_options['render_type'] = 'template';

							foreach(['tab', 'section', 'name', 'features', 'condition', 'conditions', 'inner_tab'] as $remove_field) {
								if(isset($control_options[$remove_field])) {
									unset($control_options[$remove_field]);
								}
							}

							// Gets elements ids from replacement values
							$element_ids = [];
							foreach($control['replaces'] as $replacement) {
								if(!in_array($replacement['id'], $element_ids)) {
									$element_ids[] = $replacement['id'];
								}
							}

							// Applies to our selectors
							// Ensures we select the widget wrapper to avoid conflicts with common controls
							if(!empty($control_options['selectors'])) {

								$selectors = [];

								foreach($control_options['selectors'] as $selector => $selector_value) {
									foreach($element_ids as $element_id) {
										$selectors[str_replace('{{WRAPPER}}', $this->get_custom_widget_wrapper_class() . ' {{WRAPPER}} .elementor-element-' . $element_id, $selector)] = $selector_value;
									}
								}
								$control_options['selectors'] = $selectors;
							}

							if(!empty($control_options['is_grouped'])) {

								$type = $control_options['grouped_type'];

								if(!empty($control_options['selector'])) {
									if(!empty($control['replaces']) && is_array($control['replaces'])) {
										foreach($control['replaces'] as $replacement) {
											if(!empty($replacement['selectors'])) {
												$control_options['selector'] = array_keys($replacement['selectors'])[0];
												break;
											}
										}
									}
								}

								if(empty($control_options_selector['selector'])) {
									$control_options['selector'] = '{{WRAPPER}}';
								}

								$join_selectors = [];
								foreach($element_ids as $element_id) {
									$join_selectors[] = str_replace('{{WRAPPER}}', '{{WRAPPER}} .elementor-element-' . $element_id . ' ', $control_options['selector']);
								}
								$control_options['selector'] = implode(', ', $join_selectors);

								$control_options['name'] = preg_replace('/' . $control_options['remove_from_control_name'] . '$/', '', preg_replace('/^' . $control_options['remove_from_control_name'] . '/', '', $control['id']));

								unset($control_options['is_grouped']);
								unset($control_options['grouped_type']);
								unset($control_options['remove_from_control_name']);

								$this->add_group_control(str_replace('_', '-', $type), $control_options);

							} elseif(!empty($control_options['responsive'])) {

								unset($control_options['responsive']);

								$this->add_responsive_control($control['id'], $control_options);

							} else {

								$this->add_control($control['id'], $control_options);

							}

						}

						if(!empty($this->current_tab_group)) {
							$this->end_control_tabs_group();
						}

						$this->end_controls_section();
					}
				}

			}

		}

		/**
		 * get_widget_control_replacement_data function
		 *
		 * @version 1.0
		 * @since   1.0
		 * @return array
		 */
		protected function get_widget_control_replacement_data() {

			if(!isset($this->widget_control_replacements)) {

				$this->widget_control_replacements = [];

				foreach($this->get_document()->get_control_settings_data() as $tab) {
					foreach($tab['sections'] as $section) {
						foreach($section['controls'] as $control) {
							foreach($control['replaces'] as $replacement) {

								if(!isset($this->widget_control_replacements[$replacement['id']])) {
									$this->widget_control_replacements[$replacement['id']] = [];
								}

								if($replacement['grouped'] && $control['settings']['remove_from_control_name']) {
									$this->widget_control_replacements[$replacement['id']][$replacement['control']] = preg_replace('/'.$control['settings']['remove_from_control_name'].'$/', '', preg_replace('/^'.$control['settings']['remove_from_control_name'].'/', '', $control['id'])).'_'.$replacement['group_control_key'];
								} else {
									$this->widget_control_replacements[$replacement['id']][$replacement['control']] = $control['id'];
								}
							}
						}
					}
				}
			}

			return $this->widget_control_replacements;

		}

		/**
		 * get_dynamic_control_list function
		 *
		 * @version 1.0
		 * @since   1.0
		 *
		 * @param $el
		 * @param $element_instance
		 *
		 * @return array
		 */
		protected function get_dynamic_control_list($el, $element_instance) {

			$style_controls = $element_instance->get_style_controls();
			$replacements = $this->get_widget_control_replacement_data();
			$replacement_controls = [];

			if(isset($replacements[$el['id']])) {
				foreach($replacements[$el['id']] as $replace => $with) {
					if(isset($style_controls[$replace])) {
						$replacement_controls[] = $style_controls[$replace];
					}
				}
			}

			return $replacement_controls;
		}

		/**
		 * get_custom_widget_wrapper_class function
		 *
		 * @version 1.0
		 * @since   1.0
		 * @return string
		 */
		protected function get_custom_widget_wrapper_class() {

			$class = $this->get_html_wrapper_class();

			if(empty($class)) {
				return '';
			}

			return (wp_doing_ajax() ? "#elementor " : "").'.'.$this->get_html_wrapper_class();

		}

		/**
		 * process_element_settings function
		 *
		 * @version 1.0
		 * @since   1.0
		 *
		 * @param $element
		 */
		protected function process_element_settings($element) {

			$replacement_data = $this->get_widget_control_replacement_data();
			$settings = $this->get_settings_for_display();

			if(isset($replacement_data[$element['id']])) {

				$element_instance = \Elementor\Plugin::$instance->elements_manager->create_element_instance($element);
				$controls = $this->get_dynamic_control_list($element, $element_instance);
				$dynamic_controls = [];
				$parsed_dynamic_settings = [];

				foreach($replacement_data[$element['id']] as $replace => $with) {

					$setting_replacement = empty($settings[$with]) ? '' : $settings[$with];

					$element['settings'][$replace] = $setting_replacement;
					$parsed_dynamic_settings[$replace] = $setting_replacement;
					$dynamic_controls[$replace] = $setting_replacement;

				}

			}

			return $element['settings'];

		}

		/**
		 * get_unique_style_id function
		 *
		 * @version 1.0
		 * @since   1.0
		 * @return false|string
		 */
		public function get_unique_style_id() {

			if(empty($this->unique_style_id)) {

				if(empty($this->get_id())) {
					return false;
				}

				$this->unique_style_id = empty($this->get_id()) ? substr(md5($this->get_name()), 0, 7) : $this->get_id();
			}

			return $this->unique_style_id;
		}

		/**
		 * Get HTML wrapper class.
		 *
		 * Retrieve the widget container class. Can be used to override the
		 * container class for specific widgets.
		 *
		 * @since 1.0
		 * @access protected
		 */
		protected function get_html_wrapper_class() {

			if(empty($this->get_unique_style_id())) {
				return '';
			}

            return apply_filters('ewb/widgets/html_wrapper_class', 'ewb-'.$this->get_unique_style_id(), $this->get_id());
		}

		/**
		 * _get_html_wrapper_class function
		 *
		 * @version 1.0
		 * @since   1.0
		 * @return string
		 */
		public function _get_html_wrapper_class() {
			return $this->get_html_wrapper_class();
		}

		/**
		 * Processes the custom data passed on to the custom widget as if it were the values of the widget itself.
		 *
		 * @version 1.0
		 * @since   1.0
		 *
		 * @param $data
		 *
		 * @return mixed
		 */
		private function process_widget_data($data) {

			$processed = $data;

			if(isset($data['settings'])) {
				$processed['settings'] = $this->process_element_settings($data);
			}

			if($data['elements']) {
				foreach($data['elements'] as $index => $element) {
					$processed['elements'][$index] = $this->process_widget_data($element);
				}
			}

			return $processed;
		}

		/**
		 * replace_widget_settings function
		 *
		 * @version 1.0
		 * @since   1.0
		 *
		 * @param $data
		 * @param $post_id
		 *
		 * @return array
		 */
		public function replace_widget_settings($data, $post_id) {

				$new_settings = [];

				foreach($data as $section) {
					$new_settings[] = $this->process_widget_data($section);
				}

			return $new_settings;
		}

		/**
		 * Get initial config.
		 *
		 * Retrieve the current widget initial configuration.
		 *
		 * Adds more configuration on top of the controls list, the tabs assigned to
		 * the control, element name, type, icon and more. This method also adds
		 * widget type, keywords and categories.
		 *
		 * @since 1.0.10
		 * @access protected
		 *
		 * @return array The initial widget config.
		 */
		protected function _get_initial_config() {

			if($this->get_document()->has_custom_icon()) {
				\EWB\Plugin::instance()->svg_icons->register_icon($this->get_document()->get_custom_icon_class(), $this->get_document()->get_main_meta(Document::ICON_CUSTOM_SVG_META_KEY));
			}

			$config = parent::_get_initial_config();

			$config['controls'] = $this->get_controls();
			$config['tabs_controls'] = $this->get_tabs_controls();
			$config['elType'] = $this->get_type();

			if(isset($config['tabs_controls']['advanced'])) {
				$has_advanced_tab = false;
				foreach($config['controls'] as $control) {
					if($control['tab'] && $control['tab'] == 'advanced') {
						$has_advanced_tab = true;
						break;
					}
				}

				if(!$has_advanced_tab) {
					unset($config['tabs_controls']['advanced']);
				}
			}

			foreach($config['controls'] as $key => $values) {
				$config['controls'][$key]['render_type'] = 'template';
			}

			if($this->get_document()->is_skin()) {
				$config['show_in_panel'] = false;
			}

			return $config;
		}

		/**
		 * render_skin function
		 *
		 * @version 1.0
		 * @since   1.0
		 */
		public function render_skin() {

			$this->is_rendering_skin = true;

			$this->render();

		}

		/**
		 * is_editing function
		 *
		 * @version 1.0
		 * @since   1.0
		 * @return bool
		 */
		private function is_editing() {
			return Elementor\Plugin::instance()->editor->is_edit_mode() || Elementor\Plugin::instance()->preview->is_preview_mode() || wp_doing_ajax() && !empty($_REQUEST['action']) && $_REQUEST['action'] == 'elementor_ajax' || $this->is_rendering_skin;
		}

		/**
		 * render function
		 *
		 * @version 1.0
		 * @since   1.0
		 */
		protected function render() {

			if(get_the_ID() === $this->document_id && \Elementor\Plugin::instance()->editor->is_edit_mode()) {
				echo '<div class="elementor-alert elementor-alert-danger">' . __( 'Invalid Data: The Template ID cannot be the same as the currently edited template. Please choose a different one.', 'elementor' ) . '</div>';
				return;
			}

			if($this->is_editing()) {
				echo '<'.$this->get_html_tag().' class="'.$this->get_html_wrapper_class().'"><div class="elementor-widget-container">';
			}

			if(!$this->is_rendering_skin) {
				add_filter('ewb/document/elements_data', [$this, 'replace_widget_settings'], 10, 2);
			} else {
				remove_action( 'elementor/css-file/post/enqueue', [\Elementor\Plugin::instance()->dynamic_tags, 'after_enqueue_post_css']);
			}

			if($this->get_document()->is_autosave()) {
				$css = new Widget_CSS_Preview($this->document_id);
			} else {
				$css = new Widget_CSS($this->document_id);
			}
			$css->set_widget_unique_key($this->get_html_wrapper_class());
			$css->print_css();

			Plugin::$instance->documents->switch_to_document($this->get_document());

			$data = apply_filters('elementor/frontend/builder_content_data', $this->get_document()->get_elements_data(), $this->document_id);

			if(empty($data)) {
				return '';
			}

			ob_start();
			$this->get_document()->print_elements_with_wrapper($data);
			$content =  $this->remove_inline_elements(apply_filters( 'elementor/frontend/the_content', ob_get_clean()));

			$template = preg_replace('/^\s*<style>[^<]+(?=<\/style>)<\/style>/', '', $content);

			Plugin::$instance->documents->restore_document();

			if(!$this->is_rendering_skin) {
				remove_filter('ewb/document/elements_data', [$this, 'replace_widget_settings'], 10, 2);
			} else {
				add_action( 'elementor/css-file/post/enqueue', [\Elementor\Plugin::instance()->dynamic_tags, 'after_enqueue_post_css']);
			}

			echo $template;

			if($this->is_editing()) {
				echo '</div></div>';
			}

		}

		/**
		 * before_render function
		 *
		 * @version 1.0
		 * @since   1.0
		 */
		public function before_render() {
			
			echo '<'.$this->get_html_tag().' '; $this->print_render_attribute_string('_wrapper'); echo '>';

		}

		/**
		 * Get the element raw data.
		 *
		 * Retrieve the raw element data, including the id, type, settings, child
		 * elements and whether it is an inner element.
		 *
		 * The data with the HTML used always to display the data, but the Elementor
		 * editor uses the raw data without the HTML in order not to render the data
		 * again.
		 *
		 * @since 1.0.0
		 * @access public
		 *
		 * @param bool $with_html_content Optional. Whether to return the data with
		 *                                HTML content or without. Used for caching.
		 *                                Default is false, without HTML.
		 *
		 * @return array Element raw data.
		 */
		public function get_raw_data($with_html_content = false) {
			$data = parent::get_raw_data($with_html_content);
			$data['widgetType'] = '<?php echo $this->get_widget_type(); ?>';
			$data['title'] = $this->get_title();
			return $data;
		}

		/**
		 * get_html_tag function
		 *
		 * @version 1.0
		 * @since   1.0
		 * @return string
		 */
		private function get_html_tag() {
			return 'div';
		}

		/**
		 * remove_inline_elements function
		 *
		 * @version 1.0
		 * @since   1.0
		 *
		 * @param $content
		 *
		 * @return string|string[]
		 */
		public function remove_inline_elements($content) {
			return str_replace('elementor-inline-editing', '', $content);
		}

		/**
		 * after_render function
		 *
		 * @version 1.0
		 * @since   1.0
		 */
		public function after_render() {
			echo '</div>';
		}

		/**
		 * get_config_for_editor_panel function
		 *
		 * @version 1.0
		 * @since   1.0
		 * @return array
		 */
		public function get_config_for_editor_panel() {
			return $this->_get_initial_config();
			$this->render_content();
		}

		/**
		 * Get default child type.
		 *
		 * Retrieve the widget child type based on element data.
		 *
		 * @since 1.0.0
		 * @access protected
		 *
		 * @param array $element_data Widget ID.
		 *
		 * @return array|false Child type or false if it's not a valid widget.
		 */
		protected function _get_default_child_type(array $element_data) {
			return \Elementor\Plugin::$instance->elements_manager->get_element_types( 'section' );
		}

	}

?>
