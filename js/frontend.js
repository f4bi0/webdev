var frontend = front = new function(){

    var self = this;

    self.BASE_URL = '';

    self.resolveURL = function(pseudo_url){
        if(pseudo_url.charAt(0)=='/'&&pseudo_url.charAt(1)!='/'){
            return pseudo_url.replace("/",self.BASE_URL);
        }
        return pseudo_url;
    }

    self.getJSON = function(url, params, callback){
        url = self.resolveURL(url);
        var req = $.getJSON(url,params,callback).fail(function(result){
            alert("REQUEST FAILED: \n\n getJSON("+url+") \n\n RESPONSE TEXT: '"+result.responseText+"'");
        });
        req.block = function(){
            var $modal = $('<div><span class="hourglass">&#8987;</span> Aguarde...</div>').dialog({
                   modal: true, width: 300, autoOpen: true
            });
            $modal.parents('.ui-dialog').find('.ui-dialog-titlebar').remove();
            self.charStorm.start();
            req.always(function(){
                $modal.remove();
                self.charStorm.stop();
            });
            return req;
        }
        return req;
    }

    self.getJSON_block = function(url,params,callback){
        return self.getJSON(url,params,callback).block();
    }

    self.showSuccessMsg = function(){}
    self.showErrorMsg = function(){}


    this.charStorm = new function(){
        this.drop = function(){
            var docHeight = parseInt($(document).height())-50;
            var order = Math.floor(Math.random() * 2);
            var left = Math.random() * $(document).width();
            var $char = $('<div style="color:white;position:absolute;z-index:-4000"></div>')
                .text(String.fromCharCode(Math.floor(Math.random()*9000)))
                .css({left:left,fontSize:Math.random() * 30,top:order==1?docHeight:0});
            $char.prependTo('body');
            $char.mouseenter(function(){
                $(this).remove();
            })
            var opts = {};
            if(order==1){
                opts.top = "-="+docHeight;
            } else {
                opts.top = "+="+docHeight;
            }
            $char.animate(opts,5000,function(){
                $(this).remove();
            });
        }

        this.progress = 0;

        this.start = function(intensity){
            this.progress = setInterval(this.drop, intensity||1000);
        }

        this.stop = function(){
            clearInterval(this.progress);
        }

    }

}