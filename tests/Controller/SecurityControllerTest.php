<?php

namespace App\Tests\Controller;

use App\Infrastructure\Persistence\Doctrine\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class SecurityControllerTest extends WebTestCase
{
    private $client;
    private EntityManagerInterface $entityManager;
    private UserPasswordHasherInterface $passwordHasher;

    protected function setUp(): void
    {
        $this->client = static::createClient();

        $container = static::getContainer();
        $this->entityManager = $container->get(EntityManagerInterface::class);
        $this->passwordHasher = $container->get(UserPasswordHasherInterface::class);

        // Setup the database schema for the test
        $schemaTool = new SchemaTool($this->entityManager);
        $metadata = $this->entityManager->getMetadataFactory()->getAllMetadata();
        $schemaTool->dropSchema($metadata);
        $schemaTool->createSchema($metadata);
    }

    private function createUser(): User
    {
        $user = new User();
        $user->setUsername('test_user');
        $user->setRoles(['ROLE_USER']);
        $user->setPassword($this->passwordHasher->hashPassword($user, 'test_password'));

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }

    public function testLoginDisplaysForm(): void
    {
        $crawler = $this->client->request('GET', '/login');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Acceso Admin');

        // Assert form exists
        $this->assertCount(1, $crawler->filter('form'));

        // Assert fields exist
        $this->assertCount(1, $crawler->filter('input[name="_username"]'));
        $this->assertCount(1, $crawler->filter('input[name="_password"]'));
        $this->assertCount(1, $crawler->filter('input[name="_csrf_token"]'));
    }

    public function testLoginRedirectsAuthenticatedUser(): void
    {
        $user = $this->createUser();
        $this->client->loginUser($user);

        $this->client->request('GET', '/login');

        $this->assertResponseRedirects('/admin/apartments');
    }

    public function testLoginWithValidCredentials(): void
    {
        $user = $this->createUser();

        $crawler = $this->client->request('GET', '/login');

        $form = $crawler->selectButton('Iniciar sesión →')->form([
            '_username' => 'test_user',
            '_password' => 'test_password',
        ]);

        $this->client->submit($form);

        $this->assertResponseRedirects('/admin/apartments');

        $this->client->followRedirect();
        // Since we created a generic user, it should redirect to admin apartments, but we don't need to verify the page content here.
        // As long as it redirects to the right place.
    }

    public function testLoginWithInvalidCredentials(): void
    {
        $this->createUser(); // Create user but use wrong password

        $crawler = $this->client->request('GET', '/login');

        $form = $crawler->selectButton('Iniciar sesión →')->form([
            '_username' => 'test_user',
            '_password' => 'wrong_password',
        ]);

        $this->client->submit($form);

        $this->assertResponseRedirects('http://localhost/login');
        $this->client->followRedirect();

        // Should show error message (we can just check for flash-error or the text)
        $this->assertSelectorExists('.flash-error');
    }

    public function testLogout(): void
    {
        $user = $this->createUser();
        $this->client->loginUser($user);

        // Make sure we're authenticated
        $this->client->request('GET', '/admin/apartments');
        $this->assertResponseIsSuccessful();

        $this->client->request('GET', '/logout');

        // Based on security.yaml, logout redirects to apartment_public_list
        $this->assertResponseRedirects('http://localhost/');

        $this->client->followRedirect();

        // Try accessing an admin page again, it should redirect to login
        $this->client->request('GET', '/admin/apartments');
        $this->assertResponseRedirects('http://localhost/login');
    }
}
