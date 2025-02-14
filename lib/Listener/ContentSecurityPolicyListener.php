<?php

namespace OCA\Files_External_Ethswarm\Listener;


use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\Security\CSP\AddContentSecurityPolicyEvent;

class ContentSecurityPolicyListener implements IEventListener {
    public function handle(Event $event): void {
        if (!($event instanceof AddContentSecurityPolicyEvent)) {
            return;
        }

        $csp = new \OCP\AppFramework\Http\ContentSecurityPolicy();
        $csp->addAllowedConnectDomain('https://test.hejbit.com');
        $event->addPolicy($csp);
    }
}
