<?php
require_once('ds_config.php');
require_once('./lib/xml.php');
require_once('./lib/json.php');

function get_aws_url_str($q){
	//AMAZON REST STRING
	$url = 'http://ecs.amazonaws.jp/onca/xml?';
	//アクセスキー
	$key = '02GA6TH96EC9SQKGEJ82';
	//パラメータ
	$param = array(
		"Service" => "AWSECommerceService",         //必須
		"AWSAccessKeyId" => $key,                   //必須
		"Operation" => "ItemSearch",                //検索を指定
		"SearchIndex" => "Books",                   //本を指定(今後拡張も予定？)
		"Title" => $q["title"],                     //タイトル
		"Author" => $q["author"],                   //著者
		"ResponseGroup" => "Images,ItemAttributes"  //属性と画像を検索
	);
	//URLクエリを生成
	foreach($param as $v => $k){
		if(isset($k)){
			$url .= $v.'='.$k;
			$url .= '&';
		}
	}
	return substr($url, 0, -1);
}

function set_aws_bookattr($item, $url, $cnt = 1){
	$aws_obj = array();
	$no_image = array(
		"small" => array(
			"url" => 'http://ec1.images-amazon.com/images/G/09/x-locale/detail/thumb-no-image.gif',
			"height" => 75,
			"width" => 52
		),
		"medium" => array(
			"url" => 'http://ec1.images-amazon.com/images/G/09/nav2/dp/no-image-no-ciu._SL100_.gif',
			"height" => 100,
			"width" => 100
		),
		"large" => array(
			"url" => 'http://ec1.images-amazon.com/images/G/09/nav2/dp/no-image-no-ciu._AA250_.gif',
			"height" => 250,
			"width" => 250
		)
	);
	
	for($i = 0; $i < $cnt; $i++){
		if(count($item[$i]["SmallImage"]) == 0){
			$image_small = $no_image["small"];
		}else{
			$image_small = array(
				"url" => $item[$i]["SmallImage"]["URL"],
				"height" => $item[$i]["SmallImage"]["Height"],
				"width" => $item[$i]["SmallImage"]["Width"]
			);
		}
		if(count($item[$i]["MediumImage"]) == 0){
			$image_medium = $no_image["medium"];
		}else{
			$image_medium = array(
				"url" => $item[$i]["MediumImage"]["URL"],
				"height" => $item[$i]["MediumImage"]["Height"],
				"width" => $item[$i]["MediumImage"]["Width"]
			);
		}
		if(count($item[$i]["LargeImage"]) == 0){
			$image_large = $no_image["large"];
		}else{
			$image_large = array(
				"url" => $item[$i]["LargeImage"]["URL"],
				"height" => $item[$i]["LargeImage"]["Height"],
				"width" => $item[$i]["LargeImage"]["Width"]
			);
		}
		
		$aws_book = array(
			"Author" => $item[$i]["ItemAttributes"]["Author"],
			"Title" => $item[$i]["ItemAttributes"]["Title"],
			"Publisher" => $item[$i]["ItemAttributes"]["Publisher"],
			"PublicationDate" => $item[$i]["ItemAttributes"]["PublicationDate"],
			"Price" => array(
				"Amount" => $item[$i]["ItemAttributes"]["ListPrice"]["Amount"],
				"FormattedPrice" => h($item[$i]["ItemAttributes"]["ListPrice"]["FormattedPrice"])
			),
			"Image" => array(
				"small" => $image_small,
				"medium" => $image_medium,
				"large" => $image_large
			),
			"Detail" => $item[$i]["DetailPageURL"],
			"URL" => $url
		);
		$aws_obj[] = $aws_book;
	}
	return $aws_obj;
}


function get_aws_array($url){
	$xml = XML_unserialize(file_get_contents($url));
	$aws_xml = $xml["ItemSearchResponse"]["Items"]["Item"];
	$valid_obj = "";
	if(is_array($aws_xml[0])){
		$valid_obj = set_aws_bookattr($aws_xml, $url, count($aws_xml));
	}else{
		$dummy = array();
		$dummy[] = $aws_xml;
		$valid_obj = set_aws_bookattr($dummy, $url);
	}
	//var_dump($valid_obj);
	return $valid_obj;
}

function get_aws_json($obj){
	//$xml = XML_unserialize(file_get_contents($url));
	$json = new Services_JSON; 
	$encode = $json->encode($obj);
	return $encode;
}

function get_search_query(){
	$q_title = urlencode(auto2utf8(h($_POST['aws_title'])));
	$q_author = urlencode(auto2utf8(h($_POST['aws_author'])));
	$query = array();
	if($q_title){
		$query["title"] = $q_title;
	}else if($q_author){
		$query["author"] = $q_author;
	}
	return $query;
}

$query = get_search_query() or die("query error");
$url = get_aws_url_str($query);
$obj = get_aws_array($url);
echo get_aws_json($obj);


//$json = get_aws_json($url);
//echo $json->$ItemSearchResponse->$attr[0];

//echo $url;

/*
http://ecs.amazonaws.jp/onca/xml?Service=AWSECommerceService&AWSAccessKeyId=
02GA6TH96EC9SQKGEJ82&Operation=ItemSearch&SearchIndex=Books&Title=%E7%9F%A5%E8%AD%98%E3%81%A0%E3%81%91%E3%81%82%E3%82%8B%E3%83%90%E3%82%AB%E3%81%AB%E3%81%AA%E3%82%8B%E3%81%AA

http://www.ajaxtower.jp/ecs/itemsearch/index.html


*/
?>