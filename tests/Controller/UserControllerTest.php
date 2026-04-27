<?php

namespace App\Tests\Controller;

use App\Infrastructure\Persistence\Doctrine\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserControllerTest extends WebTestCase
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

    private function createAdminUser(): User
    {
        $user = new User();
        $user->setUsername('admin_user');
        $user->setRoles(['ROLE_ADMIN']);
        $user->setPassword($this->passwordHasher->hashPassword($user, 'password'));

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }

    private function createNormalUser(): User
    {
        $user = new User();
        $user->setUsername('normal_user');
        $user->setRoles(['ROLE_USER']);
        $user->setPassword($this->passwordHasher->hashPassword($user, 'password'));

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }

    public function testAnonymousUserIsRedirected(): void
    {
        $this->client->request('GET', '/admin/users/');
        $this->assertResponseRedirects('/login'); // Assuming standard symfony login route
    }

    public function testNormalUserIsForbidden(): void
    {
        $user = $this->createNormalUser();
        $this->client->loginUser($user);

        $this->client->request('GET', '/admin/users/');
        $this->assertResponseStatusCodeSame(403);
    }

    public function testAdminCanAccessIndex(): void
    {
        $admin = $this->createAdminUser();
        $this->client->loginUser($admin);

        $this->client->request('GET', '/admin/users/');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('table'); // assuming there's a table rendering the users
    }

    public function testAdminCanCreateUser(): void
    {
        $admin = $this->createAdminUser();
        $this->client->loginUser($admin);

        $crawler = $this->client->request('GET', '/admin/users/new');
        $this->assertResponseIsSuccessful();

        $form = $crawler->selectButton('Guardar Usuario')->form([
            'user[username]' => 'new_test_user',
            'user[plainPassword][first]' => 'newpassword123',
            'user[plainPassword][second]' => 'newpassword123',
        ]);

        $this->client->submit($form);
        $this->assertResponseRedirects('/admin/users/');

        // Verify user was created and password hashed
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['username' => 'new_test_user']);
        $this->assertNotNull($user);
        $this->assertTrue($this->passwordHasher->isPasswordValid($user, 'newpassword123'));
    }

    public function testAdminCanCreateUserWithoutPassword(): void
    {
        $admin = $this->createAdminUser();
        $this->client->loginUser($admin);

        $crawler = $this->client->request('GET', '/admin/users/new');
        $this->assertResponseIsSuccessful();

        $form = $crawler->selectButton('Guardar Usuario')->form([
            'user[username]' => 'new_user_no_pass',
            'user[plainPassword][first]' => '',
            'user[plainPassword][second]' => '',
        ]);

        $this->client->submit($form);
        $this->assertResponseRedirects('/admin/users/');

        $user = $this->entityManager->getRepository(User::class)->findOneBy(['username' => 'new_user_no_pass']);
        $this->assertNotNull($user);
        $this->assertNull($user->getPassword()); // Should have no password
    }

    public function testAdminCanEditUser(): void
    {
        $admin = $this->createAdminUser();
        $userToEdit = $this->createNormalUser();
        $this->client->loginUser($admin);

        $crawler = $this->client->request('GET', '/admin/users/' . $userToEdit->getId() . '/edit');
        $this->assertResponseIsSuccessful();

        $form = $crawler->selectButton('Actualizar Usuario')->form([
            'user[username]' => 'updated_username',
        ]);

        $this->client->submit($form);
        $this->assertResponseRedirects('/admin/users/');

        $this->entityManager->clear(); // Clear identity map to get fresh data
        $updatedUser = $this->entityManager->getRepository(User::class)->find($userToEdit->getId());
        $this->assertEquals('updated_username', $updatedUser->getUsername());
    }

    public function testAdminCanEditUserPassword(): void
    {
        $admin = $this->createAdminUser();
        $userToEdit = $this->createNormalUser();
        $this->client->loginUser($admin);

        $crawler = $this->client->request('GET', '/admin/users/' . $userToEdit->getId() . '/edit');

        $form = $crawler->selectButton('Actualizar Usuario')->form([
            'user[plainPassword][first]' => 'new_updated_password',
            'user[plainPassword][second]' => 'new_updated_password',
        ]);

        $this->client->submit($form);

        $this->entityManager->clear();
        $updatedUser = $this->entityManager->getRepository(User::class)->find($userToEdit->getId());
        $this->assertTrue($this->passwordHasher->isPasswordValid($updatedUser, 'new_updated_password'));
    }

    public function testAdminCanDeleteUser(): void
    {
        $admin = $this->createAdminUser();
        $userToDelete = $this->createNormalUser();
        $this->client->loginUser($admin);

        // the form action will be in a post form.
        // We'll mimic the form submission using the crawler or directly using client.
        $this->client->request('GET', '/admin/users/'); // go to index to find the delete button

        // Assuming there's a delete button that contains the CSRF token
        $crawler = $this->client->getCrawler();
        $deleteForms = $crawler->filter('form[action="/admin/users/' . $userToDelete->getId() . '"]');

        if ($deleteForms->count() > 0) {
            $form = $deleteForms->first()->form();
            $this->client->submit($form);
        } else {
            // fallback if not on index page or structure differs: we can manually request it if we know how token is generated,
            // but generally we get it from DOM. Let's assume the controller test works with a known token generation:
            $container = static::getContainer();
            $csrfTokenManager = $container->get('security.csrf.token_manager');
            $token = $csrfTokenManager->getToken('delete'.$userToDelete->getId())->getValue();

            $this->client->request('POST', '/admin/users/' . $userToDelete->getId(), [
                '_token' => $token,
            ]);
        }

        $this->assertResponseRedirects('/admin/users/');

        $this->entityManager->clear();
        $deletedUser = $this->entityManager->getRepository(User::class)->find($userToDelete->getId());
        $this->assertNull($deletedUser);
    }
}
