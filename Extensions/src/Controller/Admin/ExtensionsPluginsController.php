<?php

namespace Croogo\Extensions\Controller\Admin;

use Cake\Event\Event;
use Croogo\Extensions\CroogoPlugin;
use Croogo\Extensions\ExtensionsInstaller;
use Cake\Core\Exception\Exception;

/**
 * Extensions Plugins Controller
 *
 * @category Controller
 * @package  Croogo.Extensions.Controller
 * @version  1.0
 * @author   Fahad Ibnay Heylaal <contact@fahad19.com>
 * @license  http://www.opensource.org/licenses/mit-license.php The MIT License
 * @link     http://www.croogo.org
 */
class ExtensionsPluginsController extends AppController {

/**
 * Controller name
 *
 * @var string
 * @access public
 */
	public $name = 'ExtensionsPlugins';

/**
 * Models used by the Controller
 *
 * @var array
 * @access public
 */
	public $uses = array(
		'Croogo/Settings.Setting',
		'Croogo/Users.User',
	);

/**
 * BC compatibility
 */
	public function __get($name) {
		if ($name == 'corePlugins') {
			return $this->_CroogoPlugin->corePlugins;
		}
	}

/**
 * beforeFilter
 *
 * @return void
 */
	public function beforeFilter(Event $event) {
		parent::beforeFilter($event);

		$this->_CroogoPlugin = new CroogoPlugin();
		$this->_CroogoPlugin->setController($this);
	}

/**
 * Admin index
 *
 * @return void
 */
	public function index() {
		$this->set('title_for_layout', __d('croogo', 'Plugins'));

		$plugins = $this->_CroogoPlugin->plugins();
		$this->set('corePlugins', $this->_CroogoPlugin->corePlugins);
		$this->set('bundledPlugins', $this->_CroogoPlugin->bundledPlugins);
		$this->set(compact('plugins'));
	}

/**
 * Admin add
 *
 * @return void
 */
	public function add() {
		$this->set('title_for_layout', __d('croogo', 'Upload a new plugin'));

		if (!empty($this->request->data)) {
			$file = $this->request->data['Plugin']['file'];
			unset($this->request->data['Plugin']['file']);

			$Installer = new ExtensionsInstaller;
			try {
				$Installer->extractPlugin($file['tmp_name']);
			} catch (CakeException $e) {
				$this->Session->setFlash($e->getMessage(), 'flash', array('class' => 'error'));
				return $this->redirect(array('action' => 'add'));
			}
			return $this->redirect(array('action' => 'index'));
		}
	}

/**
 * Admin delete
 *
 * @param string $plugin
 * @return void
 */
	public function delete($plugin = null) {
		if (!$plugin) {
			$this->Session->setFlash(__d('croogo', 'Invalid plugin'), 'flash', array('class' => 'error'));
			return $this->redirect(array('action' => 'index'));
		}
		if ($this->_CroogoPlugin->isActive($plugin)) {
			$this->Session->setFlash(__d('croogo', 'You cannot delete a plugin that is currently active.'), 'flash', array('class' => 'error'));
			return $this->redirect(array('action' => 'index'));
		}

		$result = $this->_CroogoPlugin->delete($plugin);
		if ($result === true) {
			$this->Session->setFlash(__d('croogo', 'Plugin "%s" deleted successfully.', $plugin), 'flash', array('class' => 'success'));
		} elseif (!empty($result[0])) {
			$this->Session->setFlash($result[0], 'flash', array('class' => 'error'));
		} else {
			$this->Session->setFlash(__d('croogo', 'Plugin could not be deleted.'), 'flash', array('class' => 'error'));
		}

		return $this->redirect(array('action' => 'index'));
	}

/**
 * Admin toggle
 *
 * @param string $plugin
 * @return void
 */
	public function toggle($plugin = null) {
		if (!$plugin) {
			$this->Session->setFlash(__d('croogo', 'Invalid plugin'), 'flash', array('class' => 'error'));
			return $this->redirect(array('action' => 'index'));
		}

		if ($this->_CroogoPlugin->isActive($plugin)) {
			$usedBy = $this->_CroogoPlugin->usedBy($plugin);
			if ($usedBy !== false) {
				$this->Session->setFlash(__d('croogo', 'Plugin "%s" could not be deactivated since "%s" depends on it.', $plugin, implode(', ', $usedBy)), 'flash', array('class' => 'error'));
				return $this->redirect(array('action' => 'index'));
			}
			$result = $this->_CroogoPlugin->deactivate($plugin);
			if ($result === true) {
				$this->Session->setFlash(__d('croogo', 'Plugin "%s" deactivated successfully.', $plugin), 'flash', array('class' => 'success'));
			} elseif (is_string($result)) {
				$this->Session->setFlash($result, 'flash', array('class' => 'error'));
			} else {
				$this->Session->setFlash(__d('croogo', 'Plugin could not be deactivated. Please, try again.'), 'flash', array('class' => 'error'));
			}
		} else {
			$result = $this->_CroogoPlugin->activate($plugin);
			if ($result === true) {
				$this->Session->setFlash(__d('croogo', 'Plugin "%s" activated successfully.', $plugin), 'flash', array('class' => 'success'));
			} elseif (is_string($result)) {
				$this->Session->setFlash($result, 'flash', array('class' => 'error'));
			} else {
				$this->Session->setFlash(__d('croogo', 'Plugin could not be activated. Please, try again.'), 'flash', array('class' => 'error'));
			}
		}
		return $this->redirect(array('action' => 'index'));
	}

/**
 * Migrate a plugin (database)
 *
 * @param type $plugin
 */
	public function migrate($plugin = null) {
		if (!$plugin) {
			$this->Session->setFlash(__d('croogo', 'Invalid plugin'), 'flash', array('class' => 'error'));
		} elseif ($this->_CroogoPlugin->migrate($plugin)) {
			$this->Session->setFlash(__d('croogo', 'Plugin "%s" migrated successfully.', $plugin), 'flash', array('class' => 'success'));
		} else {
			$this->Session->setFlash(
				__d('croogo', 'Plugin "%s" could not be migrated. Error: %s', $plugin, implode('<br />', $this->_CroogoPlugin->migrationErrors)),
				'flash',
				array('class' => 'success')
			);
		}
		return $this->redirect(array('action' => 'index'));
	}

/**
 * Move up a plugin in bootstrap order
 *
 * @param string $plugin
 * @throws CakeException
 */
	public function moveup($plugin = null) {
		$this->request->allowMethod('post');

		if ($plugin === null) {
			throw new Exception(__d('croogo', 'Invalid plugin'));
		}

		$class = 'success';
		$result = $this->_CroogoPlugin->move('up', $plugin);
		if ($result === true) {
			$message = __d('croogo', 'Plugin %s has been moved up', $plugin);
		} else {
			$message = $result;
			$class = 'error';
		}
		$this->Session->setFlash($message, 'flash', array('class' => $class));

		return $this->redirect($this->referer());
	}

/**
 * Move down a plugin in bootstrap order
 *
 * @param string $plugin
 * @throws CakeException
 */
	public function movedown($plugin = null) {
		$this->request->allowMethod('post');

		if ($plugin === null) {
			throw new Exception(__d('croogo', 'Invalid plugin'));
		}

		$class = 'success';
		$result = $this->_CroogoPlugin->move('down', $plugin);
		if ($result === true) {
			$message = __d('croogo', 'Plugin %s has been moved down', $plugin);
		} else {
			$message = $result;
			$class = 'error';
		}
		$this->Session->setFlash($message, 'flash', array('class' => $class));

		return $this->redirect($this->referer());
	}

}