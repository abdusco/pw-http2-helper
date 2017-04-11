<?php namespace ProcessWire;


class H2Push
{
    private static $cacheAware = false;
    private static $basePath = '';
    private static $baseUrl = '';
    private static $domain = '';
    private static $assets = [];
    const types = [
        '.css' => 'style',
        '.js' => 'script',
        '.jpg' => 'image',
        '.png' => 'image',
        '.svg' => 'image',
        '.webm' => 'image'
    ];

    public static function useCache($domain = '/')
    {
        self::$domain = $domain;
        self::$cacheAware = true;
    }


    public static function addCss($path)
    {
        static::add($path, 'style');
    }

    public static function addJs($path)
    {
        static::add($path, 'script');
    }

    public static function addImage($path)
    {
        static::add($path, 'image');
    }

    public static function add($path, $type = '')
    {
        if (!$type) $type = static::getType($path);
        static::$assets[$path] = $type;
    }

    public static function push()
    {
        if (!static::$cacheAware) {
            // push the bundle
            $headers = self::getHeaders(static::$assets);
            foreach ($headers as $h) header($h, false);
            return;
        }

        // get unique ids of current files
        // and compare it with the client's

        $idd = static::idAssets(static::$assets);

        // set cache to expire one month from now
        $oneMonthLater = time() + 60 * 60 * 24 * 30;

        // check if cache exists
        if (!isset($_COOKIE['h2push'])) {
            setcookie('h2push', json_encode($idd), $oneMonthLater, '/', self::$domain);
        } else {
            // compare current bundle with the client
            $pushCookie = $_COOKIE['h2push']; // json
            $current = json_encode($idd);
            if ($pushCookie !== $current) {
                // check which files are missing from the client
                $old = json_decode($pushCookie, true);
                $missing = array_diff_assoc($idd, $old);

                // send missing ones, and save the cache to cookies
                setcookie('h2push', json_encode($missing), $oneMonthLater, '/', self::$domain);
                $headers = self::getHeaders($missing);
                foreach ($headers as $h) header($h, false);
            }
        }

    }

    private static function getHeaders($assets): array
    {
        $headers = [];
        foreach ($assets as $asset => $type) {
            $asset = self::$baseUrl . $asset;
            $header = "Link: <$asset>; rel=preload";
            if (!empty($type)) $header .= "; as=$type ";
            $headers[] = $header;
        }
        return $headers;
    }

    private static function getType($url)
    {
        foreach (static::types as $type) {
            $length = strlen($type);
            if ($length === 0) continue;
            if (substr($url, -$length) === $type) return $type;
        }
        return '';
    }

    private static function idAssets($assets): array
    {
        // uniquely identify each asset to reset on change
        $ids = [];
        foreach ($assets as $asset => $type) {
            $assetPath = static::$basePath . $asset;
            $ids[$asset] = substr(md5_file($assetPath), 0, 8);
        }
        return $ids;
    }

    /**
     * @param $basePath string path to assets folder,
     *      Asset path and base path will be joined to calculate MD5 and check for cache
     *      such as <code>/var/www/.../site/templates/</code>
     */
    public static function setBasePath($basePath)
    {
        self::$basePath = $basePath;
    }


    /**
     * @param $baseUrl string path to assets folder,
     *      such as <code>/var/www/.../site/templates/</code>
     */
    public static function setBaseUrl($baseUrl)
    {
        self::$baseUrl = $baseUrl;
    }

}