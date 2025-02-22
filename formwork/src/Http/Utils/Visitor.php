<?php

namespace Formwork\Http\Utils;

use Detection\MobileDetect;
use Formwork\Http\Request;
use Formwork\Traits\StaticClass;
use Jaybizzle\CrawlerDetect\CrawlerDetect;

final class Visitor
{
    use StaticClass;

    /**
     * Return whether current visitor is a bot
     */
    public static function isBot(Request $request): bool
    {
        static $crawlerDetect = new CrawlerDetect();
        return $crawlerDetect->isCrawler($request->userAgent() ?? '');
    }

    /**
     * Return whether current user agent is a browser
     */
    public static function isBrowser(Request $request): bool
    {
        return !self::isBot($request);
    }

    public static function getDeviceType(Request $request): DeviceType
    {
        static $mobileDetect = new MobileDetect(config: ['autoInitOfHttpHeaders' => false]);
        $mobileDetect->setUserAgent($request->userAgent() ?? '');
        return match (true) {
            $mobileDetect->isMobile() => DeviceType::Mobile,
            $mobileDetect->isTablet() => DeviceType::Tablet,
            default                   => DeviceType::Desktop,
        };
    }

    public static function isMobile(Request $request): bool
    {
        return self::getDeviceType($request) === DeviceType::Mobile;
    }

    public static function isTablet(Request $request): bool
    {
        return self::getDeviceType($request) === DeviceType::Tablet;
    }

    public static function isDesktop(Request $request): bool
    {
        return self::getDeviceType($request) === DeviceType::Desktop;
    }
}
