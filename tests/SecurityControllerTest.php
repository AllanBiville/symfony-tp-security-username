<?php

namespace App\Tests;

use PHPUnit\Framework\TestCase;
use App\Repository\UserRepository;
use App\Tests\SecurityControllerTest;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class SecurityControllerTest extends WebTestCase
{
    
    public function testShowLogin(){
        $client = static::createClient();
        $client->request('GET','/login');
        $this->assertEquals(Response::HTTP_OK, $client->getResponse()->getStatusCode());
    }

   private function logIn($userName = 'user', $userRole = 'ROLE_uSER')
   {
       $session = $client->getContaier()->get('session');
       //$user = "superadmin";
       $firewallName = 'main';
       $firewallContext = 'main';
       $token = new UsernamePasswordToken('admin', null, $firewallName, ['ROLE_ADMIN']);
       $session->set('_security_'.$firewallContext, serialize($token));
       $session->save();

       $cookie = new Cookie($session->getName(), $session->getId());
       $this->client->getCookieJar()->set($cookie);
   }
//    public function testSecuredRoleUser(){
//        $this->login('user','ROLE_USER');
//        $crawler = $client->$request('GET', '/category/');

//        $this->assertSame(Response::HTTP_OK, $client->getResponse()->getStatusCode());
//        $this->assertSame('Category index', $Crawler->filter('h1')->text());
//    }

}
