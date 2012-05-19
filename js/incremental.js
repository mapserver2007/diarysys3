/**
 * DIARY SYSTEM - incremental.js
 * @author Tanaka Ryuichi
 * @version 1.0.0
 */

var TagGrep = Class.create();
TagGrep.prototype = {
    initialize : function(obj){
        try{
            this.field_name = obj.tag;
            this.callback = eval(obj.func);
            this.oldhtml = $(obj.tag).innerHTML;
            Event.observe($(obj.grep), 'keyup', this.hilite.bindAsEventListener(this), false);
            Event.observe($(obj.grep), 'focus', this.hilite.bindAsEventListener(this), false);
            this.callback();
        }catch(e){
            //field name not found!
        }
    },
    hilite : function(e){
        try{
            this.query = e.target ? e.target.value : e.srcElement.value;
            if(this.query.length >= 0 && this.oldhtml){
                $(this.field_name).innerHTML = this.oldhtml;
                if(this.query.length == 0){
                    this.callback();
                    return;
                }
                var newhtml = this.hiliteHTML(this.query, this.oldhtml);
                $(this.field_name).innerHTML = newhtml;
                this.callback();
            }
        }catch(err){
            //tags not display
        }
    },
    hiliteHTML : function(query, html){
        var re = [];
        var q = query.toLowerCase();
        re.push(q);
        re = new RegExp('('+re.join("|")+')', "gi");
        
        var subs = function(match){
            if(!match) return "";
            var str = "";
            str = '<span class="tagHilite">'+match+'</span>';
            return str;
        };

        var last = 0;
        var tag = '<';
        var skip = false;
        var skipre = new RegExp('^(script|style|textarea)', 'gi');
        var part = null;
        var result = '';
    
        while (last >= 0) {
            var pos = html.indexOf(tag, last);
            if(pos < 0){
                part = html.substring(last);
                last = -1;
            }else{
                part = html.substring(last, pos);
                last = pos + 1;
            }
            if(tag == '<'){
                if(!skip){
                    part = part.replace(re, subs);
                }else{
                    skip = false;
                }
            }else if(part.match(skipre)){
                skip = true;
            }
            result += part + (pos < 0 ? '' : tag);
            tag = tag == '<' ? '>' : '<';
        }
        return result;
    }
};