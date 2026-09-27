<?php

declare(strict_types=1);

namespace App\Support;

use Detection\MobileDetect;

/**
 * Reads the platform, browser and device type from a user agent string, for the browser sessions list.
 *
 * Copied from Jetstream (originally jenssegers/agent) so the profile page no longer needs the package.
 * MobileDetect only knows mobile platforms and browsers; the extra rules below cover desktop ones.
 */
class Agent extends MobileDetect
{
    /**
     * @var array<string, string>
     */
    protected static array $additionalOperatingSystems = [
        'Windows'    => 'Windows',
        'Windows NT' => 'Windows NT',
        'OS X'       => 'Mac OS X',
        'Debian'     => 'Debian',
        'Ubuntu'     => 'Ubuntu',
        'Macintosh'  => 'PPC',
        'OpenBSD'    => 'OpenBSD',
        'Linux'      => 'Linux',
        'ChromeOS'   => 'CrOS',
    ];

    /**
     * Ordered so that more specific browsers match before the engines they are built on.
     *
     * @var array<string, string>
     */
    protected static array $additionalBrowsers = [
        'Opera Mini' => 'Opera Mini',
        'Opera'      => 'Opera|OPR',
        'Edge'       => 'Edge|Edg',
        'Coc Coc'    => 'coc_coc_browser',
        'UCBrowser'  => 'UCBrowser',
        'Vivaldi'    => 'Vivaldi',
        'Chrome'     => 'Chrome',
        'Firefox'    => 'Firefox',
        'Safari'     => 'Safari',
        'IE'         => 'MSIE|IEMobile|MSIEMobile|Trident/[.0-9]+',
        'Netscape'   => 'Netscape',
        'Mozilla'    => 'Mozilla',
        'WeChat'     => 'MicroMessenger',
    ];

    public function platform(): ?string
    {
        return $this->findDetectionRulesAgainstUserAgent(
            $this->mergeRules(MobileDetect::getOperatingSystems(), static::$additionalOperatingSystems)
        );
    }

    public function browser(): ?string
    {
        return $this->findDetectionRulesAgainstUserAgent(
            $this->mergeRules(static::$additionalBrowsers, MobileDetect::getBrowsers())
        );
    }

    public function isDesktop(): bool
    {
        if ($this->getUserAgent() === static::$cloudFrontUA && $this->getHttpHeader('HTTP_CLOUDFRONT_IS_DESKTOP_VIEWER') === 'true') {
            return true;
        }

        return ! $this->isMobile() && ! $this->isTablet();
    }

    /**
     * @param  array<string, string>  $rules
     */
    protected function findDetectionRulesAgainstUserAgent(array $rules): ?string
    {
        $userAgent = $this->getUserAgent();

        if ($userAgent === null) {
            return null;
        }

        foreach ($rules as $name => $regex) {
            if (! empty($regex) && $this->match($regex, $userAgent)) {
                return $name;
            }
        }

        return null;
    }

    /**
     * Combine rule sets, joining the patterns of names that appear in more than one set.
     *
     * @param  array<string, string>  ...$ruleSets
     * @return array<string, string>
     */
    protected function mergeRules(array ...$ruleSets): array
    {
        $merged = [];

        foreach ($ruleSets as $rules) {
            foreach ($rules as $name => $regex) {
                $merged[$name] = empty($merged[$name]) ? $regex : $merged[$name] . '|' . $regex;
            }
        }

        return $merged;
    }
}
