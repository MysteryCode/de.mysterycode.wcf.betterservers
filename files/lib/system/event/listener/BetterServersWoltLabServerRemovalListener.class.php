<?php

namespace wcf\system\event\listener;

use wcf\data\package\update\PackageUpdateAction;

class BetterServersWoltLabServerRemovalListener implements IParameterizedEventListener {
	/**
	 * @inheritDoc
	 */
	public function execute($eventObj, $className, $eventName, array &$parameters) {
		if ($eventObj instanceof PackageUpdateAction) {
			if ($eventName == 'initializeAction') {
			
			}
			else if ($eventName == 'finalizeAction') {
			
			}
		}
	}
}
