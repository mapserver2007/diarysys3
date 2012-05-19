<?php
//Ajaxで受けたリクエストを各関数へ渡す
require_once('lib/json.php');
require_once('ds_config.php');
require_once('ds_common.php');
require_once('ds_entry.php');
require_once('ds_extra.php');
//require_once('ds_sidebar.php');

//POST
$pmode = $_POST['pmode']; //Pager
$tmode = $_POST['tmode']; //タグ
$wmode = $_POST['wmode']; //天気

//Pager関係
if($pmode == 'pager'){
	ds_pager();
}

//タグ関係
if($tmode == 'all'){
	$ary2json = ds_tag1(true); //一覧
}else if($tmode == 'new'){
	//新規タグ登録
	$commit = ds_tag2($_POST['tname']);
	$ary2json = ds_tag1($commit);
}

//天気関係
if($wmode == 'day'){
	$ary2json = ds_weather2();
}

//JSONに変換
$json = new Services_JSON; 
$encode = $json->encode($ary2json);
echo $encode;
?>