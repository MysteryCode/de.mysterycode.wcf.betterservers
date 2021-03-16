<?php

namespace wcf\system\event\listener;

use wcf\system\WCFACP;

class BetterServersWoltLabServerListListener implements IParameterizedEventListener {
	/**
	 * @inheritDoc
	 */
	public function execute($eventObj, $className, $eventName, array &$parameters) {
		if (PACKAGE_SERVER_BETTERSERVERS && $eventObj instanceof WCFACP && $eventName == 'initialized') {
			@include_once(WCF_DIR . '/lib/data/package/update/server/BetterServersPackageUpdateServer.class.php');
		}
	}
}
