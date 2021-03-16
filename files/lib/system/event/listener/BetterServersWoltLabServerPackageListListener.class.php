<?php

namespace wcf\system\event\listener;

use wcf\acp\page\PackageListPage;

class BetterServersWoltLabServerPackageListListener implements IParameterizedEventListener {
	/**
	 * @inheritDoc
	 */
	public function execute($eventObj, $className, $eventName, array &$parameters) {
		if (!PACKAGE_SERVER_BETTERSERVERS) return;
		
		if ($eventObj instanceof PackageListPage) {
			if ($eventName == 'readParameters') {
				$eventObj->templateName = 'betterServersPackageList';
				$eventObj->templateNameApplication = 'wcf';
			}
		}
	}
}
