<?php

namespace wcf\data\package\update\server;

use wcf\data\DatabaseObject;

class ToggleablePackageUpdateServer extends PackageUpdateServer {
	/**
	 * @inheritDoc
	 */
	protected static $baseClass = PackageUpdateServer::class;
	
	/**
	 * @inheritDoc
	 */
	protected static $databaseTableName = 'package_update_server';
	
	/**
	 * @inheritDoc
	 */
	protected function handleData($data) {
		if (PACKAGE_SERVER_WOLTLAB_AUTO_VERSIONING) {
			$isDisabled = $data['isDisabled'] ?? 0;
			
			parent::handleData($data);
			
			if ($this->isWoltLabUpdateServer() || $this->isWoltLabStoreServer()) {
				$this->data['isDisabled'] = $isDisabled;
			}
		}
		else {
			if (!empty($data['metaData'])) {
				$metaData = @\unserialize($data['metaData']);
				if (\is_array($metaData)) {
					$this->metaData = $metaData;
				}
				
				unset($data['metaData']);
			}
			
			DatabaseObject::handleData($data);
		}
	}
}
