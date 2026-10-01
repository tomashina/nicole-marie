<?php

namespace liveopencart\lib\v0029;

class extension extends library {
	use traits\cache;
	use traits\language;
	use traits\resource;
	use traits\installed;
	use traits\db;
	use traits\simple_db;
	
	protected $extension_code = '';
	protected $version        = '';
	//protected $simple_db;
	
	public $extension_event   = ''; // own internal events
	protected $events_catalog = []; // system events enabling from extension instance
	protected $events_admin   = []; // system events enabling from extension instance
	
	public function __construct($registry) {
		call_user_func_array( parent::class.'::__construct' , func_get_args());
		
		$this->simple_db       = new simple_db($registry);
		$this->extension_event = new event_manager();
	}
	
	//public function getSimpleDB() {
	//	return $this->simple_db;
	//}
	
	public function getExtensionCode() {
		return $this->extension_code;
	}
	
	public function getCurrentVersion() {
		return $this->version;
	}
	
	public function loadLanguageLazyByRoute($route) {
		
		$all          = $this->language->all();
		$sub_lang_key = 'liveopencart_'.$this->extension_code;
		$this->load->language($route, $sub_lang_key);
		$lang = $this->language->get($sub_lang_key);
		
		foreach ( array_diff( array_keys($lang->all()), array_keys($all) ) as $key ) {
			$this->language->set($key, $lang->get($key));
		}
		
	}
	
	protected function activateExtensionEvents() {
		if ( $this->inAdminSection() ) {
			$this->addEvents($this->events_admin);
		} else {
			$this->addEvents($this->events_catalog);
		}
	}
	
	protected function addEvents($events) {
		foreach ( $events as $trigger => $action_route ) {
			$this->registry->get('event')->register($trigger, new \Action( $action_route), 0);
		}
	}
	
}
