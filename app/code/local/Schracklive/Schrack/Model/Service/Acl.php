<?php

class Schracklive_Schrack_Model_Service_Acl extends Mage_Core_Model_Abstract {

	protected $acl = null;
	protected $roles = array();
	protected $resources = array();
	protected $connection = null;

	function __construct() {
		$this->acl = new Zend_Acl();
		$this->connection = Mage::getSingleton('core/resource')->getConnection('core_read');
		$this->_initRoles();
		$this->_initResources();
		$this->_initPrivileges();
	}

	public function isAllowed($roleName, $resourceName, $privilege) {
		return $this->acl->isAllowed($roleName, $resourceName, $privilege);
	}

	/**
	 * @param int $roleId
	 * @return string|null
	 * @todo return a model instead of a plain string
	 */
	public function getRoleById($roleId) {
		if (array_key_exists($roleId, $this->roles)) {
			return $this->roles[$roleId]['name'];
		} else {
			return null;
		}
	}

	protected function _initRoles() {
		$roles = $this->_getAllRoles();

		foreach ($roles as $role) {
			if ($role['parent_id'] > 0) {
				if ($role['parent_id'] == $role['id']) {
					throw Mage::exception('Schracklive_Schrack', 'roles cannot be a parent of itself: '.$role['id']);
				}

				$parentName = $roles[$role['parent_id']]['name'];

				try {
					$parent = $this->acl->getRole($parentName);
				} catch (Exception $exc) {
					Mage::log('Cannot find parent of role '.$parentName);
				}

				if (is_null($parent)) {
					// if parent hasn't been created in memory, do so
					$parent = new Zend_Acl_Role($parentName);
					$this->acl->addRole($parent);
					// unset parents
					unset($roles[$role['parent_id']]);
				}

				$this->acl->addRole(new Zend_Acl_Role($role['name']), $parent);
			} else {
				// only needs to be done if it doesn't exist
				if (!$this->acl->hasRole($role['name'])) {
					$this->acl->addRole(new Zend_Acl_Role($role['name']));
				}
			}
		}

	}

	protected function _initResources() {
		$resources = $this->_getAllResources();

		$createdIds = array();

		foreach ($resources as $resource) {
			if ($resource['parent_id'] > 0) {

				if ($resource['parent_id'] == $resource['id']) {
					throw Mage::exception('Schracklive_Schrack', 'resource cannot be a parent of itself: '.$resource['id']);
				}

				$parentName = $resources[$resource['parent_id']]['name'];

				try {
					$parent = $this->acl->get($parentName);
				} catch (Exception $exc) {

					$parent = new Zend_Acl_Resource($parentName);
					$createdIds[] = $resource['parent_id'];
					$this->acl->addResource($parent);

					// remove from array
					unset($resources[$resource['parent_id']]);
				}
				if (!in_array($resource['id'], $createdIds)) {
					$createdIds[] = $resource['id'];
					$this->acl->addResource(new Zend_Acl_Resource($resource['name']), $parent);
				}
			} else {
				if (!$this->acl->has($resource['name'])) {
					$createdIds[] = $resource['id'];
					$this->acl->addResource(new Zend_Acl_Resource($resource['name']));
				}
			}
		}
	}

	protected function _getAllResources() {
		if (!$this->resources) {

			$sql = 'SELECT * FROM acl_resources order by parent_id  ';
			$resources = array();

			foreach ($this->connection->fetchAll($sql) as $row) {
				$resources[$row['id']] = $row;
			}
			$this->resources = $resources;
		}
		return $this->resources;
	}

	protected function _getAllRoles() {
		if (!$this->roles) {

			$sql = 'SELECT * FROM acl_roles order by parent_id ';
			$roles = array();

			foreach ($this->connection->fetchAll($sql) as $row) {
				$roles[$row['id']] = $row;
			}
			$this->roles = $roles;
		}
		return $this->roles;
	}

	protected function _getAllRolesResourcesCombinations() {
		$sql = 'SELECT * FROM acl_roles_resources';
		$rolesRessourceCombination = array();

		foreach ($this->connection->fetchAll($sql) as $row) {
			$rolesRessourceCombination[$row['id']] = $row;
		}
		return $rolesRessourceCombination;
	}

	protected function _initPrivileges() {
		$privileges = $this->_getAllRolesResourcesCombinations();
		$allResources = $this->_getAllResources();
		$allRoles = $this->_getAllRoles();

		foreach ($privileges as $privilege) {
			// make sure role and resource are valid
			if ($privilege['acl_role_id'] > 0 && $privilege['acl_resource_id'] > 0) {

				$roleName = $allRoles[$privilege['acl_role_id']]['name'];
				$resourceName = $allResources[$privilege['acl_resource_id']]['name'];

				// special sign to grant all privileges
				if (trim($privilege['privilege']) == '*') {
					$this->acl->allow($roleName, $resourceName);
				} else {
					$privilegesArr = explode(',',$privilege['privilege']);
					foreach ($privilegesArr as $value) {
						$this->acl->allow($roleName, $resourceName, $value);
					}
				}
			} else {
				throw Mage::exception('Schracklive_Schrack', 'WARNING: unable to create privilege');
			}
		}
	}

}
