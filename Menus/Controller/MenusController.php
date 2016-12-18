<?php

App::uses('MenusAppController', 'Menus.Controller');

/**
 * Menus Controller
 *
 * @category Controller
 * @package  Croogo.Menus.Controller
 * @version  1.0
 * @author   Fahad Ibnay Heylaal <contact@fahad19.com>
 * @license  http://www.opensource.org/licenses/mit-license.php The MIT License
 * @link     http://www.croogo.org
 */
class MenusController extends MenusAppController {

/**
 * Controller name
 *
 * @var string
 * @access public
 */
	public $name = 'Menus';

/**
 * Models used by the Controller
 *
 * @var array
 * @access public
 */
	public $uses = array('Menus.Menu');

/**
 * afterConstruct
 */
	public function afterConstruct() {
		parent::afterConstruct();
		$this->_setupAclComponent();
	}

/**
 * beforeFilter
 *
 */
	public function beforeFilter() {
		parent::beforeFilter();
		$this->Security->unlockedActions[] = 'admin_toggle';
	}

/**
 * Toggle Link status
 *
 * @param $id string Link id
 * @param $status integer Current Link status
 * @return void
 */
	public function admin_toggle($id = null, $status = null) {
		$this->Croogo->fieldToggle($this->Menu, $id, $status);
	}

/**
 * Admin index
 *
 * @return void
 * @access public
 */
	public function admin_index() {
		$this->set('title_for_layout', __d('croogo', 'Menus'));

		$this->Menu->recursive = 0;
		$this->paginate['Menu']['order'] = 'Menu.id ASC';
		$this->set('menus', $this->paginate());
	}

/**
 * Admin add
 *
 * @return void
 * @access public
 */
	public function admin_add() {
		$this->set('title_for_layout', __d('croogo', 'Add Menu'));

		if (!empty($this->request->data)) {
			$this->Menu->create();
			$this->request->data['Menu']['title'] = htmlentities($this->request->data['Menu']['title']);
			$this->request->data['Menu']['alias'] = htmlentities($this->request->data['Menu']['alias']);
			$this->request->data['Menu']['description'] = htmlentities($this->request->data['Menu']['description']);
			if ($this->Menu->save($this->request->data)) {
				$this->Session->setFlash(__d('croogo', 'The Menu has been saved'), 'flash', array('class' => 'success'));
				$this->Croogo->redirect(array('action' => 'edit', $this->Menu->id));
			} else {
				$this->Session->setFlash(__d('croogo', 'The Menu could not be saved. Please, try again.'), 'flash', array('class' => 'error'));
			}
		}
	}

/**
 * Admin edit
 *
 * @param integer $id
 * @return void
 * @access public
 */
	public function admin_edit($id = null) {
		$this->set('title_for_layout', __d('croogo', 'Edit Menu'));

		if (!$id && empty($this->request->data)) {
			$this->Session->setFlash(__d('croogo', 'Invalid Menu'), 'flash', array('class' => 'error'));
			return $this->redirect(array('action' => 'index'));
		}
		if (!empty($this->request->data)) {
			if ($this->Menu->save($this->request->data)) {
				$this->request->data['Menu']['title'] = htmlentities($this->request->data['Menu']['title']);
				$this->request->data['Menu']['alias'] = htmlentities($this->request->data['Menu']['alias']);
				$this->request->data['Menu']['description'] = htmlentities($this->request->data['Menu']['description']);
				$this->Session->setFlash(__d('croogo', 'The Menu has been saved'), 'flash', array('class' => 'success'));
				$this->Croogo->redirect(array('action' => 'edit', $this->Menu->id));
			} else {
				$this->Session->setFlash(__d('croogo', 'The Menu could not be saved. Please, try again.'), 'flash', array('class' => 'error'));
			}
		}
		if (empty($this->request->data)) {
			$this->request->data = $this->Menu->read(null, $id);
		}
	}

/**
 * Admin delete
 *
 * @param integer $id
 * @return void
 * @access public
 */
	public function admin_delete($id = null) {
		if (!$id) {
			$this->Session->setFlash(__d('croogo', 'Invalid id for Menu'), 'flash', array('class' => 'error'));
			return $this->redirect(array('action' => 'index'));
		}
		if ($this->Menu->delete($id)) {
			$this->Session->setFlash(__d('croogo', 'Menu deleted'), 'flash', array('class' => 'success'));
			return $this->redirect(array('action' => 'index'));
		}
	}

}
