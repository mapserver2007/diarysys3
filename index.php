<?php session_start(); ?>
<?php require_once('./php/ds_config.php'); ?>
<?php require_once('./php/ds_entry.php'); ?>
<?php require_once('./php/ds_sidebar.php'); ?>
<?php require_once('./php/ds_extra.php'); ?>
<?php require_once('./php/ds_common.php'); ?>
<?php require_once('./php/lib/json.php'); ?>
<?php ds_receive(); ?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
<head>
<META http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<meta http-equiv="Cache-Control" content="no-cache" />
<meta http-equiv="Pragma" content="no-cache" />
<title>SUMMER-LIGHTS</title>
<link type="text/css" rel="stylesheet" href="css/style.css" />
<link type="text/css" rel="stylesheet" href="css/prettify.css" />
<link type="text/css" rel="stylesheet" href="css/lightbox.css" />
<link type="text/css" rel="stylesheet" href="css/quicktags_plus.css" />
<link type="text/css" rel="stylesheet" href="css/hoverbox.css" />
<script language="javascript" src="js/prototype.js"></script>
<script language="javascript" src="js/incremental.js"></script>
<script language="javascript" src="js/prettify.js"></script>
<script language="javascript" src="js/quicktags_plus.js"></script>
<script language="javascript" src="js/litebox.js"></script>
<script language="javascript" src="js/moo.fx.js"></script>
<script language="javascript" src="js/common.js"></script>
<script language="javascript" src="js/minmax.js"></script>
</head>
<body>
<!-- BOXメイン -->
<div id="box_main" class="box">
	<!-- BOXタイトル -->
	<div id="box_title">
		<a href="http://summer-lights.dyndns.ws/"><img src="images/main3.png" border="0" /></a>
	</div>
	<!-- 各種アイコン -->
	<div id="box_icon">
		<?php ds_func(); ?>
		<a href="<?php echo ROOT_URL; ?>?m=rss" style="text-decoration:none;">
			<img src="images/rss.gif" border="0" title="rss" />
		</a>
	</div>
	<!-- リンク -->
	<div id="box_link">
		<?php setLink(); ?>
	</div>
	<!-- メインコンテナ -->
	<div id="ds_container">
		<!-- ヘッダ -->
		<div id="box_c_title"><img src="images/title3.png" /></div>
		<!-- メイン -->
		<div id="ds_main">
			<!-- Pager -->
			<div id="ds_pager"></div>
			<!-- エントリ -->
			<?php ds_entry(); ?>
		</div>
		<!-- サイドメニュー -->
		<div id="ds_side_menu">
			<!-- ログイン -->
			<div id="ds_login">
				<p style="font-size:medium;margin:0px;">LOGIN</p>
				<?php ds_login(); ?>
			</div>
			<!-- サイドバー -->
			<div id="ds_side">
				<div class="ds_side_title">RECENT ENTRIES</div>
				<div id="ds_side_recent">
					<?php ds_recent(); ?>
				</div>
				<div class="ds_side_title">CATEGORIES</div>
				<div id="ds_side_category">
					<?php ds_tagcloud(); ?>
				</div>
				<div class="ds_side_title">ARCHIVES</div>
				<div id="ds_side_archive">
					<?php ds_archive(); ?>
				</div>
				<div class="ds_side_title">LINKS</div>
				<div id="ds_side_link">
					<?php ds_link(); ?>
				</div>
			</div>
		</div>
	</div>
</div>
</body>
</html>