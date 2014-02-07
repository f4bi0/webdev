/**
 * Created by prog on 14/11/13.
 */
ko_utils = knockout_utils = new function(){

    var self = this;

    ////////////////////////////////
    // GENERAL UTILS
    self.selectable = function(object, value, sync_with){
        object.select = function(){
            sync_with(value);
        }
        object.unselect = function(){
            sync_with(null);
        }
        object.selected = ko.computed(function(){
            return value == sync_with();
        });
        object.toggle_selection = function(){
            object.selected() ? object.unselect() : object.select();
        }
        return object;
    }

    self.selectableArray = function(object, value, sync_with){
        object.select = function(){
            sync_with.push(value);
        }
        object.unselect = function(){
            sync_with.remove(value);
        }
        object.selected = ko.computed(function(){
            var arr = sync_with();
            if(!arr.length) return;
            for(var i=0;i<arr.length;i++){
                if(arr[i] == value){
                    return true;
                }
            }
            return false;
        });
        object.toggle_selection = function(){
            object.selected() ? object.unselect() : object.select();
        }
        return object;
    }

    self.attr = function(object, attr, _default){
        return ko.computed(function(){
           return object() ? object()[attr] : (_default||null);
        });
    }

    //////////////////////////////////////////
    // DATASETS utils
    self.datasets = {};
    self.datasets.filtered = function(dataset, filter){
       if(typeof filter == 'string'){
           var property = filter;
           var filter = function(it){
               return ko.utils.unwrapObservable(it[property]);
           }
       }
      return ko.computed(function(){
           var result = [];
           ko.utils.arrayForEach(ko.utils.unwrapObservable(dataset), function(it){
               if(filter(it)){
                   result.push(it);
               }
           })
           return result;
       });
    }

    self.datasets.set = function(dataset, property, value){
        ko.utils.arrayForEach(ko.utils.unwrapObservable(dataset),function(it){
            if(ko.isObservable(it[property])){
                it[property](value);
            } else {
                it[property] = value;
            }
        })
    }

    self.datasets.sortable = function(dataset){
        dataset.sortOrder = true;
        dataset.sortBy = function (col) {
            dataset.sortOrder = !dataset.sortOrder;
            try{
                dataset.sort(function (a, b) {
                    var val_a = ko.utils.unwrapObservable(a[col]);
                    var val_b = ko.utils.unwrapObservable(b[col]);
                    if (val_a == val_b) {
                        return 0;
                    }
                    if (val_a > val_b) {
                        return dataset.sortOrder ? 1 : -1;
                    }
                    if (val_a < val_b) {
                        return dataset.sortOrder ? -1 : 1;
                    }
                })
            } catch(e) {
                alert("Could not sort dataset by this col: "+col+", "+e);
            }
        }
        dataset.getSorter = function(sortBy){
            return function(){
                dataset.sortBy(sortBy);
            }
        }
    }

    self.datasets.loadable = function (dataset, load_func, url, params, mapper) {
        dataset.loading = ko.observable(false);
        dataset.getLoadParams = function () {
            try{
                if(!params) return {};
                if(typeof(params) == 'function'){
                    return params();
                }
                if(typeof(params) == 'string'){
                    params = params.split(/,/g);
                }
                var obj = {};
                for(var i=0; i<params.length;i++){
                    obj[params[i]] = ko.utils.unwrapObservable(dataset[params[i]]);
                }
                return obj;
            } catch(e) {
                alert("Could not getLoadParams for url: "+url+", "+e);
            }
        }
        dataset.load = function () {
            dataset([]);
            dataset.loading(true);
            return load_func(url, dataset.getLoadParams())
                .always(function () {
                    dataset.loading(false);
                })
                .done(function (raw_dataset) {
                    try{
                        var items = [];
                        if(mapper){
                            for(var i=0;i<raw_dataset.length;i++){
                                items.push(mapper(raw_dataset[i]));
                            }
                        } else {
                           items = raw_dataset;
                        }
                    } catch(e) {
                        alert("Could not map results from url: "+url+", "+ e);
                    }
                    dataset(items);
                });
        }
    }

    self.datasets.grouped = function (dataset, group_by, cliche, mapper, order_by) {
        var cliche = typeof cliche == 'string' ? cliche.split(/,/g) : cliche;
        var order_by = order_by || group_by;
        var computed = ko.computed(function () {
            try {
                var indexed = {};
                ko.utils.arrayForEach(dataset(), function (it) {
                    var k = ko.isObservable(it[group_by]) ? it[group_by]() : it[group_by] ;
                    if (typeof indexed[k] == 'undefined') {
                        indexed[k] = {};
                        indexed[k][group_by] = k;
                        indexed[k].subset = [];
                        if (cliche) {
                            for (var i = 0; i < cliche.length; i++) {
                                var col = cliche[i];
                                indexed[k][col] = ko.isObservable(it[col]) ? it[col]() : it[col] ;
                            }
                        }
                    }
                    indexed[k].subset.push(it);
                });
                var groups = [];
                try{
                    for(var i in indexed){
                        groups.push(mapper ? mapper(indexed[i]) : indexed[i]);
                    }
                } catch(e){
                    throw "Group mapper is doing something wrong: "+e;
                }
                groups.indexed = indexed;
                groups.sort(function (a, b) {
                    return ko.utils.unwrapObservable(a[order_by]) > ko.utils.unwrapObservable(b[order_by]);
                });
                return groups;
            } catch (e) {
                alert("Could not group dataset by " + group_by + ", " + e);
            }
        });
        return computed;
    }


}

ko.bindingHandlers.hoverObservable = {
    init : function(element, valueAccessor, allBindings, viewModel, context){

        var flag = ko.utils.unwrapObservable(valueAccessor());
        var obj = {};
        obj[flag] = ko.observable(false);
        var newContext = context.extend(obj);
        $(element).mouseover(function(){
            obj[flag](true);
        }).mouseleave(function(){
                obj[flag](false);
            })
        ko.applyBindingsToDescendants(newContext, element);
        return { controlsDescendantBindings : true };
    }

}

ko.bindingHandlers.hidden = {
    update : function(element, valueAccessor, allBindings, viewModel, context){
        $(element).css('visibility',ko.utils.unwrapObservable(valueAccessor()) ? 'hidden' : 'visible');
    }
}


