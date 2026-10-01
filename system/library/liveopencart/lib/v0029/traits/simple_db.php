<?php

namespace liveopencart\lib\v0029\traits;
trait simple_db {
 
	protected $simple_db;
	
	public function getSimpleDB() {
		
		if (!$this->simple_db) {
			$this->simple_db = new \liveopencart\lib\v0029\simple_db($this->registry);
		}
		
		return $this->simple_db;
	}
}
