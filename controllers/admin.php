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
 * Forum admin action controller
 *
 * @author Egor Bulgakov <egor.bulgakov@gmail.com>
 * @package ow.ow_plugins.forum.controllers
 * @since 1.0
 */
class MAILBOX_CTRL_Admin extends ADMIN_CTRL_Abstract
{

    /**
     * Default action
     */
    public function index()
    {
        $language = OW::getLanguage();

        $configSaveForm = new ConfigSaveForm();
        $this->addForm($configSaveForm);

        $activeModes = MAILBOX_BOL_ConversationService::getInstance()->getActiveModeList();
        if (count($activeModes) > 1)
        {
            $selectedMode = 'mailchat';
        }
        else
        {
            $selectedMode = $activeModes[0];
        }

        $this->assign('selectedMode', $selectedMode);

        $mailModeEnabled = in_array('mail', $activeModes);
        $this->assign('mailModeEnabled', $mailModeEnabled);

        if ( OW::getRequest()->isPost() && $configSaveForm->isValid($_POST) )
        {
            $configSaveForm->process();

            // clear cache
            MAILBOX_BOL_ConversationService::getInstance()->resetAllUsersLastData();

            OW::getFeedback()->info($language->text('mailbox', 'settings_updated'));
            $this->redirect();
        }

        if ( !OW::getRequest()->isAjax() )
        {
            $this->setPageHeading(OW::getLanguage()->text('mailbox', 'admin_config'));
            $this->setPageHeadingIconClass('ow_ic_mail');
        }
    }
}

/**
 * Save Configurations form class
 */
class ConfigSaveForm extends Form
{

    /**
     * Class constructor
     *
     */
    public function __construct()
    {
        parent::__construct('configSaveForm');

        $language = OW::getLanguage();

        $configs = OW::getConfig()->getValues('mailbox');

//        $element = new CheckboxField('enableAttachments');
//        $element->setValue($configs['enable_attachments']);
//        $this->addElement($element);

        $activeModeList = new RadioField('active_mode_list');
        $activeModeList->setLabel($language->text('mailbox', 'settings_label_active_mode_list'));

        $availableModeCollector = new BASE_CLASS_EventCollector('plugin.mailbox.on_available_mode_collect');
        OW::getEventManager()->trigger($availableModeCollector);

        $data = $availableModeCollector->getData();

        if (!empty($data))
        {
            foreach ($data as $modeName)
            {
                $availableModeList[$modeName] = $language->text('mailbox', 'label_btn_'.$modeName);
            }
        }
        else
        {
            $availableModeList = array(
                'mail'=>$language->text('mailbox', 'label_btn_mail'),
                'chat'=>$language->text('mailbox', 'label_btn_chat'),
                'mailchat'=>$language->text('mailbox', 'label_btn_mailchat')
            );
        }

        $activeModeList->setOptions($availableModeList);

        $activeModes = MAILBOX_BOL_ConversationService::getInstance()->getActiveModeList();

        if (in_array('mail', $activeModes) && in_array('chat', $activeModes))
        {
            $activeModeList->setValue('mailchat');
        }
        else
        {
            $activeModeList->setValue($activeModes[0]);
        }

        $activeModeList->setRequired();
        $this->addElement($activeModeList);

        if (in_array('mail', $activeModes))
        {
            $sendMessageInterval = new TextField('send_message_interval');
            $sendMessageInterval->setValue($configs['send_message_interval']);
            $sendMessageInterval->setLabel($language->text('mailbox', 'settings_label_send_message_interval'));
            $this->addElement($sendMessageInterval);
        }

//        $showAllMembers = new CheckboxField('show_all_members');
//        $showAllMembers->setLabel($language->text('mailbox', 'settings_label_show_all_members'));
//        $showAllMembers->setDescription($language->text('mailbox', 'settings_desc_show_all_members'));
//        $showAllMembers->setValue( OW::getConfig()->getValue('mailbox', 'show_all_members') );
//        $this->addElement($showAllMembers);

        // submit
        $submit = new Submit('save');
        $submit->setValue($language->text('base', 'edit_button'));
        $this->addElement($submit);

//        $js = <<<EOT
//$("input[name='active_mode_list']").click(function(){
//    if ($(this).val()=='mail')
//    {
//        $("input[name='show_all_members']").attr('disabled', 'disabled');
//        $("#showAllMembersContainer").hide();
//    }
//    else
//    {
//        $("input[name='show_all_members']").removeAttr('disabled');
//        $("#showAllMembersContainer").show();
//    }
//});
//EOT;
//
//        OW::getDocument()->addOnloadScript($js);
    }

    /**
     * Updates forum plugin configuration
     *
     * @return boolean
     */
    public function process()
    {
        $values = $this->getValues();

        $enableAttachmentsValue = empty($values['enableAttachments']) ? false : (boolean)$values['enableAttachments'];

        $config = OW::getConfig();

        $enableAttachmentsValue = true; //TODO tmp solution, remove this assignment when it will be necessary
        $config->saveConfig('mailbox', 'enable_attachments', $enableAttachmentsValue);
        $config->saveConfig('mailbox', 'show_all_members', 0);

        $send_message_interval = empty($values['send_message_interval']) ? 0 : (int)$values['send_message_interval'];
        $config->saveConfig('mailbox', 'send_message_interval', $send_message_interval);

        $authorization = OW::getAuthorization();
        $groupName = 'mailbox';

        $authorization->deleteAction($groupName, 'read_message');
        $authorization->deleteAction($groupName, 'send_message');
        $authorization->deleteAction($groupName, 'reply_to_message');

        $authorization->deleteAction($groupName, 'read_chat_message');
        $authorization->deleteAction($groupName, 'send_chat_message');
        $authorization->deleteAction($groupName, 'reply_to_chat_message');

        switch($values['active_mode_list'])
        {
            case 'chat':
                $modes = array($values['active_mode_list']);
                $config->saveConfig('mailbox', 'active_modes', json_encode($modes));

                $authorization->addAction($groupName, 'read_chat_message');
                $authorization->addAction($groupName, 'send_chat_message');
                $authorization->addAction($groupName, 'reply_to_chat_message');

                break;

            case 'mail':
                $modes = array($values['active_mode_list']);
                $config->saveConfig('mailbox', 'active_modes', json_encode($modes));

                $authorization->addAction($groupName, 'read_message');
                $authorization->addAction($groupName, 'send_message');
                $authorization->addAction($groupName, 'reply_to_message');

                break;

            case 'mailchat':
                $modes = array('mail', 'chat');
                $config->saveConfig('mailbox', 'active_modes', json_encode($modes));

                $authorization->addAction($groupName, 'read_message');
                $authorization->addAction($groupName, 'send_message');
                $authorization->addAction($groupName, 'reply_to_message');

                $authorization->addAction($groupName, 'read_chat_message');
                $authorization->addAction($groupName, 'send_chat_message');
                $authorization->addAction($groupName, 'reply_to_chat_message');

                break;
        }

        if ($values['active_mode_list'] != 'mail')
        {
            $show_all_members = empty($values['show_all_members']) ? 0 : 1;
            $config->saveConfig('mailbox', 'show_all_members', $show_all_members);
        }

        if (OW::getPluginManager()->isPluginActive('usercredits'))
        {
            USERCREDITS_BOL_CreditsService::getInstance()->deleteActionsByPluginKey('mailbox');
            $credits = new MAILBOX_CLASS_Credits();
            $credits->triggerCreditActionsAdd();
        }

        return array('result' => true);
    }
}
