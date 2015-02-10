<?php

/**
 * This software is intended for use with Oxwall Free Community Software http://www.oxwall.org/ and is
 * licensed under The BSD license.

 * ---
 * Copyright (c) 2011, Oxwall Foundation
 * All rights reserved.

 * Redistribution and use in source and binary forms, with or without modification, are permitted provided that the
 * following conditions are met:
 *
 *  - Redistributions of source code must retain the above copyright notice, this list of conditions and
 *  the following disclaimer.
 *
 *  - Redistributions in binary form must reproduce the above copyright notice, this list of conditions and
 *  the following disclaimer in the documentation and/or other materials provided with the distribution.
 *
 *  - Neither the name of the Oxwall Foundation nor the names of its contributors may be used to endorse or promote products
 *  derived from this software without specific prior written permission.

 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES,
 * INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR
 * PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,
 * INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO,
 * PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED
 * AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
 * ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 */

/**
 * @author Zarif Safiullin <zaph.work@gmail.com>
 * @package ow_plugin.mailbox.components
 * @since 1.6.1
 * */
class MAILBOX_CMP_ConversationList extends OW_Component
{
    public function __construct($params = array())
    {
        parent::__construct();

        OW::getDocument()->addScript( OW::getPluginManager()->getPlugin('mailbox')->getStaticJsUrl().'conversation_list.js', 'text/javascript', 3008 );

        $defaultAvatarUrl = BOL_AvatarService::getInstance()->getDefaultAvatarUrl();
        $this->assign('defaultAvatarUrl', $defaultAvatarUrl);

        $js = "var conversationListModel = new MAILBOX_ConversationListModel;
";

        if (!empty($params['conversationId']))
        {
            $js .= "conversationListModel.set('activeConvId', {$params['conversationId']});";
            $js .= "conversationListModel.set('pageConvId', {$params['conversationId']});";
        }

        $js .= "OW.Mailbox.conversationListController = new MAILBOX_ConversationListView({model: conversationListModel});";

        OW::getDocument()->addOnloadScript($js, 3009);

        $conversationSearchForm = new Form('conversationSearchForm');
        $search = new MAILBOX_CLASS_SearchField('conversation_search');
        $search->setHasInvitation(true);
        $search->setInvitation( OW::getLanguage()->text('mailbox', 'label_invitation_conversation_search') );

        OW::getLanguage()->addKeyForJs('mailbox', 'label_invitation_conversation_search');

        $conversationSearchForm->addElement($search);
        $this->addForm($conversationSearchForm);

        $modeList = MAILBOX_BOL_ConversationService::getInstance()->getActiveModeList();
        $singleMode = count($modeList) == 1;
        $this->assign('singleMode', $singleMode);
    }
}