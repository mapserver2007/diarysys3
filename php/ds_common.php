<?php
//変数定義----------------------------------------------------------------
define('CONFIG', '/usr/local/apache2/htdocs/summer-lights.ini');
define('ROOT_URL', 'http://summer-lights.dyndns.ws/diarysys3/');
//define('ROOT_URL', 'http://192.168.0.101/diarysys3/');
define('NAME', 'SUMMER-LIGHTS powered by KURO-BOX/HG');
define('DESCRIPT', 'DIARYSYSTEM ver.3');
define('CREATOR', 'summer-lights');
define('LOGIN', 'adminmode');
define('PASSWD', 'paranoia');
//日記設定----------------------------------------------------------------
$entry_num = 15; //表示するエントリ数
$recent_num = 15; //表示する最近のエントリ数
$rss_num = 20; //RSSに表示するエントリ数
$entrymode = 0; //0:通常表示, 1:フォーム表示
//SQL定義-----------------------------------------------------------------
//エントリ
$entry_data = array(
	"table" => "diary3",
	"column" => array("id", "title", "description", "tag", "date"),
	"order" => "id desc"
);

//日記登録・更新・削除、タグ更新
$entry_reg_data = array(
	"entry" => array(
		"table" => "diary3",
		"column" => array(
			"title" => "",
			"description" => "",
			"tag" => "",
			"date" => ""
		)
	),
	"reference" => array(
		"table" => "tag_reference",
		"column" => "reference"
	),
	"edit" => array(
		"table" => "diary3",
		"column" => array(
			"title" => "",
			"description" => "",
			"tag" => ""
		)
	),
	"delete" => array(
		"table" => "diary3"
	)
);

//最近のエントリ
$recent_data = array(
	"table" => "diary3",
	"column" => array("id", "title", "date"),
	"order" => "id desc",
	"limit" => $recent_num
);

//月別アーカイブ
$archive_data = array(
	"base" => array(
		"table" => "diary3",
		"column" => "date",
		"order" => "date"
	),
	"count" => array(
		"table" => "diary3",
		"column" => array(
			"count" => "count(date)"
		),
		"where" => "date"
	)
);

$archive_data2 = array(
	"table" => "diary3",
	"column" => "date",
	"order" => "date"
);

//リンク
$link_data = array(
	array(
		"title" => "mapserver2007@避難所",
		"url" => "http://blog.livedoor.jp/mapserver2007/"
	),
	array(
		"title" => "Wiki@研究室",
		"url" => "http://devel.de.c.dendai.ac.jp/pukiwiki/"
	),
	array(
		"title" => "Web技術勉強会",
		"url" => "http://devel.de.c.dendai.ac.jp/study/"
	)
);

//RSS
$rss_data = array(
	"table" => "diary3",
	"column" => array("id", "title", "description", "tag", "date"),
	"order" => "id desc",
	"limit" => $rss_num
);
//タグ一覧
$tag_all_data = array(
	"table" => array("tag_classify", "tag_reference"),
	"column" => array(
		"tid" => "tag_classify.tid",
		"tname" => "tag_classify.tname",
		"tref" => "tag_reference.reference"
	),
	"where" => array("tag_classify.tid", "tag_reference.tid"),
	"order" => "tag_classify.tname"
);

//タグ新規登録
$tag_new_data = array(
	"classify" => array(
		"table" => "tag_classify",
		"column" => "tname"
	),
	"reference" => array(
		"table" => "tag_reference",
		"column" => array("tid", "reference")
	)
);

//テンプレートファイル定義------------------------------------------------
define('tmpl_entry', 'tmpl/ds_entry.tmpl');   //indexから見た相対パス
define('tmpl_recent', 'tmpl/ds_recent.tmpl');
define('tmpl_rss', 'tmpl/ds_rss.tmpl');
define('tmpl_func', 'tmpl/ds_func.tmpl');
define('tmpl_login', 'tmpl/ds_login.tmpl');
define('tmpl_entryform', 'tmpl/ds_entryform.tmpl');
define('tmpl_tagcloud', 'tmpl/ds_tagcloud.tmpl');
define('tmpl_entryedit', 'tmpl/ds_entryedit.tmpl');
define('tmpl_archive', 'tmpl/ds_archive.tmpl');
define('tmpl_link', 'tmpl/ds_link.tmpl');
//エラーメッセージ定義----------------------------------------------------
define('ERR_PASSWD', 'パスワードが違います');
define('ERR_ID', 'IDが取得できません');
define('ERR_TITLE', 'タイトルが入力されていません');
define('ERR_DESCRIPTION', '内容が入力されていません');
define('ERR_TAG', 'タグが選択されていません');
//Livedoor天気定義--------------------------------------------------------
$weather_url = "http://weather.livedoor.com/forecast/webservice/rest/v1";
$weather_city = 63; //東京都
$weather_day = "today"; //今日、明日、明後日が可能
//------------------------------------------------------------------------
?>