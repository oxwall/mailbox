<?php

class MAILBOX_MCMP_ConsoleConversationsPage extends OW_MobileComponent
{
    public function __construct()
    {
        $defaultAvatarUrl = BOL_AvatarService::getInstance()->getDefaultAvatarUrl();
        $this->assign('defaultAvatarUrl', $defaultAvatarUrl);

        OW::getLanguage()->addKeyForJs('mailbox', 'label_invitation_conversation_search');

//        OW::getDocument()->addScript( OW::getPluginManager()->getPlugin('mailbox')->getStaticJsUrl().'mobile_mailbox_sidebar.js' );

        $list = array('list'=>array(
            array('mode'=>'conversations', 'title'=>OW::getLanguage()->text('mailbox', 'conversations'), 'selected'=>true),
            array('mode'=>'userlist', 'title'=>OW::getLanguage()->text('mailbox', 'userlist'), 'selected'=>false)
        ));

        $js = UTIL_JsGenerator::composeJsString('
OWM.mailboxSidebarMenu = new MAILBOX_SidebarMenu({$list});
OWM.mailboxSidebarMenuView = new MAILBOX_SidebarMenuView({model: OWM.mailboxSidebarMenu});

OWM.mailboxConversations = new MAILBOX_Conversations();
OWM.mailboxConversationsView = new MAILBOX_ConversationsView({model: OWM.mailboxConversations});

OWM.mailboxUsers = new MAILBOX_Users();
OWM.mailboxUsersView = new MAILBOX_UsersView({model: OWM.mailboxUsers});
OWM.mailboxSearch = new MAILBOX_Search();

OWM.trigger("mailbox.right_sidebar_loaded");
', array('list'=>$list));

        OW::getDocument()->addOnloadScript($js);
    }
}