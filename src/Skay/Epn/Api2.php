<?php

namespace Skay\Epn;

use Curl\Curl;

/**
 * Api2
 */
class Api2
{
	const API_OAUTH_URL = 'https://oauth2.epn.bz';
	const API_APPEPN_URL = 'https://app.epn.bz';
	const API_VERSION = 2;

	// Параметры
	private $_clientId = '';
	private $_clientSecret = '';
	private $_filters = array();
	private $_url = self::API_APPEPN_URL;
	private $_method = false;
	private $_lang = 'ru';

	function __construct($clientId, $clientSecret)
	{
		$this->_clientId = $clientId;
		$this->_clientSecret = $clientSecret;
	}

	public function ssid()
	{
		$this->_filters = [
			'url' => self::API_APPEPN_URL.'/ssid',
			'data' => [
				'client_id' => 'web-client'
			],
		];
		self::initCurl();
	}

	public function getTest()
	{
		self::ssid();
		$res = self::getResponse();
		$data = $res->getData();
		$this->_filters = [
			'url' => self::API_APPEPN_URL.'/test/ping',
			'data' => [
				'v' => self::API_VERSION
			],
			'header' => [
				'X-ACCESS-TOKEN' => $data['attributes']['ssid_token']
			]
		];
		self::initCurl();
	}

	public function oauthSsid()
	{
		$this->_filters = [
			'url' => self::API_OAUTH_URL.'/ssid',
			'data' => [
				'client_id' => 'web-client'
			],
		];
		self::initCurl();
	}

	public function getGoodsOffersIdDumps($id)
	{
		self::ssid();
		$res = $this->_response;
		$data = $res->getData();
		$path = sprintf('/goods/offers/%s/dumps', $id);
		$url = self::API_APPEPN_URL.$path;
		$this->_filters = [
			'header' => [
				'X-ACCESS-TOKEN' => $data['data']['attributes']['ssid_token'],
				'ACCEPT-LANGUAGE' => 'ru'
			],
		];
		self::initCurl();
	}


	public function initCurl()
	{
		$url = $this->_filters['url'];
		$method = $this->_filters['method']??'GET';
		$header = [
			'X-API-VERSION' => self::API_VERSION,
			'ACCEPT-LANGUAGE' => $this->_lang
		];
		$header = is_array($this->_filters['header'])?array_replace($this->_filters['header'], $header):$header;
		$curl = new Curl();
		foreach ($header as $key => $value) {
			$curl->setHeader($key, $value);
		}
		$curl->setOpt(CURLOPT_RETURNTRANSFER, true);
		$curl->setOpt(CURLOPT_HTTPGET, true);
		$curl->setOpt(CURLOPT_FOLLOWLOCATION, true);
		$curl->setUserAgent($_SERVER['HTTP_USER_AGENT']);
		$data = $this->_filters['data'];
		switch ($method) {
			case 'GET':
			$curl->get($url, $data);
			break;
			case 'POST':
			$curl->post($url, $data);
			break;
			default:
			return false;
			break;
		}
		if($curl->curl_error){
			throw new \App\Models\Exception\CurlException($curl->curl_error_message, $curl->curl_error_code);
		}
		$response = json_decode($curl->response, true);
		$curl->close();
		if ($response['errors']) {
			$error = self::getError($response['errors']);
			if($error['code']==429001){
				$curl = new \Curl\Curl();
				$data = ['client_id' => 'web-client','v'=>self::API_VERSION];
				$curl->get(self::API_APPEPN_URL.'/ssid', $data);
				$captcha = $error['captcha'];
				$url = self::API_APPEPN_URL.'/captcha/check';
				$this->_filters['data']['ssid_token'] = 'd72c3fca402ac11e950a9abfffed0f03';
				$this->_filters['data']['captcha'] = $captcha['captcha']['site_key'];
				$this->_filters['data']['captcha_phrase_key'] = $captcha['captcha_phrase_key'];
				$curl->post($url, $this->_filters);
			}
			throw new \App\Models\Exception\ErrorException($error['msg'], $error['code']);
		}
		$this->_response = new \App\Models\Epn\Response($response);
		return true;
	}

	public function getResponse()
	{
		return $this->_response;
	}

	public function getError(array $data)
	{
		foreach ($data as $key => $value) {
			$this->_error['msg'] = $value['error_description'];
			$this->_error['code'] = $value['error'];
			if($value['captcha'])$this->_error['captcha'] = $value['captcha'];
		}
		return $this->_error;
	}

	public function generateUri($fullMethod)
	{
		$this->_method = explode('::', $fullMethod)[1];
		$this->_url = $this->_filters['url']??$this->_url;
		$url = $this->_url.'/'.$this->_method;
		return $url;
	}

}
