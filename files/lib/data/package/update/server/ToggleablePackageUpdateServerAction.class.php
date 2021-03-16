<?php

namespace wcf\data\package\update\server;

use wcf\data\AbstractDatabaseObjectAction;
use wcf\system\exception\PermissionDeniedException;

/**
 * @property	ToggleablePackageUpdateServerEditor[]	$objects
 * @method	ToggleablePackageUpdateServerEditor[]	getObjects()
 * @method	ToggleablePackageUpdateServerEditor	getSingleObject()
 */
class ToggleablePackageUpdateServerAction extends PackageUpdateServerAction {
	/**
	 * @inheritDoc
	 */
	protected $className = ToggleablePackageUpdateServerEditor::class;
	
	/**
	 * @inheritDoc
	 */
	public function validateDelete() {
		AbstractDatabaseObjectAction::validateDelete();
		
		foreach ($this->getObjects() as $updateServer) {
			$updateServer = new DecoratedToggleablePackageUpdateServer($updateServer->getDecoratedObject());
			if (!$updateServer->canDelete()) {
				throw new PermissionDeniedException();
			}
		}
	}
	
	/**
	 * @inheritDoc
	 */
	public function validateToggle() {
		$this->traitValidateToggle();
		
		foreach ($this->getObjects() as $updateServer) {
			$updateServer = new DecoratedToggleablePackageUpdateServer($updateServer->getDecoratedObject());
			if (!$updateServer->canDisable()) {
				throw new PermissionDeniedException();
			}
		}
	}
}
