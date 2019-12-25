<?php

class ExternalService_Curl
{
	const COOKIE_FILE = 'file';

	/**
	 * Connection header
	 *
	 * @var string
	 */
	private $headers;

	/**
	 * Response (response) header
	 *
	 * @var $resHeaders
	 */
	private $resHeaders;

	/**
	 * Connection options
	 *
	 * @var array
	 */
	private $options;

	/**
	 * Request parameters
	 *
	 * @var array
	 */
	private $params;

	/**
	 * Outgoing cookies
	 *
	 * @var resource
	 */
	private $putCookie;

	/**
	 * Incoming cookies
	 *
	 * @var resource
	 */
	private $outCookie;

	/**
	 * Temporary parameters for debugging
	 *
	 * @var array
	 */
	private $tempParams;

	/**
	 * Temporary transmission information for debugging
	 *
	 * @var array
	 */
	private $tempInfo;

	/**
	 * Configure connection settings
	 *
	 * @param string $url
	 * @param string $method
	 * @param string $cookie
	 */
	public function setup($url, $method = 'POST', $cookie = '')
	{
		$this->headers = array();
		$this->options = array();
		$this->putCookie = tmpfile();
		$this->outCookie = tmpfile();
		$this->tempParams = array();
		$this->tempInfo = array();

		$this->headers[] = 'HTTP/1.1';
		$this->addHeader('Keep-Alive', 115);
		$this->addHeader('Connection', 'keep-alive');

		$this->setOption(CURLOPT_URL, $url);

		$this->setOption(
			CURLOPT_USERAGENT,
			'Mozilla/5.0 (Windows NT 6.2; WOW64) '
			. 'AppleWebKit/537.36 (KHTML, like Gecko) '
			. 'Chrome/38.0.2125.111 Safari/537.36');

		$this->setOption(
			CURLOPT_MAXREDIRS, 5);

		if ($cookie) {
			$this->setOption(CURLOPT_COOKIE, $cookie);
		}

		switch ($method) {
			case 'GET':
				$this->setOption(CURLOPT_HTTPGET, true);
				break;

			case 'PUT':
			case 'DELETE':
			case 'PATCH':
				$this->setOption(CURLOPT_CUSTOMREQUEST, $method);
				break;

			case 'POST':
			default:
				$this->setOption(CURLOPT_POST, true);
				break;
		}
	}


	/**
	 * Add connection header
	 *
	 * @param string $key
	 * @param string $value
	 */
	public function addHeader($key, $value)
	{
		$this->headers[] = $key . ': ' . $value;
	}

	/**
	 * Add header for HTML acquisition of connection
	 */
	public function addHeaderByHtml()
	{
		$this->addHeader('Accept', 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8');
		$this->addHeader('Accept-Language:', 'ja,en-US;q=0.8,en;q=0.6');
	}

	/**
	 * Add header for HTML acquisition of connection
	 *
	 * @param string $key
	 * @param string $value
	 */
	public function setOption($key, $value)
	{
		$this->options[$key] = $value;
	}

	/**
	 * Set connection sending parameters
	 *
	 * @param array $params
	 */
	public function setParams(array $params)
	{
		if (array_key_exists(CURLOPT_POST, $this->options) || array_key_exists(CURLOPT_CUSTOMREQUEST, $this->options)) {
			$this->setOption(CURLOPT_POSTFIELDS, $params);
		}

		if (array_key_exists(CURLOPT_HTTPGET, $this->options)) {
			$url = $this->options[CURLOPT_URL];
			$query = http_build_query($params);
			$this->setOption(CURLOPT_URL, $url.'?'.$query);
		}

		$this->params[] = $params;
	}

	/**
	 * Set connection send JSON parameters
	 *
	 * @param string $json
	 */
	public function setParamByJSON($json)
	{
		if (array_key_exists(CURLOPT_POST, $this->options)
			|| array_key_exists(CURLOPT_CUSTOMREQUEST, $this->options)) {
			$this->setOption(
				CURLOPT_POSTFIELDS, $json);
		}
		$this->params[] = $json;
	}

	/**
	 * Set connection send http_build_query parameters
	 *
	 * @param string $query http_build_query
	 */
	public function setParamByQuery($query)
	{
		if (array_key_exists(CURLOPT_POST, $this->options)
			|| array_key_exists(CURLOPT_CUSTOMREQUEST, $this->options)) {
			$this->setOption(
				CURLOPT_POSTFIELDS, $query);
		}
		$this->params[] = $query;
	}

	/**
	 * Set connection send XML parameters
	 *
	 * @param $xml
	 */
	public function setParamByXML($xml)
	{
		if (array_key_exists(CURLOPT_POST, $this->options)
			|| array_key_exists(CURLOPT_CUSTOMREQUEST, $this->options)) {
			$this->setOption(CURLOPT_POSTFIELDS, $xml);
		}
		$this->params[] = $xml;
	}


	/**
	 * Set outgoing cookies
	 *
	 * @param string $data
	 * @param string $type file/string
	 */
	public function setCookie($data, $type = self::COOKIE_FILE)
	{
		if ($type == self::COOKIE_FILE) {
			$this->setCookieByFile($data);
		} else {
			$this->setCookieByString($data);
		}
	}

	/**
	 * Set outgoing cookies
	 *
	 * @param string $data
	 */
	public function setCookieByFile($data)
	{
		fwrite($this->putCookie, $data);
		rewind($this->putCookie);
	}

	/**
	 * Set outgoing cookies (string version)
	 *
	 * @param string $data
	 */
	public function setCookieByString($data)
	{
		$this->setOption(CURLOPT_COOKIE, $data);
	}


	/**
	 * Get incoming cookie
	 *
	 * @return false|string
	 */
	public function getCookie()
	{
		return stream_get_contents($this->outCookie);
	}

	/**
	 * Get the last query parameter
	 *
	 * @return array|string
	 */
	public function getLastQuery()
	{
		if (empty($this->tempParams)) return '';
		return $this->tempParams;
	}

	/**
	 * Get response header
	 *
	 * @return mixed
	 */
	public function getResponseHeader()
	{
		return $this->resheaders;
	}

	/**
	 * Get last transmission information
	 *
	 * @param null $infoOpt
	 *
	 * @return array|mixed|string
	 */
	public function getLastInfo($infoOpt = null)
	{
		if(empty($this->tempInfo)){
			return '';
		}

		if (is_null($infoOpt)){
			return $this->tempInfo;
		}

		if (is_numeric($infoOpt)) {
			$infoOptArray = array(
				CURLINFO_EFFECTIVE_URL => 'url',
				CURLINFO_CONTENT_TYPE => 'content_type',
				CURLINFO_HTTP_CODE => 'http_code',
				CURLINFO_HEADER_SIZE => 'header_size',
				CURLINFO_REQUEST_SIZE => 'request_size',
				CURLINFO_FILETIME => 'filetime',
				CURLINFO_SSL_VERIFYRESULT => 'ssl_verify_result',
				CURLINFO_REDIRECT_COUNT => 'redirect_count',
				CURLINFO_TOTAL_TIME => 'total_time',
				CURLINFO_NAMELOOKUP_TIME => 'namelookup_time',
				CURLINFO_CONNECT_TIME => 'connect_time',
				CURLINFO_PRETRANSFER_TIME => 'pretransfer_time',
				CURLINFO_SIZE_UPLOAD => 'size_upload',
				CURLINFO_SIZE_DOWNLOAD => 'size_download',
				CURLINFO_SPEED_DOWNLOAD => 'speed_download',
				CURLINFO_SPEED_UPLOAD => 'speed_upload',
				CURLINFO_CONTENT_LENGTH_DOWNLOAD => 'download_content_length',
				CURLINFO_CONTENT_LENGTH_UPLOAD => 'upload_content_length',
				CURLINFO_STARTTRANSFER_TIME => 'starttransfer_time',
				CURLINFO_REDIRECT_TIME => 'redirect_time',
				CURLINFO_PRIVATE => 'certinfo',
				CURLINFO_REDIRECT_URL => 'redirect_url',
				CURLINFO_HEADER_OUT => 'request_header',
				CURLINFO_PRIMARY_IP => 'primary_ip',
				CURLINFO_PRIMARY_PORT => 'primary_port',
				CURLINFO_LOCAL_IP => 'local_ip',
				CURLINFO_LOCAL_PORT => 'local_port',
			);
			$infoOpt = $infoOptArray[$infoOpt];
		}
		return $this->tempInfo[$infoOpt];
	}

	/**
	 * Perform the connection
	 *
	 * @param array $info
	 * @param bool $debug
	 *
	 * @return bool|string
	 */
	public function execute(&$info = null, $debug = false)
	{
		$this->setOption(
			CURLOPT_HTTPHEADER, $this->headers);
		$this->setOption(
			CURLOPT_AUTOREFERER, true);
		if ($debug) {
			$this->setOption(
				CURLOPT_HEADER, true);
			$this->setOption(
				CURLINFO_HEADER_OUT, true);
		}
		$this->setOption(
			CURLOPT_RETURNTRANSFER, true);
		$putCookieInfo =
			stream_get_meta_data($this->putCookie);
		$this->setOption(
			CURLOPT_COOKIEFILE, $putCookieInfo['uri']);
		$outCookieInfo =
			stream_get_meta_data($this->outCookie);
		$this->setOption(
			CURLOPT_COOKIEJAR, $outCookieInfo['uri']);

		$resHeaders = [];
		$this->setOption(
			CURLOPT_HEADERFUNCTION, function($curl, $header) use (&$resHeaders) {
			$len = strlen($header);
			$header = explode(':', $header, 2);
			if (count($header) < 2)
				return $len;

			$name = strtolower(trim($header[0]));
			if (!array_key_exists($name, $resHeaders))
				$resHeaders[$name] = [trim($header[1])];
			else
				$resHeaders[$name][] = trim($header[1]);

			return $len;
		});
		$curl = curl_init();
		curl_setopt_array($curl, $this->options);
		$result = curl_exec($curl);
		$info = curl_getinfo($curl);
		curl_close($curl);
		$this->tempParams = $this->params;
		$this->tempInfo = $info;
		$this->resHeaders = $resHeaders;
		$this->headers = array();
		$this->options = array();
		$this->params = array();
		return $result;
	}

}