function htmlspecialchars(string, quote_style, charset, double_encode) {
    // Convert special characters to HTML entities
    //
    // version: 1109.2015
    // discuss at: http://phpjs.org/functions/htmlspecialchars    // +   original by: Mirek Slugen
    // +   improved by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
    // +   bugfixed by: Nathan
    // +   bugfixed by: Arno
    // +    revised by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)    // +    bugfixed by: Brett Zamir (http://brett-zamir.me)
    // +      input by: Ratheous
    // +      input by: Mailfaker (http://www.weedem.fr/)
    // +      reimplemented by: Brett Zamir (http://brett-zamir.me)
    // +      input by: felix    // +    bugfixed by: Brett Zamir (http://brett-zamir.me)
    // %        note 1: charset argument not supported
    // *     example 1: htmlspecialchars("<a href='test'>Test</a>", 'ENT_QUOTES');
    // *     returns 1: '&lt;a href=&#039;test&#039;&gt;Test&lt;/a&gt;'
    // *     example 2: htmlspecialchars("ab\"c'd", ['ENT_NOQUOTES', 'ENT_QUOTES']);    // *     returns 2: 'ab"c&#039;d'
    // *     example 3: htmlspecialchars("my "&entity;" is still here", null, null, false);
    // *     returns 3: 'my &quot;&entity;&quot; is still here'
    var optTemp = 0,
        i = 0,        noquotes = false;
    if (typeof quote_style === 'undefined' || quote_style === null) {
        quote_style = 2;
    }
    string = string.toString();
    if (double_encode !== false) { // Put this first to avoid double-encoding
        string = string.replace(/&/g, '&amp;');
    }
    string = string.replace(/</g, '&lt;').replace(/>/g, '&gt;');
    var OPTS = {
        'ENT_NOQUOTES': 0,
        'ENT_HTML_QUOTE_SINGLE': 1,
        'ENT_HTML_QUOTE_DOUBLE': 2,
        'ENT_COMPAT': 2,
        'ENT_QUOTES': 3,
        'ENT_IGNORE': 4
    };
    if (quote_style === 0) {
        noquotes = true;
    }
    if (typeof quote_style !== 'number') { // Allow for a single string or an array of string flags
        quote_style = [].concat(quote_style);
        for (i = 0; i < quote_style.length; i++) {
            // Resolve string input to bitwise e.g. 'ENT_IGNORE' becomes 4
            if (OPTS[quote_style[i]] === 0) {
                noquotes = true;
            }
            else
            if (OPTS[quote_style[i]])
            {
                optTemp = optTemp | OPTS[quote_style[i]];
            }
        }
        quote_style = optTemp;
    }

    if (quote_style & OPTS.ENT_HTML_QUOTE_SINGLE)
    {
        string = string.replace(/'/g, '&#039;');
    }
    if (!noquotes)
    {
        string = string.replace(/"/g, '&quot;');
    }
    string = string.replace(/\n/g, '<br />');
    return string;
}

MAILBOX_Message = Backbone.Model.extend({

    idAttribute: 'id',

    readMessage: function(actionParams){

        var that = this;
        $.ajax({
            'type': 'POST',
            'url': OWM.Mailbox.get('authorizationResponderUrl'),
            'data': {
                'actionParams': actionParams
            },
            'success': function(data){
                if (typeof data.error != 'undefined')
                {
                    OWM.error(data.error);
                }
                else
                {
                    if (typeof data.authorizationActionText != 'undefined')
                    {
                        OWM.info(data.authorizationActionText);
                    }

                    that.set(data);
                }
            },
            'dataType': 'json'
        });
    }

});

MAILBOX_MessageView = Backbone.View.extend({
    template: function(data){ return _.template($('#dialogChatMessagePrototypeBlock').html(), data); },

    initialize: function(){
        this.setElement(this.template(this.model.attributes));
        this.text = $('.owm_chat_bubble', this.$el);
    },

    render: function(){

        this.$el.attr('id', 'messageItem'+this.model.get('id'));

        if (!this.model.get('isSystem')){
            var attachments = this.model.get('attachments');
            if (attachments.length != 0){
                var i = 0;

                if (attachments[i]['type'] == 'image'){
                    this.$el.addClass('ow_dialog_picture_item');
                    $('.owm_chat_bubble', this.$el).html( '<a href="'+attachments[i]['downloadUrl']+'" target="_blank"><img src="'+attachments[i]['downloadUrl']+'" /></a>' );
                }
                else{
                    this.$el.addClass('fileattach');

                    $('.owm_chat_bubble', this.$el).html( '<a href="'+attachments[i]['downloadUrl']+'" target="_blank">'+OWM.Mailbox.formatAttachmentFileName(attachments[i]['fileName'])+'</a>' );
                }
            }
            else{
//                html = htmlspecialchars(this.model.get('text'), 'ENT_QUOTES');
                html = this.model.get('text');
                $('.owm_chat_bubble', this.$el).html( html );
                $('.owm_chat_bubble', this.$el).autolink();
            }
        }

        if (this.model.get('isAuthor')){
            this.$el.addClass('owm_chat_bubble_mine_wrap');
            this.text.addClass('owm_chat_bubble_mine');
        }

        if (!this.model.get('readMessageAuthorized')){
            var that = this;
            this.$el.on('click', '.callReadMessage', function(e){
                that.model.readMessage($(this).attr('id'));
            });
        }

        return this;
    },

});

MAILBOX_MailMessageView = Backbone.View.extend({
    template: function(data){ return _.template($('#dialogMailMessagePrototypeBlock').html(), data) },

    initialize: function(){
        if (this.model.get('isAuthor')){
            this.model.set('profileUrl', OWM.Mailbox.get('user').profileUrl);
            this.model.set('avatarUrl', OWM.Mailbox.get('user').avatarUrl);
            this.model.set('displayName', OWM.Mailbox.get('user').displayName);
        }
        else{
            var user = OWM.Mailbox.userListCollection.findWhere({opponentId: this.model.get('senderId')});
            this.model.set('profileUrl', user.get('profileUrl'));
            this.model.set('avatarUrl', user.get('avatarUrl'));
            this.model.set('displayName', user.get('displayName'));
        }

        this.setElement(this.template(this.model.attributes));
    },

    render: function(){

        this.$el.attr('id', 'messageItem'+this.model.get('id'));

        if (!this.model.get('isSystem')){

//            html = htmlspecialchars(this.model.get('text'), 'ENT_QUOTES');
            html = this.model.get('text');
            $('.owm_mail_txt', this.$el).html( html );
            $('.owm_mail_txt', this.$el).autolink();

            var attachments = this.model.get('attachments');
            if (attachments.length != 0){

                for (var i=0; i<attachments.length; i++){

                    var attachment = $('#mailboxMailMessageAttachmentPrototypeBlock').clone();
                    attachment.removeAttr('id');

                    $('a', attachment).prepend( OWM.Mailbox.formatAttachmentFileName(attachments[i]['fileName']) );
                    $('a', attachment).attr('href', attachments[i]['downloadUrl']);
                    $('.owm_mail_attach_size', attachment).html( OWM.Mailbox.formatAttachmentFileSize(attachments[i]['fileSize']) );


                    $('.owm_mail_msg_cont', this.$el).append( attachment );
                }
            }
        }

        if (this.model.get('isAuthor')){
            this.$el.addClass('owm_mail_msg_mine_wrap');
        }

        if (!this.model.get('readMessageAuthorized')){
            var that = this;
            this.$el.on('click', '.callReadMessage', function(e){
                that.model.readMessage($(this).attr('id'));
            });
        }

        return this;
    }
});

MAILBOX_MessageList = Backbone.Collection.extend({
    model: MAILBOX_Message,
    comparator: function(model){
        return model.get('timeStamp');
    }
});

MAILBOX_UnreadMessageList = Backbone.Collection.extend({
    model: MAILBOX_Message
});

MAILBOX_SidebarMenuItem = Backbone.Model.extend({
    title: 'Default',
    selected: false,
    mode: 'default'
});

MAILBOX_SidebarMenuItemView = Backbone.View.extend({

    events: {
        'click .owm_sidebar_sub_menu_item_url': 'highlight'
    },

    initialize: function(){
        this.setElement($('#menuItem_'+this.model.get('mode')));
        this.model.on('change:selected', this.changeSelected, this);
    },

    render: function(){
        this.changeSelected();

        return this;
    },

    changeSelected: function(model, value, options){

        if (this.model.get('selected')){
            this.$el.addClass('owm_sidebar_sub_menu_item_active');
        }
        else{
            this.$el.removeClass('owm_sidebar_sub_menu_item_active');
        }
    },

    highlight: function(){
        this.model.set({selected: true});
    }

});

MAILBOX_SidebarMenuItemList = Backbone.Collection.extend({
    model: MAILBOX_SidebarMenuItem,

    initialize: function() {
        this.on('change:selected', this.onSelectedChanged, this);
    },

    onSelectedChanged: function(changedModel) {

        if (changedModel.get('selected') === true){
            this.each(function(model) {
                if (changedModel.get('mode') != model.get('mode') && model.get('selected') === true){
                    model.set('selected', false);
                }
            });
        }
    }
});

MAILBOX_SidebarMenu = Backbone.Model.extend({
    initialize: function(){
        this.itemList = new MAILBOX_SidebarMenuItemList(this.get('list'));
    }
});

MAILBOX_SidebarMenuView = Backbone.View.extend({
    el: function(){
        return $('#mailboxSidebarMenu');
    },

    initialize: function(){
        this.render();
    },

    render: function(){
        _.each(this.model.itemList.models, function (item){
            var view = new MAILBOX_SidebarMenuItemView({model: item});

            this.$el.append(view.render().$el);
        }, this);
    }
});

MAILBOX_ConversationItem = Backbone.Model.extend({
    selected: false,

    initialize: function(){

        var conversationUnread = OWM.Mailbox.unreadMessageList.findWhere({convId: this.get('conversationId')});
        if ( conversationUnread ){
            this.set({selected: true});
        }

        var that = this;
        OW.bind('mailbox.new_message_notification', function(data){
            if (data.message.convId == that.get('conversationId')){
                that.set({selected: true});
            }
        });
    }
});

MAILBOX_ConversationItemView = Backbone.View.extend({
    template: function(data){ return _.template($('#mailboxSidebarItemPrototype').html(), data); },

    events: {
        'click .owm_user_list_item': 'openConversation'
    },

    initialize: function(){
        this.setElement(this.template(this.model.attributes));
        this.model.on('change:selected', this.changeSelected, this);
        this.model.on('change:lastMessageTimestamp', this.changeLastMessageTimestamp, this);
    },

    render: function(){

        if (this.model.get('mode') == 'mail'){
            $('#mailboxSidebarItemConversationsMode', this.$el).addClass('owm_sidebar_convers_status_mail');
        }

        if (this.model.get('mode') == 'chat'){
            $('#mailboxSidebarItemConversationsMode', this.$el).addClass('owm_sidebar_convers_status_chat');
        }

        if (this.model.get('onlineStatus') !== false){
            $('#mailboxSidebarConversationsItemOnlineStatus', this.$el).show();
        }

        this.changeSelected();

        return this;
    },

    changeLastMessageTimestamp: function(){
        $('#mailboxSidebarItemListConversations .owm_convers_list_cont').prepend(this.$el);
    },

    changeSelected: function(){
        if (this.model.get('selected')){
            this.$el.addClass('owm_convers_item_active');
        }
        else{
            this.$el.removeClass('owm_convers_item_active');
        }
    },

    openConversation: function(){
        window.location.href = this.model.get('url');
    }

});

MAILBOX_ConversationItemList = Backbone.Collection.extend({
    model: MAILBOX_ConversationItem,

    comparator: function(model){
        return -model.get('lastMessageTimestamp');
    }
});

MAILBOX_Conversations = Backbone.Model.extend({

    defaults: {
        active: false,
        loadMore: true
    },

    loadedConvCount: 0,

    initialize: function(){
        OWM.mailboxSidebarMenu.itemList.on('change:selected', this.changeActive, this);
        this.itemList = new MAILBOX_ConversationItemList();
        this.loadList();

        var selectedItem = OWM.mailboxSidebarMenu.itemList.findWhere({selected: true});

        if (selectedItem && selectedItem.get('mode') == 'conversations'){
            this.set({active: true});
        }
    },

    changeActive: function(model){
        if (model.get('mode') == 'conversations'){
            this.set({active: model.get('selected')});
        }
    },

    loadList: function(){

        var numberOfConvToLoad = 20;
        var n =this.loadedConvCount + numberOfConvToLoad;
        if (n > OWM.Mailbox.convList.length){
            n = OWM.Mailbox.convList.length;
        }

        for (var i=this.loadedConvCount; i < n; i++){
            if (typeof OWM.Mailbox.convList[i] != 'undefined'){
                this.itemList.add(OWM.Mailbox.convList[i]);
            }
        }
        this.loadedConvCount = i;

        if (this.loadedConvCount == OWM.Mailbox.convList.length){
            this.set('loadMore', false);
        }
        else{
            this.set('loadMore', true);
        }
    }

});

MAILBOX_ConversationsView = Backbone.View.extend({
    el: '#mailboxSidebarItemListConversations',

    initialize: function(){
        var that = this;

        this.model.on('change:active', this.changeActive, this);
        this.model.on('change:loadMore', this.changeLoadMore, this);
        this.model.itemList.on('add', this.renderItem, this);

        $('#mailboxConversationsLoadMore').click(function(){
            that.model.loadList();
        });

        this.render();
    },

    render: function(){
        _.each(this.model.itemList.models, function(item){
            this.renderItem(item);
        }, this);

        this.changeActive();
        this.changeLoadMore();
    },

    renderItem: function(item){
        if (item.get('mode') == 'mail'){
            item.set('previewText', item.get('subject'));
        }

        var view = new MAILBOX_ConversationItemView({model: item});

        var itemIndex = this.model.itemList.indexOf(item);

        if (itemIndex == 0){
            $('.owm_convers_list_cont', this.$el).prepend(view.render().$el);
        }
        else{
            $('.owm_convers_list_cont', this.$el).append(view.render().$el);
        }
    },

    hideLoadMoreBtn: function(){
        $('#mailboxConversationsLoadMoreBlock').hide();
    },

    showLoadMoreBtn: function(){
        $('#mailboxConversationsLoadMoreBlock').show();
    },

    changeLoadMore: function(){
        if (this.model.get('loadMore')){
            this.showLoadMoreBtn();
        }
        else{
            this.hideLoadMoreBtn();
        }
    },

    changeActive: function(){
        if (this.model.get('active')){
            this.$el.addClass('active');
        }
        else{
            this.$el.removeClass('active');
        }
    }
});

MAILBOX_UserItem = Backbone.Model.extend({
    selected: false,

    remove: function(){
        this.trigger("remove");
    }
});

MAILBOX_UserItemView = Backbone.View.extend({
    template: function(data){ return _.template($('#mailboxSidebarUserItemPrototype').html(), data); },

    events: {
        'click .owm_user_list_item': 'openConversation'
    },

    initialize: function(){
        this.setElement(this.template(this.model.attributes));
        this.model.on('remove', this.remove, this);
    },

    render: function(){

        if (this.model.get('status') != 'offline'){
            $('#mailboxSidebarConversationsItemOnlineStatus', this.$el).show();
        }

        return this;
    },

    openConversation: function(){
        window.location.href = this.model.get('url');
    },

    remove: function(){
        this.$el.remove();
    }
});

MAILBOX_UserItemList = Backbone.Collection.extend({
    model: MAILBOX_UserItem,

    removeItem: function( cid ){
        var model = this.get(cid);
        model.remove();

        this.remove(cid);
    }
});

MAILBOX_Users = Backbone.Model.extend({

    defaults: {
        active: false,
        loadMore: true
    },

    loadedUserCount: 0,

    initialize: function(){
        OWM.mailboxSidebarMenu.itemList.on('change:selected', this.changeActive, this);
        this.itemList = new MAILBOX_UserItemList();
        this.loadList();
    },

    changeActive: function(model){

        if (model.get('mode') == 'userlist'){
            this.set({active: model.get('selected')});
        }
    },

    loadList: function(){
        var numberOfUsersToLoad = 20;
        var n =this.loadedUserCount + numberOfUsersToLoad;
        if (n > OWM.Mailbox.userList.length){
            n = OWM.Mailbox.userList.length;
        }

        for (var i=this.loadedUserCount; i < n; i++){
            if (typeof OWM.Mailbox.userList[i] != 'undefined'){
                this.itemList.add(OWM.Mailbox.userList[i]);
            }
        }
        this.loadedUserCount = i;

        if (this.loadedUserCount == OWM.Mailbox.userList.length){
            this.set('loadMore', false);
        }
        else{
            this.set('loadMore', true);
        }

    },
});

MAILBOX_UsersView = Backbone.View.extend({
    el: '#mailboxSidebarItemListUserlist',

    initialize: function(){
        var that = this;

        this.model.on('change:active', this.changeActive, this);
        this.model.on('change:loadMore', this.changeLoadMore, this);
        this.model.itemList.on('add', this.renderItem, this);

        $('#mailboxUsersLoadMore').click(function(){
            that.model.loadList();
        });

        this.render();
    },

    render: function(){
        _.each(this.model.itemList.models, function(item){
            this.renderItem(item);
        }, this);

        this.changeLoadMore();
        return this;
    },

    renderItem: function(item){
        var view = new MAILBOX_UserItemView({model: item});
        $('.owm_convers_list_cont', this.$el).append(view.render().$el);
    },

    hideLoadMoreBtn: function(){
        $('#mailboxUsersLoadMoreBlock').hide();
    },

    showLoadMoreBtn: function(){
        $('#mailboxUsersLoadMoreBlock').show();
    },

    changeLoadMore: function(){
        if (this.model.get('loadMore')){
            this.showLoadMoreBtn();
        }
        else{
            this.hideLoadMoreBtn();
        }
    },

    changeActive: function(){
        if (this.model.get('active')){
            this.$el.addClass('active');
        }
        else{
            this.$el.removeClass('active');
        }
    }
});

MAILBOX_Search = Backbone.View.extend({

    initialize: function(){
        this.searchBtn = $('#mailboxSidebarSearchBtn');
        this.closeBtn = $('#mailboxSidebarCloseSearchBtn');

        var self = this;

        var formElement = new OwFormElement('mailboxSidebarSearchTextField', 'mailbox_search_users_btn');

        addInvitationBeh(formElement, OW.getLanguageText('mailbox', 'label_invitation_conversation_search'));

        var parentResetValue = formElement.resetValue;

        formElement.resetValue = function(){
            parentResetValue.call(this);

            self.reset();
        };

        this.items = new MAILBOX_UserItemList();

        this.addItem = function( data ) {

            var item = new MAILBOX_UserItem(data);
            var view = new MAILBOX_UserItemView({model: item});

            $('#mailboxSidebarSearchItemList').append( view.render().$el );

            this.items.add(item);
        };

        this.reset = function(){

            var tmpList = this.items.slice(0);
            for (var i=0; i<tmpList.length; i++){
                var model = this.items.models[0];
                this.items.removeItem(model.cid);
            }

            $('.owm_user_not_found').hide();
        }

        this.updateList = function(name){
            var self = this;

            if (name == '')
            {
                self.reset();
            }
            else
            {
                var expr = new RegExp('(^'+name+'.*)|(\\s'+name+'.*)', 'i');

                for (var i=0; i<this.items.length; i++){

                    var item = this.items.models[i];

                    if (!expr.test(item.get('displayName')))
                    {
                        self.items.removeItem(item.cid);
                    }
                    else
                    {
                        $('#mailboxSidebarItem'+item.get('opponentId')).show();
                    }
                }

                for (var i=0; i<OWM.Mailbox.userList.length; i++){

                    var displayName = OWM.Mailbox.userList[i].displayName;
                    if (expr.test(displayName)){
                        var list = self.items.where({opponentId: OWM.Mailbox.userList[i].opponentId});
                        if (list.length == 0){
                            self.addItem(OWM.Mailbox.userList[i]);
                        }
                    }
                }

                if (self.items.length == 0)
                {
                    $('.owm_user_not_found').show();
                }
                else
                {
                    $('.owm_user_not_found').hide();
                }
            }
        }

        $(formElement.input).keyup(function(ev){

            if (ev.which === 13 && !ev.ctrlKey && !ev.shiftKey) {
                ev.preventDefault();

                return false;
            }

            self.updateList($(this).val());
        });

        this.searchBtn.bind('click', function(){
            $('.owm_mchat_block').addClass('owm_sidebar_search_active');
        });

        this.closeBtn.bind('click', function(){
            $('.owm_mchat_block').removeClass('owm_sidebar_search_active');
            formElement.resetValue();
            $('.owm_user_not_found').hide();
        });
    }
});

MAILBOX_NewMessageForm = Backbone.Model.extend({

    initialize: function(){
        this.uid = OWM.Mailbox.uniqueId('mailbox_conversation_'+this.get('conversationId')+'_'+this.get('opponentId')+'_');
    },

    sendMessage: function(text){

        var that = this;
        var data = {
            'convId': this.get('conversationId'),
            'text': text,
            'uid': this.uid,
            'embedAttachments': ''
        };

        var ajaxData = {};

        ajaxData['actionData'] = {
            'uniqueId': OWM.Mailbox.uniqueId('postMessage'),
            'name': 'postMessage',
            'data': data
        };

        ajaxData['actionCallbacks'] = {
            success: function(data){

                if (data.message){
                    that.trigger('message_sent', data);
                    that.uid = OWM.Mailbox.uniqueId('mailbox_conversation_'+that.get('conversationId')+'_'+that.get('opponentId')+'_');
                }

                if (data.error){
                    OW.error(data.error);
//                    self.showSendMessageFailed(tmpMessageUid);
                }
            },
            error: function(message){
                OWM.error(message);
            },
            complete: function(){
                that.trigger('message_submit', data);
            }
        };

        OWM.Mailbox.sendData(ajaxData);
    }
});

MAILBOX_NewMessageFormView = Backbone.View.extend({
    initialize: function(){
        var that = this;
        var formElement = new OwFormElement('newMessageText', 'newMessageText');
        addInvitationBeh(formElement, OW.getLanguageText('mailbox', 'text_message_invitation'));

        // init pseudo auto click
        var $textA = $(formElement.input), $submitCont = $('#newMessageSubmitForm');

        if( !this.taMessage ){
            this.taMessage = $textA.val();
        }

        $textA.unbind('focus').one('focus', function(){

            if (that.model.get('mode') == 'chat'){
                $('#mailboxConversation').addClass('owm_chat_input_opened');
            }
            else{
                $('#mailboxMailConversation').addClass('owm_mail_input_opened');
            }

            $(this).removeClass('invitation').val('');

            $('html, body').animate({scrollTop:$(document).height()}, 'slow');
        });

        if (this.model.get('mode') == 'chat'){
            $("#newmessage-att-file").change(function() {
                if (!this.files || !this.files[0]) {
                    return
                };

                $('.owm_chat_add_cont').addClass('owm_preloader_circle');

                if ( window.FileReader ) {
                    var reader = new FileReader();

                    reader.onload = function (e) {
                        $('#newMessageAttBtn').click();
                        $('.owm_chat_add_cont').removeClass('owm_preloader_circle');
                    }

                    reader.readAsDataURL(this.files[0]);
                } else {
                    $('#newMessageAttBtn').click();
                }
            });

            $('#newMessageSendBtn').click(function(ev){
                $('#newmessage-mail-send-btn').addClass('owm_preloader_circle');
                var text = $textA.val();
                that.model.sendMessage(text);
            });
        }
        else{
            $("#newmessage-mail-att-file").change(function() {
                var img = $('#newmessage-mail-att-file-prevew img');
                var name = $("#newmessage-mail-att-file-name span");

                img.hide();
                name.text("");

                if (!this.files || !this.files[0]) {
                    return
                };

                if ( window.FileReader ) {
                    var reader = new FileReader();

                    reader.onload = function (e) {
                        img.show().attr('src', e.target.result);
                    }

                    reader.readAsDataURL(this.files[0]);
                } else {
                    name.text(this.files[0].name);
                }
            });
        }

        this.model.on('message_sent', function(data){
            $(formElement.input).val('');
            $('html, body').animate({scrollTop:$(document).height()}, 'slow');
        })

        this.model.on('message_submit', function(data){
            $('#newmessage-mail-send-btn').removeClass('owm_preloader_circle');
        })
    }
});

MAILBOX_ComposeMessageFormView = Backbone.View.extend({
    el: '#composeMessageCmp',

//    events: {
//        'click .owm_mail_back': 'backBtnHandler'
//    },

    initialize: function(params){
        this.params = params;

        $("#newmessage-mail-att-file").change(function() {
            var img = $('#newmessage-mail-att-file-prevew img');
            var name = $("#newmessage-mail-att-file-name span");

            img.hide();
            name.text("");

            if (!this.files || !this.files[0]) {
                return
            };

            if ( window.FileReader ) {
                var reader = new FileReader();

                reader.onload = function (e) {
                    img.show().attr('src', e.target.result);
                }

                reader.readAsDataURL(this.files[0]);
            } else {
                name.text(this.files[0].name);
            }
        });

        var h = $(window).outerHeight() - $('#newMessageForm').outerHeight() - $('#main header').outerHeight() - $('.owm_mail_info_wrap').outerHeight() - 40;
        $('.owm_mail_compose textarea').height( h );

        $('#mailboxBackToConversations').attr('onclick', "location.href='"+this.params.profileUrl+"'");
        OWM.bind("mobile.before_show_sidebar", function( data ){
            data.openDefaultTab = false;
        });
        OWM.bind("mobile.show_sidebar", function( data ){
            if ( data.type == "right" ) {
                OWM.trigger('mobile.open_sidebar_tab', {key: 'convers'});
            }
        });
    },

    backBtnHandler: function(){
        OWM.bind('mailbox.right_sidebar_loaded', function(data){
            OWM.Mailbox.openUsers();
        });
        OWM.trigger('mobile.open_sidebar_tab', {key: 'convers'});
    }
});

MAILBOX_Conversation = Backbone.Model.extend({

    defaults: {
        'historyLoadAllowed': false
    },

    initialize: function() {
        var messageListOptions = this.get('log');
        this.messageList = new MAILBOX_MessageList(messageListOptions);
        this.set('historyLoadAllowed', this.get('logLength') > 16); //TODO this is hard code

        OWM.Mailbox.openedConversationId = this.get('conversationId');
        OWM.Mailbox.openedOpponentId = this.get('opponentId');
    },

    afterAttachment: function(data){
        if (data.message){
            this.messageList.add(data.message);
            $('html, body').animate({scrollTop:$(document).height()}, 'slow');
        }

        if (data.error){
            OWM.error(data.error);
        }

        if (owForms["newMailMessageForm"])
        {
            $("#newmessage-mail-att-file-prevew img").attr("src", "");
            $("#newmessage-mail-send-btn").removeClass("owm_preloader_circle");

            owForms["newMailMessageForm"].elements.newMessageText.setValue("");
            owForms["newMailMessageForm"].elements.uid.setValue(OWM.Mailbox.uniqueId("mailbox_conversation_"+data.message.convId+"_"+data.message.recipientId+"_"));
        }
    },

    loadHistory: function(callback){

        if (!this.get('historyLoadAllowed')) return;

        var self = this;
        this.historyLoadInProgress = true;
        OWM.Mailbox.sendInProcess = true;
        $.ajax({
            url: OWM.Mailbox.get('getHistoryResponderUrl'),
            type: 'POST',
            data: {
                convId: this.get('conversationId'),
                messageId: this.messageList.at(0).get('id'),
            },
            success: function(data){

                if ( typeof data != 'undefined' )
                {
                    if (data.log.length > 0)
                    {
                        $(data.log).each(function(){
                            self.messageList.unshift(this);
                        });

                        if (self.get('logLength') > self.get('log').length)
                        {
                            self.set('historyLoadAllowed', true, {silent: true});
                            //callback.apply();
                        }
                    }
                    else
                    {
                        self.set('historyLoadAllowed', false, {silent: true});
                    }
                }
            },
            error: function(e){
                if (im_debug_mode) console.log(e);
            },
            complete: function(){
                self.historyLoadInProgress = false;
                OWM.Mailbox.sendInProcess = false;
                callback.apply();
            },
            dataType: 'json'
        });
    }
});

MAILBOX_ConversationView = Backbone.View.extend({
    el: $('#mailboxConversation'),

//    events: {
//        '#owm_header_right_btn': 'backBtnHandler'
//    },

    initialize: function() {
        var that = this;

        this.model.messageList.on('add', this.renderMessage, this);
        this.model.on('change:status', this.changeStatus, this);
        this.model.on('change:historyLoadAllowed', function(){
            if (this.model.get('historyLoadAllowed')){
                this.showLoadHistoryBtn();
            }
            else{
                this.hideLoadHistoryBtn();
            }

        }, this);

        this.status = $('#onlineStatusBlock', this.el);
//        this.backBtn = $('#mailboxBackToConversations', this.el);
        this.loadHistoryBtn = $('#mailboxLoadHistoryBtn', this.el);
        this.showDate = false;

        this.model.bind('change', this.render, this);
        this.model.bind('destroy', this.remove, this);

        this.form = new MAILBOX_NewMessageForm({mode: this.model.get('mode'), opponentId: this.model.get('opponentId'), conversationId: this.model.get('conversationId')});

        this.form.on('message_sent', this.messageSent, this);

        var formView = new MAILBOX_NewMessageFormView({model: this.form});

        if (that.model.get('historyLoadAllowed')){
            that.showLoadHistoryBtn();
        }

        this.afterInitialize();

        OWM.bind("mobile.before_show_sidebar", function( data ){
            data.openDefaultTab = false;
        });

        OWM.bind("mobile.show_sidebar", function( data ){
            if ( data.type == "right" ) {
                OWM.trigger('mobile.open_sidebar_tab', {key: 'convers'});
            }
        });

        $(window).load(function(){
            $('html, body').animate({scrollTop:$(document).height()}, 'fast');
        });

        this.loadHistoryBtn.on('click', function(e){
            that.hideLoadHistoryBtn();
            that.model.loadHistory(function(){
                if (that.model.get('historyLoadAllowed')){
                    that.showLoadHistoryBtn();
                }
                else{
                    that.hideLoadHistoryBtn();
                }
            });
        });

        this.render();
    },

    afterInitialize: function(){
        OWM.bind('mailbox.right_sidebar_loaded', function(data){
            OWM.Mailbox.openUsers();
        });

        $(document).ready(function(){
            var h = $(window).outerHeight() - $('#main header').outerHeight() - $('.owm_chat_info').outerHeight()-$('#mailboxConversationFooter').outerHeight() - 150;
            $('#messageList').css( 'min-height', h );

            $('html, body').animate({scrollTop:$(document).height()}, 'fast');
        });
    },

    render: function(){
        $('.owm_avatar img', this.el).attr('src', this.model.get('avatarUrl'));
        $('.owm_avatar img', this.el).attr('title', this.model.get('displayName'));
        $('.owm_avatar img', this.el).attr('alt', this.model.get('displayName'));
        $('.owm_avatar a', this.el).attr('href', this.model.get('profileUrl'));

        $('#mailboxBackToConversations', this.el).attr('onclick', "location.href='"+this.model.get('profileUrl')+"'");

        $('.owm_chat_name a', this.el).attr('href', this.model.get('profileUrl'));
        $('.owm_chat_name a span', this.el).html(this.model.get('displayName'));

        $('#mailboxLoadHistoryPreloader').hide();

        this.changeStatus();

        var that = this;
        _.each(this.model.messageList.models, function (message) {
            that.renderMessage(message);
        }, this);

//        var h = $(window).outerHeight() - ($('#newMessageSubmitForm').outerHeight() + $('#newMessageForm').outerHeight()) - $('.owm_chat_info_wrap').outerHeight() - $('#main header').outerHeight();
//        $('#messageList').height( h );

        return this;
    },

    backBtnHandler: function(){
        OWM.trigger('mobile.open_sidebar_tab', {key: 'convers'});
    },

    changeStatus: function(){
        if (this.model.get('status') == 'offline'){
            this.status.hide();
        }
        else{
            this.status.show();
        }
    },

    messageSent: function(data){
        if (data.message){
            this.model.messageList.add(data.message);
        }
    },

    renderMessage: function(message){
        var itemIndex;
        itemIndex = this.model.messageList.indexOf(message);

        var view = new MAILBOX_MessageView({model: message});

        if (this.lastMessageDate != message.get('date')){
            this.lastMessageDate = message.get('date');
            this.showDate = true;
        }
        else{
            this.showDate = false;
        }

        if ( message.get('timeLabel') != this.lastMessageTimeLabel || this.showDate ){
            this.lastMessageTimeLabel = message.get('timeLabel');
            var timeBlock = this.showTimeBlock(message);
        }

        if (itemIndex == 0){
            this.$('#messageList').prepend(view.render().$el);
            if (timeBlock){
                this.$('#messageList').prepend(timeBlock);
            }
        }
        else{
            if (timeBlock){
                this.$('#messageList').append(timeBlock);
            }
            this.$('#messageList').append(view.render().$el);
        }
    },

    showTimeBlock: function(message){

        var timeBlock = $('#dialogTimeBlockPrototypeBlock').clone();
        timeBlock.attr('id', 'timeBlock'+message.get('timeStamp'));

        if (this.showDate){
            timeBlock.html(message.get('dateLabel') + '. ' + message.get('timeLabel'));
        }
        else{
            timeBlock.html(message.get('timeLabel'));
        }

        return timeBlock;
    },

    showLoadHistoryBtn: function(){
        this.loadHistoryBtn.show();
        $('#mailboxLoadHistoryPreloader').hide();
    },

    hideLoadHistoryBtn: function(){

        this.loadHistoryBtn.hide();
        if (this.model.get('historyLoadAllowed')){
            $('#mailboxLoadHistoryPreloader').show();
        }
        else{
            $('#mailboxLoadHistoryPreloader').hide();
        }
    }

});

MAILBOX_MailConversationView = MAILBOX_ConversationView.extend({
    el: $('#main section'),


//    events: {
//        'click .owm_mail_back': 'backBtnHandler'
//    },

    afterInitialize: function(){
        OWM.bind('mailbox.right_sidebar_loaded', function(data){
            OWM.Mailbox.openConversations();
        });

        $(document).ready(function(){
            var h = $(window).outerHeight() - $('#main header').outerHeight() - $('.owm_mail_info').outerHeight() + 10 - 150;
            $('#messageList').css( 'min-height', h );

            $('html, body').animate({scrollTop:$(document).height()}, 'fast');
        });
    },

    render: function(){
        $('.owm_avatar img', this.el).attr('src', this.model.get('avatarUrl'));
        $('.owm_avatar img', this.el).attr('title', this.model.get('displayName'));
        $('.owm_avatar img', this.el).attr('alt', this.model.get('displayName'));
        $('.owm_avatar a', this.el).attr('href', this.model.get('profileUrl'));

        $('#mailboxBackToConversations').attr('onclick', "location.href='"+this.model.get('profileUrl')+"'");

        $('.owm_mail_name a', this.el).attr('href', this.model.get('profileUrl'));
        $('.owm_mail_name a span', this.el).html(this.model.get('displayName'));
        $('#mailboxPreviewText', this.el).html(this.model.get('subject'));

        $('#mailboxLoadHistoryPreloader').hide();

        this.changeStatus();

        var that = this;
        _.each(this.model.messageList.models, function (message) {
            that.renderMessage(message);
        }, this);

        return this;
    },

    backBtnHandler: function(){
        OWM.trigger('mobile.open_sidebar_tab', {key: 'convers'});

        OWM.bind('mobile.console_page_loaded', function(data){
            if (data.key == 'convers'){
                OWM.Mailbox.openConversations();
            }
        });
    },

    renderMessage: function(message){
        var itemIndex;
        itemIndex = this.model.messageList.indexOf(message);

        var view = new MAILBOX_MailMessageView({model: message});

        if (this.lastMessageDate != message.get('date')){
            this.lastMessageDate = message.get('date');
            this.showDate = true;
        }
        else{
            this.showDate = false;
        }

        if ( message.get('timeLabel') != this.lastMessageTimeLabel || this.showDate ){
            this.lastMessageTimeLabel = message.get('timeLabel');
            var timeBlock = this.showTimeBlock(message);
        }

        if (itemIndex == 0){
            this.$('#messageList').prepend(view.render().$el);
            if (timeBlock){
                this.$('#messageList').prepend(timeBlock);
            }
        }
        else{
            if (timeBlock){
                this.$('#messageList').append(timeBlock);
            }
            this.$('#messageList').append(view.render().$el);
        }
    }

});

MAILBOX_Mobile = Backbone.Model.extend({

    initialize: function(params){
        var self = this;

        self.convList = [];
        self.userList = [];
        self.pingInterval = params.pingInterval;
        self.lastMessageTimestamp = params.lastMessageTimestamp || 0;
        self.userOnlineCount = 0;
        self.defaultMode = 'conversations';
        self.readMessageList = new MAILBOX_MessageList;
        self.unreadMessageList = new MAILBOX_UnreadMessageList;
        self.conversationsCount = 0;

        var readyStatus = 0;

        self.userListUrl = params.userListUrl;

        $.ajax({
            'url': self.userListUrl,
            'dataType': 'text',
            'success': function(data){
                self.userList = JSON.parse(atob(data));
                self.userListCollection = new Backbone.Collection(self.userList);
                readyStatus++;
                OW.trigger('mailbox.ready', readyStatus);
            }
        });

        self.convListUrl = params.convListUrl;

        $.ajax({
            'url': self.convListUrl,
            'dataType': 'text',
            'success': function(data){
                self.convList = JSON.parse(atob(data));
                self.convListCollection = new Backbone.Collection(self.convList);
                readyStatus++;
                OW.trigger('mailbox.ready', readyStatus);
            }
        });

        self.getParams = function(){
            var params = {};

            var date = new Date();
            var time = parseInt(date.getTime() / 1000);

            params.lastRequestTimestamp = time;
            params.lastMessageTimestamp = self.lastMessageTimestamp;
            params.readMessageList = self.readMessageList.pluck('id');
            params.unreadMessageList = self.unreadMessageList.pluck('id');
            params.userOnlineCount = self.userOnlineCount;
            params.convListLength = self.convList.length;
            params.ajaxActionData = self.ajaxActionData;
            params.conversationsCount = self.ajaxActionData;
            self.ajaxActionData = [];

            if (params.readMessageList.length != 0)
            {
                self.clearReadMessageList();
            }

            return params;
        }

        self.beforePingStatus = true;
        self.ajaxActionData = [];
        self.ajaxActionCallbacks = {};

        self.addAjaxData = function(ajaxData){
            self.ajaxActionData.push(ajaxData['actionData']);
            self.ajaxActionCallbacks[ajaxData['actionData']['uniqueId']] = ajaxData['actionCallbacks'];
        }

        self.sendData = function(ajaxData){
            if (typeof ajaxData != 'undefined')
            {
                self.addAjaxData(ajaxData);
            }

            var requestData = JSON.stringify(self.getParams());

            self.beforePingStatus = false;
            $.ajax({
                url: OWM.Mailbox.get('pingResponderUrl'),
                type: 'POST',
                data: {'request': requestData},
                success: function(data){
                    self.setData(data);
                },
                complete: function(){
                    self.beforePingStatus = true;
                },
                dataType: 'json'
            });
        }

        self.setData = function(data){

            if (typeof data.userOnlineCount != 'undefined'){
                if (typeof data.userList != 'undefined')
                {
                    self.userList = self.sortUserList(data.userList);
                }

                OW.trigger('mailbox.user_online_count_update', {userOnlineCount: data.userOnlineCount});
            }

            if (typeof data.convList != 'undefined'){
                self.conversationsCount = data.conversationsCount;

                if (self.convList > data.convList)
                {
                    $(self.convList).each(function(){

                    });
                }
                else if (data.convList > self.convList)
                {
                    $(data.convList).each(function(){

                        var conv = this;
                        var exists = false;

                        $(self.convList).each(function(){
                            if (this.conversationId == conv.conversationId)
                            {
                                exists = true;
                            }
                        });

                        if (!exists)
                        {
                            OW.trigger('mailbox.new_conversation_created', conv);
                        }
                    });
                }

                self.convList = data.convList;
            }

            if (typeof data.messageList != 'undefined')
            {
                self.newMessageList = data.messageList;
                $.each(data.messageList, function(){
                    if (this.timeStamp != self.lastMessageTimestamp)
                    {
                        if (typeof OWM.mailboxConversations != "undefined"){
                            OWM.mailboxConversations.itemList.findWhere({conversationId: this.convId}).set('lastMessageTimestamp', this.timeStamp);
                        }

                        OW.trigger('mailbox.message', this);
                    }
                } );
                self.newMessageList = [];
            }

            //TODO self.ajaxActionCallbacks.error()
            if (typeof data.ajaxActionResponse != 'undefined'){

                var callbacksToDelete = [];
                $.each(data.ajaxActionResponse, function(uniqueId, item){
                    self.ajaxActionCallbacks[uniqueId].success(item);
                    self.ajaxActionCallbacks[uniqueId].complete();
                    callbacksToDelete.push(uniqueId);
                });

                for (var i=0; i<callbacksToDelete.length; i++){
                    delete self.ajaxActionCallbacks[callbacksToDelete[i]];
                }
            }
        }

        OW.bind('mailbox.ready', function(readyStatus){
            if (readyStatus == 2){

                OW.bind('mailbox.message', function(message) {

                    if (typeof OWM.conversation != 'undefined' && OWM.conversation.get('conversationId') == message.convId){
                        OWM.conversation.messageList.add(message);
                        self.readMessageList.add(message);
                        self.lastMessageTimestamp = message.timeStamp;
                    }
                    else{
                        if (!message.isAuthor){
                            self.unreadMessageList.add(message);
                            OW.trigger('mailbox.new_message_notification', {message: message, unreadMessageList: self.unreadMessageList});
                        }
                    }
                });

                this.updateCounter = function(data){
                    var $tabCounter = $(".owm_sidebar_count_txt", "#console-tab-convers");

                    var counter = data.unreadMessageList.length;

                    OWM.trigger('mobile.console_show_counter', {counter: counter, tab:false, options: {tab: 'convers'}});

                    var $tab = $("#console-tab-convers");

                    if ( $(".owm_sidebar_count", $tab).is(":visible") ) {
                        $tabCounter.html(counter);
                    }
                    else {
                        $tabCounter.html(counter);
                        $(".owm_sidebar_count", $tab).fadeIn();
                    }
                }

                OW.bind('mailbox.new_message_notification', this.updateCounter);

                OW.bind('mailbox.message_was_read', this.updateCounter);

                OW.getPing().addCommand('mailbox_ping', {
                    params: {},
                    before: function()
                    {
                        if (!self.beforePingStatus)
                        {
                            return false;
                        }

                        if (self.sendInProcess)
                        {
                            return false;
                        }

                        this.params = self.getParams();
                    },
                    after: function( data )
                    {
                        self.setData(data);
                    }
                }).start(self.pingInterval);
            }
        });
    },

    clearReadMessageList: function(){
        this.readMessageList = new MAILBOX_MessageList;
    },

    formatAMPM: function(date) {
        var hours = date.getHours();
        var minutes = date.getMinutes();
        var strTime = '00:00';

        if (OWMailbox.useMilitaryTime)
        {
            minutes = minutes < 10 ? '0'+minutes : minutes;
            hours = hours < 10 ? '0'+hours : hours;
            strTime = hours + ':' + minutes;
        }
        else
        {
            var ampm = hours >= 12 ? 'PM' : 'AM';
            hours = hours % 12;
            hours = hours ? hours : 12;
            minutes = minutes < 10 ? '0'+minutes : minutes;
            hours = hours < 10 ? '0'+hours : hours;
            strTime = hours + ':' + minutes + ampm;
        }

        return strTime;
    },

    formatAttachmentFileName: function(fileName){
        var str = fileName;

        if (fileName.length > 20){
            str = fileName.substring(0, 10) + '...' + fileName.substring(fileName.length-10);
        }

        return str;
    },

    formatAttachmentFileSize: function(size){

        if (size >= 1024)
        {
            size = size / 1024;
            return '(' + size + 'MB)';
        }
        return '(' + size + 'KB)';
    },

    openConversations: function(){
        if (OWM.mailboxSidebarMenu){
            OWM.mailboxSidebarMenu.itemList.findWhere({mode: 'conversations'}).set({selected: true});
        }
    },

    openUsers: function(){
        if (OWM.mailboxSidebarMenu){
            OWM.mailboxSidebarMenu.itemList.findWhere({mode: 'userlist'}).set({selected: true});
        }
    },

    sortUserList: function(list){

        var sortedUserList = [];
        var usersWithCorrespondence = [];
        var usersFriendsOnline = [];
        var usersFriendsOffline = [];
        var usersMembersOnline = [];
        var usersMembersOffline = [];

        for (i in list)
        {
            var user = list[i];

            if (user.lastMessageTimestamp > 0)
            {
                usersWithCorrespondence.push(user);
            }
            else
            {
                if (user.isFriend)
                {
                    if (user.status != 'offline')
                    {
                        usersFriendsOnline.push(user);
                    }
                    else
                    {
                        usersFriendsOffline.push(user);
                    }
                }
                else
                {
                    if (user.status != 'offline')
                    {
                        usersMembersOnline.push(user);
                    }
                    else
                    {
                        usersMembersOffline.push(user);
                    }
                }
            }
        }

        usersWithCorrespondence.sort(function(user1,user2){
            return user2.lastMessageTimestamp - user1.lastMessageTimestamp;
        });

        for (i in usersWithCorrespondence)
        {
            sortedUserList.push(usersWithCorrespondence[i]);
        }

        usersFriendsOnline.sort(function(user1,user2){
            return user1.displayName.toLowerCase().localeCompare( user2.displayName.toLowerCase() );
        });

        for (i in usersFriendsOnline)
        {
            sortedUserList.push(usersFriendsOnline[i]);
        }

        usersFriendsOffline.sort(function(user1,user2){
            return user1.displayName.toLowerCase().localeCompare( user2.displayName.toLowerCase() );
        });

        for (i in usersFriendsOffline)
        {
            sortedUserList.push(usersFriendsOffline[i]);
        }

        usersMembersOnline.sort(function(user1,user2){
            return user1.displayName.toLowerCase().localeCompare( user2.displayName.toLowerCase() );
        });

        for (i in usersMembersOnline)
        {
            sortedUserList.push(usersMembersOnline[i]);
        }

        usersMembersOffline.sort(function(user1,user2){
            return user1.displayName.toLowerCase().localeCompare( user2.displayName.toLowerCase() );
        });

        for (i in usersMembersOffline)
        {
            sortedUserList.push(usersMembersOffline[i]);
        }

        return sortedUserList;
    },

    uniqueId: function(prefix){
        prefix = prefix || '';
        return prefix + Math.random().toString(36).substr(2, 9);
    }

});

$(function(){

    $.fn.extend({
        autolink: function(options){
            var exp =  new RegExp("(\\b(https?|ftp|file)://[-A-Z0-9+&amp;@#\\/%?=~_|!:,.;]*[-A-Z0-9+&amp;@#\\/%=~_|])", "ig");
            /* Credit for the regex above goes to @elijahmanor on Twitter, so follow that awesome guy! */

            this.each( function(id, item){

                if ($(item).html() == ""){
                    return 1;
                }
                var text = $(item).html().replace(exp,"<a href='$1' target='_blank'>$1</a>");
                $(item).html( text );

            });

            return this;
        }
    });

    im_debug_mode = false;
});