<?php

class BadRequestException extends Exception
{
}

class UnauthorizedException extends Exception
{
}

class NotFoundException extends Exception
{
}

class InternalException extends Exception
{
}

interface Optify
{
	public function whoAmI();
	public function getSites();
	public function getSite($siteToken);
	public function getVisitors($siteToken);
	public function getVisitor($siteToken, $visitorId);
}

class OptifyService implements Optify
{
	private $_url;
	private $_accessToken;
	private $_datetimeFormat;

	function __construct($accessToken)
	{
		$this->_accessToken = $accessToken;
		$this->_url = 'https://api.optify.net';
		$this->_datetimeFormat = 'Y-m-d H:i:s';
	}

	public function whoAmI()
	{
		$path = '/whoami';
		return $this->_get($path);
	}

	public function getSites()
	{
		$path = '/v1/sites';
		$response = $this->_get($path);
		return json_decode($response);
	}

	public function getSite($siteToken)
	{
		$path = '/v1/sites/' . $siteToken;
		$response = $this->_get($path);
		return json_decode($response);
	}

	public function getVisitors($siteToken, $params = array())
	{
		$q = array();
		if (array_key_exists('startDate', $params)) {
			$q['start_date'] = gmdate($this->_datetimeFormat, $params['startDate']);
		}

		if (array_key_exists('endDate', $params)) {
			$q['end_date'] = gmdate($this->_datetimeFormat, $params['endDate']);
		}

		if (array_key_exists('includeIsp', $params)) {
			$q['include_isp'] = $params['includeIsp'] ? 1 : 0;
		}

		if (array_key_exists('offset', $params)) {
			$q['offset'] = $params['offset'];
		}

		if (array_key_exists('count', $params)) {
			$q['count'] = $params['count'];
		}

		$path = '/v1/sites/' . $siteToken . '/visitors';
		$response = $this->_get($path, $q);
		return json_decode($response);
	}

	public function getVisitor($siteToken, $visitorId)
	{
		$path = '/v1/sites/' . $siteToken . '/visitors' . $visitorId;
		$response = $this->_get($path);
		return json_decode($response);
	}

	private function _get($path, $query = array())
	{
		$q = 'access_token=' . $this->_accessToken;
		while (list($k, $v) = each($query)) {
			$q .= '&' . urlencode($k) . '=' . urlencode($v);
		}

		$url = $this->_url . $path . '?' . $q;
		$c = curl_init($url);
		curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($c, CURLOPT_HTTPHEADER, array('Accept: application/json,text/plain', 'X-Optify-Client-Version: 1.0.0-PHP'));
		$response = curl_exec($c);
		$status = curl_getinfo($c, CURLINFO_HTTP_CODE);
		curl_close($c);

		print($url. "\n");
		print($status . "\n");

		switch ($status) {
		case 200:
			break;
		case 400:
			throw new BadRequestException();
		case 401:
			throw new UnauthorizedException();
		case 404:
			throw new NotFoundException();
		default:
			throw new InternalException();
		}

		return $response;
	}
}
