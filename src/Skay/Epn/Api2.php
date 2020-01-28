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
	private $_response = array();

	function __construct($clientId, $clientSecret)
	{
		$this->_clientId = $clientId;
		$this->_clientSecret = $clientSecret;
	}

	// Добавление запроса в список
	private function AddRequest(string $name, string $action, array $filters = [])
	{
		$filters['action'] = $action;
		$this->_filters[$name] = $filters;
		// Если список запросов пуст
		if (!sizeof($this->_filters))
		{
			return TRUE;
		}
	}

	public function ssid()
	{
		$this->_filters = [
			'url' => self::API_APPEPN_URL.'/ssid',
			'data' => [
				'client_id' => 'web-client'
			],
		];
	}

	public function oauthSsid()
	{
		$this->_filters = [
			'url' => self::API_OAUTH_URL.'/ssid',
			'data' => [
				'client_id' => 'web-client'
			]
		];
		return true;
	}

	public function token()
	{
		self::oauthSsid();
		self::RunRequests();
		$data = self::Response('data');
		$this->_filters = [
			'method' => 'POST',
			'url' => self::API_OAUTH_URL.'/token',
			'header' => [
				'X-SSID' => $data['attributes']['ssid_token']
			],
			'data' => [
				'grant_type' => 'client_credential',
				'client_id' => $this->_clientId,
				'client_secret' => $this->_clientSecret,
			]
		];
		return true;
	}

	public function getTest()
	{
		self::token();
		self::RunRequests();
		$data = self::Response('data');
		$this->_filters = [
			'url' => self::API_APPEPN_URL.'/test/ping',
			'data' => [
				'v' => self::API_VERSION
			],
			'header' => [
				'X-ACCESS-TOKEN' => $data['attributes']['access_token']
			]
		];
	}

	public function getTestUserInfo()
	{
		self::token();
		self::RunRequests();
		$data = self::Response('data');
		$this->_filters = [
			'method' => 'POST',
			'url' => self::API_APPEPN_URL.'/test/user-info',
			'header' => [
				'X-ACCESS-TOKEN' => $data['attributes']['access_token']
			]
		];
	}

	//Получение данных о пользователе
	public function userProfile()
	{
		self::token();
		self::RunRequests();
		$data = self::Response('data');
		$this->_filters = [
			'url' => self::API_APPEPN_URL.'/user/profile',
			'header' => [
				'X-ACCESS-TOKEN' => $data['attributes']['access_token']
			]
		];
	}

	//Получение данных о пользователе(сокращенный список полей)
	public function userProfileShort()
	{
		self::token();
		self::RunRequests();
		$data = self::Response('data');
		$this->_filters = [
			'url' => self::API_APPEPN_URL.'/user/profile/short',
			'header' => [
				'X-ACCESS-TOKEN' => $data['attributes']['access_token']
			]
		];
	}

	public function offersCategories()
	{
		$this->_filters = [
			'url' => self::API_APPEPN_URL.'/offers/categories',
			'data' => [
				'lang' => 'ru',
				// 'offerId' => 1
			]
		];
	}

	public function offersCompilations()
	{
		$this->_filters = [
			'url' => self::API_APPEPN_URL.'/offers/compilations',
			'data' => [
				'status' => 'active',
				'limit' => 30,
				'offset' => 0,
				'viewRules' =>  "area_web"//area_web,area_mobile,role_cashback,role_user
			]
		];
	}

	public function offersByLink($link)
	{
		self::token();
		self::RunRequests();
		$data = self::Response('data');
		$this->_filters = [
			'url' => self::API_APPEPN_URL.'/offers/by-link',
			'data' => [
				'link' => $link
			],
			'header' => [
				'X-ACCESS-TOKEN' => $data['attributes']['access_token']
			]
		];
	}

	public function offersFavorite()
	{
		self::token();
		self::RunRequests();
		$data = self::Response('data');
		$this->_filters = [
			'url' => self::API_APPEPN_URL.'/offers/favorite',
			'header' => [
				'X-ACCESS-TOKEN' => $data['attributes']['access_token']
			]
		];
	}

	public function goodsOffersIdDumps($id)
	{
		self::token();
		self::RunRequests();
		$data = self::Response('data');
		$path = sprintf('/goods/offers/%s/dumps', $id);
		$this->_filters = [
			'url' => self::API_APPEPN_URL.$path,
			'data' => [
				'offerId' => $id
			],
			'header' => [
				'X-ACCESS-TOKEN' => $data['attributes']['access_token'],
				'ACCEPT-LANGUAGE' => 'ru'
			],
		];
	}

	public function goodsHotSells()
	{
		$this->_filters = [
			'url' => self::API_APPEPN_URL.'/goods/hot-sells',
			'data' => [
			// 	'search' => '',
			// 	'order' => 'newDate', //percent,newDate,orders
			// 	'sortType' => 'desc', //desc,asc
			// 	'page' => 1,
			// 	'perPage' => 20,
			// 	'filterFrom' => 0,
			// 	'filterTo' => 100,
				'filterGoods' => 1,
			// 	'filterOffers' => '1',
			// 	// 'filterProduct' => ''
			]
		];
	}

	public function RunRequests()
	{
		$this->_url = $this->_filters['url'];
		$method = $this->_filters['method']??'GET';
		$header = [
			'X-API-VERSION' => self::API_VERSION,
			'ACCEPT-LANGUAGE' => $this->_lang,
		];
		$header = is_array($this->_filters['header'])?array_replace($this->_filters['header'], $header):$header;
		$curl = new Curl();
		foreach ($header as $key => $value) {
			$curl->setHeader($key, $value);
		}
		$curl->setUserAgent($_SERVER['HTTP_USER_AGENT']);
		$data = $this->_filters['data']??[];
		switch ($method) {
			case 'GET':
			$curl->get($this->_url, $data);
			break;
			case 'POST':
			$curl->post($this->_url, $data);
			break;
			default:
			return false;
			break;
		}
		dump($curl);
		if($curl->curl_error){
			throw new \Skay\Exception\CurlException($curl->curl_error_message, $curl->curl_error_code);
		}
		$response = json_decode($curl->response, true);
		$curl->close();
		if ($response['errors']) {
			$error = self::getError($response['errors']);
			throw new \Skay\Exception\ErrorException($error['msg'], $error['code']);
		}
		$this->_response = new \Skay\Epn\Response($response);
		return true;
	}

	public function Response($name = false)
	{
		if($name)return $this->_response->getData($name);
		return $this->_response;
	}

	public function getError(array $data)
	{
		foreach ($data as $key => $value) {
			$this->_error['msg'] = $value['error_description'];
			$this->_error['code'] = $value['error'];
			if($value['captcha'])$this->_error['captcha'] = $value['captcha'];
		}
		if($this->_error['code']==429001){
			dump($this->_url);die();
			$curl = new \Curl\Curl();
			$data = ['client_id' => 'web-client','v'=>self::API_VERSION];
			$curl->get(self::API_APPEPN_URL.'/ssid', $data);
			dump($curl);die();
			$captcha = $error['captcha'];
			$url = self::API_APPEPN_URL.'/captcha/check';
			$this->_filters['data']['ssid_token'] = 'd72c3fca402ac11e950a9abfffed0f03';
			$this->_filters['data']['captcha'] = $captcha['captcha']['site_key'];
			$this->_filters['data']['captcha_phrase_key'] = $captcha['captcha_phrase_key'];
			$curl->post($url, $this->_filters);
			dump($curl);die();
		}
		return $this->_error;
	}

}
