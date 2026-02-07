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

/**
 * Class Ets_livechatTicketModuleFrontController
 * @property \Ets_livechat $module
 */
class Ets_livechatTicketModuleFrontController extends ModuleFrontController
{
    public $display_column_left = false;
    public $display_column_right = false;
    public $_errors = array();
    public $_success;
    /** @var LC_Ticket_process */
    public $process;

    public function __construct()
    {
        parent::__construct();
        $this->display_column_right = false;
        $this->display_column_left = false;
        $this->process = LC_Ticket_process::getInstance()->setContext(Context::getContext())->setModule($this->module);
    }
    public function initContent()
    {

        if (!$this->context->customer->isLogged() && !((Tools::isSubmit('viewticket') || Tools::isSubmit('lc_send_message_ticket'))
                && ($id_ticket = (int)Tools::getValue('id_ticket'))
                && Validate::isUnsignedInt($id_ticket)
                && $id_ticket > 0 && $this->process->checkAccessTicket($id_ticket)) ) {
            Tools::redirect('index.php?controller=authentication');
        }
        parent::initContent();
        if (Tools::isSubmit('getMoreViewListOrder') && $this->process->isManagerTicket()
            && ($id_customer = (int)Tools::getValue('id_customer')) && ($customer = new Customer($id_customer))
            && Validate::isLoadedObject($customer)
        ) {
            $id_order = (int)Tools::getValue('id_order');
            $count_product = LC_Base::getCountProductOrder($id_customer,$id_order);
            $page = (int)Tools::getValue('page');
            die(
                json_encode(
                    array(
                        'list_orders' => $this->module->displayListOrder($id_customer, $page, 50),
                        'load_more' => $count_product > 50 * $page,
                        'page_next' => $page + 1,
                    )
                )
            );
        }
        if ($this->process->isManagerTicket() && Tools::isSubmit('getViewListOrder')
            && ($id_customer = (int)Tools::getValue('id_customer'))
            && Validate::isUnsignedInt($id_customer)
            && ($customer = new Customer($id_customer))
            && Validate::isLoadedObject($customer)
        ) {
            die(
                json_encode(
                    array(
                        'list_orders' => $this->module->displayListOrder($id_customer, 1, 50),
                    )
                )
            );
        }
        // set Meta:
        if (!LC_Base::getMetaByController('ticket')) {
            $this->module->setMeta();
        }
        // Process:
        if (Tools::getValue('load_more_message_ticket') || Tools::isSubmit('load_more_message_ticket')) {
            $this->module->_loadMoreMessagesTicket();
        }
        if (Tools::isSubmit('lc_send_message_ticket')
            && ($id_ticket = (int)Tools::getValue('id_ticket'))
            && Validate::isUnsignedInt($id_ticket)
            && $id_ticket > 0
            && !Ets_livechat::isAdmin()
        ) {
            $this->sendMessageTicket($id_ticket);
        }

        // Front:
        if (Tools::isSubmit('set_rating_ticket')
            && ($id_ticket = (int)Tools::getValue('id_ticket'))
            && ($rating = (int)Tools::getValue('rating'))
            && $rating >= 1
            && $rating <= 5
        ) {
            $process = LC_Ticket_process::getInstance()->setContext($this->context)->setModule($this->module);
            if ($process->checkAccessTicket($id_ticket)) {
                $ticket = new LC_Ticket($id_ticket);
                $ticket->rate = $rating;
                $ticket->update();
                $process->ajaxRender([
                    'success' => $this->module->l('Rating submitted','ticket')
                ]);
            } else {
                $process->ajaxRender([
                    'errors' => $this->module->l('You do not have permission to rate this ticket','ticket')
                ]);
            }
        }
        if (($conf = Tools::getValue('conf')) && $conf == 2) {
            $this->_success = $this->module->l('Deleted ticket successfully', 'ticket');
        }
        $this->context->smarty->assign([
            'errors_html' => $this->_errors ? $this->module->displayError($this->_errors) : false,
            'sucsecfull_html' => $this->_success ? $this->module->displaySuccessMessage($this->_success) : '',
            'breadcrumb' => $this->module->is17 ? $this->module->getBreadCrumb() : false,
            'path' => $this->module->getBreadCrumb(),
        ]);

        // View ticket:
        if (Tools::isSubmit('viewticket')
            && ($id_ticket = (int)Tools::getValue('id_ticket'))
            && Validate::isUnsignedInt($id_ticket)
            && $id_ticket > 0
        ) {
            $this->context->smarty->assign([
                'detail_ticket' => $this->displayDetailTicket($id_ticket),
                'products_licenseBeforeExpried' =>Hook::exec('actionCheckLicenseBeforeExpried',array('id_customer'=>$this->context->customer->id,'list'=>true)),
                'products_licenseExpried' =>Hook::exec('actionCheckLicenseExpried',array('id_customer'=>$this->context->customer->id,'list'=>true))
            ]);

            $this->setTemplate($this->module->is17 ? 'module:ets_livechat/views/templates/front/ticket.tpl' : 'ticket16.tpl');
        } else {
            $this->context->smarty->assign([
                'list_ticket' => $this->getListTickets(),
                'products_licenseBeforeExpried' =>Hook::exec('actionCheckLicenseBeforeExpried',array('id_customer'=>$this->context->customer->id,'list'=>true)),
                'products_licenseExpried' =>Hook::exec('actionCheckLicenseExpried',array('id_customer'=>$this->context->customer->id,'list'=>true)),
                'text' => $this->module->displayWarning('adddd')
            ]);
            $this->setTemplate($this->module->is17 ? 'module:ets_livechat/views/templates/front/tickets.tpl' : 'tickets16.tpl');
        }
    }
    public function sendMessageTicket($id_ticket)
    {
        $process = LC_Ticket_process::getInstance()->setContext($this->context)->setModule($this->module);
        if (!$process->checkAccessTicket($id_ticket))
            $this->module->_errors[] = $this->module->l('Permission denied!','ticket');
        else {
            $note = trim((string) Tools::getValue('ticket_note', ''));
            if (!Validate::isCleanHtml($note)) {
                $this->module->_errors[] = $this->module->l('Message is invalid','ticket');
            }
            $ticketObj = new LC_Ticket($id_ticket);
            $form_class = new LC_Ticket_form($ticketObj->id_form, $this->context->language->id);
            $name_file = '';
            $attachments = array();
            if ($form_class->customer_reply_upload_file
                && $process->validateUpload('ticket_file', $this->_errors)
                && isset($_FILES['ticket_file']['name'])
                && !empty($_FILES['ticket_file']['name'])
            ) {
                $_FILES['ticket_file']['name'] = str_replace(' ', '_', $_FILES['ticket_file']['name']);
                $attachment = array(
                    'rename' => uniqid() . Tools::strtolower(Tools::substr($_FILES['ticket_file']['name'], -5)),
                    'content' => Tools::file_get_contents($_FILES['ticket_file']['tmp_name']),
                    'tmp_name' => $_FILES['ticket_file']['tmp_name'],
                    'name' => $_FILES['ticket_file']['name'],
                    'mime' => $_FILES['ticket_file']['type'],
                    'error' => $_FILES['ticket_file']['error'],
                    'size' => $_FILES['ticket_file']['size'],
                );
                $attachments[] = $attachment;
                $type = Tools::strtolower(Tools::substr(strrchr($_FILES['ticket_file']['name'], '.'), 1));
                $name_file = Tools::strtolower(Tools::passwdGen(12));
                if ($form_class->save_customer_file) {
                    $fileName = _PS_ETS_LIVE_CHAT_UPLOAD_DIR_ . $name_file;
                    if (!in_array($type, $this->module->file_types)) {
                        $this->module->_errors[] = sprintf($this->module->l('File format is not accepted' . ': %s','ticket'),$_FILES['ticket_file']['name']);
                    } else {
                        $max_size = (float)Configuration::get('ETS_LC_MAX_FILE_MS');
                        $file_size = Tools::ps_round($_FILES['ticket_file']['size'] / 1048576, 2);
                        if ($file_size > $max_size && $max_size > 0)
                            $this->module->_errors[] = $this->module->l('Attachment size exceeds the allowable limit.','ticket');
                        else {
                            if (file_exists($fileName)) {
                                $name_file = Tools::strtolower(Tools::passwdGen(12)) . $name_file;
                                $fileName = _PS_ETS_LIVE_CHAT_UPLOAD_DIR_ . $name_file;
                            }
                            if (file_exists($fileName)) {
                                $this->module->_errors[] = $this->module->l('Avatar already exists. Try to rename the file then upload again','ticket');
                            }
                        }
                    }
                }
            } elseif (trim(strip_tags($note)) == '' && !$this->module->_errors) {
                $this->module->_errors[] = $this->module->l('Message is required','ticket');
            }
            if ($this->module->_errors) {
                $process->ajaxRender([
                    'error' => $this->module->displayError($this->module->_errors)
                ]);
            } else {
                if (isset($_FILES['ticket_file']['tmp_name'])
                    && $_FILES['ticket_file']['tmp_name']
                    && isset($fileName)
                    && $fileName
                    && !move_uploaded_file($_FILES['ticket_file']['tmp_name'], $fileName)
                ) {
                    $process->ajaxRender([
                        'error' => $this->module->displayError($this->module->l('Cannot upload the file','ticket'))
                    ]);
                }
                $ticket = new LC_Ticket($id_ticket);
                $is_manager_ticket = $process->isManagerTicket() ? 1 : 0;

                $last_note = LC_Note::getLastItem($id_ticket);
                $note_class = new LC_Note();
                $note_class->id_message = $id_ticket;
                $note_class->id_employee = 0;
                $note_class->id_customer = $this->context->customer->id;
                $note_class->employee = $is_manager_ticket;
                $note_class->note = Tools::nl2br(trim(strip_tags($note)));
                $note_class->file_name = isset($_FILES['ticket_file']['name']) ? $_FILES['ticket_file']['name'] : '';
                if ($note_class->add()) {
                    if(!$is_manager_ticket)
                        $ticket->date_customer_update = date('Y-m-d H:i:s');
                    else
                        $ticket->date_admin_update = date('Y-m-d H:i:s');
                    if ($is_manager_ticket) {
                        $ticket->replied = 1;
                        $ticket->customer_readed = 0;
                        $ticket->readed = 1;
                    } else {
                        $ticket->replied = 0;
                        $ticket->customer_readed = 1;
                        $ticket->readed = 0;
                    }
                    $ticket->status = 'open';
                    $ticket->update();
                    if ($note_class->file_name) {
                        if ($form_class->save_customer_file) {
                            $download = new LC_Download();
                            $download->id_note = $note_class->id;
                            $download->filename = $_FILES['ticket_file']['name'];
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
                    if ($form_class->send_mail_reply_admin || $form_class->send_mail_to_customer_customer_reply) {
                        $file_attachment = '';
                        $file_attachment_txt = '';
                        if ($note_class->file_name && $note_class->id_download && $note_class->id_download != -1 && ($download = new LC_Download($note_class->id_download)) && Validate::isLoadedObject($download)) {
                            $link_download = $this->module->getBaseLink() . '/modules/ets_livechat/download.php?downloadfile=' . md5(_COOKIE_KEY_ . $download->id);
                            $file_attachment = ($note ? $this->module->displayText('', 'br', '') : '') . $this->module->displayText($this->module->displayText($note_class->file_name, 'a', '', array('href' => $link_download)) . ($download->file_size ? $this->module->displayText('(' . ($download->file_size >= 1024 ? Tools::ps_round($download->file_size / 1024, 2) : $download->file_size) . ' ' . ($download->file_size >= 1024 ? 'MB' : 'KB') . ')', 'span', 'file_size') : ''), 'span', 'message_file');
                            $file_attachment_txt = ($note ? "\n" : '') . $note_class->file_name . ' :' . $link_download;
                        }
                        if(LC_Ticket::managerTicket($this->context->customer->email))
                        {
                            $customerInfo = LC_Departments::getCustomerInfo($this->context->customer->id);
                            $signature = $customerInfo &&  isset($customerInfo['signature']) && $customerInfo['signature'] ? $customerInfo['signature']:'';
                        }
                        $template_vars = array(
                            '{mail_content}' => Tools::nl2br(trim(Ets_livechat::replace_link(strip_tags($note)))) . $file_attachment.(isset($signature) && $signature ? '<'.'br/'.'>'.'-----' . $this->module->displayText(Tools::nl2br(strip_tags($signature)), 'p', 'employee_signature') : ''),
                            '{mail_content_txt}' => trim(strip_tags($note)) . $file_attachment_txt.(isset($signature) && $signature ? "\n" . strip_tags($signature) : ''),
                            '{customer_name}' => $this->context->customer->firstname . ' ' . $this->context->customer->lastname,
                            '{ticket_id}' => $ticket->id,
                            '{ticket_subject}' => $ticket->subject,
                            '{link_reply}' => $this->module->getAdminLink(false, false),
                            '{link_ticket}' => $this->context->link->getModuleLink($this->module->name, 'ticket', array('viewticket' => 1, 'id_ticket' => $ticket->id)),
                        );
                        if ($form_class->send_mail_reply_admin) {
                            $email_info = $form_class->getEmailAdminInfo($ticket->id_departments);
                            if ($process->isManagerTicket() && (int)$ticket->id_customer !== (int)$this->context->customer->id) {
                                $managers = LC_Note::getEmailsManagerTicket($id_ticket);
                                $email_info['mails_to'] = isset($email_info['mails_to']) && is_array($email_info['mails_to']) && count($email_info['mails_to']) > 0 ? array_merge($email_info['mails_to'], $managers['mails_to']) : $managers['mails_to'];
                                $email_info['names_to'] = isset($email_info['names_to']) && is_array($email_info['names_to']) && count($email_info['names_to']) > 0 ? array_merge($email_info['names_to'], $managers['names_to']) : $managers['names_to'];
                            }
                            if (isset($email_info['mails_to'])
                                && is_array($email_info['mails_to'])
                                && ($mails_to = $email_info['mails_to'])
                            ) {
                                $names_to = isset($email_info['names_to']) ? $email_info['names_to'] : [];
                                if (Mail::Send(
                                    Configuration::get('PS_LANG_DEFAULT'),
                                    'reply_ticket_to_admin',
                                    $this->module->getTextLang('Customer has just replied to a ticket', Configuration::get('PS_LANG_DEFAULT'),'ticket') ?: $this->module->l('Customer has just replied to a ticket','ticket'),
                                    $template_vars,
                                    $mails_to,
                                    $names_to,
                                    $form_class->send_from_email ?: null,
                                    $form_class->send_from_name ?: null,
                                    !$form_class->save_customer_file ? $attachments : null,
                                    null,
                                    $this->module->getLocalPath() . 'mails/',
                                    false,
                                    $this->context->shop->id,
                                    null,
                                    $this->context->customer->email,
                                    $this->context->customer->firstname . ' ' . $this->context->customer->lastname
                                )) {
                                    $note_class->send_email_admin = 'success';
                                } else {
                                    $note_class->send_email_admin = 'error';
                                }
                            }
                        }
                        if ($form_class->send_mail_to_customer_customer_reply) {
                            $to_email = $this->context->customer->email;
                            $to_name = $this->context->customer->firstname . ' ' . $this->context->customer->lastname;
                            if ($process->isManagerTicket() && (int)$ticket->id_customer !== (int)$this->context->customer->id) {
                                $customer = new Customer((int)$ticket->id_customer);
                                $to_email = $customer->email;
                                $to_name = $customer->firstname . ' ' . $customer->lastname;
                            }
                            if ($to_email && Validate::isEmail($to_email) && Mail::Send(
                                    $this->context->language->id,
                                    'customer_reply_ticket_to_customer',
                                    $this->module->l('You have been successfully replied on #','ticket') . $ticket->subject,
                                    $template_vars,
                                    $to_email,
                                    $to_name,
                                    $form_class->send_from_email ?: null,
                                    $form_class->send_from_name ?: null,
                                    !$form_class->save_customer_file ? $attachments : null,
                                    null,
                                    $this->module->getLocalPath() . 'mails/',
                                    false,
                                    $this->context->shop->id,
                                    null,
                                    $this->context->customer->email,
                                    $this->context->customer->firstname . ' ' . $this->context->customer->lastname
                                )) {
                                $note_class->send_email_customer = 'success';
                            } else {
                                $note_class->send_email_customer = 'error';
                            }
                        }
                        if ($note_class->send_email_admin != '' || $note_class->send_email_customer != '')
                            $note_class->update();
                    }
                    $success = $this->module->l('Your message has been sent successfully.','ticket');
                    $process->ajaxRender([
                        'error' => false,
                        'id_note' => $note_class->id,
                        'messages' => $process->getMessagesTicket($id_ticket, 'DESC', 1, 0, false, $last_note),
                        'warning' => false,
                        'success' => $success ?: '',
                    ]);
                } else {
                    if (isset($fileName)
                        && $fileName
                        && file_exists($fileName)
                    ) {
                        @unlink($fileName);
                    }
                    $process->ajaxRender([
                        'error' => $this->module->displayError($this->module->l('Your message could not be sent.','ticket'))
                    ]);
                }
            }
        }
    }
    public function displayDetailTicket($id_ticket)
    {
        if ($ticket = $this->process->checkAccessTicket($id_ticket)) {
            $is_manager_ticket = $this->process->isManagerTicket();
            // Make read ticket:
            LC_Ticket::makeReadTicket($id_ticket, $is_manager_ticket);

            $nbMessages = (int)Configuration::get('ETS_LC_NUMBER_TICKET_MESSAGES');
            $countMessages = $this->process->getMessagesTicket($ticket, false, false, false, true);
            if ($countMessages > $nbMessages) {
                $start = $countMessages - $nbMessages;
            } else {
                $start = 0;
            }
            $messages = $this->process->getMessagesTicket($ticket, false, $nbMessages ?: false, $start, false);
            if ($ticket['id_form']) {
                $form_class = new LC_Ticket_form($ticket['id_form']);
            } else {
                Tools::redirectLink($this->context->link->getModuleLink($this->module->name, 'ticket'));
            }

            // make read note ticket:
            LC_Note::makeReadNote($id_ticket, $is_manager_ticket ? ' AND (employee = 0 OR id_employee > 0)' : ' AND id_employee > 0');
            if ($fields = LC_Ticket::getFieldTicket($id_ticket)) {
                foreach ($fields as &$field) {
                    if (isset($field['id_download']) && $field['id_download'] > 0) {
                        $download = new LC_Download($field['id_download']);
                        $field['file_size'] = $download->file_size;
                    }
                    $field['link_download'] = $this->context->link->getModuleLink($this->module->name, 'download', array('downloadfile' => md5(_COOKIE_KEY_ . $field['id_download'])));
                }
            }
            if (isset($ticket['id_product']) && $ticket['id_product'] > 0) {
                $this->context->smarty->assign([
                    'product_ref' => new Product($ticket['id_product'], false, $this->context->language->id)
                ]);
            }
            if (isset($ticket['id_order']) && $ticket['id_order'] > 0) {
                $order = new Order($ticket['id_order']);
                $ticket['reference'] = $order->reference;
                $this->context->smarty->assign([
                    'order_ref' => new Order($ticket['id_order'])
                ]);
            }
            $this->context->smarty->assign([
                'ticket' => $ticket,
                'messages' => $messages,
                'fields' => $fields,
                'token_view_ticket' => ($token = Tools::getValue('token')) && Validate::isMd5($token) ? $token:'',
                'form_class' => $form_class,
                'has_order' => LC_Ticket::getCountOrder((int)$ticket['id_customer']),
                'load_more' => $nbMessages && $countMessages > $nbMessages ? true : false,
                'ETS_LC_AVATAR_IMAGE_TYPE' => Configuration::get('ETS_LC_AVATAR_IMAGE_TYPE'),
                'link_basic' => $this->module->getBaseLink(),
                'is_manager_ticket' => $is_manager_ticket,
                'isAdmin' => $this->process->isAdmin(),
                'has_product_expried' => Hook::exec('actionCheckLicenseExpried',array('id_customer'=>$ticket['id_customer'])),
            ]);
            return $this->context->smarty->fetch(_PS_MODULE_DIR_ . $this->module->name . '/views/templates/hook/detail_ticket.tpl');
        } else
            Tools::redirectLink($this->context->link->getModuleLink($this->module->name, 'ticket'));
    }

    public function getListTickets()
    {
        $isManager = $this->process->isManagerTicket();
        $filter = '';
        $post_value = array();
        if (($id_ticket = (int)Tools::getValue('id_ticket')) && !Tools::isSubmit('deleteticket')) {
            $filter .= ' AND fm.id_message=' . (int)$id_ticket;
            $post_value['id_ticket'] = (int)$id_ticket;
        }
        if(($replied = Tools::getValue('replied'))!='' && Validate::isInt($replied))
        {
            $filter .= ' AND fm.replied=' . (int)$replied;
            $post_value['replied'] = (int)$replied;
        }
        if (($form_title = Tools::getValue('form_title')) || $form_title != '') {
            if (Validate::isCleanHtml($form_title))
                $filter .= ' AND fl.title LIKE "%' . pSQL($form_title) . '%"';
            $post_value['form_title'] = $form_title;
        }
        if (($priority = Tools::getValue('priority')) || $priority != '') {
            if (Validate::isInt($priority))
                $filter .= ' AND fm.priority="' . (int)$priority . '"';
            $post_value['priority'] = (int)$priority;
        }
        if (($status = Tools::getValue('status')) || $status != '') {
            if (Validate::isCleanHtml($status))
                $filter .= ' AND fm.status="' . pSQL($status) . '"';
            $post_value['status'] = $status;
        }
        if (($date_add_from = Tools::getValue('date_add_from')) || $date_add_from != '') {
            if (Validate::isDate($date_add_from))
            {
                if($isManager)
                    $filter .= ' AND fm.date_customer_update >= "' . pSQL($date_add_from) . ' 00:00:00"';
                else
                    $filter .= ' AND fm.date_admin_update >= "' . pSQL($date_add_from) . ' 00:00:00"';
            }
            $post_value['date_add_from'] = $date_add_from;
        }
        if (($date_add_to = Tools::getValue('date_add_to')) || $date_add_to != '') {
            if (Validate::isDate($date_add_to))
            {
                if($isManager)
                    $filter .= ' AND fm.date_customer_update <= "' . pSQL($date_add_to) . ' 23:59:59"';
                else
                    $filter .= ' AND fm.date_admin_update <= "' . pSQL($date_add_to) . ' 23:59:59"';
            }
            $post_value['date_add_to'] = $date_add_to;
        }
        if (($subject = Tools::getValue('subject')) || $subject != '') {
            if (Validate::isCleanHtml($subject))
                $filter .= ' AND fm.subject LIKE "' . pSQL($subject) . '%"';
            $post_value['subject'] = $subject;
        }
        $sort = Tools::getValue('sort', $isManager ? 'fm.date_customer_update': 'fm.date_admin_update');
        if (!in_array($sort, array($isManager ? 'fm.date_customer_update': 'fm.date_admin_update', 'subject', 'status', 'priority', 'title', 'id_message')))
            $sort = $isManager ? 'fm.date_customer_update': 'fm.date_admin_update';
        $sort_type = Tools::strtolower(Tools::getValue('sort_type', 'desc'));
        if (!in_array($sort_type, array('desc', 'asc')))
            $sort_type = 'desc';
        $page = (int)Tools::getValue('page');
        if ($page < 1)
            $page = 1;
        $totalRecords = (int)LC_Ticket::getTickets(true, $filter);
        $paggination = new LC_paggination_class();
        $paggination->total = $totalRecords;

        $paggination->url = $this->context->link->getModuleLink($this->module->name, 'ticket', array_merge(array('page' => '_page_'), $post_value));
        $paggination->limit = 20;
        $totalPages = ceil($totalRecords / $paggination->limit);
        if ($page > $totalPages)
            $page = $totalPages;
        $paggination->page = $page;
        $start = $paggination->limit * ($page - 1);
        if ($start < 0)
            $start = 0;
        $paggination->text = $this->module->l('Showing {start} to {end} of {total} ({pages} Pages)', 'ticket');
        $paggination->style_links = 'links';
        $paggination->style_results = 'results';
        $tickets = LC_Ticket::getTickets(false, $filter, $sort, $sort_type, $paggination->limit, $start);
        $ETS_LC_DISPLAY_STAFF_IN_LIST_TICKET = (int)Configuration::get('ETS_LC_DISPLAY_STAFF_IN_LIST_TICKET');
        $ETS_LC_DISPLAY_PRODUCTS_IN_LIST_TICKET = (int)Configuration::get('ETS_LC_DISPLAY_PRODUCTS_IN_LIST_TICKET');
        $ETS_LC_DISPLAY_ORDER_IN_LIST_TICKET = (int)Configuration::get('ETS_LC_DISPLAY_ORDER_IN_LIST_TICKET');
        if ($tickets) {
            foreach ($tickets as &$ticket) {
                if ($ETS_LC_DISPLAY_STAFF_IN_LIST_TICKET && $this->process->isManagerTicket()) {
                    $ticket['staff'] = LC_Ticket::getLastStaff($ticket['id_message']);
                    if(!$ticket['staff'])
                    {
                        if($ticket['id_employee'] > 0 && ($employee = new Employee($ticket['id_employee'])) && Validate::isLoadedObject($employee) && $employee->active)
                        {
                            $id_employee = $ticket['id_employee'];
                            $ticket['staff'] = LC_Departments::getStaffByEmployee($id_employee);
                        }
                    }
                    $display_col_staff = true;
                }
                if ($ETS_LC_DISPLAY_PRODUCTS_IN_LIST_TICKET && $this->process->isManagerTicket()) {
                    if ($ticket['id_product']) {
                        $display_col_product = true;
                    }
                    $ticket['products'] = $this->module->getProductTicket($ticket['id_product']);
                }
                if (isset($ticket['reference']) && $ticket['reference'] && $ETS_LC_DISPLAY_ORDER_IN_LIST_TICKET) {
                    $display_col_order = true;
                }
            }
        }
        if ($forms = LC_Ticket_form::getForms(true,false,null,null,true,!$this->context->customer->isLogged())) {
            foreach ($forms as &$form) {
                $form['link'] = $this->module->getFormLink($form['id_form']);
            }
        }
        $this->context->smarty->assign(
            array(
                'tickets' => $tickets,
                'post_value' => $post_value,
                'link' => $this->context->link,
                'sort' => $sort,
                'sort_type' => $sort_type,
                'ETS_LC_DISPLAY_PRODUCTS_IN_LIST_TICKET' => $ETS_LC_DISPLAY_PRODUCTS_IN_LIST_TICKET,
                'ETS_LC_DISPLAY_ORDER_IN_LIST_TICKET' => $ETS_LC_DISPLAY_ORDER_IN_LIST_TICKET,
                'display_col_product' => isset($display_col_product) ? $display_col_product:false,
                'display_col_order' => isset($display_col_order) ? $display_col_order :false,
                'new_ticket_link' => count($forms) == 1 ? $forms[0]['link'] : false,
                'forms' => $forms,
                'pagination_text' => $paggination->render(),
                'totalRecords' => $totalRecords,
                'isManager'=>$isManager,
                'display_col_staff' =>isset($display_col_staff) ? $display_col_staff :false,
            )
        );
        return $this->context->smarty->fetch(_PS_MODULE_DIR_ . $this->module->name . '/views/templates/hook/list_ticket.tpl');
    }
}
