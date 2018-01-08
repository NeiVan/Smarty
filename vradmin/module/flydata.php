<?php
//飞机数据管理
//@author  #qq.com
//@date 6.1.2018
if(!defined('IN_T')){
    die('禁止访问！');
}
$act = Common::sfilter($_REQUEST['act']);

$tp->assign('nav','飞机数据管理');
$tp->assign('act',$act);


