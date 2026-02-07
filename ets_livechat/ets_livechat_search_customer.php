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
include(_PS_ADMIN_DIR_.'/../../config/config.inc.php');
include(dirname(__FILE__).'/ajax.init.php');
$context = Context::getContext();
$ets_livechat = Module::getInstanceByName('ets_livechat');
$token = Tools::getValue('token');
if($context->employee->id && $context->employee->isLoggedBack())
{
    if (!$token || !hash_equals(Tools::getAdminTokenLite('AdminModules'), (string) $token))
        die();
    $query = Tools::getValue('q', false);
    if (!$query OR $query == '' OR Tools::strlen($query) < 1 OR !Validate::isCleanHtml($query))
        die();
    $sql = 'SELECT c.*,a.phone,a.phone_mobile FROM `'._DB_PREFIX_.'customer` c
    LEFT JOIN `'._DB_PREFIX_.'address` a ON (c.id_customer=a.id_customer)
    WHERE CONCAT(c.firstname," ",c.lastname) LIKE "'.pSQL($query).'%" OR c.email like "'.pSQL($query).'%" OR c.id_customer="'.(int)$query.'" OR a.phone like "'.pSQL($query).'%" OR a.phone_mobile like"'.pSQL($query).'%" GROUP BY c.id_customer';
    $customers = Db::getInstance()->executeS($sql);
    if($customers)
    {
        foreach($customers as $customer)
        {
            echo trim($customer['firstname'] .' '.$customer['lastname']).'|'.($customer['email']).'|'.($customer['phone'] ? $customer['phone'] : $customer['phone_mobile']).'|'.(int)$customer['id_customer']."\n";
        }
    }
    die();
}
