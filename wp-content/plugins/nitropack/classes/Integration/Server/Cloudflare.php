<?php

namespace NitroPack\Integration\Server;

// We need this to control Cloudflare in addition to any other proxy potentially provided by the origin host company
class Cloudflare {
    const STAGE = "very_early";

    public static function detect() {
        return !empty($_SERVER["HTTP_CF_CONNECTING_IP"]);
    }

    public static function isCacheEnabled() {
        return self::detect() && !empty($_SERVER["HTTP_SEC_CH_UA_MOBILE"]);
    }

    public function init($stage) {
        if (self::detect()) {
            header("Accept-CH: Sec-CH-UA-Mobile");

            if (self::isCacheEnabled()) {
                add_action('nitropack_cacheable_cache_headers', [$this, 'allowProxyCache'], PHP_INT_MAX-1);
                add_action('nitropack_cachehit_cache_headers', [$this, 'allowProxyCache'], PHP_INT_MAX-1);
            } else {
                add_action('nitropack_cacheable_cache_headers', [$this, 'preventProxyCache'], PHP_INT_MAX-1);
                add_action('nitropack_cachehit_cache_headers', [$this, 'preventProxyCache'], PHP_INT_MAX-1);
            }
        }
    }

    public function allowProxyCache() {
        header("Vary: sec-ch-ua-mobile");
        header("CDN-Cache-Control: public, max-age=0, s-maxage=15, stale-while-revalidate=3600");
    }

    public function preventProxyCache() {
        header("CDN-Cache-Control: no-cache");
    }
}

