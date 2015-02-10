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
 * @author Podyachev Evgeny <joker.OW2@gmail.com>
 * @package ow_plugin.mailbox.components
 * @since 1.0
 * */

class MAILBOX_CMP_ConsoleMailbox extends BASE_CMP_ConsoleDropdownClick
{
    protected $counter = array(
        'number' => 0,
        'active' => false
    );

    /**
     *
     * @var BASE_CMP_ConsoleList
     */
    protected $list;

    public function __construct()
    {
        parent::__construct( OW::getLanguage()->text('mailbox', 'messages_console_title'), 'mailbox' );

        $template = OW::getPluginManager()->getPlugin('BASE')->getCmpViewDir() . 'console_dropdown_list.html';
        $this->setTemplate($template);

        $this->list = new MAILBOX_CMP_ConsoleList($this->getKey());
        $this->addClass('ow_mailbox_items_list');

        $modeList = MAILBOX_BOL_ConversationService::getInstance()->getActiveModeList();
        $singleMode = count($modeList) == 1;
        if ($singleMode)
        {
            $this->addClass('ow_mailbox_single_mode');
        }

        $toolbar = new MAILBOX_CMP_Toolbar();
        OW::getDocument()->appendBody($toolbar->render());
    }

    protected function initJs()
    {
        $js = UTIL_JsGenerator::newInstance();
        $js->addScript('OW.Console.addItem(new OW_ConsoleDropdownList({$uniqId}, {$contentIniqId}), {$key});', array(
            'uniqId' => $this->consoleItem->getUniqId(),
            'key' => $this->getKey(),
            'contentIniqId' => $this->consoleItem->getContentUniqId()
        ));
        OW::getDocument()->addOnloadScript($js);
        $this->list->initJs();

        $js = UTIL_JsGenerator::newInstance();

        $js->addScript(
            'OW.MailboxConsole = new OW_MailboxConsole({$key}, {$params});',
            array(
                'key' => $this->getKey(),
                'params' => array(
                    'issetMails' => (boolean) MAILBOX_BOL_ConversationService::getInstance()->getInboxConversationList(OW::getUser()->getId(), 0, 1)
                )
        ));

        OW::getDocument()->addOnloadScript($js);

        return $this->consoleItem->getUniqId();
    }

    public function setCounter( $number, $active = true )
    {
        $this->counter['number'] = $number;
        $this->counter['active'] = $active;
    }

    public function onBeforeRender()
    {
        parent::onBeforeRender();

        $this->assign('counter', $this->counter);
        $this->setContent($this->list->render());
    }
}