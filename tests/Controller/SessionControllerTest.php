<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class SessionControllerTest extends WebTestCase
{
    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();

        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $metadata = $entityManager->getMetadataFactory()->getAllMetadata();

        $schemaTool = new SchemaTool($entityManager);
        $schemaTool->dropSchema($metadata);
        $schemaTool->createSchema($metadata);
    }

    public function testLoginPageIsRendered(): void
    {
        $this->client->request('GET', '/login');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'Admin Login');
        self::assertSelectorExists('input[name="_username"]');
        self::assertSelectorExists('input[name="_password"]');
        self::assertSelectorExists('input[name="_csrf_token"]');
    }

    public function testLoginWithValidCredentials(): void
    {
        $this->createAdmin();

        $this->client->request('GET', '/login');
        $this->client->submitForm('Sign in', [
            '_username' => 'admin@phpnews.com',
            '_password' => 'secret123',
        ]);

        self::assertResponseRedirects('/');
    }

    public function testLoginWithInvalidCredentials(): void
    {
        $this->createAdmin();

        $this->client->request('GET', '/login');
        $this->client->submitForm('Sign in', [
            '_username' => 'admin@phpnews.com',
            '_password' => 'wrongpassword',
        ]);

        self::assertResponseRedirects('/login');
        $this->client->followRedirect();
        self::assertSelectorExists('.bg-red-50');
    }

    public function testLoginWithNonExistentUser(): void
    {
        $this->client->request('GET', '/login');
        $this->client->submitForm('Sign in', [
            '_username' => 'nobody@phpnews.com',
            '_password' => 'secret123',
        ]);

        self::assertResponseRedirects('/login');
    }

    public function testLogout(): void
    {
        $this->client->loginUser($this->createAdmin());

        $this->client->request('POST', '/logout');

        self::assertResponseRedirects('/');
    }

    public function testLogoutRejectsGetRequests(): void
    {
        $this->client->request('GET', '/logout');

        self::assertResponseStatusCodeSame(405);
    }

    private function createAdmin(string $email = 'admin@phpnews.com', string $password = 'secret123'): User
    {
        $container = self::getContainer();

        $user = new User();
        $user->setEmail($email);
        $user->setPassword(
            $container->get(UserPasswordHasherInterface::class)->hashPassword($user, $password),
        );

        $entityManager = $container->get(EntityManagerInterface::class);
        $entityManager->persist($user);
        $entityManager->flush();

        return $user;
    }
}
