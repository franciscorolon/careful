<?php

namespace NitroPack\Integration\Plugin;

class CookieNotice {
    const STAGE = "late";

    public function init($stage) {
        # Cookie Notice plugin hack
        add_action( 'init', function() {
            $cookieNoticePath = 'cookie-notice/cookie-notice.php';
            if (function_exists("is_plugin_active") && is_plugin_active($cookieNoticePath)) {
                $agent = Cookie_Notice()->bot_detect->get_user_agent();
                if ($agent) {
                    $replaced = str_replace('Nitro-Optimizer-Agent', '', $agent);
                    Cookie_Notice()->bot_detect->set_user_agent($replaced);
                }
            }
        }, 5);
    }
}
