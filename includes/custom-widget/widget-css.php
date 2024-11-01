<?php

	namespace EWB\Modules\Custom_Widget;

	use Elementor\Element_Base;

	if(!defined('ABSPATH') || !defined('WPINC')) { exit; }

	class Widget_CSS extends \Elementor\Core\Files\CSS\Post {

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
		 * @since 2.0.13
		 * @access protected
		 */
		protected function use_external_file() {
			return false;
		}

		/**
		 * get_widget_unique_key_selector function
		 *
		 * @version 1.0
		 * @since   1.0
		 * @return string
		 */
		private function get_widget_unique_key_selector() {
			return empty($this->widget_unique_key) ? "" : ".".$this->widget_unique_key;
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

			return $this->get_widget_unique_key_selector().' '.parent::get_element_unique_selector($element);

		}

	}
