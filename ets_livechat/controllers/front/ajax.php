<?php
/**
 * Copyright ETS Software Technology Co., Ltd
 *
 * NOTICE OF LICENSE
 *
 * This file is not open source! Each license that you purchased is only available for 1 website only.
 * If you want to use this file on more websites (or projects), you need to purchase additional licenses.
 * You are not allowed to redistribute, resell, lease, license, sub-license or offer our resources to any third party.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future.
 *
 * @author ETS Software Technology Co., Ltd
 * @copyright  ETS Software Technology Co., Ltd
 * @license    Valid for 1 website (or project) for each purchase of license
 */

if (!defined('_PS_VERSION_')) { exit; }

if(!class_exists('LC_Conversation') && file_exists(dirname(__FILE__).'/../../classes/LC_Conversation.php'))
    require_once(dirname(__FILE__).'/../../classes/LC_Conversation.php');
if(!class_exists('LC_Message') && file_exists(dirname(__FILE__).'/../../classes/LC_Message.php'))
    require_once(dirname(__FILE__).'/../../classes/LC_Message.php');
if(!class_exists('LC_Download') && file_exists(dirname(__FILE__).'/../../classes/LC_Download.php'))
    require_once(dirname(__FILE__).'/../../classes/LC_Download.php');
class Ets_livechatAjaxModuleFrontController extends ModuleFrontController
{
    /**
    * @see FrontController::initContent()
    */
    public function init()
    {
        parent::init();
        if(!$this->module->active)
            exit();
        if(Tools::isSubmit('submitSearchProduct'))
        {
            $query = ($q = Tools::getValue('q')) && Validate::isCleanHtml($q) ? $q : false;
            if($query)
                LC_Ticket_process::ajaxProcessSearchProduct($query);
        }
    }
    public function initContent()
    {
        parent::initContent();
        $errors = array();
        $browser_name = Tools::getValue('browser_name');
        if($browser_name && !Validate::isCleanHtml($browser_name))
            $browser_name!='Chrome';
        $token = Tools::getValue('token');
        $token_config = Configuration::getGlobalValue('ETS_LC_FO_TOKEN');
        if (!$token_config || !$token || !hash_equals($token_config, (string) $token))
        {
            die(
                json_encode(
                    array(
                        'token_errors'=> true,
                    )
                )
            );
        }  
        if($conversation = LC_Conversation::getCustomerConversation())
            $conversation->checkAutoEndChat();
        if(Tools::isSubmit('set_chatbox_position'))
        {
            $this->context->cookie->lc_chatbox_top = (float)Tools::getValue('top');
            $this->context->cookie->lc_chatbox_left = (float)Tools::getValue('left');
            $this->context->cookie->write();
            die('updated');
        }
        if(Tools::isSubmit('customer_edit_message') && ($id_message=(int)Tools::getValue('id_message')))
        {
            $message= new LC_Message($id_message);
            if($message->id_employee|| !$message->id|| !(int)Configuration::get('ETS_LC_ENABLE_EDIT_MESSAGE'))
            {
                die(
                    json_encode(
                        array(
                            'error'=> $this->module->l('error','ajax'),
                        ) 
                    )
                );
            }
            else
            {
                $conversation = new LC_Conversation($message->id_conversation);
                $conversation->latest_online = date('Y-m-d H:i:s');
                if($conversation->end_chat && !$errors)
                {
                    if(!$this->module->duplicateConversation($conversation))
                        $errors[] = $this->module->l('Sorry! We are not able to start the conversation at this time','ajax');
                }
                else
                    $conversation->update();
                die(
                    json_encode(
                        array(
                            'error'=> 0,
                            'message'=>$message->message,
                        )
                    )
                    
                );
            }
        }
        if(Tools::isSubmit('customer_delete_message')&& ($id_message=(int)Tools::getValue('id_message')))
        {
            $message= new LC_Message($id_message);
            if($message->id_employee|| !$message->id || !(int)Configuration::get('ETS_LC_ENABLE_DELETE_MESSAGE'))
            {
                die(
                    json_encode(
                        array(
                            'error'=> $this->module->l('error','ajax'),
                        )
                    )
                );
            }
            else
            {
                $conversation = new LC_Conversation($message->id_conversation);
                if($conversation->message_deleted)
                    $conversation->message_deleted .=','.$id_message;
                else
                    $conversation->message_deleted=$id_message;
                $conversation->latest_online = date('Y-m-d H:i:s');
                if($conversation->end_chat && !$errors)
                {
                    if(!$this->module->duplicateConversation($conversation))
                        $errors[] = $this->module->l('Sorry! We are not able to start the conversation at this time','ajax');
                }
                else
                    $conversation->update();
                $message->delete();
                die(
                    json_encode(
                        array(
                            'error'=> 0,
                        )
                    )
                );
            }
        }
        if(Tools::isSubmit('change_sound_conversation'))
        {
            $status = (int)Tools::getValue('status');
            if($conversation = LC_Conversation::getCustomerConversation())
            {
                $conversation->enable_sound = $status;
                $conversation->update();
            }
            Context::getContext()->cookie->enable_sound =$status;
            Context::getContext()->cookie->write();
            die('1');
        }
        if(Tools::isSubmit('load_chat_box'))
        {
            $refresh = (int)Tools::getValue('refresh');
            $conversation = LC_Conversation::getCustomerConversation();
            if($conversation && !$refresh)
            {
                $current_url = Tools::getValue('current_url');
                if(Validate::isCleanHtml($current_url))
                {
                    $conversation->current_url = $current_url;
                    $conversation->update();
                }
            }
            die(json_encode(array(
                'has_conversation' =>(int)$refresh ? 0 : 1,
                'html' => $this->module->displayChatBoxCustomer($refresh),
                'isAdminBusy' => LC_Conversation::isAdminBusy(),
                'wait_support' => $conversation && $conversation->checkWaitSupport() ? LC_Conversation::getTimeWait():false,
            )));
        }
        if(Tools::isSubmit('send_message'))
        {
            $id_message = (int)Tools::getValue('id_message');
            $latestID = (int)Tools::getValue('latestID');
            $current_url = Tools::getValue('current_url');
            $updateCustomerInfo = (int)Tools::getValue('updateCustomerInfo');
            if(!Validate::isCleanHtml($current_url))
                $current_url = '';
            $extraID=0;  
            $openInfo = false;
            $ignoreCaptcha = false;
            if(!LC_Conversation::needCaptcha())
                $ignoreCaptcha = true;
            $isAdminOnline = LC_Conversation::isAdminOnline();
            $message = trim((string) Tools::getValue('message', '')); 
            Configuration::updateValue('ETS_LC_DATE_ACTION_LAST',date('Y-m-d H:i:s'));        
            if(!($conversation = LC_Conversation::getCustomerConversation()) || $updateCustomerInfo && $conversation && (int)Configuration::get('ETS_LC_UPDATE_CONTACT_INFO'))
            {
                if(!$conversation)
                    $conversation = new LC_Conversation();
                if(!$conversation->chatref)
                    $conversation->chatref = 1+ LC_Conversation::getMaxID();
                if($conversation->checkChangeDepartment())
                {
                    $id_departments = (int)Tools::getValue('id_departments');
                    if($conversation->id_departments!= $id_departments)
                    {
                        $conversation->id_employee=0;
                        $conversation->id_employee_wait=0;
                        $conversation->id_departments_wait=0;
                    }
                    $conversation->id_departments = (int)$id_departments;
                    if(!$conversation->id_departments && LC_Conversation::isRequiredField('departments'))
                        $errors[] = $this->module->l('Department is required','ajax');
                }
                if(isset($this->context->customer->id) && (int)$this->context->customer->id)
                {
                    $conversation->customer_name = trim(Tools::ucfirst($this->context->customer->firstname).' '.Tools::ucfirst($this->context->customer->lastname));
                    $conversation->customer_email = $this->context->customer->email;
                    $conversation->customer_phone = ($addresses = $this->context->customer->getAddresses($this->context->language->id)) ? ($addresses[0]['phone'] ? $addresses[0]['phone'] : $addresses[0]['phone_mobile']) : '';
                    
                    if(!$conversation->customer_phone && LC_Conversation::isUsedField('name') && ($phone = trim((string) Tools::getValue('phone', ''))) && Validate::isPhoneNumber($phone))
                        $conversation->customer_phone = $phone;
                    if(!$conversation->customer_phone && LC_Conversation::isRequiredField('phone'))
                        $errors[] = $this->module->l('Please enter a valid phone number','ajax');
                    
                    $conversation->id_customer = (int)$this->context->customer->id;
                }
                else
                {
                    if(LC_Conversation::isUsedField('name'))
                    {
                        $name = trim((string) Tools::getValue('name', ''));
                        if(LC_Conversation::isRequiredField('name') && !$name)
                            $errors[] = $this->module->l('Name is required','ajax');
                        elseif(!Validate::isName($name))
                            $errors[] = $this->module->l('Name is not valid','ajax');
                        else
                            $conversation->customer_name = $name;
                    }
                    if(LC_Conversation::isUsedField('email') || !$isAdminOnline)
                    {
                        $email = trim((string) Tools::getValue('email', ''));
                        if(!$email && (!$isAdminOnline || LC_Conversation::isRequiredField('email')))
                            $errors[] = $this->module->l('Email is required','ajax');
                        elseif($email && !Validate::isEmail($email))
                            $errors[] = $this->module->l('Email is not valid','ajax');
                        elseif(LC_Conversation::isUsedField('email') || !$isAdminOnline)    
                            $conversation->customer_email = $email;
                    }                    
                    if(LC_Conversation::isUsedField('phone'))
                    {
                        $phone = trim((string) Tools::getValue('phone', ''));
                        if(LC_Conversation::isRequiredField('phone') && !$phone)
                            $errors[] = $this->module->l('Phone is required','ajax');
                        elseif(!Validate::isPhoneNumber($phone))
                            $errors[] = $this->module->l('Phone is not valid','ajax');
                        else
                            $conversation->customer_phone = $phone;
                    }
                    $conversation->id_customer = 0;
                }
                if(!$ignoreCaptcha && !Ets_livechat::validCaptcha())
                {
                    $captcha = Tools::getValue('cf-turnstile-response');
                    if($captcha!='')
                        $errors[] = $this->module->l('Security code is not valid','ajax');
                    else
                        $errors[]=$this->module->l('Please enter security code','ajax');
                }                                   
                if(!$errors)
                {
                    $conversation->blocked = 0;
                    $conversation->customer_writing = 0;
                    $conversation->employee_writing = 0;
                    $conversation->latest_online = date('Y-m-d H:i:s');
                    $conversation->latest_ip = Tools::getRemoteAddr();
                    $conversation->browser_name = $browser_name;
                    if (strpos($current_url, '#') !== FALSE) {
                        $current_url = Tools::substr($current_url, 0, strpos($current_url, '#'));
                    }
                    $conversation->current_url = $current_url;
                    $conversation->datetime_added = $conversation->latest_online;
                    $conversation->id_shop = Context::getContext()->shop->id;
                    if($conversation->end_chat)
                    {
                        $conversation->end_chat=0;
                        $conversation->id_employee=0;
                        $conversation->id_employee_wait=0;
                        $conversation->id_departments_wait=0;
                        $conversation->id=0;
                    }
                    if(isset(Context::getContext()->cookie->enable_sound) && !Context::getContext()->cookie->enable_sound)
                        $conversation->enable_sound=0;
                    if(!$conversation || ($conversation && !$conversation->id))
                    {
                        $this->context->cookie->ets_lc_chatbox_status='open';
                        $this->context->cookie->write();
                        $conversation->id_lang = $this->context->language->id;
                        $conversation->date_message_last_customer = date('Y-m-d H:i:s');
                        if(!$conversation->add())
                            $errors[] = $this->module->l('Sorry! We are not able to start the conversation at this time','ajax');
                        else
                        {
                            $this->context->cookie->lc_id_conversation = $conversation->id;
                            $this->context->cookie->write();
                        }
                    }
                    elseif(!$conversation->update())
                        $errors[] = $this->module->l('Sorry! We are not able to update our contact information at this time','ajax');
                }                                    
            }
            elseif($conversation = LC_Conversation::getCustomerConversation())
            {
                if(!$conversation->chatref)
                    $conversation->chatref = 1 + LC_Conversation::getMaxID();
                if($conversation->checkChangeDepartment())
                {
                    $id_departments = (int)Tools::getValue('id_departments');
                    if($conversation->id_departments!= $id_departments)
                    {
                        $conversation->id_employee=0;
                        $conversation->id_employee_wait=0;
                        $conversation->id_departments_wait=0;
                    }
                    $conversation->id_departments = $id_departments;
                    if(!$conversation->id_departments && LC_Conversation::isRequiredField('departments'))
                        $errors[] = $this->module->l('Department is required','ajax');
                    else
                    {
                        if($conversation->end_chat&& !$errors)
                        {
                            if(!$this->module->duplicateConversation($conversation))
                                $errors[] = $this->module->l('Sorry! We are not able to start the conversation at this time','ajax');
                            
                        }
                        else
                            $conversation->update();
                    }
                        
                }
            }
            if(!Configuration::get('ETS_LC_UPDATE_CONTACT_INFO') && $updateCustomerInfo)
                $errors[] = $this->module->l('You are not allowed to update your contact information','ajax');
            if(!$errors && (!$conversation || $conversation && !$conversation->id))
            {
                $this->context->cookie->lc_id_conversation = 0;
                $this->context->cookie->write();
                $errors[] = $this->module->l('This conversation does not exist','ajax');
            }
            if($conversation->end_chat && !$errors)
            {
                if(!$this->module->duplicateConversation($conversation))
                    $errors[] = $this->module->l('Sorry! We are not able to start the conversation at this time','ajax');
            }
            if($errors)
                $openInfo = true;
            if(!$updateCustomerInfo && !($message = trim((string) Tools::getValue('message', ''))) && (!isset($_FILES['message_file']) || !$_FILES['message_file']))
                $errors[] = $this->module->l('Message cannot be empty','ajax');
            elseif(!$updateCustomerInfo && $message && !Validate::isCleanHtml($message,false))
                $errors[] = $this->module->l('Message is not valid','ajax');
            elseif(Tools::strlen($message)> (int)Configuration::get('ETS_LC_MSG_LENGTH'))
                $errors[]= $this->module->l('Message is invalid','ajax');
            if(!(isset($_FILES['message_file']['tmp_name'])&& isset($_FILES['message_file']['name']) && $_FILES['message_file']['name']) && trim(strip_tags($message))=='' && $message!='' && !$errors)
            {
                $errors[]= $this->module->l('Message is invalid','ajax');
            }
            if(!$errors && !$updateCustomerInfo)
            {
                if(!$ignoreCaptcha && !Ets_livechat::validCaptcha())
                {
                    $captcha = Tools::getValue('cf-turnstile-response');
                    if($captcha!='')
                        $errors[] = $this->module->l('Security code is not valid','ajax');
                    else
                        $errors[]=$this->module->l('Please enter security code','ajax');
                }
                if($conversation->blocked && !$conversation->end_chat)
                    $errors[] = $this->module->l('You are temporarily blocked by administrator','ajax');
                if(!$errors)
                {
                    if($id_message)
                    {
                        $msg = new LC_Message($id_message);
                        $msg->datetime_edited = date('Y-m-d H:i:s');
                    }  
                    else
                    {
                        $msg = new LC_Message();
                        $msg->datetime_added = date('Y-m-d H:i:s');
                        $msg->datetime_edited = $msg->datetime_added;
                    }
                    $msg->id_employee = 0;                    
                    $msg->id_conversation = $conversation->id;
                    $msg->message = trim(strip_tags($message));
                    $msg->delivered = 0;
                    $msg->id_product=(int)Tools::getValue('send_product_id');
                    $attachments=array();
                    if(isset($_FILES['message_file']['tmp_name'])&& isset($_FILES['message_file']['name']) && $_FILES['message_file']['name'])
                    {
                        $_FILES['message_file']['name'] = str_replace(array(' ','(',')','!','@','#','+'),'_',$_FILES['message_file']['name']);
                        if(!Validate::isFileName($_FILES['message_file']['name']))
                        {
                            $errors[] = $this->module->l('File name is invalid','ajax');
                        }
                        else
                        {
                            $type = Tools::strtolower(Tools::substr(strrchr($_FILES['message_file']['name'], '.'), 1));
                            if(!in_array($type,$this->module->file_types))
                            {
                                $errors[] = $this->module->l('File upload is invalid','ajax');
                            }
                            else
                            {
                                $fileupload_name = Tools::strtolower(Tools::passwdGen(20, 'NO_NUMERIC'));
                                $max_size = Configuration::get('ETS_LC_MAX_FILE_MS');
                                $file_size = Tools::ps_round($_FILES['message_file']['size']/1048576,2);
                                if($file_size > $max_size && $max_size >0)
                                    $errors[] = $this->module->l('Attachment size exceeds the allowable limit.','ajax');
                                else
                                {
                                    if (!move_uploaded_file($_FILES['message_file']['tmp_name'], _PS_ETS_LIVE_CHAT_UPLOAD_DIR_.$fileupload_name))
                                        $errors[] = $this->module->l('Cannot upload the file','ajax');
                                    else
                                    {
                                        $msg->name_attachment=$_FILES['message_file']['name'];
                                    }
                                }
                                $attachment=array(
                                    'rename' =>  uniqid().Tools::strtolower(Tools::substr($_FILES['message_file']['name'], -5)),
                                    'content' => Tools::file_get_contents($_FILES['message_file']['tmp_name']),
                                    'tmp_name' => $_FILES['message_file']['tmp_name'],
                                    'name' =>$_FILES['message_file']['name'],
                                    'mime' => $_FILES['message_file']['type'],
                                    'error' => $_FILES['message_file']['error'],
                                    'size' => $_FILES['message_file']['size'],
                                );
                                $attachments[]=$attachment;
                            }
                        }
                        
                    }
                    if(!$errors)
                    {
                        if($id_message)
                        {
                            $id_download_old = (int)$msg->type_attachment;
                            if(!$msg->update())
                            {
                                $errors[] = $this->module->l('OPPS! We are not able to send the message at the moment. Please contact webmaster for more information','ajax');
                                if($fileupload_name && file_exists(_PS_ETS_LIVE_CHAT_UPLOAD_DIR_.$fileupload_name))
                                    @unlink(_PS_ETS_LIVE_CHAT_UPLOAD_DIR_.$fileupload_name);
                            }
                            else
                            {
                                if(isset($fileupload_name) && $fileupload_name)
                                {
                                    $download= new LC_Download();
                                    $download->filename=$_FILES['message_file']['tmp_name'];
                                    $download->name = $fileupload_name;
                                    $download->id_message= $msg->id;
                                    $download->id_conversation = $msg->id_conversation;
                                    $download->file_type=$_FILES['message_file']['type'];
                                    $download->file_size=$_FILES['message_file']['size']/1024;
                                    if($download->add())
                                    {
                                        $msg->type_attachment=$download->id;
                                        $msg->update();
                                        if(isset($id_download_old) && $id_download_old)
                                        {
                                            $download_old = new LC_Download($id_download_old);
                                            $download_old->delete();
                                        }    
                                    }elseif(isset($fileupload_name) && $fileupload_name && file_exists(_PS_ETS_LIVE_CHAT_UPLOAD_DIR_.$fileupload_name))
                                        @unlink(_PS_ETS_LIVE_CHAT_UPLOAD_DIR_.$fileupload_name);
                                }
                                if($conversation->message_edited)
                                    $conversation->message_edited .=','.$id_message;
                                else
                                    $conversation->message_edited=$id_message;
                                $conversation->latest_online = date('Y-m-d H:i:s');
                                $conversation->date_message_last = date('Y-m-d H:i:s');
                                $conversation->date_message_last_customer =date('Y-m-d H:i:s');
                                $conversation->browser_name = $browser_name;
                                if (strpos($current_url, '#') !== FALSE) {
                                    $current_url = Tools::substr($current_url, 0, strpos($current_url, '#'));
                                }
                                $conversation->current_url = $current_url;
                                $conversation->datetime_added = $conversation->latest_online;
                                LC_Conversation::sendEmail($conversation->id);
                                $conversation->customer_writing=0;
                                if($conversation->end_chat && !$errors)
                                {
                                    if(!$this->module->duplicateConversation($conversation))
                                        $errors[] = $this->module->l('Sorry! We are not able to start the conversation at this time','ajax');
                                }
                                else
                                    $conversation->update();
                            }
                        }
                        else
                        {               
                            if(!$msg->add())
                            {
                                $errors[] = $this->module->l('OPPS! We are not able to send the message at the moment. Please contact webmaster for more information','ajax');
                                if($fileupload_name && file_exists(_PS_ETS_LIVE_CHAT_UPLOAD_DIR_.$fileupload_name))
                                    @unlink(_PS_ETS_LIVE_CHAT_UPLOAD_DIR_.$fileupload_name);
                            }    
                            else
                            {
                                if(isset($fileupload_name) && $fileupload_name)
                                {
                                    $download= new LC_Download();
                                    $download->filename=$_FILES['message_file']['name'];
                                    $download->name = $fileupload_name;
                                    $download->file_type= $_FILES['message_file']['type'];
                                    $download->file_size=$_FILES['message_file']['size']/1024;
                                    $download->id_message= $msg->id;
                                    $download->id_conversation = $msg->id_conversation;
                                    if($download->add())
                                    {
                                        $msg->type_attachment=$download->id;
                                        $msg->update();
                                    }elseif(isset($fileupload_name) && $fileupload_name && file_exists(_PS_ETS_LIVE_CHAT_UPLOAD_DIR_.$fileupload_name))
                                        @unlink(_PS_ETS_LIVE_CHAT_UPLOAD_DIR_.$fileupload_name);
                                }
                                if((int)Configuration::get('ETS_ENABLE_AUTO_REPLY') && !(int)Configuration::get('ETS_LC_STAFF_ACCEPT') && $isAdminOnline)
                                {

                                    if(!Configuration::get('ETS_FORCE_ONLINE_AUTO_REPLY') || !Ets_livechat::isAdminOnlineNoForce())
                                    {

                                        if(!(int)Configuration::get('ETS_STOP_AUTO_REPLY') || (Configuration::get('ETS_STOP_AUTO_REPLY') && $conversation->replied==0))
                                        {
                                            $totalMesageCustomer = $conversation->getTotalMesageCustomer();
                                            if(($id_auto_message = (int)LC_AutoReplyMessage::getMessageByOrder($totalMesageCustomer)) && ($autoMessage = new LC_AutoReplyMessage($id_auto_message,$this->context->language->id)) && $autoMessage->auto_content)
                                            {
                                                $msg = new LC_Message();
                                                $msg->id_employee = -1;                    
                                                $msg->id_conversation = $conversation->id;
                                                $msg->message = $autoMessage->auto_content;
                                                $msg->delivered = 0;
                                                $msg->datetime_added = date('Y-m-d H:i:s'); 
                                                $msg->datetime_edited = $msg->datetime_added;
                                                $msg->add();
                                                $extraID = $msg->id;
                                            }
                                        }
                                    }
                                }
                                $conversation->latest_online = date('Y-m-d H:i:s');
                                $conversation->date_message_last = date('Y-m-d H:i:s');
                                $conversation->date_message_last_customer =date('Y-m-d H:i:s');
                                $conversation->browser_name = $browser_name;
                                if (strpos($current_url, '#') !== FALSE) {
                                    $current_url = Tools::substr($current_url, 0, strpos($current_url, '#'));
                                }
                                $conversation->current_url = $current_url;
                                $conversation->datetime_added = $conversation->latest_online;
                                LC_Conversation::sendEmail($conversation->id,isset($attachments)?$attachments:array());
                                $conversation->archive=0;
                                $conversation->customer_writing=0;
                                if($conversation->end_chat && !$errors)
                                {
                                    if(!$this->module->duplicateConversation($conversation))
                                        $errors[] = $this->module->l('Sorry! We are not able to start the conversation at this time','ajax');
                                }
                                else
                                    $conversation->update();
                            }
                        }
                    }
                        
                }
            }
            $isEmployeeSeen = $conversation? LC_Conversation::isEmployeeSeen($conversation->id):0;
            $isEmployeeDelivered = $conversation? LC_Conversation::isEmployeeDelivered($conversation->id):0;
            $isEmployeeWriting = $conversation? LC_Conversation::isEmployeeWriting($conversation->id):0;   
            $isEmployeeSent=$conversation? LC_Conversation::isEmployeeSent($conversation->id):0;                
            $updateCustomerInfo = (int)Tools::getValue('updateCustomerInfo');
            die(json_encode(array(
                    'error' => $errors ? $this->module->displayError($errors) : false,
                    'isAdminBusy' => LC_Conversation::isAdminBusy(),
                    'wait_support' => $conversation && $conversation->checkWaitSupport() ? LC_Conversation::getTimeWait():false,
                    'messages' => !$errors && $conversation ? $conversation->getMessages((int)$latestID,0,'DESC',$extraID) : array(),
                    'openInfo' => $openInfo,
                    'message_edited' => $id_message ? LC_Message::getMessage($id_message):'',
                    'id_conversation' => $conversation ? $conversation->id : 0,
                    'isAdminOnline' => $isAdminOnline,
                    'lastMessageOfEmployee' => LC_Conversation::getLastMessageOfEmployee($conversation ? $conversation->id : 0),
                    'isEmployeeSeen'=>$isEmployeeSeen&& in_array('seen',explode(',',Configuration::get('ETS_LC_DISPLAY_MESSAGE_STATUSES'))),
                    'isEmployeeDelivered'=>$isEmployeeDelivered && in_array('delevered',explode(',',Configuration::get('ETS_LC_DISPLAY_MESSAGE_STATUSES'))),
                    'isEmployeeWriting'=>$isEmployeeWriting && in_array('writing',explode(',',Configuration::get('ETS_LC_DISPLAY_MESSAGE_STATUSES'))),
                    'isEmployeeSent'=> $isEmployeeSent && in_array('sent',explode(',',Configuration::get('ETS_LC_DISPLAY_MESSAGE_STATUSES'))),
                    'updateContactInfo' => $updateCustomerInfo ? true : false,
                    'thankyouMsg' => !$isAdminOnline && ($thankyou = Configuration::get('ETS_LC_TEXT_OFFLINE_THANKYOU')) ? $thankyou : false,
                    'captcha' => LC_Conversation::needCaptcha() ? $this->context->link->getModuleLink('ets_livechat','captcha',array('rand' => Tools::substr(sha1(mt_rand()), 17, 6))) : false,
            )));             
        }
        if(Tools::isSubmit('set_chatbox_status'))
        {
            $status = Tools::getValue('status');
            $this->context->cookie->ets_lc_chatbox_status = ($status=='open' ? 'open' : 'closed');
            $this->context->cookie->lc_chatbox_top = '';
            $this->context->cookie->lc_chatbox_left ='';
            $this->context->cookie->write();
            die(json_encode(array('success' => $this->module->l('Chat box status updated','ajax').': '.$this->context->cookie->ets_lc_chatbox_status)));
        }
        if(Tools::isSubmit('set_rating') && $rating=(int)Tools::getValue('rating'))
        {
            if($rating < 1)
                $rating=1;
            if($rating > 5)
                $rating=5;
            $conversation = LC_Conversation::getCustomerConversation();
            if($conversation && Configuration::get('ETS_LC_DISPLAY_RATING'))
            {
                $conversation->latest_online = date('Y-m-d H:i:s');
                $conversation->latest_ip = Tools::getRemoteAddr();
                $conversation->browser_name = $browser_name;
                $current_url = Tools::getValue('current_url');
                if (strpos($current_url, '#') !== FALSE) {
                    $current_url = Tools::substr($current_url, 0, strpos($current_url, '#'));
                }
                if(Validate::isCleanHtml($current_url))
                    $conversation->current_url = $current_url;
                $conversation->rating = $rating;
                $conversation->update();
                die($this->module->l('Rated','ajax'));
            }
        }
        if(Tools::isSubmit('customer_end_chat'))
        {
            $conversation = LC_Conversation::getCustomerConversation();
            if($conversation)
            {
                $conversation->latest_online = date('Y-m-d H:i:s');
                $conversation->latest_ip = Tools::getRemoteAddr();
                $conversation->browser_name = $browser_name;
                $conversation->end_chat=-1;
                $conversation->update();
                die(json_encode(
                    array(
                        'suss'=>'end_chat',
                    )
                ));
            }
        }
        if(Tools::isSubmit('getOldMessages'))
        {
            $conversation = LC_Conversation::getCustomerConversation();
            $firstId = (int)Tools::getValue('firstId');
            $messages=LC_Message::getOldMessages($conversation->id,$firstId);
            if($conversation)
            {
                die(json_encode(
                   array(
                        'messages' => $messages,
                        'loaded'=> count($messages)<(int)Configuration::get('ETS_LC_MSG_COUNT')?1:0,
                        'firstId' => $messages?$messages[0]['id_message']:0,
                   ) 
                ));
            }
        }
        die($this->module->l('Access denied','ajax'));
    }
}
