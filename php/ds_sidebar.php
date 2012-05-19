<?php
require_once('ds_config.php');
require_once('ds_common.php');

//最近のエントリを表示
function ds_recent(){
	global $recent_data;
	$db = new DB;
	$db->DBConnect();
	//SQL生成
	$sqlbuilder = new SQLBuild(1); //select
	$sqlbuilder->setTable($recent_data["table"]); //テーブル
	$sqlbuilder->addSelectCols($recent_data["column"]); //カラム(引数なしだと全カラム)
	$sqlbuilder->setOrderby($recent_data["order"]); //ORDER BY
	$sqlbuilder->setLimit(0, $recent_data["limit"]); //LIMIT
	$db->executeSQL($sqlbuilder->makeSQL());
	$i = 0;
	while($row = $db->executeFetch()){
		//日付を加工
		$date1 = preg_split('/[\s]/', $row["date"]); //年月日、時分秒に分割
		$date2 = preg_split('/[-]/', $date1[0]); //年、月、日に分割
		
		//配列化
		$items[$i] = array(
			"id" => $row["id"],
			"title" => $row["title"],
			"date" => $date2[1]."/".$date2[2]
		);
		$i++;
	}
	$db->DBClose();
	if(isset($items) == false){
		echo 'エントリはありません';
	}else{
		$filename = tmpl_recent;
		show_template($filename, $items, ROOT_URL);
	}
}

//カテゴリ(タグクラウド)を表示
function ds_tagcloud(){
	global $tag_all_data;
	
	$db = new DB;
	$db->DBConnect();
	//SQL生成
	$sqlbuilder = new SQLBuild(1); //select
	$sqlbuilder->setTable($tag_all_data["table"]); //テーブル
	$sqlbuilder->addSelectCols($tag_all_data["column"]); //カラム(引数なしだと全カラム)
	$sqlbuilder->setWhere($tag_all_data["where"], null); //条件
	$sqlbuilder->setOrderby($tag_all_data["order"]); //ORDER BY
	$db->executeSQL($sqlbuilder->makeSQL());
	$i = 0;
	while($row = $db->executeFetch()){
		//タグクラウド
		$s = $row["tref"];
		if($s >= 1 && $s <=2)         $fsize = "x-small";
		else if($s >= 3 && $s <= 5)   $fsize = "small";
		else if($s >= 6 && $s <= 10)  $fsize = "medium";
		else if($s >= 11 && $s <= 30) $fsize = "large";
		else if($s >= 31 && $s <= 50) $fsize = "x-large";
		else if($s >= 51)             $fsize = "xx-large";
		else                          $fsize = "xx-small";

		//配列化
		$items[$i] = array(
			"tid" => $row["tid"],
			"tname" => $row["tname"],
			"tref" => $row["tref"],
			"tsize" => $fsize
		);
		$i++;
	}
	$db->DBClose();
	if(isset($items) == false){
		echo 'タグはありません';
	}else{
		$filename = tmpl_tagcloud;
		show_template($filename, $items, ROOT_URL);
	}
}

//日記アーカイブを表示
function ds_archive(){
	global $archive_data2;

	$db = new DB;
	$db->DBConnect();
	//SQL生成
	$sqlbuilder = new SQLBuild(1); //select
	$sqlbuilder->setTable($archive_data2["table"]); //テーブル
	$sqlbuilder->addSelectCols($archive_data2["column"]); //カラム(引数なしだと全カラム)
	$sqlbuilder->setOrderby($archive_data2["order"]); //ORDER BY
	$db->executeSQL($sqlbuilder->makeSQL());
	//$row = $db->executeFetch();
	$i = 0;
	while($row = $db->executeFetch()){
		//dateを整形
		$date = $row["date"];
		if(!$st_month && !$st_year){
			//エントリの最も古いものを取り出し、基準にする
			$ymd = preg_split('/[\s]/', $date);
			list($y, $m, $d) = split("-", $ymd[0]);
			$st_month = (int)$m;
			$st_year = (int)$y;
		}
		$m = mb_strlen($st_month) == 1 ? (string)"0".$st_month : (string)$st_month;
		//現在のレコードと基準dateが一致すれば配列化
		$crtymd = preg_split('/[\s]/', $date);
		list($cy, $cm, $cd) = split("-", $crtymd[0]);

		//$ttt = 0;
		for(;;){
			//if($ttt > 200) break;
			if($st_year == (int)$cy && $st_month == (int)$cm){
				$m = mb_strlen($cm) == 1 ? (string)"0".$cm : (string)$cm;
				$val = (string)$cy."-".$m;
				//同じ月ならば登録数をインクリメント
				if($items[$i-1]["month"] == $val){
					$items[$i-1]["count"] += 1;
					break;
				}else{
					//配列化
					$items[$i] = array(
						"str" => $st_year."年".$m."月",
						"month" => $val,
						"count" => 1
					);
					$i++;
					break;
				}
			}else{
				$st_month++;
				//12月を超えたら、次の年
				if($st_month > 12){
					$st_year++;
					$st_month = 1;
				}
			}
		}
	}

	$db->DBClose();
	if(isset($items) == false){
		echo 'エントリはありません';
	}else{
		$filename = tmpl_archive;
		$reverse_items = array_reverse($items);
		show_template($filename, $reverse_items, ROOT_URL);
	}
	
}

//リンク
function ds_link(){
	global $link_data;
	$filename = tmpl_link;
	show_template($filename, $link_data, null);
}

//ログイン
function ds_login(){
	$filename = tmpl_login;
	show_template($filename, $_SESSION['admin'], null);
}

?>