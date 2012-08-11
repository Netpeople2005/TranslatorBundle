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
        'click .translator-link':'renderForm'
    },
    initialize:function(){
        this.template = _.template($("#tpl-translator-form").html());
        return this;
    },
    render:function(){
        this.$el.html(this.template(this.model.toJSON()));
        console.log(this.$el)
        return this.$el;
    },
    updateModel:function(){
        console.log(this.$el.toJSON())
        var vista = this;
        this.model.save(this.$el.toJSON(),{
            success:function(model){
                console.log("listo")
                vista.hideForm();
            }
        })
    },
    renderForm:function(){
        $("#translator-modal-background").fadeIn();
        $("#translator-list").css({'position' : 'static' ,'visibility':'hidden'});
        this.$('.translator-modal').slideDown();    
    },
    hideForm:function(){
        $("#translator-modal-background").fadeOut();
        $("#translator-list").css({'position' : 'absolute','visibility':'visible'});
        this.$('.translator-modal').fadeOut(0); 
    }
});


$(function(){
    $("#translator-list").css({'top' : calculeTranslatorListTop()});
    $("#translator-list").on('mouseover',function(){
        $("#translator-list").stop();
        $(this).animate({'top': "-5px"});
    });
    $("#translator-list").on('mouseout',function(){
        $("#translator-list").stop();
        $(this).animate({'top': calculeTranslatorListTop() });
    });
});

function calculeTranslatorListTop(){
    return 5 - $("#translator-list").height();
}



