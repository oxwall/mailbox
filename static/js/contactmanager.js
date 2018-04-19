/*                          Contact                                     */
MAILBOX_Contact = Backbone.Model.extend({
    defaults: {
        convId: null,
        opponentId: null,
        status: '',
        lastMessageTimestamp: 0,
        displayName: '',
        profileUrl: '',
        avatarUrl: '',
        wasCorrespondence: false,
        isFriend: false,
        unreadMessagesCount: 0,
        show: false,
        wasCreatedByScroll: false
    },

    show: function(){
        this.set('show', true);
    },

    hide: function(){
        this.set('show', false);
    }
});

MAILBOX_ContactView = Backbone.View.extend({

    template: function(data){
        var itemControl = $('#ow_chat_list_proto ul li').clone();
        itemControl.attr('id', 'contactItem'+this.model.get('opponentId'));
        itemControl.data( this.model );

        return itemControl;
    },

    initialize: function(){
        var self = this;

        this.setElement(this.template());

        this.itemLink = $('a.ow_chat_item', this.$el);
        this.unreadMessagesCountWrapper = $('.ow_count_wrap', this.$el);
        this.unreadMessagesCountControl = $('.ow_count', this.$el);
        this.displayNameControl = $( '#contactItemDisplayName', this.$el );

        this.avatarUrlControl = $( '#contactItemAvatarUrl', this.$el );

        this.statusControl = $('#contactProfileStatus', this.$el);

        this.model.on('remove', function(){
            this.$el.remove();
        }, this);

        this.model.on('change:status', function(){
            this.statusControl.removeClass();
            this.statusControl.addClass('ow_chat_status');

            if (this.model.get('status') != 'offline'){
                this.statusControl.addClass(this.model.get('status'));
            }

            this.$el.removeClass();
            this.$el.addClass(this.model.get('status'));
        }, this);

        this.model.on('change:unreadMessagesCount', function(){

            this.unreadMessagesCountControl.html(this.model.get('unreadMessagesCount'));

            if (this.model.get('unreadMessagesCount') > 0){
                this.unreadMessagesCountWrapper.show();
                this.statusControl.hide();
                this.itemLink.addClass('ow_active');
            }
            else{
                this.unreadMessagesCountWrapper.hide();
                this.statusControl.show();
                this.itemLink.removeClass('ow_active');
            }
        }, this);

        this.model.on('change:show', function(){
            if (this.model.get('show')){
                this.show();
            }
            else{
                this.hide();
            }
        }, this);

        this.model.on('change:wasCreatedByScroll', function(){
            if (this.model.get('wasCreatedByScroll')){
                this.model.set('show', true);
            }
            else{
                this.model.set('show', false);
            }
        }, this);

        this.$el.click(function (){
            if (typeof OW.Mailbox.newMessageFormController != "undefined") {
                if (OW.Mailbox.newMessageFormController.closeNewMessageWindowWithConfirmation($('.mailboxDialogBlock.ow_open').length)) {
                    OW.trigger('mailbox.open_dialog', {convId: self.model.get('convId'), opponentId: self.model.get('opponentId'), mode: 'chat', isSelected: true, isActive: true});
                }  
            }
            else {
                OW.trigger('mailbox.open_dialog', {convId: self.model.get('convId'), opponentId: self.model.get('opponentId'), mode: 'chat', isSelected: true, isActive: true});
            }
        });

        OW.bind('mailbox.presence', function(presence){
            if (presence.opponentId != self.model.get('opponentId')){
                return;
            }

            self.model.set('status', presence.status);
        });

        OW.bind('mailbox.dialog_opened', function(data){
            if (data.convId != self.model.get('convId')){
                return;
            }

//            self.model.set('unreadMessagesCount', 0);
            self.updateUnreadMessagesCount({message: {convId: data.convId}, unreadMessageList: OW.Mailbox.contactManager.unreadMessageList});
        });

        OW.bind('mailbox.dialog_selected', function(data){
            if (data.convId != self.model.get('convId')){
                return;
            }

//            self.model.set('unreadMessagesCount', 0);
            self.updateUnreadMessagesCount({message: {convId: data.convId}, unreadMessageList: OW.Mailbox.contactManager.unreadMessageList});
        });

        this.updateUnreadMessagesCount = function(data){
            if (data.message.convId != self.model.get('convId')){
                return;
            }

            var unreadMessagesCount = 0;
            for(var i=0; i<data.unreadMessageList.length; i++){
                if (self.model.get('convId') == data.unreadMessageList.models[i].get('convId')){
                    unreadMessagesCount++;
                }
            }

            self.model.set('unreadMessagesCount', unreadMessagesCount);
        }

        OW.bind('mailbox.message_was_read', this.updateUnreadMessagesCount);

        OW.bind('mailbox.data_received_for_'+self.model.get('opponentId'), function(data){
            if (self.model.get('convId') == 0){
                self.model.set('convId', data.conversationId);
            }
        });
    },

    render: function(){

        this.displayNameControl.html(this.model.get('displayName'));
        this.avatarUrlControl.attr('src', this.model.get('avatarUrl'));
        this.avatarUrlControl.attr('alt', this.model.get('displayName'));
        this.avatarUrlControl.attr('title', this.model.get('shortUserData'));
        this.$el.addClass(this.model.get('status'));

        this.hide();

        this.statusControl.addClass('ow_chat_status');
        if (this.model.get('status') != 'offline'){
            this.statusControl.addClass(this.model.get('status'));
            this.show();
        }

        OW.bindTips(this.$el);

        return this;
    },

    hide: function(){
        this.$el.addClass('ow_hidden');
    },

    show: function(){
        this.$el.removeClass('ow_hidden');
    }
});

MAILBOX_ContactsCollection = Backbone.Collection.extend({
    model: MAILBOX_Contact,
    comparator: function(model){
        if (model.get('lastMessageTimestamp') > 0){
            return -model.get('lastMessageTimestamp');
        }
        else if (model.get('isFriend')){
            return model.get('status') != 'offline';
        }
        else{
            return model.get('status') == 'offline';
        }
    },

    initialize: function(){
        OW.Mailbox.usersCollection.on('add', this.addUser, this);
        OW.Mailbox.usersCollection.on('change', this.changeUser, this);
    },

    addUser: function(user){
        if ( user.get('canInvite') && ( OWMailbox.showAllMembersMode || user.get('lastMessageTimestamp') > 0 || user.get('isFriend') ) ){
            this.add(new MAILBOX_Contact(user.attributes));
        }
    },

    changeUser: function(user){
        var contact = this.findWhere({opponentId: user.get('opponentId')});

        if (contact){
            contact.set(user.attributes);
        }

        //TODO remove user from contacts if it became not friend or conversations were deleted and showAllMembersMode is off
    }
});
/*                          End Contact                                 */
/*                          Contact Manager                              */
MAILBOX_ContactManager = Backbone.Model.extend({

    defaults: {
        userOnlineCount: 0,
        userOnlineStatusCount: 0,
        totalUnreadMessages: 0,
        chatSelectorUnreadMessages: 0,
        soundEnabled: true,
        showOnlineOnly: true,
        chatBlockActive: "0",
    },

    initialize: function(){
        this.contacts = new MAILBOX_ContactsCollection;
        this.readMessageList = new Backbone.Collection;
        this.unreadMessageList = new Backbone.Collection;
        this.viewedConversationList = [];

        this.loadedContactCount = 0;
        this.set('soundEnabled', (im_readCookie('im_soundEnabled') !== null) ? parseInt(im_readCookie('im_soundEnabled')) : OWMailbox.soundEnabled);
        this.set('showOnlineOnly', (im_readCookie('im_showOnlineOnly') !== null) ? parseInt(im_readCookie('im_showOnlineOnly')) : OWMailbox.showOnlineOnly);
    },

    addUnreadMessageToList: function(message){
        this.unreadMessageList.add(message);
    },

    addViewedConversation: function(conversationId){
        this.viewedConversationList.push(conversationId);
        var conversation = OW.Mailbox.conversationsCollection.findWhere({conversationId: conversationId});
        if (conversation){
            conversation.set('conversationViewed', true);
        }
    },

    clearReadMessageList: function(){
        this.readMessageList = new Backbone.Collection;
    },

    clearViewedConversationList: function(){
        this.viewedConversationList = [];
    },

    loadList: function(numberToLoad){

        var toLoad = numberToLoad || 10; //TODO this is hardcode
        var n =this.loadedContactCount + toLoad;
        if (n > this.contacts.length){
            n = this.contacts.length;
        }

        for (var i=this.loadedContactCount; i<n; i++){
            var contact = this.contacts.models[i];

            contact.set('wasCreatedByScroll', true);
        }
        this.loadedContactCount = i;
    },

    getReadMessageList: function(){
        return this.readMessageList.pluck('id');
    },

    getUnreadMessageList: function(){
        return this.unreadMessageList.pluck('id');
    },

    getViewedConversationList: function(){
        return this.viewedConversationList;
    }

});

MAILBOX_ContactManagerView = Backbone.View.extend({
    initialize: function(){

        var self = this;

        this.dialogs = {};
        this.newMessageTimeout = 0;
        this.statusUpdatedTimeout = {};

        this.construct();

        this.model.unreadMessageList.on('add', this.addUnreadMessage, this);
        this.model.unreadMessageList.on('remove', this.removeUnreadMessage, this);

        OW.bind('mailbox.draggable.drag', function(){
            self.fitWindow();
        });

        OW.bind('mailbox.message', function(message){

            var messagesOpened = false;
            if (typeof OW.Mailbox.conversationController != 'undefined'){
                messagesOpened = true;
            }

            var conversationOpened = false;
            if (messagesOpened && OW.Mailbox.conversationController.model.convId == message.convId){
                conversationOpened = true;
            }

            if (OWMailbox.chatModeEnabled && OWMailbox.useChat == 'available' && message.mode == 'chat'){
                var dialog;
                // Message from my other resources
                if( message.senderId == OWMailbox.userDetails.userId ){

                    dialog = self.getDialog(message.convId, message.recipientId);
                    dialog.model.setMode(message.mode);

                    if ( !conversationOpened ){
                        if (!dialog.model.isOpened){
                            dialog.showTab();

                            if (!dialog.model.isActive){
                                dialog.open();
                            }
                        }
                        else{
                            dialog.write(message);
                        }
                    }
                    else if (dialog.model.isOpened){
                        dialog.write(message);
                    }

                    OW.Mailbox.lastMessageTimestamp = message.timeStamp;
                }
                else{
                    // Message to other contact
                    dialog = self.getDialog(message.convId, message.senderId);

                    if (dialog){
                        if (!message.conversationViewed && !conversationOpened && message.mode == 'chat' && dialog.model.status != 'offline' && !dialog.model.isOpened && !dialog.model.isActive){
                            dialog.showTab().open();
                            dialog.model.setIsSelected(false);

                            // silent mode
                            if (!self.model.get('soundEnabled')) {
                                dialog.model.setIsActive(false);
                            }
                        }

                        if ( dialog.model.isLoaded ){
                            dialog.write(message);
                        }

                        //Dialog is not selected it can be hidden in chat selector or opened and collapsed or opened and expanded, but we need to inform user anyway by notifications
//                    if ( !dialog.model.isSelected && !conversationOpened && !message.conversationViewed ){
                        if ( !dialog.model.isSelected && !conversationOpened ){
                            if ( message.mode == 'chat' && self.newMessageTimeout === 0 ){
                                var new_message_label = OW.getLanguageText('mailbox', 'new_message');

                                self.newMessageTimeout = setInterval(function() {
                                    document.title = document.title == new_message_label ? OWMailbox.documentTitle : new_message_label;
                                }, 3000);
                            }

                            self.model.addUnreadMessageToList(message);
                            OW.trigger('mailbox.new_message_notification', {message: message, unreadMessageList: self.model.unreadMessageList});
                        }
                    }
                }
            }
            else{
                if (!conversationOpened && message.recipientId == OWMailbox.userDetails.userId){
                    self.model.addUnreadMessageToList(message);
                    OW.trigger('mailbox.new_message_notification', {message: message, unreadMessageList: self.model.unreadMessageList});
                    return;
                }
                //else
                //{
                //    console.log(message);
                //}
            }
        });

        OW.bind('mailbox.mark_message_read', function(data){

            OW.trigger('mailbox.clear_new_message_notification', {convId: data.message.convId});

            if (!data.message.readMessageAuthorized){
                OW.trigger('mailbox.clear_new_message_blinking', {message: data.message, unreadMessageList: self.model.unreadMessageList});
                return;
            }

            if (data.message.readMessageAuthorized){
                self.model.readMessageList.add(data.message);
            }
            self.model.unreadMessageList.remove(data.message);

            for (var i=0; i < OW.Mailbox.markedUnreadConversationList.length; i++){
                var convId = OW.Mailbox.markedUnreadConversationList;
                if (data.message.convId == convId){
                    OW.Mailbox.markedUnreadConversationList.splice(i, 1);
                }
            }

            OW.trigger('mailbox.message_was_read', {message: data.message, unreadMessageList: self.model.unreadMessageList});

            self.updateChatSelectorUnreadMessagesCount();
        });

        ////////////////////////////////////// Chat Enabled //////////////////////////////////////
        if (OWMailbox.chatModeEnabled && OWMailbox.useChat == 'available'){
            this.model.contacts.on('add', this.addContact, this);
            this.model.contacts.on('change', this.updateContact, this);
            this.model.on('change:chatSelectorUnreadMessages', function(){
                self.chatSelectorUnreadMessagesCounter.html(self.model.get('chatSelectorUnreadMessages'));
                if (self.model.get('chatSelectorUnreadMessages') > 0 && !self.chatSelectorButton.hasClass('ow_active'))
                {
                    self.chatSelectorTotalUnreadMessagesCountWrapper.show();
                }
                else
                {
                    self.chatSelectorTotalUnreadMessagesCountWrapper.hide();
                }
            }, this);

            this.model.on('change:soundEnabled', function(){

                this.setSoundEnabled();

                $.ajax({
                    url: OWMailbox.settingsResponderUrl,
                    type: 'POST',
                    data: {'soundEnabled': self.model.get('soundEnabled')},
                    dataType: 'json'
                });
                im_createCookie('im_soundEnabled', (self.model.get('soundEnabled'))?1:0, 1);
            }, this);

            this.model.on('change:showOnlineOnly', function(){

                this.setShowOnlineOnly();

                $.ajax({
                    url: OWMailbox.settingsResponderUrl,
                    type: 'POST',
                    data: {'showOnlineOnly': self.model.get('showOnlineOnly')},
                    dataType: 'json'
                });
                im_createCookie('im_showOnlineOnly', (self.model.get('showOnlineOnly'))?1:0, 1);
                OWMailbox.showOnlineOnly = this.model.get('showOnlineOnly');
            }, this);



            this.model.on('change:chatBlockActive', function(){

                if (this.model.get('chatBlockActive') == "1"){
                    this.maximize();
                }
                else{
                    this.minimize();
                }

                OW.updateScroll( this.contactListWrapper );
                OWMailbox.getStorage().setItem('chatBlockActive', this.model.get('chatBlockActive'));
            }, this);
            this.model.on('change:userOnlineCount', this.onMainCounterUpdate, this);
            this.model.on('change:userOnlineStatusCount', this.onOnlineCounterUpdate, this);

            OW.bind('mailbox.application_started', function(){
                self.model.loadList();
                OW.updateScroll(self.contactListWrapper);

                var openedDialogs = OWMailbox.getOpenedDialogsCookie();

                $.each(openedDialogs, function(convId, presence){

                    self.moveToActiveMode();

                    var dialog = self.getDialog(parseInt(convId), presence.opponentId);
                    if (dialog == null){
                        var oDialogs = OWMailbox.getOpenedDialogsCookie();

                        if (typeof oDialogs[convId] != 'undefined'){
                            delete oDialogs[convId];
                        }

                        OWMailbox.setOpenedDialogsCookie(oDialogs);
                    }
                    else
                    {
                        OW.trigger('mailbox.open_dialog', {convId: convId, opponentId: presence.opponentId, mode: presence.mode, isActive: presence.isActive, isSelected: presence.isSelected});
                    }
                });

                self.fitWindow();
            });
            OW.bind('mailbox.clear_new_message_notification', function(data){
                if ( self.newMessageTimeout !== 0 ){
                    clearInterval( self.newMessageTimeout );

                    document.title = OWMailbox.documentTitle;

                    self.newMessageTimeout = 0;
                }

                self.model.addViewedConversation(data.convId);
            });
            OW.bind('mailbox.dialog_moved_to_chat_selector', function(data){
                self.updateChatSelector();
            });
            OW.bind('mailbox.dialog_removed_from_chat_selector', function(data){
                self.updateChatSelector();
            });
            OW.bind('mailbox.open_dialog', function(data){

                var dialog = self.getDialog(data.convId, data.opponentId);

                if (data.hasOwnProperty('mode')){
                    dialog.model.setMode(data.mode);
                }
                dialog.showTab().open();

                if (data.hasOwnProperty('isActive')){
                    dialog.model.setIsActive(data.isActive);
                }

                if (dialog.model.isOpened){
                    if (dialog.model.isHidden){
                        var dialogs = $('.mailboxDialogBlock.ow_open');
                        var firstDialogControl = dialogs.first();
                        var firstConvId = $(firstDialogControl).data('convId');
                        OW.trigger('mailbox.move_dialog_to_chat_selector', {convId: firstConvId});

                        OW.trigger('mailbox.remove_dialog_from_chat_selector', {convId: dialog.model.convId});
                    }
                }

                if (data.hasOwnProperty('isSelected')){
                    dialog.model.setIsSelected(data.isSelected);
                }

                self.fitWindow();
            });
            OW.bind('mailbox.close_dialog', function(data){
                if (typeof self.dialogs[data.convId] != 'undefined'){
                    var dialog = self.getDialog(data.convId, data.opponentId);
                    if (dialog.model.isOpened){
                        dialog.hideTab();
                    }
                }
            });
            OW.bind('mailbox.conversation_deleted', function(data){
                OW.trigger('mailbox.close_dialog', data);
            });
            OW.bind('mailbox.dialog_tab_shown', function(params){
                if (OWMailbox.chatModeEnabled){
                    self.moveToActiveMode();
                }

                self.fitWindow();
            });
            OW.bind('mailbox.dialog_hidden', function(params){

            });
            OW.bind('mailbox.dialog_selected', function(params){

                $.each(self.dialogs, function(id, dialog){

                    if (dialog.model.isSelected && dialog.model.convId != params.convId){
                        dialog.model.setIsSelected(false);
                    }
                });

            });
            OW.bind('mailbox.dialog_closed', function(params){
                self.removeDialog(params.convId);
                self.fitWindow();
            });
            OW.bind('mailbox.new_message_form_opened', function(data){
                self.fitWindow();
            });
            OW.bind('mailbox.new_message_form_closed', function(data){
                self.fitWindow();
            });
            OW.bind('mailbox.new_message_form_minimized', function(data){
                self.fitWindow();
            });

            $(window).resize(function(){
                self.fitWindow();
            });

            $(document).click(function( e ){
                if ( !$(e.target).is(':visible') ){
                    return;
                }

                var countOfDialogsInChatSelector = self.countOfDialogsInChatSelector();

                //Show or hide chat dialog selector
                var isContent = self.chatSelectorList.find(e.target).length;
                var isTarget = self.chatSelectorButton.is(e.target) || self.chatSelectorButton.find(e.target).length;
                if ( isTarget && !isContent ){
                    if (self.chatSelectorList.hasClass('ow_hidden')){
                        if (countOfDialogsInChatSelector > 0){
                            self.chatSelectorButton.addClass('ow_active');
                            self.chatSelectorList.removeClass('ow_hidden');

                            //Hide if there are no hidden unread dialogs
                            self.chatSelectorTotalUnreadMessagesCountWrapper.hide();
                        }
                    }
                    else{
                        self.chatSelectorButton.removeClass('ow_active');
                        self.chatSelectorList.addClass('ow_hidden');

                        //Show if there are hidden unread dialogs
                        if ( self.model.get('chatSelectorUnreadMessages') > 0 ){
                            self.chatSelectorTotalUnreadMessagesCountWrapper.show();
                        }
                    }

                    self.showChatSelector();
                }
                else if ( !isContent ){
                    self.chatSelectorButton.removeClass('ow_active');
                    self.chatSelectorList.addClass('ow_hidden');

                    if (countOfDialogsInChatSelector == 0)
                    {
                        self.hideChatSelector();
                    }
                    else
                    {
                        //Show if there are hidden unread dialogs
                        if ( self.model.get('chatSelectorUnreadMessages') > 0 )
                        {
                            self.chatSelectorTotalUnreadMessagesCountWrapper.show();
                        }
                    }
                }

                var isTarget = $('.ow_chat_in_dialog_wrap').is(e.target) || $('.ow_chat_in_dialog_wrap').find(e.target).length || $('.ow_chat_message_block').is(e.target) || $('.ow_chat_message_block').find(e.target).length;
                var isSwitchToChatBtn = $('#conversationSwitchToChatBtn').is(e.target);
                var isContactArea = $('.ow_chat_list').find(e.target).length;
                var isChatSelector = $('.ow_chat_selector').find(e.target).length;
                if ( !isTarget && !isSwitchToChatBtn && !isContactArea && !isChatSelector){
                    $.each(self.dialogs, function(id, dialog){
                        dialog.model.setIsSelected(false);
                    });
                }

            });

            $(document).ready(function(){
                self.searchFormElement = new SearchField("im_find_contact", "im_find_contact", OW.getLanguageText('mailbox', 'find_contact'));
                self.searchFormElement.setHandler(self);
            });

            this.minimizeButton.click(function(e){

                if (self.sortSettingsBtn.is(e.target) || self.sortSettingsBtn.find(e.target).length){
                    return;
                }

                if (self.model.get('chatBlockActive') == "1"){
                    self.model.set('chatBlockActive', "0");
                }
                else{
                    self.model.set('chatBlockActive', "1");
                }
            });

            this.soundSettingsBtn.click(function(){
                self.model.set('soundEnabled', !self.model.get('soundEnabled'));
            });

            this.sortSettingsBtn.click(function(){
                self.model.set('showOnlineOnly', !self.model.get('showOnlineOnly'));
            });

            this.contactListWrapper.bind('jsp-scroll-y', function(event, scrollPositionY, isAtTop, isAtBottom){
                if (self.model.loadedContactCount < self.model.contacts.length && !self.loadingContacts && isAtBottom){
                    self.loadingContacts = true;
                    self.model.loadList();
                    OW.updateScroll(self.contactListWrapper);
                    self.loadingContacts = false;
                }
            });

            $('.ow_chat_list .ow_chat_preloader').remove();

//            var im_showOnlineOnly = im_readCookie('im_showOnlineOnly');
//            if (im_showOnlineOnly){
//                OWMailbox.showOnlineOnly = parseInt(im_showOnlineOnly);
//            }
//            this.model.set('showOnlineOnly', OWMailbox.showOnlineOnly);
            this.setShowOnlineOnly();

//            var im_soundEnabled = im_readCookie('im_soundEnabled');
//            if (im_soundEnabled){
//                OWMailbox.soundEnabled = parseInt(im_soundEnabled);
//            }
//            this.model.set('soundEnabled', OWMailbox.soundEnabled);

            this.setSoundEnabled();

            if (OWMailbox.getStorage().getItem('chatBlockActive') == "1"){
                this.model.set('chatBlockActive', "1");
            }
        }
        ///////////////////////// End Chat Enabled ///////////////////////////////////////////////
    },

    addContact: function(user){

        var contactView = new MAILBOX_ContactView({model: user});

        var itemIndex = this.model.contacts.indexOf(user);

        if (itemIndex == 0){
            this.contactListContainer.prepend(contactView.render().$el);
        }
        else{
            this.contactListContainer.append(contactView.render().$el);
        }

        var jsp = this.contactListWrapper.data('jsp');

        if ( jsp ){
            OW.updateScroll( this.contactListWrapper );
        }
        else{
            OW.addScroll( this.contactListWrapper );
        }

        this.model.set('userOnlineCount', this.model.contacts.length);

        if (user.get('status') != 'offline'){
            var usersWithOnlineStatus = _.filter( this.model.contacts.models, function(contact){
                return contact.get('status') != 'offline' } );
            this.model.set('userOnlineStatusCount', usersWithOnlineStatus.length);
        }
    },

    construct: function(){

        var self = this;

        this.contactListContainer = $('.ow_chat_list ul');
        this.chatSelector = $('.ow_chat_selector');
        this.chatSelectorButton = $('.ow_btn_dialogs');
        this.chatSelectorList = $('.ow_chat_selector_list');

        this.chatSelectorTotalUnreadMessagesCountWrapper = $('.ow_chat_selector .ow_chat_block .ow_selector_panel .ow_count_wrap');
        this.chatSelectorUnreadMessagesCounter = $('.ow_chat_selector .ow_chat_block .ow_selector_panel .ow_count_wrap .ow_count_bg .ow_count');
        this.hiddenContactsCountWrapper = $('.ow_chat_selector .ow_chat_block .ow_selector_panel .ow_dialog_count');

        if (OWMailbox.chatModeEnabled && OWMailbox.useChat == 'available'){
            this.chatSettingsActive = false;
            this.fitWindowNumber = 0;

            this.contactListWrapper = $('.ow_chat_list');

            this.mainWindow = $('.ow_chat .ow_chat_block_wrap .ow_chat_block');

            this.minimizeButton = $('.btn2_panel');

            this.puller = $('.ow_puller', $('.ow_chat'));
            this.inDragging = false;

            this.puller.draggable({

                disabled: true,
                axis: "y",
                cursor: 'row-resize',
                drag: function(event, ui){
                    if (ui.position.top < 0)
                    {
                        if ( self.contactListWrapper.height() > $(window).innerHeight() * 0.8  )
                        {
                            return;
                        }
                    }

                    self.contactListWrapper.height( self.mainWindowHeight - ui.position.top );
                },
                stop: function(event, ui){
                    if ( self.contactListWrapper.height() > $(window).innerHeight() * 0.8  ){
                        self.contactListWrapper.height( $(window).innerHeight() * 0.8 );
                    }
                    self.puller.css('top','-10px');

                    if ( $('.ow_chat_list ul').height() > self.contactListWrapper.height() ){
                        OW.addScroll(self.contactListWrapper);
                    }
                    else{
                        self.contactListWrapper.width(245);
                    }

                    self.inDragging = false;
                },
                start: function(event, ui){
                    self.inDragging = true;
                    OW.removeScroll(self.contactListWrapper);
                    self.mainWindowHeight = self.contactListWrapper.height();
                }

            });

            this.foldingTimeout = false;
            this.foldingTime = 2000;

            this.notificationWrapper = $('.ow_chat');
            this.settingsWrapper = $('.ow_chat_settings');
            this.soundSettingsBtn = $('#mailboxSoundPreference');
            this.sortSettingsBtn = $('#mailboxSortUsersPreference');
            this.soundSettings = $('#im_enable_sound');
            this.totalUserOnlineCount = $('.totalUserOnlineCount');
            this.totalUserOnlineCountBackground = $('.totalUserOnlineCountBackground');
        }
    },

    countOfDialogsInChatSelector: function(){
        var size = 0, key;

        for (key in this.dialogs) {
            if (this.dialogs[key].model.isHidden)
            {
                size++;
            }
        }

        return size;
    },

    fitWindow: function() {
        var self = this;

        // exit from recursion 
        if (self.fitWindowNumber > 20)
        {
            self.fitWindowNumber = 0;
            return;
        }

        self.fitWindowNumber++;

        // get width of the contact finder
        var allChatsWidth = $('.ow_chat').outerWidth(true);

        // calculate all chats width
        $("#dialogsContainer > div:visible").each(function(index, div) {
            allChatsWidth += $(div).outerWidth(true);
        });

        // get the window inner width
        var winWidth = $(window).innerWidth();
 
        // do we need hide all chats?
        var minOpenedChats = typeof OW.Mailbox.newMessageFormController != "undefined" 
                && OW.Mailbox.newMessageFormController.isNewMessageWindowActive() ? 0 : 1;

        if (winWidth < allChatsWidth)
        {
            // folding
            if ($('.mailboxDialogBlock.ow_open').length > minOpenedChats) {
                var dialogs = $('.mailboxDialogBlock.ow_open');
                var box = !minOpenedChats
                    ? (typeof dialogs[1] != "undefined" ? dialogs[1] : dialogs[0])
                    : dialogs[1];

                var convId = $(box).data('convId');
                OW.trigger('mailbox.move_dialog_to_chat_selector', {convId: convId});

                self.fitWindow();
            }
        }
        else 
        {
            // get last hidden chat box width
            if ($('.ow_chat_selector_items li.ow_dialog_in_selector').length > 0)
            {
                var dialogs = $('div.mailboxDialogBlock.ow_hidden');
                var box = dialogs.last();

                if (winWidth > (allChatsWidth + $(box).outerWidth(true))) 
                {
                    // unfolding
                    var convId = $(box).data('convId');
                    OW.trigger('mailbox.remove_dialog_from_chat_selector', {convId: convId});
                    self.fitWindow();
                }
            }
        }

        if (self.fitWindowNumber > 0)
        {
            self.fitWindowNumber--;
        }
    },

    getContactFromUserList: function(userId){
        return OW.Mailbox.usersCollection.findWhere({opponentId: userId});
    },

    getDialog: function(convId, opponentId){
        var self = this;

        if( typeof self.dialogs[convId] == 'undefined' ){

            var contactModel = self.getContactFromUserList(opponentId);

            if (contactModel == null){

                $.post(OWMailbox.openDialogResponderUrl, {
                    userId: opponentId,
                    checkStatus: 2
                }, function(data){

                    if ( typeof data != 'undefined'){
                        if ( typeof data['warning'] != 'undefined' && data['warning'] ){
                            OW.message(data['message'], data['type']);
                        }
                        else{
                            if (data['use_chat'] && data['use_chat'] == 'promoted'){
                                self.showPromotion();
                            }
                            else{
                                OW.Mailbox.usersCollection.add(data);
                                contactModel = self.getContactFromUserList(opponentId);
                            }
                        }
                    }
                }, 'json');

                if (contactModel == null)
                {
                    return null;
                }
            }

            var dialogModel = new OWMailbox.Dialog.Model(convId);

            dialogModel.setConversationId(convId);
            dialogModel.setOpponentId(opponentId);

            dialogModel.setStatus(contactModel.get('status'));
            dialogModel.setDisplayName(contactModel.get('displayName'));
            dialogModel.setAvatarUrl(contactModel.get('avatarUrl'));
            dialogModel.setProfileUrl(contactModel.get('profileUrl'));

            self.dialogs[convId] = new OWMailbox.Dialog.Controller(dialogModel);
        }

        return self.dialogs[convId];
    },

    getRosterLength: function(){
        var size = 0, key;

        for (key in this.model.contacts) {
            if (this.model.contacts.hasOwnProperty(key)) size++;
        }

        return size;
    },

    hideChatSelector: function(){
        this.chatSelector.addClass('ow_hidden');
    },

    hidePromotion: function(){
        $('#chatPromotionBlock').addClass('ow_hidden');
    },

    isActiveMode: function(){
        return this.mainWindow.hasClass('ow_active');
    },

    isCompactMode: function(){
        return this.mainWindow.hasClass('ow_compact');
    },

    maximize: function(){
        this.mainWindow.addClass('ow_active');
        if (this.mainWindow.hasClass('ow_compact')){
            this.mainWindow.removeClass('ow_compact');
        }
        this.puller.draggable("enable");
        //this.puller.addClass('ow_im_draggable');
    },

    minimize: function(){
        this.mainWindow.removeClass('ow_active');
        this.puller.draggable("disable");
        //this.puller.removeClass('ow_im_draggable');
    },

    moveToActiveMode: function(){
        var self = this;

        if ( self.mainWindow.hasClass('ow_compact') && self.mainWindow.hasClass('ow_active') )
        {
            var speed = 'normal';

            self.mainWindow.removeClass('ow_compact');
            $('.ow_chat_cont').animate({
                right: 0
            }, speed);

            if ( $('.ow_chat_list ul').height() > self.contactListWrapper.height() )
            {
                OW.updateScroll(self.contactListWrapper);
            }

            self.puller.draggable("enable");
        }

    },

    onMainCounterUpdate: function(){
        if (OWMailbox.chatModeEnabled && !this.model.get('showOnlineOnly')){
            this.totalUserOnlineCount.html(this.model.get('userOnlineCount'));
        }
    },

    onOnlineCounterUpdate: function(){
        if (OWMailbox.chatModeEnabled && this.model.get('showOnlineOnly')){
            this.totalUserOnlineCount.html(this.model.get('userOnlineStatusCount'));
        }
    },

    removeDialog: function(convId){
        delete this.dialogs[convId];
    },

    showChatSelector: function(){
        this.chatSelector.removeClass('ow_hidden');
    },

    showPromotion: function(){
        $('#chatPromotionBlock').removeClass('ow_hidden');
    },

    updateChatSelector: function() {

        var count = this.countOfDialogsInChatSelector();
        this.hiddenContactsCountWrapper.html(count);
        if (count > 0)
        {
            this.showChatSelector();
        }
        if (count == 0)
        {
            this.hideChatSelector();
        }

    },

    updateChatSelectorUnreadMessagesCount: function(message){
        var chatSelectorUnreadMessages = 0;

        var self = this;

        $.each(this.dialogs, function(){
            if (this.model.isHidden){
                var convId = this.model.convId;
                for(var i=0; i<self.model.unreadMessageList.length; i++){
                    if (self.model.unreadMessageList.models[i].get('convId') == convId){
                        chatSelectorUnreadMessages++;
                    }
                }
            }
        });

        this.model.set('chatSelectorUnreadMessages', chatSelectorUnreadMessages);
    },

    updateList: function(name){

        var self = this;

        if (name == ''){
            for (var i=0; i<this.model.contacts.length; i++){
                var contact = this.model.contacts.models[i];
                if (contact.get('wasCreatedByScroll')){
                    contact.show();
                }
                else{
                    contact.hide();
                }
            }

            OW.updateScroll(self.contactListWrapper);
        }
        else{
            var expr = new RegExp(OW.escapeRegExp(name) , 'i');

            for (var i=0; i<this.model.contacts.length; i++){
                var contact = this.model.contacts.models[i];

                if (!expr.test(contact.get('displayName'))){
                    contact.hide();
                }
                else{
                    contact.show();
                }
            }
            OW.updateScroll(self.contactListWrapper);
        }
    },

    addUnreadMessage: function(message){

        var messages = this.model.unreadMessageList.where({convId: message.get('convId')});

        var conversation = OW.Mailbox.conversationsCollection.findWhere({conversationId: message.get('convId')});
        if (conversation){
            conversation.set('newMessageCount', messages.length);
            conversation.set('conversationRead', 0);
        }

        this.updateChatSelectorUnreadMessagesCount(message);
    },

    removeUnreadMessage: function(message){

        var messages = this.model.unreadMessageList.where({convId: message.get('convId')});

        var conversation = OW.Mailbox.conversationsCollection.findWhere({conversationId: message.get('convId')});
        if (conversation){
            conversation.set('newMessageCount', messages.length);
            conversation.set('conversationRead', 1);
            conversation.set('conversationViewed', true);
        }
    },

    updateContact: function(contact){
        var usersWithOnlineStatus = _.filter( this.model.contacts.models, function(contact){
            return contact.get('status') != 'offline' } );
        this.model.set('userOnlineStatusCount', usersWithOnlineStatus.length);
    },

    setShowOnlineOnly: function(){
        var self = this;

        if (self.model.get('showOnlineOnly')){
            $('#mailboxSortUsersPreference span').addClass('ow_btn_sort_online');
            $('#mailboxSortUsersPreference').attr('title', OW.getLanguageText('mailbox', 'show_all_users'));
            self.contactListWrapper.removeClass('showAllUsers');
            self.contactListWrapper.addClass('showOnlineOnly');
            this.onOnlineCounterUpdate();
        }
        else{
            $('#mailboxSortUsersPreference span').removeClass('ow_btn_sort_online');
            $('#mailboxSortUsersPreference').attr('title', OW.getLanguageText('mailbox', 'show_online_only'));
            self.contactListWrapper.addClass('showAllUsers');
            self.contactListWrapper.removeClass('showOnlineOnly');
            this.onMainCounterUpdate();
        }

        OW.hideTip($('#mailboxSortUsersPreference'));
        $('#mailboxSortUsersPreference').removeData('owTip');

        OW.bindTips($('#mailboxSortUsersPreference').parent());

        OW.updateScroll(self.contactListWrapper);
    },

    setSoundEnabled: function(){
        var self = this;

        if (self.model.get('soundEnabled'))
        {
            $('#mailboxSoundPreference span').removeClass('ow_btn_sound_off');
            $('#mailboxSoundPreference').attr('title', OW.getLanguageText('mailbox', 'silent_mode_on'));
        }
        else
        {
            $('#mailboxSoundPreference span').addClass('ow_btn_sound_off');
            $('#mailboxSoundPreference').attr('title', OW.getLanguageText('mailbox', 'silent_mode_off'));
        }

        OW.hideTip($('#mailboxSoundPreference'));
        $('#mailboxSoundPreference').removeData('owTip');

        OW.bindTips($('#mailboxSoundPreference').parent());
    }


});
/*                          End Contact Manager                              */
/*                         Dialog                                            */
OWMailbox.Dialog = {};

OWMailbox.Dialog.Model = function(convId){
    var self = this;

    this.convId = convId || 0;
    this.opponentId = null;
    this.mode = 'chat';
    this.status = '';
    this.firstMessageId = null;
    this.lastMessageTimestamp = 0;
    this.isLogLoaded = false;
    this.displayName = false;
    this.subject = false;
    this.profileUrl = false;
    this.avatarUrl = false;
    this.isSuspended = false;
    this.unreadMessagesCount = 0;
    this.unreadMessageList = {};
    this.shortUserData = '';

    this.isComposing = false;
    this.isActive = false;
    this.isSelected = false;
    this.isHidden = false;
    this.isLoaded = false;
    this.isOpened = false;

    this.conversationIdSetSubject = OWMailbox.makeObservableSubject();
    this.opponentIdSetSubject = OWMailbox.makeObservableSubject();
    this.modeSetSubject = OWMailbox.makeObservableSubject();
    this.statusUpdateSubject = OWMailbox.makeObservableSubject();
    this.lastMessageTimestampSetSubject = OWMailbox.makeObservableSubject();
    this.logLoadSubject = OWMailbox.makeObservableSubject();
    this.displayNameSetSubject = OWMailbox.makeObservableSubject();
    this.subjectSetSubject = OWMailbox.makeObservableSubject();
    this.profileUrlSetSubject = OWMailbox.makeObservableSubject();
    this.avatarUrlSetSubject = OWMailbox.makeObservableSubject();
    this.isSuspendedSetSubject = OWMailbox.makeObservableSubject();

    this.isComposingSetSubject = OWMailbox.makeObservableSubject();
    this.isActiveSetSubject = OWMailbox.makeObservableSubject();
    this.isSelectedSetSubject = OWMailbox.makeObservableSubject();
    this.isHiddenSetSubject = OWMailbox.makeObservableSubject();
    this.isLoadedSetSubject = OWMailbox.makeObservableSubject();
    this.isOpenedSetSubject = OWMailbox.makeObservableSubject();
    this.unreadMessagesCountSetSubject = OWMailbox.makeObservableSubject();
};

OWMailbox.Dialog.Model.prototype = {

    setConversationId: function(value){
        this.convId = value;
        this.conversationIdSetSubject.notifyObservers();
    },

    setOpponentId: function(value){
        this.opponentId = value;
        this.opponentIdSetSubject.notifyObservers();
    },

    setMode: function(value){
        this.mode = value;
        this.modeSetSubject.notifyObservers();
    },

    setStatus: function(value){
        this.status = value;
        this.statusUpdateSubject.notifyObservers();
    },

    setLastMessageTimestamp: function(value){
        this.lastMessageTimestamp = value;
        this.lastMessageTimestampSetSubject.notifyObservers();
    },

    setIsLogLoaded: function(value){
        this.isLogLoaded = value;
        this.logLoadSubject.notifyObservers();
    },

    setDisplayName: function(value){
        this.displayName = value;
        this.displayNameSetSubject.notifyObservers();
    },

    setSubject: function(value){
        this.subject = value;
        this.subjectSetSubject.notifyObservers();
    },

    setProfileUrl: function(value){
        this.profileUrl = value;
        this.profileUrlSetSubject.notifyObservers();
    },

    setAvatarUrl: function(value){
        this.avatarUrl = value;
        this.avatarUrlSetSubject.notifyObservers();
    },

    setIsSuspended: function(value, message){
        this.isSuspended = value;
        this.suspendReasonMessage = message;
        this.isSuspendedSetSubject.notifyObservers();
    },

    setIsComposing: function(value){
        this.isComposing = value;
        this.isComposingSetSubject.notifyObservers();
    },

    setIsActive: function(value){
        this.isActive = value;
        this.isActiveSetSubject.notifyObservers();
    },

    setIsHidden: function(value){
        this.isHidden = value;
        this.isHiddenSetSubject.notifyObservers();
    },

    setIsLoaded: function(value){
        this.isLoaded = value;
        this.isLoadedSetSubject.notifyObservers();
    },

    setIsOpened: function(value){
        this.isOpened = value;
        this.isOpenedSetSubject.notifyObservers();
    },

    setIsSelected: function(value){
        this.isSelected = value;
        this.isSelectedSetSubject.notifyObservers();
    },

    setUnreadMessagesCount: function(value){
        this.unreadMessagesCount = value;
        this.unreadMessagesCountSetSubject.notifyObservers();
    },

    addUnreadMessageToList: function(item){
        this.unreadMessageList[item.id] = item;

        var unreadMessagesCount = this.countUnreadMessages();
        this.setUnreadMessagesCount(unreadMessagesCount);
    },

    removeUnreadMessageFromList: function(id){
        delete this.unreadMessageList[id];

        var unreadMessagesCount = this.countUnreadMessages();
        this.setUnreadMessagesCount(unreadMessagesCount);
    },

    countUnreadMessages: function(){
        var size = 0, key;

        for (key in this.unreadMessageList) {
            if (this.unreadMessageList.hasOwnProperty(key))
            {
                size++;
            }
        }

        return size;
    }
};

OWMailbox.Dialog.Controller = function(model){

    var self = this;

    this.model = model;
    this.newMessageTimeout = 0;
    this.historyLoadAllowed = false;
    this.historyLoadInProgress = false;
    this.uid = OWMailbox.uniqueId('mailbox_dialog_'+this.model.convId+'_'+this.model.opponentId+'_');
    this.hasLinkObserver = false;
    this.embedLinkDetected = false;
    this.embedLinkResult = true;
    this.embedAttachmentsValue = '';
    this.embedAttachmentsObject = {};

    this.construct();

    owFileAttachments[this.uid] = new OWFileAttachment({
        'uid': this.uid,
        'submitUrl': OWMailbox.attachmentsSubmitUrl,
        'deleteUrl': OWMailbox.attachmentsDeleteUrl,
        'showPreview': false,
        'selector': '#main_tab_contact_' + this.model.opponentId + ' #dialogAttachmentsBtn',
        'pluginKey': 'mailbox',
        'multiple': false,
        'lItems': []
    });

    OW.bind('base.add_attachment_to_queue', function(data){

        if (data.pluginKey != 'mailbox' || data.uid != self.uid)
        {
            return;
        }

        self.attachmentsBtn.addClass('uploading');
//        $('input', self.attachmentsBtn).attr('disabled', 'disabled');
    });

    OW.bind('base.update_attachment', function(data){

        if (data.pluginKey != 'mailbox' || data.uid != self.uid)
        {
            return;
        }

        self.attachmentsBtn.removeClass('uploading');
//        $('input', self.attachmentsBtn).removeAttr('disabled');

        $.each(data.items, function(){
            if (!this.result)
            {
                OW.error(this.message);
            }
        })

        var newUid = OWMailbox.uniqueId('mailbox_dialog_'+self.model.convId+'_'+self.model.opponentId+'_');
        OW.trigger('base.file_attachment', { 'uid': self.uid, 'newUid': newUid });
        self.uid = newUid;

        OW.getPing().getCommand('mailbox_ping').start();
    });

    $('.ow_chat_in_dialog_wrap, .ow_chat_message_block', this.control).click(function( e ){
        self.model.setIsSelected(true);
    });

    this.minimizeMaximizeBtn.click(function(){

        if ( self.model.isActive )
        {
            self.hide();
        }
        else
        {
            self.open();
            self.model.setIsSelected(true);
        }

        if (!self.model.isSelected){
            OW.trigger('mailbox.clear_new_message_notification', {convId: self.model.convId});
        }

        return false;
    });

    this.closeBtn.click(function(){
        self.hideTab();

        if (!self.model.isSelected){
            OW.trigger('mailbox.clear_new_message_notification', {convId: self.model.convId});
        }
    });

    this.textareaControl.bind('paste', function(e){
        var element = this;
        setTimeout(function(){
            self.adjustTextarea($(element));
        }, 50);
    });

    this.textareaControl.bind('cut', function(e){
        var element = this;
        setTimeout(function(){
            self.adjustTextarea($(element));
        }, 50);
    });

    this.textareaControl.keyup(function(ev){
        var storage = OWMailbox.getStorage();
        storage.setItem('mailbox.dialog' + self.model.convId + '_form_message', $(this).val());

        if (ev.which === 8){
            self.adjustTextarea($(this));
        }

        if (ev.which === 13 && ev.shiftKey)
        {
            self.adjustTextarea($(this));
        }
    });

    this.textareaControl.keydown(function(ev){
        if (ev.which === 8){
            self.adjustTextarea($(this));
        }

        if (ev.which === 13 && ev.shiftKey)
        {
            self.adjustTextarea($(this));
        }
    });

    this.textareaControl.keypress(function (ev) {

        if (!self.model.isSelected){
            self.model.setIsSelected(true);
        }

        if (ev.which === 13 && !ev.shiftKey)
        {
            ev.preventDefault();

            var body = $(this).val();

            if ( $.trim(body) == '')
                return;

            self.sendMessage(body);

            if (self.dialogWindowHeight > 0)
            {
                self.messageListWrapper.height( self.dialogWindowHeight );
            }

            $(this).attr('rows', 1);
            $(this).css('height', self.textareaHeight);

            self.scrollDialog();

            self.model.setIsComposing(false);
        }
        else if (ev.which === 13 && ev.shiftKey)
        {
            self.adjustTextarea($(this));
        }
        else
        {
            self.adjustTextarea($(this));
        }
    });

    this.smallItemControl.click(function(){
        if (typeof OW.Mailbox.newMessageFormController != "undefined") {
            if (OW.Mailbox.newMessageFormController.closeNewMessageWindowWithConfirmation($('.mailboxDialogBlock.ow_open').length)) {
                OW.trigger('mailbox.open_dialog', {convId: self.model.convId, opponentId: self.model.convId, mode: self.model.mode});
            }
        }
        else {
            OW.trigger('mailbox.open_dialog', {convId: self.model.convId, opponentId: self.model.convId, mode: self.model.mode});
        }

        $('.ow_btn_dialogs').click();
    });

    this.messageListWrapper.bind('jsp-scroll-y', function(event, scrollPositionY, isAtTop, isAtBottom){

        /**/
        var dateCaps = $('.dialogMessageGroup', self.control);

        dateCaps.each(function(){

            var position = $(this).position();

            var scrollPosition = parseInt(scrollPositionY) - 15;

            if (scrollPosition > position.top)
            {
                self.setStickyDateCapValue($(this).data());
            }
            else
            {
                if (scrollPosition < 0)
                {
                    self.hideStickyDateCap();
                }
            }
        });

        /**/

        if (isAtTop && !self.historyLoadInProgress && self.model.firstMessageId != null && self.historyLoadAllowed)
        {
            //TODOS show preloader on top
            self.historyLoadInProgress = true;
            OW.Mailbox.sendInProcess = true;
            $.ajax({
                url: OWMailbox.getHistoryResponderUrl,
                type: 'POST',
                data: {
                    convId: self.model.convId,
                    messageId: self.model.firstMessageId,
                },
                success: function(data){

                    //TODOS hide preloader from top

                    if ( typeof data != 'undefined' )
                    {
                        if (data.log.length > 0)
                        {
                            var heightBefore = self.messageListControl.height();

                            $(data.log).each(function(){
                                self.writeHistory(this);
                            });

                            var heightAfter = self.messageListControl.height();

                            OW.updateScroll(self.messageListWrapper);

                            var jsp = self.messageListWrapper.data('jsp');
                            jsp.scrollByY(heightAfter - heightBefore);
                        }
                        else
                        {
                            self.historyLoadAllowed = false;
                        }
                    }
                },
                error: function(e){
                    OWMailbox.log(e);
                    self.messageListControl.html(e.responseText);
                },
                complete: function(){
                    self.historyLoadInProgress = false;
                    OW.Mailbox.sendInProcess = false;
                },
                dataType: 'json'
            });

        }

        if (isAtBottom)
        {
            self.historyLoadAllowed = true;
        }
    });

    this.messageListControl.on('click', '.callReadMessage', function(e){
        $.ajax({
            'type': 'POST',
            'url': OWMailbox.authorizationResponderUrl,
            'data': {
                'actionParams': $(this).attr('id')
            },
            'success': function(data){
                if (typeof data.error != 'undefined')
                {
                    OW.error(data.error);
                }
                else
                {
                    if (typeof data.authorizationActionText != 'undefined')
                    {
                        OW.info(data.authorizationActionText);
                    }
                    self.updateMessage(data);
                }
            },
            'dataType': 'json'
        })
    });

    this.model.conversationIdSetSubject.addObserver(function(){
//        self.control.attr('id', 'main_tab_contact_' + self.model.convId);
        self.control.data('convId', self.model.convId);
    });

    this.model.avatarUrlSetSubject.addObserver(function(){
        self.avatarControl.attr('src', self.model.avatarUrl);
    });

    this.model.profileUrlSetSubject.addObserver(function(){
        self.profileUrlControl.attr('href', self.model.profileUrl);
    });

    this.model.displayNameSetSubject.addObserver(function(){
        self.avatarControl.attr('alt', self.model.displayName);
        self.avatarControl.attr('title', self.model.shortUserData);
        OW.bindTips(self.control);
        self.displayNameControl.html(self.model.displayName);
        self.chatSelectorDisplayNameControl.html(self.model.displayName);
    });

    this.model.modeSetSubject.addObserver(function(){
//        if (self.model.mode == 'chat')
//        {
//            self.subjectBlockControl.remove();

        if (!self.hasLinkObserver){
            OWLinkObserver.observeInput('main_tab_contact_'+self.model.opponentId+' #dialogTextarea', function(link){

                self.embedLinkResult = false;
                self.embedLinkDetected = true;

                this.requestResult();

                this.onResult = function( r ){
                    self.embedLinkResult = true;

                    if (r.type == 'video' || r.type == 'link')
                    {
                        self.embedAttachmentsObject = r;
                        self.embedAttachmentsValue = JSON.stringify(r);
                    }

                    OW.trigger('mailbox.embed_link_request_result_'+self.model.convId, r);
                }
            });

            self.hasLinkObserver = true;
        }
//        }
    });

    this.model.statusUpdateSubject.addObserver(function(){

        self.statusControl.removeClass();
        self.statusControl.addClass('ow_chat_status');

        if (self.model.status != 'offline'){
            self.statusControl.addClass(self.model.status);
        }
    });

    this.model.isActiveSetSubject.addObserver(function(){
        if (!self.model.isActive){
            $('#main_tab_contact_' + self.model.opponentId).removeClass('ow_chat_dialog_active');
            $('#main_tab_contact_' + self.model.opponentId).removeClass('ow_active');
            if (self.model.unreadMessagesCount > 0){
                self.unreadMessageCountBlock.addClass('ow_count_active');
            }
        }
        else{
            $('#main_tab_contact_' + self.model.opponentId).addClass('ow_chat_dialog_active');
            $('#main_tab_contact_' + self.model.opponentId).addClass('ow_active');
            if (self.model.unreadMessagesCount > 0){
                self.unreadMessageCountBlock.removeClass('ow_count_active');
            }
        }

        var openedDialogs = OWMailbox.getOpenedDialogsCookie();

        if (typeof openedDialogs[self.model.convId] != 'undefined'){
            openedDialogs[self.model.convId]['isActive'] = self.model.isActive;
        }

        OWMailbox.setOpenedDialogsCookie(openedDialogs);
    });

    this.model.isSelectedSetSubject.addObserver(function(){
        if (self.model.isSelected){
            if ( self.model.unreadMessagesCount > 0 ){
                $.each(self.model.unreadMessageList, function(id, message){
                    OW.trigger('mailbox.mark_message_read', {message: message});
                    self.model.removeUnreadMessageFromList(id);
                });
            }
            self.chatBlock.addClass('ow_chat_block_active');
            //self.textareaControl.focus();
            OW.trigger('mailbox.dialog_selected', {convId: self.model.convId});
        }
        else{
            self.chatBlock.removeClass('ow_chat_block_active');
        }

        var openedDialogs = OWMailbox.getOpenedDialogsCookie();

        if (typeof openedDialogs[self.model.convId] != 'undefined'){
            openedDialogs[self.model.convId]['isSelected'] = self.model.isSelected;
        }

        OWMailbox.setOpenedDialogsCookie(openedDialogs);
    });

    this.model.isSuspendedSetSubject.addObserver(function(){
        if (self.model.isSuspended)
        {
            self.userIsUnreachableBlock.show();
            $('#conversationUserIsUnreachableText', self.userIsUnreachableBlock).html( self.model.suspendReasonMessage );

            if (self.model.mode == 'chat')
            {
                self.conversationChatFormBlock.hide()
            }

            if (self.model.mode == 'mail')
            {
                self.messageFormBlock.hide();
            }
        }
        else
        {
            self.userIsUnreachableBlock.hide();
            $('#conversationUserIsUnreachableText', self.userIsUnreachableBlock).html( '' );

            if (self.model.mode == 'chat')
            {
                self.conversationChatFormBlock.show()
            }

            if (self.model.mode == 'mail')
            {
                self.messageFormBlock.show();
            }
        }
    });

    this.model.unreadMessagesCountSetSubject.addObserver(function(){

        if (self.model.unreadMessagesCount == 0){
            self.unreadMessageCountBlock.removeClass('ow_count_active');
            self.unreadMessageCount.html('0');

            self.chatSelectorUnreadMessagesCountWrapper.hide();
            self.chatSelectorUnreadMessagesCountControl.html('0');
        }
        else{
            self.chatSelectorUnreadMessagesCountControl.html(self.model.unreadMessagesCount);

            if (self.model.isHidden){
                self.chatSelectorUnreadMessagesCountWrapper.show();
            }

            self.unreadMessageCount.html(self.model.unreadMessagesCount);
            if (!self.model.isActive){
                self.unreadMessageCountBlock.addClass('ow_count_active');
            }

            if ( self.model.isOpened && self.newMessageTimeout === 0 ){
                self.newMessageTimeout = setInterval(function() {

                    if ( self.control.hasClass('ow_chat_new_message') ){
                        self.control.removeClass('ow_chat_new_message');
                    }
                    else{
                        self.control.addClass('ow_chat_new_message');
                    }

                }, 1000);
            }
        }
    });

    this.model.logLoadSubject.addObserver(function(){
        self.model.setIsOpened(true);
    });

    this.model.isOpenedSetSubject.addObserver(function(){
        if (!self.control.hasClass('ow_hidden'))
        {
            self.control.addClass('ow_open');
        }

        self.enablePuller();

        var storage = OWMailbox.getStorage();
        var message = storage.getItem('mailbox.dialog' + self.model.convId + '_form_message');
        if (typeof message != 'undefined' && message != null && message != '')
        {
            var lines = message.split("\n");
            self.textareaControl.attr('rows', lines.length);
            var offset = 0;
            for (var i=1; i<=lines.length; i++)
            {
                if (i == 2)
                {
                    offset = offset + 12;
                    $('.ow_chat_message', self.control).removeClass('scroll');
                }
                else
                {
                    if (i >= 3 && i <= 6)
                    {
                        offset = offset + 17;
                        $('.ow_chat_message', self.control).removeClass('scroll');
                    }
                    else
                    {
                        if (i > 6)
                        {
                            offset = 80;
                            $('.ow_chat_message', self.control).addClass('scroll');
                            break;
                        }
                    }
                }
            }
            self.textareaControl.css('height', self.textareaHeight + offset);

            self.textareaControl.val(message);
        }

        self.scrollDialog();

        var openedDialogs = OWMailbox.getOpenedDialogsCookie();

        if (self.model.convId != 0 && typeof openedDialogs[self.model.convId] == 'undefined'){
            openedDialogs[self.model.convId] = {opponentId: self.model.opponentId, mode: self.model.mode, isActive: self.model.isActive, isSelected: self.model.isSelected};
        }

        OWMailbox.setOpenedDialogsCookie(openedDialogs);
    });

    OW.bind('mailbox.presence', function(presence){
        if (presence.opponentId != self.model.opponentId)
        {
            return;
        }
        self.model.setStatus(presence.status);
    });

    OW.bind('mailbox.move_dialog_to_chat_selector', function(data){
        if (data.convId != self.model.convId)
        {
            return;
        }

        self.model.setIsHidden(true);
        self.model.setIsSelected(false);
        self.control.addClass('ow_hidden');
        self.control.removeClass('ow_open');

        self.smallItemControl.show();
        self.smallItemControl.addClass('ow_dialog_in_selector');

        OW.trigger('mailbox.dialog_moved_to_chat_selector', {convId: self.model.convId});
    });

    OW.bind('mailbox.remove_dialog_from_chat_selector', function(data){
        if (data.convId != self.model.convId)
        {
            return;
        }

        self.model.setIsHidden(false);

        self.control.removeClass('ow_hidden');
        self.control.addClass('ow_open');

        self.scrollDialog();

        self.smallItemControl.hide();
        self.smallItemControl.removeClass('ow_dialog_in_selector');

        OW.trigger('mailbox.dialog_removed_from_chat_selector', {convId: self.model.convId});
    });

    OW.bind('mailbox.new_message_notification', function(data){
        if (data.message.convId != self.model.convId)
        {
            return;
        }

        self.model.addUnreadMessageToList(data.message);
    });

    OW.bind('mailbox.data_received_for_'+self.model.opponentId, function(data){

        if (self.model.convId == 0)
        {
            delete OW.Mailbox.contactManagerView.dialogs[self.model.convId];

            self.model.setConversationId(data.conversationId);
            OW.Mailbox.contactManagerView.dialogs[data.conversationId] = self;
        }

        if ( self.model.convId == 0 || self.model.convId == data.conversationId )
        {
            self.model.setAvatarUrl(data.avatarUrl);
            self.model.shortUserData = data.shortUserData;
            self.model.setDisplayName(data.displayName);
//            self.model.setMode(data.mode);
            self.model.setProfileUrl(data.profileUrl);
            self.model.setStatus(data.status);
//            self.model.setSubject(data.subject);
        }
    });

    OW.bind('mailbox.message_was_read', function(data){
        if (data.message.convId != self.model.convId)
        {
            return;
        }

        OW.trigger('mailbox.clear_new_message_blinking', data);
    });

    OW.bind('mailbox.clear_new_message_blinking', function(data){
        if (data.message.convId != self.model.convId)
        {
            return;
        }

        if ( self.newMessageTimeout !== 0 )
        {
            clearInterval( self.newMessageTimeout );
            if ( self.control.hasClass('ow_chat_new_message') )
            {
                self.control.removeClass('ow_chat_new_message');
            }

            self.newMessageTimeout = 0;
        }
    });

    OW.bind('mailbox.send_message', function(data){
        if (data.sentFrom != 'dialog' && data.opponentId == self.model.opponentId && data.convId == self.model.convId)
        {
            self.write(data.tmpMessage);
        }
    });

    OW.bind('mailbox.update_message', function(data){
        if (data.sentFrom != 'dialog' && data.opponentId == self.model.opponentId && data.convId == self.model.convId)
        {
            self.updateMessage(data.message);
        }
    });

    OW.bind('mailbox.after_ping', function(data){

        var openedDialogs = OWMailbox.getOpenedDialogsCookie();

        $.each(openedDialogs, function(convId, presence){
            if (convId == self.model.convId){
                self.model.setIsSelected(presence.isSelected);
            }
        });
    });

    self.setData();
};

OWMailbox.Dialog.Controller.prototype = {

    construct: function(){
        var self = this;

        this.control = $("#dialogPrototypeBlock").clone();

        this.control.attr('id', 'main_tab_contact_' + this.model.opponentId);
        this.control.data('convId', this.model.convId);
        this.control.addClass('mailboxDialogBlock');

        this.chatBlock = $('.ow_chat_block', this.control);

        this.messageListWrapper = $('.ow_chat_in_dialog', this.control);
        this.dialogWindowHeight = 250;
        this.dialogWindowWidth = 250;

        this.messageListControl = $('#dialogLog', this.messageListWrapper);

        this.avatarControl = $('#dialogProfileAvatarUrl', this.control);
        this.displayNameControl = $('#dialogProfileDisplayName', this.control);
//        this.subjectControl = $('#dialogSubject', this.control);
//        this.subjectBlockControl = $('#dialogSubjectBlock', this.control);
        this.profileUrlControl = $('#dialogProfileUrl', this.control);
        this.minimizeMaximizeBtn = $('#dialogMinimizeMaximizeBtn', this.control);
        this.closeBtn = $('#dialogCloseBtn', this.control);
        this.textareaControl = $('#dialogTextarea', this.control);
        this.textareaHeight = 42;

        this.preloaderControl = $('#dialogPreloader', this.control);
        this.statusControl = $('#dialogProfileStatus', this.control);

        this.unreadMessageCountBlock = $('#dialogUnreadMessageCountBlock', this.control);
        this.unreadMessageCount = $('#dialogUnreadMessageCount', this.control);

        this.messageGroupStickyBlockControl = $('#dialogStickyDateCapBlock', this.control);
        this.userIsUnreachableBlock = $('#dialogUserIsUnreachable', this.control);
        this.messageFormBlock = $('#dialogMessageFormBlock', this.control);

        this.puller = $('.ow_vertical_puller', this.control);
        this.puller.css('position','absolute');
        this.puller.draggable({

            disabled: false,
            axis: "y",
            cursor: 'row-resize',
            drag: function(event, ui){
                if (ui.position.top < 0)
                {
                    if (self.control.height() > 506)
                    {
                        return;
                    }

                    if ( self.messageListWrapper.height() > $(window).innerHeight() * 0.8  )
                    {
                        return;
                    }
                }
                else
                {
                    if (self.control.height() < 334)
                    {
                        return;
                    }
                }
                self.messageListWrapper.height( self.dialogWindowHeight - ui.position.top );
            },
            stop: function(event, ui){
                if ( self.messageListWrapper.height() > $(window).innerHeight() * 0.8  )
                {
                    self.messageListWrapper.height( $(window).innerHeight() * 0.8 );
                }
                self.puller.css('top','-10px');
                OW.updateScroll(self.messageListWrapper);
            },
            start: function(event, ui){
                self.dialogWindowHeight = self.messageListWrapper.height();
            }
        });

        this.diagPuller = $('.ow_diagonal_puller', this.control);
        this.diagPuller.css('position','absolute');
        this.diagPuller.draggable({

            disabled: false,
            axis: "xy",
            cursor: 'row-resize',
            drag: function(event, ui){

                if (ui.position.left < 0)
                {
                    if (self.control.width() > 404)
                    {
                        return;
                    }
                }
                else
                {
                    if (self.control.width() < 249)
                    {
                        return;
                    }
                }

                self.control.width( self.dialogWindowWidth - ui.position.left );

                if (ui.position.top < 0)
                {
                    if (self.control.height() > 506)
                    {
                        return;
                    }
                }
                else
                {
                    if (self.control.height() < 334)
                    {
                        return;
                    }
                }
                self.messageListWrapper.height( self.dialogWindowHeight - ui.position.top );
                OW.trigger('mailbox.draggable.drag', {"puller" : self.puller});
            },
            stop: function(event, ui){
                if ( self.messageListWrapper.width() > $(window).innerWidth() * 0.8  )
                {
                    self.messageListWrapper.width( $(window).innerWidth() * 0.8 );
                }
                if ( self.messageListWrapper.height() > $(window).innerHeight() * 0.8  )
                {
                    self.messageListWrapper.height( $(window).innerHeight() * 0.8 );
                }
                self.puller.css('top','-10px');

                self.diagPuller.css('left', '0px');
                self.diagPuller.css('top', '0px');
                OW.updateScroll(self.messageListWrapper);
                OW.trigger('mailbox.draggable.stop', {"puller" : self.puller});
            },
            start: function(event, ui){
                self.dialogWindowHeight = self.messageListWrapper.height();
                self.dialogWindowWidth = self.control.width();
            }
        });

        $('#dialogsContainer').prepend(this.control);

        /* Add item to chat selector */
        this.chatSelectorContactListContainer = $('.ow_chat_selector_items');

        this.smallItemControl = $('#ow_chat_selector_items_proto li').clone();
        this.smallItemControl.attr('id', 'chatSelectorContactItem'+this.model.convId);
        this.chatSelectorDisplayNameControl = $('#chatSelectorContactItemDisplayName', this.smallItemControl);
        this.chatSelectorUnreadMessagesCountWrapper = $('#chatSelectorContactItemCounterBlock', this.smallItemControl);
        this.chatSelectorUnreadMessagesCountControl = $('#chatSelectorContactItemCounter', this.smallItemControl);
        this.chatSelectorContactListContainer.append(this.smallItemControl);

        this.attachmentsBtn = $('#dialogAttachmentsBtn', this.control);

        OW.trigger('mailbox.after_dialog_render', [{'control' : this.control, 'opponentId' : this.model.opponentId}]);

    },

    adjustTextarea: function(input){

        var textWidth = input.val().width(input.css('font'));
        var lines = input.val().split("\n");
        var linesLength = 1;

        if (textWidth > input.width()){
            linesLength = Math.ceil( textWidth / input.width() );
            if (linesLength < lines.length){
                linesLength = lines.length;
            }
        }
        else{
            linesLength = lines.length;
        }

        input.attr('rows', linesLength);
        var offset = 0;
        for (var i=1; i<=linesLength; i++){
            if (i == 2){
                offset = offset + 12;
                $('.ow_chat_message', self.control).removeClass('scroll');
            }
            else{
                if (i >= 3 && i <= 6){
                    offset = offset + 17;
                    $('.ow_chat_message', self.control).removeClass('scroll');
                }
                else{
                    if (i > 6){
                        offset = 80;
                        $('.ow_chat_message', self.control).addClass('scroll');
                        break;
                    }
                }
            }
        }
        input.css('height', this.textareaHeight + offset);
        this.messageListWrapper.height( this.dialogWindowHeight - offset );

        this.scrollDialog();
    },

    disablePuller: function(){
//        this.puller.draggable("disable");
    },

    enablePuller: function() {
//        this.puller.draggable("enable");
    },

    hide: function(){

        this.control.removeClass('ow_active');
        this.disablePuller();

        OW.updateScroll(this.messageListWrapper);

        if (this.model.convId != 0)
        {
            var openedDialogs = OWMailbox.getOpenedDialogsCookie();
            openedDialogs[this.model.convId] = {opponentId: this.model.opponentId, mode: this.model.mode, isActive: 0};
            OWMailbox.setOpenedDialogsCookie(openedDialogs);
        }

        this.model.setIsActive(false);
        this.model.setIsSelected(false);

        OW.trigger('mailbox.dialog_hidden', {convId: this.model.convId});

        return this;
    },

    hideComposing: function(){
        $('#message_composing', this.messageListControl).remove();
        this.model.setIsComposing(false);
        //this.scrollDialog();
    },

    hideStickyDateCap: function(){
        this.messageGroupStickyBlockControl.hide();
    },

    hideTab: function(){

        this.messageListWrapper.hide();
        this.control.hide();

        this.messageListWrapper.remove();
        this.control.remove();
        this.smallItemControl.remove();

        var storage = OWMailbox.getStorage();
        var openedDialogs = OWMailbox.getOpenedDialogsCookie();

        if (this.model.convId != 0)
        {
            delete openedDialogs[this.model.convId];

            storage.removeItem('mailbox.dialog' + this.model.convId + '_form_message');
        }

        OWMailbox.setOpenedDialogsCookie(openedDialogs);

        OW.trigger('mailbox.dialog_closed', {convId: this.model.convId});
    },

    loadHistory: function(){

        var self = this;

        var ajaxData = {};
        ajaxData['actionData'] = {
            'uniqueId': OWMailbox.uniqueId('getLog'),
            'name': 'getLog',
            'data': {
                'convId': self.model.convId,
                'opponentId': self.model.opponentId,
                'lastMessageTimestamp': self.model.lastMessageTimestamp
            }
        };

        ajaxData['actionCallbacks'] = {
            success: function(data){

                self.removePreloader();

                if ( typeof data != 'undefined' )
                {
                    OW.trigger('mailbox.data_received_for_'+data.opponentId, data);

                    if (data.close_dialog)
                    {
                        OW.trigger('mailbox.close_dialog', {convId: self.model.convId, opponentId: self.model.opponentId});
                        return;
                    }

                    if (data.log && data.log.length > 0)
                    {
                        $(data.log).each(function(i){
                            if (i == 0)
                            {
                                self.model.firstMessageId = this.id;
                            }
                            self.write(this, 'history');
                        });
                    }

                    if (data.isSuspended)
                    {
                        self.messageFormBlock.parent().remove();
                        $('#dialogUserIsUnreachableText', self.userIsUnreachableBlock).html( data.suspendReasonMessage );
                        self.userIsUnreachableBlock.css('display', 'block');
                    }
                }

                OW.trigger('mailbox.dialogLogLoaded', {opponentId: data.opponentId});
                self.model.setIsLogLoaded(true);
                self.model.setIsLoaded(true);
            },
            error: function(e){
                OWMailbox.log(e);
                self.removePreloader();
                self.messageListControl.html(e.responseText);
            },
            complete: function(){
                OW.Mailbox.sendInProcess = false;
            }
        };
        OW.Mailbox.addAjaxData(ajaxData);

        if (self.model.isSelected){
            var ajaxData2 = {};
            ajaxData2['actionData'] = {
                'uniqueId': OWMailbox.uniqueId('markConversationRead'),
                'name': 'markConversationRead',
                'data': { conversationId: self.model.convId }
            };
            ajaxData2['actionCallbacks'] = {
                success: function( data ){},
                complete: function(){}
            }

            OW.Mailbox.addAjaxData(ajaxData2);
        }

        OW.Mailbox.sendInProcess = true;
        OW.Mailbox.sendData();
    },

    open: function(){

        var self = this;

//        if (this.model.isOpened)
//        {
        this.onOpen();
//        }
//        else
//        {
//            OW.bind(self.model.convId+'_tabOpened', function(){
//                self.onOpen();
//                OW.unbind(self.model.convId+'_tabOpened');
//            });
//        }

        return this;
    },

    onOpen: function(){

        this.model.setIsActive(true);
        this.model.setIsOpened(true);
//        this.model.setIsSelected(true);

        OW.trigger('mailbox.dialog_opened', {convId: this.model.convId});
    },

    removePreloader: function(){
        this.preloaderControl.remove();
        OW.addScroll(this.messageListWrapper, {contentWidth: '0px'});
    },

    scrollDialog: function(){
        this.historyLoadAllowed = false;
        OW.updateScroll(this.messageListWrapper);

        var jsp = this.messageListWrapper.data('jsp');
        if (typeof jsp != 'undefined' && jsp != null)
        {
            lastMessage = this.messageListControl.find('.clearfix').last();
            if (lastMessage.length > 0){
                jsp.scrollToElement(lastMessage, true, true);
            }
            else{
                jsp.scrollToBottom();
            }
        }

    },

    sendMessage: function(text){

        var self = this;

        var tmpMessageUid = OWMailbox.uniqueId('tmpMsg_');

        var d = new Date();
        var utc = d.getTime() / 1000 + (d.getTimezoneOffset() * 60);
        var timeStamp = parseInt(utc + OWMailbox.serverTimezoneOffset * 3600);

        var timeLabel = OWMailbox.formatAMPM(new Date(timeStamp*1000));

        if (!self.embedLinkDetected)
        {
            var tmpMessage = {
                'rawMessage' : true,
                'isSystem': false,
                'date': OWMailbox.todayDate,
                'dateLabel': OWMailbox.todayDateLabel,
                'id': tmpMessageUid,
                'text': text.replace(/<\/?[^>]+>/gi, ''),
                'attachments': [],
                'senderId': OWMailbox.userDetails.userId,
                'recipientId': self.model.opponentId,
                'timeStamp': timeStamp,
                'timeLabel': timeLabel
            };

            OW.trigger('mailbox.send_message', {'sentFrom': 'dialog', 'opponentId': self.model.opponentId, 'convId': self.model.convId, 'tmpMessage': tmpMessage});

            var data = {
                'convId': self.model.convId,
                'text': text,
                'uid': self.uid,
                'embedAttachments': self.embedAttachmentsValue
            };

            self.postMessage(tmpMessageUid, data);
        }
        else
        {
            var tmpMessage = {
                'rawMessage' : true,
                'isSystem': true,
                'date': OWMailbox.todayDate,
                'dateLabel': OWMailbox.todayDateLabel,
                'id': tmpMessageUid,
                'attachments': [],
                'senderId': OWMailbox.userDetails.userId,
                'recipientId': self.model.opponentId,
                'timeStamp': timeStamp,
                'timeLabel': timeLabel
            };

            var preloaderContainer = $('#dialogEmbedLinkBlockPrototype').clone();
            $('#dialogMessageText', preloaderContainer).html(text);
            tmpMessage['text'] = preloaderContainer.html();

            OW.trigger('mailbox.send_message', {'sentFrom': 'dialog', 'opponentId': self.model.opponentId, 'convId': self.model.convId, 'tmpMessage': tmpMessage});

            if (self.embedLinkResult)
            {
                var data = {
                    'convId': self.model.convId,
                    'text': text,
                    'uid': self.uid,
                    'embedAttachments': self.embedAttachmentsValue
                };

                self.postMessage(tmpMessageUid, data);
            }
            else
            {
                OW.bind('mailbox.embed_link_request_result_'+self.model.convId, function(r){
                    var data = {
                        'convId': self.model.convId,
                        'text': text,
                        'uid': self.uid,
                        'embedAttachments': self.embedAttachmentsValue
                    };

                    self.postMessage(tmpMessageUid, data);
                    OW.unbind('mailbox.embed_link_request_result_'+self.model.convId);
                });
            }

            OWLinkObserver.getObserver('main_tab_contact_'+self.model.opponentId+' #dialogTextarea').resetObserver();
        }

        tmpMessage.text = tmpMessage.text.nl2br();

        self.write(tmpMessage);

        var storage = OWMailbox.getStorage();
        storage.setItem('mailbox.dialog' + self.model.convId + '_form_message', '');
        self.textareaControl.val('');
    },

    postMessage: function(tmpMessageUid, data){
        var self = this;
        var ajaxData = {};

        ajaxData['actionData'] = {
            'uniqueId': OWMailbox.uniqueId('postMessage'),
            'name': 'postMessage',
            'data': data
        };

        ajaxData['actionCallbacks'] = {
            'tmpMessageUid' : tmpMessageUid,
            'success': function(data){

                if (typeof data.error == 'undefined' || data.error == null)
                {
                    data.message.uniqueId = tmpMessageUid;
                    self.updateMessage(data.message);
                    OW.Mailbox.lastMessageTimestamp = data.message.timeStamp;
                    OW.trigger('mailbox.update_message', {'sentFrom': 'dialog', 'opponentId': self.model.opponentId, 'convId': self.model.convId, 'message': data.message});
                }
                else
                {
                    OW.error(data.error);
                    self.showSendMessageFailed(tmpMessageUid);
                }
            },
            'error': function(e){
                self.messageListControl.html(e.responseText);
                self.showSendMessageFailed(tmpMessageUid);
            },
            'complete': function(){
                OW.Mailbox.sendInProcess = false;

                self.embedLinkResult = true;
                self.embedLinkDetected = false;
                self.embedAttachmentsValue = '';
                self.embedAttachmentsObject = {};
            }
        };

        OW.Mailbox.sendData(ajaxData);
    },

    showSendMessageFailed: function(messageId){

        var self = this;

        $('#messageItem'+messageId+' .ow_dialog_in_item', self.control).addClass('errormessage');
        $('#messageItem'+messageId+' .ow_dialog_in_item', self.control).prepend('<span class="ow_errormessage_not ow_red ow_small">'+OW.getLanguageText('mailbox', 'send_message_failed')+'</span>');

    },

    selectTab: function(value){

        var val = value || true;

        this.model.setIsSelected(val);
    },

    setData: function(){

        if (this.model.avatarUrl)
        {
            this.model.setAvatarUrl(this.model.avatarUrl);
        }
        if (this.model.displayName)
        {
            this.model.setDisplayName(this.model.displayName);
        }
        if (this.model.profileUrl)
        {
            this.model.setProfileUrl(this.model.profileUrl);
        }
//        if (this.model.mode == 'mail' && this.model.subject)
//        {
//            this.model.setSubject(this.model.subject);
//        }
        if (this.model.status)
        {
            this.model.setStatus(this.model.status);
        }
    },

    setStickyDateCapValue: function(data){

        if (data.date == OWMailbox.todayDate)
        {
            this.hideStickyDateCap();
        }
        else
        {
            this.showStickyDateCap();
        }

        $('#dialogStickyDateCap', this.messageGroupStickyBlockControl).html(data.dateLabel);
        this.messageGroupStickyBlockControl.data(data);
    },

    showComposing: function(){


        var self = this;

        if (this.model.isComposing)
        {
            return;
        }

        var message_composing_container = $('#dialogChatMessagePrototypeBlock').clone();
        message_composing_container.attr('id', 'message_composing');
        $('.ow_dialog_item', message_composing_container).addClass('odd');

        this.messageListControl.append(message_composing_container);
        this.scrollDialog();

        this.setIsComposing(true);

        // Autohide after sometime
        this.model.showComposingTimeout = setTimeout(function(){
            self.hideComposing();
        }, 2000);
    },

    showStickyDateCap: function(){
        this.messageGroupStickyBlockControl.show();
    },

    showTab: function(){

        var self = this

        if (this.model.isOpened)
        {
            return this;
        }

        this.control.addClass('ow_open');

        OW.trigger('mailbox.dialog_tab_shown', {convId: this.model.convId});
        OW.trigger(this.model.convId+'_tabOpened');

        if ( !this.model.isLogLoaded )
        {
            this.loadHistory();
        }

        return this;
    },

    showTimeBlock: function(timeLabel){

        var timeBlock = $('#dialogTimeBlockPrototypeBlock').clone();

        timeBlock.attr('id', 'timeBlock'+this.model.lastMessageTimestamp);

        $('.ow_time_text', timeBlock).html(timeLabel);

        this.messageListControl.append(timeBlock);
//        this.scrollDialog();

        return this;
    },

    updateChatMessage: function(message){
        if (typeof message.uniqueId != 'undefined'){
            var messageContainer = $('#messageItem'+message.uniqueId, this.control);

            messageContainer.attr('id', 'messageItem'+message.id);
            //messageContainer.attr('timestamp', message.timeStamp);
        }
        else{
            var messageContainer = $('#messageItem'+message.id, this.control);
        }

        var html = '';
        if (message.isSystem){
            html = message.text;

            messageContainer.html( html );
        }
        else{
            if (message.attachments.length != 0)
            {
                var i = 0;

                if (message.attachments[i]['type'] == 'image')
                {
                    messageContainer.addClass('ow_dialog_picture_item');
                    $('#dialogMessageText', messageContainer).html( '<a href="'+message.attachments[i]['downloadUrl']+'" target="_blank"><img src="'+message.attachments[i]['downloadUrl']+'" /></a>' );
                }
                else
                {
                    $('.ow_dialog_in_item', messageContainer).addClass('fileattach');

                    var attachment = $('#conversationFileAttachmentBlockPrototype').clone();
                    attachment.removeAttr('id');

                    $('#conversationFileAttachmentFileName', attachment).html( OWMailbox.formatAttachmentFileName(message.attachments[i]['fileName']) );
                    $('#conversationFileAttachmentFileName', attachment).attr('href', message.attachments[i]['downloadUrl']);
                    $('#conversationFileAttachmentFileSize', attachment).html( OWMailbox.formatAttachmentFileSize(message.attachments[i]['fileSize']) );

                    $('.ow_dialog_in_item', messageContainer).html( attachment.html() );

                }
            }
            else
            {
//                html = htmlspecialchars(message.text, 'ENT_QUOTES');
                html = message.text;

                if ($('#dialogMessageText', messageContainer).length == 0){
                    tmpMessageContainer = $('#dialogChatMessagePrototypeBlock').clone();
                    tmpMessageContainer.attr('id', 'messageItem'+message.id);
                    messageContainer.html(tmpMessageContainer.html());
                }

                $('#dialogMessageText', messageContainer).html( html );
                $('#dialogMessageText', messageContainer).autolink();
            }
        }

        if ( message.senderId != this.model.opponentId ){
            $('div.ow_dialog_item', messageContainer).addClass('even');
        }
        else{
            $('div.ow_dialog_item', messageContainer).addClass('odd');
        }

        this.scrollDialog();
        
        OW.trigger('mailbox.update_chat_message', message);
    },

    updateMessage: function(message){
        if (this.model.mode == 'chat')
        {
            this.updateChatMessage(message);
        }

    },

    writeChatMessage: function(message, css_class){

        var css_class = css_class || null;

        if ($('#messageItem'+message.id, this.control).length > 0)
        {
            return;
        }

        var groupContainer = $('#groupedMessages-'+message.date, this.control);
        if (groupContainer.length == 0){
            groupContainer = $('#dialogDateCapBlock').clone();
            $('#dialogDateCap', groupContainer).html(message.dateLabel);

            groupContainer.attr('id', 'groupedMessages-'+message.date);
            groupContainer.data({
                date: message.date,
                dateLabel: message.dateLabel
            });
        }

        var messageContainer = null;

        if (message.isSystem){
            messageContainer = $('#dialogSysMessagePrototypeBlock').clone();
        }
        else{
            messageContainer = $('#dialogChatMessagePrototypeBlock').clone();
        }

        messageContainer.attr('id', 'messageItem'+message.id);
        messageContainer.attr('data-tmp-id', 'messageItem'+message.id);
        messageContainer.attr('data-timestamp', message.timeStamp);
        messageContainer.addClass('message');

        var html = '';
        if (message.isSystem){
            html = message.text;

//            $('#dialogMessageWrapper', messageContainer).html( html );
            messageContainer.html( html );
        }
        else
        {
            if (message.attachments.length != 0){
                var i = 0;

                if (message.attachments[i]['type'] == 'image')
                {
                    messageContainer.addClass('ow_dialog_picture_item');
                    $('#dialogMessageText', messageContainer).html( '<a href="'+message.attachments[i]['downloadUrl']+'" target="_blank"><img src="'+message.attachments[i]['downloadUrl']+'" /></a>' );
                }
                else
                {
                    $('.ow_dialog_in_item', messageContainer).addClass('fileattach');

                    var attachment = $('#conversationFileAttachmentBlockPrototype').clone();
                    attachment.removeAttr('id');

                    $('#conversationFileAttachmentFileName', attachment).html( OWMailbox.formatAttachmentFileName(message.attachments[i]['fileName']) );
                    $('#conversationFileAttachmentFileName', attachment).attr('href', message.attachments[i]['downloadUrl']);
                    $('#conversationFileAttachmentFileSize', attachment).html( OWMailbox.formatAttachmentFileSize(message.attachments[i]['fileSize']) );

                    $('.ow_dialog_in_item', messageContainer).html( attachment.html() );
                }
            }
            else
            {
//                html = htmlspecialchars(message.text, 'ENT_QUOTES');
                html = message.text;

                $('#dialogMessageText', messageContainer).html( html );
                $('#dialogMessageText', messageContainer).autolink();
            }
        }

        if ( message.senderId != this.model.opponentId ){
            $('div.ow_dialog_item', messageContainer).addClass('even');
        }
        else{
            $('div.ow_dialog_item', messageContainer).addClass('odd');
        }

        if (css_class != null){
            $('div.ow_dialog_item', messageContainer).addClass(css_class);
        }

        // get last message
        var lastMessage = this.messageListControl.find('.message:last');

        // HOTFIX; Correct messages oredring (skalfa/workflow#35)
        if (message.rawMessage || !lastMessage.length || lastMessage.attr('data-timestamp') < message.timeStamp) {
            if (this.lastMessageDate != message.date){
                this.lastMessageDate = message.date;
                this.messageListControl.append(groupContainer);
            }

            if ( message.timeLabel != this.model.lastMessageTimeLabel ){
                this.model.lastMessageTimeLabel = message.timeLabel;
                this.showTimeBlock(message.timeLabel);
            }

            this.messageListControl.append(messageContainer);
            this.scrollDialog();

            this.model.setLastMessageTimestamp(message.timeStamp);
            this.model.lastMessageId = message.id;
        }
        else {
            $(messageContainer).insertBefore(lastMessage);
            this.scrollDialog();
        }

        var soundEnabled   = im_readCookie('im_soundEnabled');
        var isSoundEnabled = soundEnabled !== null 
            ? parseInt(soundEnabled) 
            : OWMailbox.soundEnabled; // use the default value

        if (css_class == null && isSoundEnabled){
            var audioTag = document.createElement('audio');
            if (!(!!(audioTag.canPlayType) && ("no" != audioTag.canPlayType("audio/mp3")) && ("" != audioTag.canPlayType("audio/mp3")) && ("maybe" != audioTag.canPlayType("audio/mp3")) )) {
                AudioPlayer.embed("im_sound_player_audio", {
                    soundFile: OWMailbox.soundUrl,
                    autostart: 'yes'
                });
            }
            else{
                $('#im_sound_player_audio')[0].play();
            }
        }

//        OW.Mailbox.lastMessageTimestamp = message.timeStamp;
    },

    write: function(message, css_class){
        if (this.model.mode == 'chat'){
            this.writeChatMessage(message, css_class);
        }

        if (this.model.isSelected && message.recipientId == OWMailbox.userDetails.userId && message.recipientRead == 0){
            OW.trigger('mailbox.mark_message_read', {message: message});
        }

        return this;
    },

    writeHistoryChatMessage: function(message){

        var messageContainer = null;

        if (message.isSystem){
            messageContainer = $('#dialogSysMessagePrototypeBlock').clone();
        }
        else{
            messageContainer = $('#dialogChatMessagePrototypeBlock').clone();
        }

        messageContainer.attr('id', 'messageItem'+message.id);

        var html = '';
        if (message.isSystem){
            html = message.text;

            messageContainer.html( html );
        }
        else
        {
            if (message.attachments.length != 0)
            {
                var i = 0;

                if (message.attachments[i]['type'] == 'image')
                {
                    messageContainer.addClass('ow_dialog_picture_item');
                    $('#dialogMessageText', messageContainer).html( '<a href="'+message.attachments[i]['downloadUrl']+'" target="_blank"><img src="'+message.attachments[i]['downloadUrl']+'" /></a>' );
                }
                else
                {
                    $('.ow_dialog_in_item', messageContainer).addClass('fileattach');

                    var attachment = $('#conversationFileAttachmentBlockPrototype').clone();
                    attachment.removeAttr('id');

                    $('#conversationFileAttachmentFileName', attachment).html( OWMailbox.formatAttachmentFileName(message.attachments[i]['fileName']) );
                    $('#conversationFileAttachmentFileName', attachment).attr('href', message.attachments[i]['downloadUrl']);
                    $('#conversationFileAttachmentFileSize', attachment).html( OWMailbox.formatAttachmentFileSize(message.attachments[i]['fileSize']) );

                    $('.ow_dialog_in_item', messageContainer).html( attachment.html() );
                }
            }
            else
            {
//                html = htmlspecialchars(message.text, 'ENT_QUOTES');
                html = message.text;

                $('#dialogMessageText', messageContainer).html( html );
                $('#dialogMessageText', messageContainer).autolink();
            }
        }

        if ( message.senderId != this.model.opponentId ){
            $('div.ow_dialog_item', messageContainer).addClass('even');
        }
        else{
            $('div.ow_dialog_item', messageContainer).addClass('odd');
        }

        var groupContainer = $('#groupedMessages-'+message.date, this.control);
        if (groupContainer.length == 0)
        {
            groupContainer.prepend(messageContainer);

            var timeBlock = $('#dialogTimeBlockPrototypeBlock').clone();
            timeBlock.attr('id', 'timeBlock'+message.timeStamp);
            $('.ow_time_text', timeBlock).html(message.timeLabel);
            groupContainer.prepend(timeBlock);

            groupContainer = $('#dialogDateCapBlock').clone();
            $('#dialogDateCap', groupContainer).html(message.dateLabel);

            groupContainer.attr('id', 'groupedMessages-'+message.date);
            groupContainer.data({
                date: message.date,
                dateLabel: message.dateLabel
            });

            this.messageListControl.prepend(groupContainer);
        }
        else
        {
            var firstMessageContainer = $('#messageItem'+this.model.firstMessageId, this.control);
            firstMessageContainer.before(messageContainer);
        }

        this.model.firstMessageId = message.id;
    },

    writeHistory: function(message){
        this.writeHistoryChatMessage(message);
    }
};
/*                          End Dialog                                       */
