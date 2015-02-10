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
 * @author Egor Bulgakov <egor.bulgakov@gmail.com>
 * @package ow_plugins.mailbox.classes
 * @since 1.0
 */
class MAILBOX_CLASS_Credits
{
    private $actions;
    private $authActions = array();

    public function __construct()
    {
        $mailboxEvent = new OW_Event('mailbox.admin.add_auth_labels');
        OW::getEventManager()->trigger($mailboxEvent);
        $data = $mailboxEvent->getData();
        if (!empty($data))
        {
            $actionLabels = $data['actions'];
            $actionNames = array_keys($actionLabels);
            foreach ($actionNames as $actionName)
            {
                $this->actions[] = array('pluginKey' => 'mailbox', 'action' => $actionName, 'amount' => 0);
                $this->authActions[$actionName] = $actionName;
            }
        }
        else
        {
            $activeModes = array('mail', 'chat');

            if (in_array('mail', $activeModes))
            {
                $this->actions[] = array('pluginKey' => 'mailbox', 'action' => 'send_message', 'amount' => 0);
                $this->actions[] = array('pluginKey' => 'mailbox', 'action' => 'read_message', 'amount' => 0);
                $this->actions[] = array('pluginKey' => 'mailbox', 'action' => 'reply_to_message', 'amount' => 0);

                $this->authActions['send_message'] = 'send_message';
                $this->authActions['read_message'] = 'read_message';
                $this->authActions['reply_to_message'] = 'reply_to_message';
            }

            if (in_array('chat', $activeModes))
            {
                $this->actions[] = array('pluginKey' => 'mailbox', 'action' => 'send_chat_message', 'amount' => 0);
                $this->actions[] = array('pluginKey' => 'mailbox', 'action' => 'read_chat_message', 'amount' => 0);
                $this->actions[] = array('pluginKey' => 'mailbox', 'action' => 'reply_to_chat_message', 'amount' => 0);

                $this->authActions['send_chat_message'] = 'send_chat_message';
                $this->authActions['read_chat_message'] = 'read_chat_message';
                $this->authActions['reply_to_chat_message'] = 'reply_to_chat_message';
            }
        }
    }

    public function bindCreditActionsCollect( BASE_CLASS_EventCollector $e )
    {
        foreach ( $this->actions as $action )
        {
            $e->add($action);
        }
    }

    public function triggerCreditActionsAdd()
    {
        $e = new BASE_CLASS_EventCollector('usercredits.action_add');

        foreach ( $this->actions as $action )
        {
            $e->add($action);
        }

        OW::getEventManager()->trigger($e);
    }
}