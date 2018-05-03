<?php
if(!defined('IN_T')){
    die('禁止访问！');
}

$act =  Common::sfilter($_REQUEST['act']);

$input=null;
if (empty($act)) {
    $input = $Json->decode(file_get_contents("php://input"));
    if (!empty($input)) {
        $act = $input['act'];
    }
}
//查询用户的手机号码
if($act=="get_info"){

    $serialnumber = Common::sfilter($_REQUEST['serialnumber']);
    $list = get_serial_number($serialnumber);
    echo $Json->encode($list);
    exit;
}

function get_serial_number($serialnumber){

    $list_sql = "SELECT * ";
    $sql = "FROM ".$GLOBALS['Base']->table('flynumber_manager')." WHERE 1 ";
    if (mb_strlen($serialnumber)<0||mb_strlen($serialnumber)>32) {
        $res['msg'] = "请输入0到32个长度的序列号";
        $res['status'] = 0;
        $res['list'] =array();
    }else{
        //搜索項目名稱
        $sql .= "AND fly_control_serial_number='$serialnumber' ";

        //echo $sql;
        $list = $GLOBALS['Db']->query($list_sql.$sql);

        $res = array(
            'msg' => "获取成功",
            'status' => 1,
            'list'=>$list
        );
    }
    return $res;
}

?>