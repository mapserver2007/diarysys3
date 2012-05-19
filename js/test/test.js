
function test_tag_execute(){
	var tags = getCommentTags(edButtons);
	var str = "@@@@@[amazon]title=レポート・論文の書き方入門,author=河野 哲也,image=http://ecx.images-amazon.com/images/I/51RQ1Q649HL._SL160_.jpg,url=http://www.amazon.co.jp/gp/redirect.html%3FASIN=4766409698%26tag=ws%26lcode=xm2%26cID=2025%26ccmID=165953%26location=/o/ASIN/4766409698%253FSubscriptionId=02GA6TH96EC9SQKGEJ82[/amazon]owata[amazon]title=中国株次の一手教えます!! (NET M@NEY BOOKS),author=阿部 享士,image=http://ecx.images-amazon.com/images/I/51z32lm0ejL._SL160_.jpg,url=http://www.amazon.co.jp/gp/redirect.html%3FASIN=4331513041%26tag=ws%26lcode=xm2%26cID=2025%26ccmID=165953%26location=/o/ASIN/4331513041%253FSubscriptionId=02GA6TH96EC9SQKGEJ82[/amazon]おおおおおおお";
	var res = test_tag_test(tags, str);
	document.getElementById('kekka').innerHTML = res;
}


function test_tag_test(tags, s) {
	var regColor = /\[color\=\#([0-9a-fA-F]{3})\]/g;
	var regImage = /\[image\=(https?:\/\/[\-\_\.\!\~\*\'\(\)a-zA-Z0-9\;\/\?\:\@\&\=\+\$,\%\#]+\.(gif|jpe?g|png|bmp))\]/g;
	var regImage2 = /\[image\](https?:\/\/[\-\_\.\!\~\*\'\(\)a-zA-Z0-9\;\/\?\:\@\&\=\+\$,\%\#]+\.(gif|jpe?g|png|bmp))/g;
	var regURL = /\[url\=(https?:\/\/[\-\_\.\!\~\*\'\(\)a-zA-Z0-9\;\/\?\:\@\&\=\+\$,\%\#]+)\]/g;
	var regURL2 = /\[url\](https?:\/\/[\-\_\.\!\~\*\'\(\)a-zA-Z0-9\;\/\?\:\@\&\=\+\$,\%\#]+)/g;
	var regSnapURL = /\[snap\](https?:\/\/[\-\_\.\!\~\*\'\(\)a-zA-Z0-9\;\/\?\:\@\&\=\+\$,\%\#]+)/g;
	var regStyle = /\[style\=([a-zA-Z0-9\:\;\ \-\_\'\!\*\(\)\/\.\,\%\#]+\;)\]/g;

	for (var i = 0; i < tags.length; i++) {
		// Color
		if(tags[i] == "color") {
			s = s.replace(regColor, '<span style=\"color: \#$1;\">');
		} else if(tags[i] == "/color") {
			s = s.replace(/\[\/color\]/g, '<\/span>');
		}
		// left center right
		else if(tags[i] == "left" || tags[i] == "center" || tags[i] == "right"){
			s = s.split('[' + tags[i] + ']').join('<p style="text-align: ' + tags[i] + ';">');
		} else if(tags[i] == "/left" || tags[i] == "/center" || tags[i] == "/right"){
			s = s.split('[' + tags[i] + ']').join('</p>');
		}
		// code
		else if(tags[i] == "code"){
			s = s.split('[' + tags[i] + ']').join('<pre class="prettyprint">');
		} else if(tags[i] == "/code"){
			s = s.split('[' + tags[i] + ']').join('</pre>');
			//s = s.replace(/<br>/g, '&nbsp;<br>');
		}
		// Image
		else if(tags[i] == "image") {
			s = s.replace(regImage, '<a href=\"$1\" rel=\"lightbox\"><img src=\"$1\" alt=\"');	//[image=URI]TEXT[/image]
			s = s.replace(regImage2, '<a href=\"$1\" rel=\"lightbox\"><img src=\"$1\" alt=\"$1\" title=\"$1');	//[image]URI[/image]
		} else if(tags[i] == "/image") {
			s = s.replace(/\[\/image\]/g, '\" class=\"pic\" \/></a>');
		}
		/*
		else if(tags[i] == "image") {
			s = s.replace(regImage, '<img src=\"$1\" alt=\"');			//[image=URI]TEXT[/image]
			s = s.replace(regImage2, '<img src=\"$1\" alt=\"$1\" title=\"$1');	//[image]URI[/image]
		} else if(tags[i] == "/image") {
			s = s.replace(/\[\/image\]/g, '\" class=\"pic\" \/>');
		}
		*/
		// URL
		else if(tags[i] == "url") {
			s = s.replace(regURL, '<a href=\"$1\">');	//[url=URI]TEXT[/url]
			s = s.replace(regURL2, '<a href=\"$1\">$1');	//[url]URI[/url]
		} else if(tags[i] == "/url") {
			s = s.replace(/\[\/url\]/g, '<\/a>');
		}
		// Snap
		else if(tags[i] == "snap") {
			s = s.replace(regSnapURL, '<a href=\"javascript:void(0);\" style=\"text-decoration: none;\"><span onclick=\"showSnapShot(\'$1\', this);\"><img src=\"images/snap.gif\" border=\"0\">');
		} else if(tags[i] == "/snap") {
			s = s.replace(/\[\/snap\]/g, '<\/span><\/a>');
		}
		// Style
		else if(tags[i] == "style") {
			s = s.replace(regStyle, '<span style=\"$1\">');
		} else if(tags[i] == "/style") {
			s = s.replace(/\[\/style\]/g, '<\/span>');
		}
		// Amazon
		else if(tags[i] == "amazon") {
			//var amazon_obj = {};
			var ary = s.match(/\[amazon\].+?\[\/amazon\]/g);
			var amazon_str = "";
			var amazon_ary = [];
			for(var k = 0; k < ary.length; k++){
				var str = ary[k].replace(/\[amazon\]/g, '');
				str = str.replace(/\[\/amazon\]/g, '');
				var test = str.split(",");
				//s = s.replace(/\[amazon\].+?\[\/amazon\]/, '<p>'+str+'<\/p>');
				var amazon_obj = {};
				for(var j = 0; j < test.length; j++){
					var elem = test[j].split("=");
					if(elem[0] == "url"){
						if(elem.length > 2){
							var url_join = "";
							for(var n = 1; n < elem.length; n++){url_join += (elem[n] + "=");}
							url_join = url_join.slice(0, -1);
							if(url_join.match(/(https?:\/\/[\-\_\.\!\~\*\'\(\)a-zA-Z0-9\;\/\?\:\@\&\=\+\$,\%\#]+)/g)){
								amazon_obj.url = url_join;
							}
						}
					}else if(elem[0] == "image"){
						if(elem[1].match(/(https?:\/\/[\-\_\.\!\~\*\'\(\)a-zA-Z0-9\;\/\?\:\@\&\=\+\$,\%\#]+\.(gif|jpe?g|png|bmp))/g)){
							amazon_obj.image = elem[1];
						}
					}else if(elem[0] == "title"){
						amazon_obj.title= elem[1];
					}else if(elem[0] == "author"){
						amazon_obj.author= elem[1];
					}
				}
				amazon_ary.push(amazon_obj);
			}
			//s = s.replace(/\[amazon\].+?\[\/amazon\]/g, amazon_str);
		} else if(tags[i] == "/amazon") {
			for(var m = 0; m < amazon_ary.length; m++){
				var html = '<div class="ds_entry_amazon_box">';
				html += '<div style="float:left;width:auto;"><a href="'+amazon_ary[m].url+'"><img class="pic" src="'+amazon_ary[m].image+'" \/><\/a><\/div>';
				html += '<div style="float:left;width:auto;">'
				html += '<p>'+amazon_ary[m].title+'<\/p>';
				html += '<p>'+amazon_ary[m].author+'<\/p>';
				html += '<\/div><div style="clear:both;" />';
				html += '<\/div>';
				//var html = '<div><img src="'+amazon_ary[m].image+'" \/>'+amazon_ary[m].title+'<\/div>';
				s = s.replace(/\[amazon\].+?\[\/amazon\]/, html);
			}
		}
		
		// Default & Others
		else{
			s = s.split('[' + tags[i] + ']').join('<' + tags[i] + '>');
		}
	}
	return s;
}
