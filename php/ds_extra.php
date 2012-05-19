<?php
require_once('ds_config.php');
require_once('ds_common.php');
require_once('lib/xml.php');

//GET、POSTデータチェック
function ds_receive(){
	//RSS
	if(h($_GET['m']) == 'rss'){ ds_rss(); exit; }
	//LOGIN
	if(h($_POST['login']) == LOGIN){ $_SESSION['admin'] = true; }
	if($_POST['logout'] == 'logout'){ $_SESSION['admin'] = false; }
}

//admin時の機能表示
function ds_func(){
	if($_SESSION['admin']){
		$filename = tmpl_func;
		show_template($filename, null, ROOT_URL);
	}
}

//Pager取得
function ds_pager(){
	//登録数
	$count = (int)$_POST['cnt'];
	
	//DB登録
	$db = new DB;
	$db->DBConnect();
	//SQL生成
	$sqlbuilder = new SQLBuild(1); //select
	$sqlbuilder->setTable($weather_data["table"]); //テーブル
	$sqlbuilder->setAry($weather_data["column"]); //カラム、値
	$db->executeSQL($sqlbuilder->makeSQL());
	$db->DBClose();
}

//天気情報取得
function ds_weather1($id){
	global $weather_url, $weather_city, $weather_day;
	//天気情報登録
	$weather_data = array(
		"table" => "diary3_weather",
		"column" => array(
			"wid" => "",
			"w_type" => "",
			"w_temp_max" => "",
			"w_temp_min" => "",
			"w_imgurl" => "",
			"w_srcurl" => "",
			"w_description" => ""
		)
	);
	//XMLを取得
	$url = $weather_url;
	$url.= '?city='.$weather_city;
	$url.= '&day='.$weather_day;
	$xml = file_get_contents($url);
	//連想配列に変換
	$data = XML_unserialize($xml);
	//SQL配列に格納
	$weather_data["column"]["wid"] = $id;
	$weather_data["column"]["w_type"] = $data["lwws"]['telop'];
	$weather_data["column"]["w_temp_max"] = 
		$data["lwws"]['temperature']['max']['celsius'] ? $data["lwws"]['temperature']['max']['celsius'] : "--";
	$weather_data["column"]["w_temp_min"] = 
		$data["lwws"]['temperature']['min']['celsius'] ? $data["lwws"]['temperature']['min']['celsius'] : "--";
	$weather_data["column"]["w_imgurl"] = $data["lwws"]['image']['url'];
	$weather_data["column"]["w_srcurl"] = $data["lwws"]['image']['link'];
	$weather_data["column"]["w_description"] = $data["lwws"]['description'];

	//DB登録
	$db = new DB;
	$db->DBConnect();
	//SQL生成
	$sqlbuilder = new SQLBuild(3); //insert
	$sqlbuilder->setTable($weather_data["table"]); //テーブル
	$sqlbuilder->setAry($weather_data["column"]); //カラム、値
	$db->executeSQL($sqlbuilder->makeSQL());
	$db->DBClose();
}

//天気ポップアップ情報取得
function ds_weather2(){
	$weather_data = array(
		"table" => "diary3_weather",
		"column" => array("wid", "w_type", "w_temp_max", "w_temp_min", "w_imgurl", "w_srcurl", "w_description")
	);
	//値チェック
	if(!$_POST['wid']) return false;
	if(!$_POST['x']) return false;
	if(!$_POST['y']) return false;
	
	//DB登録
	$db = new DB;
	$db->DBConnect();
	//SQL生成
	$sqlbuilder = new SQLBuild(1); //select
	$sqlbuilder->setTable($weather_data["table"]); //テーブル
	$sqlbuilder->addSelectCols($weather_data["column"]); //カラム(引数なしだと全カラム)
	$sqlbuilder->setWhere("wid", str_replace("w_", "", $_POST['wid']));
	$db->executeSQL($sqlbuilder->makeSQL());
	$row = $db->executeFetch();
	//JSON化
	$data = array(
		"id" => $_POST['wid'],
		"type" => $row['w_type'],
		"temp_max" => $row['w_temp_max'],
		"temp_min" => $row['w_temp_min'],
		"imgurl" => $row['w_imgurl'],
		"srcurl" => $row['w_srcurl'],
		"description" => $row['w_description'],
		"left" => $_POST['x'],
		"top" => $_POST['y']
	);
	$db->DBClose();

	return $data;
}

//RSS表示
function ds_rss(){
	global $rss_data;
	header('content-type: text/xml; charset=utf-8');
	$channel = array(
		'about' => ROOT_URL.'rss.php', // RSSのURL
		'title' => NAME,               // サイト名
		'link' => ROOT_URL,            // サイトのURL
		'description' => DESCRIPT,
		'creator' => CREATOR
	);
	$tag_data = array(
		"table" => "tag_classify",
		"column" => "tname",
		"order" => "tid"
	);
	$db = new DB;
	$db->DBConnect();
	//SQL生成
	$sqlbuilder = new SQLBuild(1); //select
	$sqlbuilder->setTable($rss_data["table"]); //テーブル
	$sqlbuilder->addSelectCols($rss_data["column"]); //カラム(引数なしだと全カラム)
	$sqlbuilder->setOrderby($rss_data["order"]); //ORDER BY
	$sqlbuilder->setLimit(0, $rss_data["limit"]); //LIMIT
	$db->executeSQL($sqlbuilder->makeSQL());

	$i = 0;
	while($row = $db->executeFetch()){
		$tags = array();
		//タグを数値から文字列に変換
		$db_tag = new DB;
		$db_tag->DBConnect();
		//SQL生成
		$sqlbuilder2 = new SQLBuild(1); //select
		$sqlbuilder2->setTable($tag_data["table"]); //テーブル
		$sqlbuilder2->addSelectCols($tag_data["column"]); //カラム(引数なしだと全カラム)
		//タグを文字列に変換
		$tid = explode(",", $row['tag']);
		foreach($tid as $v){$sqlbuilder2->setWhereOr("tid", (int)$v);}
		$sqlbuilder2->setOrderby($tag_data["order"]); //ORDER BY
		$db_tag->executeSQL($sqlbuilder2->makeSQL());

		//取り出したタグを列挙
		$j = 0;
		while($row_tag = $db_tag->executeFetch()){
			$tags[$j] = $row_tag["tname"];
			$j++;
		}
		$db_tag->DBClose();

		//配列化
		$rss[$i] = array(
			'title' => $row['title'],
			'date' => $row['date'],
			'link'=> ROOT_URL."?id=".$row['id'],
			'description' => preg_replace('/\n/', '<br>', $row['description']),
			'subject' => implode(",", $tags)
		);
		$i++;
	}
	$db->DBClose();

	$filename = tmpl_rss;
	//テンプレート出力
	show_template($filename, array(
		'channel' => $channel,
		'rss' => $rss
		), 
		null
	);
}
?>