<?php

namespace wcf\system\event\listener;

use wcf\acp\page\PackageUpdateServerListPage;
use wcf\data\package\update\server\DecoratedToggleablePackageUpdateServer;
use wcf\data\package\update\server\PackageUpdateServerList;
use wcf\data\package\update\server\ToggleablePackageUpdateServer;
use wcf\data\package\update\server\ToggleablePackageUpdateServerList;
use wcf\system\WCFACP;

class BetterServersWoltLabServerListListener implements IParameterizedEventListener {
	/**
	 * @inheritDoc
	 */
	public function execute($eventObj, $className, $eventName, array &$parameters) {
		if (!PACKAGE_SERVER_BETTERSERVERS) return;
		
		if ($eventObj instanceof PackageUpdateServerListPage) {
			return;
			if ($eventName == 'readParameters') {
				$eventObj->objectListClassName = ToggleablePackageUpdateServerList::class;
				$eventObj->templateName = 'betterServersPackageUpdateServerList';
				$eventObj->templateNameApplication = 'wcf';
			}
		}
		else if ($eventObj instanceof PackageUpdateServerList && !($eventObj instanceof ToggleablePackageUpdateServerList)) {
			return;
			$eventObj->className = ToggleablePackageUpdateServer::class;
			$eventObj->decoratorClassName = DecoratedToggleablePackageUpdateServer::class;
		}
		else if ($eventObj instanceof WCFACP) {
			if ($eventName == 'initialized') {
				@include_once(WCF_DIR . '/lib/data/package/update/server/BetterServersPackageUpdateServer.class.php');
			}
		}
	}
}
