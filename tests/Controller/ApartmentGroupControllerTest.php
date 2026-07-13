<?php

namespace App\Tests\Controller;

use App\Infrastructure\Persistence\Doctrine\Entity\ApartmentGroup;
use App\Infrastructure\Persistence\Doctrine\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class ApartmentGroupControllerTest extends WebTestCase
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

    private function createApartmentGroup(string $name, ?ApartmentGroup $parent = null): ApartmentGroup
    {
        $group = new ApartmentGroup();
        $group->setName($name);
        if ($parent) {
            $group->setParent($parent);
        }

        $this->entityManager->persist($group);
        $this->entityManager->flush();

        return $group;
    }

    public function testAnonymousUserIsRedirected(): void
    {
        $this->client->request('GET', '/admin/apartment-groups/');
        $this->assertResponseRedirects('/login'); // Assuming standard symfony login route
    }

    public function testNormalUserIsForbidden(): void
    {
        $user = $this->createNormalUser();
        $this->client->loginUser($user);

        $this->client->request('GET', '/admin/apartment-groups/');
        $this->assertResponseStatusCodeSame(403);
    }

    public function testAdminCanAccessIndex(): void
    {
        $admin = $this->createAdminUser();
        $this->client->loginUser($admin);

        $this->client->request('GET', '/admin/apartment-groups/');
        $this->assertResponseIsSuccessful();
    }

    public function testAdminCanCreateApartmentGroup(): void
    {
        $admin = $this->createAdminUser();
        $this->client->loginUser($admin);

        $crawler = $this->client->request('GET', '/admin/apartment-groups/new');
        $this->assertResponseIsSuccessful();

        $form = $crawler->selectButton('Guardar Grupo')->form([
            'apartment_group[name]' => 'New Apartment Group',
        ]);

        $this->client->submit($form);
        $this->assertResponseRedirects('/admin/apartment-groups/');

        $group = $this->entityManager->getRepository(ApartmentGroup::class)->findOneBy(['name' => 'New Apartment Group']);
        $this->assertNotNull($group);
    }

    public function testAdminCanEditApartmentGroup(): void
    {
        $admin = $this->createAdminUser();
        $group = $this->createApartmentGroup('Old Group Name');
        $this->client->loginUser($admin);

        $crawler = $this->client->request('GET', '/admin/apartment-groups/' . $group->getId() . '/edit');
        $this->assertResponseIsSuccessful();

        $form = $crawler->selectButton('Actualizar Grupo')->form([
            'apartment_group[name]' => 'Updated Group Name',
        ]);

        $this->client->submit($form);
        $this->assertResponseRedirects('/admin/apartment-groups/');

        $this->entityManager->clear();
        $updatedGroup = $this->entityManager->getRepository(ApartmentGroup::class)->find($group->getId());
        $this->assertEquals('Updated Group Name', $updatedGroup->getName());
    }

    public function testAdminCanDeleteApartmentGroup(): void
    {
        $admin = $this->createAdminUser();
        $group = $this->createApartmentGroup('Group To Delete');
        $this->client->loginUser($admin);

        // Request index to render the form with the token
        $this->client->request('GET', '/admin/apartment-groups/');

        $crawler = $this->client->getCrawler();
        $deleteForms = $crawler->filter('form[action="/admin/apartment-groups/' . $group->getId() . '"]');

        if ($deleteForms->count() > 0) {
            $form = $deleteForms->first()->form();
            $this->client->submit($form);
        } else {
            // Alternatively mock CSRF for the token if form is not found (which happens if index doesn't list the delete form)
            $token = $this->client->getContainer()->get('security.csrf.token_manager')->getToken('delete'.$group->getId())->getValue();

            $this->client->request('POST', '/admin/apartment-groups/' . $group->getId(), [
                '_token' => $token,
            ]);
        }

        $this->assertResponseRedirects('/admin/apartment-groups/');

        $this->entityManager->clear();
        $deletedGroup = $this->entityManager->getRepository(ApartmentGroup::class)->find($group->getId());
        $this->assertNull($deletedGroup);
    }
}
