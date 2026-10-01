<?php

namespace liveopencart\lib\v0029\traits;

trait curl {
	protected function getCURLData($url, $post_data=[]) {
		$ch = curl_init($url);
		//curl_setopt($ch, CURLOPT_NOBODY, true);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		if ($post_data) {
			curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_data));
		}
		
		$result = curl_exec($ch);
		$code   = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);
	
		return [
			'code'       => $code,
			'url_exists' => ($code == 200),
			'result'     => $result,
		];
	}
}
