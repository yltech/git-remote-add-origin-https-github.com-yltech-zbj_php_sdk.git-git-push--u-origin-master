# zbj_php_sdk

猪八戒开放平台通用API，用于开发者快速集成猪八戒平台接口，自行修改配置文件config.php即可使用，代码清晰简洁，一看即懂。

#Oauth2.0授权
$zbjOauth = new \zbjOauth($this->config);
$result = $zbjOauth->getToken();

#API调用
$zbjApi = new \zbjApi();
$result = $zbjApi->to("zbj.user.getServiceUserInfo", array(
  'openid' => $zbj_openid
));

#composer管理
命令：composer require yltech/zbj_php_sdk dev-master
地址：https://packagist.org/packages/yltech/zbj_php_sdk

------------------------------------------------------------
各类软硬件定制化开发 联系QQ：1572309495
