<?php
/**
* 2007-2019 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author    PrestaShop SA <contact@prestashop.com>
*  @copyright 2007-2019 PrestaShop SA
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/

if (!defined('_PS_VERSION_')) {
    exit;
}

class Pstwittercard extends Module
{
    protected $config_form = false;

    public function __construct()
    {
        $this->name = 'pstwittercard';
        $this->tab = 'social_networks';
        $this->version = '1.0.0';
        $this->author = 'Saeed Sattar Beglou';
        $this->need_instance = 0;

        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Prestashop Twitter Cards');
        $this->description = $this->l('This module will allow you to promote your﻿ site and your products on Twitter using﻿ Twitter cards﻿﻿﻿﻿.');

        $this->ps_versions_compliancy = array('min' => '1.6', 'max' => _PS_VERSION_);
    }

    public function install()
    {
        Configuration::updateValue('PSTWITTERCARD_USERNAME', null);

        return parent::install() &&
            $this->registerHook('header');
    }

    public function uninstall()
    {
        Configuration::deleteByName('PSTWITTERCARD_USERNAME');

        return parent::uninstall();
    }

    /**
     * Load the configuration form
     */
    public function getContent()
    {
        $output = '';

        if (((bool)Tools::isSubmit('submitPstwittercardModule')) == true) {
            $this->postProcess();
            $output .= $this->displayConfirmation($this->l('Settings updated'));
        }

        $this->context->smarty->assign('module_dir', $this->_path);

        $output .= $this->context->smarty->fetch($this->local_path.'views/templates/admin/configure.tpl');

        return $output.$this->renderForm();
    }

    /**
     * Create the form that will be displayed in the configuration of your module.
     */
    protected function renderForm()
    {
        $helper = new HelperForm();

        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);

        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitPstwittercardModule';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            .'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        $helper->tpl_vars = array(
            'fields_value' => $this->getConfigFormValues(), /* Add values for your inputs */
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        );

        return $helper->generateForm(array($this->getConfigForm()));
    }

    /**
     * Create the structure of your form.
     */
    protected function getConfigForm()
    {
        return array(
            'form' => array(
                'legend' => array(
                'title' => $this->l('Settings'),
                'icon' => 'icon-cogs',
                ),
                'input' => array(
                    array(
                        'col' => 3,
                        'type' => 'text',
                        'prefix' => '<i class="icon icon-twitter"></i>',
                        'desc' => $this->l('Enter a valid Twitter Username'),
                        'name' => 'PSTWITTERCARD_USERNAME',
                        'label' => $this->l('Username'),
                    ),
                ),
                'submit' => array(
                    'title' => $this->l('Save'),
                ),
            ),
        );
    }

    /**
     * Set values for the inputs.
     */
    protected function getConfigFormValues()
    {
        return array(
            'PSTWITTERCARD_USERNAME' => Configuration::get('PSTWITTERCARD_USERNAME'),
        );
    }

    /**
     * Save form data.
     */
    protected function postProcess()
    {
        $form_values = $this->getConfigFormValues();

        foreach (array_keys($form_values) as $key) {
            Configuration::updateValue($key, Tools::getValue($key));
        }
    }

    public function hookHeader()
    {
        $current_page = get_class($this->context->controller);

        if ($current_page == "ProductController") {
            if ($id_product = (int)Tools::getValue('id_product')) {
                $product = new Product($id_product, true, $this->context->language->id, $this->context->shop->id);
            }

            $image = Product::getCover($id_product);
            $protocol_link = @$_SERVER['HTTPS'] == "on"?"https://":"http://";

            if (is_array($image) && sizeof($image) == 1) {
                $link = new Link;
                $imagePath = $protocol_link . $link->getImageLink($product->link_rewrite, $image['id_image'], ImageType::getFormattedName('home'));
            }

            $this->context->smarty->assign(array(
                'twitter_site' => Configuration::get('PSTWITTERCARD_USERNAME'),
                'twitter_product_name' => $product->name,
                'twitter_description' => $product->description_short,
                'twitter_currency' => $this->context->currency->sign,
                'twitter_image' => $imagePath,
                'twitter_price' => $product->price
            ));
            return $this->display(__FILE__, 'views/templates/hook/pstwittercard_product.tpl');
        } else {
            $meta = Meta::getMetaTags($this->context->language->id, 'index');
            $this->context->smarty->assign(array(
                'twitter_site' => Configuration::get('PSTWITTERCARD_USERNAME'),
                'twitter_hometitle' => $meta['meta_title'],
                'twitter_homedesc' => $meta['meta_description'],
                'twitter_logo' => _PS_BASE_URL_._PS_IMG_.Configuration::get('PS_LOGO')
            ));
            return $this->display(__FILE__, 'views/templates/hook/pstwittercard_index.tpl');
        }
    }
}
