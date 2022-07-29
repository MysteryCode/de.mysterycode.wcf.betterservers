<?php

namespace wcf\system\event\listener;

use wcf\system\WCFACP;

class BetterServersWoltLabServerListListener extends AbstractEventListener
{
    protected function onInitialized(WCFACP $eventObj)
    {
        if (PACKAGE_SERVER_BETTERSERVERS && $eventName == 'initialized') {
            @include_once(WCF_DIR . '/lib/data/package/update/server/BetterServersPackageUpdateServer.class.php');
        }
    }
}
