<?php

namespace OAuthServer\Test\TestCase\Controller;

use Cake\Event\EventManager;
use Cake\ORM\TableRegistry;
use Cake\Routing\RouteBuilder;
use Cake\Routing\Router;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;
use OAuthServer\Controller\OAuthController;
use TestApp\Controller\TestAppController;

class OAuthControllerTest extends TestCase
{
    use IntegrationTestTrait;

    public $fixtures = [
        'plugin.OAuthServer.Clients',
        'plugin.OAuthServer.Scopes',
        'plugin.OAuthServer.AccessTokens',
        'plugin.OAuthServer.Sessions',
        'plugin.OAuthServer.SessionScopes',
        'plugin.OAuthServer.AuthCodes',
        'plugin.OAuthServer.AuthCodeScopes',
    ];

    public function setUp()
    {
        // class Router needs to be loaded in order for TestCase to automatically include routes
        // not really sure how to do it properly, this hotfix seems good enough
        Router::defaultRouteClass();

        parent::setUp();

        Router::plugin('OAuthServer', function (RouteBuilder $routes) {
            $routes->connect('/login', ['controller' => 'Users', 'action' => 'login']);
        });
    }

    public function testOauthRedirectsToAuthorize()
    {
        $this->get($this->url("/oauth") . "?client_id=CID&anything=at_all");
        $this->assertRedirect(['controller' => 'OAuth', 'action' => 'authorize', '?' => ['client_id' => 'CID', 'anything' => 'at_all']]);
    }

    public function testAuthorizeInvalidParams()
    {
        $_GET = ['client_id' => 'INVALID', 'redirect_uri' => 'http://www.example.com', 'response_type' => 'code', 'scope' => 'test'];
        $this->get($this->url('/oauth/authorize') . '?' . http_build_query($_GET));
        $this->assertResponseError();
    }

    public function testAuthorizeLoginRedirect()
    {
        $_GET = ['client_id' => 'TEST', 'redirect_uri' => 'http://www.example.com', 'response_type' => 'code', 'scope' => 'test'];
        $authorizeUrl = $this->url('/oauth/authorize') . '?' . http_build_query($_GET);

        $this->get($authorizeUrl);
        $this->assertRedirect(['controller' => 'Users', 'action' => 'login', '?' => ['redirect' => $authorizeUrl]]);
    }

    public function testStoreCurrentUserAndDefaultAuth()
    {
        $this->session(['Auth.User.id' => 5]);

        $_GET = ['client_id' => 'TEST', 'redirect_uri' => 'http://www.example.com', 'response_type' => 'code', 'scope' => 'test'];
        $this->post('/oauth/authorize' . '?' . http_build_query($_GET), ['authorization' => 'Approve']);

        $this->assertRedirect();

        $sessions = TableRegistry::get('OAuthServer.Sessions');
        $this->assertTrue($sessions->exists(['owner_id' => 5, 'owner_model' => 'Users']), "Session in database was not correct");
    }

    public function testOverrideOwnerModelAndOwnerId()
    {
        $this->session(['Auth.User.id' => 5]);

        EventManager::instance()->on('OAuthServer.beforeAuthorize', function () {
            return [
                'ownerModel' => 'AnotherModel',
                'ownerId' => 15,
            ];
        });

        $_GET = ['client_id' => 'TEST', 'redirect_uri' => 'http://www.example.com', 'response_type' => 'code', 'scope' => 'test'];
        $this->post('/oauth/authorize' . '?' . http_build_query($_GET), ['authorization' => 'Approve']);

        $this->assertEquals('AnotherModel', $this->viewVariable('ownerModel'));
        $this->assertEquals(15, $this->viewVariable('ownerId'));

        $sessions = TableRegistry::get('OAuthServer.Sessions');
        $this->assertTrue($sessions->exists(['owner_id' => 15, 'owner_model' => 'AnotherModel']), "Session in database was not correct");
    }

    private function url($path, $ext = null)
    {
        $ext = $ext ? ".$ext" : '';

        return $path . $ext;
    }
}
