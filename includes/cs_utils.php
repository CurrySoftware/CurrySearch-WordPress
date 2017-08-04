<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * This class contains some utility functions used for communication with the CurrySearch API.
 *
 * We choose not to default to the WordPress HTTP API due to following reasons:
 * 1. It is slower than directly using curl
 * 2. There is no way to keep HTTPS connections alive.
 *    During indexing we send a multitude of requests to the CurrySearch API.
 *    If for a new HTTPS connection would need to be established for each of these requests,
 *    indexing would take MUCH longer.
 *
 * This is why we look for a curl installation first and use that.
 * If curl is not installed we use the WordPress HTTP API as fallback.
 *
 * We know that using curl in WordPress plugins is bad practice.
 * So if you know a way how to persist HTTPS connections with the WordPress HTTP API please let us know.
 * We will drop curl in that case!
 */
class CurrySearchUtils{

	const MANAGEMENT_URL = "https://ms-curry-test/";
	const APPLICATION_URL = "https://ms-curry-test";

	static $curl_handle = NULL;

	static function call_as($action, $port, $api_key, $session_hash, $payload) {
		return call(CurrySearchUtils::APPLICATION_URL.":".$port."/".$action, $api_key, $session_hash, $payload);
	}

	static function call_ms($action, $api_key, $payload) {
		return call(CurrySearchUtils::MANAGEMENT_URL.$action, $api_key, NULL, $payload);
	}

	private static function call($url, $api_key, $session_hash, $payload) {
		if (curl_installed()) {

			if (!isset(CurrySearchUtils::$curl_handle)) {
				CurrySearchUtils::$curl_handle = curl_init();
			}

			$ch = curl_copy_handle(CurrySearchUtils::$curl_handle);

			curl_setopt($ch, CURLOPT_URL, $url);
			$header = array(
				'Connection: keep-alive',
				'Keep-Alive: 300',
			);
			if (isset($api_key)) {
				array_push($header, 'X-CS-ApiKey: '.$api_key);
			}
			if (isset($session_hash)) {
				array_push($header, 'X-CS-Session: '.$session_hash);
			}
			// Use POST if there is a payload and GET otherwise
			if (isset($payload)) {
				$data_string = json_encode($payload);
				curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
				curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
				array_push($header, 'Content-Type: application/json');
				array_push($header, 'Content-Length: ' . strlen($data_string));
			} else {
				curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
			}
			curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
			curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
			$response = curl_exec($ch);

			curl_close($ch);
			return $reponse;
		}
	}

	private static function curl_installed(){
		return function_exists('curl_version');
	}

}