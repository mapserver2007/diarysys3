// ========================================================================================
//  DIARY SYSTEM v3
//  http://summer-lignts.dyndns.ws/diarysys3/
// ========================================================================================
//  v2.00  2007/06/14  TMAP2g正式バージョン(TMAP2bの機能移植)
// ========================================================================================
//  使用ライブラリ・API
//  prototype.js             
//  quicktags(未使用)        http://sw-guide.de/
//  QuickTags Plus           http://blog.livedoor.jp/cie/archives/50417516.html
//  comment_preview.js       http://melrose.jugem.cc/?eid=143
//  HTMLPurifier(未使用)     http://htmlpurifier.org/
//  prettify                 http://code.google.com/p/google-code-prettify/
//  websnapr                 http://images.websnapr.com/
//  minmax.js                http://doxdesk.com/software/js/minmax.html
//  Livedoor Weather API     
//  (TMAP API)
// ========================================================================================
//  問題点
// ========================================================================================
//  開発メモ
// ========================================================================================
// パラメータ==============================================================================
//var diary = {};
//diary.defshow = 5;                                        //初期状態で表示する件数(最新からx件)
// ========================================================================================
var tag = {};
tag.all = null;                                                //タグ一覧を保持(オブジェクト)
tag.open = false;                                            //タグ一覧がの状態(falseで閉じ、trueで開き)
tag.maxnum = 5;                                                //登録する最大タグ数
tag.crtnum = 0;                                                //現在の登録タグ数
var snap = "http://images.websnapr.com/?url=";                //スナップのURL
//var snap = "http://img.simpleapi.net/small/";
// ========================================================================================

//load時のイベントリスナをセットする
addListener(window, 'load', setListener, false);

//Quicktag変換有効
addListener(window, 'load', changeCommentTags, false);

//コードハイライト有効
addListener(window, 'load', prettyPrint, false);

//Lite-box有効
addListener(window, 'load', initLightbox, false);

//初期処理
function setListener(){
    /* ブラウザ種別判定 */
    setUserAgentInfo();
}

//Pagerのセット
function setPager(limit, all, crtpage){
    //ページ数を取得
    var maxpage = Math.ceil(all / limit);
    //ページが1ページで収まるまたは存在しない場合は終了
    if(maxpage == 1 || maxpage == 0) return;
    //件数のテキスト生成
    var ptext = {};
    ptext.all = all;
    ptext.from = limit * (crtpage - 1) + 1;
    ptext.to = crtpage == maxpage ? all : limit * crtpage;
    //URLのロケーションを取得
    var ret = location.search.substr(1).split("&");
    //page要素のみ取り除く
    var get = [];
    for(var i = 0; i < ret.length; i++){
        var r = ret[i].split("=");
        if(r[0] == "page") continue;
        if(r[0] == "") break;
        get[i] = [r[0], r[1]];
    }
    //URL生成
    url = {};
    url.loc = location.href.substr(0).split("?")[0];
    url.flg = false;
    for(var j = 0; j < get.length; j++){
        //if(!get[j][0]){ url.flg = false; break;}
        if(j == 0) url.loc += "?" + get[j][0] + "=" + get[j][1];
        else url.loc += "&" + get[j][0] + "=" + get[j][1];
        url.flg = true;
    }

    var pn = document.createElement('div');
    pn.style.fontSize = "small";
    pn.appendChild(document.createTextNode(
        ptext.all + "件中" + ptext.from + "-" + ptext.to + "件表示"
    ));
    $('ds_pager').appendChild(pn);
    
    var ul = document.createElement('ul');
    var li = document.createElement('li');
    ul.appendChild(li);
    for(var i = 1; i < maxpage+1; i++){
        var loc = url.loc
        if(!url.flg)  loc += '?page=' + i
        else          loc += '&page=' + i
        //自身のページにはリンクは張らない
        var li = document.createElement('li');
        if(i == crtpage){
            //li.className = "ds_page_current";
            li.appendChild(document.createTextNode(i));
        }else{
            var a = document.createElement('a');
            a.href = loc;
            a.appendChild(document.createTextNode(i));
            li.appendChild(a);
        }
        ul.appendChild(li);
    }
    $('ds_pager').appendChild(ul);
    
    var dummy = document.createElement('div');
    dummy.style.clear = "both";
    $('ds_pager').appendChild(dummy);
}

//タグ一覧
function showAllTags(){
    if(!tag.open && tag.all == null){
        var req = "all";
        //タグ一覧を表示する領域tag_all_listを生成
        var t = document.createElement('div');
        t.id = "tag_all_list";
        $('tag_all').appendChild(t);
        $('tag_all_list').appendChild(document.createTextNode("読み込んでいます..."));
        ajaxUpdate('php/ajax_request.php', 'tmode='+req, updatePhase1);
    }else if(!tag.open && tag.all != null){
        $('tag_all').appendChild(tag.all);
        tag.open = true;
    }else{
        //オブジェクトとして保持
        tag.all = $('tag_all_list');
        //保持後削除
        rme('tag_all_list');
        tag.open = false;
    }
}

//タグ新規作成(タグ一覧には登録しない)
function createNewTag(){
    if($('tag_create_list') == null){
        //親要素
        var pn = document.createElement('div');
        pn.id = "tag_create_list";
        $('tag_create').appendChild(pn);
        
        //タグ名
        var ip = document.createElement('input');
        ip.type = "text";
        //ip.name = "new_tag";
        ip.className = "form";
        ip.style.width = 100 + "px";
        ip.style.marginRight = 4 + "px";
        $('tag_create_list').appendChild(ip);
        
        //登録ボタン
        var rg = document.createElement('input');
        rg.id = "ntag";
        rg.className = "submit";
        rg.type = "button";
        rg.value = "追加";
        addListener(rg, 'click', setNewTagRegister, false);
        $('tag_create_list').appendChild(rg);
        
        //結果表示領域
        var sp = document.createElement('span');
        sp.id = "tag_create_result";
        $('tag_create_list').appendChild(sp);
        
    }else{
        rme('tag_create_list');
    }
}

//登録済みタグの呼び出し(EDIT)
function callRegisteredTag(json){
    //showAllTags();
    /*
    for(var i = 0; i < json.length; i++){
        var tid = "tag" + json[i].tid;
        var ttt = $(tid);
        alert(ttt);
        addListener($(tid), 'load', setTagRegister, false);
    }
    */
    for(var i = 0; i < json.length; i++){
        var tid = "tag" + json[i].tid;
        var regid = "reg" + tid;
        
        var reg = document.createElement('div');
        reg.id = regid;
        reg.style.padding = 1 + "px";
        $('tag_register').appendChild(reg);
        
        //タグ名
        var ip1 = document.createElement('span');
        ip1.style.marginRight = 2 + "px";
        ip1.appendChild(document.createTextNode(
            json[i].tname
        ));
        
        //タグID(hidden)
        var ip1_h = document.createElement('input');
        ip1_h.type = "hidden";
        ip1_h.name = "ds_tag" + setTagIdHash(tid);
        ip1_h.value = tid.replace("tag", "");

        //クリアボタン
        var ip2 = document.createElement('input');
        ip2.className = "submit";
        ip2.type = "button";
        ip2.id = "ip_" + tid;
        ip2.value = "削除";
        addListener(ip2, 'click', setTagRegister, false);

        $(regid).appendChild(ip1);
        $(regid).appendChild(ip2);
        $(regid).appendChild(ip1_h);
        tag.crtnum++;
    }
}

//タグのイベント設定
function setTagEvents(){
    var tag_elem = $('tag_all_list');
    var tag_node = tag_elem.firstChild.childNodes;
    for(var j = 0; j < tag_node.length; j++){
        var tid = tag_node[j].id;
        Event.observe($(tid), 'click', setTagRegister, false);
    }
}

//タグ一覧表示
function updatePhase1(res){
    eval("var json = " + res.responseText);
    $('tag_all_list').innerHTML = "";
    tag.open = true;
    //タグ登録がある場合
    if(json.Tag.length != 0){
        //タグを順に表示
        var ul = document.createElement('ul');
        for(var i = 0; i < json.Tag.length; i++){
            var li = document.createElement('li');
            var tid = "tag" + json.Tag[i].tid;
            /* IDを設定 */
            li.id = tid;
            /* タグクラウド設定 */
            li.style.fontSize = tagCloud(json.Tag[i].tref);
            /* タグ名を設定 */
            li.appendChild(document.createTextNode(
                json.Tag[i].tname + " "
            ));
            ul.appendChild(li);
            $('tag_all_list').appendChild(ul);
        }
        //タグ検索
        var field = {tag : 'tag_all_list', grep : 'tag_regexp', func : 'setTagEvents'};
        var grep = new TagGrep(field);
        //setTagEvents();
        tag.all = $('tag_all_list');
    }else{
        $('tag_all_list').appendChild(document.createTextNode(
            "タグはありません"
        ));
    }
}

function updatePhase2(res){
    eval("var json = " + res.responseText);
    //タグ登録の結果を表示
    var result = json.Commit ? "新規登録成功" : "新規登録失敗";
    $('tag_create_result').innerHTML = result; //結果表示
    $('tag_create_list').firstChild.value = "";       //フォーム内容初期化
    if($('tag_all_list') == null){ tag.open = false; } //タグ一覧が閉じられている
    
    //登録成功の場合は保持しているタグ一覧オブジェクトを更新
    if(json.Commit && tag.open){
        $('tag_all_list').innerHTML = "";
        //タグを順に表示
        var ul = document.createElement('ul');
        for(var i = 0; i < json.Tag.length; i++){
            var li = document.createElement('li');
            var tid = "tag" + json.Tag[i].tid;
            /* IDを設定 */
            li.id = tid;
            /* タグクラウド設定 */
            li.style.fontSize = tagCloud(json.Tag[i].tref);
            /* タグ名を設定 */
            li.appendChild(document.createTextNode(
                json.Tag[i].tname + " "
            ));
            ul.appendChild(li);
            $('tag_all_list').appendChild(ul);
        }
        var field = {tag : 'tag_all_list', grep : 'tag_regexp', func : 'setTagEvents'};
        var grep = new TagGrep(field);
        //setTagEvents();
        tag.all = $('tag_all_list');
        
    }else if(json.Commit && !tag.open){
        tag.all = null;
        showAllTags();
    }
}

//天気ポップアップ
function updatePhase3(res){
    eval("var json = " + res.responseText);
    
    /* 既にポップアップがある場合は削除 */
    if($('popup')) setClosePopup();
    
    /* ポップアップ親領域 */
    var pn = document.createElement('div');
    pn.id = "popup"
    pn.className = "ds_popup";
    pn.style.left = json.left + "px";
    pn.style.top = json.top + "px";

    /* ポップアップ画像 */
    var img = getAvailableElement();
    img.style.width = 243 + "px";
    img.style.height = 195 + "px";
    img.style.position = "absolute";
    loadImg(img, "images/popup.png");
    pn.appendChild(img);

    /* ポップアップテキスト領域 */
    var cn = document.createElement('div');
    cn.className = "ds_popup_text";
    cn.style.left = 10 + "px";
    cn.style.top = 10 + "px";
    
    //タイトル
    var w0 = document.createElement('div');
    var w0_weather = document.createElement('img');
    w0_weather.src = "images/weather.gif";
    w0_weather.style.verticalAlign = "middle";
    w0.appendChild(w0_weather);
    var w0_title = document.createElement('span');
    w0_title.style.fontSize = "medium";
    w0_title.style.fontWeight = "bold";
    w0_title.appendChild(document.createTextNode(
        "天気情報"
    ));
    w0.appendChild(w0_title);
    var w0_link = document.createElement('a');
    w0_link.href = json.srcurl;
    w0_link.target = "_blank";
    w0_link.style.fontSize = "x-small";
    w0_link.style.marginLeft = 5 + "px";
    w0_link.appendChild(document.createTextNode(
        "[詳細情報]"
    ));
    w0.appendChild(w0_link);
    
    cn.appendChild(w0);
    
    //天気
    var w1 = document.createElement('div');
    var w1_weather = document.createElement('img');
    w1_weather.className = "ds_weather_img";
    w1_weather.src = json.imgurl;
    w1.appendChild(w1_weather);
    var w1_weather2 = document.createElement('span');
    w1_weather2.appendChild(document.createTextNode(
        json.type
    ));
    w1.appendChild(w1_weather2);
    
    //気温
    var w1_temp_min = document.createElement('span');
    w1_temp_min.style.color = "blue";
    w1_temp_min.style.marginLeft = 10 + "px";
    w1_temp_min.appendChild(document.createTextNode(
        json.temp_min
    ));
    w1.appendChild(w1_temp_min);

    w1_separate = document.createElement('span');
    w1_separate.appendChild(document.createTextNode("/"));
    w1.appendChild(w1_separate);
    
    var w1_temp_max = document.createElement('span');
    w1_temp_max.style.color = "red";
    w1_temp_max.appendChild(document.createTextNode(
        json.temp_max
    ));
    w1.appendChild(w1_temp_max);
    
    cn.appendChild(w1);
    
    //内容
    var w2 = document.createElement('div');
    w2.appendChild(document.createTextNode(
        json.description
    ));
    cn.appendChild(w2);
    
    //閉じるボタン
    var wc = document.createElement('img');
    wc.src = "images/close.gif";
    wc.className = "ds_popup_close";
    cn.appendChild(wc);
    addListener(wc, 'click', setClosePopup, false);
    
    pn.appendChild(cn);
    
    /* BODYへ追加 */
    document.body.appendChild(pn);
}

//Pagerの表示
function updatePhase4(res){
    
}

var tag_hash = [
    null, null, null, null, null
];

var tag_hash_idx = 1; //EDITで使うハッシュIndex

//タグIDのハッシュをセット
function setTagIdHash(tid){ //tid:タグのID値
    //hashを上から走査して空いていれば割り当て
    for(var i = 0; i < 5; i++){
        if(tag_hash[i] == null){
            tag_hash[i] = tid;
            break;
        }
    }
    //キー+1を返す
    return (i + 1);
}

//タグIDのハッシュをアンセット
function unsetTagIdHash(tid){ //tid:タグのID値
    //hashを上から走査して該当するIDを解除
    for(var i = 0; i < 5; i++){
        if(tag_hash[i] == tid){
            tag_hash[i] = null;
            break;
        }
    }
}

//新規追加時に既に同じタグがある場合はキャンセル
function checkTagIdHash(tid){
    var flg = false;
    for(var i = 0; i < 5; i++){
        //alert(tag_hash[i]+"/"+tid);
        if(tag_hash[i] == tid){
            flg = true;
            break;
        }
    }
    return flg;
}

//タグの登録状況を表示
function setTagRegister(e){
    var elem = e.target ? e.target : e.srcElement;
    elem = elem.id == "" ? elem.parentNode : elem; //ハイライトのときは親ノードを参照
    var tid = elem.id.replace("ip_", "");
    var regid = "reg" + tid;
    //選択されていないタグのみを表示(重複したら削除)
    if($(regid) == null && tag.crtnum < tag.maxnum){
        if($('tag_default')) rme('tag_default');
        var reg = document.createElement('div');
        reg.id = regid;
        reg.style.padding = 1 + "px";
        $('tag_register').appendChild(reg);
        
        //タグ名
        var ip1 = document.createElement('span');
        ip1.style.marginRight = 2 + "px";
        //ip1.appendChild(document.createTextNode(
        //    elem.firstChild.nodeValue
        //));
        ip1.appendChild(document.createTextNode(
            elem.innerHTML.replace(/<.+?>/g, "")
        ));

        //タグID(hidden)
        var ip1_h = document.createElement('input');
        ip1_h.type = "hidden";
        ip1_h.name = "ds_tag" + setTagIdHash(tid);
        ip1_h.value = tid.replace("tag", ""); //新規でないときは、登録ID値を受ける

        //クリアボタン
        var ip2 = document.createElement('input');
        ip2.className = "submit";
        ip2.type = "button";
        ip2.id = "ip_" + tid;
        ip2.value = "削除";
        addListener(ip2, 'click', setTagRegister, false);

        $(regid).appendChild(ip1);
        $(regid).appendChild(ip2);
        $(regid).appendChild(ip1_h);
        tag.crtnum++;
    }else if($(regid) != null){
        unsetTagIdHash(tid);
        //setTagUnEmphasize(e);
        rme(regid);
        tag.crtnum--;
    }

    if(tag.crtnum == 0){
        var td = document.createElement('p');
        td.id = "tag_default";
        td.style.padding = 2 + "px";
        td.appendChild(document.createTextNode("タグを登録してください"));
        $('tag_register').appendChild(td);
    }
}

//新規タグを登録
function setNewTagRegister(e){
    var elem = e.target ? e.target : e.srcElement;
    var tid = elem.id;
    var tname = elem.parentNode.firstChild.value;
    var req = "new";
    if(!tname){$('tag_create_result').innerHTML = "タグが入力されていません"; return false;}
    ajaxUpdate('php/ajax_request.php', 'tmode='+req+'&tname='+tname, updatePhase2);
}

//タグクラウド修飾
function tagCloud(s){
    var fsize;
    if(s >= 1 && s <=2)         fsize = "x-small";
    else if(s >= 3 && s <= 5)   fsize = "small";
    else if(s >= 6 && s <= 10)  fsize = "medium";
    else if(s >= 11 && s <= 30) fsize = "large";
    else if(s >= 31 && s <= 50) fsize = "x-large";
    else if(s >= 51)            fsize = "xx-large";
    else                        fsize = "xx-small";
    return fsize;
}

//天気情報ポップアップ
function showWeatherInfo(e){
    //マウスの位置を計算
    var m = getElemPos(e);
    var req = "day";
    ajaxUpdate('php/ajax_request.php', 'wmode='+req+'&wid='+e.id+'&x='+m.x+'&y='+m.y, updatePhase3);
}

//Snapポップアップ
function showSnapShot(url, e){
    //位置取得
    var m = getElemPos(e);

    /* 既にポップアップがある場合は削除 */
    if($('popup')) setClosePopup();

    /* ポップアップ親領域 */
    var pn = document.createElement('div');
    pn.id = "popup"
    pn.className = "ds_popup";
    pn.style.left = m.x + "px";
    pn.style.top = m.y + "px";

    /* ポップアップ画像 */
    var img = getAvailableElement();
    img.style.width = 243 + "px";
    img.style.height = 195 + "px";
    loadImg(img, "images/popup.png");
    pn.appendChild(img);

    /* ポップアップテキスト領域 */
    var cn = document.createElement('div');
    cn.className = "ds_popup_text";
    cn.style.left = 10 + "px";
    cn.style.top = 10 + "px";
    
    //スクリーンショット
    var snap_link = document.createElement('a');
    snap_link.href = url;
    var snap_img = document.createElement('img');
    snap_img.src = snap + url;
    snap_img.style.width = 202 + "px";
    snap_img.style.height = 152 + "px";
    snap_img.style.border = 0 + "px";
    snap_link.appendChild(snap_img);
    cn.appendChild(snap_link);

    //閉じるボタン
    var wc = document.createElement('img');
    wc.src = "images/close.gif";
    wc.id = "ds_popup_close";
    cn.appendChild(wc);
    addListener(wc, 'click', setClosePopup, false);
    
    pn.appendChild(cn);
    
    document.body.appendChild(pn);

}

//ポップアップ閉じる(共通)
function setClosePopup(){
    rme('popup');
}

//コメントプレビュー
function doCommentPreview(mode){
    /* textareaのID */
    var textAreaId = 'ds_description_form';
    
    /* プレビューを表示するタグのid属性 */
    var previewAreaId = 'ds_description_preview';

    // コメントプレビュー欄初期表示文字 または、画像タグ
    var initString = '本文のプレビューを表示します';
    
    var entry = $('ds_description_form').innerHTML;

    //textarea、preview領域が取得できなければ終了
    if(!$(textAreaId) || !$(previewAreaId)){
        return;
    }else if(mode != null && mode == 'init') {
        if(!entry) $(previewAreaId).innerHTML = initString;
        else $(previewAreaId).innerHTML = entry.replace(/\x0D\x0A|\x0D|\x0A/g,'<br />');
        return;
    }else{
        //var s = getStringCommentPreview(textAreaId).replace(/\x0D\x0A|\x0D|\x0A/g,'<br />');
        var s = getStringCommentPreview(textAreaId);
        //QuickTags Plus用
        var tags = getCommentTags(edButtons);
        s = getStringCommentTags(tags, s);
        $(previewAreaId).innerHTML = s == "" ? initString : s;
    }
    //if (!s) s = initString;
}

function getStringCommentPreview(e){
    var s = getElementCommentPreview(e).value;
    s = s.split('&').join('&amp;');
    s = s.split('<').join('&lt;');
    s = s.split('>').join('&gt;');
    s = s.split('\'').join('&quot;');
    s = s.split('\n').join('<br />');
    return s;
}

function getElementCommentPreview(e, f){
    var l = (document.layers) ? 1 : 0;
    if(l){
        f = f ? f : self;
        var a = f.document.layers;
        if(a[e]) return a[e];
        for (var w = 0; w < a.length;) return getElementCommentPreview(e, a[w++]);
    }
    if(document.all) return document.all[e];
    return document.getElementById(e);
}

//AjaxUpdate
function ajaxUpdate(url, param, callback, mymethod){
    var myAjax = new Ajax.Request(
        url,
        {
            method : 'post',
            parameters : param,
            onComplete: callback ? callback : null
        }
    );
}

//イベントリスナ登録
function addListener(elem, eventType, func, cap){
    if(elem.addEventListener){ //IE6以外
        elem.addEventListener(eventType, func, cap);
    }else if(elem.attachEvent){ //IE6
        elem.attachEvent('on' + eventType, func);
    }else{
        alert('使用しているブラウザは対応してません');
        return false;
    }
}

//イベントリスナ解除    
function removeListener(elem, eventType, func, cap){
    if(elem.removeEventListener){
        elem.removeEventListener(eventType, func, cap);
    }else if(elem.detachEvent){
        elem.detachEvent('on' + eventType, func);
    }
}

//バブリングとデフォルトイベントアクションの停止
function stopDefaultAndPropagation(e){
    //バブリング停止
    if(e.stopPropagation){
        e.stopPropagation();
    }
    if(window.event){
        window.event.cancelBubble = true;
    }
    //デフォルトイベントアクションを停止する
    if(e.preventDefault){
        e.preventDefault();
    }
    if(window.event){
        window.event.returnVelue = false;
    }
}

//ブラウザ判別
function setUserAgentInfo(){
    var browseAgent = navigator.userAgent.toLowerCase();
    if(browseAgent.indexOf("opera") != -1){
        btype = 4;
    }else if(browseAgent.indexOf("msie") !=-1 && document.all){
        btype = 1;
    }else if(browseAgent.indexOf("safari") != -1){
        btype = 3;
    }else if(browseAgent.indexOf("mozilla") != -1){
        btype = 2;
    }
}

//要素の位置を取得し、オブジェクトとして返す
function getElemPos(elem){
    var obj = new Object();
    obj.x = elem.offsetLeft;
    obj.y = elem.offsetTop;
    //上の階層を参照して値を補正
    while(elem.offsetParent){
        elem = elem.offsetParent;
        obj.x += elem.offsetLeft;
        obj.y += elem.offsetTop;
    }
    return obj;
}

//IEとFFでポップアップの要素生成を変える
function getAvailableElement(){
    if(btype == 1){
        return document.createElement('div');
    }else{
        return document.createElement('img');
    }
}

//透過PNG読み出し
function loadImg(elem, filePath){
    if(btype == 1){ //IE
        elem.style.filter = "progid:DXImageTransform.Microsoft.AlphaImageLoader(src='" + filePath + "', sizingMethod='scale')";
    }else{ //その他
        elem.src = filePath;
    }
}

//要素削除
function rme(id){
    $(id).parentNode.removeChild($(id));
}
