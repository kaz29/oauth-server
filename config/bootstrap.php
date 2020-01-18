<?php

use Cake\Core\Configure;

/**
 * OAuthServer plugin creates controller that extends App\Controller\AppController class.
 * Config OAuthServer.appController allows to override the base controller class.
 */
$appControllerAlias = 'OAuthServer\Controller\AppController';
if (!class_exists($appControllerAlias)) {
    $appControllerReal = Configure::read('OAuthServer.appController') ?: 'App\Controller\AppController';
    class_alias($appControllerReal, $appControllerAlias);
}
