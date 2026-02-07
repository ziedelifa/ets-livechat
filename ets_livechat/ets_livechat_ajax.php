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

// if (!defined('_PS_VERSION_')) { exit; }

if (!defined('_PS_ADMIN_DIR_')) {
    define('_PS_ADMIN_DIR_', getcwd());
}
include(dirname(__FILE__).'/../../config/config.inc.php');
include(dirname(__FILE__).'/ajax.init.php');
$context = Context::getContext();
/** @var Ets_livechat $ets_livechat */
$ets_livechat = Module::getInstanceByName('ets_livechat');
if($context->employee->id && $context->employee->isLoggedBack() && ($token = Tools::getValue('token')) && $token==Tools::getAdminTokenLite('AdminModules'))
{
    if($ets_livechat->all_shop && $ets_livechat->shops)
    {
        foreach($ets_livechat->shops as $shop)
        {
            LC_Conversation::updateAdminOnline($shop['id_shop']);
        }
    }
    LC_Conversation::updateAdminOnline();
    if(Tools::isSubmit('change_conversation_to_ticket') && ($id_conversation=(int)Tools::getValue('id_conversation')) )
    {
        $form = new LC_Ticket_Form(1,$context->language->id);
        $conversation= new LC_Conversation($id_conversation);
        $fieldValues = Tools::getValue('fields');
        $department = (int)Tools::getValue('ticket_id_departments')?: 0;
        $orderRef = Tools::isSubmit('order_ref') ? Tools::getValue('order_ref') : false;
        $id_product = (int)Tools::getValue('id_product')?: 0;
        $id_employee = (int)Tools::getValue('ticket_id_employee') ?: 0;
        $id_customer = (int)Tools::getValue('display_customer') ? $conversation->id_customer :0;
        $status = Tools::getValue('ticket_status');
        $priority = (int)Tools::getValue('priority');
        $fieldValues['field_captcha'] = ($field_captcha = Tools::getValue('field_captcha')) && Validate::isCleanHtml($field_captcha) ? $field_captcha:'';
        $fieldValues['g_recaptcha_response'] = ($g_recaptcha_response = Tools::getValue('g-recaptcha-response')) && Validate::isCleanHtml($g_recaptcha_response) ? $g_recaptcha_response:'';
        $res = LC_Ticket::getInstance()->addTicket(array(
            'id_departments' => $department,
            'order_ref' => $orderRef && Validate::isCleanHtml($orderRef) ? $orderRef:'',
            'id_product' => $id_product,
            'status' => Validate::isCleanHtml($status) ? $status:'open',
            'priority' => $priority ? :'',
            'fields' => $fieldValues,
            'id_employee' => $id_employee,
        ),$form,$id_customer);
        if($res['errors'])
        {
            die(
                json_encode(
                    array(
                        'errors' =>$ets_livechat->displayError($res['errors']),
                    )
                )
            );
        }
        else
        {
            $conversation->id_ticket = $res['ticket']->id;
            $conversation->update();
            $messages = Db::getInstance()->executeS('SELECT * FROM `'._DB_PREFIX_.'ets_livechat_message` WHERE id_conversation='.(int)$id_conversation);
            if($messages)
            {
                foreach($messages as $message)
                {
                    $note = new LC_Note();
                    $note->id_message= $res['ticket']->id;
                    $note->id_employee= $message['id_employee'];
                    $note->note = $message['message'];
                    $note->file_name = $message['name_attachment'];
                    $note->date_add = $message['datetime_added'];
                    $note->add();
                    if($message['type_attachment'] && $note->id && $download = new LC_Download($message['type_attachment']))
                    {
                        unset($download->id);
                        $download->id_message=0;
                        $download->id_note= $note->id;
                        Tools::copy(_PS_ETS_LIVE_CHAT_UPLOAD_DIR_.$download->filename,_PS_ETS_LIVE_CHAT_UPLOAD_DIR_.'ticket-'.$download->filename);
                        $download->filename = 'ticket-'.$download->filename;
                        $download->add();
                        $note->id_download= $download->id;
                        $note->update();
                    }
                }
            }
            die(
                json_encode(
                    array(
                        'success'=> $res['success'],
                        'id_ticket' => $res['ticket']->id,
                        'link_ticket'=> $ets_livechat->getAdminLink('AdminLiveChatTickets').'&viewticket&id_ticket='.$res['ticket']->id,
                        'subject_ticket' => $res['ticket']->subject,
                    )
                )
            );
        }
    }
    if(($action = Tools::getValue('action')) && $action=='updatePositionForm')
    {
        $ticket_form = Tools::getValue('ticket_form');
        if($ticket_form && LC_Tools::validateArray($ticket_form,'isInt'))
        {
            foreach($ticket_form as $key=> $id_form)
            {
                $position = $key+1;
                Db::getInstance()->execute('Update `'._DB_PREFIX_.'ets_livechat_ticket_form` set sort_order="'.(int)$position.'" WHERE id_form='.(int)$id_form);
            }
            $ets_livechat->updateLastAction();
            die(
                json_encode(
                    array(
                        'success'=>$ets_livechat->l('Sort order updated','ets_livechat_ajax')
                    )
                )
            );
        }
    }
    if(($action = Tools::getValue('action')) && $action=='updatePositionDepartments')
    {
        $departments= Tools::getValue('departments');
        if($departments && LC_Tools::validateArray($departments,'isInt'))
        {
            foreach($departments as $key=>$id_departments)
            {
                $position=$key+1;
                Db::getInstance()->execute('UPDATE `'._DB_PREFIX_.'ets_livechat_departments` SET sort_order="'.(int)$position.'" WHERE id_departments="'.(int)$id_departments.'"');
            }
            $ets_livechat->updateLastAction();
            die(
                json_encode(
                    array(
                        'success'=>$ets_livechat->l('Sort order updated','ets_livechat_ajax')
                    )
                )
            );
        }
    }
    if(Tools::isSubmit('getTicketNoReaded'))
    {
        $count = Db::getInstance()->getValue('SELECT COUNT(*) FROM `'._DB_PREFIX_.'ets_livechat_ticket_form_message` WHERE readed=0');
        die(json_encode(
            array(
                'count_ticket' => $count,
            )
        ));
    }
    if(Tools::isSubmit('getmessage'))
    {
        if(($query = Tools::getValue('message')) && Validate::isCleanHtml($query))
        {
            $messages = Db::getInstance()->executeS('SELECT * FROM `'._DB_PREFIX_.'ets_livechat_pre_made_message` WHERE LOWER(short_code) like "'.pSQL($query).'%"');
            if($messages)
            {
                die(
                    json_encode(
                        array(
                            'messages'=>$messages,
                        )
                    )  
                );
            }
        }
        die(
                json_encode(
                    array(
                        'error'=>'null',
                    )
                )  
        );

    }
    
    if(Tools::isSubmit('set_chatbox_position'))
    {
        $context->cookie->lc_chatbox_top = (float)Tools::getValue('top');
        $context->cookie->lc_chatbox_left = (float)Tools::getValue('left');
        $context->cookie->write();
        LC_Conversation::updateAdminOnline();
        die('updated'); 
    }
    if(Tools::isSubmit('employee_edit_message')&& ($id_message=(int)Tools::getValue('id_message')))
    {
        $ets_livechat->updateLastAction();
        $message= new LC_Message($id_message);
        if(!$message->id_employee|| !$message->id || !(int)Configuration::get('ETS_LC_ENABLE_EDIT_MESSAGE'))
        {
            die(
                json_encode(
                    array(
                        'error'=> $ets_livechat->l('error','ets_livechat_ajax'),
                    )
                )
            );
        }
        else
        {
            $conversation = new LC_Conversation($message->id_conversation);
            $conversation->latest_online = date('Y-m-d H:i:s');
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
    if(Tools::isSubmit('employee_delete_message')&& ($id_message=(int)Tools::getValue('id_message')))
    {
        $ets_livechat->updateLastAction();
        $message= new LC_Message($id_message);
        if(!$message->id_employee|| !$message->id || !(int)Configuration::get('ETS_LC_ENABLE_DELETE_MESSAGE'))
        {
            die(
                json_encode(
                    array(
                        'error'=> $ets_livechat->l('error','ets_livechat_ajax'),
                    )
                )
            );
        }
        else
        {
            $conversation = new LC_Conversation($message->id_conversation);
            if($conversation->employee_message_deleted)
                $conversation->employee_message_deleted .=','.$id_message;
            else
                $conversation->employee_message_deleted=$id_message;
            $conversation->latest_online = date('Y-m-d H:i:s');
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
   if(Tools::isSubmit('close_conversation_chatbox') && ($id_conversation =(int)Tools::getValue('id_conversation')))
   {
        $ets_livechat->updateLastAction();
        $conversation_opened = json_decode($context->cookie->converation_opened,true);
        if (($key = array_search($id_conversation, $conversation_opened)) !== false) {
            unset($conversation_opened[$key]);
            $context->cookie->converation_opened = json_encode($conversation_opened);
            $context->cookie->write();
        } 
        $conversation_hided = json_decode($context->cookie->converation_hided,true);
        if ($conversation_hided && ($key = array_search($id_conversation, $conversation_hided)) !== false) {
            unset($conversation_hided[$key]);
            $context->cookie->converation_hided = json_encode($conversation_hided);
            $context->cookie->write();
        } 
   }
   if(Tools::isSubmit('hide_conversation_chatbox') && ($id_conversation =(int)Tools::getValue('id_conversation')))
   {
        $ets_livechat->updateLastAction();
        $conversation_hided = json_decode($context->cookie->converation_hided,true);
        $status = Tools::getValue('status');
        if($status=='open')
        {
            if (($key = array_search($id_conversation, $conversation_hided)) !== false) {
                unset($conversation_hided[$key]);
                $context->cookie->converation_hided = json_encode($conversation_hided);
                $context->cookie->write();
            } 
        }
        else
        {
            if (!$conversation_hided || !in_array($id_conversation, $conversation_hided)){
                $conversation_hided[]=$id_conversation;
                $context->cookie->converation_hided = json_encode($conversation_hided);
                $context->cookie->write();
            }
        }
   }
   if(Tools::isSubmit('submit_clear_message') && ($ETS_CLEAR_MESSAGE = Tools::getValue('ETS_CLEAR_MESSAGE')))
   {
        $where ='';
        if(!$ets_livechat->all_shop || !$ets_livechat->shops)
        {
            $where .=' AND id_conversation IN (SELECT id_conversation FROM `'._DB_PREFIX_.'ets_livechat_conversation` WHERE id_shop ="'.(int)Context::getContext()->shop->id.'")';
        }
        $ets_livechat->updateLastAction();   
        switch ($ETS_CLEAR_MESSAGE) {
            case '1_week':
                $sql ='DELETE  FROM `'._DB_PREFIX_.'ets_livechat_message` WHERE datetime_added <"'.pSQL(date('Y-m-d',strtotime('-1 WEEK'))).'"'.(string)$where;
                Db::getInstance()->execute($sql);
                break;
            case '1_month_ago':
                $sql = 'DELETE  FROM `'._DB_PREFIX_.'ets_livechat_message` WHERE datetime_added <"'.pSQL(date('Y-m-d',strtotime('-1 MONTH'))).'"'.(string)$where;
                Db::getInstance()->execute($sql);
                break;
            case '6_month_ago':
                $sql = 'DELETE * FROM `'._DB_PREFIX_.'ets_livechat_message` WHERE datetime_added <"'.pSQL(date('Y-m-d',strtotime('-6 MONTH'))).'"'.(string)$where;
                Db::getInstance()->execute($sql);
                break;
            case '1_year_ago':
                $sql = 'DELETE * FROM `'._DB_PREFIX_.'ets_livechat_message` WHERE datetime_added <"'.pSQL(date('Y-m-d',strtotime('-1 YEAR'))).'"'.(string)$where;
                Db::getInstance()->execute($sql);
                break;
            case 'everything':
                $sql = 'DELETE  FROM `'._DB_PREFIX_.'ets_livechat_message` WHERE 1'.(string)$where;
                Db::getInstance()->execute($sql);
                break;
        }
        $sql = 'SELECT * FROM `'._DB_PREFIX_.'ets_livechat_conversation` WHERE id_conversation NOT IN (SELECT id_conversation FROM `'._DB_PREFIX_.'ets_livechat_message`)';
        $conversations = Db::getInstance()->executeS($sql);
        $ids_conversation ='';
        if($conversations)
        {
            foreach($conversations as $conversation)
            {
                $ids_conversation .= ','.$conversation['id_conversation']; 
                Db::getInstance()->execute('DELETE FROM `'._DB_PREFIX_.'ets_livechat_conversation` WHERE id_conversation='.(int)$conversation['id_conversation']);
            }
        }
        die(json_encode(
            array(
                'ids_conversation'=> trim($ids_conversation,','),
            )
        ));
   }
   if(Tools::isSubmit('end_chat_conversation') && ($id_conversation=(int)Tools::getValue('id_conversation')) && LC_Conversation::checkExistConversation($id_conversation))
   {
        $ets_livechat->updateLastAction();
        if(!LC_Conversation::checkConversationEmployee($id_conversation,$context->employee->id) || LC_Conversation::checkWaitAccept($id_conversation))
        {
            die(json_encode(array(
                'id_conversation' => $id_conversation,
                'checkDepartment' => $ets_livechat->l('You\'re not allowed to access this conversation. It has been changed to another department','ets_livechat_ajax'),
            )));   
        }
        Db::getInstance()->execute('UPDATE `'._DB_PREFIX_.'ets_livechat_conversation` SET end_chat="'.(int)$context->employee->id.'" WHERE id_conversation="'.(int)$id_conversation.'"');
        die('1');
   }
   if(Tools::isSubmit('delete_pre_made_message') && ($id_pre_made_message =(int)Tools::getValue('id_pre_made_message')))
   {
        $ets_livechat->updateLastAction();
        Db::getInstance()->execute('DELETE FROM `'._DB_PREFIX_.'ets_livechat_pre_made_message` WHERE id_pre_made_message='.(int)$id_pre_made_message);
        die(
            json_encode(
                array(
                    'success' => $ets_livechat->l('Deleted pre-made message successfully','ets_livechat_ajax')
                )
            )
        );
   }
   if(Tools::isSubmit('delete_departments') && ($id_departments= (int)Tools::getValue('id_departments')))
   {
        $ets_livechat->updateLastAction();
        $department= new LC_Departments($id_departments);
        $department->delete();
        die(
            json_encode(
                array(
                    'success' => $ets_livechat->l('Deleted successfully','ets_livechat_ajax')
                )
            )
        );
   }
   if(Tools::isSubmit('load_made_messages'))
   {
        die(
            json_encode(
                array(
                    'html'=> $ets_livechat->displayListPreMadeMessages(),
                )
            )  
        );
   }
   if(Tools::isSubmit('get_pre_made_message') && ($id_pre_made_message=(int)Tools::getValue('id_pre_made_message')))
   {
        $message = Db::getInstance()->getRow('SELECT * FROM `'._DB_PREFIX_.'ets_livechat_pre_made_message` WHERE id_pre_made_message='.(int)$id_pre_made_message);
        die(json_encode($message));
   }
   
   if(Tools::isSubmit('submit_pre_made_message'))
   {
        $ets_livechat->updateLastAction();
        $ets_livechat->_postPreMadeMessage();
   }
   if(Tools::isSubmit('submit_departments'))
   {
        $ets_livechat->updateLastAction();
        $ets_livechat->_postDepartments();
   }
   if(Tools::isSubmit('get_auto_reply_info') && ($id_auto_msg=(int)Tools::getValue('id_auto_msg')))
   {
        $message = Db::getInstance()->getRow('SELECT * FROM `'._DB_PREFIX_.'ets_livechat_auto_msg` WHERE id_auto_msg ="'.(int)$id_auto_msg.'"');
        if($message)
        {
            $message['content'] = Db::getInstance()->executeS('SELECT * FROM `'._DB_PREFIX_.'ets_livechat_auto_msg_lang` WHERE id_auto_msg ="'.(int)$id_auto_msg.'"');
        }
        die(json_encode($message));
   }
   if(Tools::isSubmit('delete_auto_reply') && ($id_auto_msg =(int)Tools::getValue('id_auto_msg')) && ($autoMessage = new LC_AutoReplyMessage($id_auto_msg)) && $autoMessage->delete())
   {
        die(
            json_encode(
                array(
                    'success' => $ets_livechat->l('Deleted auto message successfully','ets_livechat_ajax'),
                )  
            )
        );
   }
   
   if(Tools::isSubmit('submit_auto_reply'))
   {
        $ets_livechat->updateLastAction();
        $ets_livechat->_postAutoReply();
   }    
   if(Tools::isSubmit('getOldMessages')&& ($id_conversation = (int)Tools::getValue('id_conversation')) && LC_Conversation::checkExistConversation($id_conversation))
   {
       $firstId = (int)Tools::getValue('firstId');
       $messages=LC_Message::getOldMessages($id_conversation,$firstId);
       die(json_encode(
           array(
                'messages' => $messages,
                'loaded'=> count($messages)<(int)Configuration::get('ETS_LC_MSG_COUNT')?1:0,
                'firstId' => $messages?$messages[0]['id_message']:0,
           ) 
       ));    
   } 
   if(Tools::isSubmit('change_status_display_admin'))
   {
        $ets_livechat->updateLastAction();
        $status = (int)Tools::getValue('status');
        Configuration::updateValue('ETS_CONVERSATION_DISPLAY_ADMIN',(int)$status);
   }
   if(Tools::isSubmit('change_status_employee') && ($change_status_employee=Tools::getValue('change_status_employee')))
   {
    
        $ets_livechat->updateLastAction();
        if($change_status_employee=='foce_online')
        {
            if($context->employee->id_profile==1)
            {
                Configuration::updateValue('ETS_LC_FORCE_ONLINE',1);
                $employees = Db::getInstance()->executeS('SELECT * FROM `'._DB_PREFIX_.'employee`');
                if($employees)
                {
                    foreach($employees as $employee)
                    {
                        if(!LC_Departments::getStatusEmployee($employee['id_employee']))
                        {
                            Db::getInstance()->execute('INSERT INTO `'._DB_PREFIX_.'ets_livechat_employee_status`(id_employee,id_shop,status) VALUES("'.(int)$employee['id_employee'].'","'.(int)$context->shop->id.'","online")');
                        }
                        else
                            Db::getInstance()->execute('UPDATE `'._DB_PREFIX_.'ets_livechat_employee_status` SET status="online" WHERE id_employee="'.(int)$employee['id_employee'].'" AND id_shop='.(int)$context->shop->id);
                    }
                }
            }
            
        }
        else
        {
            if(!Configuration::get('ETS_LC_FORCE_ONLINE') || $context->employee->id_profile==1)
            {
                Configuration::updateValue('ETS_LC_FORCE_ONLINE',0);
                if(!LC_Departments::getStatusEmployee($context->employee->id))
                {
                    Db::getInstance()->execute('INSERT INTO `'._DB_PREFIX_.'ets_livechat_employee_status`(id_employee,id_shop,status) VALUES("'.(int)$context->employee->id.'","'.(int)$context->shop->id.'","'.pSQL($change_status_employee).'")');
                }
                else
                    Db::getInstance()->execute('UPDATE `'._DB_PREFIX_.'ets_livechat_employee_status` SET status="'.pSQL($change_status_employee).'" WHERE id_employee="'.(int)$context->employee->id.'" AND id_shop='.(int)$context->shop->id);
            }
            
        }
        die('1');
   }
   if(Tools::isSubmit('delete_conversation') && ($id_conversation=(int)Tools::getValue('id_conversation')) && LC_Conversation::checkExistConversation($id_conversation))
   {
        $ets_livechat->updateLastAction();
        if(!LC_Conversation::checkConversationEmployee($id_conversation,$context->employee->id))
        {
            die(json_encode(array(
                'id_conversation' => $id_conversation,
                'checkDepartment' => $ets_livechat->l('You\'re not allowed to access this conversation. It has been changed to another department','ets_livechat_ajax'),
            )));   
        }
        Db::getInstance()->execute('DELETE FROM `'._DB_PREFIX_.'ets_livechat_conversation` WHERE id_conversation='.(int)$id_conversation);
        Db::getInstance()->execute('DELETE FROM `'._DB_PREFIX_.'ets_livechat_message` WHERE id_conversation='.(int)$id_conversation);
        $conversation_opened = json_decode($context->cookie->converation_opened,true);
        if (($key = array_search($id_conversation, $conversation_opened)) !== false) {
            unset($conversation_opened[$key]);
            $context->cookie->converation_opened = json_encode($conversation_opened);
            $context->cookie->write();
        }
        die('1');
   }
   if(Tools::isSubmit('load_more_customer_chat'))
   {
        $ets_livechat->_getMoreCustomer();
   }
   if(Tools::isSubmit('load_list_customer_chat'))
   {
        $status= LC_Departments::getStatusEmployee($context->employee->id);
        $auto = Tools::getValue('auto') ? true :false;
        if($auto)
        {
            $customer_all = (int)Tools::getValue('customer_all');
            $customer_archive = (int)Tools::getValue('customer_archive');
            $customer_search = Tools::getValue('customer_search');
            $count_conversation = (int)Tools::getValue('count_conversation',20);
            if($reload_list= $ets_livechat->checkNewMessage())
            {
                die(json_encode(array(
                    'status_employee' => Configuration::get('ETS_LC_FORCE_ONLINE') ? 'foce_online' : $status ,
                    'html' => $ets_livechat->displayListCustomerChat(),
                    'totalMessageNoSeen'=>LC_Conversation::getTotalMessageNoSeen(),
                    'reload_list'=>$reload_list,
                    'last_message'=> $reload_list && $reload_list==1? LC_Message::getMessage():array(),
                    'conversations' =>$reload_list==1 ? LC_Conversation::getListConversations($customer_all,$customer_archive,Validate::isCleanHtml($customer_search) ? $customer_search:'','',$count_conversation):'',
                    'level_request'=> LC_Conversation::getLevelRequestAdmin(),
                )));    
            }
            else{
                die(json_encode(array(
                    'reload_list'=>0,
                    'status_employee' => Configuration::get('ETS_LC_FORCE_ONLINE') ? 'foce_online' : $status,
                    'level_request'=> LC_Conversation::getLevelRequestAdmin(),
                    'totalMessageNoSeen'=>LC_Conversation::getTotalMessageNoSeen(),
                    'conversations' =>LC_Conversation::getListConversations($customer_all,$customer_archive,Validate::isCleanHtml($customer_search) ? $customer_search:'','',$count_conversation),
                )));
            }
        }
        else{
            die(json_encode(array(
                'html' => $ets_livechat->displayListCustomerChat(),
                'totalMessageNoSeen'=>LC_Conversation::getTotalMessageNoSeen(),
                'reload_list'=>2,
                'status_employee' =>Configuration::get('ETS_LC_FORCE_ONLINE') ? 'foce_online' : $status,
                'level_request'=> LC_Conversation::getLevelRequestAdmin(),
            )));
        }
        
   }
   if(Tools::isSubmit('load_chat_box')&& ($id_conversation=(int)Tools::getValue('id_conversation')) )
   {    
        if(LC_Conversation::checkExistConversation($id_conversation))
        {
            $conversation_class = new LC_Conversation($id_conversation);
            if(LC_Conversation::checkConversationEmployee($conversation_class,$context->employee->id))
            {
                $message_delivered =(int)Tools::getValue('message_delivered');
                $message_seen =(int)Tools::getValue('message_seen');
                $message_writing =(int)Tools::getValue('message_writing');
                LC_Conversation::updateMessageStattus($id_conversation,$message_delivered,$message_seen,$message_writing,'employee');
                $conversation_opened = json_decode($context->cookie->converation_opened,true);
                if(!$conversation_opened || !in_array($id_conversation,$conversation_opened))
                {
                    $conversation_opened[]=$id_conversation;
                    $context->cookie->converation_opened = json_encode($conversation_opened);
                    $context->cookie->write();
                }
                $refresh = (int)Tools::getValue('refresh');
                if($refresh)
                {
                    $ets_livechat->displayChatBoxEmployee($id_conversation,$refresh);
                }
                else
                {
                    $ets_livechat->updateLastAction();
                    die(json_encode(array(
                        'html' => $ets_livechat->displayChatBoxEmployee($id_conversation,$refresh),
                        'totalMessageNoSeen'=>LC_Conversation::getTotalMessageNoSeen(),
                    )));
                }
            }
            else
            {
                die(json_encode(array(
                    'checkDepartment' => $ets_livechat->l('You\'re not allowed to access this conversation. It has been changed to another department','ets_livechat_ajax'),
                )));
            }
            
        }
        else
        {
            die(json_encode(array(
                    'html' => '',
                    'totalMessageNoSeen'=>LC_Conversation::getTotalMessageNoSeen(),
            )));
        }
   }
   if(Tools::isSubmit('load_chat_boxs') && ($ids_conversation = explode(',',Tools::getValue('ids_conversation'))) && LC_Tools::validateArray($ids_conversation,'isInt'))
   {
        $conversations=array();
        $messages_seen= explode(',',Tools::getValue('messages_seen'));
        $messages_writing= explode(',',Tools::getValue('messages_writing'));
        if(LC_Tools::validateArray($messages_seen) && LC_Tools::validateArray($messages_writing))
        {
            foreach($ids_conversation as $key=> $id_conversation)
            {
                $conversation_class= new LC_Conversation($id_conversation);
                if(LC_Conversation::checkExistConversation($id_conversation) && $id_conversation && LC_Conversation::checkConversationEmployee($conversation_class,$context->employee->id))
                {
                    $message_seen = (int)$messages_seen[$key];
                    $message_writing = $messages_writing[$key];
                    LC_Conversation::updateMessageStattus($id_conversation,false,$message_seen,$message_writing,'employee');
                    $conversations[]=array(
                        'conversation'=> $ets_livechat->displayChatBoxEmployee($id_conversation,true,true),
                        'id_conversation' =>$id_conversation,
                    );
                }
            }
        }
        $reload_list= $ets_livechat->checkNewMessage();
        $customer_all = (int)Tools::getValue('customer_all');
        $customer_archive = (int)Tools::getValue('customer_archive');
        $customer_search = Tools::getValue('customer_search');
        $count_conversation = (int)Tools::getValue('count_conversation',20);
        die(json_encode(array(
            'conversations'=>$conversations,
            'reload_list'=>$reload_list,
            'status_employee' => ($status= LC_Departments::getStatusEmployee($context->employee->id)) ? $status: Configuration::get('ETS_LC_STATUS_EMPLOYEE'),
            'list_customer_html'=> $reload_list? $ets_livechat->displayListCustomerChat():'',
            'last_message'=> $reload_list && $reload_list==1? LC_Message::getMessage():array(),
            'listconversations' =>$reload_list!=2? LC_Conversation::getListConversations($customer_all,$customer_archive,Validate::isCleanHtml($customer_search) ? $customer_search:'','',$count_conversation):'',
            'totalMessageNoSeen' => LC_Conversation::getTotalMessageNoSeen(),
            'level_request'=> LC_Conversation::getLevelRequestAdmin(),
        )));
   }
   if(Tools::isSubmit('send_message') && ($id_conversation=(int)Tools::getValue('id_conversation')) && LC_Conversation::checkExistConversation($id_conversation))
   {
        $ets_livechat->updateLastAction();
        $message = trim((string) Tools::getValue('message', ''));
        $conversation= new LC_Conversation($id_conversation);
        if(!LC_Conversation::checkConversationEmployee($conversation,$context->employee->id))
        {
            die(json_encode(array(
                'id_conversation' => $id_conversation,
                'checkDepartment' => $ets_livechat->l('You\'re not allowed to access this conversation. It has been changed to another department','ets_livechat_ajax'),
            )));   
        }
        if($conversation->end_chat)
        {
            $ets_livechat->errors[]='';
        }
        elseif((Configuration::get('ETS_LC_STAFF_ACCEPT') && $conversation->id_employee==0 && !LC_Conversation::checkSupperAdminDecline($conversation) ))
        {
            $ets_livechat->errors[]=$ets_livechat->l('You have to accept this chat before start chatting.','ets_livechat_ajax');
        }
        if(Tools::strlen($message)>(int)Configuration::get('ETS_LC_MSG_LENGTH'))
            $ets_livechat->errors[]=$ets_livechat->l('Message is invalid','ets_livechat_ajax');
        elseif($message && !Validate::isCleanHtml($message,false))
            $ets_livechat->errors[]=$ets_livechat->l('Message is invalid','ets_livechat_ajax');
        if($id_message=(int)Tools::getValue('id_message'))
        {
            $msg = new LC_Message($id_message);
            $msg->datetime_edited= date('Y-m-d H:i:s');
        }    
        else
        {
            $msg = new LC_Message();
            $msg->datetime_added = date('Y-m-d H:i:s');
            $msg->datetime_edited = $msg->datetime_added;
        } 
        $msg->id_employee = $context->employee->id;                    
        $msg->id_conversation = $id_conversation;
        $msg->message =trim(strip_tags($message));
        $msg->delivered = 0;
        $attachments=array();
        if(!$ets_livechat->errors && isset($_FILES['message_file']['tmp_name'])&& isset($_FILES['message_file']['name']) && $_FILES['message_file']['name'])
        {
            $_FILES['message_file']['name'] = str_replace(array(' ','(',')','!','@','#','+'),'_',$_FILES['message_file']['name']);
            if(!Validate::isFileName($_FILES['message_file']['name']))
                $ets_livechat->errors[] = $ets_livechat->l('File name is not valid','ets_livechat_ajax');
            else
            {
                $type = Tools::strtolower(Tools::substr(strrchr($_FILES['message_file']['name'], '.'), 1));
                if(!in_array($type,$ets_livechat->file_types))
                {
                    $ets_livechat->errors[] = $ets_livechat->l('File upload is invalid','ets_livechat_ajax');
                }
                else
                {
                    $fileupload_name = Tools::strtolower(Tools::passwdGen(20, 'NO_NUMERIC'));
                    $max_size = Configuration::get('ETS_LC_MAX_FILE_MS');
                    $file_size = $_FILES['message_file']['size']/1048576;
                    if($file_size > $max_size && $max_size >0)
                        $ets_livechat->l('Attachment size exceeds the allowable limit.','ets_livechat_ajax');
                    else
                    {
                        if (!move_uploaded_file($_FILES['message_file']['tmp_name'], _PS_ETS_LIVE_CHAT_UPLOAD_DIR_.$fileupload_name))
                            $ets_livechat->errors[] = $ets_livechat->l('Cannot upload the file','ets_livechat_ajax');
                        else
                        {
                            $msg->name_attachment = $_FILES['message_file']['name'];
                            unset($_FILES['message_file']);
                        }
                    }
                    
                }
            }
            
        }
        elseif($msg->message=='' && $message!='' && !$ets_livechat->errors)
        {
            $ets_livechat->errors[]=$ets_livechat->l('Message is invalid','ets_livechat_ajax');
        }
        if(!$ets_livechat->errors)
        {
            if($id_message=(int)Tools::getValue('id_message'))
            {
                $id_download_old = (int)$msg->type_attachment;
                if($msg->update())
                {
                    if(isset($fileupload_name) && $fileupload_name)
                    {
                        $download= new LC_Download();
                        $download->filename= $msg->name_attachment;
                        $download->name = $fileupload_name;
                        $download->id_message= $msg->id;
                        $download->id_conversation = $msg->id_conversation;
                        $download->file_size = $file_size*1024;
                        $download->file_type= $type;
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
                    if($conversation->employee_message_edited)
                        $conversation->employee_message_edited .=','.$id_message;
                    else
                        $conversation->employee_message_edited=$id_message;
                    $conversation->date_message_last = date('Y-m-d H:i:s');
                    $conversation->replied=1;
                    $conversation->employee_writing=0;
                    $conversation->update();
                    
                    
                }
                else
                {
                    $ets_livechat->errors[] = $ets_livechat->l('Send message failed','ets_livechat_ajax');
                    if(isset($fileupload_name) && $fileupload_name && file_exists(_PS_ETS_LIVE_CHAT_UPLOAD_DIR_.$fileupload_name))
                        @unlink(_PS_ETS_LIVE_CHAT_UPLOAD_DIR_.$fileupload_name);
                }
            }
            else
            {
                
                if($msg->add())
                {
                    if(isset($fileupload_name) && $fileupload_name)
                    {
                        $download= new LC_Download();
                        $download->filename= $msg->name_attachment;
                        $download->name = $fileupload_name;
                        $download->id_message= $msg->id;
                        $download->id_conversation = $msg->id_conversation;
                        $download->file_size = $file_size*1024;
                        $download->file_type= $type;
                        if($download->add())
                        {
                            $msg->type_attachment=$download->id;
                            $msg->update();
                        }elseif(isset($fileupload_name) && $fileupload_name && file_exists(_PS_ETS_LIVE_CHAT_UPLOAD_DIR_.$fileupload_name))
                            @unlink(_PS_ETS_LIVE_CHAT_UPLOAD_DIR_.$fileupload_name);
                    }
                    $conversation->date_message_last = date('Y-m-d H:i:s');
                    if($conversation->id_departments_wait)
                    {
                        $conversation->id_departments = $conversation->id_departments_wait==-1 ? 0 : $conversation->id_departments_wait;
                        $conversation->id_departments_wait=0;
                    }
                    $conversation->replied=1;
                    $conversation->employee_writing=0;
                    $conversation->update();
                }
                else
                {
                    $ets_livechat->errors[] = $ets_livechat->l('Send message failed','ets_livechat_ajax');
                    if(isset($fileupload_name) && $fileupload_name && file_exists(_PS_ETS_LIVE_CHAT_UPLOAD_DIR_.$fileupload_name))
                        @unlink(_PS_ETS_LIVE_CHAT_UPLOAD_DIR_.$fileupload_name);
                }
            }
            if(!$ets_livechat->errors)
            {
                if(Tools::isSubmit('send_message_to_mail'))
                {
                    if($msg->name_attachment)
                    {
                        $linkdownload = $context->link->getModuleLink('ets_livechat','download',array('downloadfile'=>md5(_COOKIE_KEY_.$msg->type_attachment)));
                        $attachment_file= Module::getInstanceByName('ets_livechat')->displayText(Module::getInstanceByName('ets_livechat')->displayText($msg->name_attachment,'a','file_sent',array('href'=>$linkdownload,'target'=>'_blank')),'p','');
                        $attachment_file= Module::getInstanceByName('ets_livechat')->displayText(Module::getInstanceByName('ets_livechat')->displayText($msg->name_attachment,'a','',array('class'=>'file_sent','target'=>'_blank','href'=>$linkdownload) ),'p','');
                        $attachment_file_txt = $msg->name_attachment .': '.$linkdownload;
                    }
                    $template_vars =array(
                        '{message}'=> Tools::nl2br($msg->message).(isset($attachment_file) ? $attachment_file :''),
                        '{message_txt}'=> strip_tags($msg->message).(isset($attachment_file_txt) ? $attachment_file_txt :''),
                        '{year}' =>Date('Y'),
                    );
                    if($conversation->id_customer)
                    {
                        $customer= new Customer($conversation->id_customer);
                        $email = $customer->email;
                    }
                    else
                        $email= $conversation->customer_email;
                    if($email && file_exists(dirname(__FILE__).'/mails/'.$context->language->iso_code.'/send_message.html'))
                    {
                        Mail::Send(
        					isset($customer) ? $customer->id_lang : ($conversation->id_lang ? : Context::getContext()->language->id),
        					'send_message',
                            ($ets_livechat->getTextLang('Message from',isset($customer) ? $customer->id_lang : ($conversation->id_lang ? : Context::getContext()->language->id),'ets_livechat_ajax')?:$ets_livechat->l('Message from','ets_livechat_ajax')).' '.$context->shop->name,
        					$template_vars,
        					$email,
        					null,
        					null,
        					null,
        					null,
        					null,
        					dirname(__FILE__).'/mails/',
        					null,
        					Context::getContext()->shop->id
        				);
                    }
                }
                $ets_livechat->displayChatBoxEmployee($id_conversation,true);
            }
            die(json_encode(array(
                'html' => '',
                'error' => $ets_livechat->errors ? $ets_livechat->displayError($ets_livechat->errors):false,
            )));
        }
        else
        {
            die(json_encode(array(
                'html' => '',
                'error' => $ets_livechat->errors?$ets_livechat->displayError($ets_livechat->errors):false,
            )));  
        }
   }
   if(Tools::isSubmit('changed_satatusblock') && ($id_conversation =(int)Tools::getValue('id_conversation')) && LC_Conversation::checkExistConversation($id_conversation))
   {
        if(!LC_Conversation::checkConversationEmployee($id_conversation,$context->employee->id))
        {
            die(json_encode(array(
                'checkDepartment' => $ets_livechat->l('You\'re not allowed to access this conversation. It has been changed to another department','ets_livechat_ajax'),
            )));   
        }
        $ets_livechat->updateLastAction();
        $status= (int)Tools::getValue('status');
        Db::getInstance()->execute('UPDATE `'._DB_PREFIX_.'ets_livechat_conversation` set blocked="'.(int)$status.'" WHERE id_conversation='.(int)$id_conversation);
        die(
            json_encode(
                array(
                    'status' => $status,
                    'text_status' => $status? $ets_livechat->l('Blocked','ets_livechat_ajax'):$ets_livechat->l('Block','ets_livechat_ajax'),
                )
            )
        );
   }
   if(Tools::isSubmit('changed_satatuscaptcha') && ($id_conversation=(int)Tools::getValue('id_conversation')) &&  LC_Conversation::checkExistConversation($id_conversation))
   {
        if(!LC_Conversation::checkConversationEmployee($id_conversation,$context->employee->id))
        {
            die(json_encode(array(
                'id_conversation' => $id_conversation,
                'checkDepartment' => $ets_livechat->l('You\'re not allowed to access this conversation. It has been changed to another department','ets_livechat_ajax'),
            )));   
        }
        $ets_livechat->updateLastAction();
        $status= (int)Tools::getValue('status');
        Db::getInstance()->execute('UPDATE `'._DB_PREFIX_.'ets_livechat_conversation` set captcha_enabled="'.(int)$status.'" WHERE id_conversation='.(int)$id_conversation);
        die(
            json_encode(
                array(
                    'status' => $status,
                    'text_status' => $status? $ets_livechat->l('Captcha','ets_livechat_ajax'):$ets_livechat->l('Captcha','ets_livechat_ajax'),
                )
            )
        );
   }
   if(Tools::isSubmit('add_active_customer_chat') && ($id_conversation=(int)Tools::getValue('id_conversation')) && LC_Conversation::checkExistConversation($id_conversation))
   {
        Db::getInstance()->execute('UPDATE `'._DB_PREFIX_.'ets_livechat_conversation` SET archive=0 WHERE id_conversation='.(int)$id_conversation);
        die(json_encode(array(
            'html' => $ets_livechat->displayListCustomerChat(),
            'totalMessageNoSeen'=>LC_Conversation::getTotalMessageNoSeen(),
        )));
   }
   if(Tools::isSubmit('add_archive_customer_chat') && ($id_conversation=(int)Tools::getValue('id_conversation')) && LC_Conversation::checkExistConversation($id_conversation) )
   {
        Db::getInstance()->execute('UPDATE `'._DB_PREFIX_.'ets_livechat_conversation` SET archive=1 WHERE id_conversation='.(int)$id_conversation);
        die(json_encode(array(
            'html' => $ets_livechat->displayListCustomerChat(),
            'totalMessageNoSeen'=>LC_Conversation::getTotalMessageNoSeen(),
        )));
   }
   if(Tools::isSubmit('change_department') && ($id_conversation=(int)Tools::getValue('id_conversation')))
   {
        $ets_livechat->updateLastAction();
        if(LC_Conversation::checkExistConversation($id_conversation))
        {
            $error='';
            $id_departments = (int)Tools::getValue('id_departments');
            $id_employee =(int)Tools::getValue('id_employee');
            if($id_departments &&  $id_employee > 0 && !LC_Departments::checkDepartmentsExitsEmployee($id_departments,$id_employee))
                $error = $ets_livechat->l('Departments and employee are invalid','ets_livechat_ajax');
            $conversation = new LC_Conversation($id_conversation);
            if($conversation->id_employee!= $context->employee->id && $context->employee->id_profile!=1)
                $error = $ets_livechat->l('You do not have access permission','ets_livechat_ajax');
            if(!$error)
            {
                $conversation->id_departments_wait = $id_departments;
                $conversation->id_employee_wait =(int)$id_employee;
                $conversation->id_tranfer= $context->employee->id;
                if($conversation->update())
                {
                    Db::getInstance()->execute('DELETE FROM `'._DB_PREFIX_.'ets_livechat_staff_decline` WHERE id_conversation="'.(int)$conversation->id.'" AND id_employee='.(int)$conversation->id_employee_wait);
                }
                
                die(
                    json_encode(
                        array(
                            'id_conversation' =>$id_conversation,
                            'succ' => $ets_livechat->l('Changed successfully','ets_livechat_ajax'),
                            'waiting_acceptance' => $ets_livechat->checkWaitingAcceptance($conversation),
                        )
                    )
                );
            }
            else
            {
                die(
                    json_encode(
                        array(
                            'error'=>$error,
                        )
                    )
                );
            }
        }
   }
   if(Tools::isSubmit('change_status_form') && ($id_form=(int)Tools::getValue('id_form')))
   {
        $active = (int)Tools::getValue('active');
        Db::getInstance()->execute('UPDATE `'._DB_PREFIX_.'ets_livechat_ticket_form` SET active="'.(int)$active.'" WHERE id_form='.(int)$id_form);
        die(
            json_encode(
                array(
                    'active' =>(int)$active,
                    'id_form' => $id_form,
                    'title' => $active ? $ets_livechat->l('Click to disabled','ets_livechat_ajax'): $ets_livechat->l('Click to enabled','ets_livechat_ajax'),
                    'success' => $ets_livechat->l('Status changed','ets_livechat_ajax'),
                )
            )
        );
   }
   if(Tools::isSubmit('change_status_departments') && $id_departments=Tools::getValue('id_departments'))
   {
        $status = (int)Tools::getValue('status');
        $ets_livechat->updateLastAction();
        Db::getInstance()->execute('UPDATE `'._DB_PREFIX_.'ets_livechat_departments` SET status="'.(int)$status.'" WHERE id_departments='.(int)$id_departments);
        die(
            json_encode(
                array(
                    'status' =>(int)$status,
                    'id_departments' => $id_departments,
                    'success' => $ets_livechat->l('Status changed','ets_livechat_ajax'),
                    'title' => $status ? $ets_livechat->l('Click to disabled','ets_livechat_ajax'): $ets_livechat->l('Click to enabled','ets_livechat_ajax')
                )
            )
        );
   }
   if(Tools::isSubmit('change_status_staff') && ($id_employee=(int)Tools::getValue('id_employee')))
   {
        $errors = array();
        $employee = new Employee($id_employee);
        $status = (int)Tools::getValue('status');
        if(!Validate::isLoadedObject($employee))
            $errors[] = $ets_livechat->l('Employee is not valid','ets_livechat_ajax');
        elseif($employee->id_profile==1 && $status==0)
            $errors[] = $ets_livechat->l('You do not have permission to disable this staff','ets_livechat_ajax');
        if(!$errors)
        {
            $ets_livechat->updateLastAction();
            if($staff = Db::getInstance()->getRow('SELECT * FROM `'._DB_PREFIX_.'ets_livechat_staff` WHERE id_employee='.(int)$id_employee))
                Db::getInstance()->execute('UPDATE `'._DB_PREFIX_.'ets_livechat_staff` SET status="'.(int)$status.'" WHERE id_employee='.(int)$id_employee);
            else
            {
                
                $name = $employee->firstname.' '.$employee->lastname;
                Db::getInstance()->execute('INSERT INTO `'._DB_PREFIX_.'ets_livechat_staff` (id_employee,name,status) VALUES ("'.(int)$id_employee.'","'.pSQL($name).'","'.(int)$status.'")');
            }
            die(
                json_encode(
                    array(
                        'status' =>(int)$status,
                        'id_employee' => $id_employee,
                        'title' => $status ? $ets_livechat->l('Click to disabled','ets_livechat_ajax'): $ets_livechat->l('Click to enabled','ets_livechat_ajax'),
                        'success' => $ets_livechat->l('Status changed','ets_livechat_ajax'),
                    )
                )
            );
        }
        else
        {
            die(
                json_encode(
                    array(
                        'error'=> $ets_livechat->displayError($errors),
                    )
                )
            );
        }
        
   }
   if(Tools::isSubmit('get_staff') && ($id_employee=(int)Tools::getValue('id_employee')))
   {
        die(
            json_encode(
                array(
                    'html' =>$ets_livechat->getFormStaff($id_employee),
                )
            )
        );
   }
   if(Tools::isSubmit('submit_save_staff') && ($id_employee=(int)Tools::getValue('id_employee')))
   {
        $ets_livechat->updateLastAction();
        $ets_livechat->_postStaff($id_employee);
   }
   if(Tools::isSubmit('delete_avata_staff') && ($id_employee=(int)Tools::getValue('id_staff')) )
   {
        $ets_livechat->updateLastAction();
        $avatar = Db::getInstance()->getValue('SELECT avata FROM `'._DB_PREFIX_.'ets_livechat_staff` WHERE id_employee='.(int)$id_employee);
        if($avatar)
        {
            Db::getInstance()->execute('UPDATE `'._DB_PREFIX_.'ets_livechat_staff` SET avata="" WHERE id_employee='.(int)$id_employee);
            if(file_exists(_PS_ETS_LIVE_CHAT_IMG_DIR_.$avatar))
                @unlink(_PS_ETS_LIVE_CHAT_IMG_DIR_.$avatar);
        }
        die(json_encode(
            array(
                'success' => $ets_livechat->l('Deleted avatar successfully','ets_livechat_ajax'),
                'image' => Context::getContext()->link->getMediaLink(_PS_ETS_LIVE_CHAT_IMG_.'adminavatar.jpg'),
            )
        )
        );
   }
   if(Tools::isSubmit('change_company_info') && ($value=Tools::getValue('value')) && Validate::isCleanHtml($value))
   {
        die(
            json_encode(LC_Departments::getCompanyInfo(Context::getContext()->employee->id,$value))
        );
   }
   if(Tools::isSubmit('accept_submit') && ($id_conversation =(int)Tools::getValue('id_conversation')))
   {
        $ets_livechat->updateLastAction();
        $ets_livechat->_accpectConversation($id_conversation);
   }
   if(Tools::isSubmit('decline_submit') && ($id_conversation=(int)Tools::getValue('id_conversation')))
   {
        $ets_livechat->updateLastAction();
        $ets_livechat->_declineConversation($id_conversation);
   }
   if(Tools::isSubmit('cancel_acceptance') && ($id_conversation= (int)Tools::getValue('id_conversation')))
   {
        $ets_livechat->updateLastAction();
        $ets_livechat->_cancelAcceptance($id_conversation);
   }
   if(Tools::isSubmit('conversation_note') && ($conversation_note = Tools::getValue('conversation_note')) && Validate::isCleanHtml($conversation_note) && ($id_conversation= (int)Tools::getValue('id_conversation')))
   {
        $ets_livechat->updateLastAction();
        Db::getInstance()->execute('UPDATE `'._DB_PREFIX_.'ets_livechat_conversation` SET NOTE ="'.pSQL($conversation_note,true).'" WHERE id_conversation="'.(int)$id_conversation.'"');
        die(
            json_encode(
                array(
                    'error'=>false,
                )
            )  
        );
   }
   if(Tools::isSubmit('view_conversation') && ($id_conversation= (int)Tools::getValue('id_conversation')))
   {
        die(
            json_encode(
                array(
                    'converation_messages' => $ets_livechat->_displayConversationDetail($id_conversation),
                )
            )
        );
   }
   if(Tools::isSubmit('gethistory') && ($id_conversation=(int)Tools::getValue('id_conversation')))
   {
        $conversation = new LC_Conversation($id_conversation);
        if($conversation->chatref)
        {
            die(
                json_encode(
                    array(
                        'conversation_history' => $ets_livechat->_displayHistoryChatCustomer($conversation->chatref),
                    )
                )
            );
        }
        else
        {
            die(
                json_encode(
                    array(
                        'error' => $ets_livechat->l('Conversation does not exist','ets_livechat_ajax'),
                    )
                )
            );
        }
   }
   if(Tools::isSubmit('add_new_field_in_form'))
   {
        $max_position = (int)Tools::getValue('max_position');
        die(
            json_encode(
                array(
                    'html_form_filed' => $ets_livechat->GetFormField(0,$max_position+1),
                )
            )  
        );
   }
   $action = Tools::getValue('action');
   if($action=='updatePositionField')
   {
        $ets_livechat->updateLastAction();
        if(($ticket_form_field = Tools::getValue('ticket_form_field')) && LC_Tools::validateArray($ticket_form_field,'isInt'))
        {
            foreach($ticket_form_field as $key=> $field)
            {
                if($field)
                {
                    Db::getInstance()->execute('UPDATE `'._DB_PREFIX_.'ets_livechat_ticket_form_field` SET position ="'.(int)$key.'" WHERE id_field='.(int)$field);
                }
            }
        }
        die(
            json_encode(
                array(
                    'success' => $ets_livechat->l('Position updated successfully','ets_livechat_ajax'),
                )
            )
        );
   }
   if(Tools::isSubmit('delete_form_field') && ($id_field= (int)Tools::getValue('id_field')))
   {
        $ets_livechat->updateLastAction();
        $field_class= new LC_Ticket_field($id_field);
        if($field_class->id_form!=1)
        {
            $field_class->delete();
            die(
                json_encode(
                    array(
                        'success' => $ets_livechat->l('Field deleted successfully','ets_livechat_ajax'),
                    )
                )
            ); 
        }
   }
   if(Tools::isSubmit('delete_form_obj') && ($id_form= (int)Tools::getValue('id_form')))
   {
        if($id_form!=1)
        {
            $ets_livechat->updateLastAction();
            $form= new LC_Ticket_form($id_form);
            $form->delete();
            die(
                json_encode(
                    array(
                        'success' => $ets_livechat->l('Form deleted successfully','ets_livechat_ajax'),
                    )
                )
            ); 
        }
        
   }
   if(Tools::isSubmit('submit_clear_attachments') && ($ETS_CLEAR_ATTACHMENT = Tools::getValue('ETS_CLEAR_ATTACHMENT')))
   {
    
        $ets_livechat->updateLastAction();  
        switch ($ETS_CLEAR_ATTACHMENT) {
            case '1_week':
                $date= date('Y-m-d',strtotime('-1 WEEK'));
                break;
            case '1_month_ago':
                $date = date('Y-m-d',strtotime('-1 MONTH'));
                break;
            case '6_month_ago':
                $date = date('Y-m-d',strtotime('-6 MONTH'));
                break;
            case '1_year_ago':
                $date = date('Y-m-d',strtotime('-1 YEAR'));
                break;
            case 'everything':
                LC_Message::getAttachmentsMessage();
                LC_Note::getAttachmentsNote();
                LC_Ticket::getAttachmentsTickets();
                break;
        
        }
        if(isset($date))
        {
            LC_Message::getAttachmentsMessage(false,' AND datetime_added <"'.pSQL($date).'"');
            LC_Note::getAttachmentsNote(false,' AND date_add <"'.pSQL($date).'"');
            LC_Ticket::getAttachmentsTickets(false,' AND t.date_add <"'.pSQL($date).'"');
        }
        $message_week= LC_Message::getAttachmentsMessage(true,' AND datetime_added <"'.pSQL(date('Y-m-d',strtotime('-1 WEEK'))).'"');
        $note_week= LC_Note::getAttachmentsNote(true,' AND date_add <"'.pSQL(date('Y-m-d',strtotime('-1 WEEK'))).'"');
        $attachment_week= LC_Ticket::getAttachmentsTickets(true,' AND t.date_add <"'.pSQL(date('Y-m-d',strtotime('-1 WEEK'))).'"');
        $messages_1_month_ago= LC_Message::getAttachmentsMessage(true,' AND datetime_added <"'.pSQL(date('Y-m-d',strtotime('-1 MONTH'))).'"');
        $note_1_month_ago= LC_Note::getAttachmentsNote(true,' AND date_add <"'.pSQL(date('Y-m-d',strtotime('-1 MONTH'))).'"');
        $attachment_1_month_ago = LC_Ticket::getAttachmentsTickets(true,' AND t.date_add <"'.pSQL(date('Y-m-d',strtotime('-1 MONTH'))).'"');
        $messages_6_month_ago = LC_Message::getAttachmentsMessage(true,' AND datetime_added <"'.pSQL(date('Y-m-d',strtotime('-6 MONTH'))).'"');
        $notes_6_month_ago = LC_Note::getAttachmentsNote(true,' AND date_add <"'.pSQL(date('Y-m-d',strtotime('-6 MONTH'))).'"');
        $attachments_6_month_ago = LC_Ticket::getAttachmentsTickets(true,' AND t.date_add <"'.pSQL(date('Y-m-d',strtotime('-6 MONTH'))).'"');
        $messages_year_ago = LC_Message::getAttachmentsMessage(true,' AND datetime_added <"'.pSQL(date('Y-m-d',strtotime('-1 YEAR'))).'"');
        $notes_year_ago = LC_Note::getAttachmentsNote(true,' AND date_add <"'.pSQL(date('Y-m-d',strtotime('-1 YEAR'))).'"');
        $attachments_year_ago = LC_Ticket::getAttachmentsTickets(true,' AND t.date_add <"'.pSQL(date('Y-m-d',strtotime('-1 YEAR'))).'"');
        $messages_everything = LC_Message::getAttachmentsMessage(true);
        $notes_everything = LC_Note::getAttachmentsNote(true);
        $attachments_everything = LC_Ticket::getAttachmentsTickets(true);
        die(
            json_encode(
                array(
                    'success' => $ets_livechat->l('Attachment deleted successfully','ets_livechat_ajax'),
                    'attachments_1_week' => $message_week['count']+$note_week['count']+$attachment_week['count'],
                    'attachments_1_week_size' => Tools::ps_round($message_week['size']+$note_week['size']+$attachment_week['size'],2),
                    'attachments_1_month_ago' => $messages_1_month_ago['count'] + $note_1_month_ago['count']+$attachment_1_month_ago['count'],
                    'attachments_1_month_ago_size' => Tools::ps_round($messages_1_month_ago['size'] + $note_1_month_ago['size']+$attachment_1_month_ago['size'],2),
                    'attachments_6_month_ago' => $messages_6_month_ago['count']+$notes_6_month_ago['count']+$attachments_6_month_ago['count'],
                    'attachments_6_month_ago_size' => Tools::ps_round($messages_6_month_ago['size']+$notes_6_month_ago['size']+$attachments_6_month_ago['size'],2),
                    'attachments_year_ago' =>$messages_year_ago['count']+$notes_year_ago['count']+$attachments_year_ago['count'] ,
                    'attachments_year_ago_size' =>Tools::ps_round($messages_year_ago['size']+$notes_year_ago['size']+$attachments_year_ago['size'],$messages_year_ago['size']+$notes_year_ago['size']+$attachments_year_ago['size']) ,
                    'attachments_everything' =>  $messages_everything['count']+$notes_everything['count']+$attachments_everything['count'],
                    'attachments_everything_size' =>  Tools::ps_round($messages_everything['size']+$notes_everything['size']+$attachments_everything['size'],2),
                )
            )
        );
   }
   if(Tools::isSubmit('submitSendMail') && ($mail_conversation_id= (int)Tools::getValue('mail_conversation_id')))
   {
        $ets_livechat->updateLastAction();
        $ets_livechat->_submitSendMail($mail_conversation_id);
   }
}
else
{
    die(json_encode(array(
        'html' => '',
        'error' => $ets_livechat->displayError('You have been logged out'),
    )));  
}
?>
