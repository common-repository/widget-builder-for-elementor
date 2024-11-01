<?php

	namespace EWB\Modules\Custom_Widget;

	if(!defined('ABSPATH') || !defined('WPINC')) { exit; }

	class Widget_CSS_Preview extends \Elementor\Core\Files\CSS\Post_Preview {

		private $widget_unique_key = '';

		/**
		 * set_widget_unique_key function
		 *
		 * @version 1.0
		 * @since   1.0
		 *
		 * @param $key
		 */
		public function set_widget_unique_key($key) {
			$this->widget_unique_key = $key;
		}

		/**
		 * get_widget_unique_key_selector function
		 *
		 * @version 1.0
		 * @since   1.0
		 * @return string
		 */
		private function get_widget_unique_key_selector() {
			return '.'.$this->widget_unique_key;
		}

		/**
		 * get_element_unique_selector function
		 *
		 * @version 1.0
		 * @since   1.0
		 *
		 * @param Element_Base $element
		 *
		 * @return string
		 */
		public function get_element_unique_selector(Element_Base $element) {

			return $this->get_widget_unique_key_selector().' .elementor-widget-container > '.parent::get_element_unique_selector($element);

		}

	}
