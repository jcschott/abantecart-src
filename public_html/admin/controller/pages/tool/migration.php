<?php
/*------------------------------------------------------------------------------
  $Id$

  AbanteCart, Ideal OpenSource Ecommerce Solution
  http://www.AbanteCart.com

  Copyright © 2011-2013 Belavier Commerce LLC

  This source file is subject to Open Software License (OSL 3.0)
  License details is bundled with this package in the file LICENSE.txt.
  It is also available at this URL:
  <http://www.opensource.org/licenses/OSL-3.0>

 UPGRADE NOTE:
   Do not edit or add to this file if you wish to upgrade AbanteCart to newer
   versions in the future. If you wish to customize AbanteCart for your
   needs please refer to http://www.AbanteCart.com for more information.
------------------------------------------------------------------------------*/
if (!defined('DIR_CORE') || !IS_ADMIN) {
	header('Location: static_pages/');
}
if (defined('IS_DEMO') && IS_DEMO) {
	header('Location: static_pages/demo_mode.php');
}
class ControllerPagesToolMigration extends AController {

	private $error = array();
	public $data = array();
	const MODULE_NAME = 'tool/migration';

	public function main() {

		//init controller data
		$this->extensions->hk_InitData($this, __FUNCTION__);

		$this->_setCommonVars();
		$this->model_tool_migration->clearStepData();

		//text
		$this->data['text_description'] = $this->language->get('text_description');
		$this->data['button_start_migration'] = $this->language->get('button_start_migration');

		//url
		$this->data['start_migration'] = $this->html->getSecureURL('tool/migration/step_one');

		$this->view->batchAssign($this->data);
		$this->view->assign('help_url', $this->gen_help_url());

		$this->processTemplate('pages/tool/migration/index.tpl');

		//update controller data
		$this->extensions->hk_UpdateData($this, __FUNCTION__);
	}

	public function step_one() {

		//init controller data
		$this->extensions->hk_InitData($this, __FUNCTION__);

		$this->_setCommonVars('heading_title_step_one');
		if (!$this->_validateAccess()) {
			$this->redirect($this->html->getSecureURL('tool/migration'));
		}

		$errors = array(
			'warning',
			'db_host',
			'db_user',
			'db_name',
			'cart_type',
			'cart_url',
		);

		$formData = array(
			'cart_type',
			'cart_url',
			'db_host',
			'db_user',
			'db_name',
			'db_password',
			'db_prefix',
		);

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && ($this->_validateStepOne())) {
			$this->model_tool_migration->saveStepData($formData);
			$this->redirect($this->html->getSecureURL('tool/migration/step_two'));
		}

		$this->data['cancel'] = $this->html->getSecureURL('tool/migration');

		// form
		$form = new AForm('ST');
		$form->setForm(
			array('form_name' => 'migrationFrm')
		);
		$this->data['form']['form_open'] = $form->getFieldHtml(array('type' => 'form',
			'name' => 'migrationFrm',
			'action' => $this->html->getSecureURL('tool/migration/step_one')));

		$cart_types = array_merge(array('' => $this->language->get('entry_cart_select')),
		$this->model_tool_migration->getCartList());

		$this->data['form']['button_continue'] = $form->getFieldHtml(array(
			'type' => 'button',
			'name' => 'submit',
			'text' => $this->language->get('button_continue'),
			'style' => 'button1',
		));
		$this->data['form']['button_cancel'] = $form->getFieldHtml(array(
			'type' => 'button',
			'name' => 'cancel',
			'text' => $this->language->get('button_cancel'),
			'style' => 'button2',
		));

		$this->data['form']['cart_type'] = $form->getFieldHtml(array('type' => 'selectbox',
			'name' => 'cart_type',
			'required' => true,
			'options' => $cart_types,
		));


		$this->data['form']['cart_url'] = $form->getFieldHtml(array('type' => 'input',
			'name' => 'cart_url',
			'required' => true,
			'value' => $this->request->post['cart_url'],
		));
		$this->data['form']['db_host'] = $form->getFieldHtml(array('type' => 'input',
			'name' => 'db_host',
			'required' => true,
			'value' => $this->request->post['db_host'],
		));
		$this->data['form']['db_user'] = $form->getFieldHtml(array('type' => 'input',
			'name' => 'db_user',
			'required' => true,
			'value' => $this->request->post['db_user'],
		));
		$this->data['form']['db_password'] = $form->getFieldHtml(array('type' => 'input',
			'name' => 'db_password',
			'value' => $this->request->post['db_password'],
		));
		$this->data['form']['db_name'] = $form->getFieldHtml(array('type' => 'input',
			'name' => 'db_name',
			'required' => true,
			'value' => $this->request->post['db_name'],
		));
		$this->data['form']['db_prefix'] = $form->getFieldHtml(array('type' => 'input',
			'name' => 'db_prefix',
			'value' => $this->request->post['db_prefix'],
		));


		$source = ($this->request->server['REQUEST_METHOD'] == 'POST') ? $this->request->post : $this->session->data['migration'];
		$this->_setFormData($formData, $source);
		$this->_setErrors($errors);

		$this->view->batchAssign($this->data);
		$this->view->assign('help_url', $this->gen_help_url('step_one'));

		$this->processTemplate('pages/tool/migration/step_one.tpl');

		//update controller data
		$this->extensions->hk_UpdateData($this, __FUNCTION__);
	}

	public function step_two() {
		//init controller data
		$this->extensions->hk_InitData($this, __FUNCTION__);
		$this->loadLanguage('common/header');

		$this->_setCommonVars('heading_title_step_two');
		if (!$this->_validateAccess() || !$this->model_tool_migration->isStepData()) {
			$this->redirect($this->html->getSecureURL('tool/migration'));
		}

		$errors = array(
			'warning',
			'migrate_data',
		);

		$formData = array(
			'migrate_products',
			'migrate_customers',
			'migrate_orders',
			'erase_existing_data',
		);

		$this->data['counts'] = $this->model_tool_migration->getCounts();

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && ($this->_validateStepTwo())) {
			$this->model_tool_migration->saveStepData($formData);
			$this->redirect($this->html->getSecureURL('tool/migration/step_three'));
		}

		// form
		$form = new AForm('ST');
		$form->setForm(
			array('form_name' => 'migrationFrm')
		);
		$this->data['form']['form_open'] = $form->getFieldHtml(array('type' => 'form',
			'name' => 'migrationFrm',
			'action' => $this->html->getSecureURL('tool/migration/step_two')));

		$this->data['form']['button_continue'] = $form->getFieldHtml(array(
			'type' => 'button',
			'name' => 'submit',
			'text' => $this->language->get('button_continue'),
			'style' => 'button1',
		));
		$this->data['form']['button_cancel'] = $form->getFieldHtml(array(
			'type' => 'button',
			'name' => 'cancel',
			'text' => $this->language->get('button_cancel'),
			'style' => 'button2',
		));
		$this->data['form']['button_back'] = $form->getFieldHtml(array(
			'type' => 'button',
			'name' => 'cancel',
			'text' => $this->language->get('button_back'),
			'style' => 'button',
		));




		$this->data['form']['migrate_products'] = $form->getFieldHtml(array(
			'type' => 'checkbox',
			'value' => '1',
			'checked' => false,
			'name' => 'migrate_products'
		));
		$this->data['form']['migrate_products_text'] = $this->language->get('text_product')." (%s)\n".$this->language->get('text_category')." (%s)\n".$this->language->get('text_manufacturer')." (%s)\n";

		$this->data['form']['migrate_customers'] = $form->getFieldHtml(array(
			'type' => 'checkbox',
			'value' => '1',
			'checked' => false,
			'name' => 'migrate_customers'
		));
		$this->data['form']['migrate_customers_text'] = $this->language->get('text_customer')." (%s)";

		$this->data['form']['erase_existing_data'] = $form->getFieldHtml(array(
			'type' => 'checkbox',
			'value' => '1',
			'checked' => false,
			'name' => 'erase_existing_data'
		));

		$this->data['cancel'] = $this->html->getSecureURL('tool/migration');
		$this->data['back'] = $this->html->getSecureURL('tool/migration/step_one');

		$source = ($this->request->server['REQUEST_METHOD'] == 'POST') ? $this->request->post : $this->session->data['migration'];
		$this->_setFormData($formData, $source);
		$this->_setErrors($errors);

		$this->view->batchAssign($this->data);
		$this->view->assign('help_url', $this->gen_help_url('step_two'));

		$this->processTemplate('pages/tool/migration/step_two.tpl');

		//update controller data
		$this->extensions->hk_UpdateData($this, __FUNCTION__);
	}

	public function step_three() {
		//init controller data
		$this->extensions->hk_InitData($this, __FUNCTION__);

		$this->_setCommonVars('heading_title_step_three');
		if (!$this->_validateAccess() || !$this->model_tool_migration->isStepData()) {
			$this->redirect($this->html->getSecureURL('tool/migration'));
		}

		$this->data['result'] = $this->model_tool_migration->run();
		$this->data['button_continue'] = $this->language->get('button_continue');
		$this->data['continue'] = $this->html->getSecureURL('tool/migration');

		$this->view->batchAssign($this->data);
		$this->view->assign('help_url', $this->gen_help_url('step_three'));

		$this->processTemplate('pages/tool/migration/step_three.tpl');

		//update controller data
		$this->extensions->hk_UpdateData($this, __FUNCTION__);

	}

	private function _setCommonVars($heading_title = '') {
		$this->loadLanguage(self::MODULE_NAME);

		$this->document->setTitle($this->language->get('heading_title'));

		$this->data['heading_title'] = $this->language->get('heading_title');
		if (!empty($heading_title))
			$this->data['heading_title'] .= $this->language->get($heading_title);

		if (isset($this->session->data['success'])) {
			$this->data['success'] = $this->session->data['success'];
			unset($this->session->data['success']);
		} else {
			$this->data['success'] = '';
		}

		if (isset($this->session->data['warning'])) {
			$this->data['error_warning'] = $this->session->data['warning'];
			unset($this->session->data['warning']);
		} else {
			$this->data['error_warning'] = '';
		}

		$this->document->resetBreadcrumbs();
		$this->document->addBreadcrumb(array(
			'href' => $this->html->getSecureURL('index/home'),
			'text' => $this->language->get('text_home'),
			'separator' => FALSE
		));
		$this->document->addBreadcrumb(array(
			'href' => $this->html->getSecureURL('tool/migration'),
			'text' => $this->language->get('heading_title'),
			'separator' => ' :: '
		));
	}

	private function _setErrors($errors) {
		if (empty($errors)) return;
		foreach ($errors as $err) {
			$this->data['error_' . $err] = isset($this->error[$err]) ? $this->error[$err] : '';
		}
	}

	private function _setFormData($data, $source) {
		if (empty($data)) return;
		foreach ($data as $field) {
			$this->data[$field] = isset($source[$field]) ? html_entity_decode($source[$field]) : '';
		}
	}

	private function _validateAccess() {
		if (!$this->user->canModify(self::MODULE_NAME)) {
			$this->session->data['warning'] = $this->language->get('error_permission');
			$this->error = TRUE;
		}

		if (!$this->error) {
			return TRUE;
		} else {
			return FALSE;
		}
	}

	private function _validateStepOne() {
		if (!$this->request->post['cart_type']) {
			$this->error['cart_type'] = $this->language->get('error_cart_type');
		}
		if (!$this->error && !$this->model_tool_migration->isCartSupported($this->request->post['cart_type'])) {
			$this->error['warning'] = $this->language->get('error_cart_not_supported');
		}

		if (strpos($this->request->post['cart_url'], 'http') === FALSE && $this->request->post['cart_url']) {
			$this->request->post['cart_url'] = 'http://' . $this->request->post['cart_url'];
		}

		if (!$this->request->post['cart_url']) {
			$this->error['cart_url'] = $this->language->get('error_cart_url');
		}
		if (!$this->request->post['db_host']) {
			$this->error['db_host'] = $this->language->get('error_db_host');
		}
		if (!$this->request->post['db_user']) {
			$this->error['db_user'] = $this->language->get('error_db_user');
		}
		if (!$this->request->post['db_name']) {
			$this->error['db_name'] = $this->language->get('error_db_name');
		}

/*		if(!$this->error && !array_sum($this->data['counts'])){
			$this->data['counts'] = $this->model_tool_migration->getCounts();
			$this->error['warning'] = $this->language->get('error_empty_source_cart');
		}*/

		if (!$this->error)
			if (!$connection = @mysql_connect($this->request->post['db_host'], $this->request->post['db_user'], $this->request->post['db_password'])) {
				$this->error['warning'] = $this->language->get('error_db_connection');
			} else {
				if (!@mysql_select_db($this->request->post['db_name'], $connection)) {
					$this->error['warning'] = $this->language->get('error_db_not_exist');
					mysql_close($connection);
				}

			}

		if (!$this->error) {
			return TRUE;
		} else {
			return FALSE;
		}
	}

	private function _validateStepTwo() {
		if (	(
				empty($this->request->post['migrate_products']) &&
				empty($this->request->post['migrate_customers']) &&
				empty($this->request->post['migrate_orders'])
				)
			|| !array_sum($this->data['counts'])
		) {
			$this->error['migrate_data'] = $this->language->get('error_migrate_data');
		}

		if (!$this->error) {
			return TRUE;
		} else {
			return FALSE;
		}
	}
}