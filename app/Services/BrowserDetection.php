<?php

namespace App\Services;

class BrowserDetection
{
    private $_user_agent;
    private $_name;
    private $_version;
    private $_platform;

    /**
     * @var array
     */
    private $_basic_browser = array (
        'Trident\/7.0' => 'Internet Explorer 11',
        'Beamrise' => 'Beamrise',
        'Opera' => 'Opera',
        'OPR' => 'Opera',
        'Shiira' => 'Shiira',
        'Chimera' => 'Chimera',
        'Phoenix' => 'Phoenix',
        'Firebird' => 'Firebird',
        'Camino' => 'Camino',
        'Netscape' => 'Netscape',
        'OmniWeb' => 'OmniWeb',
        'Konqueror' => 'Konqueror',
        'icab' => 'iCab',
        'Lynx' => 'Lynx',
        'Links' => 'Links',
        'hotjava' => 'HotJava',
        'amaya' => 'Amaya',
        'IBrowse' => 'IBrowse',
        'iTunes' => 'iTunes',
        'Silk' => 'Silk',
        'Dillo' => 'Dillo',
        'Maxthon' => 'Maxthon',
        'Arora' => 'Arora',
        'Galeon' => 'Galeon',
        'Iceape' => 'Iceape',
        'Iceweasel' => 'Iceweasel',
        'Midori' => 'Midori',
        'QupZilla' => 'QupZilla',
        'Namoroka' => 'Namoroka',
        'NetSurf' => 'NetSurf',
        'BOLT' => 'BOLT',
        'EudoraWeb' => 'EudoraWeb',
        'shadowfox' => 'ShadowFox',
        'Swiftfox' => 'Swiftfox',
        'Uzbl' => 'Uzbl',
        'UCBrowser' => 'UCBrowser',
        'Kindle' => 'Kindle',
        'wOSBrowser' => 'wOSBrowser',
        'Epiphany' => 'Epiphany',
        'SeaMonkey' => 'SeaMonkey',
        'Avant Browser' => 'Avant Browser',
        'Firefox' => 'Firefox',
        'Chrome' => 'Google Chrome',
        'MSIE' => 'Internet Explorer',
        'Internet Explorer' => 'Internet Explorer',
        'Safari' => 'Safari',
        'Mozilla' => 'Mozilla'
    );

    /**
     * @var array
     */
    private $_basic_platform = array(
        'windows' => 'Windows',
        'iPad' => 'iPad',
        'iPod' => 'iPod',
        'iPhone' => 'iPhone',
        'mac' => 'Apple',
        'android' => 'Android',
        'linux' => 'Linux',
        'Nokia' => 'Nokia',
        'BlackBerry' => 'BlackBerry',
        'FreeBSD' => 'FreeBSD',
        'OpenBSD' => 'OpenBSD',
        'NetBSD' => 'NetBSD',
        'UNIX' => 'UNIX',
        'DragonFly' => 'DragonFlyBSD',
        'OpenSolaris' => 'OpenSolaris',
        'SunOS' => 'SunOS',
        'OS\/2' => 'OS/2',
        'BeOS' => 'BeOS',
        'win' => 'Windows',
        'Dillo' => 'Linux',
        'PalmOS' => 'PalmOS',
        'RebelMouse' => 'RebelMouse'
    );

    /**
     * BrowserDetection constructor.
     * @param string $ua
     */
    public function __construct($ua = '')
    {
        if (empty($ua)) {
            $this->_user_agent = (!empty($_SERVER['HTTP_USER_AGENT'])
                ? $_SERVER['HTTP_USER_AGENT']
                : getenv('HTTP_USER_AGENT')
            );
        } else {
            $this->_user_agent = $ua;
        }
    }

    /**
     * @return $this
     */
    public function detect()
    {
        $this->detectBrowser();
        $this->detectPlatform();
        return $this;
    }

    /**
     * @return void
     */
    protected function detectBrowser(): void
    {
        foreach ($this->_basic_browser as $pattern => $name) {
            if (preg_match("/".$pattern."/i",$this->_user_agent, $match)) {
                $this->_name = $name;
                $known = array('Version', $pattern, 'other');
                $pattern_version = '#(?<browser>' . join('|', $known).')[/ ]+(?<version>[0-9.|a-zA-Z.]*)#';

                if (!preg_match_all($pattern_version, $this->_user_agent, $matches)) {}

                $i = count($matches['browser']);
                if ($i != 1) {
                    if (strripos($this->_user_agent,"Version") < strripos($this->_user_agent,$pattern)){
                        @$this->_version = $matches['version'][0];
                    }else {
                        @$this->_version = $matches['version'][1];
                    }
                }else {
                    $this->_version = $matches['version'][0];
                }

                break;
            }
        }
    }

    /**
     * @return void
     */
    protected function detectPlatform(): void
    {
        foreach($this->_basic_platform as $key => $platform) {
            if (stripos($this->_user_agent, $key) !== false) {
                $this->_platform = $platform;
                break;
            }
        }
    }

    /**
     * @return mixed
     */
    public function getBrowser()
    {
        return $this->_name ?? 'Unknown';
    }

    /**
     * @return mixed
     */
    public function getVersion()
    {
        return $this->_version;
    }

    /**
     * @return string
     */
    public function getPlatform()
    {
        return $this->_platform ?? 'Unknown';
    }

    /**
     * @return string
     */
    public function getUserAgent()
    {
        return $this->_user_agent;
    }
}