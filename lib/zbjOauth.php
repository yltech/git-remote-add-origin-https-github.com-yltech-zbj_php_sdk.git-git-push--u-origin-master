<?php
/**
 * 猪八戒API-OAuth2.0授权类
 * 
 * @author Ansion <1572309495@qq.com>
 * @since  2018-3-12
 */
class zbjOauth {
    
    public $authUrl = "http://openapi.zbj.com/oauth2/authorize";
	public $accessUrl = "http://openapi.zbj.com/oauth2/accesstoken";
	public $refreshUrl = "http://openapi.zbj.com/oauth2/refreshtoken";

    public function __construct() {
        if (!defined('APP_KEY') || empty(APP_KEY)) { 
			exit('APP_KEY not setting');
		}
		if (!defined('APP_SECRET') || empty(APP_SECRET)) { 
			exit('APP_SECRET not setting');
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
		$state = md5(time().mt_rand(1000, 9999));
		$_SESSION['zbj_state'] = $state;
        $url = $this->authUrl;
		$params = array(
			'client_id' => APP_KEY,
			'response_type' => 'code',
			'redirect_uri'  => URL_CALLBACK,
			'scope' => 'zbj.user.getUserBaseInfo-1.0+zbj.tradeOrder.queryOrderInfo-1.0',
			'display' => $type,
			'state' => $state
		);
		$url .= "?" . http_build_query($params);
        header("location:{$url}");
    }

    /**
     * GET 请求
     *
     * @param $url
     * @return mixed|string
     */
    private function send_request($url) {

        if (function_exists('curl_exec')) {

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, self::$connectTimeout);
            curl_setopt($ch, CURLOPT_TIMEOUT, self::$socketTimeout);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

            $data = curl_exec($ch);

            if (curl_errno($ch)) {
                $err = sprintf("curl[%s] error[%s]", $url, curl_errno($ch) . ':' . curl_error($ch));
                $this->triggerError($err);
            }

            curl_close($ch);
        } else {
            $opts    = array(
                'http' => array(
                    'method'  => "GET",
                    'timeout' => self::$connectTimeout + self::$socketTimeout,
                )
            );
            $context = stream_context_create($opts);
            $data    = file_get_contents($url, false, $context);
        }

        return $data;
    }

    /**
     *
     * @param       $url
     * @param array $postdata
     * @return mixed|string
     */
    private function post_request($url, $postdata = '') {
        if (!$postdata) {
            return false;
        }

        $data = http_build_query($postdata);
        if (function_exists('curl_exec')) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, self::$connectTimeout);
            curl_setopt($ch, CURLOPT_TIMEOUT, self::$socketTimeout);

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
                $opts    = array(
                    'http' => array(
                        'method'  => 'POST',
                        'header'  => "Content-type: application/x-www-form-urlencoded\r\n" . "Content-Length: " . strlen($data) . "\r\n",
                        'content' => $data,
                        'timeout' => self::$connectTimeout + self::$socketTimeout
                    )
                );
                $context = stream_context_create($opts);
                $data    = file_get_contents($url, false, $context);
            }
        }

        return $data;
    }
}
