<?php
/**
 * TOP SDK 入口文件
 * 请不要修改这个文件，除非你知道怎样修改以及怎样恢复
 * @author xuteng.xt
 */

/**
 * 定义常量开始
 * 在include("TopSdk.php")之前定义这些常量，不要直接修改本文件，以利于升级覆盖
 */
/**
 * SDK工作目录
 * 存放日志，TOP缓存数据
 */
if (!defined("TOP_SDK_WORK_DIR"))
{
	define("TOP_SDK_WORK_DIR", "/tmp/");
}

/**
 * 是否处于开发模式
 * 在你自己电脑上开发程序的时候千万不要设为false，以免缓存造成你的代码修改了不生效
 * 部署到生产环境正式运营后，如果性能压力大，可以把此常量设定为false，能提高运行速度（对应的代价就是你下次升级程序时要清一下缓存）
 */
if (!defined("TOP_SDK_DEV_MODE"))
{
	define("TOP_SDK_DEV_MODE", true);
}

if (!defined("TOP_AUTOLOADER_PATH"))
{
	define("TOP_AUTOLOADER_PATH", dirname(__FILE__));
}

/**
 * 封装Mob发送短信的方法
 *@param api:接口地址
 *@param appkey:Mob的AppKey
 *@param phone:请求手机号
 *@param zone:国家区号
 *@param code:验证码
*/
function Sms_mob($api, $appkey, $phone, $zone, $code)
{
    // 发送验证码
    $response = postRequest( $api . '/sms/verify', array(
        'appkey' => $appkey,
        'phone' => $phone,
        'zone' => $zone,
        'code' => $code,
    ) );

    //判断是否是一个合法的json
    if(analyJson($response)){
        $mobRes = analyJson($response);
    }else{
        $mobRes = array('status'=>0);
    }

    //判断手机验证码是否正确-写一个方法
    if ($mobRes['status'] == 200) {
        $res['errcode'] = 0;
        $res['phoneNumber'] = $phone;
        $res['errmsg'] = '信息核对成功！';
    }else{
        $res['errcode'] = 40006;
        $res['errmsg'] = '短信验证码不正确,请重新发送';
    }
    return urldecode(json_encode(urlencodeFun($res)));
}

/**
 * 发起一个post请求到指定接口
 *
 * @param string $api 请求的接口
 * @param array $params post参数
 * @param int $timeout 超时时间
 * @return string 请求结果
 */
function postRequest( $api, array $params = array(), $timeout = 30 ) {
    $ch = curl_init();
    curl_setopt( $ch, CURLOPT_URL, $api );
    // 以返回的形式接收信息
    curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
    // 设置为POST方式
    curl_setopt( $ch, CURLOPT_POST, 1 );
    curl_setopt( $ch, CURLOPT_POSTFIELDS, http_build_query( $params ) );
    // 不验证https证书
    curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, 0 );
    curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, 0 );
    curl_setopt( $ch, CURLOPT_TIMEOUT, $timeout );
    curl_setopt( $ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/x-www-form-urlencoded;charset=UTF-8',
        'Accept: application/json',
    ) );
    // 发送数据
    $response = curl_exec( $ch );
    // 不要忘记释放资源
    curl_close( $ch );
    return $response;
}

/**
 * 中文乱码
 */
function urlencodeFun(array $params = array()){
    foreach ( $params as $key => $value ) {
        $params[$key] = urlencode ( $value );
    }
    return $params;
}

/**
 * 解析json串
 * @param type $json_str
 * @return type
 */
function analyJson($json_str) {
    $json_str = str_replace('＼＼', '', $json_str);
    $out_arr = array();
    preg_match('/{.*}/', $json_str, $out_arr);
    if (!empty($out_arr)) {
        $result = json_decode($out_arr[0], TRUE);
    } else {
        return FALSE;
    }
    return $result;
}
