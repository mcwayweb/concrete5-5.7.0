<?php
namespace Concrete\Core\Cookie;

use Symfony\Component\HttpFoundation\Cookie as CookieObject;
use Request;

class CookieJar
{

    protected $cookies = array();
    protected $clearedCookies = array();

    /**
     * Adds a CookieObject to the cookie pantry
     * @param string $name The cookie name
     * @param string|null $value The value of the cookie
     * @param int $expire The number of minutes until the cookie expires
     * @param string $path The path for the cookie
     * @param null|string $domain The domain the cookie is available to
     * @param bool $secure whether the cookie should only be transmitted over a HTTPS connection from the client
     * @param bool $httpOnly Whether the cookie will be made accessible only through the HTTP protocol
     * @return \Symfony\Component\HttpFoundation\Cookie
     */
    public function set(
        $name,
        $value = null,
        $expire = 0,
        $path = '/',
        $domain = null,
        $secure = false,
        $httpOnly = true
    ) {
        $cookie = new CookieObject($name, $value, $expire, $path, $domain, $secure, $httpOnly);
        $this->add($cookie);
        return $cookie;
    }

    /**
     * Adds a CookieObject to the array of cookies for the object
     * @param CookieObject $cookie
     */
    public function add($cookie)
    {
        $this->cookies[] = $cookie;
    }

    /**
     * Used to determine if the cookie key exists in the pantry
     * @param string $cookie
     * @return bool
     */
    public function has($cookie)
    {
        $request = Request::getInstance();
        return $request->cookies->has($cookie);
    }

    public function clear($cookie)
    {
        $this->clearedCookies[] = $cookie;
    }

    /**
     * @param string $name The cookie key
     * @return mixed
     */
    public static function get($name)
    {
        $request = Request::getInstance();
        $value = $request->cookies->get($name);
        return $value;
    }

    /**
     * @return CookieObject[]
     */
    public function getCookies()
    {
        return $this->cookies;
    }

    public function getClearedCookies()
    {
        return $this->clearedCookies;
    }
}
