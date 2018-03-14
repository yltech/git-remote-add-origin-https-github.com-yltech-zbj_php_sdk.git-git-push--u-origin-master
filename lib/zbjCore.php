<?php

/**
 * 猪八戒API-OAuth2.0授权类
 * 
 * @author Ansion <1572309495@qq.com>
 * @since  2018-3-12
 */
class zbjOauth {
	//授权发起地址
	public $authUrl		 = "http://openapi.zbj.com/oauth2/authorize";
	//获取token
	public $accessUrl	 = "http://openapi.zbj.com/oauth2/accesstoken";
	//刷新token
	public $refreshUrl	 = "http://openapi.zbj.com/oauth2/refreshtoken";
	//应用配置
	public $config;
	/**
	 * 构造函数
	 * @param type $config
	 */
	public function __construct($config=[]) {
		if (empty($config)) {
			if (!defined('APP_KEY') || empty(APP_KEY)) {
				exit('APP_KEY not setting');
			}
			if (!defined('APP_SECRET') || empty(APP_SECRET)) {
				exit('APP_SECRET not setting');
			}
			$this->config = array(
				'app_key'	 => APP_KEY,
				'app_secret' => APP_SECRET,
				'url_callback' => URL_CALLBACK,
				'url_callmsg' => URL_CALLMSG
			);
		} elseif (!empty($config)) {
			if (empty($config['app_key'])) {
				exit('APP_KEY not setting');
			}
			if (empty($config['app_secret'])) {
				exit('APP_SECRET not setting');
			}
			$this->config = $config;
		}
		
	}

	/**
	 * 获取授权登录url
	 * 直接发布搭配猪八戒上的应用无需该操作
	 *
	 * @param null $user_id
	 * @return int
	 */
	public function toLogin($type = 'pc') {
		$state					 = md5(time() . mt_rand(1000, 9999));
		$_SESSION['zbj_state']	 = $state;
		$url					 = $this->authUrl;
		$params					 = array(
			'client_id'		 => $this->config['app_key'],
			'response_type'	 => 'code',
			'redirect_uri'	 => $this->config['url_callback'],
			'scope'			 => 'zbj.user.getUserBaseInfo-1.0+zbj.tradeOrder.queryOrderInfo-1.0',
			'display'		 => $type,
			'state'			 => $state
		);
		$url					 .= "?" . http_build_query($params);
		header("location:{$url}");
	}

	/**
	 * 获取access_token
	 * 发布到工具市场（pc端或移动端）的应用，其访问令牌在授权服务端的有效时长等同于购买时长，因此不存在此刷新操作。
	 */
	public function getToken() {
		$state = $_GET['state'];
		$code = $_GET['code'];
		/* 猪八戒应用无需验证state
		 * if ($state != $_SESSION['zbj_state']) {
			exit('state不一致，非法');
		}*/
		if (empty($code)) {
			exit('错误：code');
		}
		//获取access_token
		$postdata = array(
			'client_id'		 => $this->config['app_key'],
			'grant_type'	 => 'authorization_code',
			'redirect_uri'	 => $this->config['url_callback'],
			'code'		 => $code,
		);
		$postdata = $this->makeSign($postdata);
		$result = $this->post_request($this->accessUrl, $postdata);
		if (empty($result) || empty($result['access_token'])) {
			echo "<pre>";
			print_r($result);
			exit();
		}
		$expires_in = time()+intval($result['expires_in']);
		setcookie('zbj_access_token', $result['access_token'], $expires_in);
		setcookie('zbj_openid', $result['openid'], $expires_in);
		setcookie('zbj_auth_all', json_encode($result), $expires_in);
		return $result;
	}
	/**
	 * 生成签名
	 */
	private function makeSign($data) {
		ksort($data);
		$str = $this->config['app_secret'];
		foreach ($data as $k => $v) {
			$str .= $k . $v;
		}
		$str .= $this->config['app_secret'];
		$client_secret = strtoupper(sha1($str));
		$data['client_secret'] = $client_secret;
		return $data;
	}
	/**
	 * GET 请求
	 *
	 * @param $url
	 * @return mixed|string
	 */
	public function get_request($url) {

		if (function_exists('curl_exec')) {

			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 60);
			curl_setopt($ch, CURLOPT_TIMEOUT, 60);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

			$data = curl_exec($ch);

			if (curl_errno($ch)) {
				$err = sprintf("curl[%s] error[%s]", $url, curl_errno($ch) . ':' . curl_error($ch));
				$this->triggerError($err);
			}

			curl_close($ch);
		} else {
			$opts	 = array(
				'http' => array(
					'method'	 => "GET",
					'timeout'	 => 60 + 60,
				)
			);
			$context = stream_context_create($opts);
			$data	 = file_get_contents($url, false, $context);
		}
		if (!is_array($data) || json_decode($data, true)) {
			$data = json_decode($data, true);
		}
		return $data;
	}

	/**
	 *
	 * @param       $url
	 * @param array $postdata
	 * @return mixed|string
	 */
	public function post_request($url, $postdata = '') {
		if (!$postdata) {
			return false;
		}

		$data = http_build_query($postdata);
		if (function_exists('curl_exec')) {
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 60);
			curl_setopt($ch, CURLOPT_TIMEOUT, 60);

			//不可能执行到的代码
			if (!$postdata) {
				curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
			} else {
				curl_setopt($ch, CURLOPT_POST, 1);
				curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
			}
			$data = curl_exec($ch);

			if (curl_errno($ch)) {
				$err = sprintf("curl[%s] error[%s]", $url, curl_errno($ch) . ':' . curl_error($ch));
				$this->triggerError($err);
			}

			curl_close($ch);
		} else {
			if ($postdata) {
				$opts	 = array(
					'http' => array(
						'method'	 => 'POST',
						'header'	 => "Content-type: application/x-www-form-urlencoded\r\n" . "Content-Length: " . strlen($data) . "\r\n",
						'content'	 => $data,
						'timeout'	 => 60 + 60
					)
				);
				$context = stream_context_create($opts);
				$data	 = file_get_contents($url, false, $context);
			}
		}
		if (!is_array($data) || json_decode($data, true)) {
			$data = json_decode($data, true);
		}
		return $data;
	}
}

/**
 * 猪八戒API-业务API主入口
 * 
 * @author Ansion <1572309495@qq.com>
 * @since  2018-3-12
 */
class zbjApi {
	//接口统一请求地址
	public $ApiUrl		 = "http://openapi.zbj.com/router";
	//返回数据格式，json,xml
	public $format = "json";
	//相应错误语种设置，en
	public $errLang = "zh_CN";
	//app设置
	public $config;
	/**
	 * 构造函数
	 * @param type $config
	 */
	public function __construct($config=[], $format=null, $errLang=null) {
		if (empty($config)) {
			if (!defined('APP_KEY') || empty(APP_KEY)) {
				exit('APP_KEY not setting');
			}
			if (!defined('APP_SECRET') || empty(APP_SECRET)) {
				exit('APP_SECRET not setting');
			}
			$this->config = array(
				'app_key'	 => APP_KEY,
				'app_secret' => APP_SECRET,
				'url_callback' => URL_CALLBACK,
				'url_callmsg' => URL_CALLMSG
			);
		} elseif (!empty($config)) {
			if (empty($config['app_key'])) {
				exit('APP_KEY not setting');
			}
			if (empty($config['app_secret'])) {
				exit('APP_SECRET not setting');
			}
			$this->config = $config;
		}
		if (!empty($format)) {
			$this->format = $format;
		}
		if (!empty($errLang)) {
			$this->errLang = $errLang;
		}
	}
	/**
	 * 执行接口
	 */
	public function to($method, $params=[], $isPost=true){
		if (empty($method) || !is_string($method)) {
			return false;
		}
		if (empty($params) || !is_array($params)) {
			return false;
		}
		$accessToken = $_COOKIE['zbj_access_token'];
		$openid = $_COOKIE['zbj_openid'];
		if (empty($accessToken) || empty($openid)) {
			exit("授权过期或失效，请重新授权");
		}
		//补齐公共参数
		$comParams = array(
			'appKey' => $this->config['app_key'],
			'accessToken' => $accessToken,
			'method' => $method,
			'format' => $this->format,
			'locale' => $this->errLang,
			'timestamp' => $this->getMillisecond(),
		);
		//合并参数
		$params = array_merge($params,$comParams);
		if (empty($params['v'])) {
			//默认 版本号
			$params['v'] = "1.0";
		}
		//生成鉴权参数
		$signArray = $comParams;
		$signArray['method'] = $method;
		$signArray['openid'] = $openid;
		$signArray['v'] = $params['v'];
		$sign = $this->makeSign($signArray);
		////////////////////////
		$params['sign'] = $sign;
		$zbjOauth = new zbjOauth($this->config);
		if ($isPost) {
			return $zbjOauth->post_request($this->ApiUrl, $params);
		} else {
			return $zbjOauth->get_request($this->ApiUrl, $params);
		}
	}
	/**
	 * 获取13位时间戳
	 * @return type
	 */
	private function getMillisecond() { 
		list($t1, $t2) = explode(' ', microtime()); 
		return (float)sprintf('%.0f',(floatval($t1)+floatval($t2))*1000); 
	}
	/**
	 * 生成签名
	 */
	private function makeSign($data) {
		ksort($data);
		$str = $this->config['app_secret'];
		foreach ($data as $k => $v) {
			$str .= $k . $v;
		}
		$str .= $this->config['app_secret'];
		$sign = strtoupper(sha1($str));
		return $sign;
	}
}
