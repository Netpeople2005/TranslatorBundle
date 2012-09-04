$.fn.toJSON=function(){
    var data = {};
    this.each(function(form) {
        var inputs = $(':input', this);
        inputs.each(function(index) {
            if(/(.+)\[(.+)\]/.test($(this).attr('name'))){          
                var name = $(this).attr('name').replace(']','')
                name = name.split('[') 
                if (data[name[0]] == undefined){
                    data[name[0]] = {};                                    
                }
                data[name[0]][name[1]] = $(this).val();                
            }else{
                data[$(this).attr('name')] = $(this).val();                
            }
        });
    });
    return data;
};

$.fn.populate=function(json){
    if ( $.type(json) !== 'object' ){
        return;
    }
    this.each(function(form) {
        var inputs = $(':input', this);
        inputs.each(function(index) {
            if ( $(this).attr('name') in json ){
                $(this).val(json[$(this).attr('name')]);                
            }else if(/(.+)\[(.+)\]/.test($(this).attr('name'))){          
                var name = $(this).attr('name').replace(']','')
                name = name.split('[')
                if ( name[0] in json ){
                    $(this).val(json[name[0]][name[1]]);
                }        
            }
        });
    });
};

$(function(){
    $.each(TRANSLATOR_LOCALES, function(indice, valor){
        $("#translator-form .translator-form select[name=locale]").append('<option>' + valor + '</option>');
    });
    $("#translator-form .translator-form select[name=locale]").on('change',function(){
        var json = $("#translator-form .translator-form").toJSON();
        json.id = $("#translator-form .translator-form").data('id');
        json.parameters = $("#translator-form .translator-form").data('parameters');
        translatorLoading(true);
        $.getJSON(TRANSLATOR_URL, json , function(json){
            translatorLoading(false,"Listo...!!!");
            $("#translator-form .translator-form").populate(json);
        });
    });
    $("#translator-list").css({
        'top' : calculeTranslatorListTop(),
        'display' : 'block'
    });
    $("#translator-list ul li a").on('click',function(){
        $("#translator-list #translator-form").css({
            //'top':$(this).offset().top - 5
            });
        $("#translator-list ul li").removeClass('hover');
        $(this).parent().addClass('hover');
        $(this).parent().append($("#translator-list #translator-form").show());
        $("#translator-form .translator-form").populate($(this).data('json'));
        $("#translator-form .translator-form").data({ 
            id : $(this).data('json').id, 
            parameters : $(this).data('json').parameters
        });
    });
    $("#translator-list").on('click',function(){
        $(this).stop();
        $(this).animate({
            'top': "-1px"
        });
    });
    $("body").on('click',function(event){
        if($(event.target).is('#translator-list,#translator-list *')){
            return;
        }
        $("#translator-list ul li").removeClass('hover');
        $("#translator-list").stop();
        $("#translator-list").animate({
            'top': calculeTranslatorListTop()
        });
        $("#translator-list #translator-form").hide();
    });
    $("#translator-form form").on('submit',function(event){
        event.preventDefault();
        var json = $("#translator-form .translator-form").toJSON();
        json.id = $("#translator-form .translator-form").data('id');
        json.parameters = $("#translator-form .translator-form").data('parameters');
        translatorLoading(true);
        $.post(TRANSLATOR_URL, json , function(json){
            translatorLoading(false,"Guardado con Exito...!!!");
            $("#translator-form .translator-form").populate(json);
            $("#translator-list ul li a[data-id='" + json.id + "']").data('json',$("#translator-form .translator-form").toJSON());
        });
    });
});

function calculeTranslatorListTop(){
    return 25 - $("#translator-list").height();
}

function translatorLoading(show, mensaje){
    if ( show === true ){
        $(".translator-buttons :submit").attr('disabled','disabled');
        $(".translator-buttons img").show(0);  
        $("#translator-message").html("").show(0);
    }else if(show === false){
        $(".translator-buttons :submit").attr('disabled',false);
        $(".translator-buttons img").hide(0);
        $("#translator-message").html(mensaje == 'undefinde' ? '' : mensaje);
        $("#translator-message").stop().show(0).fadeOut(6000,function(){
            $(this).html('').show(0);
        });
    }
}


