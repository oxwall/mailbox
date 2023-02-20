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
 * @package ow_plugin.mailbox.classes
 * @since 1.6.1
 * */
class MAILBOX_CLASS_UserField extends InvitationFormElement
{
    public function __construct($name, $id = '')
    {
        parent::__construct($name, $id);
    }

    /**
     * @see FormElement::renderInput()
     *
     * @param array $params
     * @return string
     */
    public function renderInput( $params = null )
    {
        parent::renderInput($params);

        $this->addAttribute('type', 'hidden');
        $this->addAttribute('class', 'userFieldHidden');
        $this->addAttribute('placeholder', OW::getLanguage()->text('mailbox', 'to'));

        $input = new UserFieldRenderable();

        $input->assign('input', UTIL_HtmlTag::generateTag('input', $this->attributes));

        return $input->render();
    }

    public function getElementJs()
    {
        $jsString = "var formElement = new MailboxUserField(" . json_encode($this->getId()) . ", " . json_encode($this->getName()) . ", " . json_encode( $this->invitation ) . ");";

        return $jsString.$this->generateValidatorAndFilterJsCode("formElement");
    }
}

class UserFieldRenderable extends OW_Component
{
    public function __construct()
    {
        $this->setTemplate(OW::getPluginManager()->getPlugin('mailbox')->getCmpViewDir().'user_field.html');

        $defaultAvatarUrl = BOL_AvatarService::getInstance()->getDefaultAvatarUrl();
        $this->assign('defaultAvatarUrl', $defaultAvatarUrl);
    }
}
