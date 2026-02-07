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

class LC_Departments extends ObjectModel
{
    /** @var Context|null */
    protected $context = null;
    public $name;
    public $description;
    public $status;
    public $sort_order;
    public static $definition = array(
        'table' => 'ets_livechat_departments',
        'primary' => 'id_departments',
        'fields' => array(
            'name' => array('type' => self::TYPE_STRING),
            'description' => array('type' => self::TYPE_STRING),
            'status' => array('type' => self::TYPE_INT),
            'sort_order' => array('type' => self::TYPE_INT),
            'all_employees' => array('type' => self::TYPE_INT),
        )
    );

    public function __construct($id = null, $idLang = null)
    {
        parent::__construct($id, $idLang);
        $this->context = Context::getContext();
    }

    public function delete()
    {
        Db::getInstance()->execute('DELETE FROM `' . _DB_PREFIX_ . 'ets_livechat_departments_employee` WHERE id_departments=' . (int)$this->id);
        Db::getInstance()->execute('UPDATE `' . _DB_PREFIX_ . 'ets_livechat_conversation` set id_departments=0 WHERE id_departments=' . (int)$this->id);
        if (parent::delete()) {
            $departments = Db::getInstance()->executeS('SELECT * FROM `' . _DB_PREFIX_ . 'ets_livechat_departments` ORDER BY sort_order ASC');
            if ($departments) {
                $i = 1;
                foreach ($departments as $department) {
                    Db::getInstance()->execute('UPDATE `' . _DB_PREFIX_ . 'ets_livechat_departments` SET sort_order="' . (int)$i . '" WHERE id_departments=' . (int)$department['id_departments']);
                    $i++;
                }
            }
        }
        return true;
    }

    public static function getEmployeeDepartments($active = true)
    {
        return Db::getInstance()->executeS('
            SELECT e.*,d.id_departments,s.name,s.avata,IFNULL(s.status,1) as status 
            FROM `' . _DB_PREFIX_ . 'employee` e 
                LEFT JOIN `' . _DB_PREFIX_ . 'ets_livechat_departments_employee` de ON (de.id_employee=e.id_employee)
                LEFT JOIN `' . _DB_PREFIX_ . 'ets_livechat_departments` d ON (d.id_departments = de.id_departments)
                LEFT JOIN `' . _DB_PREFIX_ . 'ets_livechat_staff` s ON (s.id_employee=e.id_employee) 
            WHERE e.active=' . ($active ? 1 : 0) . ' AND (s.status is null OR s.status=1) GROUP BY e.id_employee');
    }
    public static function getDepartmentByEmployee($id_employee)
    {
        return Db::getInstance()->executeS('SELECT * FROM `' . _DB_PREFIX_ . 'ets_livechat_departments_employee` WHERE id_employee=' . (int)$id_employee);
    }
    public static function getStaffByEmployee($id_employee)
    {
        return Db::getInstance()->getRow('SELECT e.*,s.name,s.avata,IFNULL(s.status,1) as status,s.signature FROM `' . _DB_PREFIX_ . 'employee` e
        LEFT JOIN `' . _DB_PREFIX_ . 'ets_livechat_staff` s ON (e.id_employee= s.id_employee)
        WHERE e.id_employee='.(int)$id_employee);
    }
    public static function getStaffByCustomer($id_customer)
    {
        return Db::getInstance()->getRow('SELECT c.*,CONCAT(c.firstname," ",c.lastname) as name,ci.avata, c.active as status,ci.signature FROM `' . _DB_PREFIX_ . 'customer` c
        LEFT JOIN `' . _DB_PREFIX_ . 'ets_livechat_customer_info` ci ON (ci.id_customer= c.id_customer)
        WHERE c.id_customer='.(int)$id_customer);
    }
    public static function getStaff()
    {
        $sql = 'SELECT e.*,count(DISTINCT lc.id_conversation) as total_conversation, count(DISTINCT fm.id_message) as total_ticket,FORMAT(count(DISTINCT lc.id_conversation) +count(DISTINCT fm.id_message),0) as total_support FROM `'._DB_PREFIX_.'employee` e
        LEFT JOIN `'._DB_PREFIX_.'ets_livechat_message` m ON (e.id_employee=m.id_employee)
        LEFT JOIN `'._DB_PREFIX_.'ets_livechat_conversation` lc ON (lc.id_conversation = m .id_conversation '.(!LC_Tools::allShop() ? ' AND lc.id_shop="'.(int)Context::getContext()->shop->id.'"':'').' )
        LEFT JOIN `'._DB_PREFIX_.'ets_livechat_ticket_form_message_note` fmn ON (e.id_employee= fmn.id_employee)
        LEFT JOIN `'._DB_PREFIX_.'ets_livechat_ticket_form_message` fm ON (fmn.id_message=fm.id_message '.(!LC_Tools::allShop() ? ' AND fm.id_shop ="'.(int)Context::getContext()->shop->id.'"':'').' AND fm.status="close")
        GROUP BY e.id_employee having total_support>0 ORDER BY total_support DESC';
        $staffs = Db::getInstance()->executeS($sql);
        if($staffs)
        {
            for($i=0;$i < count($staffs)-1;$i++)
                for($j=$i;$j<count($staffs);$j++)
                {
                    if($staffs[$i]['total_support'] < $staffs[$j]['total_support'])
                    {
                        $tam = $staffs[$i];
                        $staffs[$i] = $staffs[$j];
                        $staffs[$j] = $tam;
                    }
                }
        }
        if($staffs)
        {
            foreach($staffs as &$staff)
            {
                $rate_conversation = Db::getInstance()->getRow('SELECT SUM(lc.rating) as total_rating,COUNT(lc.id_conversation) as total FROM `'._DB_PREFIX_.'ets_livechat_conversation` lc WHERE rating >0 '.(!LC_Tools::allShop() ? ' AND id_shop="'.(int)Context::getContext()->shop->id.'"':'').' AND id_conversation IN (SELECT id_conversation FROM `'._DB_PREFIX_.'ets_livechat_message` WHERE id_employee="'.(int)$staff['id_employee'].'")');
                $rate_ticket= Db::getInstance()->getRow('SELECT SUM(rate) as total_rate, COUNT(id_message) as total FROM `'._DB_PREFIX_.'ets_livechat_ticket_form_message` WHERE rate >0 '.(!LC_Tools::allShop() ? ' AND id_shop= "'.(int)Context::getContext()->shop->id.'"':'').' AND id_message IN (SELECT id_message FROM `'._DB_PREFIX_.'ets_livechat_ticket_form_message_note` WHERE id_employee="'.(int)$staff['id_employee'].'")');
                $staff['avg_rate']=$rate_conversation['total'] || $rate_ticket['total'] ?  Tools::ps_round((float)($rate_conversation['total_rating']+$rate_ticket['total_rate'])/(float)($rate_conversation['total']+$rate_ticket['total']),1):0;
                $floor_rate =floor($staff['avg_rate']);
                $staff['du'] = $staff['avg_rate']*10 - $floor_rate*10;
                $staff['avatar'] = LC_Departments::getAvatarEmployee($staff['id_employee']);
            }
        }
        return $staffs;
    }
    public static function getAllDepartments($check_user = true,$active= true)
    {
        $cache_key = 'LC_Departments::getAllDepartments_'.($check_user ? '1':'0').'_'.($active ? '1':'0');
        if(!Cache::isStored($cache_key))
        {
            if ($check_user && !LC_Conversation::isUsedField('departments'))
                $result = false;
            else {
                $result = Db::getInstance()->executeS('SELECT * FROM `' . _DB_PREFIX_ . 'ets_livechat_departments` WHERE 1 '.($active ? ' AND status=1':'').' ORDER BY sort_order ASC');
            }
            Cache::store($cache_key,$result);
            return $result;
        }
        return Cache::retrieve($cache_key);

    }
    public static function checkDepartmentsExitsEmployee($id_departments, $id_employee = 0)
    {
        if (!$id_employee) {
            $id_employee = Context::getContext()->employee->id;
            $employee = Context::getContext()->employee;
        } else
            $employee = new Employee($id_employee);
        if ($employee->id_profile == 1)
            return true;
        $sql = 'SELECT d.id_departments FROM `' . _DB_PREFIX_ . 'ets_livechat_departments` d
        LEFT JOIN `' . _DB_PREFIX_ . 'ets_livechat_departments_employee` de ON (d.id_departments=de.id_departments)
        WHERE (d.all_employees=1 OR de.id_employee="' . (int)$id_employee . '") AND d.id_departments="' . (int)$id_departments . '"';
        return Db::getInstance()->getRow($sql);
    }
    public static function getEmployees($id_departments)
    {
        return Db::getInstance()->executeS(
            'SELECT e.*,pl.name as profile_name FROM `' . _DB_PREFIX_ . 'employee` e
                LEFT JOIN `' . _DB_PREFIX_ . 'profile_lang` pl ON (e.id_profile= pl.id_profile AND pl.id_lang ="' . (int)Context::getContext()->language->id . '")
                INNER JOIN `' . _DB_PREFIX_ . 'ets_livechat_departments_employee` de ON (e.id_employee= de.id_employee)
                WHERE de.id_departments="' . (int)$id_departments . ' AND e.id_profile!=1"
                ');
    }
    public static function getDepartMentsByID($id_departments)
    {
        $departments = Db::getInstance()->getRow('SELECT * FROM `' . _DB_PREFIX_ . 'ets_livechat_departments` WHERE id_departments=' . (int)$id_departments);
        if ($departments) {
            $agents = Db::getInstance()->executeS('SELECT * FROM `' . _DB_PREFIX_ . 'employee` e 
            INNER JOIN `' . _DB_PREFIX_ . 'ets_livechat_departments_employee` d ON (d.id_employee = e.id_employee)
            WHERE d.id_departments = "' . (int)$id_departments . '"');
            $array_agents = array();
            if ($agents) {
                foreach ($agents as $agent) {
                    $array_agents[] = $agent['id_employee'];
                }
            }
            $departments['agents'] = $array_agents;
        }
        return $departments;
    }
    public static function getEmployeeStaffs($active=true)
    {
        return Db::getInstance()->executeS('SELECT e.*,s.name,s.avata,IFNULL(s.status,1) as status,s.signature,pl.name as profile_name FROM `' . _DB_PREFIX_ . 'employee` e
        INNER JOIN `'._DB_PREFIX_.'employee_shop` es ON (es.id_employee = e.id_employee AND es.id_shop='.(int)Context::getContext()->shop->id.')
        LEFT JOIN `' . _DB_PREFIX_ . 'ets_livechat_staff` s ON (e.id_employee=s.id_employee) 
        LEFT JOIN `' . _DB_PREFIX_ . 'profile_lang` pl ON (e.id_profile = pl.id_profile AND pl.id_lang="' . (int)Context::getContext()->language->id . '")
        WHERE e.active=1'.($active ? ' HAVING status=1':''));
    }
    public static function getMaxSortOrder()
    {
        return Db::getInstance()->getValue('SELECT max(sort_order) FROM `' . _DB_PREFIX_ . 'ets_livechat_departments`');
    }
    public function addEmployees($id_employees)
    {
        Db::getInstance()->execute('DELETE FROM `' . _DB_PREFIX_ . 'ets_livechat_departments_employee` WHERE id_departments=' . (int)$this->id);
        if($id_employees)
        {
            foreach ($id_employees as $id_employee) {
                Db::getInstance()->execute('INSERT INTO `' . _DB_PREFIX_ . 'ets_livechat_departments_employee` (id_departments,id_employee) VALUES ( "' . (int)$this->id . '","' . (int)$id_employee . '")');
            }
            return Db::getInstance()->executeS('SELECT e.*,pl.name as profile_name FROM `' . _DB_PREFIX_ . 'employee` e LEFT JOIN `' . _DB_PREFIX_ . 'profile_lang` pl ON (e.id_profile=pl.id_profile AND pl.id_lang ="' . (int)Context::getContext()->language->id . '") WHERE e.id_employee IN (' . implode(',', array_map('intval', $id_employees)) . ')');
        }
        return false;
    }
    public static function getInfoStaffByIdEmployee($id_employee)
    {
        $employee = Db::getInstance()->getRow(
            'SELECT * FROM `' . _DB_PREFIX_ . 'employee` WHERE id_employee=' . (int)$id_employee
        );
        $staff = Db::getInstance()->getRow('SELECT * FROM `' . _DB_PREFIX_ . 'ets_livechat_staff` WHERE id_employee=' . (int)$id_employee);
        if ($staff) {
            $employee['name'] = $staff['name'];
            $employee['avata'] = $staff['avata'] ? _PS_ETS_LIVE_CHAT_IMG_ . $staff['avata'] : '';
            $employee['status'] = $staff['status'];
            $employee['signature'] = $staff['signature'];
        } else {
            $employee['name'] = '';
            $employee['avata'] = '';
            $employee['status'] = 1;
            $employee['signature'] = '';
        }
        return $employee;
    }
    public static function addUpdateStaff($id_employee,$nick_name,$signature,$imageName,$staff_status)
    {
        if ($staff = Db::getInstance()->getRow('SELECT * FROM `' . _DB_PREFIX_ . 'ets_livechat_staff` WHERE id_employee=' . (int)$id_employee)) {
            $oldAvata = $staff['avata'];
            $update = Db::getInstance()->execute('UPDATE `' . _DB_PREFIX_ . 'ets_livechat_staff` SET name="' . pSQL($nick_name) . '"' . (Context::getContext()->employee->id_profile == 1 ? ',status="' . (int)$staff_status . '"' : '') . ', signature="' . pSQL(trim($signature)) . '"' . ($imageName ? ' , avata="' . pSQL($imageName) . '"' : '') . ' WHERE id_employee=' . (int)$id_employee);
            if ($update && $oldAvata && $imageName && file_exists(_PS_ETS_LIVE_CHAT_IMG_DIR_ . $oldAvata)) {
                @unlink(_PS_ETS_LIVE_CHAT_IMG_DIR_ . $oldAvata);
            }
        } else {
            Db::getInstance()->execute('INSERT INTO `' . _DB_PREFIX_ . 'ets_livechat_staff` (id_employee,name,avata,status,signature) values("' . (int)$id_employee . '","' . pSQL($nick_name) . '","' . pSQL($imageName) . '","' . (Context::getContext()->employee->id_profile == 1 ? (int)$staff_status : 1) . '","' . pSQL(trim($signature)) . '")');
        }
        return true;
    }
    public static function getCompanyInfo($id_employee, $info = '')
    {
        $cache_key = 'LC_Departments::getCompanyInfo_'.$info.($info ? '_'.$info:'');
        if(!Cache::isStored($cache_key))
        {
            $context = Context::getContext();
            if (isset($context->employee) && $context->employee->id)
                $admin = true;
            else
                $admin = false;
            if (!$info)
                $info = Configuration::get('ETS_LC_DISPLAY_COMPANY_INFO');
            if ($info == 'general' || $id_employee == 0) {
                $name = Configuration::get('ETS_LC_COMPANY_NAME');
                $logo = $admin ? _PS_ETS_LIVE_CHAT_IMG_ . Configuration::get('ETS_LC_COMPANY_LOGO') : $context->link->getMediaLink(_PS_ETS_LIVE_CHAT_IMG_ . Configuration::get('ETS_LC_COMPANY_LOGO'));
            } else {
                $employee = Db::getInstance()->getRow(
                    'SELECT * FROM `' . _DB_PREFIX_ . 'employee` WHERE id_employee=' . (int)$id_employee
                );
                $staff = Db::getInstance()->getRow('SELECT * FROM `' . _DB_PREFIX_ . 'ets_livechat_staff` WHERE id_employee=' . (int)$id_employee);
                if ($staff) {
                    if ($staff['name'])
                        $name = $staff['name'];
                    else
                        $name = $employee['firstname'] . ' ' . $employee['lastname'];
                    if ($staff['avata'])
                        $logo = $admin ? _PS_ETS_LIVE_CHAT_IMG_ . $staff['avata'] : $context->link->getMediaLink(_PS_ETS_LIVE_CHAT_IMG_ . $staff['avata']);
                    else
                        $logo = $admin ? _PS_ETS_LIVE_CHAT_IMG_ . Configuration::get('ETS_LC_COMPANY_LOGO') : $context->link->getMediaLink(_PS_ETS_LIVE_CHAT_IMG_ . Configuration::get('ETS_LC_COMPANY_LOGO'));
                } else {
                    $name = $employee['firstname'] . ' ' . $employee['lastname'];
                    $logo = $admin ? _PS_ETS_LIVE_CHAT_IMG_ . Configuration::get('ETS_LC_COMPANY_LOGO') : $context->link->getMediaLink(_PS_ETS_LIVE_CHAT_IMG_ . Configuration::get('ETS_LC_COMPANY_LOGO'));
                }
            }
            $result= array(
                'name' => $name,
                'logo' => $logo,
            );
            Cache::store($cache_key,$result);
        }
        return Cache::retrieve($cache_key);
    }
    public static function checkDepartments()
    {
        if (!LC_Conversation::isUsedField('departments'))
            return false;
        else {
            $departments = Db::getInstance()->executeS('SELECT * FROM `' . _DB_PREFIX_ . 'ets_livechat_departments` WHERE status=1');
            if ($departments)
                return true;
        }
        return false;
    }
    public static function getStaffDecline($id_employee)
    {
        return Db::getInstance()->executeS('SELECT * FROM `' . _DB_PREFIX_ . 'ets_livechat_staff_decline` WHERE id_employee="' . (int)$id_employee .'"');
    }
    public static function getStatusEmployee($id_employee)
    {
        $status = Db::getInstance()->getValue('SELECT status FROM `' . _DB_PREFIX_ . 'ets_livechat_employee_status` WHERE id_employee=' . (int)$id_employee . ' AND id_shop=' . (int)Context::getContext()->shop->id);
        return trim($status) ? trim($status) : false;
    }
    public static function updateCustomerInfo($id_customer,$imageName,$signature)
    {
        if(($customerInfo = self::getCustomerInfo($id_customer)))
        {
            $customer_avata_old = isset($customerInfo['avata']) ? $customerInfo['avata'] :'';
            Db::getInstance()->execute('UPDATE `'._DB_PREFIX_.'ets_livechat_customer_info` SET '.(isset($imageName) ? 'avata="'.pSQL($imageName).'",':'').' signature="'.pSQL($signature).'" WHERE id_customer='.(int)$id_customer);
            if($imageName && $customer_avata_old && file_exists(_PS_ETS_LIVE_CHAT_IMG_DIR_.$customer_avata_old))
                @unlink(_PS_ETS_LIVE_CHAT_IMG_DIR_.$customer_avata_old);
        }
        else
            Db::getInstance()->execute('INSERT INTO `'._DB_PREFIX_.'ets_livechat_customer_info`(id_customer,avata,signature) values("'.(int)$id_customer.'","'.(isset($imageName) ? pSQl($imageName) :'').'","'.pSQL($signature).'")');
        return true;
    }
    public static function deleteAvatarCustomer($id_customer)
    {
        $customerInfo = self::getCustomerInfo($id_customer);
        $customer_avata = $customerInfo && isset($customerInfo['avata'])? $customerInfo['avata']:'';
        if($customer_avata && file_exists(_PS_ETS_LIVE_CHAT_IMG_DIR_.$customer_avata))
        {
            @unlink(_PS_ETS_LIVE_CHAT_IMG_DIR_.$customer_avata);
            Db::getInstance()->execute('UPDATE `'._DB_PREFIX_.'ets_livechat_customer_info` SET avata="" WHERE id_customer='.(int)$id_customer);
        }
        return true;
    }
    public static function getCustomerInfo($id_customer)
    {
        return Db::getInstance()->getRow('SELECT avata,signature FROM `' . _DB_PREFIX_ . 'ets_livechat_customer_info` WHERE id_customer=' .(int)$id_customer);
    }
    public static function getAvatarCustomer($id_customer)
    {
        $context = Context::getContext();
        if (defined('_PS_ADMIN_DIR_') && isset($context->employee) && $context->employee->id)
            $admin = true;
        else
            $admin = false;
        $customer_avatar = Db::getInstance()->getValue('SELECT avata FROM `' . _DB_PREFIX_ . 'ets_livechat_customer_info` WHERE id_customer=' . (int)$id_customer);
        if ($id_customer && $customer_avatar)
            return $context->link->getMediaLink(_PS_ETS_LIVE_CHAT_IMG_ . $customer_avatar);
        elseif (Configuration::get('ETS_LC_CUSTOMER_AVATA'))
            return $admin ? _PS_ETS_LIVE_CHAT_IMG_ . Configuration::get('ETS_LC_CUSTOMER_AVATA') : $context->link->getMediaLink(_PS_ETS_LIVE_CHAT_IMG_ . Configuration::get('ETS_LC_CUSTOMER_AVATA'));
        else
            return $admin ? _PS_ETS_LIVE_CHAT_IMG_ . 'customeravata.jpg' : $context->link->getMediaLink(_PS_ETS_LIVE_CHAT_IMG_ . 'customeravata.jpg');
    }
    public static function getAvatarEmployee($id_employee)
    {
        $context = Context::getContext();
        if (defined('_PS_ADMIN_DIR_') && isset($context->employee) && $context->employee->id)
            $admin = true;
        else
            $admin = false;
        if (Configuration::get('ETS_LC_COMPANY_LOGO'))
            $shop_logo = $admin ? _PS_ETS_LIVE_CHAT_IMG_ . Configuration::get('ETS_LC_COMPANY_LOGO') : $context->link->getMediaLink(_PS_ETS_LIVE_CHAT_IMG_ . Configuration::get('ETS_LC_COMPANY_LOGO'));
        else
            $shop_logo = $admin ? _PS_ETS_LIVE_CHAT_IMG_ . 'adminavatar.jpg' : $context->link->getMediaLink(_PS_ETS_LIVE_CHAT_IMG_ . 'adminavatar.jpg');
        if (Configuration::get('ETS_LC_DISPLAY_COMPANY_INFO') == 'general' || $id_employee == -1) {
            return $shop_logo;
        } else
            return ($avata = Db::getInstance()->getValue('SELECT avata FROM `' . _DB_PREFIX_ . 'ets_livechat_staff` WHERE id_employee=' . (int)$id_employee)) ? $context->link->getMediaLink(_PS_ETS_LIVE_CHAT_IMG_ . $avata) : $shop_logo;
    }
    public static function isDisabledStaff($id_employee = 0)
    {
        if (!$id_employee)
            $id_employee = Context::getContext()->employee->id;
        $employee = new Employee($id_employee);
        if ($employee->id_profile != 1) {
            if ($staff = Db::getInstance()->getRow('SELECT * FROM `' . _DB_PREFIX_ . 'ets_livechat_staff` WHERE id_employee=' . (int)$id_employee)) {
                return $staff['status'] ? false : true;
            }
        }
        return false;
    }
}
