<?php
/**
* 2007-2014 PrestaShop
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
*  @author    Raphaël SA <raphael.miw@gmail.com>
*  @copyright 2015-2015 miw SA
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/

if (!defined('_PS_VERSION_'))
	exit;

class MiwHome extends Module
{

	public function __construct()
	{
		$this->name = 'miwhome';
		$this->tab = 'front_office_features';
		$this->version = '1.0.0';
		$this->author = 'RaphRaph';
		$this->need_instance = 0;

		$this->bootstrap = false;
		parent::__construct();

		$this->displayName = $this->l('blocs homepage');
		$this->description = $this->l('Gestion des blocs sur la page d\'accueil.');
	}

	public function install()
	{
//		$this->_clearCache('*');
		Configuration::updateValue('MYHOME_NB_BLOCS', 3);

		$installOK = parent::install()
			&& $this->registerHook('header')
			&& $this->registerHook('displayHomeTab')
			&& $this->registerHook('displayHomeTabContent');

		if ($installOK)
			return false;

		return true;
	}

	/*public function uninstall()
	{
		return parent::uninstall();
	}*/

	public function getContent()
	{
		// 1 - gestion des actions
		$output = '';
		if (Tools::isSubmit('submitMyHome'))
			$this->sauveConfiguration($output);

		// 2 - rendu du formulaire de configuration
		return $output.$this->renderForm();
	}

	public function sauveConfiguration(&$output)
	{
		$errors = array();

		// a - contrôle des valeurs passées
		$nbr = Tools::getValue('MYHOME_NB_BLOCS');
		if (!Validate::isInt($nbr) || $nbr <= 0)
			$errors[] = $this->l('The number of products is invalid. Please enter a positive number.');

		// b - affichage du résultat du traitement des actions
		if (isset($errors) && count($errors))
			$output = $this->displayError(implode('<br />', $errors));
		else
		{
			Configuration::updateValue('MYHOME_NB_BLOCS', (int)$nbr);
			Tools::clearCache(Context::getContext()->smarty, $this->getTemplatePath('homefeatured.tpl'));
			$output = $this->displayConfirmation($this->l('Your settings have been updated.'));
		}

	}

	public function hookDisplayHeader($params)
	{
		echo '<br>hookDisplayHeader';
		$this->hookHeader($params);
	}

	public function hookHeader($params)
	{
		echo '<br>hookDisplayHomeTab';
		var_dump($params);
		// uniquement sur la homepage
		if (isset($this->context->controller->php_self) && $this->context->controller->php_self == 'index')
			$this->context->controller->addCSS(_THEME_CSS_DIR_.'product_list.css');

		// partout
		$this->context->controller->addCSS(($this->_path).'homefeatured.css', 'all');
	}

	public function hookDisplayHomeTab($params)
	{
		echo '<br>hookDisplayHomeTab';
		var_dump($params);
		return 'hookDisplayHomeTab';
//		if (!$this->isCached('tab.tpl', $this->getCacheId('homefeatured-tab')))
//			$this->_cacheProducts();
//
//		return $this->display(__FILE__, 'tab.tpl', $this->getCacheId('homefeatured-tab'));
	}

	public function hookDisplayHome($params)
	{
		echo '<br>hookDisplayHome';
		var_dump($params);
		if (!$this->isCached('homefeatured.tpl', $this->getCacheId()))
		{
			$this->_cacheProducts();
			$this->smarty->assign(
				array(
					'products' => HomeFeatured::$cache_products,
					'add_prod_display' => Configuration::get('PS_ATTRIBUTE_CATEGORY_DISPLAY'),
					'homeSize' => Image::getSize(ImageType::getFormatedName('home')),
				)
			);
		}

//		return $this->getCacheId();
		return $this->display(__FILE__, 'homefeatured.tpl', $this->getCacheId());
	}

	public function hookDisplayHomeTabContent($params)
	{
		echo '<br>hookDisplayHomeTabContent';
		var_dump($params);
		return $this->hookDisplayHome($params);
	}

	public function renderForm()
	{
		$fields_form = array(
			'form' => array(
				'legend' => array(
					'title' => $this->l('Settings'),
					'icon' => 'icon-cogs'
				),
				'description' => $this->l('Configurez la homepage'),
				'input' => array(
					array(
						'type' => 'text',
						'label' => $this->l('Number of blocs to be displayed'),
						'name' => 'MYHOME_NB_BLOCS',
						'class' => 'fixed-width-xs',
						'desc' => $this->l('Set the number of products that you would like to display on homepage (default: 8).'),
					),
					/*array(
						'type' => 'switch',
						'label' => $this->l('Randomly display featured products'),
						'name' => 'HOME_FEATURED_RANDOMIZE',
						'class' => 'fixed-width-xs',
						'desc' => $this->l('Enable if you wish the products to be displayed randomly (default: no).'),
						'values' => array(
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
						),
					),*/
				),
				'submit' => array(
					'title' => $this->l('Save'),
				)
			),
		);

		$helper = new HelperForm();
		$helper->show_toolbar = false;
		$helper->table = $this->table;
		$lang = new Language((int)Configuration::get('PS_LANG_DEFAULT'));
		$helper->default_form_language = $lang->id;
		$helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') ? Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') : 0;
		$this->fields_form = array();
		$helper->id = (int)Tools::getValue('id_carrier');	// ??
		$helper->identifier = $this->identifier;
		$helper->submit_action = 'submitMyHome';
		$helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false).'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
		$helper->token = Tools::getAdminTokenLite('AdminModules');
		$helper->tpl_vars = array(
			'fields_value' => $this->getConfigFieldsValues(),
			'languages' => $this->context->controller->getLanguages(),
			'id_language' => $this->context->language->id
		);

		return $helper->generateForm(array($fields_form));
	}

	public function getConfigFieldsValues()
	{
		return array(
			'MYHOME_NB_BLOCS' => Tools::getValue('MYHOME_NB_BLOCS', (int)Configuration::get('MYHOME_NB_BLOCS'))
		);
	}
}
