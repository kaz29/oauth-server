<?php
namespace OAuthServer\Test\TestCase\Auth;

use Cake\Controller\ComponentRegistry;
use Cake\Http\Response;
use Cake\Http\ServerRequest;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;
use OAuthServer\Auth\OAuthAuthenticate;

class OAuthAuthenticateTest extends TestCase
{

    /**
     * setup
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $this->Collection = $this->getMockBuilder(ComponentRegistry::class)->getMock();
        $this->auth = new OAuthAuthenticate($this->Collection, [
            'userModel' => 'Users'
        ]);
        TableRegistry::clear();
        $this->response = $this->getMockBuilder(Response::class)->getMock();
    }

    public function testAuthenticate()
    {
        $request = new ServerRequest('posts/index');
//        $request->data = [];
        $this->assertFalse($this->auth->authenticate($request, $this->response));
    }
}
