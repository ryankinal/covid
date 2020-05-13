<?php
class HTTP
{
	protected $baseOptions = array(
		CURLOPT_RETURNTRANSFER => 1
	);
	protected $lastException;
	protected $curl;

	const FORMAT_JSON = 1;
	const FORMAT_WWW_FORM_URLENCODED = 2;
	const FORMAT_WWW_FORM_URLENCODED_NO_INDEXES = 3;
	const FORMAT_RAW = 4;

	public function __construct()
	{

	}

	private function curl($options = array())
	{
		$response = array();
		$curl = curl_init();

		try
		{
			curl_setopt_array($curl, $options);
			$data = curl_exec($curl);
			$info = curl_getinfo($curl);

			if (curl_errno($curl) == 0)
			{
				if ($decoded = json_decode($data, 1))
				{
					$data = $decoded;
				}

				$response['data'] = $data;
			}
			else
			{
				$response['data'] = array(
					'error' => curl_error($curl)
				);
			}

			$code = $info['http_code'];

			if ($code >= 200 && $code < 300)
			{
				$response['success'] = true;
			}
			else
			{
				$response['success'] = false;
			}

			$response['info'] = $info;
			$response['curl'] = $curl;
			$response['options'] = $options;
		}
		catch (Exception $e)
		{
			$response['exception'] = $e;
		}
		
		curl_close($curl);

		return $response;
	}

	protected function formatData($data, $format)
	{
		switch ($format)
		{
			case self::FORMAT_JSON:
				return json_encode($data);
			case self::FORMAT_WWW_FORM_URLENCODED:
				return http_build_query($data);
			case self::FORMAT_WWW_FORM_URLENCODED_NO_INDEXES:
				$parts = array();

				foreach ($data as $index => $value)
				{
					if (is_array($value))
					{
						foreach ($value as $v)
						{
							$parts[] = urlencode($index).'='.urlencode($v);
						}
					}
					else
					{
						$parts[] = urlencode($index).'='.urlencode($value);
					}
				}

				return implode('&', $parts);
		}
	}

	protected function getContentTypeHeader($format)
	{
		switch ($format)
		{
			case self::FORMAT_JSON:
				return 'application/json';
			case self::FORMAT_WWW_FORM_URLENCODED:
				return 'application/x-www-form-urlencoded';
		}
	}

	protected function formatHeaders($headers)
	{
		$formatted = array();

		foreach ($headers as $index => $header)
		{
			if (is_numeric($index))
			{
				$formatted[] = $header;
			}
			else
			{
				$formatted[] = $index.': '.$header;	
			}
		}

		return $formatted;
	}

	public function get($url, $query = array(), $headers = array())
	{
		if (is_array($url))
		{
			$options = $url;
			$url = $options['url'];
			$query = isset($options['query']) ? $options['query'] : array();
			$headers = isset($options['headers']) ? $options['headers'] : array();
		}

		$curlOptions = $this->baseOptions;

		if (is_array($query) && count($query) > 0)
		{
			$url .= '?'.$this->formatData($query, self::FORMAT_WWW_FORM_URLENCODED);
		}

		if (is_array($headers) && count($headers) > 0)
		{
			$curlOptions[CURLOPT_HTTPHEADER] = $this->formatHeaders($headers);
		}

		$curlOptions[CURLOPT_URL] = $url;

		return $this->curl($curlOptions);
	}

	public function put($url, $data = array(), $query = array(), $contentType = self::FORMAT_JSON, $headers = array())
	{
		if (is_array($url))
		{
			$options = $url;
			$url = $options['url'];
			$query = isset($options['query']) ? $options['query'] : array();
			$headers = isset($options['headers']) ? $options['headers'] : array();
			$contentType = isset($options['content-type']) ? $options['content-type'] : self::FORMAT_JSON;
			$data = isset($options['data']) ? $options['data'] : array();
		}

		if (is_array($query) && count($query) > 0)
		{
			$url .= '?'.$this->formatData($query, self::FORMAT_WWW_FORM_URLENCODED);
		}

		if (!isset($headers['Content-type']))
		{
			$headers['Content-type'] = $this->getContentTypeHeader($contentType);
		}

		$curlOptions = $this->baseOptions;
		$curlOptions[CURLOPT_CUSTOMREQUEST] = 'PUT';
		$curlOptions[CURLOPT_POSTFIELDS] = $this->formatData($data, $contentType);
		$curlOptions[CURLOPT_HTTPHEADER] = $this->formatHeaders($headers);
		$curlOptions[CURLOPT_POST] = 1;
		$curlOptions[CURLOPT_URL] = $url;

		return $this->curl($curlOptions);
	}

	public function patch($url, $data = array(), $query = array(), $contentType = self::FORMAT_JSON, $headers = array())
	{
		if (is_array($url))
		{
			$options = $url;
			$url = $options['url'];
			$query = isset($options['query']) ? $options['query'] : array();
			$headers = isset($options['headers']) ? $options['headers'] : array();
			$contentType = isset($options['content-type']) ? $options['content-type'] : self::FORMAT_JSON;
			$data = isset($options['data']) ? $options['data'] : array();
		}

		if (is_array($query) && count($query) > 0)
		{
			$url .= '?'.$this->formatData($query, self::FORMAT_WWW_FORM_URLENCODED);
		}

		if (!isset($headers['Content-type']))
		{
			$headers['Content-type'] = $this->getContentTypeHeader($contentType);
		}

		$curlOptions = $this->baseOptions;
		$curlOptions[CURLOPT_CUSTOMREQUEST] = 'PATCH';
		$curlOptions[CURLOPT_POSTFIELDS] = $this->formatData($data, $contentType);
		$curlOptions[CURLOPT_HTTPHEADER] = $this->formatHeaders($headers);
		$curlOptions[CURLOPT_POST] = 1;
		$curlOptions[CURLOPT_URL] = $url;

		return $this->curl($curlOptions);
	}

	public function post($url, $data = array(), $query = array(), $headers = array(), $contentType = self::FORMAT_JSON)
	{
		if (is_array($url))
		{
			$options = $url;
			$url = $options['url'];
			$data = isset($options['data']) ? $options['data'] : array();
			$query = isset($options['query']) ? $options['query'] : array();
			$headers = isset($options['headers']) ? $options['headers'] : array();
			$contentType = isset($options['content-type']) ? $options['content-type'] : self::FORMAT_JSON;
		}

		if (is_array($query) && count($query) > 0)
		{
			$url .= '?'.$this->formatData($query, self::FORMAT_WWW_FORM_URLENCODED);
		}

		if (!isset($headers['Content-type']))
		{
			$headers['Content-type'] = $this->getContentTypeHeader($contentType);
		}

		if (is_array($data))
		{
			$data = $this->formatData($data, $contentType);
		}

		$curlOptions = $this->baseOptions;
		$curlOptions[CURLOPT_POSTFIELDS] = $data;
		$curlOptions[CURLOPT_HTTPHEADER] = $this->formatHeaders($headers);
		$curlOptions[CURLOPT_POST] = 1;
		$curlOptions[CURLOPT_URL] = $url;

		return $this->curl($curlOptions);
	}

	public function delete($url, $data = array(), $query = array(), $contentType = self::FORMAT_JSON, $headers = array())
	{
		if (is_array($url))
		{
			$options = $url;
			$url = $options['url'];
			$query = isset($options['query']) ? $options['query'] : array();
			$headers = isset($options['headers']) ? $options['headers'] : array();
			$contentType = isset($options['content-type']) ? $options['content-type'] : self::FORMAT_JSON;
			$data = isset($options['data']) ? $options['data'] : array();
		}

		if (is_array($query) && count($query) > 0)
		{
			$url .= '?'.$this->formatData($query, self::FORMAT_WWW_FORM_URLENCODED);
		}

		if (!isset($headers['Content-type']))
		{
			$headers['Content-type'] = $this->getContentTypeHeader($contentType);
		}

		if (is_array($data))
		{
			$data = $this->formatData($data, $contentType);
		}

		$curlOptions = $this->baseOptions;
		$curlOptions[CURLOPT_POSTFIELDS] = $data;
		$curlOptions[CURLOPT_CUSTOMREQUEST] = 'DELETE';
		$curlOptions[CURLOPT_HTTPHEADER] = $this->formatHeaders($headers);
		$curlOptions[CURLOPT_URL] = $url;

		return $this->curl($curlOptions);
	}
}
?>