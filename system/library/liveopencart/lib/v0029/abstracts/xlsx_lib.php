<?php

namespace liveopencart\lib\v0029\abstracts;

abstract class xlsx_lib {

	use \liveopencart\lib\v0029\traits\vendor_lib;
	
	protected $registry;
	
	public function __construct($registry) {
		$this->registry = $registry;
		$this->init();
	}
	
	protected function init() { // to redefine in childs
		
	}
	
	public function __get($name) {
		return $this->registry->get($name);
	}
	
	public function getNewSheetInfo($name, $data = []) {
		return new \liveopencart\lib\v0029\vendors\xlsx_sheet_info($name, $data);
	}
	
	protected function prepareSheetName($name) {
		$str = str_replace(['\\', '/', '?', '*', '[', ']', ':'], ' ', htmlspecialchars_decode($name) );
		if ( function_exists('mb_substr') ) {
			return mb_substr($str, 0, 31);
		} else {
			return substr($str, 0, 31);
		}
	}
	
	public function getName() {
		return $this->name;
	}

	public function install() {
		return $this->installVendorLib($this->download_url, $this->download_md5_hash);
	}
	
	protected function getLoaderFileName() {
		return $this->getVendorDir().$this->loader_file_name;
	}
	
	protected function loadLib() {
		if ( file_exists($this->getLoaderFileName()) ) {
			require_once( $this->getLoaderFileName() );
		}
	}
}
