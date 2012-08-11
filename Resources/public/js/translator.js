$.fn.toJSON=function(){
    var data = {};
    this.each(function(form) {
        form = $(':input', this);
        form.each(function(index) {
            data[$(this).attr('name')] = $(this).val();
        });
    });
    return data;
};

var Message = Backbone.Model.extend({
    urlRoot: TranslatorURL,
    defaults:{
        id: null,
        index:null,
        value:null,
        parameters:null,
        domain:null,
        locale:null
    },
    initialize:function(){
        this.on('change:parameters',function(model, params){
            model.set('parameters',model.previous('parameters'));
        },this);
    }
});

var MessagesCollection = Backbone.Collection.extend({
    url: TranslatorURL,
    model: Message
});

var MessageView = Backbone.View.extend({
    events:{
        'click .translator-save':'updateModel',
        'click .translator-close':'hideForm',
        'click .translator-link':'renderForm',
        'change .translator-domain-select':'getModelByLocale'
    },
    initialize:function(){
        this.template = _.template($("#tpl-translator-form").html());
        return this;
    },
    render:function(){
        this.$el.html(this.template(this.model.toJSON()));
        _.each(TranslatorLanguages,function(lan){
            this.$(".translator-domain-select").append('<option>' + lan + '</option>');            
        },this);
        this.$(".translator-domain-select").val(this.model.get('locale'));
        return this.$el;
    },
    updateModel:function(){
        var vista = this;
        this.model.save(this.$el.toJSON(),{
            success:function(model){
                vista.hideForm();
            }
        })
    },
    renderForm:function(){
        $("#translator-modal-background").fadeIn();
        $("#translator-list").css({
            'position' : 'static' ,
            'visibility':'hidden'
        });
        this.$('.translator-modal').slideDown();    
    },
    hideForm:function(){
        $("#translator-modal-background").fadeOut();
        $("#translator-list").css({
            'position' : 'absolute',
            'visibility':'visible'
        });
        this.$('.translator-modal').fadeOut(0); 
    },
    getModelByLocale:function(){
        this.model.set('locale',this.$(".translator-domain-select").val());
        var vista = this;
        $.getJSON(TranslatorURL, this.model.toJSON(),function(data){
            vista.$('[name=value]').val(data.value);
            vista.model.set('value',data.value);
        });
    }
});


$(function(){
    $("#translator-list").css({
        'top' : calculeTranslatorListTop()
        });
    $("#translator-list").on('mouseover',function(){
        $("#translator-list").stop();
        $(this).animate({
            'top': "-5px"
        });
    });
    $("#translator-list").on('mouseout',function(){
        $("#translator-list").stop();
        $(this).animate({
            'top': calculeTranslatorListTop()
        });
    });
});

function calculeTranslatorListTop(){
    return 20 - $("#translator-list").height();
}



