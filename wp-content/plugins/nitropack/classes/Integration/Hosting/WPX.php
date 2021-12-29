<?php

namespace NitroPack\Integration\Hosting;

class WPX extends Hosting {
    const STAGE = NULL;

    public static function detect() {
        $hostname = gethostname();
        return $hostname && (preg_match("/wpx\.net$/", $hostname) || preg_match("/wpxhosting\.com$/", $hostname));
    }
}

