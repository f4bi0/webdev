var widgets = new function(){

    var self = this;

    self.BaseClass = function(){

        var self = this;
        this.name;
        this.element = function(){ return $('#'+self.name); }
        this.$ = function(selector){ return selector ? this.element().find(selector) : this.element(); }
        this.render = function(){
            if(!this.element()[0]){
                throw "Widget '"+this.name+"' could not be rendered - corresponding element not found";
            }
            ko.applyBindings(this, this.element()[0]);
            this.$('.place_widget').each(function(){
                $(this).replaceWith($('#'+$(this).attr('value')));
            });
            this.notify('rendered');
        }

        this.callbacks = {};

        this.on = function(name, callback){
            if(!this.callbacks[name]){
                this.callbacks[name] = [];
            }
            this.callbacks[name].push(callback);
            return this;
        }

        this.once = function(name, callback){
            callback.widget_callback_once = true;
            this.on(name, callback);
            return this;
        }

        this.runCallbacks = this.notify = function(name, data){
            if(!this.callbacks[name]) return;
            $.each(this.callbacks[name], function(i, cb){
                if(cb){
                    cb.call(self, data);
                    if(cb.widget_callback_once){
                        delete self.callbacks[name][i];
                    }
                }
            })
            return this;
        }

        this.done = function(data){
            this.runCallbacks('done', data);
        }

    }

    self.extend = function(obj){
        var base = new self.BaseClass();
        var funcNameRegex = /function (.{1,})\(/;
        var results = (funcNameRegex).exec(obj.constructor.toString());
        base.name = (results && results.length > 1) ? results[1] : "";
        base.child = obj;
        for(var i in base){
            obj[i] = base[i];
        }
        return obj;
    }

}

