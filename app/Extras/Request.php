<?php

namespace App\Extras;

class Request
{
	protected $baseUrl;
	private $debug;
	
	public function __construct($baseUrl = null) {
		$this->baseUrl = $baseUrl ?: env("BASE_URL");
		$this->debug = false;
	}
	
	public function get($url, $params = array())
	{
		$endUrl = $this->baseUrl . $url . "?";
		
		foreach ($params as $key => $value) {
			$endUrl .= "&{$key}={$value}";
		}
		$response = json_decode($this->get_web_page($endUrl));
		
		return $response;
	}
	
	public function post($url, $params = array())
	{
		$endUrl = $this->baseUrl . $url;
		$response = json_decode($this->get_web_page($endUrl, $params));
		if ( ! $response) {
			$response = $this->get_web_page($endUrl, $params);
		}
		
		return $response;
	}
	
	protected function get_web_page($url, $params = null, $headers = null)
	{
		$options = array(
			CURLOPT_RETURNTRANSFER => true,   // return web page
			CURLOPT_HEADER         => false,  // don't return headers
			CURLOPT_FOLLOWLOCATION => true,   // follow redirects
			CURLOPT_MAXREDIRS      => 10,     // stop after 10 redirects
			CURLOPT_ENCODING       => "",     // handle compressed
			CURLOPT_USERAGENT      => "test", // name of client
			CURLOPT_AUTOREFERER    => true,   // set referrer on redirect
			CURLOPT_CONNECTTIMEOUT => 120,    // time-out on connect
			CURLOPT_TIMEOUT        => 120,    // time-out on response
		);
		
		if ($params) $options[CURLOPT_POSTFIELDS] = http_build_query($params);
		
		if (!is_null($params) && isset($params["filesize"]) && $params["filesize"]) $options[CURLOPT_INFILESIZE] = $params["filesize"];
		
		if ($headers) {
			foreach ($headers as $key => $value) {
				unset($headers[$key]);
				$headers[] = $key.": ".$value;
			}
			
			$options[CURLOPT_HTTPHEADER] = $headers;
		}
		
		$ch = curl_init($url);
		curl_setopt_array($ch, $options);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		
		// For debugging purposes
		if ($this->debug) {
			curl_setopt($ch, CURLOPT_VERBOSE, true);
			$verbose = fopen('php://temp', 'w+');
			curl_setopt($ch, CURLOPT_STDERR, $verbose);
		}
		
		$content = curl_exec($ch);
		
		if ($this->debug) {
			if ($content === FALSE) {
				printf("cUrl error (#%d): %s<br>\n", curl_errno($ch),
					htmlspecialchars(curl_error($ch)));
			}
			
			rewind($verbose);
			$verboseLog = stream_get_contents($verbose);
			
			echo "Verbose information:\n<pre>", htmlspecialchars($verboseLog), "</pre>\n";
		}
		
		curl_close($ch);
		return $content;
	}
	
	public function getOthers($url, $params = array(), $headers = null)
	{
		$endUrl = $url . "?";
		
		foreach ($params as $key => $value) {
			$endUrl .= "&{$key}={$value}";
		}
		
		$resp     = $this->get_web_page($endUrl, null, $headers);
		$response = json_decode($resp);
		if ( ! $response) {
			$response = $resp;
		}
		
		return $response;
	}
	
	public function postOthers($url, $params = array(), $headers = null)
	{
		$endUrl   = $url;
		$resp     = $this->get_web_page($endUrl, $params, $headers);
		$response = json_decode($resp);
		if ( ! $response) {
			$response = $resp;
		}
		
		return $response;
	}
}