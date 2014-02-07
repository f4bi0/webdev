<?php
/*
 * 05/12/2013 - Fábio
*/

require_once(dirname(__FILE__).'/class.Frontend.php');

class UIException extends Exception
{
}

class UI
{
    static $title;
    static $uid;

    public static function getURL()
    {
        return Frontend::$config['base_url'] . str_replace('_', '/', strtolower(static::getId())) . '.php?';
    }

    public static function getCallURL($method_name)
    {
        if (!method_exists($class = get_called_class(), $method_name)) {
            throw new UIException($class . ': action method not found: ' . $method_name);
        }
        return static::getURL() . '&call=' . $method_name;
    }


    public static function testRequest()
    {
        $request = str_replace('/', '_', strtolower($_SERVER['REQUEST_URI']));
        return strstr($request, strtolower(get_called_class()));
    }

    public static function dispatch()
    {
        if (static::testRequest()) {
            static::$uid = get_called_class();
            if (isset($_REQUEST['call'])) {
                try {
                    $result = System::callMethodAssoc(get_called_class(), $_REQUEST['call'], $_REQUEST);
                    echo json_encode($result);
                } catch (Exception $e) {
                    echo get_class($e) . ': ' . $e->getMessage() . "\n";
                    print_r($e->getTrace());
                }
            } else {
                static::render();
            }
        }
    }

    public static function getId(){
        return static::$uid ?: get_called_class();
    }

    public static function render()
    {
        $self = static::getId();
        $title = static::$title ? : str_replace('_', ' ', $self);
        try {
            ob_start();
            foreach (get_class_methods($self) as $method) {
                if (substr($method, 0, 3) == 'ui_') {
                    static::$method();
                }
            }
            $output = ob_get_clean();
            $output = "
            <script>
                var self = $self = {};
                self.uid = '$self';
                self.$ = function(selector){ return $('#'+self.uid+' '+selector); }
                self.url = '".static::getURL()."';
                self.call = function(method, params){
                    params = params || {};
                    params.call = method;
                    return $.getJSON(self.url,params).fail(function(result){
                        alert('Failed call to method '+method+' at url: '+self.url+\"\\n\\n Response Text: \\n\\n\"+result.responseText);
                    });
                }
            </script>
            <div id=\"$self\">$output</div>";
            $output .= "<script>ko.applyBindings($self, \$('#$self')[0]);</script>";

        } catch (Exception $e) {
            ob_end_clean();
            $output = '<span class="ui-state-error">'.$e->getMessage().'</span>';
            $output.= '<div class=box><pre>'.print_r($e->getTrace(),1).'</pre></div>';
        }

        Frontend::render($title, $output);
    }

    //////////////////////////////////
    // Abaixo: utilitários

    public static function _ui_dateRange($k_start='data_inicial',$k_end='data_final',$v_start='today',$v_end='today')
    {
        $v_start = date('d/m/Y',strtotime($v_start));
        $v_end = date('d/m/Y',strtotime($v_end));
        ?>
         De: <input name="<?php echo $k_start; ?>" data-bind="value:<?php echo $k_start; ?>" size="8" />
        Até: <input name="<?php echo $k_end; ?>" data-bind="value:<?php echo $k_end; ?>" size="8" />
        <script>
            self.data_inicial = ko.observable('<?php echo $v_start; ?>');
            self.data_final = ko.observable('<?php echo $v_end; ?>');

            var options = {dateFormat:"dd/mm/yy",dayNames:["Domingo","Segunda","Terça","Quarta","Quinta","Sexta","Sábado","Domingo"],dayNamesMin:["D","S","T","Q","Q","S","S","D"],dayNamesShort:["Dom","Seg","Ter","Qua","Qui","Sex","Sáb","Dom"],monthNames:["Janeiro","Fevereiro","Março","Abril","Maio","Junho","Julho","Agosto","Setembro","Outubro","Novembro","Dezembro"],monthNamesShort:["Jan","Fev","Mar","Abr","Mai","Jun","Jul","Ago","Set","Out","Nov","Dez"],nextText:"Próximo",prevText:"Anterior",changeMonth:true,changeYear:true,yearRange:"2007:+0"};

            $('#<?php echo static::getId(); ?> [name=<?php echo $k_start; ?>]').datepicker(options);
            $('#<?php echo static::getId(); ?> [name=<?php echo $k_end; ?>]').datepicker(options);
        </script>
    <?php
    }

    public static function _ui_actionBtn($action, $params=null, $label = null, $class='bt')
    {
        $params_str = '';
        if ($params) {
            foreach (explode(',',$params) as $param) $params_str .= "$param : self.$param(),";
            $params_str = rtrim($params_str, ',');
        }
        $is_array = substr($action,-1)=='s';
        ?>
        <button class="<?php echo $class; ?>" data-bind="visible:!<?php echo $action ?>.loading(),click:<?php echo $action ?>"><?php echo $label ?: $action; ?></button>
        <span data-bind="visible:<?php echo $action ?>.loading()">Carregando...</span>
        <script>
            self.<?php echo $action; ?> = function () {
                self.<?php echo $action; ?>.loading(true);
                var params = {<?php echo $params_str ?>};
                var url = '<?php echo $url = static::getCallURL($action);  ?>';
                return $.getJSON(url, params)
                    .done(function (result) {
                        if (result instanceof Array) {
                            self.<?php echo $action ?>.result(ko.utils.arrayMap(result,
                                function (it) {
                                    return self.<?php echo $action ?>.map(it);
                                }));
                        } else {
                            self.<?php echo $action ?>.result(self.<?php echo $action ?>.map(result));
                        }
                    })
                    .complete(function () {
                        self.<?php echo $action; ?>.loading(false);
                    })
                    .fail(function (result) {
                        alert('getJSON failed at: \n\n ' + url + "\n\n Response was: \n\n " + result.responseText);
                    });
            }
            self.<?php echo $action; ?>.loading = ko.observable();
            self.<?php echo $action; ?>.result = <?php echo $is_array ? 'ko.observableArray()' : 'ko.observable()'; ?>;
            self.<?php echo $action; ?>.map = function (it) {
                return it;
            }
        </script>
    <?php
    }

    static function get_methods()
    {
        $f = new ReflectionClass($self = static::getId());
        $methods = array();
        foreach ($f->getMethods() as $m) {
            if ($m->class == $self && !strstr($m->name,'ui_')) {
                $methods[] = $m->name;
            }
        }
        $result = array();
        foreach ($methods as $method) {
            $method = new ReflectionMethod($self, $method);
            $params = array();
            foreach ($method->getParameters() as $param) {
                if ($param->isOptional()) {
                    $params[] = array('name' => $param->name, 'value' => $param->getDefaultValue(), 'optional' => true);
                } else {
                    $params[] = array('name' => $param->name, 'value' => '', 'optional' => false);
                }
            }
            $result[] = array(
                'name' => $method->name,
                'params' => $params
            );
        }
        return $result;
    }

    public static function filemtime()
    {
        $self = new ReflectionClass(static::getId());
        $fmtime = filemtime($self->getFileName());
        return $fmtime;
    }

    public static function _ui_auto_refresh(){

        ?>
        <script>
            var fmtime = <?php echo static::filemtime(); ?>;
            setInterval(function(){
                $.get('<?php echo static::getCallURL('filemtime'); ?>',function(result){
                    if(result!=fmtime){
                        location.href = '<?php echo static::getURL(); ?>';
                    }
                })
            },1000);
        </script>
    <?php
    }

    public static function _ui_get_methods()
    {
        ?>

        <div class="box get_methods" style="position:absolute;right:0;bottom:0;">
            <?php echo static::_ui_actionBtn('get_methods', '', 'List Methods'); ?>
            <script>
                self.get_methods.map = function (it) {
                    it.call = function () {
                        var params = {call: it.name};
                        $.each(it.params, function (i, param) {
                            params[param.name] = param.value;
                        });
                        var $modal = $('<div>Aguarde...</div>').dialog({
                            title: "Calling " + it.name + "()",
                            modal: true,
                            width: 800,
                            autoOpen: true
                        });
                        $.get('<?php static::getURL() ?>', params)
                            .done(function (result) {
                                $modal.html(result);
                                $modal.dialog('option','title','Output of '+it.name+'() : ');
                            })
                    }
                    return it;
                }
            </script>
            <button data-bind="click:function(){ get_methods.result([]); },visible:get_methods.result().length">Hide Methods</button>
            <ul data-bind="foreach:get_methods.result">
                <li class="item">
                    <form href="#" data-bind="event:{submit:call}">
                        <span data-bind="text:name,click:call" style="cursor:pointer"></span> (
                        <span data-bind="foreach:params">
                            <span data-bind="if:!optional">
                                <input data-bind="value:value,text:name,attr:{placeholder:'$'+name}"/>
                            </span>
                            <span data-bind="if:optional">
                                <input data-bind="value:value,text:name,attr:{placeholder:'$'+name+' = '+value}"/>
                            </span>
                        </span> )
                    </form>
                </li>
            </ul>
        </div>

    <?php
    }

    public static function _ui_actionIpt($action, $param, $params, $class='')
    {
        ?>
        <input id="<?php echo $action; ?>" class="<?php echo $class ?>" data-bind="value:<?php echo $action; ?>.ipt" />
        <script>
            var action = '<?php echo $action; ?>';
            self[action] = function(value){
                self[action].loading(true);
                $.getJSON(self.url,{
                    call : action,
                <?php echo $param ?> : value
            }).done(function(result){
                self[action].result(self[action].map(result));
            }).fail(function(result){
                    alert('Failed call to method '+action+' at url: '+self.url+"\n\n Response Text: \n\n "+result.responseText);
                });
            }
            self[action].loading = ko.observable(false);
            self[action].ipt = ko.observable('');
            self[action].last_value = ko.observable('');
            self[action].result = ko.observable(null);
            self[action].map = function(){}
            self[action].focus = function(){
                self[action].ipt('');
                setTimeout(function () {
                    self.$('#'+action).focus();
                }, 200);
            }
            self[action].ipt.subscribe(function(value){
                value = $.trim(value);
                if(value){
                    self[action].focus();
                    self[action].last_value(value);
                    self[action](value);
                }
            })
            $(function(){
                self[action].focus();
            })
        </script>
    <?php
    }


}

