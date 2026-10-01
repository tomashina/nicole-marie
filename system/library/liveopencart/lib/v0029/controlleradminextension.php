<?php

// the file names this way (without underscores) because of the standard opencart autoloader limitations

namespace liveopencart\lib\v0029;

class ControllerAdminExtension extends ControllerAdmin {
	use traits\extension_config;
	use traits\json;
	use traits\lib_factory;
	
	protected $route_base           = 'extension';
	protected $extension_type       = 'module';
	protected $extension_module     = ''; // last part of controller route (filename)
	protected $extension_code       = ''; // liveopencart ext code
	protected $route_extensions     = 'marketplace/extension';
	protected $route_extensions_oc2 = 'extension/extension';
	protected $event_prefix         = 'liveopencart.';
	protected $events               = [];

	/*
	public function __construct() {
		call_user_func_array( array('parent', '__construct') , func_get_args());
	}
	*/
	
	public function selfControl() {
		call_user_func_array( parent::class.'::'.__FUNCTION__ , func_get_args());
		
		if (!$this->extension_module) {
			throw new \Exception('Error: Class property "extension_module" should be defined!');
		}
		if (!$this->extension_code) {
			throw new \Exception('Error: Class property "extension_code" should be defined!');
		}
	}
	
	protected function getIndexData($data = []) {
		$data = call_user_func_array( parent::class.'::'.__FUNCTION__ , func_get_args());
		
		$this->load->model('setting/setting');
		$this->saveSettingsIfAny($data);
		
		$data['settings'] = $this->getCurrentPageSettings();
		
		return $data;
	}

	protected function getLinks() {

		$data = call_user_func_array( parent::class.'::'.__FUNCTION__ , func_get_args());
	
		$data['cancel']   = $this->getLinkWithToken($this->getRouteExtensions() , '&type=' . $this->extension_type);
		$data['redirect'] = $data['action'];

		return $data;

	}

	protected function getRouteExtensions() {
		return VERSION >= '3.0.0.0' ? $this->route_extensions : $this->route_extensions_oc2;
	}

	protected function getRouteExtension($custom_code = '', $addition_code = '') {
		return 'extension/' . $this->extension_type . '/' . ($custom_code ? $custom_code : $this->extension_module) . ($addition_code ? '/' . $addition_code : '');
	}

	protected function getBreadcrumbsPath() {
		$breadcrumbs[] = [
			'text' => $this->language->get('text_module') ,
			'href' => $this->getLinkWithToken($this->getRouteExtensions() , '&type=' . $this->extension_type)
		];
		return $breadcrumbs;
	}
	
	protected function getBreadcrumbSelf() {
		$breadcrumb = [
			'text' => $this->language->get('module_name') ,
			'href' => $this->getLinkWithToken($this->getRouteExtension())
		];
		return $breadcrumb;
	}
	
	protected function getExtLib() { // to be redefined in child class
		
	}
	
	protected function getExtensionCode() { // can be redefined for exts without lib
		return $this->getExtLib()->getExtensionCode();
	}

	protected function getCurrentVersion() { // can be redefined for exts without lib
		return $this->getExtLib()->getCurrentVersion();
	}
	
	protected function getModuleDetails() {
		$data                            = [];
		$data['extension_code']          = $this->getExtensionCode();
		$data['module_version']          = $this->getCurrentVersion();
		$data['config_admin_language']   = $this->config->get('config_admin_language');
		$data['extension_lic_key']       = $this->getLicKey();
		$data['url_save_activation_key'] = str_replace('&amp;', '&', $this->getLinkWithToken($this->getRoute('save_lic_key')));
		return $data;
	}

	protected function setSettings($settings) {
		$data = [];
		foreach ($settings as $setting_key => $setting_value) {
			$data[$this->getExtensionSettingPrefix() . $setting_key] = $setting_value;
		}

		$this->load->model('setting/setting');
		$this->model_setting_setting->editSetting($this->getExtensionSettingCode(), $data);

	}

	protected function getCurrentPageSettings($defaults = []) {
		$settings = $defaults;
		if (isset($this->request->post['settings'])) {
			$settings = $this->request->post['settings'];
		}
		elseif ($this->getExtensionConfig('settings')) {
			$settings = $this->getExtensionConfig('settings');
		}
		else {
			$settings = [];
		}
		return $settings;
	}
	
	public function save_lic_key() {
		$json = [];
		if (!$this->validatePermissionModify()) {
			$json['error'] = $this->language->get('error_permission');
		} else {
			$key = $this->request->post['key'] ?? '';
			$this->setLicKey($key);
			$json['success'] = true;
		}
		$this->setOutputJSON($json);
	}
	
	protected function getLicKeySettingCode() {
		return 'liveopencart_'.$this->extension_code;
	}
	
	protected function getLicKey() {
		$this->load->model('setting/setting');
		$key_settings = $this->model_setting_setting->getSetting($this->getLicKeySettingCode());
		return $key_settings[$this->getLicKeySettingCode().'_key'] ?? '';
	}
	
	protected function setLicKey($key) {
		$this->load->model('setting/setting');
		$key_settings = $this->model_setting_setting->editSetting($this->getLicKeySettingCode(), [
			$this->getLicKeySettingCode().'_key' => $key,
		]);
	}

	protected function saveSettingsIfAny($data) {
		if ($this->existSettingsToSave()) {
			$this->saveSettingsStandard($data);
		}
	}

	protected function existSettingsToSave() {
		return (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate());
	}

	protected function saveSettingsStandard($data, $post_data = false) {

		if ($post_data === false) {
			$post_data = $this->request->post;
		}
		$settings = [
			'settings' => isset($post_data['settings']) ? $post_data['settings'] : [] ,
			'status'   => isset($post_data['settings']['status']) ? $post_data['settings']['status'] : '', // for standard opencart module status
			
		];
		$this->setSettings($settings);

		$this->session->data['success'] = $this->language->get('text_success');
		$this->response->redirect($data['redirect']);
	}

	protected function updateEvents() {
		$this->load->model('setting/event');

		foreach ($this->getEvents() as $event) {
			$status     = isset($event['status']) ? $event['status'] : 1;
			$sort_order = isset($event['sort_order']) ? $event['sort_order'] : 0;

			if ($status) {
				$oc_event = $this->model_setting_event->getEventByCode($event['code']);
				if ($oc_event) {
					if ($oc_event['trigger'] != $event['trigger'] || $oc_event['action'] != $event['action']) {
						$this->model_setting_event->deleteEventByCode($event['code']);
						$this->model_setting_event->addEvent($event['code'], $event['trigger'], $event['action'], $status, $sort_order);
					}
				}
				else {
					$this->model_setting_event->addEvent($event['code'], $event['trigger'], $event['action'], $status, $sort_order);
				}
			}
			else { // we remove disabled events
				$this->model_setting_event->deleteEventByCode($event['code']);
			}
		}
	}

	protected function removeEvents() {
		$this->load->model('setting/event');
		foreach ($this->getEvents() as $event) {
			$this->model_setting_event->deleteEventByCode($event['code']);
		}
	}

	protected function getEvents() { // returns events with prefixes added to codes
		$events = [];
		foreach ($this->events as $event) {
			$event['code'] = $this->event_prefix . $event['code'];
			$events[]      = $event;
		}
		return $events;
	}

	//protected function validatePermission() {
	//	if (!$this->user->hasPermission('modify', $this->getRoute())) {
	//		$this->error['warning'] = $this->language->get('error_permission');
	//		return false;
	//	}
	//	return true;
	//}

	protected function validate() {
		return $this->validatePermission();
	}
}
