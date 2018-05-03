<?php
//通行证入口文件
define('IN_T',true);
require 'source/include/init.php';

//请求模块
$module = isset($_REQUEST['module']) ? Common::sfilter($_REQUEST['module']) : 'login';



if(file_exists($module_file= 'module/api/'.$module.'.php')){
    require $module_file;
    $tp->assign('module',$module);
    //$tp->display($_lang['moban'].'/passport.tpl');
}
else{
    die('禁止访问！');
}
?>