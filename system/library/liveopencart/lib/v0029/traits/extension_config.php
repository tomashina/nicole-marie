<?php

namespace liveopencart\lib\v0029\traits;

trait extension_config {
	protected function getExtensionSettingCode() {
		return $this->extension_type.'_'.$this->extension_module;
	}
	
	protected function getExtensionSettingPrefix() {
		return $this->getExtensionSettingCode().'_';
	}
	
	protected function getExtensionConfig($setting_name) {
		return $this->config->get($this->getExtensionSettingPrefix().$setting_name);
	}
}
