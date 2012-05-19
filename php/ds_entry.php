<?php
require_once('ds_config.php');
require_once('ds_common.php');
require_once('ds_extra.php');
//require_once('HTMLPurifier.auto.php');

//エントリフラグチェック
function ds_entry(){
	global $entrymode;
	//管理者かどうかチェック
	if($_SESSION['admin']){
		//状態設定
		if(h($_GET['m']) == "write"){ $entrymode = 1; }
		//日記登録
		else if($_POST['m'] == 'entry'){ $entrymode = 2; }
		//日記編集(フォーム表示)
		else if($_GET['m'] == "revision"){ $entrymode = 3; }
		//日記編集(登録処理)
		else if($_POST['m'] == "revision"){ $entrymode = 4; }
		//日記削除(前処理)
		else if($_GET['m'] == "delete")  { $entrymode = 5; }
		//日記削除(削除処理)
		else if($_POST['m'] == "delete")  { $entrymode = 6; }
	}
	//管理者でなくても実行可能
	//タグからエントリ表示
	if($_GET['m'] == "tag"){ $entrymode = 7; }
	//月別エントリ表示
	if($_GET['m'] == "month"){ $entrymode = 8; }

	if($entrymode == 0){ //通常表示
		ds_entry1();
	}else if($entrymode == 1){ //フォーム表示
		ds_entry2();
	}else if($entrymode == 2){ //フォーム再表示or登録成功表示
		ds_entry3();
	}else if($entrymode == 3){ //日記の編集フォーム表示
		ds_entry4();
	}else if($entrymode == 4){ //日記の編集処理
		ds_entry5();
	}else if($entrymode == 5){ //日記の削除フォーム表示
		ds_entry6();
	}else if($entrymode == 6){ //日記の削除処理
		ds_entry7();
	}else if($entrymode == 7){ //タグからエントリ表示
		ds_entry8();
	}else if($entrymode == 8){ //月別エントリ表示
		ds_entry9();
	}
}

//エントリを通常表示
function ds_entry1($mode = "entry", $data = null){
	global $entry_data, $entry_num;
	//タグ(tid->tname)
	$tag_data = array(
		"table" => "tag_classify",
		"column" => array("tid", "tname"),
		"order" => "tid"
	);
	//天気
	$weather_data = array(
		"table" => "diary3_weather",
		"column" => array("wid","w_type","w_imgurl","w_srcurl","w_description"),
		"where" => "wid"
	);
	//登録数
	$count_data = array(
		"table" => "diary3",
		"column" => array("count" => "count(id)")
	);
	
	//GETデータ
	$get_id = isset($_GET['id']) ? 
		array("key" => "id", "val" => (int)$_GET['id']) : null; //IDを受けた時
	//ページ番号
	//$data_page = isset($_GET["page"]) ? ((int)$_GET["page"] - 1) * $entry_num : 0;
	$crt_page = isset($_GET["page"]) ? (int)$_GET["page"] : 1; //現在のページ番号(1からスタート)
	$data_page = is_int($crt_page) ? ($crt_page - 1) * $entry_num : 0;
	//タグからエントリ表示
	$data_tag = isset($data["tag"]) ? $data["tag"] : null;
	//deleteのパスチェック
	$data_err = isset($data["delete"]) ? $data["delete"] : null;
	//月別アーカイブ
	$data_arc = isset($data["month"]) ? $data["month"] : null;

	$db = new DB;
	$db->DBConnect();
	//登録件数を取得
	$sqlbuilder = new SQLBuild(1); //select
	$sqlbuilder->setTable($count_data["table"]); //テーブル
	$sqlbuilder->addSelectCols($count_data["column"]); //カラム(引数なしだと全カラム)
	//GETデータの場合
	if($get_id){$sqlbuilder->setWhere($get_id["key"], $get_id["val"]);}
	//タグから表示の場合
	else if($data_tag){ foreach($data_tag as $eid) $sqlbuilder->setWhereOr("id", $eid);}
	//月別アーカイブの場合
	else if($data_arc){ $sqlbuilder->setWherelike($data_arc["col"], $data_arc["val"]); };
	$db->executeSQL($sqlbuilder->makeSQL());
	$row = $db->executeFetch();
	$count = $row["count"];

	//SQL生成
	$sqlbuilder = new SQLBuild(1); //select
	$sqlbuilder->setTable($entry_data["table"]); //テーブル
	$sqlbuilder->addSelectCols($entry_data["column"]); //カラム(引数なしだと全カラム)
	/* 条件 */
	//GETデータの場合
	if($get_id){$sqlbuilder->setWhere($get_id["key"], $get_id["val"]);}
	//タグから表示の場合
	else if($data_tag){ foreach($data_tag as $eid) $sqlbuilder->setWhereOr("id", $eid);}
	//月別アーカイブの場合
	else if($data_arc){ $sqlbuilder->setWherelike($data_arc["col"], $data_arc["val"]); };
	$sqlbuilder->setOrderby($entry_data["order"]); //ORDER BY
	$sqlbuilder->setLimit($data_page, $entry_num);
	$db->executeSQL($sqlbuilder->makeSQL());
	//エントリ内容を取得
	$i = 0;
	while($row = $db->executeFetch()){
		$tags = array();
		//タグ取得
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
		//取り出したタグを配列化
		$j = 0;
		while($row_tag = $db_tag->executeFetch()){
			$tags[$j] = array(
				"tid" => $row_tag["tid"],
				"tname" => $row_tag["tname"]
			);
			$j++;
		}
		//天気取得
		$sqlbuilder3 = new SQLBuild(1); //select
		$sqlbuilder3->setTable($weather_data["table"]); //テーブル
		$sqlbuilder3->addSelectCols($weather_data["column"]); //カラム(引数なしだと全カラム)
		$sqlbuilder3->setWhere($weather_data["where"], $row['id']); //条件
		$db_tag->executeSQL($sqlbuilder3->makeSQL());
		$w_items = $db_tag->executeFetch();
		
		$db_tag->DBClose();
		
		//管理者であれば編集を許可
		$admin = $_SESSION['admin'] ? 
			($mode == "entry" ? true : false) : false;

		//配列化
		$items[$i] = array(
			"id" => $row["id"],
			"title" => $row["title"],
			"description" => preg_replace('/\r\n/', '<br />', stripslashes($row["description"])),
			"tag" => $tags,
			"date" => $row["date"],
			"weather" => $w_items,
			"admin" => $admin,
			"mode" => $mode, //entry, edit, delete
			"error" => $data_err, //削除パス失敗時
			"count" => array( //Pager用
				"start" => $entry_num, //表示件数
				"all"   => $count,     //全件数
				"crtpage" => $crt_page //現在のページ番号
			)
		);
		$i++;
	}
	$db->DBClose();

	if(isset($items) == false){
		echo 'エントリはありません';
	}else{
		$filename = tmpl_entry;
		show_template($filename, $items, ROOT_URL);
	}
}

//登録フォーム表示
function ds_entry2(){
	$filename = tmpl_entryform;
	show_template($filename, null, null);
}

//日記登録
function ds_entry3(){
	global $entry_reg_data, $recent_data;
	$err = 0;
	$err_items = array(
		"Passwd" => array(
			"Message" => ERR_PASSWD,
			"Flg" => false,
			"Value" => ""
		),
		"Title" => array(
			"Message" => ERR_TITLE,
			"Flg" => false,
			"Value" => ""
		),
		"Description" => array(
			"Message" => ERR_DESCRIPTION,
			"Flg" => false,
			"Value" => ""
		),
		"Tag" => array(
			"Message" => ERR_TAG,
			"Flg" => false,
			"Value" => ""
		)
	);
	//パスワードチェック
	if($_POST['ds_passwd'] != PASSWD){
		$err_items["Passwd"]["Flg"] = true;
		$err++;
	}
	//タイトルチェック
	if(!$_POST['ds_title']){
		$err_items["Title"]["Flg"] = true;
		$err++;
	}else{
		$entry_reg_data["entry"]["column"]["title"] = h($_POST['ds_title']);
		$err_items["Title"]["Value"] = h($_POST['ds_title']);
	}
	//内容チェック
	if(!$_POST['ds_description']){
		$err_items["Description"]["Flg"] = true;
		$err++;
	}else{
		$entry_reg_data["entry"]["column"]["description"] = h($_POST['ds_description']);
		$err_items["Description"]["Value"] = h($_POST['ds_description']);
	}
	//タグチェック
	if(!$_POST['ds_tag1'] && !$_POST['ds_tag2'] && !$_POST['ds_tag3']
		&& !$_POST['ds_tag4'] && !$_POST['ds_tag5']){
		$err_items["Tag"]["Flg"] = true;
		$err++;
	}else{
		$tmp_array = array();
		if($_POST['ds_tag1']){ $tmp_array[] = $_POST['ds_tag1'];}
		if($_POST['ds_tag2']){ $tmp_array[] = $_POST['ds_tag2'];}
		if($_POST['ds_tag3']){ $tmp_array[] = $_POST['ds_tag3'];}
		if($_POST['ds_tag4']){ $tmp_array[] = $_POST['ds_tag4'];}
		if($_POST['ds_tag5']){ $tmp_array[] = $_POST['ds_tag5'];}
		//カンマ区切り
		$entry_reg_data["entry"]["column"]["tag"] = implode(",", $tmp_array);
		
		//選択済みのタグを問い合わせ
		$tags = array();
		$tag_data = array(
			"table" => "tag_classify",
			"column" => array("tid", "tname"),
			"order" => "tid"
		);
		//タグ取得
		$db_tag = new DB;
		$db_tag->DBConnect();
		//SQL生成
		$sqlbuilder2 = new SQLBuild(1); //select
		$sqlbuilder2->setTable($tag_data["table"]); //テーブル
		$sqlbuilder2->addSelectCols($tag_data["column"]); //カラム(引数なしだと全カラム)
		//タグを文字列に変換
		foreach($tmp_array as $v){$sqlbuilder2->setWhereOr("tid", (int)$v);}
		$sqlbuilder2->setOrderby($tag_data["order"]); //ORDER BY
		$db_tag->executeSQL($sqlbuilder2->makeSQL());
		//取り出したタグを配列化
		$j = 0;
		while($row_tag = $db_tag->executeFetch()){
			$tags[$j] = array(
				"tid" => $row_tag["tid"],
				"tname" => $row_tag["tname"]
			);
			$j++;
		}
		$db_tag->DBClose();

		$err_items["Tag"]["Value"] = $tags;
	}
	
	//エラーがあればエラー状態出力して
	//入力画面に戻る
	if($err > 0){
		$filename = tmpl_entryform;
		show_template($filename, $err_items, null);
		return false;
	}
	$err_items = "";
	
	//日付
	$entry_reg_data["entry"]["column"]["date"] = date("YmdHis");
	
	//登録処理
	$db = new DB;
	$db->DBConnect();
	$sqlbuilder = new SQLBuild(3); //insert
	$sqlbuilder->setTable($entry_reg_data["entry"]["table"]); //テーブル
	$sqlbuilder->setAry($entry_reg_data["entry"]["column"]); //カラム、値
	$db->executeSQL($sqlbuilder->makeSQL());

	//天気情報を登録
	//今登録したデータのIDを外部キーとする
	$sqlbuilder4 = new SQLBuild(1); //select
	$sqlbuilder4->setTable($recent_data["table"]); //テーブル
	$sqlbuilder4->addSelectCols("id"); //カラム(引数なしだと全カラム)
	$sqlbuilder4->setOrderby($recent_data["order"]); //ORDER BY
	$sqlbuilder4->setLimit(0, 1); //1件だけ取り出す
	$db->executeSQL($sqlbuilder4->makeSQL());
	$row = $db->executeFetch();
	//IDを渡す
	ds_weather1($row['id']);
	
	$db->DBClose();
	
	//リファレンスを更新
	$tags = explode(",", $entry_reg_data["entry"]["column"]["tag"]); //登録タグ
	//$tref = array();
	$db = new DB;
	$db->DBConnect();
	foreach($tags as $key => $value){
		//該当タグのリファレンスを取得
		$sqlbuilder2 = new SQLBuild(1); //select
		$sqlbuilder2->setTable($entry_reg_data["reference"]["table"]); //テーブル
		$sqlbuilder2->addSelectCols($entry_reg_data["reference"]["column"]); //カラム(引数なしだと全カラム)
		$sqlbuilder2->setWhere("tid", $value); //条件
		$db->executeSQL($sqlbuilder2->makeSQL());
		$row = $db->executeFetch();
		//更新処理
		$sqlbuilder3 = new SQLBuild(4); //update
		$sqlbuilder3->setTable($entry_reg_data["reference"]["table"]);
		$sqlbuilder3->setLtr("reference", ((int)$row["reference"] + 1));
		$sqlbuilder3->setWhere("tid", $value);
		$db->executeSQL($sqlbuilder3->makeSQL());
	}
	$db->DBClose();
	
	//未登録タグ(reference=0)を削除
	ds_tag3();
	
	
	//ユニオンを更新
	//保留～

	//登録成功のばあいは通常表示
	ds_entry1();
}

//日記編集フォーム表示
function ds_entry4(){
	global $entry_data;
	//タグ
	$tag_data = array(
		"table" => "tag_classify",
		"column" => array("tid", "tname"),
		"order" => "tid"
	);
	//エラーデータ
	$err = 0;
	$err_items = array(
		"Passwd" => array(
			"Message" => ERR_PASSWD,
			"Flg" => false,
			"Value" => ""
		),
		"Id" => array(
			"Message" => ERR_ID,
			"Flg" => false,
			"Value" => ""
		),
		"Title" => array(
			"Message" => ERR_TITLE,
			"Flg" => false,
			"Value" => ""
		),
		"Description" => array(
			"Message" => ERR_DESCRIPTION,
			"Flg" => false,
			"Value" => ""
		),
		"Tag" => array(
			"Message" => ERR_TAG,
			"Flg" => false,
			"Value" => ""
		)
	);
	//GETデータ(初回時)
	$get_id = isset($_GET['id']) ? 
		array("key" => "id", "val" => (int)$_GET['id']) : null; //IDを受けた時
	//POSTデータ(登録失敗時)
	//$post_id = (int)$_POST['id'];

	if($get_id){
		$db = new DB;
		$db->DBConnect();
		//SQL生成
		$sqlbuilder = new SQLBuild(1); //select
		$sqlbuilder->setTable($entry_data["table"]); //テーブル
		$sqlbuilder->addSelectCols($entry_data["column"]); //カラム(引数なしだと全カラム)
		if($get_id) $sqlbuilder->setWhere($get_id["key"], $get_id["val"]); //条件
		//$sqlbuilder->setOrderby($entry_data["order"]); //ORDER BY
		$db->executeSQL($sqlbuilder->makeSQL());
		//エントリ内容を取得
		$row = $db->executeFetch();

		$tags = array();
		//タグ取得
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
		//取り出したタグを配列化
		$j = 0;
		while($row_tag = $db_tag->executeFetch()){
			$tags[$j] = array(
				"tid" => $row_tag["tid"],
				"tname" => $row_tag["tname"]
			);
			$j++;
		}
		$db_tag->DBClose();
		
		//配列化
		$items = array(
			"id" => $row["id"],
			"title" => $row["title"],
			"description" => $row["description"],
			"tag" => $tags
		);
		$db->DBClose();
	}
	
	//パスワードチェック
	//初回(編集開始状態)のときはログインパスチェックはしない
	if($_POST['ds_passwd'] != PASSWD && !$get_id){
		$err_items["Passwd"]["Flg"] = true;
		$err++;
	}
	//IDチェック
	if(!$items["id"]){
		$err_items["Id"]["Flg"] = true;
		$err++;
	}else{
		$err_items["Id"]["Value"] = $items['id'];
	}
	//タイトルチェック
	if(!$items['title']){
		$err_items["Title"]["Flg"] = true;
		$err++;
	}else{
		$err_items["Title"]["Value"] = $items['title'];
	}
	//内容チェック
	if(!$items['description']){
		$err_items["Description"]["Flg"] = true;
		$err++;
	}else{
		$err_items["Description"]["Value"] = $items['description'];
	}
	//タグチェック
	if(!$items['tag']){
		$err_items["Tag"]["Flg"] = true;
		$err++;
	}else{
		$err_items["Tag"]["Value"] = $items['tag'];
	}
	
	//エラーがあればエラー状態出力して
	//入力画面に戻る(通常であればここは通らない)
	if($err > 0){
		$filename = tmpl_entryedit;
		show_template($filename, $err_items, null);
		return false;
	}

	if(isset($items) == false){
		echo 'エントリはありません';
	}else{
		$filename = tmpl_entryedit;
		show_template($filename, $err_items, ROOT_URL);
	}
}

//日記編集(登録処理)
function ds_entry5(){
	global $entry_reg_data;
	//タグ
	$tag_data = array(
		"table" => "tag_classify",
		"column" => array("tid", "tname"),
		"order" => "tid"
	);
	//エラーデータ
	$err = 0;
	$err_items = array(
		"Passwd" => array(
			"Message" => ERR_PASSWD,
			"Flg" => false,
			"Value" => ""
		),
		"Id" => array(
			"Message" => ERR_ID,
			"Flg" => false,
			"Value" => ""
		),
		"Title" => array(
			"Message" => ERR_TITLE,
			"Flg" => false,
			"Value" => ""
		),
		"Description" => array(
			"Message" => ERR_DESCRIPTION,
			"Flg" => false,
			"Value" => ""
		),
		"Tag" => array(
			"Message" => ERR_TAG,
			"Flg" => false,
			"Value" => ""
		)
	);
	
	//POSTデータ
	$post_id = (int)$_POST['id']; //update時のキー

	if($post_id){
		//POSTデータセット
		//パスワードチェック
		if($_POST['ds_passwd'] != PASSWD){
			$err_items["Passwd"]["Flg"] = true;
			$err++;
		}
		//IDチェック
		if(!$post_id){
			$err_items["Id"]["Flg"] = true;
			$err++;
		}else{
			$err_items["Id"]["Value"] = $post_id;
		}
		//タイトルチェック
		if(!$_POST['ds_title']){
			$err_items["Title"]["Flg"] = true;
			$err++;
		}else{
			$entry_reg_data["edit"]["column"]["title"] = h($_POST['ds_title']);
			$err_items["Title"]["Value"] = $_POST['ds_title'];
		}
		//内容チェック
		if(!$_POST['ds_description']){
			$err_items["Description"]["Flg"] = true;
			$err++;
		}else{
			$entry_reg_data["edit"]["column"]["description"] = h($_POST['ds_description']);
			$err_items["Description"]["Value"] = $_POST['ds_description'];
		}
		//タグチェック
		if(!$_POST['ds_tag1'] && !$_POST['ds_tag2'] && !$_POST['ds_tag3']
			&& !$_POST['ds_tag4'] && !$_POST['ds_tag5']){
			$err_items["Tag"]["Flg"] = true;
			$err++;
		}else{
			$tmp_array = array();
			if($_POST['ds_tag1']){ $tmp_array[] = $_POST['ds_tag1'];}
			if($_POST['ds_tag2']){ $tmp_array[] = $_POST['ds_tag2'];}
			if($_POST['ds_tag3']){ $tmp_array[] = $_POST['ds_tag3'];}
			if($_POST['ds_tag4']){ $tmp_array[] = $_POST['ds_tag4'];}
			if($_POST['ds_tag5']){ $tmp_array[] = $_POST['ds_tag5'];}
			//カンマ区切り
			$entry_reg_data["edit"]["column"]["tag"] = implode(",", $tmp_array);
			//フォーム再表示用処理
			$tags = array();
			//タグ取得
			$db_tag = new DB;
			$db_tag->DBConnect();
			//SQL生成
			$sqlbuilder2 = new SQLBuild(1); //select
			$sqlbuilder2->setTable($tag_data["table"]); //テーブル
			$sqlbuilder2->addSelectCols($tag_data["column"]); //カラム(引数なしだと全カラム)
			//タグを文字列に変換
			$tid = explode(",", $entry_reg_data["edit"]["column"]["tag"]);
			foreach($tid as $v){$sqlbuilder2->setWhereOr("tid", (int)$v);}
			$sqlbuilder2->setOrderby($tag_data["order"]); //ORDER BY
			$db_tag->executeSQL($sqlbuilder2->makeSQL());
			//取り出したタグを配列化
			$j = 0;
			while($row_tag = $db_tag->executeFetch()){
				$tags[$j] = array(
					"tid" => $row_tag["tid"],
					"tname" => $row_tag["tname"]
				);
				$j++;
			}
			$db_tag->DBClose();
			$err_items["Tag"]["Value"] = $tags;
		}
		
		//エラーがあればエラー状態出力して
		//入力画面に戻る(通常であればここは通らない)
		if($err > 0){
			$filename = tmpl_entryedit;
			show_template($filename, $err_items, null);
			return false;
		}

		//選択済みのタグを問い合わせ
		$tags = array();
		$tag_data = array(
			"table" => "tag_classify",
			"column" => array("tid", "tname"),
			"order" => "tid"
		);
		//タグ取得
		$db_tag = new DB;
		$db_tag->DBConnect();
		//SQL生成
		$sqlbuilder2 = new SQLBuild(1); //select
		$sqlbuilder2->setTable($tag_data["table"]); //テーブル
		$sqlbuilder2->addSelectCols($tag_data["column"]); //カラム(引数なしだと全カラム)
		//タグを文字列に変換
		foreach($tmp_array as $v){$sqlbuilder2->setWhereOr("tid", (int)$v);}
		$sqlbuilder2->setOrderby($tag_data["order"]); //ORDER BY
		$db_tag->executeSQL($sqlbuilder2->makeSQL());
		//取り出したタグを配列化
		$j = 0;
		while($row_tag = $db_tag->executeFetch()){
			$tags[$j] = array(
				"tid" => $row_tag["tid"],
				"tname" => $row_tag["tname"]
			);
			$j++;
		}
		$db_tag->DBClose();
		
		//タグreference更新前処理
		$tag_data_check = array(
			"table" => "diary3",
			"column" => "tag"
		);
		//タグ取得
		$db_tag2 = new DB;
		$db_tag2->DBConnect();
		//SQL生成
		$sqlbuilder3 = new SQLBuild(1); //select
		$sqlbuilder3->setTable($tag_data_check["table"]); //テーブル
		$sqlbuilder3->addSelectCols($tag_data_check["column"]); //カラム(引数なしだと全カラム)
		$sqlbuilder3->setWhere("id", $post_id); //条件
		$db_tag2->executeSQL($sqlbuilder3->makeSQL());
		$row_tag = $db_tag2->executeFetch();
		$db_tag2->DBClose();
		
		//変更前のタグのreferenceを全てデクリメント
		$before = explode(",", $row_tag[$tag_data_check["column"]]); //変更前のタグ
		$db_tb = new DB;
		$db_tb->DBConnect();
		foreach($before as $key => $value){
			//該当タグのリファレンスを取得
			$sqlbuilder4 = new SQLBuild(1); //select
			$sqlbuilder4->setTable($entry_reg_data["reference"]["table"]); //テーブル
			$sqlbuilder4->addSelectCols($entry_reg_data["reference"]["column"]); //カラム(引数なしだと全カラム)
			$sqlbuilder4->setWhere("tid", $value); //条件
			$db_tb->executeSQL($sqlbuilder4->makeSQL());
			$row = $db_tb->executeFetch();
			//更新処理
			$sqlbuilder4 = new SQLBuild(4); //update
			$sqlbuilder4->setTable($entry_reg_data["reference"]["table"]);
			$sqlbuilder4->setLtr("reference", ((int)$row["reference"] - 1));
			$sqlbuilder4->setWhere("tid", $value);
			$db_tb->executeSQL($sqlbuilder4->makeSQL());
		}
		$db_tb->DBClose();
		
		//変更後のタグのreferenceを全てインクリメント
		$after = $tmp_array; //変更後のタグ
		$db_ta = new DB;
		$db_ta->DBConnect();
		foreach($after as $key => $value){
			//該当タグのリファレンスを取得
			$sqlbuilder5 = new SQLBuild(1); //select
			$sqlbuilder5->setTable($entry_reg_data["reference"]["table"]); //テーブル
			$sqlbuilder5->addSelectCols($entry_reg_data["reference"]["column"]); //カラム(引数なしだと全カラム)
			$sqlbuilder5->setWhere("tid", $value); //条件
			$db_ta->executeSQL($sqlbuilder5->makeSQL());
			$row = $db_ta->executeFetch();
			//更新処理
			$sqlbuilder5 = new SQLBuild(4); //update
			$sqlbuilder5->setTable($entry_reg_data["reference"]["table"]);
			$sqlbuilder5->setLtr("reference", ((int)$row["reference"] + 1));
			$sqlbuilder5->setWhere("tid", $value);
			$db_ta->executeSQL($sqlbuilder5->makeSQL());
		}
		$db_ta->DBClose();
		
		//日記更新
		$db = new DB;
		$db->DBConnect();
		$sqlbuilder = new SQLBuild(4); //update
		$sqlbuilder->setTable($entry_reg_data["edit"]["table"]);
		$sqlbuilder->setAry($entry_reg_data["edit"]["column"]);
		$sqlbuilder->setWhere("id", $post_id);
		$db->executeSQL($sqlbuilder->makeSQL());
		$db->DBClose();
		
		$_GET['id'] = $post_id; //GETに直接値を代入
	}

	//未登録タグ(reference=0)を削除(タグ削除時)
	ds_tag3();
	
	//エントリ表示
	ds_entry1("edit");
}

//日記削除フォーム表示
function ds_entry6(){
	//テンプレートのみ変更して出力
	ds_entry1("delete");
}

//日記削除処理
function ds_entry7(){
	global $entry_reg_data;
	$err = 0;
	//POSTデータ
	$post_id = (int)$_POST['id']; //update時のキー
	
	if($post_id){
		//パスワードチェック
		if($_POST['ds_passwd'] != PASSWD){
			$_GET['id'] = $post_id;
			$err_items["delete"] = ERR_PASSWD;
			$err++;
		}
	}
	//エラーがあればエラー状態出力して
	//入力画面に戻る
	if($err > 0){
		$filename = tmpl_entry;
		ds_entry1("delete", $err_items);
		return false;
	}
	//タグreference更新前処理
	$tag_data_check = array(
		"table" => "diary3",
		"column" => "tag"
	);
	//タグ取得
	$db_tag = new DB;
	$db_tag->DBConnect();
	//SQL生成
	$sqlbuilder2 = new SQLBuild(1); //select
	$sqlbuilder2->setTable($tag_data_check["table"]); //テーブル
	$sqlbuilder2->addSelectCols($tag_data_check["column"]); //カラム(引数なしだと全カラム)
	$sqlbuilder2->setWhere("id", $post_id); //条件
	$db_tag->executeSQL($sqlbuilder2->makeSQL());
	$row_tag = $db_tag->executeFetch();
	$db_tag->DBClose();
	
	//登録されていたタグreferenceを全てデクリメント
	$deltag = explode(",", $row_tag[$tag_data_check["column"]]); //変更前のタグ
	$db_tag2 = new DB;
	$db_tag2->DBConnect();
	foreach($deltag as $key => $value){
		//該当タグのリファレンスを取得
		$sqlbuilder3 = new SQLBuild(1); //select
		$sqlbuilder3->setTable($entry_reg_data["reference"]["table"]); //テーブル
		$sqlbuilder3->addSelectCols($entry_reg_data["reference"]["column"]); //カラム(引数なしだと全カラム)
		$sqlbuilder3->setWhere("tid", $value); //条件
		$db_tag2->executeSQL($sqlbuilder3->makeSQL());
		$row = $db_tag2->executeFetch();
		//更新処理
		$sqlbuilder4 = new SQLBuild(4); //update
		$sqlbuilder4->setTable($entry_reg_data["reference"]["table"]);
		$sqlbuilder4->setLtr("reference", ((int)$row["reference"] - 1));
		$sqlbuilder4->setWhere("tid", $value);
		$db_tag2->executeSQL($sqlbuilder4->makeSQL());
	}
	$db_tag2->DBClose();
	//削除処理
	$db = new DB;
	$db->DBConnect();
	//SQL生成
	$sqlbuilder = new SQLBuild(2); //delete
	$sqlbuilder->setTable($entry_reg_data["delete"]["table"]); //テーブル
	$sqlbuilder->setWhere("id", $post_id);
	$db->executeSQL($sqlbuilder->makeSQL());
	$db->DBClose();

	//未登録タグ(reference=0)を削除(タグ削除時)
	ds_tag3();
	
	//エントリ表示
	ds_entry1("entry");
}

//タグからエントリ表示
function ds_entry8(){
	//タグのID
	$tid = (int)$_GET['tid'];
	
	$entry_tag = array(
		"table" => "diary3",
		"column" => array("id", "tag"),
		"order" => "id"
	);
	
	$db = new DB;
	$db->DBConnect();
	//SQL生成
	$sqlbuilder = new SQLBuild(1); //select
	$sqlbuilder->setTable($entry_tag["table"]); //テーブル
	$sqlbuilder->addSelectCols($entry_tag["column"]); //カラム(引数なしだと全カラム)
	$sqlbuilder->setOrderby($entry_tag["order"]); //ORDER BY
	$db->executeSQL($sqlbuilder->makeSQL());
	
	while($row = $db->executeFetch()){
		//カンマ→配列
		$extag = explode(",", $row['tag']);
		//照合し、一致したものがあれば配列に登録
		foreach($extag as $t){
			if($tid == $t){
				$match_id["tag"][] = $row['id'];
			}
		}
	}
	$db->DBClose();
	
	if(!$match_id){
		echo 'エントリはありません';
	}else{
		//エントリ表示
		ds_entry1("entry", $match_id);
	}
}

//月別エントリ表示
function ds_entry9(){
	global $archive_data;
	$err = 0;
	//GETデータ
	$month = h($_GET['month']);
	list($y, $m) = split("-", $month);
	//値チェック
	if((int)$y < 2000 || (int)$y > 2100) $err++;
	if((int)$m < 1 || (int)$m > 12) $err++;
	//変な値が既に来ていた場合は、DBに渡さない
	if($err == 0){
		//DBチェック
		$db = new DB;
		$db->DBConnect();
		//SQL生成
		$sqlbuilder = new SQLBuild(1); //select
		$sqlbuilder->setTable($archive_data["count"]["table"]); //テーブル
		$sqlbuilder->addSelectCols($archive_data["count"]["column"]); //カラム(引数なしだと全カラム)
		$sqlbuilder->setWherelike($archive_data["count"]["where"], $month);
		$db->executeSQL($sqlbuilder->makeSQL());
		$row = $db->executeFetch();
		$db->DBClose();
		
		if($row["count"] == 0) $err++;
	}
	if($err > 0){
		echo 'エントリはありません';
	}else{
		$item = array(
			"month" => array(
				"col" => "date",
				"val" => $month
			)
		);
		ds_entry1("entry", $item);
	}
}

//タグ一覧表示
function ds_tag1($commit){
	global $tag_all_data;
	
	$db = new DB;
	$db->DBConnect();
	//COMMITフラグ
	$ary2json["Commit"] = $commit;
	//SQL生成
	$sqlbuilder = new SQLBuild(1); //select
	$sqlbuilder->setTable($tag_all_data["table"]); //テーブル
	$sqlbuilder->addSelectCols($tag_all_data["column"]); //カラム(引数なしだと全カラム)
	$sqlbuilder->setWhere($tag_all_data["where"], null); //条件
	$sqlbuilder->setOrderby($tag_all_data["order"]); //ORDER BY
	$db->executeSQL($sqlbuilder->makeSQL());

	$i = 0;
	$child = $tag_all_data["column"]; //カラム要素を分解して利用
	while($row = $db->executeFetch()){
		foreach($row as $k => $v){
			if(is_string($k)){//キーが連想のもののみ取り出す
				foreach($child as $gk => $gv){
					if($gk == $k){
						$ary2json["Tag"][$i][$gk] = $v;
					}
				}
			}
		}
		$i++;
	}
	//タグが無い場合
	if(!isset($ary2json["Tag"])) $ary2json["Tag"] = ""; //lengthで値をとるため
	$db->DBClose();
	
	return $ary2json;
}

function ds_tag2($tag){
	global $tag_new_data;
	//タグIDを取得
	$tag_id_data = array(
		"table" => "tag_classify",
		"column" => "tid"
	);

	$db = new DB;
	$db->DBConnect();
	//SQL生成(tag_classify)
	$sqlbuilder = new SQLBuild(3); //insert
	$sqlbuilder->setTable($tag_new_data["classify"]["table"]); //テーブル
	$sqlbuilder->setStr($tag_new_data["classify"]["column"], $tag); //カラム、値
	$db->executeSQL($sqlbuilder->makeSQL());
	$state = $db->getSQLState();
	if(!$state) return $state;

	//SQL生成(tag_reference)
	//登録したタグIDを取得
	$sqlbuilder2 = new SQLBuild(1); //select
	$sqlbuilder2->setTable($tag_id_data["table"]); //テーブル
	$sqlbuilder2->addSelectCols($tag_id_data["column"]); //カラム(引数なしだと全カラム)
	$sqlbuilder2->setWhere("tname", $tag); //条件
	$db->executeSQL($sqlbuilder2->makeSQL());
	$row = $db->executeFetch();
	$tid = $row["tid"];
	$state = $db->getSQLState();
	if(!$state) return $state;

	//タグIDからreference値を0に設定
	//この時点では正式登録とはみなさないので0
	$sqlbuilder3 = new SQLBuild(3); //insert
	$sqlbuilder3->setTable($tag_new_data["reference"]["table"]); //テーブル
	$sqlbuilder3->setStr($tag_new_data["reference"]["column"][0], $tid); //カラム、値
	$sqlbuilder3->setStr($tag_new_data["reference"]["column"][1], 0); //カラム、値
	$db->executeSQL($sqlbuilder3->makeSQL());
	$state = $db->getSQLState();
	if(!$state) return $state;

	$db->DBClose();
	
	//SQL実行結果を返す
	return $state;
}

function ds_tag3(){
	$db = new DB;
	$db->DBConnect();
	//タグが新規登録されても使用されなかったとき
	$tag_del_data = array(
		"select" => array(
			"table" => "tag_reference",
			"column" => "tid"
		),
		"delete" => array(
			"table" => array("tag_classify", "tag_reference")
		)
	);
	//referenceが0のものを抽出
	$tid = array();
	$sqlbuilder = new SQLBuild(1); //select
	$sqlbuilder->setTable($tag_del_data["select"]["table"]); //テーブル
	$sqlbuilder->addSelectCols($tag_del_data["select"]["column"]); //カラム(引数なしだと全カラム)
	$sqlbuilder->setWhere("reference", 0); //条件
	$db->executeSQL($sqlbuilder->makeSQL());
	while($row = $db->executeFetch()){$tid[] = $row["tid"];}
	
	if($tid){
		//tag_classifyから削除
		$sqlbuilder2 = new SQLBuild(2); //delete
		$sqlbuilder2->setTable($tag_del_data["delete"]["table"][0]); //テーブル
		foreach($tid as $val){$sqlbuilder2->setWhereOr("tid", $val);}
		$db->executeSQL($sqlbuilder2->makeSQL());
		
		//tag_referenceから削除
		$sqlbuilder3 = new SQLBuild(2); //delete
		$sqlbuilder3->setTable($tag_del_data["delete"]["table"][1]); //テーブル
		foreach($tid as $val){$sqlbuilder3->setWhereOr("tid", $val);}
		$db->executeSQL($sqlbuilder3->makeSQL());
	}
	$db->DBClose();
}
?>