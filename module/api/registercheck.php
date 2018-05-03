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
        'password' => empty($_REQUEST['password']) ? "" : Common::sfilter($_REQUEST['password']),
        'rePassword' => empty($_REQUEST['rePassword']) ? "" : Common::sfilter($_REQUEST['rePassword']),
    );
    //判断用户国籍
    //判断是否是一个正确的手机号
    if (!Common::is_mobile($preparams['phoneNumber'])) {
        $res['errcode'] = 40001;
        $res['errmsg'] = '不是一个正确的手机号';
    } else {
        //判断表单是否填写完整
        if (mb_strlen($preparams['password']) == "" || strlen($preparams['rePassword']) == "") {
            $res['errcode'] = 40003;
            $res['errmsg'] = '请填入完整的表单';
        }else if(strlen($preparams['password'])!=strlen($preparams['rePassword'])&&$preparams['password']!=$preparams['rePassword']){
            $res['errcode'] = 40004;
            $res['errmsg'] = '两次输入密码不一致';
        }else {
            $params['password'] = Common::encrypt($preparams['password']);
            //更新到数据库
            if ($Db->update($Base->table('user'),array('password'=>$params['password']),array('phone'=>$preparams['phoneNumber']))){
                $res['errcode'] = 0;
                $res['errmsg'] = '注册成功！';
            } else{
                $res['errcode'] = 40006;
                $res['errmsg'] = '密码跟上一次一致,请重新输入';
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
