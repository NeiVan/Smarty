<?php
header("Content-type: application/json; charset=utf-8");
if(!defined('IN_T')){
    die('禁止访问！');
}
//接收的参数
$access_token =  Common::sfilter($_REQUEST['access_token']);

if($access_token=='feipai6') {
    $preparams = array(
        'phoneNumber' => empty($_REQUEST['phoneNumber']) ? "" : Common::sfilter($_REQUEST['phoneNumber']),
        'countryCode' => empty($_REQUEST['countryCode']) ? "" : Common::sfilter($_REQUEST['countryCode']),
        'smsNumber' => empty($_REQUEST['smsNumber']) ? "" : Common::sfilter($_REQUEST['smsNumber']),
        'email' => empty($_REQUEST['email']) ? "" : Common::sfilter($_REQUEST['email']),
        'flyControlSerialNumber' => empty($_REQUEST['flyControlSerialNumber']) ? "" : Common::sfilter($_REQUEST['flyControlSerialNumber']),
        'status' => empty($_REQUEST['status']) ? "" : Common::sfilter($_REQUEST['status']),
    );
    //判断用户国籍
    //判断是否是一个正确的手机号
    if (!Common::is_mobile($preparams['phoneNumber'])) {
        $res['errcode'] = 40001;
        $res['errmsg'] = '不是一个正确的手机号';
    }else if(mb_strlen($preparams['flyControlSerialNumber'])<=0||mb_strlen($preparams['flyControlSerialNumber'])>32){
        $res['errcode'] = 40008;
        $res['errmsg'] = '序列号无效或不匹配';
    } else {
        //判断手机号码是否注册过
        //判断表单是否填写完整
        if (mb_strlen($preparams['phoneNumber']) == "" || strlen($preparams['countryCode']) == "" || strlen($preparams['smsNumber']) == "" || strlen($preparams['email']) == "") {
            $res['errcode'] = 40002;
            $res['errmsg'] = '请填入完整的表单';
        }else if(!Common::is_email($preparams['email'])){
            $res['errcode'] = 40003;
            $res['errmsg'] = '请输入有效的邮箱';
        }else {
            $smsNumber = isset($_REQUEST['smsNumber']) ? strtolower(Common::sfilter($_REQUEST['smsNumber'])) : '';
            $api = array(
                'apiurl' => 'https://webapi.sms.mob.com',
                'appkey' => '2399ba11f61aa',
            );
            //发送验证码
            $response = postRequest('https://webapi.sms.mob.com/sms/verify', array(
                'appkey' => '2399ba11f61aa',
                'phone' => $preparams['phoneNumber'],
                'zone' => $preparams['countryCode'],
                'code' => $smsNumber,
            ));
            //判断是否是一个合法的json
            if(analyJson($response)){
                $mobRes = analyJson($response);
            }else{
                $mobRes = array('status'=>0);
            }
            //判断手机验证码是否正确-写一个方法
            if ($mobRes['status'] == 200) {
                $flydata['fly_user_phone']= $preparams['phoneNumber'];
                $flydata['fly_user_countrycode'] = $preparams['countryCode'];
                $flydata['fly_user_email']= $preparams['email'];
                $flydata['fly_control_serial_number']= $preparams['flyControlSerialNumber'];
                $flydata['status']= $preparams['status'];
                $flydata['create_time'] = date('Y-m-d H:i:s', Common::gmtime());
                $flydata['last_time'] = date('Y-m-d H:i:s', Common::gmtime());
                $list = get_serial_number($preparams['flyControlSerialNumber'],$preparams['phoneNumber']);
                //判断手机号，序列号是否是存在
                if($list['status']!=0){
                     if($list['status']==1){
                         $res['errcode'] = 40006;
                         $res['errmsg'] = '飞行器已被本手机激活';
                     }else if($list['status']==2){
                         $res['errcode'] = 40007;
                         $res['errmsg'] = '飞行器已被其他手机激活';
                     }
                }else{
                    //写入到数据库
                    $Db->insert($Base->table('flynumber_manager'),$flydata);
                    $res['errcode'] = 0;
                    $res['phoneNumber'] = $preparams['phoneNumber'];
                    $res['errmsg'] = '恭喜激活成功！';
                }
            }else if($mobRes['status'] == 467){
                $res['errcode'] = 40005;
                $res['errmsg'] = '请求校验验证码频繁';
            }
            else{
                $res['errcode'] = 40004;
                $res['errmsg'] = '短信验证码不正确,请重新发送';

            }
        }
    }
    echo urldecode(json_encode(urlencodeFun($res)));
    exit;
}else{
    $res=array(
        'errcode' =>40000,
        'errmsg' => 'invalid access_token!',
    );
    echo urldecode(json_encode(urlencodeFun($res)));
    exit;
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
 *  获得飞机序列号
 */
function get_serial_number($flyControlSerialNumber,$phoneNumber){
    $list_sql = "SELECT status,fly_control_serial_number ";
    $sql = "FROM ".$GLOBALS['Base']->table('flynumber_manager')." WHERE 1 ";
    //搜索手机号
    $sql .= "AND fly_user_phone='$phoneNumber' ";
    //搜索序列号
    $sqlnumber= "SELECT fly_control_serial_number FROM".$GLOBALS['Base']->table('flynumber_manager')." WHERE fly_control_serial_number='".$flyControlSerialNumber."'";
    $list = $GLOBALS['Db']->query($list_sql.$sql);
    $listnumber = $GLOBALS['Db']->query($sqlnumber);
    if(is_array($list)&&!empty($list)){
        //手机号有,查询序列号
        if(is_array($listnumber)&&!empty($listnumber)){
            //手机号无,查询序列号有,提示:飞行器已被本手机激活
            $res = array(
                'status' => 1,
            );
        }else{
            //手机号有，查询序列号无          写入
            $res = array(
                'status' => 0,
            );
        }
    }else{
        //手机号无,查询序列号
        if(is_array($listnumber)&&!empty($listnumber)){
            //手机号有,查询序列号有      飞行器已被其他手机激活
            $res = array(
                'status' => 2,
            );
        }else{
            //手机号无,查询序列号无,     写入
            $res = array(
                'status' => 0,
            );
        }

    }
    return $res;
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