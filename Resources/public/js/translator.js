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
        'click .translator-edit':'renderForm'
    },
    initialize:function(){
        this.$el = $(".translator-label-" + this.model.get('index'));
        this.template = _.template($("#tpl-translator-form").html());
        return this;
    },
    render:function(){
        this.$el.append(this.template(this.model.toJSON()));
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
        this.$el.css({ 'position' : 'static' });
        this.$('.translator-edit').css('visibility','hidden');    
        this.$('.translator-modal').slideDown();    
    },
    hideForm:function(){
        $("#translator-modal-background").fadeOut();
        this.$el.css({ 'position' : 'relative' });
        this.$('.translator-modal').fadeOut(0); 
        this.$('.translator-edit').css('visibility','visible');    
    }
});




