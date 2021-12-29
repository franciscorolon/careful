<?php

namespace NitroPack\Integration\Server;

class LiteSpeed {
    const STAGE = "very_early";
    const DEVICE_COOKIE = "ls_nitro_device";

    public static function detect() {
        return !empty($_SERVER["X-LSCACHE"]) || ( !empty($_SERVER["SERVER_SOFTWARE"]) && strtolower($_SERVER["SERVER_SOFTWARE"]) == "litespeed" );
    }

    public static function isCacheEnabled() {
        return false;
        return self::detect() && !empty($_SERVER["X-LSCACHE"]) && in_array("on", array_map("trim", explode(",", $_SERVER["X-LSCACHE"])));
    }

    public static function isCachePossible() {
        return isset($_COOKIE[self::DEVICE_COOKIE]);
    }

    public static function sendCacheHeader($maxAge = NULL) {
        if (!$maxAge) {
            header("X-LiteSpeed-Cache-Control: public");
        } else if (is_numeric($maxAge)) {
            header("X-LiteSpeed-Cache-Control: public,max-age=" . (int)$maxAge);
        }
    }

    public static function purge($url = NULL, $tag = NULL) {
        if ($url || $tag) {
            $headerValues = [];

            if ($url) {
                $urlObj = new \NitroPack\Url((new \NitroPack\Url($url))->getNormalized());
                if (!$urlObj->getQuery()) {
                    $headerValues[] = $urlObj->getPath();
                } else {
                    $headerValues[] = $urlObj->getPath() . "?" . $urlObj->getQuery();
                }
            }

            if ($tag) {
                $headerValues[] = "tag=" . $tag;
            }

            header("X-LiteSpeed-Purge: " . implode(", ", $headerValues), false);
        } else {
            header("X-LiteSpeed-Purge: *", false);
        }
    }

    public function init($stage) {
        return;
        if (self::isCacheEnabled()) {
            add_action('nitropack_integration_purge_url', [$this, 'purgeUrl']);
            add_action('nitropack_integration_purge_all', [$this, 'purgeAll']);
            add_action('nitropack_early_cache_headers', [$this, 'setupVary']);
            add_action('nitropack_cacheable_cache_headers', [$this, 'allowProxyCache']);
        }
    }

    public function purgeUrl($url) {
        self::purge($url);
    }

    public function purgeAll() {
        self::purge();
    }

    public function setupVary() {
        header("X-LiteSpeed-Vary: cookie=" . self::DEVICE_COOKIE);
    }

    public function allowProxyCache() {
        if (self::isCachePossible()) {
            self::sendCacheHeader(3600);
        } else if (!empty($_SERVER["HTTP_USER_AGENT"])) {
            $device = new \NitroPack\SDK\Device($_SERVER["HTTP_USER_AGENT"]);
            if ($device->isMobile()) {
                nitropack_setcookie(self::DEVICE_COOKIE, "mobile", time() + 86400);
            } else if ($device->isTablet()) {
                nitropack_setcookie(self::DEVICE_COOKIE, "tablet", time() + 86400);
            } else {
                nitropack_setcookie(self::DEVICE_COOKIE, "desktop", time() + 86400);
            }
        }
    }
}

