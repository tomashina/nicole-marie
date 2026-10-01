<?php
class ModelSFToolUpload extends Model {
	public function addUpload($name, $filename) {
		$code = sha1(uniqid(mt_rand(), true));

		$this->db->query("INSERT INTO `" . DB_PREFIX . "upload` SET `name` = '" . $this->db->escape($name) . "', `filename` = '" . $this->db->escape($filename) . "', `code` = '" . $this->db->escape($code) . "', `date_added` = NOW()");

		return $code;
	}

	public function getUploadByCode($code) {
		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "upload` WHERE code = '" . $this->db->escape($code) . "'");

		return $query->row;
	}
	
	public function DeleteUploadByCode($code) {
		$this->db->query("DELETE FROM `" . DB_PREFIX . "upload` WHERE code = '" . $this->db->escape($code) . "'");
	}
	
	public function UpdateCustomer($data) {
		$this->db->query("UPDATE " . DB_PREFIX . "customer SET file = '" . $this->db->escape(serialize($data['file'])) . "' WHERE customer_id = '" . (int)$data['customer_id'] . "'");
	}
	
	public function getFiles($data = array()) {
		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "upload` ORDER BY date_added DESC");
		
		return $query->rows;
	}
	
	public function getTotalFiles($data = array()) {
		$query = $this->db->query("SELECT COUNT(*) as total FROM `" . DB_PREFIX . "upload`");
		
		return $query->row['total'];
	}
	
	public function DeleteFile($upload_id) {
		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "upload` WHERE upload_id = '" . (int)$this->db->escape($upload_id) . "'");
				
		if ($query) {
			$file = DIR_UPLOAD . $query->row['filename'];
			
			if (file_exists($file)) {
				unlink($file);				
				$this->DeleteUploadByCode($query->row['code']);				
				return true;
			}
		}
		
		return false;
	}
	
	public function DeleteAllFiles() {
		$this->db->query("TRUNCATE table `" . DB_PREFIX . "upload`");

		$files = glob(DIR_UPLOAD . '/*');
		
		foreach($files as $file){
		   if(is_file($file)){
			  unlink($file);
		   }
		}
	}
	
	public function DeleteTempFiles() {
		$files = glob(DIR_UPLOAD . '/temp/*');
		
		foreach($files as $file){
		   if(is_file($file)){
			  unlink($file);
		   }
		}
	}
	
	public function initializedb() {
		$this->db->query("ALTER TABLE `" . DB_PREFIX . "customer` ADD `file` text NOT NULL;");
	}
}