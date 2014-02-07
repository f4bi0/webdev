jq_utils = jquery_utils = new function(){

    var self = this;

    self.removeCloseButtonFromDialog = function($dialog){

    }

    self.datepicker = function(target,options){
        var $target = target instanceof jQuery ? target : $(target);
        var options = $.extend({
            dateFormat: 'dd/mm/yy',
            dayNames: [
                'Domingo','Segunda','Terça','Quarta','Quinta','Sexta','Sábado','Domingo'
            ],
            dayNamesMin: [
                'D','S','T','Q','Q','S','S','D'
            ],
            dayNamesShort: [
                'Dom','Seg','Ter','Qua','Qui','Sex','Sáb','Dom'
            ],
            monthNames: [
                'Janeiro','Fevereiro','Março','Abril','Maio','Junho','Julho','Agosto','Setembro',
                'Outubro','Novembro','Dezembro'
            ],
            monthNamesShort: [
                'Jan','Fev','Mar','Abr','Mai','Jun','Jul','Ago','Set',
                'Out','Nov','Dez'
            ],
            nextText: 'Próximo',
            prevText: 'Anterior',
            changeMonth : true,
            changeYear : true,
            yearRange : '2007:+0'
        },options||{});
        $target.datepicker(options);
        return $target;
    }

}