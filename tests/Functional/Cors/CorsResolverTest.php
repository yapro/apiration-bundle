<?php

declare(strict_types=1);

namespace YaPro\ApiRationBundle\Tests\Functional\Cors;

use Symfony\Component\HttpClient\CurlHttpClient;
use YaPro\ApiRationBundle\Cors\CorsResolver;
use YaPro\SymfonyHttpClientExt\HttpClientExtTrait;
use YaPro\SymfonyHttpTestExt\BaseTestCase;

// Т.к. это доп. функциональность, то:
// - ниже созданные тесты отключены с помощью phpunit.xml.dist
// - testLoginWithSecurityCookie не может быть пройден, т.к. требует функционала symfony-авторизации порождающий пхп-сессию
class CorsResolverTest extends BaseTestCase
{
    use HttpClientExtTrait;

    private string $userRoleAdmin = 'admin';

    public function testOptionsRequest(): void
    {
        $origin = 'http://foo.example';
        $this->sendRequest('OPTIONS', '/login', [], [], [
            'Origin' => $origin,
        ]);
        $this->assertHeader('Access-Control-Allow-Credentials', CorsResolver::ACCESS_CONTROL_ALLOW_CREDENTIALS);
        $this->assertHeader('Access-Control-Allow-Headers', CorsResolver::ACCESS_CONTROL_ALLOW_HEADERS);
        $this->assertHeader('Access-Control-Allow-Methods', CorsResolver::ACCESS_CONTROL_ALLOW_METHODS);
        $this->assertHeader('Access-Control-Allow-Origin', $origin);
        // @todo а какой тут response (боди)
    }

    private function assertHeader(string $headerName, string $headerValue): void
    {
        $this->assertSame($this->getHeader($headerName), $headerValue);
    }

    public function testLoginWithSecurityCookie(): void
    {
        $this->post('/login', [
            'email' => 'login',
            'password' => 'pa$$word',
        ]);

        self::assertResponseIsSuccessful();

        $headerValue = $this->getHeader('Set-Cookie');
        $cookieParams = explode(';', $headerValue);
        // вытаскиваем PHPSESSID=510ef5c70dd626778170f5a85c69b3fa из списка параметров из-за генерируемого значения
        $cookieName = explode('=', array_shift($cookieParams));
        $sameAttributes = 'path=/; domain=' . getenv('CORS_DOMAIN_NAME') . '; ';

        // Параметр HttpOnly говорит браузеру, что кука доступна только для браузера, и не доступна из JavaScript через
        // свойства Document.cookie. HttpOnly включена по-умолчанию с помощью параметра cookie_httponly.
        // Параметр secure появляется только когда запрос идет по https, это следствие настройки cookie_secure: auto.

        // Если self::$client настроен на отправку запросов через реальный веб-сервер (в нашем случае Nginx):
        if (class_exists('Symfony\Component\HttpClient\CurlHttpClient')
            && $this->getHttpClient() instanceof CurlHttpClient) {
            $this->assertSame('PHPSESSID', $cookieName[0]);
            $this->assertSame($sameAttributes . 'HttpOnly; SameSite=none', trim(implode(';', $cookieParams)));
        } else { // когда self::$client не использует веб-сервер, то мы получаем такой расклад:
            $this->assertSame('MOCKSESSID', $cookieName[0]);
            $this->assertSame($sameAttributes . 'secure; httponly; samesite=none', trim(implode(';', $cookieParams)));
        }
    }
}
