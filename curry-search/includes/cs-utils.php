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

	static $curl_handle = NULL;

	static function call_as($action, $port, $api_key, $session_hash, $payload) {
		return CurrySearchUtils::call(
			CurrySearchConstants::APPLICATION_URL.":".$port."/".
			CurrySearchConstants::AS_API_VERSION.'/'.$action, $api_key, $session_hash, $payload);
	}

	static function call_ms($action, $api_key, $payload) {
		return CurrySearchUtils::call(
			CurrySearchConstants::MANAGEMENT_URL.CurrySearchConstants::MS_API_VERSION.'/'.$action,
			$api_key, NULL, $payload);
	}

	static function call_stats($action, $api_key, $payload) {
		return CurrySearchUtils::call(
			CurrySearchConstants::STATS_URL.$action, $api_key, NULL, $payload);
	}

	private static function api_admin_warn() {
		?>
		<div class="notice notice-warn is-dismissible">
        	<p>Problem occured during communication with the CurrySearch API! If problem persists please contact support@curry-software.com</p>
		</div>
		<?php
	}

	private static function call($url, $api_key, $session_hash, $payload) {
		if (CurrySearchUtils::curl_installed()) {

			if (!isset(CurrySearchUtils::$curl_handle)) {
				CurrySearchUtils::$curl_handle = curl_init();
			}

			$ch = curl_copy_handle(CurrySearchUtils::$curl_handle);

			curl_setopt($ch, CURLOPT_URL, $url);

			$header = array(
				'Connection: keep-alive',
				'Keep-Alive: 300',
				'X-CS-Plugin: '.CurrySearchConstants::API_VERSION,
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
			global $wp_version;

			curl_setopt($ch, CURLOPT_USERAGENT, 'Curl/WordPress/'.$wp_version.'/PHP'.phpversion().'; ' . home_url());
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
			curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);

			$response = curl_exec($ch);
			$http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
			if ($http_status != 200) {
				add_action( 'admin_notices', array('CurrySearchUtils', 'api_admin_warn' ));
			}
			curl_close($ch);
			return $response;
		} else {

			$header = array();

			// Use POST if there is a payload and GET otherwise
			if (isset($payload)) {
				  $response = wp_remote_post($url, array(
					  'headers' => array (
						  'Content-Type' => 'application/json',
						  'X-CS-ApiKey' => $api_key,
						  'X-CS-Plugin' => CurrySearchConstants::API_VERSION,
					  ),
					  'timeout' => 30,
					  'body' => json_encode($payload)));
			} else {
				if (isset($api_key)) {
				  $response = wp_remote_get($url, array(
					  'headers' => array(
						  'X-CS-ApiKey' => $api_key,
						  'X-CS-Plugin' => CurrySearchConstants::API_VERSION,
					  ),
					  'timeout' => 30,
				  ));
				  } else {
					$response = wp_remote_get($url);
				  }

			}
			return wp_remote_retrieve_body($response);
		}
	}


    /**
     * Renders some javascript that enables autocomplete by CurrySearch for a certain input field.
     * This field is identified by an id
     * Echoes the resulting html!
     */
    static function autocomplete($field_id) {
        $session_hash = CurrySearchUtils::get_session_hash();
		$public_api_key = CurrySearch::get_public_api_key();
		$url = CurrySearchConstants::APPLICATION_URL.':'.CurrySearch::get_port().'/'.CurrySearchConstants::QAC_ACTION;
		$input_id = $field_id;

        // js thats hooks up the elm code
		include_once(CURRYSEARCH_PLUGIN_PATH.'public/html/elm-hook.html');
    }


	/**
	 * We do not care about users or ips. This is private data.
	 * But for meaningful statitics we need a way to differentiate between different search session
	 * For this we hash together the users ip, their user agent and the current date.
	 *
	 * This hash acts as an pseudonymous identifying function.
	 * We can not see who or what access your site but we can see wich search request are
	 * made by the same device on the same day.
	 *
	 */
	static function get_session_hash() {
		if(isset($_SERVER['REMOTE_ADDR'])) {
			$ip = $_SERVER['REMOTE_ADDR'];
		}
		if(isset($_SERVER['HTTP_USER_AGENT'])) {
			$ua = $_SERVER['HTTP_USER_AGENT'];
		}
		$date = date("omd");
		$sha = sha1($ip.$ua.$date);
		return substr($sha, 0, 10);
	}

	private static function curl_installed(){
		return function_exists('curl_version');
	}

}