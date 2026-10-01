<?php

namespace liveopencart\lib\v0029\traits;

trait debug {
	
	protected $debug_forced = false;
	
	protected function enableDebug() {
		$this->setDebug(true);
	}
	
	protected function setDebug($is_debug) {
		$this->debug_forced = $is_debug;
	}
	
	protected function isDebug() {
		return $this->debug_forced || substr(parse_url(HTTP_SERVER)['host'], -5) == '.tnkr';
	}
}
