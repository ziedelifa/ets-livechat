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
class LC_Conversation extends ObjectModel
{
    /** @var Context|null */
    protected $context = null;
    public $id_customer;
    public $id_shop;
    public $id_lang;
    public $blocked;
    public $archive;
    public $captcha_enabled;
    public $customer_writing;
    public $employee_writing;
    public $customer_name;
    public $customer_email;
    public $customer_phone; 
    public $date_message_seen_employee;
    public $date_message_seen_customer;
    public $date_message_delivered_employee;
    public $date_message_delivered_customer;
    public $date_message_writing_employee;
    public $date_message_writing_customer;
    public $date_message_last; 
    public $date_message_last_customer;  
    public $date_mail_last;
    public $latest_ip;
    public $rating;
    public $enable_sound;
    public $latest_online;
    public $datetime_added;
    public $browser_name;
    public $id_departments;
    public $id_departments_wait;
    public $id_employee;
    public $id_employee_wait;
    public $id_tranfer;
    public $date_accept;
    public $end_chat;
    public $message_deleted;
    public $message_edited;
    public $employee_message_deleted;
    public $employee_message_edited;
    public $replied;    
    public $current_url;
    public $http_referer;
    public $chatref;
    public $note;
    public $id_ticket;
    public static $definition = array(
		'table' => 'ets_livechat_conversation',
		'primary' => 'id_conversation',
		'fields' => array(
            'id_customer' => array('type' => self::TYPE_INT),    
            'id_ticket'=> array('type' => self::TYPE_INT),
            'id_shop' => array('type' => self::TYPE_INT),  
            'id_lang' => array('type' => self::TYPE_INT),   
            'blocked' => array('type' => self::TYPE_INT), 
            'archive' =>array('type' => self::TYPE_INT), 
            'captcha_enabled'=>array('type'=>self::TYPE_INT),
            'customer_writing' => array('type' => self::TYPE_INT),
            'employee_writing' => array('type' => self::TYPE_INT),  
            'customer_name' => array('type' => self::TYPE_STRING),            
            'customer_email' => array('type' => self::TYPE_STRING),
            'customer_phone' => array('type' => self::TYPE_STRING),
            'latest_ip' => array('type' => self::TYPE_STRING),
            'browser_name'=>array('type'=>self::TYPE_STRING),
            'id_departments'=>array('type'=>self::TYPE_INT),
            'id_departments_wait'=>array('type'=>self::TYPE_INT),
            'id_employee'=>array('type'=>self::TYPE_INT),
            'id_employee_wait'=>array('type'=>self::TYPE_INT),
            'id_tranfer'=>array('type'=>self::TYPE_INT),
            'date_accept' =>array('type' => self::TYPE_DATE),
            'latest_online' => array('type' => self::TYPE_DATE),
            'datetime_added' => array('type' => self::TYPE_DATE),  
            'date_message_seen_employee' => array('type' => self::TYPE_DATE), 
            'date_message_seen_customer' => array('type' => self::TYPE_DATE),
            'date_message_writing_employee' => array('type' => self::TYPE_DATE), 
            'date_message_writing_customer' => array('type' => self::TYPE_DATE), 
            'date_message_delivered_employee' => array('type' => self::TYPE_DATE), 
            'date_message_delivered_customer' => array('type' => self::TYPE_DATE),
            'date_mail_last' => array('type'=>self::TYPE_DATE),
            'rating' => array('type'=>self::TYPE_INT),
            'enable_sound'=>array('type'=>self::TYPE_INT),
            'end_chat'=>array('type'=>self::TYPE_INT),
            'message_edited'=>array('type'=>self::TYPE_STRING),
            'message_deleted'=>array('type'=>self::TYPE_STRING),	
            'employee_message_deleted'=>array('type'=>self::TYPE_STRING),
            'employee_message_edited'=>array('type'=>self::TYPE_STRING),
            'date_message_last' => array('type' => self::TYPE_DATE),   
            'date_message_last_customer'=> array('type' => self::TYPE_DATE), 
            'replied' => array('type' => self::TYPE_INT),  
            'http_referer' => array('type' => self::TYPE_STRING),
            'current_url' => array('type' => self::TYPE_STRING),  
            'chatref' => array('type' => self::TYPE_INT),  
            'note' => array('type' => self::TYPE_STRING),   
        )
	);
    public function __construct($id = null, $idLang = null)
    {
        parent::__construct($id, $idLang);
        $this->context = Context::getContext();
    }
    public static function getCustomerConversation()
    {
        $context = Context::getContext();
        if(isset($context->customer->id) && (int)$context->customer->id) 
        {
            return self::getConversationByIdCustomer((int)$context->customer->id);
        }  
        elseif(isset($context->cookie->lc_id_conversation) && (int)$context->cookie->lc_id_conversation && Db::getInstance()->getValue('SELECT id_conversation FROM `'._DB_PREFIX_.'ets_livechat_conversation` WHERE id_conversation='.(int)$context->cookie->lc_id_conversation))
            return new LC_Conversation((int)$context->cookie->lc_id_conversation);
        return false;
    }
    public static function getConversationByIdCustomer($id_customer)
    {
        return ($id_conversation = (int)Db::getInstance()->getValue("SELECT max(id_conversation) FROM `"._DB_PREFIX_."ets_livechat_conversation` WHERE id_customer=".(int)$id_customer.' ORDER BY chatref DESC')) ? new LC_Conversation($id_conversation) : false;
    }
    public function getMessages($lastedID = 0, $limit=0,$orderType = 'DESC',$extraID=0)
    {
        return $this->id ? LC_Message::getMessages($this->id,$lastedID,$limit,$orderType,$extraID) : false;
    }
    public static function isUsedField($fieldName)
    {
        return ($fields = explode(',',Configuration::get('ETS_LC_CHAT_FIELDS'))) && is_array($fields) && in_array($fieldName,$fields);           
    }
    public static function isRequiredField($fieldName)
    {        
        if(!self::isUsedField($fieldName))
            return false;
        return ($fields = explode(',',Configuration::get('ETS_LC_CHAT_FIELDS_REQUIRED'))) && is_array($fields) && in_array($fieldName,$fields);           
    }
    public static function getListConversations($all=true,$archive=false,$customer_name='',$lasttime='',$count_conversation=20)
    {
        $ets_livechat= new Ets_livechat();
        $declines = $ets_livechat->getDeclineConversation();
        if($all)
        {
            $sql = 'SELECT lc.*,CONCAT(c.firstname," ",c.lastname) as fullname  FROM `'._DB_PREFIX_.'ets_livechat_conversation` lc
                LEFT JOIN `'._DB_PREFIX_.'ets_livechat_departments` ld ON (ld.id_departments=lc.id_departments OR ld.id_departments=lc.id_departments_wait)
                LEFT JOIN `'._DB_PREFIX_.'ets_livechat_departments_employee` lde ON (ld.id_departments=lde.id_departments)
                LEFT JOIN `'._DB_PREFIX_.'employee` e ON (e.id_employee = lde.id_employee)
                LEFT JOIN `'._DB_PREFIX_.'customer` c ON (c.id_customer=lc.id_customer)
                WHERE 1
                '.(!$ets_livechat->all_shop?'  AND lc.id_shop="'.(int)Context::getContext()->shop->id.'"':'')
                .(Context::getContext()->employee->id_profile!= 1 ? ' AND (e.id_employee="'.(int)Context::getContext()->employee->id.'" OR lc.id_departments=0 OR ld.all_employees=1 OR lc.id_departments_wait=-1)':'')
                .(Configuration::get('ETS_LC_STAFF_ACCEPT') && Context::getContext()->employee->id_profile!=1 ? ' AND (lc.id_employee=0 OR lc.id_employee="'.(int)Context::getContext()->employee->id.'" OR lc.id_employee_wait="'.(int)Context::getContext()->employee->id.'" OR lc.id_employee_wait=-1)':'')
                .(isset($declines) && $declines ? ' AND lc.id_conversation NOT IN ('.implode(',',array_map('intval',$declines)).')':'')
                .($lasttime ? ' AND date_message_last_customer < "'.pSQL($lasttime).'"': '')
                .' GROUP BY lc.id_conversation ORDER BY date_message_last_customer DESC LIMIT 0,'.(int)$count_conversation;
            if($customer_name)
            {
                $sql = 'SELECT lc.*,CONCAT(c.firstname," ",c.lastname) as fullname,m.message,m.datetime_added FROM `'._DB_PREFIX_.'ets_livechat_conversation` lc
                LEFT JOIN `'._DB_PREFIX_.'ets_livechat_departments` ld ON (ld.id_departments=lc.id_departments)
                LEFT JOIN `'._DB_PREFIX_.'ets_livechat_departments_employee` lde ON (ld.id_departments=lde.id_departments OR ld.id_departments=lc.id_departments_wait)
                LEFT JOIN `'._DB_PREFIX_.'employee` e ON (e.id_employee = lde.id_employee)
                LEFT JOIN `'._DB_PREFIX_.'customer` c ON (c.id_customer=lc.id_customer)
                LEFT JOIN `'._DB_PREFIX_.'ets_livechat_message` m ON(m.id_conversation=lc.id_conversation AND m.id_employee=0)
                WHERE '
                .(!$ets_livechat->all_shop?' lc.id_shop="'.(int)Context::getContext()->shop->id.'" AND ':'')
                .'( CONCAT(c.firstname," ",c.lastname) LIKE "%'.pSQL($customer_name).'%" OR lc.customer_name like "%'.pSQL($customer_name).'%" OR m.message like "%'.pSQL($customer_name).'%")'
                .(Context::getContext()->employee->id_profile!= 1 ? ' AND (e.id_employee="'.(int)Context::getContext()->employee->id.'" OR lc.id_departments=0 OR ld.all_employees=1 OR lc.id_departments_wait=-1)':'')
                .(Configuration::get('ETS_LC_STAFF_ACCEPT') && Context::getContext()->employee->id_profile!=1 ? ' AND (lc.id_employee=0 OR lc.id_employee="'.(int)Context::getContext()->employee->id.'" OR lc.id_employee_wait="'.(int)Context::getContext()->employee->id.'" OR lc.id_employee_wait=-1)':'') 
                .(isset($declines) && $declines ? ' AND lc.id_conversation NOT IN ('.implode(',',array_map('intval',$declines)).')':'')
                .($lasttime ? ' AND date_message_last_customer < "'.pSQL($lasttime).'"': '')
                .' GROUP BY lc.id_conversation ORDER BY date_message_last_customer DESC LIMIT 0,'.(int)$count_conversation;
                $conversations = Db::getInstance()->executeS($sql);
                if($conversations)
                {
                    foreach($conversations as $key=> &$conversation)
                    {
                        if($conversation['id_customer'] && Tools::strpos(Tools::strtolower($conversation['fullname']),Tools::strtolower($customer_name))===false && Tools::strpos(Tools::strtolower($conversation['message']),Tools::strtolower($customer_name))===false)
                        {
                            unset($conversations[$key]);
                            continue;
                        }
                        else
                        {
                            LC_Conversation::updateMessageStattus($conversation['id_conversation'],true,false,false,'employee');
                            if(LC_Conversation::isCustomerOnline($conversation['id_conversation']))
                                $conversation['online']=1;
                            else
                                $conversation['online']=0;
                            $conversation['wait_accept']= LC_Conversation::checkWaitAccept($conversation['id_conversation']);
                            $conversation['has_changed']= LC_Conversation::checkHasChanged($conversation['id_conversation']);
                        }
                    }
                } 
                return $conversations;
            }
        }
        else
        {
            $sql = 'SELECT lc.*,CONCAT(c.firstname," ",c.lastname) as fullname  FROM `'._DB_PREFIX_.'ets_livechat_conversation` lc
                LEFT JOIN `'._DB_PREFIX_.'ets_livechat_departments` ld ON (ld.id_departments=lc.id_departments OR ld.id_departments=lc.id_departments_wait)
                LEFT JOIN `'._DB_PREFIX_.'ets_livechat_departments_employee` lde ON (ld.id_departments=lde.id_departments)
                LEFT JOIN `'._DB_PREFIX_.'employee` e ON (e.id_employee = lde.id_employee)
                LEFT JOIN `'._DB_PREFIX_.'customer` c ON (c.id_customer=lc.id_customer)
                WHERE '
                .(!$ets_livechat->all_shop ? ' lc.id_shop="'.(int)Context::getContext()->shop->id.'" AND ':'')
                .' archive ='.($archive ? '1' : '0')
                .(Context::getContext()->employee->id_profile!= 1 ? ' AND (e.id_employee="'.(int)Context::getContext()->employee->id.'" OR lc.id_departments=0 OR ld.all_employees=1 OR lc.id_departments_wait=-1)':'')
                .(Configuration::get('ETS_LC_STAFF_ACCEPT')  && Context::getContext()->employee->id_profile!=1 ? ' AND (lc.id_employee=0 OR lc.id_employee="'.(int)Context::getContext()->employee->id.'" OR lc.id_employee_wait="'.(int)Context::getContext()->employee->id.'" OR lc.id_employee_wait=-1)':'')
                .(isset($declines) && $declines ? ' AND lc.id_conversation NOT IN ('.implode(',',array_map('intval',$declines)).')':'')
                .($lasttime ? ' AND date_message_last_customer < "'.pSQL($lasttime).'"': '')
                .' GROUP BY lc.id_conversation ORDER BY date_message_last_customer DESC LIMIT 0,'.(int)$count_conversation;
        }
        $conversations = Db::getInstance()->executeS($sql);

        if($conversations)
        {
            foreach($conversations as &$conversation)
            {
                LC_Conversation::updateMessageStattus($conversation['id_conversation'],true,false,false,'employee');
                if(LC_Conversation::isCustomerOnline($conversation['id_conversation']))
                    $conversation['online']=1;
                else
                    $conversation['online']=0;
                $conversation['wait_accept']= LC_Conversation::checkWaitAccept($conversation['id_conversation']);
                $conversation['has_changed']= LC_Conversation::checkHasChanged($conversation['id_conversation']);
            }
        } 
        return $conversations;
    }
    public static function getConversations($all=true,$archive=false,$customer_name='',$lasttime='',$count_conversation=20)
    {
        $ets_livechat= new Ets_livechat();
        $declines = $ets_livechat->getDeclineConversation();
        if(Configuration::get('PS_MULTISHOP_FEATURE_ACTIVE') && Shop::getContext() == Shop::CONTEXT_ALL)
        {
            $all_shop = true;
        }
        else
            $all_shop=false; 
        $context = Context::getContext();
        $sql_message ='SELECT COUNT(DISTINCT m.id_message) as count_message_not_seen,m.id_conversation FROM `'._DB_PREFIX_.'ets_livechat_message` m';
        $sql_message .= ' INNER JOIN `'._DB_PREFIX_.'ets_livechat_conversation` c ON (c.id_conversation=m.id_conversation'.(!$all_shop ? ' AND c.id_shop= "'.(int)$context->shop->id.'"':'').')';
        if(isset($context->employee) && isset($context->employee->id_profile) && $context->employee->id_profile!=1 && LC_Departments::checkDepartments())
        {
            $sql_message .=' LEFT JOIN `'._DB_PREFIX_.'ets_livechat_departments` d ON (d.id_departments=c.id_departments)
            LEFT JOIN `'._DB_PREFIX_.'ets_livechat_departments_employee` de ON (de.id_departments=c.id_departments)';
        } 
        $sql_message .=' WHERE m.id_employee=0 AND (c.date_message_seen_employee="" OR c.date_message_seen_employee IS NULL OR c.date_message_seen_employee="0000-00-00 00:00:00" OR   m.datetime_added > c.date_message_seen_employee)';
        if(isset($context->employee) && isset($context->employee->id_profile) &&  $context->employee->id_profile!=1 && LC_Departments::checkDepartments())
            $sql_message .=' AND (d.all_employees=1 OR c.id_departments=0 OR de.id_employee="'.(int)$context->employee->id.'")';
        $sql_message .=' GROUP BY m.id_conversation';
        if($all)
        {
            $sql = 'SELECT lc.*,messsage.count_message_not_seen, CONCAT(c.firstname," ",c.lastname) as fullname  FROM `'._DB_PREFIX_.'ets_livechat_conversation` lc
                LEFT JOIN `'._DB_PREFIX_.'ets_livechat_departments` ld ON (ld.id_departments=lc.id_departments OR ld.id_departments=lc.id_departments_wait)
                LEFT JOIN `'._DB_PREFIX_.'ets_livechat_departments_employee` lde ON (ld.id_departments=lde.id_departments)
                LEFT JOIN `'._DB_PREFIX_.'employee` e ON (e.id_employee = lde.id_employee)
                LEFT JOIN `'._DB_PREFIX_.'customer` c ON (c.id_customer=lc.id_customer)
                LEFT JOIN ('.(string)$sql_message.') as messsage ON (messsage.id_conversation=lc.id_conversation)
                WHERE 1
                '.(!$ets_livechat->all_shop?'  AND lc.id_shop="'.(int)Context::getContext()->shop->id.'"':'')
                .(Context::getContext()->employee->id_profile!= 1 ? ' AND (e.id_employee="'.(int)Context::getContext()->employee->id.'" OR lc.id_departments=0 OR ld.all_employees=1 OR lc.id_departments_wait=-1)':'')
                .(Configuration::get('ETS_LC_STAFF_ACCEPT') && Context::getContext()->employee->id_profile!=1 ? ' AND (lc.id_employee=0 OR lc.id_employee="'.(int)Context::getContext()->employee->id.'" OR lc.id_employee_wait="'.(int)Context::getContext()->employee->id.'" OR lc.id_employee_wait=-1)':'')
                .(isset($declines) && $declines ? ' AND lc.id_conversation NOT IN ('.implode(',',array_map('intval',$declines)).')':'')
                .($lasttime ? ' AND date_message_last_customer < "'.pSQL($lasttime).'"': '')
                .' GROUP BY lc.id_conversation ORDER BY date_message_last_customer DESC LIMIT 0,'.(int)$count_conversation;
            if($customer_name)
            {
                $sql = 'SELECT lc.*,messsage.count_message_not_seen,CONCAT(c.firstname," ",c.lastname) as fullname,m.message,m.datetime_added FROM `'._DB_PREFIX_.'ets_livechat_conversation` lc
                LEFT JOIN `'._DB_PREFIX_.'ets_livechat_departments` ld ON (ld.id_departments=lc.id_departments OR ld.id_departments=lc.id_departments_wait)
                LEFT JOIN `'._DB_PREFIX_.'ets_livechat_departments_employee` lde ON (ld.id_departments=lde.id_departments)
                LEFT JOIN `'._DB_PREFIX_.'employee` e ON (e.id_employee = lde.id_employee)
                LEFT JOIN `'._DB_PREFIX_.'customer` c ON (c.id_customer=lc.id_customer)
                LEFT JOIN `'._DB_PREFIX_.'ets_livechat_message` m ON(m.id_conversation=lc.id_conversation AND m.id_employee=0)
                LEFT JOIN ('.(string)$sql_message.') as messsage ON (messsage.id_conversation=lc.id_conversation)
                WHERE '.(!$ets_livechat->all_shop?' lc.id_shop="'.(int)Context::getContext()->shop->id.'" AND ':'').'( CONCAT(c.firstname," ",c.lastname) LIKE "%'.pSQL($customer_name).'%" OR lc.customer_name like "%'.pSQL($customer_name).'%" OR m.message like "%'.pSQL($customer_name).'%")'
                .(Context::getContext()->employee->id_profile!= 1 ? ' AND (e.id_employee="'.(int)Context::getContext()->employee->id.'" OR lc.id_departments=0 OR ld.all_employees=1 OR lc.id_departments_wait=-1)':'')
                .(Configuration::get('ETS_LC_STAFF_ACCEPT') && Context::getContext()->employee->id_profile!=1 ? ' AND (lc.id_employee=0 OR lc.id_employee="'.(int)Context::getContext()->employee->id.'" OR lc.id_employee_wait="'.(int)Context::getContext()->employee->id.'" OR lc.id_employee_wait=-1)':'')
                .(isset($declines) && $declines ? ' AND lc.id_conversation NOT IN ('.implode(',',array_map('intval',$declines)).')':'')
                .($lasttime ? ' AND date_message_last_customer < "'.pSQL($lasttime).'"': '')
                .' GROUP BY lc.id_conversation ORDER BY date_message_last_customer DESC LIMIT 0,'.(int)$count_conversation;
                $conversations = Db::getInstance()->executeS($sql);
                if($conversations)
                {
                    foreach($conversations as $key=> &$conversation)
                    {
                        if($conversation['id_customer'] && Tools::strpos(Tools::strtolower($conversation['fullname']),Tools::strtolower($customer_name))===false && Tools::strpos(Tools::strtolower($conversation['message']),Tools::strtolower($customer_name))===false)
                        {
                            unset($conversations[$key]);
                            continue;
                        }
                        else
                        {
                            LC_Conversation::updateMessageStattus($conversation['id_conversation'],true,false,false,'employee');
                            if(LC_Conversation::isCustomerOnline($conversation['id_conversation']))
                                $conversation['online']=1;
                            else
                                $conversation['online']=0;
                            $conversation['wait_accept']= LC_Conversation::checkWaitAccept($conversation['id_conversation']);
                            if(Tools::strpos(Tools::strtolower($conversation['message']),Tools::strtolower($customer_name))!==false)
                            {
                                $conversation['last_message'] = Db::getInstance()->getRow('SELECT * FROM `'._DB_PREFIX_.'ets_livechat_message` WHERE id_conversation="'.(int)$conversation['id_conversation'].'" AND id_employee=0 AND message like "%'.pSQL($customer_name).'%" ORDER BY id_message DESC');
                            }
                            else
                                $conversation['last_message'] = Db::getInstance()->getRow('SELECT * FROM `'._DB_PREFIX_.'ets_livechat_message` WHERE id_conversation="'.(int)$conversation['id_conversation'].'" AND id_employee=0 ORDER BY id_message DESC');
                            if(!$conversation['last_message'])
                            {
                                unset($conversations[$key]);
                                continue;
                            }
                            if(date('Y-m-d')==date('Y-m-d',strtotime($conversation['last_message']['datetime_added'])))
                            {
                                $conversation['last_message']['datetime_added'] =date('h:i A',strtotime($conversation['last_message']['datetime_added']));
                            }
                            else
                            {
                                if(date('Y')==date('Y',strtotime($conversation['last_message']['datetime_added'])))
                                {
                                    $conversation['last_message']['datetime_added'] =date('d-m h:i A',strtotime($conversation['last_message']['datetime_added']));
                                }
                                else
                                    $conversation['last_message']['datetime_added'] =date('d-m-Y h:i A',strtotime($conversation['last_message']['datetime_added']));
                            }
                            if(Tools::strpos(Tools::strtolower($conversation['customer_name']),Tools::strtolower($customer_name))!==false)
                            {
                                $conversation['customer_name'] = str_replace(Tools::strtolower($customer_name),Module::getInstanceByName('ets_livechat')->displayText($customer_name,'span','search_text'),Tools::strtolower($conversation['customer_name']));
                            }
                            if(Tools::strpos(Tools::strtolower($conversation['fullname']),Tools::strtolower($customer_name))!==false)
                            {
                                $conversation['fullname'] = str_replace(Tools::strtolower($customer_name),Module::getInstanceByName('ets_livechat')->displayText($customer_name,'span','search_text'),Tools::strtolower($conversation['fullname']));
                            }
                            if(Tools::strpos(Tools::strtolower($conversation['last_message']['message']),Tools::strtolower($customer_name))!==false)
                            {
                                $conversation['last_message']['message'] = str_replace(Tools::strtolower($customer_name),Module::getInstanceByName('ets_livechat')->displayText($customer_name,'span','search_text'),Tools::strtolower($conversation['last_message']['message']));
                            }
                            if($ets_livechat->emotions)
                            {
                                foreach($ets_livechat->emotions as $key=> $emotion)
                                {
                                    $img = Module::getInstanceByName('ets_livechat')->displayText(Module::getInstanceByName('ets_livechat')->displayText('','img','',array('src'=>Context::getContext()->link->getMediaLink(_MODULE_DIR_.'ets_livechat/views/img/emotions/'.$emotion['img']))),'span','',array('title'=>$emotion['title']));

                                    $conversation['last_message']['message'] = str_replace(array(Tools::strtolower($key),$key),array($img,$img),$conversation['last_message']['message']);
                                }
                            }
                            if($conversation['last_message'] && $attachment= Db::getInstance()->getRow('SELECT * FROM `'._DB_PREFIX_.'ets_livechat_download` WHERE id_download="'.(int)$conversation['last_message']['type_attachment'].'" AND id_message="'.(int)$conversation['last_message']['id_message'].'"'))
                            {
                                $context=Context::getContext();
                                if(isset($context->employee) && $context->employee->id)
                                    $linkdownload= $ets_livechat->getBaseLink().'/modules/ets_livechat/download.php?downloadfile='.md5(_COOKIE_KEY_.$conversation['last_message']['type_attachment']);
                                else
                                    $linkdownload = $context->link->getModuleLink('ets_livechat','download',array('downloadfile'=>md5(_COOKIE_KEY_.$conversation['last_message']['type_attachment'])));

                                $conversation['last_message']['message'] .= ($conversation['last_message']['message'] ? Module::getInstanceByName('ets_livechat')->displayText('|&lt;','span',''):'')
                                    .Module::getInstanceByName('ets_livechat')->displayText(Module::getInstanceByName('ets_livechat')->displayText($conversation['last_message']['name_attachment'].($attachment['file_size'] ? Module::getInstanceByName('ets_livechat')->displayText('('.($attachment['file_size'] >=1024 ? Tools::ps_round($attachment['file_size']/1024,2):$attachment['file_size']).' '.($attachment['file_size'] >= 1024 ? 'MB':'KB').')','span','file_size'):''),'a','file_sent',array('href'=>$linkdownload,'target'=>'_blank')),'span','file_message');
                            }
                        }
                    }
                } 
                return $conversations;
            }
        }
        else
        {
            $sql = 'SELECT lc.*,messsage.count_message_not_seen,CONCAT(c.firstname," ",c.lastname) as fullname  FROM `'._DB_PREFIX_.'ets_livechat_conversation` lc
                LEFT JOIN `'._DB_PREFIX_.'ets_livechat_departments` ld ON (ld.id_departments=lc.id_departments OR ld.id_departments=lc.id_departments_wait)
                LEFT JOIN `'._DB_PREFIX_.'ets_livechat_departments_employee` lde ON (ld.id_departments=lde.id_departments)
                LEFT JOIN `'._DB_PREFIX_.'employee` e ON (e.id_employee = lde.id_employee)
                LEFT JOIN `'._DB_PREFIX_.'customer` c ON (c.id_customer=lc.id_customer)
                LEFT JOIN ('.(string)$sql_message.') as messsage ON (messsage.id_conversation=lc.id_conversation)
                WHERE '
                .(!$ets_livechat->all_shop ? ' lc.id_shop="'.(int)Context::getContext()->shop->id.'" AND ':'')
                .' archive ='.($archive?'1':'0')
                .(Context::getContext()->employee->id_profile!= 1 ? ' AND (e.id_employee="'.(int)Context::getContext()->employee->id.'" OR lc.id_departments=0 OR ld.all_employees=1 OR lc.id_departments_wait=-1)':'')
                .(Configuration::get('ETS_LC_STAFF_ACCEPT')  && Context::getContext()->employee->id_profile!=1 ? ' AND (lc.id_employee=0 OR lc.id_employee="'.(int)Context::getContext()->employee->id.'" OR lc.id_employee_wait="'.(int)Context::getContext()->employee->id.'" OR lc.id_employee_wait=-1)':'')
                .(isset($declines) && $declines ? ' AND lc.id_conversation NOT IN ('.implode(',',array_map('intval',$declines)).')':'')
                .($lasttime ? ' AND date_message_last_customer < "'.pSQL($lasttime).'"': '')
                .' GROUP BY lc.id_conversation ORDER BY date_message_last_customer DESC LIMIT 0,'.(int)$count_conversation;
        }
        $conversations = Db::getInstance()->executeS($sql);
        if($conversations)
        {
            foreach($conversations as $key=> &$conversation)
            {
                
                LC_Conversation::updateMessageStattus($conversation['id_conversation'],true,false,false,'employee');
                if(LC_Conversation::isCustomerOnline($conversation['id_conversation']))
                    $conversation['online']=1;
                else
                    $conversation['online']=0;
                    $conversation['wait_accept']= LC_Conversation::checkWaitAccept($conversation['id_conversation']);
                $conversation['has_changed']=LC_Conversation::checkHasChanged($conversation['id_conversation']);
                $conversation['last_message'] = Db::getInstance()->getRow('SELECT * FROM `'._DB_PREFIX_.'ets_livechat_message` WHERE id_conversation="'.(int)$conversation['id_conversation'].'" AND id_employee=0 ORDER BY id_message DESC');
                if($conversation['last_message'])
                {
                    if(date('Y-m-d')==date('Y-m-d',strtotime($conversation['last_message']['datetime_added'])))
                    {
                        $conversation['last_message']['datetime_added'] =date('h:i A',strtotime($conversation['last_message']['datetime_added']));
                    }
                    else
                    {
                       if(date('Y')==date('Y',strtotime($conversation['last_message']['datetime_added'])))
                       {
                            $conversation['last_message']['datetime_added'] =date('d-m h:i A',strtotime($conversation['last_message']['datetime_added']));
                       }
                       else
                            $conversation['last_message']['datetime_added'] =date('d-m-Y h:i A',strtotime($conversation['last_message']['datetime_added']));
                    }
                    if($ets_livechat->emotions)
                    {
                        foreach($ets_livechat->emotions as $key=> $emotion)
                        {
                            $img = Module::getInstanceByName('ets_livechat')->displayText(
                            Module::getInstanceByName('ets_livechat')->displayText(
                            '','img','',array('src'=>Context::getContext()->link->getMediaLink(_MODULE_DIR_.'ets_livechat/views/img/emotions/'.$emotion['img'])) ),'span','',array('title'=>$emotion['title']));
                            $conversation['last_message']['message'] = str_replace(array(Tools::strtolower($key),$key),array($img,$img),$conversation['last_message']['message']);
                        }
                    }
                    if($conversation['last_message'] && $attachment= Db::getInstance()->getRow('SELECT * FROM `'._DB_PREFIX_.'ets_livechat_download` WHERE id_download="'.(int)$conversation['last_message']['type_attachment'].'" AND id_message="'.(int)$conversation['last_message']['id_message'].'"'))
                    {
                        $context=Context::getContext();
                        if(isset($context->employee) && $context->employee->id)
                            $linkdownload= $ets_livechat->getBaseLink().'/modules/ets_livechat/download.php?downloadfile='.md5(_COOKIE_KEY_.$conversation['last_message']['type_attachment']);
                        else
                            $linkdownload = $context->link->getModuleLink('ets_livechat','download',array('downloadfile'=>md5(_COOKIE_KEY_.$conversation['last_message']['type_attachment'])));

                        $conversation['last_message']['message'] .= ($conversation['last_message']['message'] ? Module::getInstanceByName('ets_livechat')->displayText('|&lt;','span',''):'')
                            .Module::getInstanceByName('ets_livechat')->displayText(Module::getInstanceByName('ets_livechat')->displayText($conversation['last_message']['name_attachment'],'a','file_sent',array('href'=>$linkdownload,'target'=>'_blank'))
                                .($attachment['file_size'] ? Module::getInstanceByName('ets_livechat')->displayText('('.($attachment['file_size'] >=1024 ? Tools::ps_round($attachment['file_size']/1024,2):$attachment['file_size']).' '.($attachment['file_size'] >= 1024 ? 'MB':'KB').')','span','file_size'):''),'span','file_message');
                    }
                }
                else
                    unset($conversations[$key]);
                
            }
        }
        return $conversations;
    }
    public static function updateMessageStattus($id_conversation,$delevered=false,$viewed=false,$wirting=false,$type='customer')
    {
        $date= date('Y-m-d H:i:s');
        if($id_conversation && ($delevered || $viewed|| $wirting))
        {
            $conversation= new LC_Conversation($id_conversation);
            if($type=='customer')
            {
                if($viewed)
                    $conversation->date_message_seen_customer= $date;
                if($delevered)
                    $conversation->date_message_delivered_customer= $date;
                if($wirting)
                    $conversation->date_message_writing_customer = $date;
            }
            else
            {
                if($viewed)
                    $conversation->date_message_seen_employee=$date;
                if($delevered)
                    $conversation->date_message_delivered_employee=$date;
                if($wirting)
                {
                    $conversation->employee_writing=1;
                    $conversation->date_message_writing_employee = $date;
                }
                    
            }
            $conversation->update();
        }
    }
    public static function isCustomerSeen($id_conversation)
    {
        $date_lastview = Db::getInstance()->getValue('SELECT date_message_seen_customer FROM `'._DB_PREFIX_.'ets_livechat_conversation` WHERE id_conversation ='.(int)$id_conversation);
        if($date_lastview && $date_lastview!='0000-00-00 00:00:00')
        {
            $messages = Db::getInstance()->executeS('SELECT * FROM `'._DB_PREFIX_.'ets_livechat_message` WHERE id_conversation="'.(int)$id_conversation.'" AND id_employee!=0 AND datetime_added >"'.pSQL($date_lastview).'"');
            if(Count($messages))
                return false;
            else
                return true;
        }
        return false;
    }
    public static function isCustomerDelivered($id_conversation)
    {
        $date_delivered = Db::getInstance()->getValue('SELECT date_message_delivered_customer FROM `'._DB_PREFIX_.'ets_livechat_conversation` WHERE id_conversation ='.(int)$id_conversation);
        if($date_delivered && $date_delivered!='0000-00-00 00:00:00')
        {
            $messages = Db::getInstance()->executeS('SELECT * FROM `'._DB_PREFIX_.'ets_livechat_message` WHERE id_conversation="'.(int)$id_conversation.'" AND id_employee!=0 AND datetime_added >"'.pSQL($date_delivered).'"');
            if(Count($messages))
                return false;
            else
                return true;
        }
        return false;
    }
    public static function isCustomerWriting($id_conversation)
    {
        $date_writing = Db::getInstance()->getValue('SELECT date_message_writing_customer FROM `'._DB_PREFIX_.'ets_livechat_conversation` WHERE id_conversation ='.(int)$id_conversation.' AND customer_writing=1');
        $refresh_speed=((int)Configuration::get('ETS_LC_TIME_OUT')+(int)Configuration::get('ETS_LC_TIME_OUT_BACK_END'))/1000;
        if($date_writing && $date_writing!='0000-00-00 00:00:00')
        {
            if(strtotime('NOW') - strtotime($date_writing)<=$refresh_speed)
            {
                return true;
            }
        }
        return false;
    }
    public static function isCustomerSent($id_conversation)
    {
        $mesages = Db::getInstance()->executeS('SELECT * FROm `'._DB_PREFIX_.'ets_livechat_message` WHERE id_conversation="'.(int)$id_conversation.'" AND id_employee!=0');
        if(count($mesages))
            return true;
        return false;
    }
    public static function isEmployeeSeen($id_conversation)
    {
        $date_lastview = Db::getInstance()->getValue('SELECT date_message_seen_employee FROM `'._DB_PREFIX_.'ets_livechat_conversation` WHERE id_conversation ='.(int)$id_conversation);
        if($date_lastview && $date_lastview!='0000-00-00 00:00:00')
        {
            $messages = Db::getInstance()->executeS('SELECT * FROM `'._DB_PREFIX_.'ets_livechat_message` WHERE id_conversation="'.(int)$id_conversation.'" AND id_employee=0 AND datetime_added >"'.pSQL($date_lastview).'"');
            if(count($messages))
                return false;
            else
                return true;
        }
        return false;
    }
    public static function isEmployeeDelivered($id_conversation)
    {
        $date_delivered = Db::getInstance()->getValue('SELECT date_message_delivered_employee FROM `'._DB_PREFIX_.'ets_livechat_conversation` WHERE id_conversation ='.(int)$id_conversation);
        if($date_delivered && $date_delivered!='0000-00-00 00:00:00')
        {
            $messages = Db::getInstance()->executeS('SELECT * FROM `'._DB_PREFIX_.'ets_livechat_message` WHERE id_conversation="'.(int)$id_conversation.'" AND id_employee=0 AND datetime_added >"'.pSQL($date_delivered).'"');
            if(count($messages))
                return false;
            else
                return true;
        }
        return false;
    }
    public static function isEmployeeSent($id_conversation)
    {
        $mesages = Db::getInstance()->executeS('SELECT * FROm `'._DB_PREFIX_.'ets_livechat_message` WHERE id_conversation="'.(int)$id_conversation.'" AND id_employee=0');
        if(count($mesages))
            return true;
        return false;
    }
    public static function isEmployeeWriting($id_conversation)
    {
        $refresh_speed=((int)Configuration::get('ETS_LC_TIME_OUT')+(int)Configuration::get('ETS_LC_TIME_OUT_BACK_END'))/1000;
        $date_writing = Db::getInstance()->getValue('SELECT date_message_writing_employee FROM `'._DB_PREFIX_.'ets_livechat_conversation` WHERE id_conversation ='.(int)$id_conversation.' AND employee_writing=1');
        if($date_writing && $date_writing!='0000-00-00 00:00:00')
        {
            if(strtotime('now') - strtotime($date_writing)<=$refresh_speed)
            {
                return true;
            }
        }
        return false;
    }
    public function isJquestAjax()
    {
        $timeout = (int)Configuration::get('ETS_LC_ONLINE_TIMEOUT')*60;
        if((int)Configuration::get('ETS_LC_AUTO_FRONTEND_SPEED'))
            $timeout =$timeout*4;
        $timeout2= ceil($timeout/2);
        $timeout3 = ceil($timeout/3);
        $timeout4 = ceil($timeout/4);
        if(strtotime("now") < strtotime($this->date_message_last)+$timeout)
        {
            if(!(int)Configuration::get('ETS_LC_AUTO_FRONTEND_SPEED'))
                return 1;
            if(strtotime("now") < strtotime($this->date_message_last)+$timeout4)
                return 1;
            if(strtotime("now") < strtotime($this->date_message_last)+$timeout3)
                return 2;
            if(strtotime("now") < strtotime($this->date_message_last)+$timeout2)
                return 3;
            return 4;
        }
        else
            return 0;
    }
    public static function getMessagesEmployeeNotSeen($id_conversation)
    {
        $conversation = new LC_Conversation($id_conversation);
        $date_lastview =$conversation->date_message_seen_employee;
        $context= Context::getContext();
        if($date_lastview && $date_lastview!='0000-00-00 00:00:00')
        {
            $sql ='SELECT COUNT(DISTINCT m.id_message) FROM `'._DB_PREFIX_.'ets_livechat_message` m';
            if(isset($context->employee) && isset($context->employee->id_profile) && $context->employee->id_profile!=1 && LC_Departments::checkDepartments())
            {
                $sql .=' LEFT JOIN `'._DB_PREFIX_.'ets_livechat_conversation` c ON (c.id_conversation=m.id_conversation)
                LEFT JOIN `'._DB_PREFIX_.'ets_livechat_departments` d ON (d.id_departments=c.id_departments)
                LEFT JOIN `'._DB_PREFIX_.'ets_livechat_departments_employee` de ON (de.id_departments=c.id_departments)';
            } 
            $sql .=' WHERE m.id_conversation="'.(int)$id_conversation.'" AND m.id_employee=0 AND m.datetime_added >"'.pSQL($date_lastview).'"';
            if(isset($context->employee) && isset($context->employee->id_profile) &&  $context->employee->id_profile!=1 && LC_Departments::checkDepartments())
                $sql .=' AND (d.all_employees=1 OR c.id_departments=0 OR de.id_employee="'.(int)$context->employee->id.'")';
            return Db::getInstance()->getValue($sql);
        }
        else
        {
            $sql ='SELECT COUNT(DISTINCT m.id_message) FROM `'._DB_PREFIX_.'ets_livechat_message` m ';
            if(isset($context->employee) && isset($context->employee->id_profile) && $context->employee->id_profile!=1 && LC_Departments::checkDepartments())
            {
                $sql .=' LEFT JOIN `'._DB_PREFIX_.'ets_livechat_conversation` c ON (c.id_conversation=m.id_conversation)
                LEFT JOIN `'._DB_PREFIX_.'ets_livechat_departments` d ON (d.id_departments=c.id_departments)
                LEFT JOIN `'._DB_PREFIX_.'ets_livechat_departments_employee` de ON (de.id_departments=c.id_departments)';
            } 
            $sql .='WHERE m.id_conversation="'.(int)$id_conversation.'" AND m.id_employee=0';
            if(isset($context->employee) && isset($context->employee->id_profile) &&  $context->employee->id_profile!=1 && LC_Departments::checkDepartments())
                $sql .=' AND (d.all_employees=1 OR c.id_departments=0 OR de.id_employee="'.(int)$context->employee->id.'")';
            return Db::getInstance()->getValue($sql);
        }
    }
    public static function getTotalMessageNoSeen()
    {
        $context= Context::getContext();
        if(Configuration::get('PS_MULTISHOP_FEATURE_ACTIVE') && Shop::getContext() == Shop::CONTEXT_ALL)
        {
            $all_shop = true;
        }
        else
            $all_shop=false; 
        $sql ='SELECT COUNT(DISTINCT m.id_message) FROM `'._DB_PREFIX_.'ets_livechat_message` m';
        $sql .= ' INNER JOIN `'._DB_PREFIX_.'ets_livechat_conversation` c ON (c.id_conversation=m.id_conversation'.(!$all_shop ? ' AND c.id_shop= "'.(int)$context->shop->id.'"':'').')';
        if(isset($context->employee) && isset($context->employee->id_profile) && $context->employee->id_profile!=1 && LC_Departments::checkDepartments())
        {
            $sql .=' LEFT JOIN `'._DB_PREFIX_.'ets_livechat_departments` d ON (d.id_departments=c.id_departments)
            LEFT JOIN `'._DB_PREFIX_.'ets_livechat_departments_employee` de ON (de.id_departments=c.id_departments)';
        } 
        $sql .=' WHERE m.id_employee=0 AND (c.date_message_seen_employee="" OR c.date_message_seen_employee IS NULL OR c.date_message_seen_employee="0000-00-00 00:00:00" OR   m.datetime_added > c.date_message_seen_employee)';
        if(isset($context->employee) && isset($context->employee->id_profile) &&  $context->employee->id_profile!=1 && LC_Departments::checkDepartments())
            $sql .=' AND (d.all_employees=1 OR c.id_departments=0 OR de.id_employee="'.(int)$context->employee->id.'")';
        return Db::getInstance()->getValue($sql);    
    }
    public static function getMessagesCustomerNotSeen($id_conversation)
    {
        $date_lastview = Db::getInstance()->getValue('SELECT date_message_seen_customer FROM `'._DB_PREFIX_.'ets_livechat_conversation` WHERE id_conversation ='.(int)$id_conversation);
        if($date_lastview && $date_lastview!='0000-00-00 00:00:00')
        {
            $messages = Db::getInstance()->executeS('SELECT * FROM `'._DB_PREFIX_.'ets_livechat_message` WHERE id_conversation="'.(int)$id_conversation.'" AND id_employee!=0 AND datetime_added >"'.pSQL($date_lastview).'"');
            if(count($messages))
                return count($messages);
            else
                return 0;
        }
        else
        {
            $messages = Db::getInstance()->executeS('SELECT * FROM `'._DB_PREFIX_.'ets_livechat_message` WHERE id_conversation="'.(int)$id_conversation.'" AND id_employee!=0');
            if(count($messages))
                return count($messages);
            else
                return 0;
        }
            
    }
    public static function isCustomerOnline($id_conversation)
    {
        $timeout= (int)Configuration::get('ETS_LC_TIME_OUT_BACK_END')*3/1000+(int)Configuration::get('ETS_LC_TIME_OUT')*3/1000;
        $conversation = new LC_Conversation($id_conversation);
        if($conversation->end_chat)
            return false;
        return  strtotime('now') < strtotime($conversation->latest_online)+$timeout;
    }
    public static function sendEmail($id_conversation)
    {
        if(!Configuration::get('ETS_LC_SEND_MAIL_WHEN_SEND_MG') || Ets_livechat::isAdminOnlineNoForce()||!Configuration::get('ETS_LC_MAIL_TO'))
            return false;
        $send_mail=false;
        $messages=array();
        $when_send_email =explode(',',Configuration::get('ETS_LC_SEND_MAIL'));
        if(in_array('first_message',$when_send_email))
        {
            $messages = Db::getInstance()->executeS('SELECT * FROM `'._DB_PREFIX_.'ets_livechat_message` WHERE id_conversation="'.(int)$id_conversation.'"');
            if(count($messages)==1)
                $send_mail=true;
        }
        if(in_array('affter_a_centaint_time',$when_send_email))
        {
            $hours = (float)Configuration::get('ETS_CENTAINT_TIME_SEND_EMAIL');
            $timeout = $hours*60*60;
            $date_mail_last = Db::getInstance()->getValue('SELECT date_mail_last FROM `'._DB_PREFIX_.'ets_livechat_conversation` WHERE id_conversation='.(int)$id_conversation);
            if((int)$timeout>0)
            {
                $messages = Db::getInstance()->executeS("SELECT * FROM `"._DB_PREFIX_."ets_livechat_message` WHERE id_conversation=".(int)$id_conversation." AND datetime_added >'".pSQL(date('Y-m-d H:i:s', strtotime('-'.(int)$timeout.' seconds')))."' ORDER BY id_message");
                if($messages)
                {
                    $employee_message=true; 
                    foreach($messages as $message)
                    {
                        if($message['id_employee'])
                            $employee_message= false;
                    }
                    if($employee_message)
                    {
                        if($date_mail_last==''||$date_mail_last=='0000-00-00 00:00:00')
                        {       
                            $first_datetime_added = Db::getInstance()->getValue('SELECT datetime_added FROM `'._DB_PREFIX_.'ets_livechat_message` WHERE id_conversation='.(int)$id_conversation.' order by id_message asc');
                            if(strtotime('now') > strtotime($first_datetime_added)+$timeout)
                                $send_mail= true;
                        }
                        else{
                            if(strtotime('now') > strtotime($date_mail_last)+$timeout)
                                $send_mail= true;
                        }
                    }
                }
            }
        }
        if($send_mail && $messages)
        {
            if($messages)
            {
                $ets_livechat= new Ets_livechat();
                foreach($messages as &$message)
                {
                    if($ets_livechat->emotions)
                    {
                        foreach($ets_livechat->emotions as $key=> $emotion)
                        {
                            $img = Module::getInstanceByName('ets_livechat')->displayText(Module::getInstanceByName('ets_livechat')->displayText('','img','',array('src'=>Context::getContext()->link->getMediaLink(_MODULE_DIR_.'ets_livechat/views/img/emotions/'.$emotion['img']))),'span','',array('title'=>$emotion['title']));
                            $message['message'] = str_replace(array(Tools::strtolower($key),$key),array($img,$img),$message['message']);
                        }
                    }
                    if($message['name_attachment'] && $attachment= Db::getInstance()->getRow('SELECT * FROM `'._DB_PREFIX_.'ets_livechat_download` WHERE id_download="'.(int)$message['type_attachment'].'" AND id_message="'.(int)$message['id_message'].'"'))
                    {
                        $linkdownload= $ets_livechat->getBaseLink().'/modules/ets_livechat/download.php?downloadfile='.md5(_COOKIE_KEY_.$message['type_attachment']);

                        $message['message'] .= ($message['message'] ? Module::getInstanceByName('ets_livechat')->displayText('','br',''):'').Module::getInstanceByName('ets_livechat')->displayText(Module::getInstanceByName('ets_livechat')->displayText($message['name_attachment'],'a','file_sent',array('href'=>$linkdownload,'target'=>'_blank')).($attachment['file_size'] ? Module::getInstanceByName('ets_livechat')->displayText('('.($attachment['file_size'] >=1024 ? Tools::ps_round($attachment['file_size']/1024,2):$attachment['file_size']).' '.($attachment['file_size'] >= 1024 ? 'MB':'KB').')','span','file_size') :''),'span','file_message');
                    }
                }
            }
            $emails= array();
            $email_to = explode(',',Configuration::get('ETS_LC_MAIL_TO'));
            if(in_array('shop',$email_to))
                $emails[]=Configuration::get('PS_SHOP_EMAIL');
            if(in_array('employee',$email_to))
            {
                $employees = Db::getInstance()->executeS('SELECT * FROM `'._DB_PREFIX_.'employee` e,`'._DB_PREFIX_.'employee_shop` es WHERE e.id_employee=es.id_employee AND es.id_shop="'.(int)Context::getContext()->shop->id.'" AND active=1');
                if($employees)
                    foreach($employees as $employee)
                    {
                        if(!in_array($employee['email'],$emails))
                            $emails[]=$employee['email'];
                    }
            }
            if(in_array('custom',$email_to))
            {
                $emails_custom = explode(',',Configuration::get('ETS_LC_CUSTOM_EMAIL'));
                if($emails_custom)
                {
                    foreach($emails_custom as $email)
                    {
                        if(!in_array($email,$emails))
                            $emails[]=$email;
                    }
                }
            }
            if($emails)
            {
                $ets_livechat = new Ets_livechat();
                $template_vars =array(
                    '{messages}'=>$ets_livechat->getTemplateEmail($messages),
                    '{link_admin}' =>Configuration::get('ETS_DIRECTORY_ADMIN_URL')? Module::getInstanceByName('ets_livechat')->displayText('Log into back office','a','button_link button_directory_admin_url',array('target'=>'_blank','href'=>Tools::getShopDomainSsl(true).Context::getContext()->shop->getBaseURI().Configuration::get('ETS_DIRECTORY_ADMIN_URL'))):'',
                    '{customer_info}' =>$ets_livechat->displayCucstomerInfo($id_conversation),
                    '{year}' =>date('Y'),
                );
                $conversation = new LC_Conversation($id_conversation);
                if($conversation->id_customer)
                {
                    $customer= new Customer($conversation->id_customer);
                    $customer_name = $customer->firstname.' '.$customer->lastname;
                    $from = $customer->email;
                }
                else
                {
                    $customer_name = $conversation->customer_name;
                    $from = $conversation->customer_email?$conversation->customer_email:null;
                }
                foreach($emails as $email)
                {
                    if($email && file_exists(dirname(__FILE__).'/../mails/'.Context::getContext()->language->iso_code.'/new_message.html'))
                    {
                        Mail::Send(
        					Configuration::get('PS_LANG_DEFAULT'),
        					'new_message',
                            ($ets_livechat->getTextLang('New Message from',Configuration::get('PS_LANG_DEFAULT'),'LC_Conversation') ? : $ets_livechat->l('New Message from','LC_Conversation')).' '.$customer_name,
        					$template_vars,
        					$email,
        					null,
        					$from,
        					$customer_name,
        					null,
        					null,
        					dirname(__FILE__).'/../mails/',
        					null,
        					Context::getContext()->shop->id
        				);
                    }
                }
            }
        }
        return false;
    }
    public static function lastMessageIsEmployee($id_conversation) 
    {
        $message = Db::getInstance()->getRow('SELECT * FROM `'._DB_PREFIX_.'ets_livechat_message` WHERE id_conversation="'.(int)$id_conversation.'" order by id_message desc');
        if($message && isset($message['id_employee']) && $message['id_employee'])
            return true;
        else
            return false;
    }
    public static function getLastMessageOfEmployee($id_conversation)
    {
        return Db::getInstance()->getRow('SELECT m.id_message,m.message,m.id_employee,m.datetime_added,m.datetime_edited,CONCAT(e.firstname," ",e.lastname) as employee_name,e.email FROM `'._DB_PREFIX_.'ets_livechat_message` m, `'._DB_PREFIX_.'employee` e WHERE m.id_employee= e.id_employee AND m.id_conversation='.(int)$id_conversation.' ORDER BY m.datetime_added desc');
    }
    public static function getLevelRequestAdmin()
    {
        if(!(int)Configuration::get('ETS_LC_AUTO_BACKEND_SPEED'))
            return 1;
        $timeout = 3600;
        $timeout2= ceil($timeout/2);
        $timeout3 = ceil($timeout/3);
        $timeout4 = ceil($timeout/4);
        $date_action_last = Configuration::get('ETS_LC_DATE_ACTION_LAST');
        if(strtotime("now") < strtotime($date_action_last)+$timeout4)
            return 1;
        if(strtotime("now") < strtotime($date_action_last)+$timeout3)
            return 2;
        if(strtotime("now") < strtotime($date_action_last)+$timeout2)
            return 3;
        return 4;
    }
    public function update($null_values = false)
    {
        return parent::update($null_values);
    }

    public static function updateConversationAfterRegister($conversation,$id_customer)
    {
        if ($oldConversation = LC_Conversation::getConversationByIdCustomer($id_customer)) {
            if ($oldConversation->chatref)
                Db::getInstance()->execute('UPDATE `' . _DB_PREFIX_ . 'ets_livechat_conversation` SET chatref="' . (int)$oldConversation->chatref . '" WHERE chatref=' . (int)$conversation->chatref);
            else
                Db::getInstance()->execute('UPDATE `' . _DB_PREFIX_ . 'ets_livechat_conversation` SET chatref="' . (int)$conversation->chatref . '" WHERE id_customer=' . (int)$id_customer);
        }
        $conversation->id_customer = $id_customer;
        $conversation->update();
        return true;
    }
    public static function checkCaptcha($type)
    {
        if (!$type || !($str = Configuration::get('ETS_LC_CAPTCHA')) || $str && !($types = explode(',', $str)) || $types && !is_array($types))
            return false;
        return in_array($type, $types);
    }
    public static function isCustomerLoggedIn()
    {
        $context = Context::getContext();
        return isset($context->customer->id) && (int)$context->customer->id && $context->customer->isLogged();
    }
    public static function isAdminForcedOnline()
    {
        if (Configuration::get('ETS_LC_FORCE_ONLINE')) {
            $day = date('N');
            $hours = date('H');
            if ((Configuration::get('ETS_LC_FORCED_ONLINE_DAY') == 'all' || (Configuration::get('ETS_LC_FORCED_ONLINE_DAY') && in_array($day, explode(',', Configuration::get('ETS_LC_FORCED_ONLINE_DAY'))))) && (Configuration::get('ETS_LC_FORCED_ONLINE_HOURS') == 'all' || (Configuration::get('ETS_LC_FORCED_ONLINE_HOURS') && in_array($hours, explode(',', Configuration::get('ETS_LC_FORCED_ONLINE_HOURS')))))) {
                return true;
            }
        }
        return false;
    }
    public static function isAdminOnline()
    {
        if (self::isAdminForcedOnline())
            return 'online';
        $conversation = LC_Conversation::getCustomerConversation();
        $timeout = (int)Configuration::get('ETS_LC_TIME_OUT_BACK_END') * 5 / 1000 + (int)Configuration::get('ETS_LC_TIME_OUT') * 5 / 1000;
        $currenttime = strtotime(date('Y-m-d H:i:s'));
        $datetime = date('Y-m-d H:i:s', $currenttime - $timeout);
        $employees = Db::getInstance()->executeS('SELECT * FROM `' . _DB_PREFIX_ . 'ets_livechat_employee_online` WHERE date_online >="' . pSQL($datetime) . '"' . ($conversation && $conversation->id_employee ? ' AND id_employee=' . (int)$conversation->id_employee : ''));
        if ($employees) {
            $employee_status = array(
                'online' => false,
                'do_not_disturb' => false,
                'invisible' => false,
            );
            foreach ($employees as $employee) {
                $status = Db::getInstance()->getValue('SELECT status FROM `' . _DB_PREFIX_ . 'ets_livechat_employee_status` WHERE id_employee =' . (int)$employee['id_employee'] . ' AND id_shop=' . (int)Context::getContext()->shop->id);
                if (isset($employee_status[$status]))
                    $employee_status[$status] = true;
            }
            foreach ($employee_status as $k => $v)
                if ($v)
                    return $k;
            return 0;
        } else
            return 0;
    }
    public static function needCaptcha()
    {
        $cache_key = 'LC_Conversation::neddCaptcha';
        if(Cache::isStored($cache_key))
            return Cache::retrieve($cache_key);
        $conversation = LC_Conversation::getCustomerConversation();
        if ($conversation && $conversation->captcha_enabled)
        {
            cache::store($cache_key,true);
            return true;
        }
        $latest_ip = Tools::getRemoteAddr();
        $count_conversation = count(Db::getInstance()->executeS('SELECT * FROM `' . _DB_PREFIX_ . 'ets_livechat_conversation` WHERE latest_ip="' . pSQL($latest_ip) . '" AND datetime_added>="' . pSQL(date('Y-m-d H:i:s', strtotime('-1 MINUTE'))) . '"'));
        if ($count_conversation >= 10)
        {
            cache::store($cache_key,true);
            return true;
        }
        if (!Configuration::get('ETS_LC_CAPTCHA'))
        {
            Cache::store($cache_key,false);
            return false;
        }
        $isAdminOnline = self::isAdminOnline();
        $conversation = LC_Conversation::getCustomerConversation();
        $generalCheck = self::checkCaptcha('always') || (self::checkCaptcha('first') && !$conversation) || (!$isAdminOnline && self::checkCaptcha('fromsecond') && $conversation) || !self::isCustomerLoggedIn() && self::checkCaptcha('notlog') || !self::isCustomerLoggedIn() && self::checkCaptcha('secondnotlogin') && $conversation;
        if (!$generalCheck && $conversation && $conversation->id && self::checkCaptcha('auto')) {
            $messages = Db::getInstance()->executeS("SELECT id_message,id_employee FROM `" . _DB_PREFIX_ . "ets_livechat_message` WHERE id_conversation=" . (int)$conversation->id . " AND datetime_added >'" . pSQL(date('Y-m-d H:i:s', strtotime('-1 minute'))) . "' ORDER BY id_message DESC limit 0,10");
            if (count($messages) < 10)
            {
                Cache::store($cache_key,false);
                return false;
            }
            if ($messages) {
                foreach ($messages as $message) {
                    if ($message['id_employee'])
                    {
                        Cache::store($cache_key,false);
                        return false;
                    }
                }
                Cache::store($cache_key,true);
                return true;
            }

        }
        Cache::store($cache_key,$generalCheck);
        return $generalCheck;
    }
    public static function getTimeWait($id_conversation = 0)
    {
        if (!$id_conversation) {
            $conversation = self::getCustomerConversation();
            if ($conversation)
                $id_conversation = $conversation->id;
        }
        $timeFirstMessage = Db::getInstance()->getValue('SELECT MIN(datetime_added) FROM `' . _DB_PREFIX_ . 'ets_livechat_message` WHERE id_conversation=' . (int)$id_conversation);
        $timeWait = Configuration::get('ETS_LC_TIME_WAIT') ? Configuration::get('ETS_LC_TIME_WAIT') * 60 : 0;
        if (!$timeWait)
            return true;
        if (strtotime('now') < strtotime($timeFirstMessage) + $timeWait)
            return strtotime($timeFirstMessage) + $timeWait - strtotime('now');
    }
    public static function isAdminBusy()
    {
        $conversation = LC_Conversation::getCustomerConversation();
        if ($conversation && !self::getTimeWait() && !$conversation->end_chat) {
            if ($conversation->id_employee)
                return false;
            else
                return !Db::getInstance()->getRow('SELECT * FROM `' . _DB_PREFIX_ . 'ets_livechat_message` WHERE id_conversation=' . (int)$conversation->id . ' AND id_employee!=0');
        } elseif ($conversation && LC_Conversation::getTimeWait() && !$conversation->end_chat) {
            if (self::isAdminForcedOnline())
                return false;
            $sql = 'SELECT e.id_employee FROM `' . _DB_PREFIX_ . 'employee` e';
            if ($conversation->id_departments) {
                $sql .= ' LEFT JOIN `' . _DB_PREFIX_ . 'ets_livechat_departments_employee` de ON (de.id_employee=e.id_employee)';
                $sql .= ' LEFT JOIN `' . _DB_PREFIX_ . 'ets_livechat_departments` d ON (d.all_employees=1 OR d.id_departments = de.id_departments)';
            }
            $sql .= ' WHERE e.active=1 AND e.id_employee NOT IN (SELECT id_employee FROM `' . _DB_PREFIX_ . 'ets_livechat_staff_decline` WHERE id_conversation="' . (int)$conversation->id . '")';
            if ($conversation->id_departments) {
                $sql .= ' AND (e.id_profile=1 OR d.id_departments="' . (int)$conversation->id_departments . '")';
            }
            $employees = Db::getInstance()->executeS($sql);
            if ($employees) {
                foreach ($employees as $employee) {
                    $date_online = Db::getInstance()->getValue('SELECT date_online FROM `' . _DB_PREFIX_ . 'ets_livechat_employee_online` WHERE id_shop="' . (int)Context::getContext()->shop->id . '"' . ' AND id_employee="' . (int)$employee['id_employee'] . '"' . ' ORDER BY date_online DESC');
                    $timeout = (int)Configuration::get('ETS_LC_TIME_OUT_BACK_END') * 3 / 1000 + (int)Configuration::get('ETS_LC_TIME_OUT') * 3 / 1000;

                    if ($date_online && (strtotime(date('Y-m-d H:i:s')) < strtotime($date_online) + $timeout))
                        return false;
                }
            }
            return !Db::getInstance()->getRow('SELECT * FROM `' . _DB_PREFIX_ . 'ets_livechat_message` WHERE id_conversation=' . (int)$conversation->id . ' AND id_employee > 0');

        }
        return false;
    }
    public static function getDateLastAdminOnline()
    {
        $conversation = self::getCustomerConversation();
        $sql = 'SELECT date_online FROM `' . _DB_PREFIX_ . 'ets_livechat_employee_online` WHERE id_shop="' . (int)Context::getContext()->shop->id . '"' . ($conversation && $conversation->id_employee && !$conversation->end_chat ? ' AND id_employee="' . (int)$conversation->id_employee . '"' : '') . ' ORDER BY date_online DESC';
        return Db::getInstance()->getValue($sql);
    }
    public static function updateAdminOnline($id_shop = 0)
    {
        if (!$id_shop)
            $id_shop = Context::getContext()->shop->id;
        if (!Module::isEnabled('ets_livechat'))
            return false;
        if (Db::getInstance()->executeS('SELECT * FROM `' . _DB_PREFIX_ . 'ets_livechat_employee_online` WHERE id_employee=' . (int)Context::getContext()->employee->id . ' AND id_shop=' . (int)$id_shop)) {
            Db::getInstance()->execute('UPDATE `' . _DB_PREFIX_ . 'ets_livechat_employee_online` SET date_online = "' . pSQL(date('Y-m-d H:i:s')) . '" WHERE id_employee=' . (int)Context::getContext()->employee->id . ' AND id_shop=' . (int)$id_shop);
        } else
            Db::getInstance()->execute('INSERT INTO `' . _DB_PREFIX_ . 'ets_livechat_employee_online`(id_employee,id_shop,date_online) VALUES("' . (int)Context::getContext()->employee->id . '","' . (int)$id_shop . '","' . pSQL(date('Y-m-d H:i:s')) . '")');
        if (!Db::getInstance()->getRow('SELECT * FROM  `' . _DB_PREFIX_ . 'ets_livechat_employee_status` WHERE id_employee=' . (int)Context::getContext()->employee->id . ' AND id_shop=' . (int)$id_shop))
            Db::getInstance()->execute('INSERT INTO `' . _DB_PREFIX_ . 'ets_livechat_employee_status`(id_employee,id_shop,status) VALUES("' . (int)Context::getContext()->employee->id . '","' . (int)$id_shop . '","online")');
    }
    public static function checkConversationEmployee($conversation, $id_employee)
    {
        if (!is_object($conversation))
            $conversation = new LC_Conversation($conversation);
        $employee = new Employee($id_employee);
        $sql = 'SELECT * FROM `' . _DB_PREFIX_ . 'ets_livechat_conversation` lc
            LEFT JOIN `' . _DB_PREFIX_ . 'ets_livechat_departments` d ON (lc.id_departments=d.id_departments OR lc.id_departments_wait=d.id_departments)
            LEFT JOIN `' . _DB_PREFIX_ . 'ets_livechat_departments_employee` de ON (d.id_departments= de.id_departments)
            WHERE lc.id_conversation="' . (int)$conversation->id . '" '
            . ($employee->id_profile != 1 && LC_Departments::checkDepartments() ? ' AND (de.id_employee="' . (int)$id_employee . '" OR d.all_employees =1 OR lc.id_departments=0 OR lc.id_departments_wait=-1)' : '')
            . (Configuration::get('ETS_LC_STAFF_ACCEPT') && $employee->id_profile != 1 ? ' AND (lc.id_employee=0 OR lc.id_employee="' . (int)$employee->id . '" OR lc.id_employee_wait="' . (int)$employee->id . '" OR lc.id_employee_wait=-1)' : '');
        return Db::getInstance()->getRow($sql);
    }
    public static function checkDeclineEmployee($id_employee,$id_conversation)
    {
        return Db::getInstance()->getRow('SELECT * FROM `' . _DB_PREFIX_ . 'ets_livechat_staff_decline` WHERE id_employee=' . (int)$id_employee . ' AND id_conversation=' . (int)$id_conversation);
    }
    public static function declineEmployee($id_employee,$id_conversation)
    {
        return Db::getInstance()->execute('INSERT INTO `' . _DB_PREFIX_ . 'ets_livechat_staff_decline`(id_employee,id_conversation) VALUES("' . (int)$id_employee . '","' . (int)$id_conversation . '")');
    }
    public function checkWaitSupport()
    {
        if (!Configuration::get('ETS_LC_STAFF_ACCEPT'))
            return false;
        if ($this->id_employee)
            return false;
        else
            return !Db::getInstance()->getRow('SELECT * FROM `' . _DB_PREFIX_ . 'ets_livechat_message` WHERE id_conversation=' . (int)$this->id . ' AND id_employee !=0');
    }
    public static function checkHasChanged($conversation)
    {
        if (!is_object($conversation))
            $conversation = new LC_Conversation($conversation);
        if (Configuration::get('ETS_LC_ENDCHAT_AUTO') > 0 && !$conversation->end_chat) {
            $timeend = Configuration::get('ETS_LC_ENDCHAT_AUTO') * 60;
            if (strtotime('now') > (strtotime($conversation->date_message_last_customer) + $timeend)) {
                $conversation->end_chat = -1;
                $conversation->save();
                return false;
            }
        }
        $context = Context::getContext();
        if (!$conversation->end_chat && ($conversation->id_employee_wait == $context->employee->id or $conversation->id_employee_wait == -1 or ($context->employee->id_profile == 1 && $conversation->id_employee_wait)) && $conversation->id_employee && $conversation->id_employee != $context->employee->id && Configuration::get('ETS_LC_STAFF_ACCEPT')) {
            $employee = new Employee($conversation->id_tranfer);
            return $employee->firstname . ' ' . $employee->lastname;
        }
        if ($conversation->end_chat && $conversation->id_departments_wait > 0 && $conversation->id_departments && !LC_Departments::checkDepartmentsExitsEmployee($conversation->id_departments) && LC_Departments::checkDepartmentsExitsEmployee($conversation->id_departments_wait)) {
            $department = new LC_Departments($conversation->id_departments);
            return $department->name;
        }
        return 0;
    }
    public static function checkWaitAccept($conversation)
    {
        if (!is_object($conversation))
            $conversation = new LC_Conversation($conversation);
        if (Configuration::get('ETS_LC_ENDCHAT_AUTO') > 0 && !$conversation->end_chat) {
            $timeend = Configuration::get('ETS_LC_ENDCHAT_AUTO') * 60;
            if (strtotime('now') > (strtotime($conversation->date_message_last_customer) + $timeend)) {
                $conversation->end_chat = -1;
                $conversation->save();
                return 0;
            }
        }
        if (!$conversation->end_chat && !$conversation->id_employee && Configuration::get('ETS_LC_STAFF_ACCEPT')) {
            if (LC_Conversation::checkSupperAdminDecline($conversation))
                return 0;
            else
                return 1;
        }
        return 0;
    }
    public static function checkSupperAdminDecline($conversation)
    {
        if (Context::getContext()->employee->id_profile == 1) {
            if (LC_Conversation::checkDeclineEmployee(Context::getContext()->employee->id,$conversation->id))
                return true;
            else
                return false;
        }
        return false;
    }
    public static function getCountConversationByChatref($chatref)
    {
        return Db::getInstance()->getValue('SELECT count(DISTINCT lc.id_conversation) FROM `' . _DB_PREFIX_ . 'ets_livechat_conversation` lc
            WHERE lc.id_shop="' . (int)Context::getContext()->shop->id . '" AND lc.chatref=' . (int)$chatref);
    }
    public static function getConverSationByChatref($chatref,$start,$limit)
    {
        $sql = 'SELECT lc.*,CONCAT(c.firstname," ",c.lastname) as fullname  FROM `' . _DB_PREFIX_ . 'ets_livechat_conversation` lc
            LEFT JOIN `' . _DB_PREFIX_ . 'ets_livechat_departments` ld ON (ld.id_departments=lc.id_departments OR ld.id_departments=lc.id_departments_wait)
            LEFT JOIN `' . _DB_PREFIX_ . 'ets_livechat_departments_employee` lde ON (ld.id_departments=lde.id_departments)
            LEFT JOIN `' . _DB_PREFIX_ . 'employee` e ON (e.id_employee = lde.id_employee)
            LEFT JOIN `' . _DB_PREFIX_ . 'customer` c ON (c.id_customer=lc.id_customer)
            WHERE 1 AND lc.id_shop="' . (int)Context::getContext()->shop->id . '"
            AND lc.chatref="' . (int)$chatref . '" 
            GROUP BY lc.id_conversation ORDER BY date_message_last_customer DESC LIMIT ' . (int)$start . ',' . (int)$limit;
        return Db::getInstance()->executeS($sql);
    }
    public static function getLastMessage($id_conversation)
    {
        return Db::getInstance()->getRow('SELECT * FROM `' . _DB_PREFIX_ . 'ets_livechat_message` WHERE id_conversation="' . (int)$id_conversation. '" AND id_employee=0 ORDER BY id_message DESC');
    }
    public static function getCountConversation($filter = false,$all_shop=false)
    {
        $sql = 'SELECT count(c.id_conversation) FROM `' . _DB_PREFIX_ . 'ets_livechat_conversation` c WHERE 1' . (!$all_shop ? ' AND id_shop=' . (int)Context::getContext()->shop->id : '') . ($filter ? (string)$filter : '');
        return Db::getInstance()->getValue($sql);
    }
    public function checkFileNumberUpload()
    {
        if (($max_number = (int)Configuration::get('ETS_LC_NUMBER_FILE_MS')) && $this->id) {
            $sql = 'SELECT count(id_message) FROM `' . _DB_PREFIX_ . 'ets_livechat_message`
                WHERE name_attachment!="" AND id_conversation ="' . (int)$this->id . '" AND id_employee=0';
            $total_file = Db::getInstance()->getValue($sql);
            if ($total_file < $max_number)
                return true;
            else
                return false;
        }
        return true;
    }
    public static function checkEnableLivechat($controller)
    {
        $cache_key = 'LC_Conversation::checkEnableLivechat_'.$controller;
        if(Cache::isStored($cache_key))
            return Cache::retrieve($cache_key);
        if (Configuration::get('ETS_LC_LIVECHAT_ON') == 'never')
            $result = false;
        elseif ((int)Configuration::get('ETS_DISPLAY_DASHBOARD_ONLY') && ($controller != 'AdminDashboard' || !Validate::isControllerName($controller)))
            $result = false;
        else {
            if (LC_Base::checkVesionModule()) {
                if (Db::getInstance()->getRow('SELECT * FROM `' . _DB_PREFIX_ . 'ets_livechat_staff` WHERE id_employee="' . (int)Context::getContext()->employee->id . '" AND status=0') && Context::getContext()->employee->id_profile != 1)
                    $result = false;
                else
                    $result = true;
            }
            else
                $result = true;
        }
        Cache::store($cache_key,$result);
        return $result;
    }
    public function checkChangeDepartment()
    {
        if (!LC_Departments::getAllDepartments())
            return false;
        if ( !$this->id || $this->end_chat)
            return true;
        else
            return false;
    }
    public function checkAutoEndChat()
    {
        if (Configuration::get('ETS_LC_ENDCHAT_AUTO') > 0 && !$this->end_chat) {
            $timeend = Configuration::get('ETS_LC_ENDCHAT_AUTO') * 60;
            if (strtotime('now') > (strtotime($this->date_message_last_customer) + $timeend)) {
                $this->end_chat = -1;
                $this->save();
                return true;
            }
        }
        return false;
    }
    public static function checkExistConversation($id_conversation)
    {
        if (($conversation = new LC_Conversation($id_conversation)) && Validate::isLoadedObject($conversation) && (LC_Tools::allShop() || $conversation->id_shop == Context::getContext()->shop->id))
            return true;
        else
            return false;
    }
    public static function checkAccess()
    {
        if (!($pages = explode(',', Tools::strtolower(Configuration::get('ETS_LC_MISC')))))
            return false;
        if (!($groups = explode(',', Tools::strtolower(Configuration::get('ETS_LC_CUSTOMER_GROUP')))))
            return false;
        if (Configuration::get('ETS_LC_LIVECHAT_ON') == 'never' || (Configuration::get('ETS_LC_LIVECHAT_ON') == 'online' && LC_Conversation::isAdminOnline() !== 'online'))
            return false;
        $black_list = explode(PHP_EOL, Configuration::get('ETS_BLACK_LIST_IP'));
        $my_ip = Tools::getRemoteAddr();
        if (in_array($my_ip, $black_list))
            return false;
        $id_customer = (Context::getContext()->customer->id) ? (int)(Context::getContext()->customer->id) : 0;
        $id_group = null;
        if ($id_customer) {
            $id_group = Customer::getDefaultGroupId((int)$id_customer);
        }
        if (!$id_group) {
            $id_group = (int)Group::getCurrent()->id;
        }
        if ((in_array('all', $pages) || in_array(Tools::strtolower(Context::getContext()->controller->php_self), $pages) || !in_array(Tools::strtolower(Context::getContext()->controller->php_self), array('index', 'category', 'product', 'cms')) && in_array('other', $pages)) && (in_array('all', $groups) || in_array($id_group, $groups)))
            return true;
        return false;
    }
    public static function getMaxID()
    {
        return (int)Db::getInstance()->getValue('SELECT MAX(id_conversation) FROM `'._DB_PREFIX_.'ets_livechat_conversation`');
    }
    public function getTotalMesageCustomer()
    {
        return Db::getInstance()->getValue('SELECT count(*) FROM `'._DB_PREFIX_.'ets_livechat_message` WHERE id_employee=0 AND id_conversation='.(int)$this->id);
    }
    public static function getMinYear()
    {
        return (int)Db::getInstance()->getValue('SELECT MIN(YEAR(datetime_added)) FROM `'._DB_PREFIX_.'ets_livechat_conversation`');
    }
    public static function getRecentlyConversation()
    {
        $sql = '
            SELECT  lc.*,CONCAT(c.firstname," ",c.lastname) as fullname,c.email FROM `'._DB_PREFIX_.'ets_livechat_conversation` lc
            INNER JOIN `'._DB_PREFIX_.'ets_livechat_message` m ON (lc.id_conversation=m.id_conversation)
            LEFT JOIN `'._DB_PREFIX_.'customer` c ON (c.id_customer=lc.id_customer)
            JOIN (SELECT MAX(m2.id_message) max_id_message,lc2.customer_email
            FROM `'._DB_PREFIX_.'ets_livechat_conversation` lc2
            INNER JOIN `'._DB_PREFIX_.'ets_livechat_message` m2 ON (lc2.id_conversation=m2.id_conversation) 
            WHERE 1 '.(!LC_Tools::allShop() ? ' AND lc2.id_shop="'.(int)Context::getContext()->shop->id.'"':'').' AND  m2.id_employee=0
            GROUP BY lc2.customer_email) ty ON (ty.max_id_message=m.id_message)
            WHERE 1 '.(!LC_Tools::allShop() ? ' AND lc.id_shop="'.(int)Context::getContext()->shop->id.'"':'').'
            ORDER BY ty.max_id_message DESC
            LIMIT 0,5';
        return Db::getInstance()->executeS($sql);
    }
}
