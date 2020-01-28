<?php

namespace Skay\Epn;

use Curl\Curl;

/**
 * Api
 */
class Api
{
	const API_URL = 'http://api.epn.bz/json';
	const API_VERSION = 2;

	// Параметры
	private $_apiKey = '';
	private $_userHash = '';
	public $_filters = array();

	function __construct($apiKey, $userHash)
	{
		$this->_apiKey = $apiKey;
		$this->_userHash = $userHash;
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

	public function clearFilters()
	{
		$this->_filters = [];
	}

	// Добавление запроса на получение списка категорий
	public function CategoriesList(string $name, string $lang = 'ru')
	{
		self::AddRequest($name, 'list_categories', ['lang' => $lang]);
		return TRUE;
	}

	// Добавление запроса на получение списка категорий.
	public function CurrenciesList(string $name)
	{
		$this->AddRequest($name, 'list_currencies', []);
		return TRUE;
	}

	// ддобавление запроса на поиск.
	public function SearchOffers(string $name, array $filters = [])
	{
		$params = [
			'query' => '',
			'orderby' => '',
			'order_direction' => '',
			'limit' => 10,
			'offset' => 0,
			'category' => '',
			'store' => '',
			'price_min' => 0,
			'price_max' => 10000,
			'lang' => 'ru',
			'currency' => 'RUR'
		];
		$filters = array_replace($params, $filters);
		// Добавляем запрос в список
		$this->AddRequest($name, 'search', $filters);
		return TRUE;
	}

	// Добавление запроса на получение количества товаров в категориях.
	// "category", "limit", "offset", "currency" - игнорируются.
	public function CountForCategories(string $name, array $filters = [])
	{
		$params = [
			'query' => '',
			'orderby' => '',
			'order_direction' => '',
			'limit' => '',
			'offset' => 0,
			'category' => '',
			'store' => '',
			'price_min' => 0,
			'price_max' => 10000,
			'lang' => 'ru'
		];
		$filters = array_replace($params, $filters);
		// Добавляем запрос в список
		$this->AddRequest($name, 'count_for_categories', $filters);
		return TRUE;
	}

	// Получение списка поддерживаемых валют.
	public function ListCurrencies($name)
	{
		$this->AddRequest($name, 'list_currencies', []);
	}

	public function TopMonthly(string $name, array $filters = [])
	{
		$params = [
			'orderby' => 'sales', //sales,commission
			'category' => '',
			'lang' => 'ru',
			'currency' => 'RUR'
		];
		$filters = array_replace($params, $filters);
		$this->AddRequest($name, 'top_monthly', $filters);
	}

	public function OfferInfo(string $name, array $filters = [])
	{
		$params = [
			'id' => 4000141331623,
			'lang' => 'ru',
			'currency' => 'RUR,USD'
		];
		$filters = array_replace($params, $filters);
		$this->AddRequest($name, 'offer_info', $filters);
	}

	public function RunRequests()
	{
		return self::initCurl();;
	}

	/**
	 * @return Response
	 */
	public function getResponse($name = false)
	{
		if($name)return $this->_response->getData($name);
		$data = $this->_response;
		return $data;
	}

	public function initCurl()
	{
		$data = array(
			'user_api_key' => $this->_apiKey,
			'user_hash' => $this->_userHash,
			'api_version' => self::API_VERSION,
			'requests' => $this->_filters,
		);
		$post_data = json_encode($data);
		$curl = new Curl();
		$curl->setHeader('Content-Type', 'text/plain');
		$curl->setUserAgent($_SERVER['HTTP_USER_AGENT']);
		$curl->setOpt(CURLOPT_RETURNTRANSFER, true);
		$curl->setOpt(CURLOPT_FOLLOWLOCATION, true);
		$curl->post(self::API_URL, $post_data);
		dump($curl, true);
		if($curl->curl_error){
			$this->_last_error = $curl_error_message;
			$this->_last_error_type = 'network';
		}elseif($curl->http_error){
			$this->_last_error = $http_error_message;
			$this->_last_error_type = 'network';
		}
		$data = json_decode($curl->response, true);
		if (!empty($data['error'])) {
			$this->_last_error = sprintf('Error: %s', $data['error']);
			$this->_last_error_type = 'data';
		}

		$this->_response = new \App\Models\Epn\Response($data['results']);

		return $this->_last_error == '' ? TRUE : FALSE;
	}

	public function LastError() {
		return $this->_last_error;
	}

	public function LastErrorType() {
		return $this->_last_error_type;
	}

}
