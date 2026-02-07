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

require_once(dirname(__FILE__) . '/classes/src/autoload.php');

if (!class_exists('LC_Tools') && file_exists(dirname(__FILE__) . '/classes/LC_Tools.php'))
    require_once(dirname(__FILE__) . '/classes/LC_Tools.php');
if (!class_exists('LC_Base') && file_exists(dirname(__FILE__) . '/classes/LC_Base.php'))
    require_once(dirname(__FILE__) . '/classes/LC_Base.php');
if (!class_exists('LC_Conversation') && file_exists(dirname(__FILE__) . '/classes/LC_Conversation.php'))
    require_once(dirname(__FILE__) . '/classes/LC_Conversation.php');
if (!class_exists('LC_Message') && file_exists(dirname(__FILE__) . '/classes/LC_Message.php'))
    require_once(dirname(__FILE__) . '/classes/LC_Message.php');
if (!class_exists('LC_MadeMessage') && file_exists(dirname(__FILE__) . '/classes/LC_MadeMessage.php'))
    require_once(dirname(__FILE__) . '/classes/LC_MadeMessage.php');
if (!class_exists('LC_AutoReplyMessage') && file_exists(dirname(__FILE__) . '/classes/LC_AutoReplyMessage.php'))
    require_once(dirname(__FILE__) . '/classes/LC_AutoReplyMessage.php');
if (!class_exists('LC_Download') && file_exists(dirname(__FILE__) . '/classes/LC_Download.php'))
    require_once(dirname(__FILE__) . '/classes/LC_Download.php');
if (!class_exists('LC_Departments') && file_exists(dirname(__FILE__) . '/classes/LC_Departments.php'))
    require_once(dirname(__FILE__) . '/classes/LC_Departments.php');
if (!class_exists('LC_Ticket_form') && file_exists(dirname(__FILE__) . '/classes/LC_Ticket_form.php'))
    require_once(dirname(__FILE__) . '/classes/LC_Ticket_form.php');
if (!class_exists('LC_Ticket_field') && file_exists(dirname(__FILE__) . '/classes/LC_Ticket_field.php'))
    require_once(dirname(__FILE__) . '/classes/LC_Ticket_field.php');
if (!class_exists('LC_Ticket') && file_exists(dirname(__FILE__) . '/classes/LC_Ticket.php'))
    require_once(dirname(__FILE__) . '/classes/LC_Ticket.php');
if (!class_exists('LC_Note') && file_exists(dirname(__FILE__) . '/classes/LC_Note.php'))
    require_once(dirname(__FILE__) . '/classes/LC_Note.php');
if (!class_exists('LC_paggination_class') && file_exists(dirname(__FILE__) . '/classes/LC_paggination_class.php'))
    require_once(dirname(__FILE__) . '/classes/LC_paggination_class.php');
if (!class_exists('LC_Mail') && file_exists(dirname(__FILE__) . '/classes/LC_Mail.php'))
    require_once(dirname(__FILE__) . '/classes/LC_Mail.php');

if (!defined('_PS_ETS_LIVE_CHAT_UPLOAD_DIR_')) {
    define('_PS_ETS_LIVE_CHAT_UPLOAD_DIR_', _PS_DOWNLOAD_DIR_ . 'ets_livechat/');
}
if (!defined('_PS_ETS_LIVE_CHAT_UPLOAD_')) {
    define('_PS_ETS_LIVE_CHAT_UPLOAD_', __PS_BASE_URI__ . 'download/ets_livechat/');
}
if (!defined('_PS_ETS_LIVE_CHAT_IMG_DIR_')) {
    define('_PS_ETS_LIVE_CHAT_IMG_DIR_', _PS_IMG_DIR_ . 'ets_livechat/');
}
if (!defined('_PS_ETS_LIVE_CHAT_IMG_')) {
    define('_PS_ETS_LIVE_CHAT_IMG_', __PS_BASE_URI__ . 'img/ets_livechat/');
}
if (!class_exists('LC_Ticket_process') && file_exists(dirname(__FILE__) . '/classes/LC_Ticket_process.php'))
    require_once(dirname(__FILE__) . '/classes/LC_Ticket_process.php');

class Ets_livechat extends Module
{
    private $errorMessage;
    public $baseAdminPath;
    private $_html;
    public $emotions = array();
    public $url_module;
    public $all_shop = false;
    public $errors = array();
    public $_errors = array();
    public $shops = array();
    public $is17 = false;
    public $is16 = false;
    public $file_types = array();
    public $link_new_employee ='';
    public $is_configurable;
    public $lc_configTabs;
    public function __construct()
    {
        $this->name = 'ets_livechat';
        $this->tab = 'front_office_features';
        $this->version = '2.7.1';
        $this->author = 'PrestaHero';
        $this->need_instance = 0;
        $this->bootstrap = true;
		$this->module_key = '';
        $this->is_configurable = 1;
        parent::__construct();
        $this->file_types = array("pdf", "gif", 'png', 'jpg', 'doc', 'docx', 'xls', 'xlsx', 'zip');
        $this->url_module = $this->_path;
        $this->displayName = $this->l('Live Chat, Contact Form And Ticketing System');
        $this->description = $this->l('Built-in live chat, contact form and ticketing system (helpdesk) module for PrestaShop, self-managed, free forever! 3-in-1 complete customer support channel to communicate with online customers easily and boost sales.');
        $this->ps_versions_compliancy = array('min' => '1.5.0.0', 'max' => '9.99.99');
        //Emotion icons
        if (version_compare(_PS_VERSION_, '1.7', '>='))
            $this->is17 = true;
        elseif (version_compare(_PS_VERSION_, '1.6', '>='))
            $this->is16 = true;
        if (Configuration::get('PS_MULTISHOP_FEATURE_ACTIVE') && Shop::getContext() == Shop::CONTEXT_ALL) {
            $this->all_shop = true;
            $this->shops = Shop::getShops(false);
        }
        $this->emotions = array(
            ':((' => array(
                'img' => 'crying.gif',
                'title' => $this->l('Crying'),
            ),
            ':))' => array(
                'img' => 'laughing.gif',
                'title' => $this->l('Laughing'),
            ),
            ':(|)' => array(
                'img' => 'monkey.gif',
                'title' => $this->l('Monkey'),
            ),
            ':(' => array(
                'img' => 'sad.gif',
                'title' => $this->l('Sad'),
            ),
            '|:D/' => array(
                'img' => 'dancing.gif',
                'title' => $this->l('Dancing'),
            ),
            '>:D>' => array(
                'img' => 'big hug.gif',
                'title' => $this->l('Big hug'),
            ),
            ':D' => array(
                'img' => 'big grin.gif',
                'title' => $this->l('Big grin'),
            ),
            ';;)' => array(
                'img' => 'batting eyelashes.gif',
                'title' => $this->l('batting eyelashes'),
            ),
            ';))' => array(
                'img' => 'hee hee.gif',
                'title' => $this->l('Hee hee'),
            ),
            ';)' => array(
                'img' => 'winking.gif',
                'title' => $this->l('Winking'),
            ),
            ':-/' => array(
                'img' => 'confused.gif',
                'title' => $this->l('Confused'),
            ),
            ':x' => array(
                'img' => 'love struck.gif',
                'title' => $this->l('Love struck'),
            ),
            ':">' => array(
                'img' => 'blushing.gif',
                'title' => $this->l('Blushing'),
            ),
            '>:P' => array(
                'img' => 'phbbbbt.gif',
                'title' => $this->l('Phbbbbt'),
            ),
            ':P' => array(
                'img' => 'tongue.gif',
                'title' => $this->l('Tongue'),
            ),
            ':-*' => array(
                'img' => 'kiss.gif',
                'title' => $this->l('Kiss'),
            ),
            '=((' => array(
                'img' => 'broken heart.gif',
                'title' => $this->l('Broken heart'),
            ),
            '3:-O' => array(
                'img' => 'cow.gif',
                'title' => $this->l('Cow'),
            ),
            ':-O' => array(
                'img' => 'surprise.gif',
                'title' => $this->l('Surprise'),
            ),
            'X(' => array(
                'img' => 'angry.gif',
                'title' => $this->l('Angry'),
            ),
            '~:>' => array(
                'img' => 'chicken.gif',
                'title' => $this->l('Chicken'),
            ),
            ':>' => array(
                'img' => 'smug.gif',
                'title' => $this->l('Smug'),
            ),
            'B-)' => array(
                'img' => 'cool.gif',
                'title' => $this->l('Cool'),
            ),
            '#:-S' => array(
                'img' => 'whew.gif',
                'title' => $this->l('Whew!'),
            ),
            ':-SS' => array(
                'img' => 'nailbiting.gif',
                'title' => $this->l('Nailbiting'),
            ),
            ':-S' => array(
                'img' => 'worried.gif',
                'title' => $this->l('Worried'),
            ),
            '>:)' => array(
                'img' => 'devil.gif',
                'title' => $this->l('Devil'),
            ),
            '(:|' => array(
                'img' => 'yawn.gif',
                'title' => $this->l('Yawn'),
            ),
            ':|' => array(
                'img' => 'straight face.gif',
                'title' => $this->l('straight face'),
            ),
            '/:)' => array(
                'img' => 'raised eyebrow.gif',
                'title' => $this->l('Raised eyebrow'),
            ),
            '=))' => array(
                'img' => 'rolling on the floor.gif',
                'title' => $this->l('rolling on the floor'),
            ),
            'O:)' => array(
                'img' => 'angel.gif',
                'title' => $this->l('Angel'),
            ),
            ':-B' => array(
                'img' => 'nerd.gif',
                'title' => $this->l('Nerd'),
            ),
            '=;' => array(
                'img' => 'talk to the hand.gif',
                'title' => $this->l('Nerd'),
            ),
            ':-??' => array(
                'img' => 'i do not know.gif',
                'title' => $this->l('I don\'t know'),
            ),
            '%-(' => array(
                'img' => 'not listening.gif',
                'title' => $this->l('Not listening'),
            ),
            ':@)' => array(
                'img' => 'pig.gif',
                'title' => $this->l('Pig'),
            ),

            '@};-' => array(
                'img' => 'rose.gif',
                'title' => $this->l('Rose'),
            ),
            '%%-' => array(
                'img' => 'good luck.gif',
                'title' => $this->l('Good luck'),
            ),
            '~O)' => array(
                'img' => 'coffee.gif',
                'title' => $this->l('Coffee'),
            ),
            '*-:)' => array(
                'img' => 'idea.gif',
                'title' => $this->l('Idea'),
            ),
            '8-X' => array(
                'img' => 'skull.gif',
                'title' => $this->l('Skull'),
            ),
            '=:)' => array(
                'img' => 'bug.gif',
                'title' => $this->l('Bug'),
            ),
            '>-)' => array(
                'img' => 'alien.gif',
                'title' => $this->l('Alien'),
            ),
            ':-L' => array(
                'img' => 'frustrated.gif',
                'title' => $this->l('Frustrated'),
            ),
            '[-O>' => array(
                'img' => 'praying.gif',
                'title' => $this->l('Praying'),
            ),
            ':-c' => array(
                'img' => 'call me.gif',
                'title' => $this->l('Call me'),
            ),
            ':)]' => array(
                'img' => 'on the phone.gif',
                'title' => $this->l('On the phone'),
            ),
            '~X(' => array(
                'img' => 'at wits.gif',
                'title' => $this->l('At wits\' end'),
            ),
            ':-h' => array(
                'img' => 'wave.gif',
                'title' => $this->l('Wave'),
            ),
            ':-t' => array(
                'img' => 'time out.gif',
                'title' => $this->l('Time out'),
            ),
            '8->' => array(
                'img' => 'daydreaming.gif',
                'title' => $this->l('Daydreaming'),
            ),
            'I-|' => array(
                'img' => 'sleepy.gif',
                'title' => $this->l('Sleepy'),
            ),
            '8-|' => array(
                'img' => 'rolling eyes.gif',
                'title' => $this->l('Rolling eyes'),
            ),
            'L-)' => array(
                'img' => 'loser.gif',
                'title' => $this->l('loser'),
            ),
            ':-&' => array(
                'img' => 'sick.gif',
                'title' => $this->l('Sick'),
            ),
            ':-$' => array(
                'img' => 'do not tell anyone.gif',
                'title' => $this->l('Don\'t tell anyone'),
            ),
            '[-(' => array(
                'img' => 'not talking.gif',
                'title' => $this->l('Not talking'),
            ),
            ':O)' => array(
                'img' => 'clown.gif',
                'title' => $this->l('Clown'),
            ),
            '8-}' => array(
                'img' => 'silly.gif',
                'title' => $this->l('Silly'),
            ),
            '>:-P' => array(
                'img' => 'party.gif',
                'title' => $this->l('Party'),
            ),
            '=P~' => array(
                'img' => 'drooling.gif',
                'title' => $this->l('Drooling'),
            ),
            ':-?' => array(
                'img' => 'thinking.gif',
                'title' => $this->l('thinking'),
            ),
            '#-o' => array(
                'img' => 'doh.gif',
                'title' => $this->l('D\'oh'),
            ),
            '=D>' => array(
                'img' => 'applause.gif',
                'title' => $this->l('Applause'),
            ),

            '@-)' => array(
                'img' => 'hypnotized.gif',
                'title' => $this->l('Hypnotized'),
            ),
            ':^o' => array(
                'img' => 'liar.gif',
                'title' => $this->l('Liar'),
            ),
            ':-w' => array(
                'img' => 'waiting.gif',
                'title' => $this->l('Waiting'),
            ),
            ':->' => array(
                'img' => 'sigh.gif',
                'title' => $this->l('Sigh'),
            ),

            '>):)' => array(
                'img' => 'cowboy.gif',
                'title' => $this->l('Cowboy'),
            ),
            '$-)' => array(
                'img' => 'money eyes.gif',
                'title' => $this->l('Money eyes'),
            ),
            ':-"' => array(
                'img' => 'whistling.gif',
                'title' => $this->l('Whistling'),
            ),
            'b-(' => array(
                'img' => 'feeling beat up.gif',
                'title' => $this->l('Feeling beat up'),
            ),
            ':)>-' => array(
                'img' => 'peace sign.gif',
                'title' => $this->l('peace sign'),
            ),
            '[-X' => array(
                'img' => 'shame on you.gif',
                'title' => $this->l('Shame on you'),
            ),
            '>:/' => array(
                'img' => 'bring it on.gif',
                'title' => $this->l('Bring it on'),
            ),
            ':-@' => array(
                'img' => 'chatterbox.gif',
                'title' => $this->l('chatterbox'),
            ),
            '^:)^' => array(
                'img' => 'not worthy.gif',
                'title' => $this->l('Not worthy'),
            ),
            ':)' => array(
                'img' => 'happy.gif',
                'title' => $this->l('Happy'),
            ),
            ':-j' => array(
                'img' => 'oh go on.gif',
                'title' => $this->l('Oh go on'),
            ),
        );
        $this->lc_configTabs = array(
            'status' => $this->l('Statuses'),
            'chat_box' => $this->l('Chat box'),
            'im' => $this->l('IM'),
            'privacy' => $this->l('Privacy'),
            'fields' => $this->l('Fields'),
            'email' => $this->l('Email'),
            'security' => $this->l('Security'),
            'timing' => $this->l('Timing'),
            'display' => $this->l('Display'),
            'sound' => $this->l('Sound'),
            'auto_reply' => $this->l('Auto reply'),
            'pre_made_message' => $this->l('Pre-made messages'),
            'sosial' => $this->l('Social login'),
            'back_list_ip' => $this->l('IP black list'),
            'clearer' => $this->l('Clean-up'),
        );

    }

    /**
     * @see Module::uninstall()
     */
    public function uninstall()
    {
        return parent::uninstall() 
        && $this->unregisterHook('displayHeader')
        && $this->unregisterHook('displayBackOfficeHeader')
        && $this->unregisterHook('actionAuthentication')
        && $this->unregisterHook('actionCustomerLogoutAfter')
        && $this->unregisterHook('displayBackOfficeFooter')
        && $this->unregisterHook('DisplayBlockOnline')
        && $this->unregisterHook('DisplayBlockBusy')
        && $this->unregisterHook('DisplayBlockInvisible')
        && $this->unregisterHook('DisplayBlockOffline')
        && $this->unregisterHook('displayStaffs')
        && $this->unregisterHook('displaySystemTicket')
        && $this->unregisterHook('customerAccount')
        && $this->unregisterHook('displayMyAccountBlock')
        && $this->unregisterHook('moduleRoutes')
        && $this->unregisterHook('displayLeftColumn')
        && $this->unregisterHook('displayFooter')
        && $this->unregisterHook('displayRightColumn')
        && $this->unregisterHook('displayNav')
        && $this->unregisterHook('actionDispatcherBefore')
        && $this->unregisterHook('displayNav1')
        && $this->unregisterHook('customBlockSupport')
        && $this->unregisterHook('contactLink')
        && $this->unregisterHook('actionCustomerGridDefinitionModifier')
        && $this->unregisterHook('registerGDPRConsent')
        && $this->unregisterHook('displayProductAdditionalInfo')
        && $this->unregisterHook('displayRightColumnProduct')
        && $this->unregisterHook('actionObjectLanguageAddAfter')
        && $this->_uninstallDb() && $this->_uninstallTabs() && $this->unInstallLinkDefault();
    }

    /**
     * @see Module::install()
     */
    public function install()
    {
        if (Module::isInstalled('ets_livechat_free'))
            return false;
        return parent::install()
            && $this->registerHook('displayHeader')
            && $this->registerHook('displayBackOfficeHeader')
            && $this->registerHook('actionAuthentication')
            && $this->registerHook('actionCustomerLogoutAfter')
            && $this->registerHook('displayBackOfficeFooter')
            && $this->registerHook('DisplayBlockOnline')
            && $this->registerHook('DisplayBlockBusy')
            && $this->registerHook('DisplayBlockInvisible')
            && $this->registerHook('DisplayBlockOffline')
            && $this->registerHook('displayStaffs')
            && $this->registerHook('displaySystemTicket')
            && $this->registerHook('customerAccount')
            && $this->registerHook('displayMyAccountBlock')
            && $this->registerHook('moduleRoutes')
            && $this->registerHook('displayLeftColumn')
            && $this->registerHook('displayFooter')
            && $this->registerHook('displayRightColumn')
            && $this->registerHook('displayNav')
            && $this->registerHook('displayNav1')
            && $this->registerHook('customBlockSupport')
            && $this->registerHook('contactLink')
            && $this->registerHook('actionDispatcherBefore')
            && $this->registerHook('actionCustomerGridDefinitionModifier')
            && $this->registerhook('registerGDPRConsent')
            && $this->registerhook('displayProductAdditionalInfo')
            && $this->registerhook('displayRightColumnProduct')
            && $this->registerHook('actionObjectLanguageAddAfter')
            && $this->_installDb() && $this->createTemplateMail() && $this->_installTabs() && $this->installLinkDefault();
    }
    public function hookActionDispatcherBefore($params)
    {
        $context = $this->context;
        $controller = Tools::getValue('controller');
        if(version_compare(_PS_VERSION_, '8.0.0', '>=') && (Tools::strtolower($controller)=='admincustomers' || Tools::strtolower($controller)=='adminorders') && isset($params['controller_type']) && $params['controller_type']==Dispatcher::FC_ADMIN && isset($context->employee->id) && $context->employee->id && $context->employee->isLoggedBack())
        {

            $this->assignTwigVar(
                $this->getTwigs()
            );
        }
    }
    public function _installDb()
    {
        LC_Base::installDb();
        $languages = Language::getLanguages(false);
        chmod(dirname(__FILE__) . '/ets_livechat_search_customer.php', 0644);
        chmod(dirname(__FILE__) . '/ets_livechat_ajax.php', 0644);
        chmod(dirname(__FILE__) . '/download.php', 0644);
        chmod(dirname(__FILE__) . '/../ets_livechat', 0755);
        if (!is_dir(_PS_ETS_LIVE_CHAT_UPLOAD_DIR_)) {
            @mkdir(_PS_ETS_LIVE_CHAT_UPLOAD_DIR_, 0755, true);
            @copy(dirname(__FILE__) . '/index.php', _PS_ETS_LIVE_CHAT_UPLOAD_DIR_ . 'index.php');
        }
        if (!is_dir(_PS_ETS_LIVE_CHAT_IMG_DIR_)) {
            @mkdir(_PS_ETS_LIVE_CHAT_IMG_DIR_, 0755, true);
            @copy(dirname(__FILE__) . '/index.php', _PS_ETS_LIVE_CHAT_IMG_DIR_ . 'index.php');
        }
        Tools::copy(dirname(__FILE__) . '/views/img/temp/customeravata.jpg', _PS_ETS_LIVE_CHAT_IMG_DIR_ . 'customeravata.jpg');
        Tools::copy(dirname(__FILE__) . '/views/img/temp/chatbubble.png', _PS_ETS_LIVE_CHAT_IMG_DIR_ . 'chatbubble.png');
        Tools::copy(dirname(__FILE__) . '/views/img/temp/adminavatar.jpg', _PS_ETS_LIVE_CHAT_IMG_DIR_ . 'adminavatar.jpg');
        if ($configs = LC_Base::getInstance()->setConfig()) {
            foreach ($configs as $key => $config) {
                if (isset($config['lang']) && $config['lang']) {
                    $values = array();
                    foreach ($languages as $lang) {
                        $values[$lang['id_lang']] = isset($config['default_lang']) && $config['default_lang'] ? $this->getTextLang($config['default_lang'], $lang,'lc_base') : (isset($config['default']) ? $config['default'] : '');
                    }
                    Configuration::updateGlobalValue($key, $values, true);
                } else
                    Configuration::updateGlobalValue($key, isset($config['default']) ? $config['default'] : '', true);
            }
        }
        Configuration::updateGlobalValue('ETS_CONVERSATION_DISPLAY_ADMIN', 1);
        $this->createFormDefault();
        $this->updateLastAction();
        Configuration::updateGlobalValue('ETS_LIVECHAT_ADMIN_TICKET', 1);
        $alias = array();
        foreach (Language::getLanguages(false) as $language) {
            $alias[$language['id_lang']] = $this->getTextLang('support', $language) ?: $this->l('support');
        }
        Configuration::updateGlobalValue('ETS_LC_URL_ALIAS', $alias);
        if (!Configuration::get('PS_ROUTE_product_rule') || Tools::strpos(Configuration::get('PS_ROUTE_product_rule'), '.html'))
            Configuration::updateGlobalValue('ETS_LC_URL_SUBFIX', 0);
        else
            Configuration::updateGlobalValue('ETS_LC_URL_SUBFIX', 1);
        Configuration::updateGlobalValue('ETS_LC_NUMBER_TICKET_MESSAGES', 10);
        Configuration::updateGlobalValue('ETS_LC_NUMBER_TICKET_MANAGER', '');
        if (!Configuration::getGlobalValue('ETS_LC_FO_TOKEN')) {
            Configuration::updateGlobalValue('ETS_LC_FO_TOKEN', Tools::passwdGen(32));
        }
        $this->setAdminForder();
        return true;
    }

    public function _installTabs()
    {
        $languages = Language::getLanguages(false);
        if (!Tab::getIdFromClassName('AdminLiveChat')) {
            $tab = new Tab();
            $tab->class_name = 'AdminLiveChat';
            $tab->module = 'ets_livechat';
            $tab->id_parent = 0;
            foreach ($languages as $lang) {
                $tab->name[$lang['id_lang']] = $this->getTextLang('Live Chat and Support', $lang) ?: $this->l('Live Chat and Support');
            }
            $tab->save();
        }

        $tabId = Tab::getIdFromClassName('AdminLiveChat');
        if ($tabId) {
            $subTabs = array(
                array(
                    'class_name' => 'AdminLiveChatDashboard',
                    'tab_name' => $this->l('Dashboard'),
                    'tabname' => 'Dashboard',
                    'icon' => 'icon icon-dashboard',
                ),
                array(
                    'class_name' => 'AdminLiveChatTickets',
                    'tab_name' => $this->l('Tickets'),
                    'tabname' => 'Tickets',
                    'icon' => 'icon icon-ticket',
                ),
                array(
                    'class_name' => 'AdminLiveChatSettings',
                    'tab_name' => $this->l('Settings'),
                    'tabname' => 'Settings',
                    'icon' => 'icon-AdminAdmin',
                ),
                array(
                    'class_name' => 'AdminLiveChatCronJob',
                    'tab_name' => $this->l('Cronjob'),
                    'tabname' => 'Cronjob',
                    'icon' => 'icon icon-cronjob',
                ),
                array(
                    'class_name' => 'AdminLiveChatHelp',
                    'tab_name' => $this->l('Help'),
                    'tabname' => 'Help',
                    'icon' => 'icon icon-question-circle',
                )

            );
            foreach ($subTabs as $tabArg) {
                if (!Tab::getIdFromClassName($tabArg['class_name'])) {
                    $tab = new Tab();
                    $tab->class_name = $tabArg['class_name'];
                    $tab->module = 'ets_livechat';
                    $tab->id_parent = $tabId;
                    $tab->icon = $tabArg['icon'];
                    foreach ($languages as $lang) {
                        $tab->name[$lang['id_lang']] = $this->getTextLang($tabArg['tabname'], $lang) ?: $tabArg['tab_name'];
                    }
                    $tab->save();
                }
            }
        }
        return true;
    }

    public function installLinkDefault()
    {
        $metas = array(
            array(
                'controller' => 'info',
                'title' => $this->l('Chat info'),
                'tabname' => 'Chat info',
                'url_rewrite' => 'chat-info',
                'url_rewrite_lang' => $this->l('chat-info')
            ),
            array(
                'controller' => 'history',
                'title' => $this->l('Chat history'),
                'tabname' => 'Chat history',
                'url_rewrite' => 'chat-history',
                'url_rewrite_lang' => $this->l('chat-history')
            ),
            array(
                'controller' => 'ticket',
                'title' => $this->l('Support tickets'),
                'tabname' => 'Support tickets',
                'url_rewrite' => 'tickets',
                'url_rewrite_lang' => $this->l('tickets')
            ),
        );
        $languages = Language::getLanguages(false);
        foreach ($metas as $meta) {
            if (!LC_Base::getMetaByUrlRewrite($meta['url_rewrite']) && !LC_Base::getMetaByController($meta['controller'])) {
                $meta_class = new Meta();
                $meta_class->page = 'module-' . $this->name . '-' . $meta['controller'];
                $meta_class->configurable = 1;
                foreach ($languages as $language) {
                    $meta_class->title[$language['id_lang']] = $this->getTextLang($meta['tabname'], $language) ?: $meta['title'];
                    $meta_class->url_rewrite[$language['id_lang']] = ($link_rewite = $this->getTextLang($meta['url_rewrite_lang'], $language)) ? Tools::link_rewrite($link_rewite) : $meta['url_rewrite'];
                }
                $meta_class->add();
            }
        }
        return true;
    }

    private function _uninstallTabs()
    {
        $tabs = array('AdminLiveChatDashboard', 'AdminLiveChatTickets', 'AdminLiveChatSettings', 'AdminLiveChatHelp', 'AdminLiveChatCronJob');
        if ($tabs)
            foreach ($tabs as $classname) {
                if ($tabId = Tab::getIdFromClassName($classname)) {
                    $tab = new Tab($tabId);
                    if ($tab)
                        $tab->delete();
                }
            }
        if ($tabId = Tab::getIdFromClassName('AdminLiveChat')) {
            $tab = new Tab($tabId);
            if ($tab)
                $tab->delete();
        }
        return true;
    }

    private function _uninstallDb()
    {
        if ($configs = LC_Base::getInstance()->setConfig()) {
            foreach ($configs as $key => $config) {
                Configuration::deleteByName($key);
            }
            unset($config);
        }
        Configuration::deleteByName('ETS_CONVERSATION_DISPLAY_ADMIN');
        Configuration::deleteByName('ETS_LC_DATE_ACTION_LAST');
        Configuration::deleteByName('ETS_LC_NUMBER_TICKET_MANAGER');
        Configuration::deleteByName('ETS_LC_ONLY_DISPLAY_TICKET_OPEN');
        $this->rrmdir(_PS_ETS_LIVE_CHAT_UPLOAD_DIR_);
        $this->rrmdir(_PS_ETS_LIVE_CHAT_IMG_DIR_);
        if (file_exists(_PS_CACHE_DIR_.'ets_livechat_cronjob_log.txt'))
            @unlink(_PS_CACHE_DIR_.'ets_livechat_cronjob_log.txt');
        return LC_Base::dropTable();
    }

    public function createTemplateMail()
    {
        $languages = Language::getLanguages(false);
        foreach ($languages as $language) {
            $this->copy_directory(dirname(__FILE__) . '/mails/en', dirname(__FILE__) . '/mails/' . $language['iso_code']);
        }
        return true;
    }

    public function copy_directory($src, $dst)
    {
        if (is_dir($src)) {
            $dir = opendir($src);
            if (!file_exists($dst))
                @mkdir($dst);
            while (false !== ($file = readdir($dir))) {
                if (($file != '.') && ($file != '..')) {
                    if (is_dir($src . '/' . $file)) {
                        $this->copy_directory($src . '/' . $file, $dst . '/' . $file);
                    } elseif (!file_exists($dst . '/' . $file)) {
                        copy($src . '/' . $file, $dst . '/' . $file);
                    }
                }
            }
            closedir($dir);
        }
    }

    public function unInstallLinkDefault()
    {
        $metas = array(
            array(
                'controller' => 'info',
                'title' => $this->l('Chat info'),
                'url_rewrite' => 'chat-info'
            ),
            array(
                'controller' => 'history',
                'title' => $this->l('Chat-history'),
                'url_rewrite' => 'chat-history'
            ),
            array(
                'controller' => 'ticket',
                'title' => $this->l('Support tickets'),
                'url_rewrite' => 'tickets'
            ),
        );
        foreach ($metas as $meta) {
            if ($id_meta = (int)LC_Base::getMetaByController($meta['controller'])) {
                $meta_class = new Meta($id_meta);
                $meta_class->delete();
            }
        }
        return true;
    }

    public function getContent()
    {
        if(!$this->active)
            return $this->displayWarning(sprintf($this->l('You must enable "%s" module to configure its features'),$this->displayName));
        $this->link_new_employee = $this->context->link->getAdminLink('AdminEmployees',true,array('route' => 'admin_employees_create'));
        $this->setAdminForder();
        $this->baseAdminPath = $this->context->link->getAdminLink('AdminModules') . '&tabsetting=1&configure=' . $this->name;
        if (!Tools::isSubmit('tabsetting'))
            Tools::redirectAdmin($this->context->link->getAdminLink('AdminLiveChatDashboard'));
        if (Tools::isSubmit('changeInputInline') && ($name = Tools::getValue('name')) && Validate::isCleanHtml($name)) {
            $value = Tools::getValue('value');
            if ($value && !Validate::isCleanHtml($value)) {
                die(
                json_encode(
                    array(
                        'errors' => $this->l('Value is not valid'),
                    )
                )
                );
            }
            Configuration::updateValue($name, $value);
            die(
            json_encode(
                array(
                    'success' => $this->l('Updated successfully'),
                )
            )
            );
        }
        if (Tools::isSubmit('saveConfigTicket')) {
            $errors = array();
            $languages = Language::getLanguages(false);
            $id_lang_default = Configuration::get('PS_LANG_DEFAULT');
            if ($ETS_LIVECHAT_ADMIN_TICKET = (int)Tools::getValue('ETS_LIVECHAT_ADMIN_TICKET')) {
                $ETS_LC_URL_ALIAS_default = Tools::getValue('ETS_LC_URL_ALIAS_' . $id_lang_default);
                if (!$ETS_LC_URL_ALIAS_default) {
                    $errors[] = $this->l('Support URL alias is required');
                }
                $ETS_LC_URL_ALIAS = array();
                foreach ($languages as $language) {
                    $ETS_LC_URL_ALIAS[$language['id_lang']] = Tools::getValue('ETS_LC_URL_ALIAS_' . $language['id_lang']);
                    if ($ETS_LC_URL_ALIAS[$language['id_lang']] && !Validate::isLinkRewrite($ETS_LC_URL_ALIAS[$language['id_lang']]))
                        $errors[] = sprintf($this->l('Support URL alias is not valid in %s'), $language['iso_code']);
                }
                $ETS_LC_DAY_AUTO_CLOSE_TICKET = Tools::getValue('ETS_LC_DAY_AUTO_CLOSE_TICKET');
                if ($ETS_LC_DAY_AUTO_CLOSE_TICKET != '' && (!Validate::isUnsignedInt($ETS_LC_DAY_AUTO_CLOSE_TICKET) || $ETS_LC_DAY_AUTO_CLOSE_TICKET == 0))
                    $errors[] = $this->l('Number of days to automatically close support ticket is not valid');
                $ETS_LC_NUMBER_TICKET_MESSAGES = Tools::getValue('ETS_LC_NUMBER_TICKET_MESSAGES');
                if ($ETS_LC_NUMBER_TICKET_MESSAGES != '' && (!Validate::isInt($ETS_LC_NUMBER_TICKET_MESSAGES) || $ETS_LC_NUMBER_TICKET_MESSAGES <= 0))
                    $errors[] = $this->l('Number of messages to display is not valid');
                $ETS_LC_NUMBER_TICKET_MANAGER = Tools::getValue('ETS_LC_NUMBER_TICKET_MANAGER', '');
                if (trim((string) $ETS_LC_NUMBER_TICKET_MANAGER) !== '') {
                    $mails = explode(',', (string) $ETS_LC_NUMBER_TICKET_MANAGER);
                    foreach ($mails as $mail) {
                        if (trim($mail) == '') {
                            $errors[] = $this->l('Each email separated by a comma (,)');
                            break;
                        } elseif (!Validate::isEmail(trim($mail))) {
                            $errors[] = sprintf($this->l('Email - %s is invalid'), $mail);
                        } elseif (!Customer::customerExists(trim($mail))) {
                            $errors[] = sprintf($this->l('Email - %s does not exist.'), $mail);
                        }
                    }
                }
                if ($errors) {
                    die(json_encode(array(
                        'errors' => $this->displayError($errors),
                    )));
                } else {
                    $ETS_LC_DISPLAY_PRODUCTS_IN_LIST_TICKET = (int)Tools::getValue('ETS_LC_DISPLAY_PRODUCTS_IN_LIST_TICKET');
                    $ETS_LC_DISPLAY_STAFF_IN_LIST_TICKET = (int)Tools::getValue('ETS_LC_DISPLAY_STAFF_IN_LIST_TICKET');
                    $ETS_LC_DISPLAY_ORDER_IN_LIST_TICKET = (int)Tools::getValue('ETS_LC_DISPLAY_ORDER_IN_LIST_TICKET');
                    $ETS_LC_URL_SUBFIX = (int)Tools::getValue('ETS_LC_URL_SUBFIX');
                    $ETS_LC_URL_REMOVE_ID = (int)Tools::getValue('ETS_LC_URL_REMOVE_ID');
                    $ETS_LC_ONLY_DISPLAY_TICKET_OPEN = (int)Tools::getValue('ETS_LC_ONLY_DISPLAY_TICKET_OPEN');
                    Configuration::deleteByName('PS_ROUTE_livechatform');
                    Configuration::deleteByName('PS_ROUTE_livechatformnoid');
                    Configuration::updateValue('ETS_LIVECHAT_ADMIN_TICKET', $ETS_LIVECHAT_ADMIN_TICKET);
                    Configuration::updateValue('ETS_LC_DAY_AUTO_CLOSE_TICKET', $ETS_LC_DAY_AUTO_CLOSE_TICKET);
                    Configuration::updateValue('ETS_LC_DISPLAY_PRODUCTS_IN_LIST_TICKET', $ETS_LC_DISPLAY_PRODUCTS_IN_LIST_TICKET);
                    Configuration::updateValue('ETS_LC_DISPLAY_ORDER_IN_LIST_TICKET',$ETS_LC_DISPLAY_ORDER_IN_LIST_TICKET);
                    Configuration::updateValue('ETS_LC_DISPLAY_STAFF_IN_LIST_TICKET', $ETS_LC_DISPLAY_STAFF_IN_LIST_TICKET);
                    Configuration::updateValue('ETS_LC_NUMBER_TICKET_MESSAGES', $ETS_LC_NUMBER_TICKET_MESSAGES);
                    Configuration::updateValue('ETS_LC_NUMBER_TICKET_MANAGER', trim((string) $ETS_LC_NUMBER_TICKET_MANAGER), true);
                    Configuration::updateValue('ETS_LC_ONLY_DISPLAY_TICKET_OPEN',$ETS_LC_ONLY_DISPLAY_TICKET_OPEN);
                    if ($ETS_LIVECHAT_ADMIN_TICKET) {
                        Configuration::updateValue('ETS_LC_URL_SUBFIX', $ETS_LC_URL_SUBFIX);
                        Configuration::updateValue('ETS_LC_URL_REMOVE_ID', $ETS_LC_URL_REMOVE_ID);
                        $alias = array();
                        foreach ($languages as $language)
                            $alias[$language['id_lang']] = $ETS_LC_URL_ALIAS[$language['id_lang']] ?: $ETS_LC_URL_ALIAS[$id_lang_default];
                        Configuration::updateValue('ETS_LC_URL_ALIAS', $alias);
                    }
                    $forms = LC_Ticket_form::getForms(false,false,null,null,false);
                    if ($forms) {
                        foreach ($forms as &$form) {
                            $form['link'] = $this->getFormLink($form['id_form']);
                        }
                    }
                    die(
                        json_encode(
                            array(
                                'success' => $this->l('Updated successfully'),
                                'forms' => $forms
                            )
                        )
                    );
                }
            } else {
                Configuration::updateValue('ETS_LIVECHAT_ADMIN_TICKET', 0);
                die(
                    json_encode(
                        array(
                            'success' => $this->l('Updated successfully'),
                            'forms' => '',
                        )
                    )
                );
            }

        }
        if ($this->context->employee->id_profile != 1) {
            $this->context->smarty->assign(
                array(
                    'form_html' => $this->getFormStaff($this->context->employee->id),
                    'action' => $this->context->link->getAdminLink('AdminModules') . '&tabsetting=1&configure=ets_livechat',
                )
            );
            return $this->displayAdminJs() . $this->displayMenuTop() . $this->display(__FILE__, 'my_info.tpl');
        }
        if (Tools::isSubmit('saveFormTicket')) {
            $this->saveObjForm();
        }
        if (Tools::isSubmit('get_form_ticket_form')) {
            $this->_displayFormTicket();
        }
        $this->context->controller->addJqueryUI('ui.sortable');
        if (($action = Tools::getValue('action')) && $action == 'updatePreMadeMessageOrdering') {
            if (($pre_made_message = Tools::getValue('pre_made_message')) && LC_Tools::validateArray($pre_made_message, 'isInt')) {
                LC_MadeMessage::updatePositionMadeMessages($pre_made_message);
            }
        }
        $this->_postConfig();
        //Display errors if have
        if ($this->errorMessage)
            $this->_html .= $this->errorMessage;
        //Add js
        $this->_html .= $this->displayAdminJs();
        if ($this->all_shop)
            $this->_html .= $this->displayMenuTop().$this->display(__FILE__, 'allshop.tpl');
        else
            $this->_html .= $this->displayMenuTop();
        //Render views
        $this->renderConfig();
        if (!Module::isEnabled($this->name))
            return $this->display(__FILE__, 'disabled.tpl');
        return $this->_html;
    }

    public function displayAdminJs()
    {
        $this->context->controller->addJqueryPlugin('tagify');
        $current_tab_active = Tools::getValue('current_tab_acitve', Tools::getValue('ETS_TAB_CURENT_ACTIVE', 'status'));
        $this->smarty->assign(array(
            'ETS_LC_MODULE_URL' => $this->_path,
            'current_tab_active' => $current_tab_active && Validate::isCleanHtml($current_tab_active) ? $current_tab_active : 'status',
            'lc_default_lang' => Configuration::get('PS_LANG_DEFAULT'),
            'PS_BASE_URI' => __PS_BASE_URI__,
            'ps15' => version_compare(_PS_VERSION_, '1.6', '<'),
            'PS_ALLOW_ACCENTED_CHARS_URL' => Configuration::get('PS_ALLOW_ACCENTED_CHARS_URL'),
        ));
        return $this->display(__FILE__, 'admin-js.tpl');
    }

    public function renderConfig()
    {
        $configs = LC_Base::getInstance()->setConfig();
        $fields_form = array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l('Live Chat configuration'),
                    'icon' => 'icon-AdminAdmin'
                ),
                'input' => array(),
                'submit' => array(
                    'title' => $this->l('Save'),
                )
            ),
        );
        if ($configs) {
            foreach ($configs as $key => $config) {
                $confFields = array(
                    'name' => isset($config['multiple']) && $config['multiple'] ? $key . '[]' : $key,
                    'type' => $config['type'],
                    'label' => $config['label'],
                    'desc' => isset($config['desc']) ? $config['desc'] : false,
                    'required' => isset($config['required']) && $config['required'] && $config['type'] != 'switch' ? true : false,
                    'autoload_rte' => isset($config['autoload_rte']) && $config['autoload_rte'] ? true : false,
                    'options' => isset($config['options']) && $config['options'] ? $config['options'] : array(),
                    'suffix' => isset($config['suffix']) && $config['suffix'] ? $config['suffix'] : false,
                    'form_group_class' => isset($config['form_group_class']) ? $config['form_group_class'] : '',
                    'all' => isset($config['all']) ? $config['all'] : false,
                    'values' => $config['type'] == 'switch' ? array(
                        array(
                            'id' => 'active_on',
                            'value' => 1,
                            'label' => $this->l('Yes')
                        ),
                        array(
                            'id' => 'active_off',
                            'value' => 0,
                            'label' => $this->l('No')
                        )
                    ) : (isset($config['values']) ? $config['values'] : false),
                    'lang' => isset($config['lang']) ? $config['lang'] : false,
                    'multiple' => isset($config['multiple']) && $config['multiple'],
                    'tab' => isset($config['tab']) ? $config['tab'] : '',
                );
                if (!$confFields['suffix'])
                    unset($confFields['suffix']);
                $fields_form['form']['input'][] = $confFields;
            }
        }
        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $lang = new Language((int)Configuration::get('PS_LANG_DEFAULT'));
        $helper->default_form_language = $lang->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') ? Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') : 0;
        $helper->module = $this;
        $helper->identifier = $this->identifier;
        $helper->submit_action = 'saveConfig';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false) . '&tabsetting=1&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name . '&control=config';
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $language = new Language((int)Configuration::get('PS_LANG_DEFAULT'));
        $fields = array();
        $languages = Language::getLanguages(false);
        $helper->override_folder = '/';
        if (Tools::isSubmit('saveConfig')) {
            if ($configs) {
                foreach ($configs as $key => $config) {
                    if (isset($config['lang']) && $config['lang']) {
                        foreach ($languages as $l) {
                            $fields[$key][$l['id_lang']] = str_replace(array('%5B', '%5D'), array('[', ']'), Tools::getValue($key . '_' . $l['id_lang'], isset($config['default']) ? $config['default'] : ''));
                        }
                    } elseif ($config['type'] == 'checkbox') {
                        $fields[$key] = in_array('all', Tools::getValue($key, array())) ? 'all' : Tools::getValue($key, array());

                    } elseif ($config['type'] == 'select' && isset($config['multiple']) && $config['multiple']) {
                        $fields[$key . '[]'] = Tools::getValue($key, array());
                    } else
                        $fields[$key] = Tools::getValue($key, isset($config['default']) ? $config['default'] : '');
                }
            }
        } else {
            if ($configs) {
                foreach ($configs as $key => $config) {
                    if (isset($config['lang']) && $config['lang']) {
                        foreach ($languages as $l) {
                            $fields[$key][$l['id_lang']] = str_replace(array('%5B', '%5D'), array('[', ']'), Configuration::get($key, $l['id_lang']));
                        }
                    } elseif ($config['type'] == 'checkbox') {
                        $fields[$key] = Configuration::get($key) ? (Configuration::get($key) != 'all' ? explode(',', Configuration::get($key)) : Configuration::get($key)) : array();
                    } elseif ($config['type'] == 'select' && isset($config['multiple']) && $config['multiple']) {
                        $fields[$key . '[]'] = Configuration::get($key) != '' ? explode(',', Configuration::get($key)) : array();
                    } else
                        $fields[$key] = Configuration::get($key);
                }
            }
        }
        $display_bubble_imge = Configuration::get('ETS_LC_BUBBLE_IMAGE') ? _PS_ETS_LIVE_CHAT_IMG_ . Configuration::get('ETS_LC_BUBBLE_IMAGE') : '';
        $controller = Tools::getValue('controller');
        $helper->tpl_vars = array(
            'base_url' => $this->context->shop->getBaseURL(),
            'language' => array(
                'id_lang' => $language->id,
                'iso_code' => $language->iso_code
            ),
            'fields_value' => $fields,
            'languages' => $this->context->controller->getLanguages(),
            'isConfigForm' => true,
            'display_logo' => Configuration::get('ETS_LC_COMPANY_LOGO') ? _PS_ETS_LIVE_CHAT_IMG_ . Configuration::get('ETS_LC_COMPANY_LOGO') : '',
            'logo_del_link' => $this->baseAdminPath . '&delimage=yes&image=ETS_LC_COMPANY_LOGO',
            'display_bubble_imge' => $display_bubble_imge,
            'bubble_imge_del_link' => $this->baseAdminPath . '&delimage=yes&image=ETS_LC_BUBBLE_IMAGE',
            'display_avata' => Configuration::get('ETS_LC_CUSTOMER_AVATA') ? _PS_ETS_LIVE_CHAT_IMG_ . Configuration::get('ETS_LC_CUSTOMER_AVATA') : '',
            'avata_del_link' => $this->baseAdminPath . '&delimage=yes&image=ETS_LC_CUSTOMER_AVATA',
            'configTabs' => $this->lc_configTabs,
            'id_language' => $this->context->language->id,
            'time_zone' => date_default_timezone_get(),
            'time_now' => date('Y-m-d H:i:s'),
            'enable_livechat' =>Validate::isControllerName($controller) && LC_Conversation::checkEnableLivechat($controller),
            'is_ps15' => version_compare(_PS_VERSION_, '1.6', '<'),
            'link_callback' => $this->context->link->getModuleLink($this->name, 'callback'),
        );
        $this->_html .= $helper->generateForm(array($fields_form));
    }

    private function _postConfig()
    {
        $errors = array();
        $languages = Language::getLanguages(false);
        $id_lang_default = (int)Configuration::get('PS_LANG_DEFAULT');
        $configs = LC_Base::getInstance()->setConfig();
        //Delete image
        if (Tools::isSubmit('delimage') && ($image = Tools::getValue('image')) && Validate::isCleanHtml($image)) {
            if (isset($configs[$image]) && !isset($configs[$image]['required']) || (isset($configs[$image]['required']) & !$configs[$image]['required'])) {
                if ($this->all_shop && $this->shops) {
                    foreach ($this->shops as $shop) {
                        $imageName = Configuration::get($image, null, $shop['id_shop_group'], $shop['id_shop']);
                        $imagePath = _PS_ETS_LIVE_CHAT_IMG_DIR_ . $imageName;
                        if ($imageName && file_exists($imagePath)) {
                            if ($imageName != 'customeravata.jpg' && $imageName != 'adminavatar.jpg')
                                @unlink($imagePath);
                            Configuration::updateValue($image, '', false, $shop['id_shop_group'], $shop['id_shop']);
                        }
                    }
                }
                $imageName = Configuration::get($image);
                $imagePath = _PS_ETS_LIVE_CHAT_IMG_DIR_ . $imageName;
                if ($imageName && file_exists($imagePath)) {
                    if ($imageName != 'customeravata.jpg' && $imageName != 'adminavatar.jpg')
                        @unlink($imagePath);
                    Configuration::updateValue($image, '');
                }
                Tools::redirectAdmin($this->context->link->getAdminLink('AdminModules', true) . '&tabsetting=1&conf=4&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name . '&current_tab_acitve=chat_box');
            } else
                $errors[] = sprintf($this->l('%s is required'), $configs[$image]['label']);
        }
        if (Tools::isSubmit('saveConfig') && !Tools::isSubmit('submitFilterChart')) {
            if ($configs) {
                foreach ($configs as $key => $config) {
                    if (isset($config['lang']) && $config['lang']) {
                        $val_lang_default = Tools::getValue($key . '_' . $id_lang_default);
                        if (isset($config['required']) && $config['required'] && $config['type'] != 'switch' && trim($val_lang_default) == '') {
                            $errors[] = sprintf($this->l('%s is required'), $config['label']);
                        } elseif ($val_lang_default && !Validate::isCleanHtml($val_lang_default))
                            $errors[] = sprintf($this->l('%s is not valid in default language'), $config['label']);
                        else {
                            foreach ($languages as $language) {
                                if ($language['id_lang'] != $id_lang_default) {
                                    $val_lang = Tools::getValue($key . '_' . $language['id_lang']);
                                    if ($val_lang && !Validate::isCleanHtml($val_lang))
                                        $errors[] = sprintf($this->l('%s is not valid in language %s'), $config['label'], $language['iso_code']);
                                }
                            }
                        }
                    } else {
                        $key_val = Tools::getValue($key);
                        if (isset($config['type']) && $config['type'] == 'file' && isset($_FILES[$key]["name"]) && $_FILES[$key]["name"]) {
                            $_FILES[$key]["name"] = str_replace(array(' ','(',')','!','@','#','+'), '_', $_FILES[$key]["name"]);
                            if (!Validate::isFileName($_FILES[$key]["name"]))
                                $errors[] = '"' . $_FILES[$key]["name"] . '" ' . $this->l('is invalid');
                            else {
                                $imageFileType = Tools::strtolower(pathinfo(basename($_FILES[$key]["name"]), PATHINFO_EXTENSION));
                                if (!in_array($imageFileType,array('jpg','png','gif','jpeg','webp'))) {
                                    $errors[] = $config['label'] . " is invalid.";
                                }
                            }

                        }
                        if (isset($config['type']) && $config['type'] == 'file') {
                            if (isset($config['required']) && $config['required'] === true && Configuration::get($key) == '' && !isset($_FILES[$key]['size']))
                                $errors[] = $config['label'] . ' ' . $this->l('is required');
                            if (isset($_FILES[$key]['size']) && $_FILES[$key]['size'] ) {
                                $maxFileSize = Configuration::get('PS_ATTACHMENT_MAXIMUM_SIZE');
                                $fileSize = round($_FILES[$key]['size'] / (1024 * 1024),2);
                                if ($fileSize > $maxFileSize)
                                    $errors[] = sprintf($this->l('%s cannot be larger than %dMb'),$config['label'],$maxFileSize);
                            }
                        } else {
                            if (isset($config['required']) && $config['required'] === true && $config['type'] != 'switch' && trim($key_val) == '') {
                                $errors[] = sprintf($this->l('%s is required'), $config['label']);
                            } elseif (!is_array($key_val) && isset($config['validate']) && method_exists('Validate', $config['validate'])) {
                                $validate = $config['validate'];
                                if (trim($key_val) && !Validate::$validate(trim($key_val)))
                                    $errors[] = sprintf($this->l('%s is invalid'), $config['label']);
                                unset($validate);
                            } elseif (Tools::isSubmit($key) && !is_array($key_val) && !Validate::isCleanHtml(trim($key_val))) {
                                $errors[] = sprintf($this->l('%s is invalid'), $config['label']);
                            }
                        }
                    }
                }
            }
            $ETS_LC_MSG_COUNT = Tools::getValue('ETS_LC_MSG_COUNT');
            if (!Validate::isUnsignedInt($ETS_LC_MSG_COUNT))
                $errors[] = $this->l('Message count is invalid');
            $ETS_LC_TIME_OUT = Tools::getValue('ETS_LC_TIME_OUT');
            if ($ETS_LC_TIME_OUT != '' && (!Validate::isUnsignedInt($ETS_LC_TIME_OUT) || $ETS_LC_TIME_OUT == '0' || $ETS_LC_TIME_OUT < 1000))
                $errors[] = $this->l('Refresh speed front end, min 1000');
            $ETS_LC_TIME_OUT_BACK_END = Tools::getValue('ETS_LC_TIME_OUT_BACK_END');
            if ($ETS_LC_TIME_OUT_BACK_END != '' && (!Validate::isUnsignedInt($ETS_LC_TIME_OUT_BACK_END) || $ETS_LC_TIME_OUT_BACK_END == '0' || $ETS_LC_TIME_OUT_BACK_END < 1000))
                $errors[] = $this->l('Refresh speed back end, min 1000');
            $ETS_LC_BOX_WIDTH = Tools::getValue('ETS_LC_BOX_WIDTH');
            if ($ETS_LC_BOX_WIDTH != '' && (!Validate::isUnsignedInt($ETS_LC_BOX_WIDTH) || $ETS_LC_BOX_WIDTH == '0' || $ETS_LC_BOX_WIDTH < 300))
                $errors[] = $this->l('Chat box width is invalid, min 300');
            $ETS_LC_MSG_LENGTH = Tools::getValue('ETS_LC_MSG_LENGTH');
            if ($ETS_LC_MSG_LENGTH != '' && (!Validate::isUnsignedInt($ETS_LC_MSG_LENGTH) || $ETS_LC_MSG_LENGTH == '0' || $ETS_LC_MSG_LENGTH > 1000 || $ETS_LC_MSG_LENGTH < 10))
                $errors[] = $this->l('Message length is invalid, min 10, max 1000');
            $ETS_LC_MSG_COUNT = Tools::getValue('ETS_LC_MSG_COUNT');
            if ($ETS_LC_MSG_COUNT != '' && (!Validate::isUnsignedInt($ETS_LC_MSG_COUNT) || $ETS_LC_MSG_COUNT == '0' || $ETS_LC_MSG_COUNT < 3))
                $errors[] = $this->l('Message count is invalid, min 3');
            $ETS_LC_MAX_FILE_MS = Tools::getValue('ETS_LC_MAX_FILE_MS');
            if ($ETS_LC_MAX_FILE_MS != '' && $ETS_LC_MAX_FILE_MS == 0) {
                $errors[] = $this->l('Max upload file size is invalid');
            }
            $ETS_LC_NUMBER_FILE_MS = Tools::getValue('ETS_LC_NUMBER_FILE_MS');
            if ($ETS_LC_NUMBER_FILE_MS != '' && $ETS_LC_NUMBER_FILE_MS == 0) {
                $errors[] = $this->l('Maximum number of files that customer can upload per conversation');
            }
            if (($ETS_LIVECHAT_ENABLE_FACEBOOK = (int)Tools::getValue('ETS_LIVECHAT_ENABLE_FACEBOOK')) && Validate::isInt($ETS_LIVECHAT_ENABLE_FACEBOOK)) {
                if (!($ETS_LIVECHAT_FACEBOOK_APP_ID = Tools::getValue('ETS_LIVECHAT_FACEBOOK_APP_ID')))
                    $errors[] = $this->l('Facebook application ID is required');
                elseif (!Validate::isCleanHtml($ETS_LIVECHAT_FACEBOOK_APP_ID))
                    $errors[] = $this->l('Facebook application ID is not valid');
                if (!($ETS_LIVECHAT_FACEBOOK_APP_SECRET = Tools::getValue('ETS_LIVECHAT_FACEBOOK_APP_SECRET')))
                    $errors[] = $this->l('Facebook application secret is required');
                elseif (!Validate::isCleanHtml($ETS_LIVECHAT_FACEBOOK_APP_SECRET))
                    $errors[] = $this->l('Facebook application secret is not valid');
            }
            if (($ETS_LIVECHAT_ENABLE_GOOGLE = (int)Tools::getValue('ETS_LIVECHAT_ENABLE_GOOGLE')) && Validate::isInt($ETS_LIVECHAT_ENABLE_GOOGLE)) {
                if (!($ETS_LIVECHAT_GOOGLE_APP_ID = Tools::getValue('ETS_LIVECHAT_GOOGLE_APP_ID')))
                    $errors[] = $this->l('Google application ID is required');
                elseif (!Validate::isCleanHtml($ETS_LIVECHAT_GOOGLE_APP_ID))
                    $errors[] = $this->l('Google application ID is not valid');
                if (!($ETS_LIVECHAT_GOOGLE_APP_SECRET = Tools::getValue('ETS_LIVECHAT_GOOGLE_APP_SECRET')))
                    $errors[] = $this->l('Google application secret is required');
                elseif (!Validate::isCleanHtml($ETS_LIVECHAT_GOOGLE_APP_SECRET))
                    $errors[] = $this->l('Google application secret is not valid');
            }
            if (($ETS_LIVECHAT_ENABLE_TWITTER = (int)Tools::getValue('ETS_LIVECHAT_ENABLE_TWITTER')) && Validate::isInt($ETS_LIVECHAT_ENABLE_TWITTER)) {
                if (!($ETS_LIVECHAT_TWITTER_APP_ID = Tools::getValue('ETS_LIVECHAT_TWITTER_APP_ID')))
                    $errors[] = $this->l('X application ID is required');
                elseif (!Validate::isCleanHtml($ETS_LIVECHAT_TWITTER_APP_ID))
                    $errors[] = $this->l('X application ID is not valid');
                if (!($ETS_LIVECHAT_TWITTER_APP_SECRET = Tools::getValue('ETS_LIVECHAT_TWITTER_APP_SECRET')))
                    $errors[] = $this->l('X application secret is required');
                elseif (!Validate::isCleanHtml($ETS_LIVECHAT_TWITTER_APP_SECRET))
                    $errors[] = $this->l('X application secret is not valid');
            }
            if (($ETS_LC_MAIL_TO = Tools::getValue('ETS_LC_MAIL_TO')) && in_array('custom', $ETS_LC_MAIL_TO)) {
                if (($ETS_LC_CUSTOM_EMAIL = Tools::getValue('ETS_LC_CUSTOM_EMAIL'))) {
                    $emails = explode(',', $ETS_LC_CUSTOM_EMAIL);
                    foreach ($emails as $email) {
                        if (!Validate::isEmail($email)) {
                            $errors[] = $this->l('Custom emails is invalid');
                        }
                    }
                } else
                    $errors[] = $this->l('Custom emails is invalid');
            }
            //Custom validation
            if (!$errors) {
                if ($configs) {
                    foreach ($configs as $key => $config) {
                        if (isset($config['lang']) && $config['lang']) {
                            $valules = array();
                            foreach ($languages as $lang) {
                                if ($config['type'] == 'switch')
                                    $valules[$lang['id_lang']] = (int)trim((string) Tools::getValue($key . '_' . $lang['id_lang'], '')) ? 1 : 0;
                                else
                                    $valules[$lang['id_lang']] = trim((string) Tools::getValue($key . '_' . $lang['id_lang'], '')) ?: trim((string) Tools::getValue($key . '_' . $id_lang_default, ''));
                            }
                            if ($this->all_shop && $this->shops) {
                                foreach ($this->shops as $shop) {
                                    Configuration::updateValue($key, $valules, true, $shop['id_shop_group'], $shop['id_shop']);
                                }
                            }
                            Configuration::updateValue($key, $valules, true);
                        } else {
                            $val = Tools::getValue($key, '');
                            if ($config['type'] == 'switch') {
                                if ($this->all_shop && $this->shops) {
                                    foreach ($this->shops as $shop) {
                                        Configuration::updateValue($key, (int)trim((string) $val) ? 1 : 0, true, $shop['id_shop_group'], $shop['id_shop']);
                                    }
                                }
                                Configuration::updateValue($key, (int)trim((string) $val) ? 1 : 0, true);
                            } elseif ($config['type'] == 'checkbox') {
                                if ($this->all_shop && $this->shops) {
                                    Configuration::updateValue($key, $val && is_array($val) ? (in_array('all', $val) ? 'all' : implode(',', $val)) : '', true, $shop['id_shop_group'], $shop['id_shop']);
                                }
                                Configuration::updateValue($key, $val && is_array($val) ? (in_array('all', $val) ? 'all' : implode(',', $val)) : '', true);
                            } elseif ($config['type'] == 'file') {
                                //Upload file
                                if (isset($_FILES[$key]['tmp_name']) && isset($_FILES[$key]['name']) && $_FILES[$key]['name']) {
                                    $type = Tools::strtolower(Tools::substr(strrchr($_FILES[$key]['name'], '.'), 1));
                                    $imageName = $_FILES[$key]['name'];
                                    $fileName = _PS_ETS_LIVE_CHAT_IMG_DIR_ . $imageName;
                                    if (file_exists($fileName)) {
                                        $time = md5(time());
                                        for ($i = 0; $i < 6; $i++) {
                                            $index = rand(0, Tools::strlen($time) - 1);
                                            $imageName = $time[$index] . $imageName;
                                        }
                                        $fileName = _PS_ETS_LIVE_CHAT_IMG_DIR_ . $imageName;
                                    }
                                    if (file_exists($fileName)) {
                                        $errors[] = $config['label'] . $this->l(' already exists. Try to rename the file then reupload');
                                    } else {
                                        $imagesize = @getimagesize($_FILES[$key]['tmp_name']);
                                        if (!$errors && isset($_FILES[$key]) &&
                                            !empty($_FILES[$key]['tmp_name']) &&
                                            !empty($imagesize) &&
                                            in_array($type, array('jpg', 'gif', 'jpeg', 'png','webp'))
                                        ) {
                                            if (!move_uploaded_file($_FILES[$key]['tmp_name'], $fileName))
                                                $errors[] = $this->l('Cannot upload the file');
                                            if (!$errors) {
                                                if ($this->all_shop && $this->shops) {
                                                    foreach ($this->shops as $shop) {
                                                        $oldImage = Configuration::get($key, null, $shop['id_shop_group'], $shop['id_shop']);
                                                        Tools::copy(_PS_ETS_LIVE_CHAT_IMG_DIR_ . $imageName, _PS_ETS_LIVE_CHAT_IMG_DIR_ . $shop['id_shop'] . $imageName);
                                                        Configuration::updateValue($key, $shop['id_shop'] . $imageName, true, $shop['id_shop_group'], $shop['id_shop']);
                                                        if ($oldImage && $oldImage != 'customeravata.jpg' && $oldImage != 'adminavatar.jpg') {
                                                            if (file_exists(_PS_ETS_LIVE_CHAT_IMG_DIR_ . $oldImage))
                                                                @unlink(_PS_ETS_LIVE_CHAT_IMG_DIR_ . $oldImage);
                                                        }
                                                    }
                                                } else {
                                                    $oldImage = Configuration::get($key);
                                                    Configuration::updateValue($key, $imageName, true);
                                                    if ($oldImage && file_exists(_PS_ETS_LIVE_CHAT_IMG_DIR_ . $oldImage) && $oldImage != 'customeravata.jpg' && $oldImage != 'adminavatar.jpg')
                                                        @unlink(_PS_ETS_LIVE_CHAT_IMG_DIR_ . $oldImage);
                                                }
                                            }
                                        }
                                    }
                                }
                                //End upload file
                            } elseif ($config['type'] == 'select' && isset($config['multiple']) && $config['multiple']) {
                                if ($this->all_shop && $this->shops) {
                                    foreach ($this->shops as $shop) {
                                        Configuration::updateValue($key, implode(',', $val), true, $shop['id_shop_group'], $shop['id_shop']);
                                    }
                                }
                                Configuration::updateValue($key, implode(',', $val));
                            } else {
                                if ($this->all_shop && $this->shops) {
                                    foreach ($this->shops as $shop) {
                                        Configuration::updateValue($key, trim($val), true, $shop['id_shop_group'], $shop['id_shop']);
                                    }
                                }
                                Configuration::updateValue($key, trim($val), true);
                            }

                        }
                    }
                }
            }
            if (count($errors)) {
                if (Tools::isSubmit('run_ajax')) {
                    die(json_encode(
                        array(
                            'error' => true,
                            'errors' => $this->displayError($errors),
                        )
                    ));
                }
                $this->errorMessage = $this->displayError($errors);
            } else {
                if (Tools::isSubmit('run_ajax')) {
                    die(json_encode(
                        array(
                            'error' => false,
                        )
                    ));
                }
                $current_tab_acitve = Tools::getValue('ETS_TAB_CURENT_ACTIVE');
                Tools::redirectAdmin($this->context->link->getAdminLink('AdminModules', true) . '&tabsetting=1&conf=4&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name . '&current_tab_acitve=' . ($current_tab_acitve && Validate::isCleanHtml($current_tab_acitve) ? $current_tab_acitve : ''));
            }

        }
    }

    public function getConfigs($js = false, $id_lang = false)
    {
        if (!$id_lang)
            $id_lang = $this->context->language->id;
        $configs = array();
        foreach (LC_Base::getInstance()->setConfig() as $key => $val) {
            if ($js && (!isset($val['js']) || isset($val['js']) && !$val['js']))
                continue;
            $configs[$key] = isset($val['lang']) && $val['lang'] ? Tools::getValue($key . '_' . $id_lang, Configuration::get($key, $id_lang)) : Tools::getValue($key, Configuration::get($key));
        }
        return $configs;
    }

    public function strToIds($str)
    {
        $ids = array();
        if ($temp = explode(',', $str)) {
            foreach ($temp as $id)
                if (!in_array((int)$id, $ids))
                    $ids[] = (int)$id;
        }
        return $ids;
    }

    public function formatTime($datetime, $full = false)
    {
        $now = new DateTime;
        $ago = new DateTime($datetime);
        $diff = $now->diff($ago);
        $diff->w = floor($diff->d / 7);
        $diff->d -= $diff->w * 7;
        $string = array(
            'y' => $this->l('year'),
            'm' => $this->l('month'),
            'w' => $this->l('week'),
            'd' => $this->l('day'),
            'h' => $this->l('hour'),
            'i' => $this->l('minute'),
            's' => $this->l('second'),
        );
        foreach ($string as $k => &$v) {
            if ($diff->$k) {
                $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? $this->l('s') : '');
            } else {
                unset($string[$k]);
            }
        }
        if (!$full) $string = array_slice($string, 0, 1);
        return $string ? implode(', ', $string) . $this->l(' ago') : $this->l('just now');
    }
    //Views
    public function hookDisplayHeader()
    {
		$this->context->controller->addJqueryUI('dragable');
        $controller = Tools::getValue('controller');
        if($controller == 'orderdetail')
        {
            $this->context->controller->addJS($this->_path . 'views/js/order.js');
        }
        $module = Tools::getValue('module');
        if($this->context->controller instanceof IdentityController)
        {
            $this->context->controller->addJS($this->_path . 'views/js/info.js');
            $this->context->controller->addJS($this->_path . 'views/js/my_account.js');
            $this->context->controller->addCSS($this->_path . 'views/css/info.css', 'all');
        }
        if (!LC_Conversation::checkAccess() && $module != $this->name) {
            $this->context->controller->addCSS($this->_path . 'views/css/account.css', 'all');
            return;
        } else {
            $this->context->controller->addCSS($this->_path . 'views/css/my_account.css', 'all');
            $this->context->controller->addCSS($this->_path . 'views/css/livechat.css', 'all');
        }

        if (LC_Conversation::checkAccess()) {
            if (version_compare(_PS_VERSION_, '1.6', '<')) {
                $this->context->controller->addJS($this->_path . 'views/js/livechat15.js');
            } else {
                $this->context->controller->addJS($this->_path . 'views/js/livechat.js');
            }
        }
        $assigns = $this->getConfigs(true);
        $assigns['ETS_LC_URL_AJAX'] = $this->context->link->getModuleLink($this->name, 'ajax', array('token' => Configuration::getGlobalValue('ETS_LC_FO_TOKEN')));
        $assigns['ETS_LC_URL_OAUTH'] = $this->context->link->getModuleLink($this->name, 'oauth');
        $conversation = LC_Conversation::getCustomerConversation();
        $this->smarty->assign(
            array(
                'assigns' => $assigns,
                'isRequestAjax' => $conversation ? $conversation->isJquestAjax() : 0,
            )
        );
        if ($conversation && $module != $this->name) {
            if (empty($_SERVER['HTTP_X_REQUESTED_WITH']) || Tools::strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) != 'xmlhttprequest') {
                $current_url = $this->getLinkCurrentByUrl();
                $conversation->current_url = $current_url;
                $conversation->update();
            }
        }
        $this->context->controller->addJqueryPlugin('growl');
        $this->context->controller->addJS($this->_path . 'views/js/jquery.rating.pack.js');
        if (version_compare(_PS_VERSION_, '1.6', '<')) {

            $this->context->controller->addCSS($this->_path . 'views/css/livechat15.css', 'all');
        }
        if (version_compare(_PS_VERSION_, '1.7', '<'))
            $this->context->controller->addJqueryUI('ui.draggable');
        if (version_compare(_PS_VERSION_, '1.7', '<'))
            $this->context->controller->addCSS($this->_path . 'views/css/my_account16.css', 'all');
        if ($module == $this->name) {
            $this->context->controller->addJqueryUI('ui.datepicker');
            $this->context->controller->addJS($this->_path . 'views/js/my_account.js');
            $controller = Tools::getValue('controller');
            if($controller=='form')
            {
                $this->context->controller->addJqueryPlugin('autocomplete');
                $this->context->smarty->assign(
                    array(
                        'ets_lc_link_search_product' => $this->context->link->getModuleLink($this->name,'ajax',array('submitSearchProduct'=>1)),
                    )
                );
                $this->context->controller->addJS($this->_path . 'views/js/search_product.js');
                $this->context->controller->addCSS($this->_path . 'views/css/search_product.css', 'all');
            }
        }
        return $this->display(__FILE__, 'header.tpl');
    }
    public function hookRegisterGDPRConsent()
    {
        /* registerGDPRConsent is a special kind of hook that doesn't need a listener, see :
           https://build.prestashop.com/howtos/module/how-to-make-your-module-compliant-with-prestashop-official-gdpr-compliance-module/
          However since Prestashop 1.7.8, modules must implement a listener for all the hooks they register: a check is made
          at module installation.
        */
    }
    public function addTwigVar($key, $value)
    {
        if ($sfContainer = $this->getSfContainer()) {
            $sfContainer->get('twig')->addGlobal($key, $value);
        }
    }
    public function hookDisplayBackOfficeHeader()
    {
        $controller = Tools::getValue('controller');
        $configure = Tools::getValue('configure');
        if($this->is17 && version_compare(_PS_VERSION_, '8.0.0', '<') && (Tools::strtolower($controller)=='admincustomers' || Tools::strtolower($controller)=='adminorders'))
        {
            $twigs = $this->getTwigs();
            foreach($twigs as $key=>$value)
            {
                $this->addTwigVar($key,$value);
            }
        }
        if($controller=='AdminOrders' || $controller=='AdminCustomers' || $controller=='AdminPhMdLicense')
        {
            $this->context->controller->addCSS($this->_path . 'views/css/link_ticket.css', 'all');
            $this->context->controller->addJS($this->_path . 'views/js/link_ticket.js');
            $this->context->controller->addCSS($this->_path . 'views/css/livechat.admin.css', 'all');
            $this->context->controller->addJS($this->_path . 'views/js/ticket.js');
        }
        if (LC_Departments::isDisabledStaff()) {
            if (Tools::strtolower($configure) == 'ets_livechat' || in_array($controller, array('AdminLiveChatDashboard', 'AdminLiveChatTickets', 'AdminLiveChatHistory', 'AdminLiveChatSettings', 'AdminLiveChatHelp', 'AdminLiveChatCronJob'))) {
                Tools::redirectAdmin($this->context->link->getAdminLink('AdminDashboard'));
            } else
                $this->context->controller->addCSS($this->_path . 'views/css/disable.css', 'all');
        }
        $this->context->controller->addCSS($this->_path . 'views/css/admin_all.css', 'all');
        $this->context->controller->addJS($this->_path.'views/js/admin_all.js');
        if (version_compare(_PS_VERSION_, '1.7.6.0', '>=') && version_compare(_PS_VERSION_, '1.7.7.0', '<')) {
            $this->context->controller->addJS(_PS_JS_DIR_ . 'jquery/jquery-' . _PS_JQUERY_VERSION_ . '.min.js');
        } else {
            $this->context->controller->addJquery();
        }
        $display = false;
        if (Tools::strtolower($configure) == 'ets_livechat' || in_array($controller, array('AdminLiveChatDashboard', 'AdminLiveChatTickets', 'AdminLiveChatHistory', 'AdminLiveChatSettings', 'AdminLiveChatHelp', 'AdminLiveChatCronJob'))) {
            $this->context->controller->addCSS($this->_path . 'views/css/livechat.admin.css', 'all');
            $this->context->controller->addCSS($this->_path . 'views/css/livechat.admin.footer.css', 'all');
            $display = true;
        }

        if (Tools::strtolower($configure) == 'ets_livechat' || in_array($controller, array('AdminLiveChatDashboard', 'AdminLiveChatTickets', 'AdminLiveChatHistory', 'AdminLiveChatSettings', 'AdminLiveChatHelp', 'AdminLiveChatCronJob'))) {
            if (version_compare(_PS_VERSION_, '1.6', '<'))
                $this->context->controller->addCSS($this->_path . 'views/css/livechat.admin15.css', 'all');
            $display = true;
            if($controller=='AdminLiveChatTickets')
            {
                $this->context->controller->addJqueryPlugin('autocomplete');
                $this->context->smarty->assign(
                    array(
                        'ets_lc_link_search_product' => $this->context->link->getAdminLink('AdminLiveChatTickets',true).'&submitSearchProduct=1',
                    )
                );
                $this->context->controller->addJS($this->_path . 'views/js/search_product.js');
                $this->context->controller->addCSS($this->_path . 'views/css/search_product.css', 'all');
            }
        }
        if (!Configuration::get('ETS_DISPLAY_DASHBOARD_ONLY') || $controller == 'AdminDashboard') {
            $this->context->controller->addCSS($this->_path . 'views/css/livechat.admin.footer.css', 'all');
            if (version_compare(_PS_VERSION_, '1.6', '<'))
                $this->context->controller->addCSS($this->_path . 'views/css/livechat.admin15.footer.css', 'all');
            if (version_compare(_PS_VERSION_, '1.7', '<'))
                $this->context->controller->addCSS($this->_path . 'views/css/livechat.admin16.footer.css', 'all');
            if (trim($controller) !== 'AdminProducts' && $controller!='AdminModulesPositions') {
                $this->context->controller->addJqueryUI(array('ui.draggable'));
                $this->context->controller->addJqueryPlugin('autocomplete');
            }
            $display = true;
        } elseif (in_array($controller, array('AdminLiveChatDashboard', 'AdminLiveChatTickets', 'AdminLiveChatHistory', 'AdminLiveChatSettings', 'AdminLiveChatHelp'))) {
            $this->context->controller->addJqueryPlugin('autocomplete');
        }
        if ($display)
            return $this->display(__FILE__, 'admin_header.tpl');
    }
    public function getSfContainer()
    {
        if(!class_exists('\PrestaShop\PrestaShop\Adapter\SymfonyContainer'))
        {
            $kernel = null;
            try{
                $kernel = new AppKernel('prod', false);
                $kernel->boot();
                return $kernel->getContainer();
            }
            catch (Exception $ex){
                return null;
            }
        }
        $sfContainer = call_user_func(array('\PrestaShop\PrestaShop\Adapter\SymfonyContainer', 'getInstance'));
        return $sfContainer;
    }
    public function assignTwigVar($params)
    {
        /** @var \Twig\Environment $tw */
        if(!class_exists('Ets_livechat_twig'))
            require_once(dirname(__FILE__).'/classes/Ets_livechat_twig.php');
        if($sfContainer = $this->getSfContainer())
        {
            try {
                $tw = $sfContainer->get('twig');
                $firstKey = array_keys($params)[0];
                if(!array_key_exists($firstKey, $tw->getGlobals()))
                    $tw->addExtension(new Ets_livechat_twig($params));
            } catch (\Twig\Error\RuntimeError $e) {

            }
        }
    }
    public function displayChatBoxCustomer($refresh = false)
    {
        $id_message = (int)Tools::getValue('id_message');
        $latestID = (int)Tools::getValue('latestID');
        $conversation = LC_Conversation::getCustomerConversation();
        $message_writing = (int)Tools::getValue('message_writing');
        $message_seen = (int)Tools::getValue('message_seen');
        $message_delivered = (int)Tools::getValue('message_delivered');
        if ($conversation) {
            if ($message_delivered)
                $conversation->date_message_delivered_customer = date('Y-m-d H:i:s');
            if ($message_seen) {
                $conversation->date_message_seen_customer = date('Y-m-d H:i:s');
            }
            if ($message_writing) {
                $conversation->date_message_writing_customer = date('Y-m-d H:i:s');
                $conversation->customer_writing = 1;
            }

            $conversation->latest_online = date('Y-m-d H:i:s');
            $conversation->update();
        }
        $isEmployeeSeen = $conversation ? LC_Conversation::isEmployeeSeen($conversation->id) : 0;
        $isEmployeeDelivered = $conversation ? LC_Conversation::isEmployeeDelivered($conversation->id) : 0;
        $isEmployeeWriting = $conversation ? LC_Conversation::isEmployeeWriting($conversation->id) : 0;
        $isEmployeeSent = $conversation ? LC_Conversation::isEmployeeSent($conversation->id) : 0;
        $isAdminOnline = LC_Conversation::isAdminOnline();
        $lastMessageOfEmployee = LC_Conversation::getLastMessageOfEmployee($conversation ? $conversation->id : 0);
        $company = LC_Departments::getCompanyInfo($conversation ? $conversation->id_employee : 0, Configuration::get('ETS_LC_DISPLAY_COMPANY_INFO'));
        $isRequestAjax = $conversation ? $conversation->isJquestAjax() : 0;
        $this->context->cookie->lc_siteloaded = 1;
        $this->context->cookie->write();
        if ($conversation && $conversation->employee_message_edited) {
            $employee_message_edited = LC_Message::getMessageByListID($conversation->employee_message_edited);
        } else
            $employee_message_edited = '';
        if (Configuration::get('ETS_LC_DISPLAY_COMPANY_INFO') == 'general' && Configuration::get('ETS_LC_COMPANY_NAME')) {
            $employee_name = Configuration::get('ETS_LC_COMPANY_NAME');
        } else
            $employee_name = $lastMessageOfEmployee ? $lastMessageOfEmployee['employee_name'] : Configuration::get('ETS_LC_COMPANY_NAME');
        $departments = LC_Departments::getAllDepartments();
        $change_department = $conversation ? $conversation->checkChangeDepartment():true;
        if ($refresh && $conversation && $conversation->id) {
            $assign = array(
                'isAdminBusy' => LC_Conversation::isAdminBusy(),
                'wait_support' => $conversation && $conversation->checkWaitSupport() ? LC_Conversation::getTimeWait() : false,
                'end_chat' => $conversation->end_chat,
                'end_chat_admin' => $conversation->end_chat >= 1 ? true : false,
                'isRequestAjax' => $isRequestAjax,
                'employee_accept' => $conversation->id_employee && ($employee = new Employee($conversation->id_employee)) ? (Configuration::get('ETS_LC_DISPLAY_COMPANY_INFO') == 'general' ? Configuration::get('ETS_LC_COMPANY_NAME') : $employee->firstname . ' ' . $employee->lastname) . $this->l(' accepted chat') : false,
                'isCustomerLoggedIn' => LC_Conversation::isCustomerLoggedIn(),
                'isAdminOnline' => $isAdminOnline,
                'departments' => $departments,
                'change_department' => $change_department,
                'isEmployeeSeen' => $isEmployeeSeen && in_array('seen', explode(',', Configuration::get('ETS_LC_DISPLAY_MESSAGE_STATUSES'))),
                'isEmployeeDelivered' => $isEmployeeDelivered && in_array('delevered', explode(',', Configuration::get('ETS_LC_DISPLAY_MESSAGE_STATUSES'))),
                'isEmployeeWriting' => $isEmployeeWriting && in_array('writing', explode(',', Configuration::get('ETS_LC_DISPLAY_MESSAGE_STATUSES'))),
                'isEmployeeSent' => $isEmployeeSent && in_array('sent', explode(',', Configuration::get('ETS_LC_DISPLAY_MESSAGE_STATUSES'))),
                'customer' => isset($this->context->customer->id) && $this->context->customer->id ? array(
                    'name' => trim(Tools::ucfirst($this->context->customer->firstname) . ' ' . Tools::ucfirst($this->context->customer->lastname)),
                ) : ($conversation ?
                    array(
                        'name' => $conversation->customer_name,
                    ) : false
                ),
                'upload_file' => $conversation->checkFileNumberUpload(),
                'employee_name' => $employee_name,
                'refresh' => $refresh,
                'company' => $company,
                'id_conversation' => $conversation ? $conversation->id : 0,
                'playsound_enable' => $conversation ? $conversation->enable_sound : 1,
                'lastMessageIsEmployee' => LC_Conversation::lastMessageIsEmployee($conversation ? $conversation->id : 0),
                'count_message_not_seen' => LC_Conversation::getMessagesCustomerNotSeen($conversation ? $conversation->id : 0),
                'messages' => $conversation && !$conversation->end_chat && ($messages = $conversation->getMessages((int)$latestID, (int)Configuration::get('ETS_LC_MSG_COUNT'))) ? array_reverse($messages) : false,
                'captcha' => LC_Conversation::needCaptcha() ? $this->context->link->getModuleLink($this->name, 'captcha', array('rand' => Tools::substr(sha1(mt_rand()), 17, 6))) : false,
                'captchaUrl' => LC_Conversation::needCaptcha() ? $this->context->link->getModuleLink($this->name, 'captcha', array('init' => 'ok')) : '',
                'employee_message_deleted' => $conversation ? $conversation->employee_message_deleted : '',
                'employee_message_edited' => $employee_message_edited,
                'conversation_rate' => $conversation->rating,
                'message_edited' => $id_message ? LC_Message::getMessage($id_message) : '',
            );
            if ($conversation) {
                $conversation->employee_message_deleted = '';
                $conversation->employee_message_edited = '';
                $conversation->update();
            }
            die(json_encode($assign));
        }
        $assign = array(
            'isRTL' => isset($this->context->language->is_rtl) && $this->context->language->is_rtl,
            'conversation' => $conversation,
            'wait_support' => $conversation && $conversation->checkWaitSupport() ? LC_Conversation::getTimeWait() : false,
            'isAdminBusy' => LC_Conversation::isAdminBusy(),
            'config' => $this->getConfigs(),
            'end_chat' => $conversation && $conversation->end_chat,
            'end_chat_admin' => $conversation && $conversation->end_chat == 1 ? true : false,
            'isRequestAjax' => $isRequestAjax,
            'isCustomerLoggedIn' => LC_Conversation::isCustomerLoggedIn(),
            'isAdminOnline' => $isAdminOnline,
            'departments' => $departments,
            'change_department' => $change_department,
            'isEmployeeSeen' => $isEmployeeSeen && in_array('seen', explode(',', Configuration::get('ETS_LC_DISPLAY_MESSAGE_STATUSES'))),
            'isEmployeeDelivered' => $isEmployeeDelivered && in_array('delevered', explode(',', Configuration::get('ETS_LC_DISPLAY_MESSAGE_STATUSES'))),
            'isEmployeeWriting' => $isEmployeeWriting && in_array('writing', explode(',', Configuration::get('ETS_LC_DISPLAY_MESSAGE_STATUSES'))),
            'isEmployeeSent' => $isEmployeeSent && in_array('sent', explode(',', Configuration::get('ETS_LC_DISPLAY_MESSAGE_STATUSES'))),
            'customer' => isset($this->context->customer->id) && $this->context->customer->id ? array(
                'name' => trim(Tools::ucfirst($this->context->customer->firstname) . ' ' . Tools::ucfirst($this->context->customer->lastname)),
                'email' => $this->context->customer->email,
                'phone' => ($addresses = $this->context->customer->getAddresses($this->context->language->id)) ? ($addresses[0]['phone'] ? $addresses[0]['phone'] : ($addresses[0]['phone_mobile'] ? $addresses[0]['phone_mobile'] : ($conversation && $conversation->customer_phone ? $conversation->customer_phone : ''))) : ($conversation && $conversation->customer_phone ? $conversation->customer_phone : ''),
                'phoneRegistered' => $addresses && ($addresses[0]['phone'] || $addresses[0]['phone_mobile']),
            ) : ($conversation ?
                array(
                    'name' => $conversation->customer_name,
                    'email' => $conversation->customer_email,
                    'phone' => $conversation->customer_phone,
                    'phoneRegistered' => $conversation->customer_phone,
                ) : false
            ),
            'employee_name' => $employee_name,
            'employee_info' => LC_Departments::getCompanyInfo($lastMessageOfEmployee && isset($lastMessageOfEmployee['id_employee']) && $lastMessageOfEmployee['id_employee'] ? $lastMessageOfEmployee['id_employee'] : ($conversation ? $conversation->id_employee : 0), 'staff'),
            'refresh' => $refresh,
            'company' => $company,
            'upload_file' => $conversation ? $conversation->checkFileNumberUpload():true,
            'lc_chatbox_top' => isset($this->context->cookie->lc_chatbox_top) && $this->context->cookie->lc_chatbox_top !== '' ? $this->context->cookie->lc_chatbox_top : false,
            'lc_chatbox_left' => isset($this->context->cookie->lc_chatbox_left) ? $this->context->cookie->lc_chatbox_left : false,
            'id_conversation' => $conversation ? $conversation->id : 0,
            'has_conversation' => 1,
            'playsound_enable' => $conversation ? $conversation->enable_sound : 1,
            'lastMessageIsEmployee' => LC_Conversation::lastMessageIsEmployee($conversation ? $conversation->id : 0),
            'lastMessageOfEmployee' => $lastMessageOfEmployee,
            'count_message_not_seen' => LC_Conversation::getMessagesCustomerNotSeen($conversation ? $conversation->id : 0),
            'ajaxUrl' => $this->context->link->getModuleLink($this->name, 'ajax'),
            'chatBoxStatus' => isset($this->context->cookie->ets_lc_chatbox_status) && $this->context->cookie->ets_lc_chatbox_status ? $this->context->cookie->ets_lc_chatbox_status : '',
            'messages' => $conversation && !$conversation->end_chat && ($messages = $conversation->getMessages((int)$latestID, (int)Configuration::get('ETS_LC_MSG_COUNT'))) ? array_reverse($messages) : false,
            'captcha' => LC_Conversation::needCaptcha() ? $this->context->link->getModuleLink($this->name, 'captcha', array('rand' => Tools::substr(sha1(mt_rand()), 17, 6))) : false,
            'captchaUrl' => $this->context->link->getModuleLink($this->name, 'captcha', array('init' => 'ok')),
            'emotions' => $this->emotions,
            'employee_message_deleted' => $conversation ? $conversation->employee_message_deleted : '',
            'employee_message_edited' => $employee_message_edited,
            'message_edited' => $id_message ? LC_Message::getMessage($id_message) : '',
            'livechatDir' => $this->_path,
            'contact_link' => $this->getLinkContact(),
            'product_current' => $this->getProductCurrent($conversation),
            'display_bubble_imge' => Configuration::get('ETS_CLOSE_CHAT_BOX_TYPE') == 'image' && Configuration::get('ETS_LC_BUBBLE_IMAGE') ? $this->context->link->getMediaLink(_PS_ETS_LIVE_CHAT_IMG_ . Configuration::get('ETS_LC_BUBBLE_IMAGE')) : '',
        );
        $this->smarty->assign($assign);
        return $this->display(__FILE__, 'chatbox-customer.tpl');
    }

    public function getLinkContact()
    {
        if (Configuration::get('ETS_LC_LINK_SUPPORT_TYPE') == 'contact-form')
            return $this->context->link->getPageLink('contact');
        elseif (Configuration::get('ETS_LC_LINK_SUPPORT_TYPE') == 'ticket-form' && $id_form = Configuration::get('ETS_LC_LINK_SUPPORT_FORM'))
            return $this->getFormLink($id_form);
        elseif (Configuration::get('ETS_LC_LINK_SUPPORT_TYPE') == 'custom-link' && Configuration::get('ETS_LC_SUPPORT_LINK', $this->context->language->id))
            return Configuration::get('ETS_LC_SUPPORT_LINK', $this->context->language->id);
        return '';

    }
    public function checkWaitingAcceptance($conversation)
    {
        if ((($conversation->id_employee_wait && Configuration::get('ETS_LC_STAFF_ACCEPT') && $conversation->id_employee == $this->context->employee->id) || ($conversation->id_departments_wait && !LC_Departments::checkDepartmentsExitsEmployee($conversation->id_departments_wait))) && !$conversation->end_chat) {
            if ($conversation->id_employee_wait != -1) {
                $employee = new Employee($conversation->id_employee_wait);
                return $employee->firstname . ' ' . $employee->lastname;
            } else {
                if ($conversation->id_departments_wait != -1) {
                    $department = new LC_Departments($conversation->id_departments_wait);
                    return $department->name;
                } else
                    return $this->l('All department');
            }

        }
    }

    public function displayChatBoxEmployee($id_conversation, $refresh = false, $list = false)
    {
        $id_message = (int)Tools::getValue('id_message');
        if (!LC_Conversation::checkExistConversation($id_conversation))
            return '';
        if ($this->all_shop && $this->shops) {
            foreach ($this->shops as $shop) {
                LC_Conversation::updateAdminOnline($shop['id_shop']);
            }
        }
        LC_Conversation::updateAdminOnline();
        $conversation = new LC_Conversation($id_conversation);
        $isCustomerOnline = LC_Conversation::isCustomerOnline($id_conversation);
        if ($conversation->id_customer) {
            $customer = new Customer($conversation->id_customer);
            $customer_name = $customer->firstname . ' ' . $customer->lastname;
            $customer_phone = ($addresses = $customer->getAddresses($this->context->language->id)) ? ($addresses[0]['phone'] ? $addresses[0]['phone'] : ($addresses[0]['phone_mobile'] ? $addresses[0]['phone_mobile'] : ($conversation && $conversation->customer_phone ? $conversation->customer_phone : ''))) : ($conversation && $conversation->customer_phone ? $conversation->customer_phone : '');
            $customer_email = $customer->email;
        } else {
            $customer_name = $conversation->customer_name;
            $customer_phone = $conversation->customer_phone;
            $customer_email = $conversation->customer_email;
        }
        $customer_avata = LC_Departments::getAvatarCustomer($conversation->id_customer);
        $isCustomerSeen = $conversation ? LC_Conversation::isCustomerSeen($conversation->id) : 0;
        $isCustomerDelivered = $conversation ? LC_Conversation::isCustomerDelivered($conversation->id) : 0;
        $isCustomerWriting = $conversation ? LC_Conversation::isCustomerWriting($conversation->id) : 0;
        $isCustomerSent = $conversation ? LC_Conversation::isCustomerSent($conversation->id) : 0;
        $isRequestAjax = 0;
        $end_chat = '';
        if ($conversation->end_chat) {
            if ($conversation->end_chat == $this->context->employee->id)
                $end_chat = $this->l('You has ended this chat');
            elseif ($conversation->end_chat > 0) {
                $employee = new Employee($conversation->end_chat);
                $end_chat = $employee->firstname . ' ' . $employee->lastname . ' ' . $this->l('has ended this chat');
            } else
                $end_chat = ($customer_name ? $customer_name : 'Chat ID #' . $conversation->id) . ' ' . $this->l('has left chat');
        } elseif ($conversation->checkAutoEndChat()) {
            $end_chat = $this->l('Chat has ended.') . ' ' . ($customer_name ? $customer_name : 'Chat ID #' . $conversation->id) . ' ' . $this->l('has left chat');
        } else {
            $isRequestAjax = $conversation ? $conversation->isJquestAjax() : 0;
        }
        if ($conversation->message_edited) {
            $message_edited = LC_Message::getMessageByListID($conversation->message_edited);
        } else
            $message_edited = '';
        if ($conversation->id_ticket)
            $conversation->ticket = new LC_Ticket($conversation->id_ticket);
        $conversation_hided = json_decode($this->context->cookie->converation_hided, true);
        $employees = LC_Departments::getEmployeeDepartments();
        if ($employees) {
            foreach ($employees as &$employee) {
                $employe_departments = LC_Departments::getDepartmentByEmployee($employee['id_employee']);
                $employee['departments'] = $employe_departments;
            }
        }
        if (!$refresh) {
            $link_customer = $this->getAdminBaseLink().Configuration::get('ETS_DIRECTORY_ADMIN_URL'). '/' .Dispatcher::getInstance()->createUrl('AdminCustomers') . '&token='.Tools::getAdminTokenLite('AdminCustomers').'&id_customer=' . (int)$conversation->id_customer . '&viewcustomer';
            $post_fields = Tools::getValue('fields');
            if ($idCustomer = (int)Tools::getValue('id_customer')) {
                $customerObj = new Customer($idCustomer);
            } else
                $customerObj = false;
            $post_fields['id_customer'] = $idCustomer;
            $post_fields['search_customer'] = Tools::getValue('search_customer_ticket', $customerObj ? $customerObj->firstname . ' ' . $customerObj->lastname : '');
            $post_fields['id_customer_ticket'] = (int)Tools::getValue('id_customer_ticket', $customerObj ? $customerObj->id : '');
            $post_fields['order_ref'] = ($orderRef = Tools::getValue('order_ref')) && Validate::isReference($orderRef) ? $orderRef :'';
            $post_fields['id_product_ref'] = (int)Tools::getValue('id_product',(int)Tools::getValue('id_product_ref'));
            $assign = array(
                'error' => $this->errors ? $this->displayError($this->errors) : false,
                'isRTL' => isset($this->context->language->is_rtl) && $this->context->language->is_rtl,
                'conversation' => $conversation,
                'config' => $this->getConfigs(),
                'end_chat' => $end_chat,
                'history_chat' => Configuration::get('ETS_LIVECHAT_ADMIN_OLD') ? $this->_displayHistoryChatCustomer($conversation->chatref) : '',
                'customer_avata' => $customer_avata,
                'waiting_acceptance' => $this->checkWaitingAcceptance($conversation),
                'has_changed' => LC_Conversation::checkHasChanged($conversation),
                'wait_accept' => LC_Conversation::checkWaitAccept($conversation),
                'accept_employee' => new Employee($conversation->id_employee),
                'employees' => $employees,
                'departments' => LC_Departments::getAllDepartments(),
                'ETS_LIVECHAT_ADMIN_DE' => Configuration::get('ETS_LIVECHAT_ADMIN_DE') || $this->context->employee->id_profile == 1,
                'isRequestAjax' => $isRequestAjax,
                'isCustomerOnline' => $isCustomerOnline,
                'pre_made_messages' => LC_MadeMessage::getMadeMessages(),
                'isCustomerSeen' => $isCustomerSeen && in_array('seen', explode(',', Configuration::get('ETS_LC_DISPLAY_MESSAGE_STATUSES'))),
                'isCustomerDelivered' => $isCustomerDelivered && in_array('delevered', explode(',', Configuration::get('ETS_LC_DISPLAY_MESSAGE_STATUSES'))),
                'isCustomerWriting' => $isCustomerWriting && in_array('writing', explode(',', Configuration::get('ETS_LC_DISPLAY_MESSAGE_STATUSES'))),
                'isCustomerSent' => $isCustomerSent && in_array('sent', explode(',', Configuration::get('ETS_LC_DISPLAY_MESSAGE_STATUSES'))),
                'lastMessageIsEmployee' => LC_Conversation::lastMessageIsEmployee($conversation ? $conversation->id : 0),
                'customer_name' => $customer_name,
                'date_accept' => $conversation && $conversation->id ? LC_Base::convertDate($conversation->date_accept) : false,
                'chatbox_closed' => $conversation && $conversation_hided ? in_array($conversation->id, $conversation_hided) : 0,
                'customer_email' => $customer_email,
                'customer_phone' => $customer_phone,
                'id_conversation' => $conversation ? $conversation->id : 0,
                'customer_rated' => $conversation ? $conversation->rating : 0,
                'count_message_not_seen' => (int)LC_Conversation::getMessagesEmployeeNotSeen($conversation->id),
                'refresh' => $refresh,
                'link_ticket' =>$this->getAdminBaseLink().Configuration::get('ETS_DIRECTORY_ADMIN_URL'). '/' .Dispatcher::getInstance()->createUrl('AdminLiveChatTickets') . '&token='.Tools::getAdminTokenLite('AdminLiveChatTickets'),
                'link_customer' => $conversation->id_customer ? $link_customer : '',
                'ajaxUrl' => $this->_path . 'ets_livechat_ajax.php?token=' . Tools::getAdminTokenLite('AdminModules'),
                'chatBoxStatus' => isset($this->context->cookie->ets_lc_chatbox_status) && $this->context->cookie->ets_lc_chatbox_status ? $this->context->cookie->ets_lc_chatbox_status : false,
                'messages' => $conversation && ($messages = $conversation->getMessages(0, (int)Configuration::get('ETS_LC_MSG_COUNT'))) ? array_reverse($messages) : array(),
                'message_deleted' => $conversation ? $conversation->message_deleted : '',
                'message_edited' => $message_edited,
                'employee_message_edited' => $id_message ? LC_Message::getMessage($id_message) : '',
                'emotions' => $this->emotions,
                'livechatDir' => $this->_path,
                'link' => $this->context->link,
                'form_ticket' => ($form = new LC_Ticket_Form(1, $this->context->language->id)) && $form->id ? $form->renderHtmlForm($conversation->id,false,$post_fields) : '',
            );
            $this->smarty->assign($assign);
            return $this->display(__FILE__, 'chatbox-employee.tpl');
        } else {
            $assign = array(
                'error' => $this->errors ? $this->displayError($this->errors) : false,
                'end_chat' => $end_chat,
                'waiting_acceptance' => $this->checkWaitingAcceptance($conversation),
                'has_changed' => LC_Conversation::checkHasChanged($conversation),
                'wait_accept' => LC_Conversation::checkWaitAccept($conversation),
                'isRequestAjax' => $isRequestAjax,
                'isCustomerOnline' => $isCustomerOnline,
                'customer_avata' => $customer_avata,
                'pre_made_messages' => LC_MadeMessage::getMadeMessages(),
                'isCustomerSeen' => $isCustomerSeen && in_array('seen', explode(',', Configuration::get('ETS_LC_DISPLAY_MESSAGE_STATUSES'))),
                'isCustomerDelivered' => $isCustomerDelivered && in_array('delevered', explode(',', Configuration::get('ETS_LC_DISPLAY_MESSAGE_STATUSES'))),
                'isCustomerWriting' => $isCustomerWriting && in_array('writing', explode(',', Configuration::get('ETS_LC_DISPLAY_MESSAGE_STATUSES'))),
                'isCustomerSent' => $isCustomerSent && in_array('sent', explode(',', Configuration::get('ETS_LC_DISPLAY_MESSAGE_STATUSES'))),
                'lastMessageIsEmployee' => LC_Conversation::lastMessageIsEmployee($conversation ? $conversation->id : 0),
                'customer_name' => $customer_name,
                'chatbox_closed' => $conversation && $conversation_hided ? in_array($conversation->id, $conversation_hided) : 0,
                'current_url' => $conversation->current_url,
                'customer_email' => $customer_email,
                'customer_phone' => $customer_phone,
                'id_conversation' => $conversation ? $conversation->id : 0,
                'customer_rated' => $conversation ? $conversation->rating : 0,
                'count_message_not_seen' => (int)LC_Conversation::getMessagesEmployeeNotSeen($conversation->id),
                'refresh' => $refresh,
                'chatBoxStatus' => isset($this->context->cookie->ets_lc_chatbox_status) && $this->context->cookie->ets_lc_chatbox_status ? $this->context->cookie->ets_lc_chatbox_status : false,
                'messages' => $conversation && ($messages = $conversation->getMessages(0, (int)Configuration::get('ETS_LC_MSG_COUNT'))) ? array_reverse($messages) : false,
                'message_deleted' => $conversation ? $conversation->message_deleted : '',
                'message_edited' => $message_edited,
                'employee_message_edited' => $id_message ? LC_Message::getMessage($id_message) : '',
            );
            if ($conversation) {
                $conversation->message_deleted = '';
                $conversation->message_edited = '';
                $conversation->update();
            }
            if (!$list) {
                die(json_encode($assign));
            } else
                return $assign;
        }

    }

    public function hookActionAuthentication($params)
    {
        if (isset($params['customer']) && isset($params['customer']->id) && ($id_customer = $params['customer']->id) && (int)$this->context->cookie->lc_id_conversation && ($conversation = new LC_Conversation((int)$this->context->cookie->lc_id_conversation)) && Validate::isLoadedObject($conversation) && !$conversation->id_customer && $conversation->id) {
            LC_Conversation::updateConversationAfterRegister($conversation,$id_customer);
        }
    }

    public function hookActionCustomerLogoutAfter()
    {
        $this->context->cookie->lc_id_conversation = 0;
        $this->context->cookie->write();
    }
    public static function validCaptcha($captcha = false)
    {
        if (!LC_Conversation::needCaptcha())
            return true;
		
		$captcha = Tools::getValue('cf-turnstile-response');
		
		$secretKey = '0x4AAAAAABgPEljp619NJx6mc6MMsjrkZEo';
		$ip = $_SERVER['REMOTE_ADDR'];
		
		$url_path = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';
		$data = array(
			'secret' => $secretKey, 
			'response' => $captcha, 
			'remoteip' => $ip
		);
		
		$options = array(
			'http' => array(
				'method' => 'POST',
				'content' => http_build_query($data)
			)
		);
		
		$stream = stream_context_create($options);
		
		$result = file_get_contents($url_path, false, $stream);
		
		$response = json_decode($result, true);
		
		return $result['success'];
        /*if (!$captcha)
            $captcha = Tools::strtolower(trim((string) Tools::getValue('captcha', '')));
        if (Context::getContext()->cookie->ets_lc_captcha_code && $captcha != Tools::strtolower(Context::getContext()->cookie->ets_lc_captcha_code))
            return false;
        return true;*/
    }
    public static function isAdminOnlineNoForce()
    {
        $timeout = (int)Configuration::get('ETS_LC_TIME_OUT_BACK_END') * 3 / 1000 + (int)Configuration::get('ETS_LC_TIME_OUT') * 3 / 1000;
        $conversation = LC_Conversation::getCustomerConversation();
        $last_online = LC_Conversation::getDateLastAdminOnline();
        $statusEmployee = ($conversation && $conversation->id_employee && !$conversation->end_chat && $status = LC_Departments::getStatusEmployee($conversation->id_employee)) ? $status : '';
        if ($last_online && (strtotime(date('Y-m-d H:i:s')) < strtotime($last_online) + $timeout))
            if ($statusEmployee == 'offline')
                return 0;
            else
                return $statusEmployee;
        else
            return 0;
    }
    public function getRequestContainer()
    {
        if($sfContainer = $this->getSfContainer())
        {
            return $sfContainer->get('request_stack')->getCurrentRequest();
        }
        return null;
    }
    public function hookDisplayBackOfficeFooter()
    {
        if ($this->context->cookie->converation_opened) {
            $declines = $this->getDeclineConversation();
            $conversation_opened = json_decode($this->context->cookie->converation_opened, true);
            if ($conversation_opened) {
                foreach ($conversation_opened as $key => $id_conversation) {
                    $conversation = new LC_Conversation($id_conversation);
                    if (!LC_Conversation::checkConversationEmployee($conversation, $this->context->employee->id) || in_array($id_conversation, $declines))
                        unset($conversation_opened[$key]);
                }
                if ($conversation_opened)
                    $this->context->cookie->converation_opened = json_encode($conversation_opened);
                else
                    $this->context->cookie->converation_opened = '';
                $this->context->cookie->write();
            }
        }
        $made_messages = LC_MadeMessage::getMadeMessages();
        $controller = Tools::getValue('controller');
        if($controller=='AdminCustomers')
        {
            if($this->is17)
            {
                $request = $this->getRequestContainer();
                $idCustomer = null;
                if($request){
                    $idCustomer = $request->get('customerId');
                }
                else{
                    $idCustomer = (int)Tools::getValue('id_customer');
                }
            }
            else
                $idCustomer = (int)Tools::getValue('id_customer');
            if($idCustomer)
            {
                $link_create_ticket = $this->context->link->getAdminLink('AdminLiveChatTickets').'&addticket&id_form=2&id_customer='.(int)$idCustomer;
            }
        }
        if($controller=='AdminOrders' || $controller=='AdminCustomers' || $controller=='AdminPhMdLicense')
        {
            $forms  = $this->getListFormTickets();
        }
        $this->smarty->assign(array(
            'ETS_LC_MODULE_URL' => $this->_path,
            'ETS_CONVERSATION_DISPLAY_ADMIN' => (int)Configuration::get('ETS_CONVERSATION_DISPLAY_ADMIN'),
            'ETS_CONVERSATION_LIST_TYPE' => Configuration::get('ETS_CONVERSATION_LIST_TYPE'),
            'ETS_CLOSE_CHAT_BOX_BACKEND_TYPE' => Configuration::get('ETS_CLOSE_CHAT_BOX_BACKEND_TYPE'),
            'assigns' => $this->getConfigs(true),
            'link_create_ticket' => isset($link_create_ticket) ? $link_create_ticket : false,
            'level_request' => LC_Conversation::getLevelRequestAdmin(),
            'converation_opened' => $this->context->cookie->converation_opened,
            'ets_ajax_message_url' => $this->_path . 'ets_livechat_ajax.php?token=' . Tools::getAdminTokenLite('AdminModules') . '&getMessage=1',
            'isRTL' => isset($this->context->language->is_rtl) && $this->context->language->is_rtl,
            'enable_livechat' => LC_Conversation::checkEnableLivechat($controller),
            'admin_controller' => in_array($controller, array('AdminLiveChatDashboard', 'AdminLiveChatTickets', 'AdminLiveChatHistory', 'AdminLiveChatSettings', 'AdminLiveChatHelp')),
            'controller_current' => Validate::isControllerName($controller) ? $controller : '',
            'ETS_LC_MODULE_URL_AJAX' => $this->_path . 'ets_livechat_ajax.php?token=' . Tools::getAdminTokenLite('AdminModules'),
            'id_profile' => $this->context->employee->id_profile,
            'made_messages' => $made_messages,
            'forms' => isset($forms) && Count($forms)>1 ? $forms : false,
            'link_customer_search' => $this->getBaseLink() . '/modules/' . $this->name . '/ets_livechat_search_customer.php?token=' . md5($this->id),
            'ETS_LC_MODULE_URL_ADMIM' => $this->context->link->getAdminLink('AdminModules') . '&tabsetting=1&configure=ets_livechat',
        ));
        return $this->display(__FILE__, 'admin_footer.tpl');
    }

    public function displayListCustomerChat()
    {
        if ($this->all_shop && $this->shops) {
            foreach ($this->shops as $shop) {
                LC_Conversation::updateAdminOnline($shop['id_shop']);
            }
        }
        LC_Conversation::updateAdminOnline();
        $refresh = (int)Tools::getValue('refresh');
        $auto = (int)Tools::getValue('auto');
        if (!$auto) {
            $this->updateLastAction();
        }
        $customer_all = (int)Tools::getValue('customer_all');
        $customer_archive = (int)Tools::getValue('customer_archive');
        $customer_search = Tools::getValue('customer_search');
        $count_conversation = (int)Tools::getValue('count_conversation',20);
        $conversations = LC_Conversation::getConversations($customer_all, $customer_archive, $customer_search && Validate::isCleanHtml($customer_search) ? $customer_search : '','',$count_conversation);
        $status = LC_Departments::getStatusEmployee($this->context->employee->id) ? LC_Departments::getStatusEmployee($this->context->employee->id) : 'online';
        $this->context->smarty->assign(
            array(
                'isRTL' => isset($this->context->language->is_rtl) && $this->context->language->is_rtl,
                'config' => $this->getConfigs(),
                'employee_info' => LC_Departments::getCompanyInfo($this->context->employee->id),
                'totalMessageNoSeen' => LC_Conversation::getTotalMessageNoSeen(),
                'conversations' => $conversations,
                'lc_chatbox_top' => isset($this->context->cookie->lc_chatbox_top) && $this->context->cookie->lc_chatbox_top !== '' ? $this->context->cookie->lc_chatbox_top : false,
                'lc_chatbox_left' => isset($this->context->cookie->lc_chatbox_left) ? $this->context->cookie->lc_chatbox_left : false,
                'loaded' => Count($conversations) < $count_conversation,
                'id_profile' => $this->context->employee->id_profile,
                'status_employee' => Configuration::get('ETS_LC_FORCE_ONLINE') ? 'foce_online' : $status,
                'ETS_CONVERSATION_DISPLAY_ADMIN' => (int)Configuration::get('ETS_CONVERSATION_DISPLAY_ADMIN'),
                'livechatDir' => $this->_path,
                'link' => $this->context->link,
                'modulUrl' => 'index.php?controller=AdminModules&token=' . Tools::getAdminTokenLite('AdminModules') . '&tabsetting=1&configure=' . $this->name,
                'refresh' => (int)$refresh,
            )
        );
        return $this->display(__FILE__, 'list_customer_chat.tpl');
    }

    public function getTemplateEmail($messages)
    {
        $this->context->smarty->assign(
            array(
                'messages' => $messages,
            )
        );
        return $this->display(__FILE__, 'email_messages.tpl');
    }

    public function renderExtraForm()
    {
        $auto_replies = LC_AutoReplyMessage::getAllAutoMessages();
        $pre_made_messages = LC_MadeMessage::getMadeMessages();
        $employees = LC_Departments::getEmployeeStaffs(true);
        $departments = LC_Departments::getAllDepartments(false,false);
        if ($departments) {
            foreach ($departments as &$department) {
                $department['agents'] = LC_Departments::getEmployees($department['id_departments']);
            }
        }
        $message_week = LC_Message::getAttachmentsMessage(true, ' AND datetime_added <"' . pSQL(date('Y-m-d', strtotime('-1 WEEK'))) . '"');
        $note_week = LC_Note::getAttachmentsNote(true, ' AND date_add <"' . pSQL(date('Y-m-d', strtotime('-1 WEEK'))) . '"');
        $attachment_week = LC_Ticket::getAttachmentsTickets(true, ' AND t.date_add <"' . pSQL(date('Y-m-d', strtotime('-1 WEEK'))) . '"');
        $messages_1_month_ago = LC_Message::getAttachmentsMessage(true, ' AND datetime_added <"' . pSQL(date('Y-m-d', strtotime('-1 MONTH'))) . '"');
        $note_1_month_ago = LC_Note::getAttachmentsNote(true, ' AND date_add <"' . pSQL(date('Y-m-d', strtotime('-1 MONTH'))) . '"');
        $attachment_1_month_ago = LC_Ticket::getAttachmentsTickets(true, ' AND t.date_add <"' . pSQL(date('Y-m-d', strtotime('-1 MONTH'))) . '"');
        $messages_6_month_ago = LC_Message::getAttachmentsMessage(true, ' AND datetime_added <"' . pSQL(date('Y-m-d', strtotime('-6 MONTH'))) . '"');
        $notes_6_month_ago = LC_Note::getAttachmentsNote(true, ' AND date_add <"' . pSQL(date('Y-m-d', strtotime('-6 MONTH'))) . '"');
        $attachments_6_month_ago = LC_Ticket::getAttachmentsTickets(true, ' AND t.date_add <"' . pSQL(date('Y-m-d', strtotime('-6 MONTH'))) . '"');
        $messages_year_ago = LC_Message::getAttachmentsMessage(true, ' AND datetime_added <"' . pSQL(date('Y-m-d', strtotime('-1 YEAR'))) . '"');
        $notes_year_ago = LC_Note::getAttachmentsNote(true, ' AND date_add <"' . pSQL(date('Y-m-d', strtotime('-1 YEAR'))) . '"');
        $attachments_year_ago = LC_Ticket::getAttachmentsTickets(true, ' AND t.date_add <"' . pSQL(date('Y-m-d', strtotime('-1 YEAR'))) . '"');
        $messages_everything = LC_Message::getAttachmentsMessage(true);
        $notes_everything = LC_Note::getAttachmentsNote(true);
        $attachments_everything = LC_Ticket::getAttachmentsTickets(true);
        $this->context->smarty->assign(
            array(
                'auto_replies' => $auto_replies,
                'pre_made_messages' => $pre_made_messages,
                'version' => 'v' . $this->version,
                'employees' => $employees,
                'languages' => Language::getLanguages(false),
                'defaultFormLanguage' => (int)Configuration::get('PS_LANG_DEFAULT'),
                'attachments_1_week' => $message_week['count'] + $note_week['count'] + $attachment_week['count'],
                'attachments_1_week_size' => Tools::ps_round($message_week['size'] + $note_week['size'] + $attachment_week['size'], 2),
                'attachments_1_month_ago' => $messages_1_month_ago['count'] + $note_1_month_ago['count'] + $attachment_1_month_ago['count'],
                'attachments_1_month_ago_size' => Tools::ps_round($messages_1_month_ago['size'] + $note_1_month_ago['size'] + $attachment_1_month_ago['size'], 2),
                'attachments_6_month_ago' => $messages_6_month_ago['count'] + $notes_6_month_ago['count'] + $attachments_6_month_ago['count'],
                'attachments_6_month_ago_size' => Tools::ps_round($messages_6_month_ago['size'] + $notes_6_month_ago['size'] + $attachments_6_month_ago['size'], 2),
                'attachments_year_ago' => $messages_year_ago['count'] + $notes_year_ago['count'] + $attachments_year_ago['count'],
                'attachments_year_ago_size' => Tools::ps_round($messages_year_ago['size'] + $notes_year_ago['size'] + $attachments_year_ago['size'], 2),
                'attachments_everything' => $messages_everything['count'] + $notes_everything['count'] + $attachments_everything['count'],
                'attachments_everything_size' => Tools::ps_round($messages_everything['size'] + $notes_everything['size'] + $attachments_everything['size'], 2),
                'departments' => $departments,
                'link_new_employee' => $this->context->link->getAdminLink('AdminEmployees',true,array('route' => 'admin_employees_create')),
            )
        );
        if (version_compare(_PS_VERSION_, '1.6', '<'))
            return $this->display(__FILE__, 'extra_form_15.tpl');
        else
            return $this->display(__FILE__, 'extra_form.tpl');
    }

    public function _postAutoReply()
    {
        $message_order = Tools::getValue('message_order');
        $auto_content = array();
        $id_lang_defult = (int)Configuration::get('PS_LANG_DEFAULT');
        $auto_content_default = trim((string) Tools::getValue('auto_content_'.$id_lang_defult, ''));
        $id_auto_msg = (int)Tools::getValue('id_auto_msg');
        $max_lang_content = (int)Configuration::get('ETS_LC_MSG_LENGTH');
        if(trim($auto_content_default) == '')
            $this->errors[] = $this->l('Auto content is required');
        elseif(Tools::strlen(trim($auto_content_default)) > $max_lang_content)
            $this->errors[] = sprintf($this->l('The message content cannot be longer than %d characters'),$max_lang_content);
        elseif (!Validate::isCleanHtml($auto_content_default)) {
            $this->errors[] = $this->l('Auto content is invalid');
        }
        $languages = Language::getLanguages(false);
        foreach($languages as $language)
        {
            if($language['id_lang']!=$id_lang_defult)
            {
                $auto_content_lang = Tools::getValue('auto_content_'.$language['id_lang']);
                if ($auto_content_lang && (!Validate::isCleanHtml($auto_content_lang) || Tools::strlen(trim($auto_content_lang)) > $max_lang_content)) {
                    $this->errors[] = sprintf($this->l('Auto content in %s is invalid'),$language['iso_code']);
                }
                $auto_content[$language['id_lang']] = $auto_content_lang ? : $auto_content_default;
            }
            else
                $auto_content[$id_lang_defult] = $auto_content_default;
        }
        if (!$id_auto_msg) {
            if($message_order=='')
                $this->errors[] = $this->l('Message order is required');
            elseif ((int)$message_order==0 || !Validate::isUnsignedInt($message_order))
                $this->errors[] = $this->l('Message order is invalid');
            if (LC_AutoReplyMessage::getMessageByOrder($message_order)) {
                $this->errors[] = $this->l('Message order existed');
            }
        } else {
            if (($id =LC_AutoReplyMessage::getMessageByOrder($message_order)) && $id!= $id_auto_msg) {
                $this->errors[] = $this->l('Message order existed');
            }
        }
        if (!$this->errors) {
            if ($id_auto_msg) {
                $autoMessage = new LC_AutoReplyMessage($id_auto_msg);
                $autoMessage->auto_content = $auto_content;
                $autoMessage->message_order = (int)$message_order;
                $autoMessage->update();
                $success = $this->l('Updated auto message successfully');
            } else {
                $autoMessage = new LC_AutoReplyMessage();
                $autoMessage->message_order = (int)$message_order;
                $autoMessage->auto_content = $auto_content;
                $autoMessage->add();
                $success = $this->l('Added auto message successfully');
            }
            die(
                json_encode(
                    array(
                        'error' => false,
                        'id_auto_msg' => $autoMessage->id,
                        'message_order' => (int)$autoMessage->message_order,
                        'auto_content' => trim($autoMessage->auto_content[$this->context->language->id]),
                        'success' => $success
                    )
                )
            );
        } else {
            die(
                json_encode(
                    array(
                        'error' => $this->errors ? $this->displayError($this->errors) : false,
                    )
                )
            );
        }
    }

    public function _getFromDepartments($id_departments)
    {
        $this->context->smarty->assign(
            array(
                'departments' => LC_Departments::getDepartMentsByID($id_departments),
                'employees' => LC_Departments::getEmployeeStaffs(),
                'link_new_employee' => $this->context->link->getAdminLink('AdminEmployees',true,array('route' => 'admin_employees_create')),
            )
        );
        die(
            json_encode(
                array(
                    'departments_from' => $this->display(__FILE__, 'department_form.tpl'),
                )
            )
        );
    }

    public function _postDepartments()
    {
        $departments_agents = trim((string) Tools::getValue('departments_agents', ''));
        if (!$departments_agents)
            $this->errors[] = $this->l('Staffs are required');
        elseif (!Validate::isCleanHtml($departments_agents))
            $this->errors[] = $this->l('Staffs are not valid');
        else
            $departments_agents = explode(',', $departments_agents);
        $departments_name_all = (int)Tools::getValue('departments_name_all');
        $departments_status = (int)Tools::getValue('departments_status');
        $departments_name = Tools::getValue('departments_name');
        $departments_description = Tools::getValue('departments_description');
        if ($departments_description && !Validate::isCleanHtml($departments_description))
            $this->errors[] = $this->l('Department description is not valid');
        if (!$departments_name)
            $this->errors[] = $this->l('Department name is required');
        elseif (!Validate::isCleanHtml($departments_name))
            $this->errors[] = $this->l('Department name is not valid');

        if ($this->errors) {
            die(
                json_encode(
                    array(
                        'error' => $this->errors ? $this->displayError($this->errors) : false,
                    )
                )
            );
        } else {
            if ($id_departments = (int)Tools::getValue('id_departments')) {
                $departments = new LC_Departments($id_departments);
            } else {
                $departments = new LC_Departments();
                $max_position = LC_Departments::getMaxSortOrder();
                $departments->sort_order = $max_position + 1;
            }
            $departments->all_employees = (int)$departments_name_all;
            $departments->status = $departments_status;
            $departments->name = $departments_name;
            $departments->description = $departments_description;
            if (!$departments->id) {
                if (!$departments->add()) {
                    die(
                        json_encode(
                            array(
                                'error' => $this->displayError($this->l('Add item failed.')),
                            )
                        )
                    );
                } else
                    $success = $this->l('Added department successfully');
            } elseif (!$departments->update()) {
                die(
                    json_encode(
                        array(
                            'error' => $this->displayError($this->l('Update item failed.')),
                        )
                    )
                );
            } else
                $success = $this->l('Updated department successfully');
            $this->context->smarty->assign(
                array(
                    'departments' => $departments,
                    'employees' => $departments->addEmployees($departments_agents),
                )
            );
            die(
                json_encode(
                    array(
                        'error' => false,
                        'id_departments' => $departments->id,
                        'success' => $success,
                        'department' => $this->display(__FILE__, 'department.tpl'),
                    )
                )
            );
        }
    }

    public function _postPreMadeMessage()
    {
        $short_code_message = trim((string) Tools::getValue('short_code_message', ''));
        $message_content = trim((string) Tools::getValue('message_content', ''));
        $id_pre_made_message = (int)Tools::getValue('id_pre_made_message');
        if (!Validate::isCleanHtml($short_code_message) || Tools::strlen($short_code_message) <= 0 || Tools::strlen($short_code_message) > 200) {
            $this->errors[] = $this->l('Short code is invalid.');
        }
        if (!Validate::isCleanHtml($message_content) || Tools::strlen($message_content) <= 0 || Tools::strlen($message_content) > (int)Configuration::get('ETS_LC_MSG_LENGTH')) {
            $this->errors[] = $this->l('Message content is invalid.');
        }
        if (!$this->errors) {
            if ($id_pre_made_message) {
                $madeMessage = new LC_MadeMessage(($id_pre_made_message));
                $madeMessage->short_code=  $short_code_message;
                $madeMessage->message_content = $message_content;
                $madeMessage->update();
                $success = $this->l('Updated pre-made message successfully');
            } else {
                $madeMessage = new LC_MadeMessage();
                $madeMessage->short_code = $short_code_message;
                $madeMessage->message_content = $message_content;
                $madeMessage->add();
                $success = $this->l('Added pre-made message successfully');
            }
            die(
                json_encode(
                    array(
                        'error' => false,
                        'pre_made_message' => array(
                            'short_code' =>$madeMessage->short_code,
                            'message_content' => $madeMessage->message_content,
                            'id_pre_made_message' => $madeMessage->id,
                        ),
                        'success' => $success,
                    )
                )
            );
        } else {
            die(
                json_encode(
                    array(
                        'error' => $this->errors ? $this->displayError($this->errors) : false,
                    )
                )
            );
        }
    }

    static public function getBrowserInfo($browser_name)
    {
        switch ($browser_name) {
            case 'Firefox':
                $class = 'firefox';
                break;
            case 'Chrome':
                $class = 'chrome';
                break;
            case 'Opera':
                $class = 'opera';
                break;
            case 'Safari':
                $class = 'safari';
                break;
            case 'Internet explorer':
                $class = 'internet_explorer';
                break;
            default:
                $class = '';
        }
        return $class;
    }

    public function displayListPreMadeMessages()
    {
        $pre_made_messages = LC_MadeMessage::getMadeMessages();
        $this->context->smarty->assign(
            array(
                'pre_made_messages' => $pre_made_messages,
                'link_pre_made_messages' => 'index.php?controller=AdminModules&token=' . Tools::getAdminTokenLite('AdminModules') . '&tabsetting=1&configure=' . $this->name . '&current_tab_acitve=pre_made_message',
            )
        );
        return $this->display(__FILE__, 'pre_made_messages.tpl');
    }
    public function checkNewMessage()
    {
        $lastID_Conversation = (int)Tools::getValue('lastID_Conversation');
        $lastID_message = (int)Tools::getValue('lastID_message');
        $customer_search = Tools::getValue('customer_search');
        $customer_archive = (int)Tools::getValue('customer_archive');
        if (($customer_search && Validate::isCleanHtml($customer_search)) || $customer_archive)
            return 0;
        $customer_all = (int)Tools::getValue('customer_all');
        return LC_Message::checkNewMessage($lastID_Conversation,$lastID_message,$customer_all);
    }
    public function hookDisplayBlockOnline()
    {
        $html = '';
        $languages = Language::getLanguages(false);
        if ($languages) {
            foreach ($languages as $language) {
                $this->assignConfig($language['id_lang']);
                $html .= $this->display(__FILE__, 'onlineformchat.tpl');
            }
        }
        return $html;
    }

    public function hookDisplayBlockBusy()
    {
        if (version_compare(_PS_VERSION_, '1.6', '<'))
            return '';
        $html = '';
        $languages = Language::getLanguages(false);
        if ($languages) {
            foreach ($languages as $language) {
                $this->assignConfig($language['id_lang']);
                $html .= $this->display(__FILE__, 'busyformchat.tpl');
            }
        }
        return $html;
    }

    public function hookDisplayBlockInvisible()
    {
        if (version_compare(_PS_VERSION_, '1.6', '<'))
            return '';
        $html = '';
        $languages = Language::getLanguages(false);
        if ($languages) {
            foreach ($languages as $language) {
                $this->assignConfig($language['id_lang']);
                $html .= $this->display(__FILE__, 'invisiblefromchat.tpl');
            }
        }
        return $html;
    }

    public function hookDisplayBlockOffline()
    {
        if (version_compare(_PS_VERSION_, '1.6', '<'))
            return '';
        $html = '';
        $languages = Language::getLanguages(false);
        if ($languages) {
            foreach ($languages as $language) {
                $this->assignConfig($language['id_lang']);
                $html .= $this->display(__FILE__, 'offlineformchat.tpl');
            }
        }
        return $html;
    }

    public function assignConfig($id_lang)
    {
        $langauge = new Language($id_lang);
        $this->context->smarty->assign(
            array(
                'config' => $this->getConfigs(false, $id_lang),
                'isRTL' => isset($langauge->is_rtl) && $langauge->is_rtl,
                'language' => $langauge,
                'defaultFormLanguage' => Configuration::get('PS_LANG_DEFAULT'),
                'employee' => $this->context->employee,
                'employee_info' => LC_Departments::getCompanyInfo($this->context->employee->id, 'staff'),
                'needCaptcha' => LC_Conversation::needCaptcha(),
                'captcha' => $this->context->link->getModuleLink($this->name, 'captcha', array('rand' => Tools::substr(sha1(mt_rand()), 17, 6))),
                'livechatDir' => $this->_path,
                'link' => $this->context->link,
                'isAdminOnline' => 'online',
                'departments' => LC_Departments::getAllDepartments(),
                'emotions' => $this->emotions,
            )
        );
    }

    public function displayError($errors)
    {
        $this->context->smarty->assign(
            array(
                'errors' => $errors
            )
        );
        return $this->display(__FILE__, 'error.tpl');
    }

    public function displayCucstomerInfo($id_conversation)
    {
        $conversation = new LC_Conversation($id_conversation);
        if ($conversation->id_customer) {
            $customer = new Customer($conversation->id_customer);
            $this->context->smarty->assign(
                array(
                    'name' => trim(Tools::ucfirst($customer->firstname) . ' ' . Tools::ucfirst($customer->lastname)),
                    'email' => $customer->email,
                    'phone' => ($addresses = $customer->getAddresses($this->context->language->id)) ? ($addresses[0]['phone'] ? $addresses[0]['phone'] : ($addresses[0]['phone_mobile'] ? $addresses[0]['phone_mobile'] : ($conversation && $conversation->customer_phone ? $conversation->customer_phone : ''))) : ($conversation && $conversation->customer_phone ? $conversation->customer_phone : ''),
                )
            );
        } else {
            $this->context->smarty->assign(
                array(
                    'name' => $conversation->customer_name,
                    'email' => $conversation->customer_email,
                    'phone' => $conversation->customer_phone,
                )
            );
        }
        return $this->display(__FILE__, 'customer_info.tpl');
    }
    public function getLinkCurrentByUrl()
    {
        if ($_SERVER['SERVER_PORT'] != "80") {
            $url = $_SERVER['SERVER_NAME'] . ':' . $_SERVER['SERVER_PORT'] . $_SERVER['REQUEST_URI'];
        } else
            $url = $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'];
        if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) {
            $url = 'https://' . $url;
        } else
            $url = 'http://' . $url;
        if (strpos($url, '#') !== FALSE) {
            $url = Tools::substr($url, 0, strpos($url, '#'));
        }
        return $url;
    }

    public function getLoginConfigs()
    {
        return array(
            'callback' => $this->context->link->getModuleLink($this->name, 'callback', array(), true),
            'providers' => array(
                'Google' => array(
                    'enabled' => Configuration::get('ETS_LIVECHAT_ENABLE_GOOGLE') ? true : false,
                    'keys' => array(
                        'id' => Configuration::get('ETS_LIVECHAT_GOOGLE_APP_ID'),
                        'secret' => Configuration::get('ETS_LIVECHAT_GOOGLE_APP_SECRET'),
                        'key' => '',
                    ),
                ),
                'Facebook' => array(
                    'enabled' => Configuration::get('ETS_LIVECHAT_ENABLE_FACEBOOK') ? true : false,
                    'keys' => array(
                        'id' => Configuration::get('ETS_LIVECHAT_FACEBOOK_APP_ID'),
                        'secret' => Configuration::get('ETS_LIVECHAT_FACEBOOK_APP_SECRET'),
                        'key' => '',
                    ),
                ),
                'Twitter' => array(
                    'enabled' => Configuration::get('ETS_LIVECHAT_ENABLE_TWITTER') ? true : false,
                    'keys' => array(
                        'id' => Configuration::get('ETS_LIVECHAT_TWITTER_APP_ID'),
                        'secret' => Configuration::get('ETS_LIVECHAT_TWITTER_APP_SECRET'),
                        'key' => '',
                    ),
                ),
            )
        );
    }

    public function closePopup()
    {
        return $this->display(__FILE__, 'frontJs.tpl');
    }

    public function updateContext(Customer $customer)
    {
        if ($this->is17)
            return false;
        $this->context->cookie->id_compare = isset($this->context->cookie->id_compare) ? $this->context->cookie->id_compare : CompareProduct::getIdCompareByIdCustomer($customer->id);
        $this->context->cookie->id_customer = (int)($customer->id);
        $this->context->cookie->customer_lastname = $customer->lastname;
        $this->context->cookie->customer_firstname = $customer->firstname;
        $this->context->cookie->logged = 1;
        $customer->logged = 1;
        $this->context->cookie->is_guest = $customer->isGuest();
        $this->context->cookie->passwd = $customer->passwd;
        $this->context->cookie->email = $customer->email;
        $this->context->customer = $customer;
        if (Configuration::get('PS_CART_FOLLOWING') && (empty($this->context->cookie->id_cart) || Cart::getNbProducts($this->context->cookie->id_cart) == 0) && $id_cart = (int)Cart::lastNoneOrderedCart($this->context->customer->id)) {
            $this->context->cart = new Cart($id_cart);
        } else {
            $this->context->cart->id_carrier = 0;
            $this->context->cart->setDeliveryOption(null);
            $this->context->cart->id_address_delivery = (int)Address::getFirstCustomerAddressId((int)($customer->id));
            $this->context->cart->id_address_invoice = (int)Address::getFirstCustomerAddressId((int)($customer->id));
        }
        $this->context->cart->id_customer = (int)$customer->id;
        $this->context->cart->secure_key = $customer->secure_key;
        $this->context->cart->save();
        $this->context->cookie->id_cart = (int)$this->context->cart->id;
        $this->context->cookie->write();
        $this->context->cart->autosetProductAddress();
        Hook::exec('actionAuthentication', array('customer' => $this->context->customer));
        CartRule::autoRemoveFromCart($this->context);
        CartRule::autoAddToCart($this->context);
    }

    public function createUser($profile, $provider)
    {
        if (!$profile) {
            die(json_encode(array('errors' => $this->l('Connect API error! Please check your account again.'))));
        } elseif ($provider) {
            $profile = $this->prepareDataToSave($profile);
            $customer = new Customer();
            $customer->id_shop = (int)$this->context->shop->id;
            $customer->lastname = $profile->lastName;
            $customer->firstname = $profile->firstName;
            $customer->email = $profile->email;
            $passwdGen = Tools::passwdGen(8);
            $customer->passwd = md5(_COOKIE_KEY_ . $passwdGen);
            if ($customer->save()) {
                LC_Base::addSocialAddCustomer($profile->identifier,$customer->email);
                $customer->updateGroup(array((int)Configuration::get('PS_CUSTOMER_GROUP')));
                if ($this->is17) {
                    $this->context->updateCustomer($customer);
                    Hook::exec('actionAuthentication', array('customer' => $this->context->customer));
                    CartRule::autoRemoveFromCart($this->context);
                    CartRule::autoAddToCart($this->context);
                } else
                    $this->updateContext($customer);
                LC_Base::trackingLoginSocial($customer, $provider);

            } else
                die(json_encode(array('errors' => $this->l('Create account error. Please check your account profile.'))));
        }
    }
    public function prepareDataToSave($profile)
    {
        if ($profile->firstName && $profile->lastName && Validate::isName($profile->firstName) && Validate::isName($profile->lastName)) {
            return $profile;
        } elseif ($profile->firstName) {
            $profile->lastName = $profile->firstName;
        } elseif ($profile->lastName) {
            $profile->firstName = $profile->lastName;
        } elseif ($profile->displayName) {
            $profile->displayName = str_replace('+', '', $profile->displayName);
            $parts = explode(' ', trim($profile->displayName));
            $nameParts = array();
            foreach ($parts as $part) {
                if (trim($part) == '') continue;
                $nameParts[] = $part;
            }
            if (count($nameParts) == 1) {
                $profile->firstName = $profile->lastName = $nameParts[0];
            } elseif (count($nameParts) > 1) {
                $profile->firstName = $nameParts[0];
                unset($nameParts[0]);
                $profile->lastName = implode(' ', $nameParts);
            }
        }
        if (!$profile->firstName || !\Validate::isName($profile->firstName))
            $profile->firstName = 'Unknown';
        if (!$profile->lastName || !\Validate::isName($profile->lastName))
            $profile->lastName = 'Unknown';

        return $profile;
    }
    public function hookDisplayStaffs()
    {
        $employees = LC_Departments::getEmployeeStaffs(false);
        if ($employees) {
            foreach ($employees as &$employee) {
                if ($employee['avata'])
                    $employee['avata'] = _PS_ETS_LIVE_CHAT_IMG_ . $employee['avata'];
                else
                    $employee['avata'] = _PS_ETS_LIVE_CHAT_IMG_ . 'adminavatar.jpg';
            }
        }
        $this->context->smarty->assign(
            array(
                'employees' => $employees,
            )
        );
        return $this->display(__FILE__, 'staffs.tpl');
    }

    public function getFormStaff($id_employee)
    {

        $this->context->smarty->assign(
            array(
                'employee' => LC_Departments::getInfoStaffByIdEmployee($id_employee),
                'id_profile' => $this->context->employee->id_profile,
            )
        );
        return $this->display(__FILE__, 'staff.tpl');
    }

    public function _postStaff($id_employee)
    {
        $errors = array();
        $employee = new Employee($id_employee);
        $staff_status = (int)Tools::getValue('staff_status');
        $nick_name = Tools::getValue('nick_name');
        if (!Validate::isLoadedObject($employee))
            $errors[] = $this->l('Employee is not valid');
        elseif ($employee->id_profile == 1 && $staff_status == 0)
            $errors[] = $this->l('You do not have permission to disable this staff');
        $signature = Tools::getValue('signature');
        if ($signature && !Validate::isCleanHtml($signature))
            $errors[] = $this->l('Signature is not valid');
        if ($nick_name && !Validate::isCleanHtml($nick_name))
            $errors[] = $this->l('Nick name is not valid');
        $imageName ='';
        if (isset($_FILES['avata_staff']['tmp_name']) && isset($_FILES['avata_staff']['name']) && $_FILES['avata_staff']['name']) {
            $_FILES['avata_staff']['name'] = str_replace(array(' ','(',')','!','@','#','+'), '_', $_FILES['avata_staff']['name']);
            if (!Validate::isFileName($_FILES['avata_staff']['name']))
                $errors[] = $this->l('Avatar file name is not valid');
            else {
                $type = Tools::strtolower(Tools::substr(strrchr($_FILES['avata_staff']['name'], '.'), 1));
                $imageName = $_FILES['avata_staff']['name'];
                $fileName = _PS_ETS_LIVE_CHAT_IMG_DIR_ . $imageName;
                if (file_exists($fileName)) {
                    $time = md5(time());
                    for ($i = 0; $i < 6; $i++) {
                        $index = rand(0, Tools::strlen($time) - 1);
                        $imageName = $time[$index] . $imageName;
                    }
                    $fileName = _PS_ETS_LIVE_CHAT_IMG_DIR_ . $imageName;
                }
                if (file_exists($fileName)) {
                    $errors[] = $this->l('Avatar already existed. Try to rename the file then reupload');
                } else {
                    $imagesize = @getimagesize($_FILES['avata_staff']['tmp_name']);
                    if (!$errors && isset($_FILES['avata_staff']) &&
                        !empty($_FILES['avata_staff']['tmp_name']) &&
                        !empty($imagesize) &&
                        in_array($type, array('jpg', 'gif', 'jpeg', 'png','webp'))
                    ){
                        if($type=='webp')
                        {
                            if (!move_uploaded_file($_FILES['avata_staff']['tmp_name'], $fileName))
                                $errors[] = $this->l('Cannot upload the file');
                        }
                        else
                        {
                            $temp_name = tempnam(_PS_TMP_IMG_DIR_, 'PS');
                            if (!$temp_name || !move_uploaded_file($_FILES['avata_staff']['tmp_name'], $temp_name))
                                $errors[] = $this->l('Cannot upload the file');
                            elseif (!ImageManager::resize($temp_name, $fileName, 120, 120, $type))
                                $errors[] = $this->displayError($this->l('An error occurred during the image upload process.'));
                            if ($temp_name && file_exists($temp_name))
                                @unlink($temp_name);
                        }

                    } else {
                        $errors[] = $this->displayError($this->l('Avatar is not valid.'));
                    }

                }
            }

        } else
            $imageName = '';
        if ($errors) {
            die(
                json_encode(
                    array(
                        'error' => $this->displayError($errors),
                    )
                )
            );
        } else {
            if(LC_Departments::addUpdateStaff($id_employee,$nick_name,$signature,$imageName,$staff_status))
            {
                die(
                    json_encode(
                        array(
                            'success' => $this->l('Updated successfully'),
                            'image' => $imageName ? _PS_ETS_LIVE_CHAT_IMG_ . $imageName : '',
                            'nick_name' => $nick_name ?: '--',
                            'status' => $staff_status,
                            'signature' => Tools::nl2br(strip_tags(trim($signature))) ?: '--',
                            'id_employee' => $id_employee,
                        )
                    )
                );
            }

        }

    }
    public function _accpectConversation($id_conversation, $ajax = true)
    {
        $conversation = new LC_Conversation($id_conversation);
        $error = '';
        if (($conversation->id_employee == 0 || ($conversation->id_departments_wait == 0 && $conversation->id_employee)) && $conversation->id_departments)
            if ($this->context->employee->id_profile != 1 && LC_Departments::checkDepartments() && $conversation->id_departments && !LC_Departments::checkDepartmentsExitsEmployee($conversation->id_departments))
                $error = $this->l('You do not have access permission');
        if ($conversation->id_employee && $conversation->id_departments_wait > 0)
            if ($this->context->employee->id_profile != 1 && !LC_Departments::checkDepartmentsExitsEmployee($conversation->id_departments_wait))
                $error = $this->l('You do not have access permission');
        if ($conversation->id_employee && $conversation->id_employee_wait > 0 && $conversation->id_employee_wait != $this->context->employee->id)
            $error = $this->l('You do not have access permission');
        if ($conversation->id_employee && !$conversation->id_employee_wait && !$conversation->id_departments_wait)
            $error = $this->l('There was an employee who accepted the conversation');
        if (LC_Conversation::checkDeclineEmployee($this->context->employee->id,$conversation->id))
            $error = $this->l('You declined');
        if (!$error) {
            $conversation->id_employee = $this->context->employee->id;
            $conversation->id_employee_wait = 0;
            if ($conversation->id_departments_wait) {
                $conversation->id_departments = $conversation->id_departments_wait == -1 ? 0 : $conversation->id_departments_wait;
                $conversation->id_departments_wait = 0;
            }
            $conversation->date_accept = date('Y-m-d H:i:s');
            $conversation->update();
            if ($ajax) {
                die(
                    json_encode(
                        array(
                            'error' => false,
                        )
                    )
                );
            }
        }
        if ($ajax) {
            die(
                json_encode(
                    array(
                        'error' => $error,
                    )
                )
            );
        }
        return false;

    }

    public function _declineConversation($id_conversation)
    {
        $conversation = new LC_Conversation($id_conversation);
        if ($conversation->id_employee == $this->context->employee->id) {
            die(json_encode(
                array(
                    'error' => $this->l('You do not have access permission'),
                )
            ));
        }
        if (!LC_Conversation::checkDeclineEmployee($this->context->employee->id,$conversation->id)) {
            LC_Conversation::declineEmployee($this->context->employee->id,$conversation->id);
        }
        die(
            json_encode(
                array(
                    'error' => false,
                    'id_profile' => $this->context->employee->id_profile,
                )
            )
        );
    }

    public function getDeclineConversation()
    {
        if (LC_Base::checkVesionModule()) {
            if (isset($this->context->employee) && $this->context->employee->id && $this->context->employee->id_profile != 1) {
                $array = array();
                $declines = LC_Departments::getStaffDecline($this->context->employee->id);
                if ($declines) {
                    foreach ($declines as $decline)
                        $array[] = $decline['id_conversation'];

                }
                return $array;
            }
        }
        return array();
    }

    public function duplicateConversation(&$conversation)
    {
        if (!($lc_conversation_end_chat = (int)Tools::getValue('lc_conversation_end_chat'))) {
            die(
                json_encode(
                    array(
                        'lc_conversation_end_chat' => $lc_conversation_end_chat ?: 1,
                    )
                )
            );
        }
        $conversation->end_chat = 0;
        $conversation->id_employee = 0;
        $conversation->id_employee_wait = 0;
        $conversation->id_departments_wait = 0;
        $conversation->id = 0;
        $conversation->replied = 0;
        $conversation->blocked = 0;
        $conversation->rating = 0;
        $conversation->id_ticket = 0;
        $conversation->captcha_enabled = 0;
        if (!$conversation->add())
            return false;
        else {
            $this->context->cookie->lc_id_conversation = $conversation->id;
            $this->context->cookie->write();
            return true;
        }
    }

    public function _cancelAcceptance($id_conversation)
    {
        $conversation = new LC_Conversation($id_conversation);
        if (($conversation->id_departments_wait || $conversation->id_employee_wait) && $conversation->id_employee == $this->context->employee->id) {
            $conversation->id_employee_wait = 0;
            $conversation->id_departments_wait = 0;
            $conversation->update();
            die(json_encode(
                array(
                    'error' => false,
                )
            ));
        } else {
            die(json_encode(
                array(
                    'error' => $this->l('You do not have access permission'),
                )
            ));
        }
    }
    public function hookCustomerAccount($params)
    {
        if (!$this->context->customer->isLogged())
            return '';
        $this->smarty->assign(
            array(
                'ETS_LC_CUSTOMER_OLD' => Configuration::get('ETS_LC_CUSTOMER_OLD'),
                'count_support' => LC_Ticket::getCountTicketNoReaded($this->context->customer->id),
                'ETS_LIVECHAT_ADMIN_TICKET' => Configuration::get('ETS_LIVECHAT_ADMIN_TICKET'),
                'link' => $this->context->link,
                'open_livechat' => LC_Conversation::checkAccess(),
            )
        );
        if ($this->is17)
            return $this->display(__FILE__, 'my-account.tpl');
        else
            return $this->display(__FILE__, 'my-account16.tpl');
    }

    public function hookDisplayMyAccountBlock($params)
    {
        return $this->hookCustomerAccount($params);
    }
    public static function getToken()
    {
        if(($token = Tools::getValue('token')) && Validate::isCleanHtml($token))
            return $token;
        return false;
    }
    public function getBreadCrumb()
    {
        $nodes = array();
        $controller = Tools::getValue('controller');
        $nodes[] = array(
            'title' => $this->l('Home'),
            'url' => $this->context->link->getPageLink('index', true),
        );
        if ($controller == 'info') {
            $nodes[] = array(
                'title' => $this->l('My account'),
                'url' => $this->context->link->getPageLink('my-account'),
            );
            $nodes[] = array(
                'title' => $this->l('Chat info'),
                'url' => $this->context->link->getModuleLink($this->name, 'info')
            );
        }
        if ($controller == 'form') {
            $nodes[] = array(
                'title' => $this->l('My account'),
                'url' => $this->context->link->getPageLink('my-account'),
            );
            $nodes[] = array(
                'title' => $this->l('Support ticket'),
                'url' => $this->context->link->getModuleLink($this->name, 'ticket')
            );
            $id_form = (int)Tools::getValue('id_form');
            $url_alias = Tools::getValue('url_alias');
            if (!$id_form && $url_alias && Validate::isLinkRewrite($url_alias)) {
                $id_form = LC_Ticket_form::getIdFormByFriendlyUrl($url_alias);
            }
            if ($id_form) {
                $form = new LC_Ticket_form($id_form, $this->context->language->id);
                $nodes[] = array(
                    'title' => $form->title,
                    'url' => $this->getFormLink($id_form)
                );
            }
        }
        if ($controller == 'ticket') {
            $nodes[] = array(
                'title' => $this->l('My account'),
                'url' => $this->context->link->getPageLink('my-account'),
            );
            $nodes[] = array(
                'title' => $this->l('Support tickets'),
                'url' => $this->context->link->getModuleLink($this->name, 'ticket')
            );
            if (Tools::isSubmit('viewticket') && ($id_ticket = (int)Tools::getValue('id_ticket'))) {
                $nodes[] = array(
                    'title' => $this->l('Ticket #') . $id_ticket,
                    'url' => $this->context->link->getModuleLink($this->name, 'ticket', array('viewticket' => 1, 'id_ticket' => $id_ticket))
                );
            }
        }
        if ($controller == 'history') {
            $nodes[] = array(
                'title' => $this->l('My account'),
                'url' => $this->context->link->getPageLink('my-account'),
            );
            $nodes[] = array(
                'title' => $this->l('Chat history'),
                'url' => $this->context->link->getModuleLink($this->name, 'history'),
            );
            if (Tools::isSubmit('viewchat') && ($id_conversation = (int)Tools::getValue('id'))) {
                $nodes[] = array(
                    'title' => $this->l('Conversation #') . $id_conversation,
                    'url' => $this->context->link->getModuleLink($this->name, 'history', array('viewchat' => 1, 'id' => $id_conversation)),
                );
            }
        }
        if ($this->is17)
            return array('links' => $nodes, 'count' => count($nodes));
        return $this->displayBreadcrumb($nodes);
    }

    public function displayBreadcrumb($nodes)
    {
        $this->smarty->assign(array('nodes' => $nodes));
        return $this->display(__FILE__, 'nodes.tpl');
    }

    public function displaySuccessMessage($msg, $title = false, $link = false)
    {
        $this->smarty->assign(array(
            'msg' => $msg,
            'title' => $title,
            'link_ets' => $link
        ));
        if ($msg)
            return $this->displayConfirmation($this->display(__FILE__, 'success_message.tpl'));
    }

    public function renderFormCustomerInformation()
    {
        $customer_info = LC_Departments::getCustomerInfo($this->context->customer->id);
        $this->smarty->assign(
            array(
                'customer' => $this->context->customer,
                'link' => $this->context->link,
                'link_delete_image' => $this->context->link->getModuleLink($this->name, 'info', array('deleteavatar' => 1)),
                'customer_info' => $customer_info,
                'customer_avata' => $customer_info && isset($customer_info['avata']) && $customer_info['avata'] && file_exists(_PS_ETS_LIVE_CHAT_IMG_DIR_ . $customer_info['avata']) ? _PS_ETS_LIVE_CHAT_IMG_ . $customer_info['avata'] : '',
                'avata_default' => $this->context->link->getMediaLink(_PS_ETS_LIVE_CHAT_IMG_ . 'customeravata.jpg'),
                'isManager' => LC_Ticket_process::getInstance()->isManagerTicket(),
            )
        );
        return $this->display(__FILE__, 'info.tpl');
    }

    public function _displayHistoryChatCustomer($chatref)
    {
        if ($chatref) {
            $totalRecords = LC_Conversation::getCountConversationByChatref($chatref);
            $page = (int)Tools::getValue('page');
            if ($page < 1)
                $page = 1;
            if(!class_exists('LC_paggination_class'))
                require_once(dirname(__FILE__) . '/classes/LC_paggination_class.php');
            $paggination = new LC_paggination_class();
            $paggination->total = $totalRecords;
            $paggination->url = isset($this->context->customer) && $this->context->customer->id ? $this->context->link->getModuleLink($this->name, 'history', array('page' => '_page_')) : 'gethistory&page=_page_';
            $paggination->limit = 20;
            $totalPages = ceil($totalRecords / $paggination->limit);
            if ($page > $totalPages)
                $page = $totalPages;
            $paggination->page = $page;
            $start = $paggination->limit * ($page - 1);
            if ($start < 0)
                $start = 0;
            $paggination->text = $this->l('Showing {start} to {end} of {total} ({pages} Pages)');
            $paggination->style_links = 'links';
            $paggination->style_results = 'results';
            $conversations = LC_Conversation::getConverSationByChatref($chatref,$start,$paggination->limit);
        } else
            $conversations = array();
        if ($conversations) {
            foreach ($conversations as $key=> &$conversation) {
                if (isset($this->context->customer) && $this->context->customer->id)
                    $conversation['link_view'] = $this->context->link->getModuleLink($this->name, 'history', array('viewchat' => 1, 'id' => $conversation['id_conversation']));
                else
                    $conversation['link_view'] = $this->context->link->getAdminLink('AdminModules') . '&tabsetting=1&configure=ets_livechat&viewchat&id=' . (int)$conversation['id_conversation'];
                $conversation['last_message'] = LC_Conversation::getLastMessage($conversation['id_conversation']);
                if ($conversation['last_message']) {
                    if (date('Y-m-d') == date('Y-m-d', strtotime($conversation['last_message']['datetime_added']))) {
                        $conversation['last_message']['datetime_added'] = date('h:i A', strtotime($conversation['last_message']['datetime_added']));
                    } else {
                        if (date('Y') == date('Y', strtotime($conversation['last_message']['datetime_added']))) {
                            $conversation['last_message']['datetime_added'] = date('d-m h:i A', strtotime($conversation['last_message']['datetime_added']));
                        } else
                            $conversation['last_message']['datetime_added'] = date('d-m-Y h:i A', strtotime($conversation['last_message']['datetime_added']));
                    }
                    if ($this->emotions) {
                        foreach ($this->emotions as $key => $emotion) {
                            $img = $this->displayText(
                                $this->displayText(
                                    '', 'img', '', array('src' => $this->context->link->getMediaLink($this->_path . 'views/img/emotions/' . $emotion['img']))), 'span', '', array('title' => $emotion['title']));
                            $conversation['last_message']['message'] = str_replace(array(Tools::strtolower($key), $key), array($img, $img), $conversation['last_message']['message']);
                        }
                    }
                } else
                    unset($conversations[$key]);

            }
        }
        $this->smarty->assign(
            array(
                'conversations' => $conversations,
                'paggination' => isset($paggination) ? $paggination->render() : '',
            )
        );
        return $this->display(__FILE__, 'history.tpl');
    }

    public function _displayConversationDetail($conversation)
    {
        if (!is_object($conversation))
            $conversation = new LC_Conversation($conversation);
        $this->smarty->assign(
            array(
                'messages' => ($messages = $conversation->getMessages(0, 0, 'ASC', 0)) ? array_reverse($messages) : false,
                'config' => $this->getConfigs(),
                'link_back' => isset($this->context->customer) && $this->context->customer->id ? $this->context->link->getModuleLink($this->name, 'history') : '',
            )
        );
        return $this->display(__FILE__, 'conversation_detail.tpl');
    }

    public function _getMoreCustomer()
    {
        $count_conversation = (int)Tools::getValue('count_conversation');
        $lastID_Conversation = (int)Tools::getValue('lastID_Conversation');
        $customer_all = (int)Tools::getValue('customer_all');
        $customer_archive = (int)Tools::getValue('customer_archive');
        $customer_search = Tools::getValue('customer_search');
        $count_conversation = (int)Tools::getValue('count_conversation',20);
        $conversation = new LC_Conversation($lastID_Conversation);
        $conversations = LC_Conversation::getConversations($customer_all, $customer_archive, $customer_search && Validate::isCleanHtml($customer_search) ? $customer_search : '', $conversation->date_message_last_customer,$count_conversation);
        $this->context->smarty->assign(
            array(
                'conversations' => $conversations,
                'refresh' => 1,
            )
        );
        die(
        json_encode(
            array(
                'list_more_customer' => $this->display(__FILE__, 'list_customer_chat.tpl'),
                'loaded' => Count($conversations) < $count_conversation,
            )
        )
        );
    }

    public function hookDisplaySystemTicket()
    {
        $forms = LC_Ticket_form::getForms(false,false,null,null,false);
        if ($forms) {
            foreach ($forms as &$form) {
                $form['link'] = $this->getFormLink($form['id_form']);
            }
        }
        $ETS_LC_URL_ALIAS = array();
        foreach (Language::getLanguages(false) as $language)
            $ETS_LC_URL_ALIAS[$language['id_lang']] = Configuration::get('ETS_LC_URL_ALIAS', $language['id_lang']);
        $this->smarty->assign(
            array(
                'forms' => $forms,
                'ETS_LIVECHAT_ADMIN_TICKET' => Configuration::get('ETS_LIVECHAT_ADMIN_TICKET'),
                'ETS_LC_URL_SUBFIX' => Configuration::get('ETS_LC_URL_SUBFIX'),
                'ETS_LC_URL_REMOVE_ID' => Configuration::get('ETS_LC_URL_REMOVE_ID'),
                'ETS_LC_NUMBER_TICKET_MESSAGES' => Configuration::get('ETS_LC_NUMBER_TICKET_MESSAGES') != '' ? (int)Configuration::get('ETS_LC_NUMBER_TICKET_MESSAGES') : '',
                'ETS_LC_NUMBER_TICKET_MANAGER' => trim(($manager = Configuration::get('ETS_LC_NUMBER_TICKET_MANAGER'))) !== '' ? $manager : '',
                'ETS_LC_ONLY_DISPLAY_TICKET_OPEN' => (int)Configuration::get('ETS_LC_ONLY_DISPLAY_TICKET_OPEN'),
                'ETS_LC_URL_ALIAS' => $ETS_LC_URL_ALIAS,
                'languages' => Language::getLanguages(false),
                'ETS_LC_DAY_AUTO_CLOSE_TICKET' => Configuration::get('ETS_LC_DAY_AUTO_CLOSE_TICKET'),
                'ETS_LC_DISPLAY_PRODUCTS_IN_LIST_TICKET' => Configuration::get('ETS_LC_DISPLAY_PRODUCTS_IN_LIST_TICKET'),
                'ETS_LC_DISPLAY_ORDER_IN_LIST_TICKET' => Configuration::get('ETS_LC_DISPLAY_ORDER_IN_LIST_TICKET'),
                'ETS_LC_DISPLAY_STAFF_IN_LIST_TICKET' => Configuration::get('ETS_LC_DISPLAY_STAFF_IN_LIST_TICKET'),
                'default_language' => new Language((int)Configuration::get('PS_LANG_DEFAULT')),
            )
        );
        return $this->display(__FILE__, 'system_ticket.tpl');
    }
    public function _displayFormTicket()
    {
        die(
        json_encode(
            array(
                'form_html' => $this->_renderFormticket(),
                'fields_list' => ($id_form = (int)Tools::getValue('id_form')) ? $this->_displayListFields($id_form) : '',
            )
        )
        );
    }

    public function _renderFormticket()
    {
        $id_form = (int)Tools::getValue('id_form');
        $fields_form = array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l('Ticket forms'),
                ),
                'input' => LC_Ticket_form::getInstance()->setConfigForm($id_form),
                'submit' => array(
                    'title' => $this->l('Save'),
                ),
                'buttons' => array(
                    array(
                        'type' => 'button',
                        'id' => 'form_ticket_form_cancel_btn',
                        'title' => $this->l('Cancel'),
                        'icon' => 'process-icon-cancel',
                    )
                ),
                'form' => array(
                    'id_form' => 'form_new_ticket_form',
                )
            ),
        );
        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->table = 'form_ticket';
        $lang = new Language((int)Configuration::get('PS_LANG_DEFAULT'));
        $languages = Language::getLanguages(false);
        foreach ($languages as &$l) {
            if ($l['id_lang'] == $lang->id)
                $l['is_default'] = true;
            else
                $l['is_default'] = false;
        }
        $helper->default_form_language = $lang->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') ? Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') : 0;
        $helper->module = $this;
        $helper->identifier = $this->identifier;
        $helper->submit_action = 'saveFormTicket';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false) . '&tabsetting=1&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name;
        $helper->token = $this->context->employee->id ? Tools::getAdminTokenLite('AdminModules') : false;
        $language = new Language((int)Configuration::get('PS_LANG_DEFAULT'));
        $helper->tpl_vars = array(
            'base_url' => $this->context->shop->getBaseURL(),
            'language' => array(
                'id_lang' => $language->id,
                'iso_code' => $language->iso_code
            ),
            'fields_value' => $this->getFieldsFormTicketValues(),
            'languages' => $languages,
            'id_language' => $this->context->language->id,
            'image_baseurl' => $this->_path . 'views/img/',
            'is_ps15' => version_compare(_PS_VERSION_, '1.6', '<'),
            'link' => $this->context->link,
        );
        $helper->override_folder = '/';
        return $helper->generateForm(array($fields_form));
    }

    public function _displayListFields($id_form)
    {
        if ($fields = LC_Ticket_form::getListFields($id_form)) {
            foreach ($fields as $key => &$field) {
                $field['html_form'] = $this->GetFormField($field['id_field'], $key + 1);
            }
        }
        $this->context->smarty->assign(
            array(
                'fields' => $fields,
            )
        );
        return $this->display(__FILE__, 'list_fields.tpl');
    }
    public static function file_get_contents($url, $use_include_path = false, $stream_context = null, $curl_timeout = 60)
    {
        if ($stream_context == null && preg_match('/^https?:\/\//', $url)) {
            $stream_context = stream_context_create(array(
                "http" => array(
                    "timeout" => $curl_timeout,
                    "max_redirects" => 101,
                    "header" => 'User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_14_6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/77.0.3865.90 Safari/537.36'
                ),
                "ssl" => array(
                    "allow_self_signed" => true,
                    "verify_peer" => false,
                    "verify_peer_name" => false,
                ),
            ));
        }
        if (function_exists('curl_init')) {
            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_RETURNTRANSFER => 1,
                CURLOPT_URL => html_entity_decode($url),
                CURLOPT_USERAGENT => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_14_6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/77.0.3865.90 Safari/537.36',
                CURLOPT_SSL_VERIFYHOST => false,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_TIMEOUT => $curl_timeout,
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_FOLLOWLOCATION => true,
            ));
            $content = curl_exec($curl);
            curl_close($curl);
            return $content;
        } elseif (in_array(ini_get('allow_url_fopen'), array('On', 'on', '1')) || !preg_match('/^https?:\/\//', $url)) {
            return Tools::file_get_contents($url, $use_include_path, $stream_context);
        } else {
            return false;
        }
    }

    public static function getBaseModLink()
    {
        $context = Context::getContext();
        return (Configuration::get('PS_SSL_ENABLED_EVERYWHERE') ? 'https://' : 'http://') . $context->shop->domain . $context->shop->getBaseURI();
    }

    public function getFieldsFormTicketValues()
    {
        $values = array();
        $languages = Language::getLanguages(false);
        if (($id_form = (int)Tools::getValue('id_form'))) {
            $form = new LC_Ticket_form($id_form);
            $values['id_form'] = $form->id;
        } else {
            $form = new LC_Ticket_form();
            $values['id_form'] = '';
        }
        $fields = $form->setConfigForm($id_form);
        foreach ($fields as $filed) {
            $key = $filed['name'];
            if ($key != 'id_form') {
                if (isset($filed['lang']) && $filed['lang']) {
                    foreach ($languages as $language) {
                        $values[$key][$language['id_lang']] = $form->id ? $form->{$key}[$language['id_lang']] : (isset($filed['default']) ? $filed['default'] : '');
                    }
                } else {
                    $value = $form->id ? $form->{$key} : (isset($filed['default']) ? $filed['default'] : '');
                    $values[$key] = $filed['type'] != 'checkbox' ? $value : explode(',', $value);
                }
            }
        }
        return $values;
    }

    public function saveObjForm()
    {
        $languages = Language::getLanguages(false);
        $success = '';
        if ($id_form = (int)Tools::getValue('id_form')) {
            $object = new LC_Ticket_form($id_form);
        } else {
            $object = new LC_Ticket_form();
        }
        $fields = $object->setConfigForm($id_form);
        $id_language_default = (int)Configuration::get('PS_LANG_DEFAULT');
        if (!($number_field = (int)Tools::getValue('number_field'))) {
            $this->errors[] = $this->l('Field is required');
        } else {
            $default_labels = Tools::getValue('ets_fields_label_' . $id_language_default);
            if ($default_labels) {
                foreach ($default_labels as $default_label) {
                    if (!$default_label) {
                        $this->errors[] = $this->l('Label field is required');
                        break;
                    }
                }
            }
            foreach ($languages as $language) {
                $language_labels = Tools::getValue('ets_fields_label_' . $language['id_lang']);
                if ($language_labels) {
                    foreach ($language_labels as $language_label) {
                        if ($language_label && !Validate::isCleanHtml($language_label))
                            $this->errors[] = $this->l('Label field is invalid');
                    }
                }
            }

        }
        foreach ($fields as $field) {
            $key = $field['name'];
            if ($object->id == 1 && $key == 'active')
                continue;
            if (!isset($field['lang']) || (isset($field['lang']) && !$field['lang'])) {
                $val = Tools::getValue($key);
                if (isset($field['required']) && $field['required'] && !$val) {
                    $this->errors[] = sprintf($this->l('%s is required'), $field['label']);
                } elseif (isset($field['validate']) && method_exists('Validate', $field['validate'])) {
                    $validate = $field['validate'];
                    if ($val && !is_array($val) && !Validate::$validate($val))
                        $this->errors[] = sprintf($this->l('%s is not valid'), $field['label']);
                    else
                        $object->{$key} = $field['type'] != 'checkbox' ? $val : ($val ? implode(',', $val):'');
                    unset($validate);
                } else
                    $object->{$key} = $field['type'] != 'checkbox' ? $val : ($val ? implode(',', $val):'');
            } elseif (isset($field['lang']) && $field['lang']) {
                $val_default = Tools::getValue($key . '_' . $id_language_default);
                if (isset($field['required']) && $field['required'] && !$val_default) {
                    $this->errors[] = sprintf($this->l('%s is required'), $field['label']);
                } else {
                    $values = array();

                    foreach ($languages as $language) {
                        $val = Tools::getValue($key . '_' . $language['id_lang']);
                        if (isset($field['validate']) && method_exists('Validate', $field['validate'])) {
                            $validate = $field['validate'];
                            if (trim($val) && !Validate::$validate(trim($val)))
                                $this->errors[] = sprintf($this->l('%s in language %s is not valid'), $field['label'], $language['iso_code']);
                            else
                                $values[$language['id_lang']] = $val ?: $val;
                            unset($validate);
                        } else
                            $values[$language['id_lang']] = $val ?: $val_default;
                    }
                    $object->{$key} = $values;
                }
            }

        }
        foreach ($languages as $language) {
            if (($friendly_url = Tools::getValue('friendly_url_' . $language['id_lang'])) && ($id_form = (int)LC_Ticket_form::getIdFormByFriendlyUrl($friendly_url,true)) && $id_form!=$object->id)
                $this->errors[] = $this->l('Friendly URL in ') . $language['iso_code'] . ' ' . $this->l('is exists');
            if (($friendly_url = Tools::getValue('friendly_url_' . $language['id_lang'])) && Validate::isLinkRewrite($friendly_url) && !LC_Tools::checkIsLinkRewrite($friendly_url))
                $this->errors[] = $this->l('Friendly URL in ') . $language['iso_code'] . ' ' . $this->l('is not valid');
        }
        $allow_captcha = (int)Tools::getValue('allow_captcha');
        if ($allow_captcha) {
            $captcha_type = Tools::getValue('captcha_type');
            if ($captcha_type == 'google-v2') {
                if (!($google2_site_key = Tools::getValue('google2_site_key')))
                    $this->errors[] = $this->l('Site key is required');
                elseif (!Validate::isCleanHtml($google2_site_key))
                    $this->errors[] = $this->l('Site key is not valid');
                if (!($google2_secret_key = Tools::getValue('google2_secret_key')))
                    $this->errors[] = $this->l('Secret key is required');
                elseif (!Validate::isCleanHtml($google2_secret_key))
                    $this->errors[] = $this->l('Secret key is required');
            }
            if ($captcha_type == 'google-v3') {
                $google3_site_key = Tools::getValue('google3_site_key');
                $google3_secret_key = Tools::getValue('google3_secret_key');
                if (!$google3_site_key)
                    $this->errors[] = $this->l('Site key is required');
                elseif (!Validate::isCleanHtml($google3_site_key))
                    $this->errors[] = $this->l('Site key is not valid');
                if (!$google3_secret_key)
                    $this->errors[] = $this->l('Secret key is required');
                elseif (!Validate::isCleanHtml($google3_secret_key))
                    $this->errors[] = $this->l('Secret key is not valid');
            }
        }

        if (!$this->errors) {
            if (!$object->id) {
                $object->id_shop = $this->context->shop->id;
                if (!$object->add()) {
                    $this->errors[] = $this->l('Add failed');
                }
                $success = $this->l('Form added successfully');
            } else {
                if (!$object->update())
                    $this->errors[] = $this->l('Update failed');
                else
                    $success = $this->l('Form updated successfully');
            }
        }
        if ($this->errors) {
            if (Tools::isSubmit('run_ajax'))
                die(
                json_encode(
                    array(
                        'error' => $this->displayError($this->errors),
                    )
                )
                );
            else
                return false;
        } else {
            if ($number_field) {
                $ets_fields_id_field = Tools::getValue('ets_fields_id_field');
                $ets_fields_type = Tools::getValue('ets_fields_type');
                $ets_fields_is_contact_mail = Tools::getValue('ets_fields_is_contact_mail');
                $ets_fields_is_contact_name = Tools::getValue('ets_fields_is_contact_name');
                $ets_fields_position = Tools::getValue('ets_fields_position');
                $ets_fields_is_subject = Tools::getValue('ets_fields_is_subject');
                $ets_fields_is_customer_phone_number = Tools::getValue('ets_fields_is_customer_phone_number');
                $ets_fields_required = Tools::getValue('ets_fields_required');
                if ($ets_fields_id_field && LC_Tools::validateArray($ets_fields_id_field, 'isInt') && LC_Tools::validateArray($ets_fields_type) && LC_Tools::validateArray($ets_fields_is_contact_mail, 'isInt') && LC_Tools::validateArray($ets_fields_is_contact_name, 'isInt') && LC_Tools::validateArray($ets_fields_position) && LC_Tools::validateArray($ets_fields_is_subject, 'isInt') && LC_Tools::validateArray($ets_fields_is_customer_phone_number, 'isInt') && LC_Tools::validateArray($ets_fields_required, 'isInt')) {
                    foreach ($ets_fields_id_field as $index => $id_field) {
                        if ($id_field)
                            $field_class = new LC_Ticket_field($id_field);
                        else
                            $field_class = new LC_Ticket_field();
                        if ($object->id == 1 && !$field_class->id)
                            continue;
                        if ($object->id != 1) {
                            $field_class->type = isset($ets_fields_type[$index]) ? $ets_fields_type[$index] : 'text';
                            $field_class->is_contact_mail = isset($ets_fields_is_contact_mail[$index]) ? $ets_fields_is_contact_mail[$index] : 0;
                            $field_class->is_contact_name = isset($ets_fields_is_contact_name[$index]) ? $ets_fields_is_contact_name[$index] : 0;
                            $field_class->id_form = $object->id;
                            $field_class->position = isset($ets_fields_position[$index]) ? $ets_fields_position[$index] : 1;
                            $field_class->is_subject = isset($ets_fields_is_subject[$index]) ? $ets_fields_is_subject[$index] : 0;
                            $field_class->required = isset($ets_fields_required[$index]) ? $ets_fields_required[$index] : '';
                            $field_class->is_customer_phone_number = isset($ets_fields_is_customer_phone_number[$index]) ? $ets_fields_is_customer_phone_number[$index] : '';
                        }
                        $ets_fields_options_default = Tools::getValue('ets_fields_options_' . $id_language_default);
                        $ets_fields_label_default = Tools::getValue('ets_fields_label_' . $id_language_default);
                        $ets_fields_placeholder_default = Tools::getValue('ets_fields_placeholder_' . $id_language_default);
                        $ets_fields_description_default = Tools::getValue('ets_fields_description_' . $id_language_default);
                        if (LC_Tools::validateArray($ets_fields_options_default) && LC_Tools::validateArray($ets_fields_label_default) && LC_Tools::validateArray($ets_fields_placeholder_default) && LC_Tools::validateArray($ets_fields_description_default)) {
                            foreach ($languages as $language) {
                                $ets_fields_options = Tools::getValue('ets_fields_options_' . $language['id_lang']);
                                $ets_fields_label = Tools::getValue('ets_fields_label_' . $language['id_lang']);
                                $ets_fields_placeholder = Tools::getValue('ets_fields_placeholder_' . $language['id_lang']);
                                $ets_fields_description = Tools::getValue('ets_fields_description_' . $language['id_lang']);
                                if (LC_Tools::validateArray($ets_fields_options) && LC_Tools::validateArray($ets_fields_label) && LC_Tools::validateArray($ets_fields_placeholder) && LC_Tools::validateArray($ets_fields_description)) {
                                    $field_class->label[$language['id_lang']] = isset($ets_fields_label[$index]) && $ets_fields_label[$index] ? $ets_fields_label[$index] : (isset($ets_fields_label_default[$index]) ? $ets_fields_label_default[$index] : '');
                                    $field_class->options[$language['id_lang']] = isset($ets_fields_options[$index]) && $ets_fields_options[$index] ? $ets_fields_options[$index] : (isset($ets_fields_options_default[$index]) ? $ets_fields_options_default[$index] : '');
                                    $field_class->placeholder[$language['id_lang']] = isset($ets_fields_placeholder[$index]) && $ets_fields_placeholder[$index] ? $ets_fields_placeholder[$index] : (isset($ets_fields_placeholder_default[$index]) ? $ets_fields_placeholder_default[$index] : '');
                                    $field_class->description[$language['id_lang']] = isset($ets_fields_description[$index]) && $ets_fields_description[$index] ? $ets_fields_description[$index] : (isset($ets_fields_description_default[$index]) ? $ets_fields_description_default[$index] : '');
                                }

                            }
                        }

                        if ($field_class->id)
                            $field_class->update();
                        else
                            $field_class->add();
                    }
                }
            }
            if ($success) {
                if (Tools::isSubmit('run_ajax')) {
                    die(
                    json_encode(
                        array(
                            'error' => false,
                            'success' => $success,
                            'id_form' => $object->id,
                            'link_form' => $this->getAllLinkContactForm($object->id),
                            'form_value' => array(
                                'id_form' => $object->id,
                                'title' => $object->title[$this->context->language->id],
                                'description' => $object->description[$this->context->language->id],
                                'active' => $object->active,
                                'sort_order' => $object->sort_order,
                                'link' => $this->getFormLink($object->id),
                            ),
                            'active_title' => $object->active ? $this->l('Click to disabled') : $this->l('Click to enabled'),
                            'fields_list' => $this->_displayListFields($object->id),)
                    )
                    );
                }
            }
        }
    }
    public function createFormDefault()
    {
        $languages = Language::getLanguages(false);
        $fields = LC_Ticket_form::getInstance()->setConfigForm();
        $form = new LC_Ticket_form();
        foreach ($fields as $filed) {
            $key = $filed['name'];
            if ($key != 'id_form') {
                if (isset($filed['lang']) && $filed['lang']) {
                    $value = array();
                    foreach ($languages as $language) {
                        $value[$language['id_lang']] = $key == 'title' ? ($this->getTextLang('Ticket from chat', $language) ?: $this->l('Ticket from chat')) : ($key == 'friendly_url' ? Tools::link_rewrite(($this->getTextLang('Ticket from chat', $language) ?: $this->l('Ticket from chat'))) : (isset($filed['default']) ? (isset($filed['default_lang']) && $filed['default_lang'] ? $this->getTextLang($filed['default_lang'], $language) : $filed['default']) : ''));
                    }
                    $form->{$key} = $value;
                } else {
                    $form->{$key} = isset($filed['default']) ? $filed['default'] : '';
                }
            }
        }
        $form->id_shop = $this->context->shop->id;
        $form->add();
        $field_class = new LC_Ticket_field();
        $field_class->type = 'text';
        $field_class->is_subject = 1;
        $field_class->required = 1;
        $field_class->id_form = $form->id;
        $field_class->position = 1;
        foreach ($languages as $language) {
            $field_class->label[$language['id_lang']] = $this->getTextLang('Subject', $language) ?: $this->l('Subject');
        }
        $field_class->add();
        $field_class2 = new LC_Ticket_field();
        $field_class2->type = 'text_editor';
        $field_class2->id_form = $form->id;
        $field_class2->position = 2;
        foreach ($languages as $language) {
            $field_class2->label[$language['id_lang']] = $this->getTextLang('Description', $language) ?: $this->l('Description');
        }
        $field_class2->add();
        $field_class3 = new LC_Ticket_field();
        $field_class3->type = 'email';
        $field_class3->id_form = $form->id;
        $field_class3->position = 4;
        $field_class3->is_contact_mail = 1;
        foreach ($languages as $language) {
            $field_class3->label[$language['id_lang']] = $this->getTextLang('Email', $language) ?: $this->l('Email');
        }
        $field_class3->add();
        $field_class4 = new LC_Ticket_field();
        $field_class4->type = 'text';
        $field_class4->id_form = $form->id;
        $field_class4->position = 3;
        $field_class4->is_contact_name = 1;
        foreach ($languages as $language) {
            $field_class4->label[$language['id_lang']] = $this->getTextLang('Name', $language) ?: $this->l('Name');
        }
        $field_class4->add();
        $form = new LC_Ticket_form();
        foreach ($fields as $filed) {
            $key = $filed['name'];
            if ($key != 'id_form') {
                if (isset($filed['lang']) && $filed['lang']) {
                    $value = array();
                    foreach ($languages as $language) {
                        if ($key == 'description')
                            $value[$language['id_lang']] = $this->getTextLang('Form for technical support submit', $language) ?: $this->l('Form for technical support submit');
                        else
                            $value[$language['id_lang']] = $key == 'title' ? ($this->getTextLang('Technical support', $language) ?: $this->l('Technical support')) : ($key == 'friendly_url' ? Tools::link_rewrite(($this->getTextLang('Technical support', $language) ?: $this->l('Technical support'))) : (isset($filed['default']) ? (isset($filed['default_lang']) && $filed['default_lang'] ? $this->getTextLang($filed['default_lang'], $language) : $filed['default']) : ''));
                    }
                    $form->{$key} = $value;
                } else {
                    $form->{$key} = isset($filed['default']) ? $filed['default'] : '';
                }
            }
        }
        $form->id_shop = $this->context->shop->id;
        $form->add();
        $field_class = new LC_Ticket_field();
        $field_class->type = 'text';
        $field_class->is_contact_name = 1;
        $field_class->required = 1;
        $field_class->id_form = $form->id;
        $field_class->position = 1;
        foreach ($languages as $language) {
            $field_class->label[$language['id_lang']] = $this->getTextLang('Name', $language) ?: $this->l('Name');
        }
        $field_class->add();
        $field_class2 = new LC_Ticket_field();
        $field_class2->type = 'email';
        $field_class2->is_contact_mail = 1;
        $field_class2->required = 1;
        $field_class2->id_form = $form->id;
        $field_class2->position = 2;
        foreach ($languages as $language) {
            $field_class2->label[$language['id_lang']] = $this->getTextLang('Email', $language) ?: $this->l('Email');
        }
        $field_class2->add();
        $field_class3 = new LC_Ticket_field();
        $field_class3->type = 'text';
        $field_class3->is_subject = 1;
        $field_class3->required = 1;
        $field_class3->position = 3;
        $field_class3->id_form = $form->id;
        foreach ($languages as $language) {
            $field_class3->label[$language['id_lang']] = $this->getTextLang('Subject', $language) ?: $this->l('Subject');
        }
        $field_class3->add();
        $field_class4 = new LC_Ticket_field();
        $field_class4->type = 'phone_number';
        $field_class4->required = 0;
        $field_class4->is_customer_phone_number = 1;
        $field_class4->id_form = $form->id;
        $field_class4->position = 4;
        foreach ($languages as $language) {
            $field_class4->label[$language['id_lang']] = $this->getTextLang('Phone', $language) ?: $this->l('Phone');
        }
        $field_class4->add();
        $field_class5 = new LC_Ticket_field();
        $field_class5->type = 'file';
        $field_class5->required = 0;
        $field_class5->id_form = $form->id;
        $field_class5->position = 6;
        foreach ($languages as $language) {
            $field_class5->label[$language['id_lang']] = $this->getTextLang('File', $language) ?: $this->l('File');
        }
        $field_class5->add();
        $field_class6 = new LC_Ticket_field();
        $field_class6->type = 'text_editor';
        $field_class6->required = 1;
        $field_class6->id_form = $form->id;
        $field_class6->position = 5;
        foreach ($languages as $language) {
            $field_class6->label[$language['id_lang']] = $this->getTextLang('Message', $language) ?: $this->l('Message');
            $field_class6->placeholder[$language['id_lang']] = $this->getTextLang('Can we help you?', $language) ?: $this->l('Can we help you?');
        }
        $field_class6->add();
    }

    public function GetFormField($id_field = 0, $position = 1)
    {
        $this->context->smarty->assign(
            array(
                'languages' => Language::getLanguages(false),
                'fields' => LC_Ticket_field::getInstance()->setConfigField(),
                'fields_value' => $this->getFieldsFormTicketFieldValues($id_field),
                'id_field' => $id_field,
                'field_class' => new LC_Ticket_field($id_field, $this->context->language->id),
                'position' => $position,
                'defaultFormLanguage' => Configuration::get('PS_LANG_DEFAULT'),
            )
        );
        return $this->display(__FILE__, 'form_field.tpl');
    }

    public function getFieldsFormTicketFieldValues($id_field)
    {
        $values = array();
        $languages = Language::getLanguages(false);
        if ($id_field) {
            $filed_class = new LC_Ticket_field($id_field);
            $values['id_field'] = $filed_class->id;
        } else {
            $filed_class = new LC_Ticket_field();
            $values['id_field'] = '';
        }
        $defin = LC_Ticket_field::$definition;
        $fields = $defin['fields'];
        foreach ($fields as $key => $filed) {
            if (isset($filed['lang']) && $filed['lang']) {
                foreach ($languages as $language) {
                    $values[$key][$language['id_lang']] = $filed_class->id ? $filed_class->{$key}[$language['id_lang']] : (isset($filed['default']) ? $filed['default'] : '');
                }
            } else
                $values[$key] = $filed_class->id ? $filed_class->{$key} : (isset($filed['default']) ? $filed['default'] : '');
        }
        return $values;
    }
    public function getBaseLink()
    {
        $link = (Configuration::get('PS_SSL_ENABLED_EVERYWHERE') ? 'https://' : 'http://') . Context::getContext()->shop->domain . Context::getContext()->shop->getBaseURI();
        return trim($link, '/');
    }

    public function displayMenuTop()
    {
        $controller = Tools::getValue('controller');
        $this->context->smarty->assign(
            array(
                'link' => $this->context->link,
                'controller' => Validate::isControllerName($controller) ? $controller : '',
                'id_profile' => $this->context->employee->id_profile,
            )
        );
        return $this->display(__FILE__, 'menu_top.tpl');
    }
    public function setMeta()
    {
        if (Tools::getValue('controller') == 'history') {
            $id = (int)Tools::getValue('id');
            $metas = array(
                'title' => Tools::isSubmit('viewchat') && $id ? $this->l('Conversation #') . $id : $this->l('Chat history'),
                'meta_title' => Tools::isSubmit('viewchat') && $id ? $this->l('Conversation #') . $id : $this->l('Chat history'),
                'description' => '',
                'keywords' => '',
                'robots' => 'index',
            );
        }
        if (Tools::getValue('controller') == 'info') {
            $metas = array(
                'title' => $this->l('Chat info'),
                'meta_title' => $this->l('Chat info'),
                'description' => '',
                'keywords' => '',
                'robots' => 'index',
            );
        }
        if (Tools::getValue('controller') == 'ticket') {
            $id_ticket = (int)Tools::getValue('id_ticket');
            $metas = array(
                'title' => Tools::isSubmit('viewticket') && $id_ticket ? $this->l('Ticket  #') . $id_ticket : $this->l('Support tickets'),
                'meta_title' => Tools::isSubmit('viewticket') && $id_ticket ? $this->l('Ticket  #') . $id_ticket : $this->l('Support tickets'),
                'description' => '',
                'keywords' => '',
                'robots' => 'index',
            );
        }
        if ($this->is17) {
            $body_classes = array(
                'lang-' . $this->context->language->iso_code => true,
                'lang-rtl' => (bool)$this->context->language->is_rtl,
                'country-' . $this->context->country->iso_code => true,
            );
            $page = array(
                'title' => '',
                'canonical' => '',
                'meta' => $metas,
                'page_name' => 'lc_form_page',
                'body_classes' => $body_classes,
                'admin_notifications' => array(),
            );
            $this->context->smarty->assign(array('page' => $page));
        } else {
            $this->context->smarty->assign($metas);
        }
    }

    public function getProductCurrent($conversation)
    {
        if (($id_product = (int)Tools::getValue('product_page_product_id')) && Configuration::get('ETS_LC_SEND_PRODUCT_LINK') && (!$conversation || $conversation->end_chat)) {
            return $this->getProductInfo($id_product);
        }
        return false;
    }

    public function getProductInfo($id_product)
    {
        $id_customer = (isset($this->context->customer->id) && $this->context->customer->id) ? (int)($this->context->customer->id) : 0;
        $id_group = null;
        if ($id_customer) {
            $id_group = Customer::getDefaultGroupId((int)$id_customer);
        }
        if (!$id_group) {
            $id_group = (int)Group::getCurrent()->id;
        }
        $group = new Group($id_group);
        if ($group->price_display_method)
            $tax = false;
        else
            $tax = true;
        $product = new Product($id_product, true, Configuration::get('PS_LANG_DEFAULT'));
        if (!Validate::isLoadedObject($product))
            return false;
        $id_product_attribute = $product->getDefaultIdProductAttribute();
        $pinfo = array();
        $pinfo['name'] = $product->name;
        $price = $product->getPrice($tax, null);
        $oldPrice = $product->getPriceWithoutReduct(!$tax, false);
        $discount = $oldPrice - $price;
        $pinfo['price'] = $this->context->controller ? Tools::displayPrice($price) : '';
        $pinfo['old_price'] = $this->context->controller ? Tools::displayPrice($oldPrice) : '';
        $pinfo['discount_percent'] = (($oldPrice - $price) > 0 ? round(($oldPrice - $price) / $oldPrice * 100) : 0);
        $pinfo['discount_amount'] = $this->context->controller ? Tools::displayPrice($discount) : '';;
        $pinfo['id_product'] = $product->id;
        $images = $product->getImages((int)$this->context->cookie->id_lang);
        $link = $this->context->link;
        if (isset($images[0]))
            $id_image = Configuration::get('PS_LEGACY_IMAGES') ? ($product->id . '-' . $images[0]['id_image']) : $images[0]['id_image'];
        else
            $id_image = $this->context->language->iso_code . '-default';
        $pinfo['img_url'] = $link->getImageLink($product->link_rewrite, $id_image, LC_Tools::getFormattedName('home'));
        $pinfo['link'] = $link->getProductLink($product, null, null, null, null, null, $id_product_attribute);
        return $pinfo;
    }

    public function getProductHtml($id_product)
    {
        if ($product = $this->getProductInfo($id_product)) {
            $this->context->smarty->assign(
                array(
                    'product' => $product,
                )
            );
            return $this->display(__FILE__, 'product.tpl');
        }
    }

    public function _submitSendMail($id_conversation)
    {
        $conversation = new LC_Conversation($id_conversation);
        $errors = array();
        if (!($title_mail = Tools::getValue('title_mail'))) {
            $errors[] = $this->l('Title is required');
        } elseif ($title_mail && !Validate::isCleanHtml($title_mail))
            $errors[] = $this->l('Title is invalid');
        if (!($content_mail = Tools::getValue('content_mail')))
            $errors[] = $this->l('Message is required');
        elseif ($content_mail && !Validate::isCleanHtml($content_mail)) {
            $errors[] = $this->l('Message is invalid');
        }
        if ($conversation->id_customer) {
            $customer = new Customer($conversation->id_customer);
            $email = $customer->email;
            $name = $customer->firstname . ' ' . $customer->lastname;
        } else {
            $email = $conversation->customer_email;
            $name = $conversation->customer_name;
        }
        if (!$email || !Validate::isEmail($email))
            $errors[] = $this->l('Customer email is invalid');
        if ($errors) {
            die(
                json_encode(
                    array(
                        'error' => $this->displayError($errors),
                    )
                )
            );
        } else {
            $template_vars = array(
                '{content_mail}' => $content_mail,
                '{content_mail_txt}' => strip_tags($content_mail),
            );
            if (Mail::Send(
                isset($customer) ? $customer->id_lang : ($conversation->id_lang ?: Context::getContext()->language->id),
                'livechat_message',
                $title_mail,
                $template_vars,
                $email,
                $name,
                null,
                null,
                null,
                null,
                dirname(__FILE__) . '/mails/',
                null,
                Context::getContext()->shop->id
            )) {
                die(
                    json_encode(
                        array(
                            'error' => false,
                            'success' => $this->l('Email sent successfully'),
                        )
                    )
                );
            } else {
                die(
                    json_encode(
                        array(
                            'error' => $this->displayError($this->l('Sending email failed')),
                        )
                    )
                );
            }
        }

    }

    public function getCountMessage($filter)
    {
        return LC_Message::getCountMessage($filter,$this->all_shop);
    }
    public function getCountConversation($filter = false)
    {
        return LC_Conversation::getCountConversation($filter,$this->all_shop);
    }
    public function hookModuleRoutes($params)
    {
        $subfix = (int)Configuration::get('ETS_LC_URL_SUBFIX') ? '.html' : '';
        $support_alias = Configuration::get('ETS_LC_URL_ALIAS', $this->context->language->id) ?: 'support';
        Configuration::updateValue('PS_ROUTE_livechatform', '');
        Configuration::updateValue('PS_ROUTE_livechatformnoid', '');
        $routers = array(
            'livechatform' => array(
                'controller' => 'form',
                'rule' => $support_alias . '/{id_form}-{url_alias}' . $subfix,
                'keywords' => array(
                    'id_form' => array('regexp' => '[0-9]+', 'param' => 'id_form'),
                    'url_alias' => array('regexp' => '[_a-zA-Z0-9-]+', 'param' => 'url_alias'),
                ),
                'params' => array(
                    'fc' => 'module',
                    'module' => $this->name,
                ),
            ),
            'livechatformnoid' => array(
                'controller' => 'form',
                'rule' => $support_alias . '/{url_alias}' . $subfix,
                'keywords' => array(
                    'url_alias' => array('regexp' => '[_a-zA-Z0-9-]+', 'param' => 'url_alias'),
                ),
                'params' => array(
                    'fc' => 'module',
                    'module' => $this->name,
                ),
            ),
        );
        return $routers;
    }

    public function getLangLinkFriendly($id_lang = null, Context $context = null, $id_shop = null)
    {
        if (!$context)
            $context = Context::getContext();
        if (Language::isMultiLanguageActivated($id_shop ?: $context->shop->id) && (int)Configuration::get('ETS_SEO_ENABLE_REMOVE_LANG_CODE_IN_URL') && $id_lang == (int)Configuration::get('PS_LANG_DEFAULT')) {
            return '';
        }
        if ((!Configuration::get('PS_REWRITING_SETTINGS') && in_array($id_shop, array($context->shop->id, null))) || !Language::isMultiLanguageActivated($id_shop) || !(int)Configuration::get('PS_REWRITING_SETTINGS', null, null, $id_shop))
            return '';
        if (Module::isEnabled('ets_seo') && Language::isMultiLanguageActivated($id_shop) && (int)Configuration::get('ETS_SEO_ENABLE_REMOVE_LANG_CODE_IN_URL') && $id_lang == (int)Configuration::get('PS_LANG_DEFAULT')) {
            return '';
        }
        if (!$id_lang)
            $id_lang = $context->language->id;

        return Language::getIsoById($id_lang) . '/';
    }

    public function getBaseLinkFriendly($id_shop = null, $ssl = null)
    {
        static $force_ssl = null;

        if ($ssl === null) {
            if ($force_ssl === null)
                $force_ssl = (Configuration::get('PS_SSL_ENABLED') && Configuration::get('PS_SSL_ENABLED_EVERYWHERE'));
            $ssl = $force_ssl;
        }

        if (Configuration::get('PS_MULTISHOP_FEATURE_ACTIVE') && $id_shop !== null)
            $shop = new Shop($id_shop);
        else
            $shop = Context::getContext()->shop;

        $base = ($ssl ? 'https://' . $shop->domain_ssl : 'http://' . $shop->domain);

        return $base . $shop->getBaseURI();
    }

    public function getFormLink($id_form, $params = array(), $id_lang = 0)
    {
        if (!$id_lang)
            $id_lang = $this->context->language->id;
        if (Configuration::get('PS_REWRITING_SETTINGS') && LC_Base::checkVesionModule()) {
            $form = new LC_Ticket_form($id_form, $id_lang);
            if ($form->friendly_url) {
                $subfix = (int)Configuration::get('ETS_LC_URL_SUBFIX') ? '.html' : '';
                $support_alias = Configuration::get('ETS_LC_URL_ALIAS', $id_lang) ?: 'support';
                $url = $this->getBaseLinkFriendly(null, null) . $this->getLangLinkFriendly($id_lang, null, null) . $support_alias;
                $url .= '/' . (!Configuration::get('ETS_LC_URL_REMOVE_ID') ? $form->id . '-' : '') . $form->friendly_url . $subfix;
                if (isset($params['id_form']))
                    unset($params['id_form']);
                if ($params) {
                    $extra = '';
                    foreach ($params as $key => $param)
                        $extra .= '&' . $key . '=' . $param;
                    $url .= '?' . ltrim($extra, '&');
                }
                return $url;
            }
        }
        $params['id_form'] = $id_form;
        return $this->context->link->getModuleLink($this->name, 'form', $params, null, $id_lang);
    }
    public function displayRecentlyCustomer($count_login_customers, $login_customers)
    {
        $this->context->smarty->assign(
            array(
                'login_customers' => $login_customers,
                'count_login_customers' => $count_login_customers,
            )
        );
        return $this->display(__FILE__, 'recently_customers.tpl');
    }
    public static function displayPriority($priority)
    {
        return Module::getInstanceByName('ets_livechat')->getPriority($priority);
    }
    public function getPriority($priority)
    {
        if ($priority == '1')
            return $this->l('low');
        if ($priority == 2)
            return $this->l('medium');
        if ($priority == 3)
            return $this->l('high');
        else
            return $this->l('urgent');
    }
    public function displayBlockSupport($position)
    {
        if (!Configuration::get('ETS_LIVECHAT_ADMIN_TICKET'))
            return '';
        $forms = LC_Ticket_form::getForms(true);
        if ($forms) {
            foreach ($forms as &$form) {
                $form['link'] = $this->getFormLink($form['id_form']);

            }
            $this->context->smarty->assign(
                array(
                    'forms' => $forms,
                    'position' => $position,
                    'ps17' => $this->is17,
                )
            );
            return $this->display(__FILE__, 'form_block.tpl');
        }
        return '';
    }

    public function hookDisplayLeftColumn()
    {
        if (in_array('left', explode(',', Configuration::get('ETS_LV_SUPPORT_TICKET')))) {
            return $this->displayBlockSupport('left');
        }
        return '';
    }

    public function hookDisplayFooter()
    {
        $html = '';
        if (in_array('footer', explode(',', Configuration::get('ETS_LV_SUPPORT_TICKET')))) {
            $html .= $this->displayBlockSupport('footer');
        }
        $controller = Tools::getValue('controller');
        if($controller == 'orderdetail' && ($id_order = (int)Tools::getValue('id_order')))
        {
            $html .= $this->displayFormOnOrderPage($id_order);
        }
        if ($this->context->controller instanceof IdentityController && isset($this->context->customer->id) && $this->context->customer->id > 0 && $this->context->customer->isLogged()) {

            $html .= $this->renderFormCustomerInformation();
        }
        return $html;
    }

    public function hookDisplayRightColumn()
    {
        if (in_array('right', explode(',', Configuration::get('ETS_LV_SUPPORT_TICKET')))) {
            return $this->displayBlockSupport('right');
        }
        return '';
    }

    public function hookDisplayNav()
    {
        if (in_array('top_nav', explode(',', Configuration::get('ETS_LV_SUPPORT_TICKET')))) {
            return $this->displayBlockSupport('top_nav');
        }
        return '';
    }

    public function hookDisplayNav1()
    {
        if (in_array('top_nav', explode(',', Configuration::get('ETS_LV_SUPPORT_TICKET')))) {
            return $this->displayBlockSupport('top_nav');
        }
        return '';
    }

    public function hookCustomBlockSupport()
    {
        if (in_array('custom_hook', explode(',', Configuration::get('ETS_LV_SUPPORT_TICKET')))) {
            return $this->displayBlockSupport('custom_hook');
        }
        return '';
    }
    public function displayFormOnProductPage($id_product)
    {
        if(($forms = LC_Ticket_form::getListFormInProductPage()))
        {
            foreach($forms as &$form)
            {
                $form['link'] = $this->getFormLink($form['id_form'],$id_product ? array('id_product_ref' => $id_product): array());
            }
            $this->context->smarty->assign(
                array(
                    'product_page_forms' => $forms,
                )
            );
            return $this->display(__FILE__,'form_on_product_page.tpl');
        }
        return '';
    }
    public function displayFormOnOrderPage($id_order)
    {
        if(($order = new Order($id_order)) && Validate::isLoadedObject($order) && ($forms = LC_Ticket_form::getListFormInOrderPage()))
        {
            foreach($forms as &$form)
            {
                $form['link'] = $this->getFormLink($form['id_form'],$order->reference ? array('order_ref' => $order->reference): array());
            }
            $this->context->smarty->assign(
                array(
                    'order_page_forms' => $forms,
                )
            );
            return $this->display(__FILE__,'form_on_order_page.tpl');
        }
    }
    public function hookDisplayProductAdditionalInfo($params)
    {
        if(isset($params['product']) && ($product= $params['product']) && isset($product['id_product']))
        {
            return $this->displayFormOnProductPage($product['id_product']);
        }
    }
    public function hookDisplayRightColumnProduct()
    {
        if(($id_product = (int)Tools::getValue('id_product')) && Validate::isUnsignedId($id_product))
        {
            return $this->displayFormOnProductPage($id_product);
        }
    }
    public function updateLastAction()
    {

        if ($this->all_shop && $this->shops) {
            foreach ($this->shops as $shop) {
                Configuration::updateValue('ETS_LC_DATE_ACTION_LAST', date('Y-m-d H:i:s'), true, $shop['id_shop_group'], $shop['id_shop']);
                LC_Conversation::updateAdminOnline($shop['id_shop']);
            }
        }
        Configuration::updateValue('ETS_LC_DATE_ACTION_LAST', date('Y-m-d H:i:s'));
        LC_Conversation::updateAdminOnline();
    }
    public function getAdminLink($controller, $token = true)
    {
        return $this->getBaseLink() . '/' . Configuration::get('ETS_DIRECTORY_ADMIN_URL') . '/index.php' . ($controller ? '?controller=' . $controller : '') . ($token && $controller ? '&token=' . Tools::getAdminTokenLite($controller) : '');
    }
    public function updateDefaultConfig()
    {
        $languages = Language::getLanguages(false);
        if ($configs = LC_Base::getInstance()->setConfig()) {
            foreach ($configs as $key => $config) {
                if (Configuration::get($key) === false) {
                    if (isset($config['lang']) && $config['lang']) {
                        $values = array();
                        foreach ($languages as $lang) {
                            $values[$lang['id_lang']] = isset($config['default']) ? $config['default'] : '';
                        }
                        Configuration::updateValue($key, $values, true);
                    } else
                        Configuration::updateValue($key, isset($config['default']) ? $config['default'] : '', true);
                }

            }
        }
        if (!Configuration::getGlobalValue('ETS_LC_FO_TOKEN')) {
            Configuration::updateGlobalValue('ETS_LC_FO_TOKEN', Tools::passwdGen(32));
        }
        if (!file_exists(_PS_ETS_LIVE_CHAT_IMG_DIR_ . 'customeravata.jpg'))
            Tools::copy(dirname(__FILE__) . '/views/img/temp/customeravata.jpg', _PS_ETS_LIVE_CHAT_IMG_DIR_ . 'customeravata.jpg');
        if (!file_exists(_PS_ETS_LIVE_CHAT_IMG_DIR_ . 'chatbubble.png'))
            Tools::copy(dirname(__FILE__) . '/views/img/temp/chatbubble.png', _PS_ETS_LIVE_CHAT_IMG_DIR_ . 'chatbubble.png');
        if (!file_exists(_PS_ETS_LIVE_CHAT_IMG_DIR_ . 'adminavatar.jpg'))
            Tools::copy(dirname(__FILE__) . '/views/img/temp/adminavatar.jpg', _PS_ETS_LIVE_CHAT_IMG_DIR_ . 'adminavatar.jpg');
    }
    public function getAllLinkContactForm($id_form, $id_lang = 0)
    {
        $this->context->smarty->assign(
            array(
                'languages' => Language::getLanguages(false),
                'id_current_lang' => $id_lang ?: (int)Configuration::get('PS_LANG_DEFAULT'),
                'ets_livechat' => $this,
                'id_form' => $id_form,
            )
        );
        return $this->display(__FILE__, 'all_link_contact.tpl');
    }
    public function rrmdir($dir)
    {
        $dir = rtrim($dir, '/');
        if ($dir && is_dir($dir)) {
            if ($objects = scandir($dir)) {
                foreach ($objects as $object) {
                    if ($object != "." && $object != "..") {
                        if (is_dir($dir . "/" . $object) && !is_link($dir . "/" . $object))
                            $this->rrmdir($dir . "/" . $object);
                        elseif(file_exists($dir . "/" . $object))
                            @unlink($dir . "/" . $object);
                    }
                }
            }
            rmdir($dir);
        }
    }
    public function getLinkOrderAdmin($id_order)
    {
        if (version_compare(_PS_VERSION_, '1.7.7.0', '>=')) {
            $sfContainer = call_user_func(array('\PrestaShop\PrestaShop\Adapter\SymfonyContainer', 'getInstance'));
            if (null !== $sfContainer) {
                $sfRouter = $sfContainer->get('router');
                $link_order = $sfRouter->generate(
                    'admin_orders_view',
                    array('orderId' => $id_order)
                );
            }
        } else
            $link_order = $this->context->link->getAdminLink('AdminOrders') . '&id_order=' . (int)$id_order . '&vieworder';
        return $link_order;
    }

    public function _runCronJob()
    {
        $ok = false;
        if (Configuration::get('ETS_LIVECHAT_ADMIN_TICKET') && $days = (int)Configuration::get('ETS_LC_DAY_AUTO_CLOSE_TICKET')) {
            $tickets = LC_Ticket::getTicketExpriedSupport($days);
            if ($tickets) {
                $ok = true;
                foreach ($tickets as $ticket) {
                    $ticketObj = new LC_Ticket($ticket['id_message']);
                    $ticketObj->status = 'close';
                    if($ticketObj->update())
                    {
                        if ($customer = LC_Ticket::getEmailCustomer($ticket['id_message'])) {
                            $form_class = new LC_Ticket_form($ticket['id_form'], (int)Configuration::get('PS_LANG_DEFAULT'));
                            $template_vars = array(
                                '{customer_name}' => $customer['name'],
                                '{subject}' => $ticket['subject'],
                                '{days}'=>$days,
                                '{link_ticket}' => Context::getContext()->link->getModuleLink($this->name, 'ticket', array('viewticket' => 1, 'id_ticket' => $ticket['id_message'])),
                            );
                            Mail::Send(
                                $customer['id_lang'] ?: Configuration::get('PS_LANG_DEFAULT'),
                                'close_ticket_customer',
                                $this->getTextLang('Your ticket has been closed', $customer['id_lang'] ?: Configuration::get('PS_LANG_DEFAULT')) ?: $this->l('Your ticket has been closed'),
                                $template_vars,
                                $customer['email'],
                                $customer['name'] ? $customer['name'] : null,
                                $form_class->send_from_email ?: null,
                                $form_class->send_from_name ?: null,
                                null,
                                null,
                                dirname(__FILE__) . '/mails/',
                                false,
                                $ticket['id_shop'],
                                null,
                                $customer['email'] ? $customer['email'] : null,
                                $customer['name'] ? $customer['name'] : null
                            );
                        }
                    }

                }
            }
        }
        if ($ok && Configuration::getGlobalValue('ETS_LC_SAVE_CRONJOB_LOG'))
            file_put_contents(_PS_CACHE_DIR_.'ets_livechat_cronjob_log.txt', Tools::displayDate(date('Y-m-d H:i:s'), Configuration::get('PS_LANG_DEFAULT'), true) . ': ' . count($tickets) . ' ' . (count($tickets) == 1 ? $this->l('ticket has been closed') : $this->l('tickets have been closed')) . "\n", FILE_APPEND);
        if (!$ok && Configuration::getGlobalValue('ETS_LC_SAVE_CRONJOB_LOG'))
            file_put_contents(_PS_CACHE_DIR_.'ets_livechat_cronjob_log.txt', Tools::displayDate(date('Y-m-d H:i:s'), Configuration::get('PS_LANG_DEFAULT'), true) . ': ' . $this->l('Cronjob run but nothing to do') . "\n", FILE_APPEND);
        Configuration::updateGlobalValue('ETS_LC_CRONJOB_TIME',date('Y-m-d H:i:s'));
        if (Tools::isSubmit('ajax'))
            die(
                json_encode(
                    array(
                        'success' => $this->l('Cronjob done'),
                        'cronjob_log' => file_exists(_PS_CACHE_DIR_.'ets_livechat_cronjob_log.txt') ? Tools::file_get_contents(_PS_CACHE_DIR_.'ets_livechat_cronjob_log.txt') : '',
                    )
                )
            );
    }
    public function getTextLang($text, $lang, $file_name = '')
    {
        $text2 = preg_replace("/\\\*'/", "\'", $text);
        if (is_array($lang))
            $iso_code = $lang['iso_code'];
        elseif (is_object($lang))
            $iso_code = $lang->iso_code;
        else {
            $language = new Language($lang);
            $iso_code = $language->iso_code;
        }
        $modulePath = rtrim(_PS_MODULE_DIR_, '/') . '/' . $this->name;
        $fileTransDir = $modulePath . '/translations/' . $iso_code . '.' . 'php';
        if (!@file_exists($fileTransDir)) {
            return $text;
        }
        $fileContent = Tools::file_get_contents($fileTransDir);
        $strMd5 = md5($text2);
        $keyMd5 = '<{' . $this->name . '}prestashop>' . ($file_name ? Tools::strtolower($file_name) : $this->name) . '_' . $strMd5;
        preg_match('/(\$_MODULE\[\'' . preg_quote($keyMd5) . '\'\]\s*=\s*\')(.*)(\';)/', $fileContent, $matches);
        if ($matches && isset($matches[2])) {
            return $matches[2];
        }
        return $text;
    }

    public function moveForderUpload()
    {
        $this->copy_directory(_PS_UPLOAD_DIR_ . 'ets_livechat/downloads', _PS_DOWNLOAD_DIR_ . 'ets_livechat');
        $this->copy_directory(_PS_UPLOAD_DIR_ . 'ets_livechat/config', _PS_IMG_DIR_ . 'ets_livechat');
        $this->rrmdir(_PS_UPLOAD_DIR_ . 'ets_livechat');
    }

    static public function replace_link($text)
    {
        if (Tools::strpos($text, 'http') !== false || Tools::strpos($text, 'https') !== false) {
            $pattern = "/(?i)\b((?:https?:\/\/|www\d{0,3}[.]|[a-z0-9.\-]+[.][a-z]{2,4}\/)(?:[^\s()<>]+|\(([^\s()<>]+|(\([^\s()<>]+\)))*\))+(?:\(([^\s()<>]+|(\([^\s()<>]+\)))*\)|[^\s`!()\[\]{};:'\".,<>?«»“”‘’]))/";
            $text = preg_replace($pattern, Module::getInstanceByName('ets_livechat')->displayText('$1', 'a', '', array('href' => '$1', 'target' => '_blank', 'rel' => 'noopener noreferrer nofollow')), $text);
        }
        return $text;
    }

    public function hookContactLink($params)
    {
        if (isset($params['id_form']) && ($id_form = (int)$params['id_form']) && ($form = new LC_Ticket_form($id_form, $this->context->language->id)) && Validate::isLoadedObject($form) && ((isset($params['label']) && $params['label']) || $form->button_link_contact_label)) {
            $link_form = $this->getFormLink($form->id ,isset($params['id_product'])&& (int)$params['id_product'] && ($product = new Product($params['id_product'])) && Validate::isLoadedObject($product) ? array('id_product_ref'=>$params['id_product']):array());
            
            $this->smarty->assign(
                array(
                    'contact_label' => isset($params['label']) && $params['label'] ? $params['label'] : $form->button_link_contact_label,
                    'link_form'=>$link_form,
                    'ticket_icon' => isset($params['icon']) ? $params['icon']:'',
                )
            );
            return $this->display(__FILE__,'contact_link.tpl');
        }
    }

    public static function isAdmin()
    {
        $context = Context::getContext();
        return defined('_PS_ADMIN_DIR_') && isset($context->employee) && (int)$context->employee->id && $context->employee->isLoggedBack();
    }
    public function displayText($content = null, $tag=null, $class = null, $attr_datas = array())
    {
        $text = '<' . $tag . ' ';
        if ($attr_datas) {
            foreach ($attr_datas as $key => $value)
                $text .= $key . '="' . $value . '" ';
        }
        if ($class && is_string($class)) {
            $text .= ' class="' . $class . '" ';
        }
        if ($tag == 'img' || $tag == 'br' || $tag == 'path' || $tag == 'input')
            $text .= ' />';
        else
            $text .= '>';
        if ($tag && $tag != 'img' && $tag != 'input' && $tag != 'br' && !is_null($content))
            $text .= $content;
        if ($tag && $tag != 'img' && $tag != 'path' && $tag != 'input' && $tag != 'br')
            $text .= '</' . $tag . '>';
        return $text;
    }
    public static function getInstance()
    {
        return Module::getInstanceByName('ets_livechat');
    }
    public function setAdminForder()
    {
        $admin_dir = basename(getcwd());
        $forder_name = Configuration::get('ETS_DIRECTORY_ADMIN_URL');
        if($forder_name!=$admin_dir)
            Configuration::get('ETS_DIRECTORY_ADMIN_URL',$forder_name);
    }
    public function hookActionCustomerGridDefinitionModifier($params)
    {
        $definition = &$params['definition'];
        $columns = $definition->getColumns();
        $columns->remove('actions');
        $actions = (new PrestaShop\PrestaShop\Core\Grid\Action\Row\RowActionCollection())
                        ->add(
                            (new PrestaShop\PrestaShop\Core\Grid\Action\Row\Type\LinkRowAction('edit'))
                            ->setName($this->trans('Edit', [], 'Admin.Actions'))
                            ->setIcon('edit')
                            ->setOptions([
                                'route' => 'admin_customers_edit',
                                'route_param_name' => 'customerId',
                                'route_param_field' => 'id_customer',
                            ])
                        )
                        ->add(
                            (new PrestaShop\PrestaShop\Core\Grid\Action\Row\Type\LinkRowAction('view'))
                            ->setName($this->trans('View', [], 'Admin.Actions'))
                            ->setIcon('zoom_in')
                            ->setOptions([
                                'route' => 'admin_customers_view',
                                'route_param_name' => 'customerId',
                                'route_param_field' => 'id_customer',
                            ])
                        )
                        ->add((new PrestaShop\PrestaShop\Core\Grid\Action\Row\Type\Customer\DeleteCustomerRowAction('delete'))
                            ->setName($this->trans('Delete', [], 'Admin.Actions'))
                            ->setIcon('delete')
                            ->setOptions([
                                'customer_id_field' => 'id_customer',
                                'customer_delete_route' => 'admin_customers_delete',
                            ])
                        );
        if(Module::isEnabled('ets_ordermanager'))
        {
            $actions->add((new PrestaShop\PrestaShop\Core\Grid\Action\Row\Type\LinkRowAction('login_as_customer'))
                            ->setName($this->l('Login as customer'))
                            ->setIcon('fa_ets fa-user')
                            ->setOptions([
                                'route' => 'admin_customers_login_as_customer',
                                'route_param_name' => 'customerId',
                                'route_param_field' =>'id_customer',
                            ])
                        );
        }
        if(Module::isEnabled('ets_trackingcustomer'))
        {
            $actions->add((new PrestaShop\PrestaShop\Core\Grid\Action\Row\Type\LinkRowAction('restore'))
                            ->setName($this->l('Customer sessions'))
                            ->setIcon('fa_ets fa-file-o')
                            ->setOptions([
                                'route' => 'admin_customers_activities',
                                'route_param_name' => 'customerId',
                                'route_param_field' =>'id_customer',
                            ])
                        );
        }
        $actions->add((new PrestaShop\PrestaShop\Core\Grid\Action\Row\Type\LinkRowAction('create_ticket_as_customer'))
            ->setName($this->l('Create ticket'))
            ->setIcon('fa_ets fa-ticket')
            ->setOptions([
                'route' => 'admin_customers_create_ticket_as_customer',
                'route_param_name' => 'customerId',
                'route_param_field' =>'id_customer',
            ])
        );
        $columns->add((new PrestaShop\PrestaShop\Core\Grid\Column\Type\Common\ActionColumn('actions'))
                ->setName($this->trans('Actions', [], 'Admin.Global'))
                ->setOptions([
                    'actions' => $actions,
                ]));
    }
    public function getListFormTickets($link_support = false){
        $forms = LC_Ticket_form::getForms();
        if ($forms) {
            foreach ($forms as &$form) {
                $form['link'] = $this->context->link->getAdminLink('AdminLiveChatTickets') . '&addticket&id_form=' . $form['id_form'];
            }
        }
        if($link_support)
            return $forms ? $forms[0]['link']:'';
        return $forms;
    }
    public function getAdminBaseLink($idShop = null, $ssl = null, $relativeProtocol = false)
    {
        if (null === $ssl) {
            $ssl = Configuration::get('PS_SSL_ENABLED') && Configuration::get('PS_SSL_ENABLED_EVERYWHERE');
        }

        if (Configuration::get('PS_MULTISHOP_FEATURE_ACTIVE')) {
            if (null === $idShop) {
                $idShop = LC_Tools::getMatchingUrlShopId();
            }

            //Use the matching shop if present, or fallback on the default one
            if (null !== $idShop) {
                $shop = new Shop($idShop);
            } else {
                $shop = new Shop(Configuration::get('PS_SHOP_DEFAULT'));
            }
        } else {
            $shop = Context::getContext()->shop;
        }

        if ($relativeProtocol) {
            $base = '//' . ($ssl && Configuration::get('PS_SSL_ENABLED') ? $shop->domain_ssl : $shop->domain);
        } else {
            $base = (($ssl && Configuration::get('PS_SSL_ENABLED')) ? 'https://' . $shop->domain_ssl : 'http://' . $shop->domain);
        }

        return $base . $shop->getBaseURI();
    }
    public function isBackOffice($supper_admin = false)
    {
        return defined('_PS_ADMIN_DIR_') && isset($this->context->employee) && $this->context->employee->id > 0 && (!$supper_admin || $this->context->employee->id_profile = _PS_ADMIN_PROFILE_) ? 1 : 0;
    }
    public function displayListOrder($id_customer, $page = 1, $limit = 50)
    {
        $id_order = (int)Tools::getValue('id_order');
        if(Module::isEnabled('ets_shoplicense'))
        {
            return Hook::exec('SLDisplayListOrder',array('id_customer'=>$id_customer,'page'=>$page,'limit'=>$limit));
        }
        $products = LC_Base::getListProductOrders($id_customer,$id_order,$page,$limit);
        $count_product = LC_Base::getCountProductOrder($id_customer,$id_order);
        if ($products) {
            foreach ($products as &$product) {
                $product['current_state'] = $this->displayOrderState($product['current_state']);
                $id_image = 0;
                if ($product['product_attribute_id']) {
                    $image = Product::getCombinationImageById($product['product_attribute_id'], $this->context->language->id);
                    if ($image)
                        $id_image = $image['id_image'];
                }
                if (!$id_image)
                    $id_image = ($cover = Product::getCover($product['product_id'])) ? $cover['id_image']:0;
                if ($id_image) {
                    $type_image = ImageType::getFormattedName('small');
                    $path = implode('/', str_split((string)$id_image)) . '/';
                    $product['image'] = $this->displayText('', 'img', '', array('src' => __PS_BASE_URI__ . 'img/p/' . $path . $id_image . '-' . $type_image . '.jpg', 'style' => 'width:45px;'));
                } else
                    $product['image'] = '--';
                $product['link_order'] = LC_Ticket_process::getInstance()->isAdmin() ? $this->getLinkOrderAdmin($product['id_order']):'';
                $product['link_product'] =LC_Ticket_process::getInstance()->isAdmin() ? $this->context->link->getAdminLink('AdminProducts',true,array('id_product' => $product['product_id'])) : $this->context->link->getProductLink($product['product_id']);
            }
            $this->context->smarty->assign(
                array(
                    'products' => $products,
                    'id_customer' => $id_customer,
                    'id_order' => $id_order,
                    'page_next' => $page + 1,
                    'load_more' => $count_product > $limit * $page,
                )
            );
            return $this->display(__FILE__,'ticket_products.tpl');
        }
    }
    public function displayOrderState($id_order_state)
    {
        if ($id_order_state && ($orderState = new OrderState($id_order_state,$this->context->language->id)) && Validate::isLoadedObject($orderState)){
            $this->context->smarty->assign(
                array(
                    'orderState' => $orderState,
                )
            );
            return $this->display(__FILE__,'order_state.tpl');

        }
        return '--';
    }
    public function getTwigs()
    {
        return array(
            'ets_lc_link_customer_create_ticket' =>$this->getListFormTickets(true),
            'Create_ticket_text' => $this->l('Create ticket'),
        );
    }
    public function _loadMoreMessagesTicket()
    {
        $process = LC_Ticket_process::getInstance()->setContext($this->context)->setModule($this);
        if (($id_ticket = (int)Tools::getValue('id_ticket'))
            && ($ticket = $process->checkAccessTicket($id_ticket))
            && ($nbMessages = (int)Configuration::get('ETS_LC_NUMBER_TICKET_MESSAGES'))
        ) {
            LC_Ticket::makeReadTicket($id_ticket, self::isAdmin() || $process->isManagerTicket() && (int)$ticket['id_customer'] !== (int)$this->context->customer->id ? 1 : 0);

            $last_note = LC_Note::getLastItem($id_ticket);
            $countMessages = $process->getMessagesTicket($ticket, false, false, false, true);
            $nbPages = ceil($countMessages / $nbMessages);
            $page = (int)Tools::getValue('page');
            if ($page < 1)
                $page = 1;
            if ($page > $nbPages)
                $page = $nbPages;
            $start = $countMessages - $nbMessages * $page;
            $messages = $process->getMessagesTicket($ticket, false, $nbMessages ?: false, $start > 0 ? $start : 0, false, $last_note);
            $tpl_vars = [
                'messages' => $messages,
                'ETS_LC_AVATAR_IMAGE_TYPE' => Configuration::get('ETS_LC_AVATAR_IMAGE_TYPE'),
                'link_basic' => $this->getBaseLink(),
            ];
            $this->context->smarty->assign($tpl_vars);
            $process->ajaxRender([
                'list_messages' => $this->display(__FILE__,'admin_ticket_massages.tpl'),
                'loadmore' => $start > 0 ? true : false,
                'next_page' => $page + 1,
            ]);
        }
    }
    public function postAdminProcess()
    {
        $process = LC_Ticket_process::getInstance()->setContext($this->context)->setModule($this);
        if (Tools::getValue('load_more_message_ticket') || Tools::isSubmit('load_more_message_ticket')) {
            $this->_loadMoreMessagesTicket();
        }

        if (Tools::isSubmit('deleteticket')
            && ($id_ticket = (int)Tools::getValue('id_ticket'))
            && Validate::isUnsignedInt($id_ticket)
            && $id_ticket > 0
        ) {
            if ($process->checkAccessTicket($id_ticket)) {
                $ticket_class = new LC_Ticket($id_ticket);
                if ($ticket_class->delete()) {
                    Tools::redirectAdmin($this->context->link->getAdminLink('AdminLiveChatTickets') . '&conf=2');
                } else
                    $this->_errors[] = $this->l('Delete ticket failed');
            } else {
                Tools::redirectAdmin($this->context->link->getAdminLink('AdminLiveChatTickets'));
            }
        }

        if (Tools::isSubmit('changestatus')
            && ($changestatus = Tools::getValue('changestatus'))
            && in_array($changestatus, array('open', 'close', 'cancel'))
            && ($id_ticket = (int)Tools::getValue('id_ticket'))
            && Validate::isUnsignedInt($id_ticket)
            && $id_ticket > 0
        ) {
            if ($process->checkAccessTicket($id_ticket)) {
                $ticketObj = New LC_Ticket(($id_ticket));
                $ticketObj->status = $changestatus;
                if($ticketObj->update())
                {
                    if (Tools::isSubmit('viewticket')) {
                        Tools::redirectAdmin($this->context->link->getAdminLink('AdminLiveChatTickets') . '&viewticket&id_ticket=' . (int)$id_ticket . '&conf=4');
                    } else {
                        Tools::redirectAdmin($this->context->link->getAdminLink('AdminLiveChatTickets') . '&conf=4');
                    }
                }

            } else {
                Tools::redirectAdmin($this->context->link->getAdminLink('AdminLiveChatTickets'));
            }
        }
        if (Tools::isSubmit('change_priority')
            && ($id_ticket = (int)Tools::getValue('id_ticket'))
            && Validate::isUnsignedInt($id_ticket)
            && $id_ticket > 0
        ) {
            if ($process->checkAccessTicket($id_ticket)) {
                $ticket_priority = (int)Tools::getValue('ticket_priority');
                $ticketObj = new LC_Ticket($id_ticket);
                $ticketObj->priority = $ticket_priority;
                if($ticketObj->update())
                    Tools::redirectAdmin($this->context->link->getAdminLink('AdminLiveChatTickets') . '&viewticket&id_ticket=' . (int)$id_ticket . '&conf=4');
            } else
                Tools::redirectAdmin($this->context->link->getAdminLink('AdminLiveChatTickets'));
        }
        if (Tools::isSubmit('transfer_ticket')
            && ($id_ticket = (int)Tools::getValue('id_ticket'))
            && Validate::isUnsignedInt($id_ticket)
            && $id_ticket > 0
        ) {
            if ($process->checkAccessTicket($id_ticket)) {
                $id_departments_ticket = (int)Tools::getValue('id_departments_ticket');
                $id_employee_ticket = (int)Tools::getValue('id_employee_ticket');
                $ticketObj = new LC_Ticket($id_ticket);
                $ticketObj->id_departments = $id_departments_ticket;
                $ticketObj->id_employee = $id_employee_ticket;
                $ticketObj->readed = 0;
                if ($ticketObj->update()) {
                    Tools::redirectAdmin($this->context->link->getAdminLink('AdminLiveChatTickets') . '&viewticket&id_ticket=' . (int)$id_ticket . '&conf=444');
                }
            } else {
                Tools::redirectAdmin($this->context->link->getAdminLink('AdminLiveChatTickets'));
            }
        }
        if (Tools::isSubmit('submitBulkActionTicket')
            && ($bulk_action_ticket = Tools::getValue('bulk_action_ticket'))
            && ($ticket_readed = array_keys(Tools::getValue('ticket_readed')))
            && LC_Tools::validateArray($ticket_readed, 'isInt')
        ) {
            if ($bulk_action_ticket == 'mark_as_read') {
                if(LC_Ticket::markAsRead($ticket_readed))
                {
                    $process->ajaxRender([
                        'url_reload' => $this->context->link->getAdminLink('AdminLiveChatTickets') . '&conf=4'
                    ]);
                }
            } elseif ($bulk_action_ticket == 'mark_as_unread') {
                if(LC_Ticket::markAsUnread($ticket_readed))
                {
                    $process->ajaxRender([
                        'url_reload' => $this->context->link->getAdminLink('AdminLiveChatTickets') . '&conf=4'
                    ]);
                }
            } elseif ($bulk_action_ticket == 'delete_selected') {
                if(LC_Ticket::markAsDelete($ticket_readed))
                {
                    $process->ajaxRender([
                        'url_reload' => $this->context->link->getAdminLink('AdminLiveChatTickets') . '&conf=2'
                    ]);
                }
            }
        }
        if (Tools::isSubmit('lc_send_message_ticket')
            && ($id_ticket = (int)Tools::getValue('id_ticket'))
            && Validate::isUnsignedInt($id_ticket)
            && $id_ticket > 0
            && self::isAdmin()
        ) {
            if (!$process->checkAccessTicket($id_ticket)) {
                $process->ajaxRender([
                    'error' => $this->displayError([$this->l('Permission denied!')]),
                ]);
            }
            $attachments = array();
            $name_file = '';
            $ticketObj = new LC_Ticket($id_ticket);
            $form_class = new LC_Ticket_form($ticketObj->id_form, $this->context->language->id);
            $note = trim((string) Tools::getValue('ticket_note', ''));
            if (trim($note) != '' && !Validate::isCleanHtml($note)) {
                $this->_errors[] = $this->l('Message is not valid');
            }
            if ($process->validateUpload('ticket_file', $this->_errors)
                && isset($_FILES['ticket_file']['name'])
                && !empty($_FILES['ticket_file']['name'])
            ) {
                $type = Tools::strtolower(Tools::substr(strrchr($_FILES['ticket_file']['name'], '.'), 1));
                $_FILES['ticket_file']['name'] = str_replace(' ', '_', $_FILES['ticket_file']['name']);
                $file_name = $_FILES['ticket_file']['name'];
                $_FILES['ticket_file']['name'] = Tools::strtolower(Tools::passwdGen(20, 'NO_NUMERIC'));
                $attachment = array(
                    'rename' => uniqid() . Tools::strtolower(Tools::substr($file_name, -5)),
                    'content' => Tools::file_get_contents($_FILES['ticket_file']['tmp_name']),
                    'tmp_name' => $_FILES['ticket_file']['tmp_name'],
                    'name' => $file_name,
                    'mime' => $_FILES['ticket_file']['type'],
                    'error' => $_FILES['ticket_file']['error'],
                    'size' => $_FILES['ticket_file']['size'],
                );
                $attachments[] = $attachment;
                $name_file = $_FILES['ticket_file']['name'];
                if ($form_class->save_staff_file) {
                    $fileName = _PS_ETS_LIVE_CHAT_UPLOAD_DIR_ . $name_file;
                    if (!in_array($type, $this->file_types)) {
                        $this->_errors[] = $this->l('Uploaded file is invalid');
                    } else {
                        $max_size = Configuration::get('ETS_LC_MAX_FILE_MS');
                        $file_size = Tools::ps_round($_FILES['ticket_file']['size'] / 1048576, 2);
                        if ($file_size > $max_size && $max_size > 0)
                            $this->_errors[] = $this->l('Attachment size exceeds the allowable limit.');
                        else {
                            if (file_exists($fileName)) {
                                $name_file = Tools::strtolower(Tools::passwdGen(6, 'NO_NUMERIC')) . $name_file;
                                $fileName = _PS_ETS_LIVE_CHAT_UPLOAD_DIR_ . $name_file;
                            }
                            if (file_exists($fileName)) {
                                $this->_errors[] = $this->l('File already exists. Try to rename the file then upload again.');
                            }
                        }

                    }
                }
            }
            elseif(trim(strip_tags($note)) == '')
                $this->_errors[] = $this->l('Message is required');
            $note_origin = Tools::getValue('ticket_note_origin');
            if (trim($note_origin) != '' && (trim(strip_tags($note_origin)) == '' || !Validate::isCleanHtml($note_origin))) {
                $this->_errors[] = $this->l('Message origin is not valid');
            }
            if ($this->_errors) {
                $process->ajaxRender([
                    'error' => $this->displayError($this->_errors)
                ]);
            } else {
                if (isset($_FILES['ticket_file']['tmp_name'])
                    && $_FILES['ticket_file']['tmp_name']
                    && isset($fileName)
                    && $fileName
                    && !move_uploaded_file($_FILES['ticket_file']['tmp_name'], $fileName)
                ) {
                    $process->ajaxRender([
                        'error' => $this->displayError($this->l('Cannot upload the file')),
                    ]);
                }
                $last_note = LC_Note::getLastItem($id_ticket);
                $note_class = new LC_Note();
                $note_class->id_message = $id_ticket;
                $note_class->id_employee = (int)$this->context->employee->id;
                $note_class->id_customer = 0;
                $note_class->employee = 1;
                $note_class->note = Tools::nl2br(trim(strip_tags($note)));
                $note_class->note_origin = Tools::nl2br(trim(strip_tags($note_origin)));
                $note_class->file_name = isset($file_name) ? $file_name : '';
                if ($note_class->add()) {
                    $ticket = new LC_Ticket($id_ticket);
                    $ticket->date_admin_update = date('Y-m-d H:i:s');
                    $ticket->customer_readed = 0;
                    $ticket->readed = 1;
                    $ticket->replied = 1;
                    $ticket->update();
                    if ($note_class->file_name) {
                        if ($form_class->save_staff_file) {
                            $download = new LC_Download();
                            $download->id_note = $note_class->id;
                            $download->filename = isset($file_name) ? $file_name :'';
                            $download->name = $name_file;
                            $download->file_type = $_FILES['ticket_file']['type'];
                            $download->file_size = $_FILES['ticket_file']['size'] / 1024;
                            if ($download->add()) {
                                $note_class->id_download = $download->id;
                                $note_class->update();
                            }
                        } else {
                            $note_class->id_download = -1;
                            $note_class->update();
                        }
                    }

                    if (($form_class->send_mail_reply_customer && ($customer = LC_Ticket::getEmailCustomer($id_ticket))) || $form_class->send_mail_to_admin_admin_reply) {
                        $employeeInfo = LC_Departments::getStaffByEmployee($this->context->employee->id);
                        $signature = $employeeInfo && isset($employeeInfo['signature']) && $employeeInfo['signature'] ? $employeeInfo['signature']:'';
                        $file_attachment = '';
                        $file_attachment_txt = '';
                        if ($note_class->file_name && $note_class->id_download &&  $note_class->id_download !=-1 && ($download = new LC_Download($note_class->id_download)) && Validate::isLoadedObject($download)) {
                            $link_download = $this->context->link->getModuleLink($this->name, 'download', array('downloadfile' => md5(_COOKIE_KEY_ . $download->id)));
                            $file_attachment = ($note ? Module::getInstanceByName('ets_livechat')->displayText('|&lt;', 'span', '') : '') . Module::getInstanceByName('ets_livechat')->displayText(Module::getInstanceByName('ets_livechat')->displayText($note_class->file_name, 'a', '', array('href' => $link_download)) . ($download->file_size ? Module::getInstanceByName('ets_livechat')->displayText('(' . ($download->file_size >= 1024 ? Tools::ps_round($download->file_size / 1024, 2) : $download->file_size) . ' ' . ($download->file_size >= 1024 ? 'MB' : 'KB') . ')', 'span', 'file_size') : ''), 'span', 'message_file');
                            $file_attachment_txt = ($note ? "\n" : '') . $note_class->file_name . ' :' . $link_download;
                        }
                        $template_vars = array(
                            '{mail_content}' => Tools::nl2br(trim(Ets_livechat::replace_link(strip_tags($note)))) . $file_attachment . ($signature ? '<'.'br/'.'>'.'-----' . Module::getInstanceByName('ets_livechat')->displayText(Tools::nl2br(strip_tags($signature)), 'p', 'employee_signature') : ''),
                            '{mail_content_txt}' => strip_tags(trim($note)) . $file_attachment_txt . ($signature ? "\n" . strip_tags($signature) : ''),
                            '{customer_name}' => isset($customer) && isset($customer['name']) ?  $customer['name']:'',
                            '{employee_name}' => $this->context->employee->firstname . ' ' . $this->context->employee->lastname,
                            '{link_ticket}' => $this->context->link->getModuleLink($this->name, 'ticket', array('viewticket' => 1, 'id_ticket' => $ticket->id,'token' => md5(_COOKIE_KEY_.$ticket->id.$ticket->date_add))),
                            '{view_ticket}' => $this->getAdminLink(false, false),
                            '{ticket_id}' => $id_ticket,
                            '{ticket_subject}' => $ticket->subject,
                        );
                        if ($form_class->send_mail_reply_customer && ($customer = LC_Ticket::getEmailCustomer($id_ticket))) {
                            if (Mail::Send(
                                $customer['id_lang'],
                                'reply_ticket_to_customer',
                                Configuration::get('PS_SHOP_NAME') . ' ' . ($this->getTextLang('just replied to your ticket', $customer['id_lang']) ?: $this->l('just replied to your ticket')) . ' (#' . $ticket->id . '-' . $ticket->subject . ')',
                                $template_vars,
                                $customer['email'],
                                $customer['name'] ? $customer['name'] : null,
                                $form_class->send_from_email ?: null,
                                $form_class->send_from_name ?: null,
                                !$form_class->save_staff_file || !$customer['id_customer'] ? $attachments : null,
                                null,
                                $this->getLocalPath() . 'mails/',
                                false,
                                $this->context->shop->id,
                                null,
                                Configuration::get('PS_SHOP_EMAIL'),
                                Configuration::get('PS_SHOP_NAME')
                            )) {
                                $note_class->send_email_customer = 'success';
                            } else {
                                $note_class->send_email_customer = 'error';
                            }
                        }
                        if ($form_class->send_mail_to_admin_admin_reply) {
                            if (Mail::Send(
                                $this->context->language->id,
                                'admin_reply_ticket_to_admin',
                                $this->l('You have been successfully replied on') . ' (#' . $ticket->subject . ')',
                                $template_vars,
                                $this->context->employee->email,
                                $this->context->employee->firstname . ' ' . $this->context->employee->lastname,
                                $form_class->send_from_email ?: null,
                                $form_class->send_from_name ?: null,
                                !$form_class->save_staff_file ? $attachments : null,
                                null,
                                $this->getLocalPath() . 'mails/',
                                false,
                                $this->context->shop->id,
                                null,
                                Configuration::get('PS_SHOP_EMAIL'),
                                Configuration::get('PS_SHOP_NAME')
                            )) {
                                $note_class->send_email_admin = 'success';
                            } else {
                                $note_class->send_email_admin = 'error';
                            }
                        }
                        if ($note_class->send_email_customer != '' || $note_class->send_email_admin != '') {
                            $note_class->update();
                        }
                    }

                    if ($note_class->send_email_admin != 'error' && $note_class->send_email_customer != 'error') {
                        $warning = false;
                    } else {
                        $warning = true;
                    }

                    if ($note_class->send_email_admin == '' && $note_class->send_email_customer == '')
                        $success = $this->l('Your message has been sent successfully.');
                    elseif ($note_class->send_email_admin == 'success' && $note_class->send_email_customer == 'success')
                        $success = $this->l('Your message has been sent successfully. Email was also sent to admin and customer.');
                    elseif ($note_class->send_email_customer == 'success') {
                        if ($note_class->send_email_admin == 'error')
                            $success = $this->l('Your message has been sent successfully. Email was also sent to customer but could not be sent to admin');
                        else
                            $success = $this->l('Your message has been sent successfully. Email was also sent to customer successfully.');
                    } elseif ($note_class->send_email_admin == 'success') {
                        if ($note_class->send_email_customer == 'error')
                            $success = $this->l('Your message has been sent successfully. Email was also sent to admin but could not be sent to customer');
                        else
                            $success = $this->l('Your message has been sent successfully. Email was also sent to admin.');
                    } elseif ($note_class->send_email_customer == 'error')
                        $success = $this->l('Your message has been sent successfully. Email could not be sent to customer.');
                    elseif ($note_class->send_email_admin == 'error')
                        $success = $this->l('Your message has been sent successfully. Email could not be sent to admin.');

                    $process->ajaxRender([
                        'error' => false,
                        'id_note' => $note_class->id,
                        'messages' => $process->getMessagesTicket($id_ticket, 'DESC', 1, 0, false, $last_note),
                        'success' => isset($success) && $success ? $success : '',
                        'warning' => $warning,
                    ]);
                } else {
                    if (isset($fileName) && $fileName && file_exists($fileName)) {
                        @unlink($fileName);
                    }
                    $process->ajaxRender([
                        'error' => $this->displayError($this->l('Message was not sent. Unknown error occurred.')),
                    ]);
                }
            }
        }
    }
    public function getProductTicket($id_product)
    {
        if (($product = new Product($id_product, false, $this->context->language->id))
            && $product->id > 0
        ) {
            $product = [
                'id_product' => $id_product,
                'name' => $product->name
            ];
            $image = Product::getCover($id_product, $this->context);
            if ($image) {
                $path = implode('/', str_split((string)$image['id_image'])) . '/';
                $product['image'] = __PS_BASE_URI__ . 'img/p/' . $path . $image['id_image'] . '-' . LC_Tools::getFormattedName('small') . '.jpg';
            } else {
                $product['image'] = '';
            }
            $this->context->smarty->assign(array(
                'product' => $product,
            ));

            return $this->display(__FILE__,'ticket_product.tpl');
        }

        return '--';
    }
    public function hookActionObjectLanguageAddAfter()
    {
        LC_Base::duplicateRowsFromDefaultShopLang(_DB_PREFIX_.'ets_livechat_auto_msg_lang',$this->context->shop->id,'id_auto_msg');
        LC_Base::duplicateRowsFromDefaultShopLang(_DB_PREFIX_.'ets_livechat_ticket_form_field_lang',$this->context->shop->id,'id_field');
        LC_Base::duplicateRowsFromDefaultShopLang(_DB_PREFIX_.'ets_livechat_ticket_form_lang',$this->context->shop->id,'id_form');
    }
}
