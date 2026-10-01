<?php

namespace liveopencart\lib\v0029\traits;

trait language {
	protected function loadLanguage($route = '') {
		$this->load->language( ($route ? $route : $this->getRoute()) );
	}
	
	protected function getLanguageData($route = '') {
		$language = new \Language($this->config->get('language_directory'));
		$language->load( ($route ? $route : $this->getRoute()) );
		return $language->all();
	}
	
	protected function getLanguageValueIfExists($key) {
		$all = $this->language->all();
		if ( isset($all[$key]) ) {
			return $all[$key];
		}
	}
	
	protected function getLanguageValue($key, $default_value = '') {
		$value = $this->getLanguageValueIfExists($key);
		if ( $value ) {
			return $value;
		} else {
			return $default_value;
		}
	}
}
