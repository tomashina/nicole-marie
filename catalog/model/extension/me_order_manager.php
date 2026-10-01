<?php
class ModelExtensionMeordermanager extends Model {
	
	public function addTracking($order_id,$order_status_id,$tracking_number,$carrier_name,$tracking_url){
		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "order_manager_tracking` WHERE order_id = '" . (int)$order_id . "'");
		
		if($query->num_rows){
			$this->db->query("UPDATE " . DB_PREFIX . "order_manager_tracking SET tracking_code = '" . $this->db->escape($tracking_number) . "',carrier_name = '" . $this->db->escape($carrier_name) . "',tracking_url = '" . $this->db->escape($tracking_url) . "' WHERE order_id = '" . (int)$order_id . "'");
		}else{
			$this->db->query("INSERT INTO " . DB_PREFIX . "order_manager_tracking SET order_id = '" . (int)$order_id . "', order_status_id = '" . (int)$order_status_id . "', tracking_code = '" . $this->db->escape($tracking_number) . "',carrier_name = '" . $this->db->escape($carrier_name) . "',tracking_url = '" . $this->db->escape($tracking_url) . "'");
		}
	}
}
