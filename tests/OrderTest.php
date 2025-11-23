<?php

namespace App\Tests;

use App\DataFixtures\ServiceFixtures;
use App\Entity\User;
use App\Entity\Service;
use Doctrine\Common\DataFixtures\Loader;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class OrderTest extends WebTestCase
{
    private $client;
    private $entityManager;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->entityManager = $this->client->getContainer()->get(EntityManagerInterface::class);

        $this->resetDatabase();
        $this->loadFixtures();
    }

    private function resetDatabase(): void
    {
        $metadata = $this->entityManager->getMetadataFactory()->getAllMetadata();
        if (!empty($metadata)) {
            $schemaTool = new SchemaTool($this->entityManager);
            $schemaTool->dropSchema($metadata);
            $schemaTool->createSchema($metadata);
        }
    }

    private function loadFixtures(): void
    {
        $loader = new Loader();
        $loader->addFixture(new ServiceFixtures());
        $purger = new ORMPurger($this->entityManager);
        $executor = new \Doctrine\Common\DataFixtures\Executor\ORMExecutor(
            $this->entityManager,
            $purger
        );
        $executor->execute($loader->getFixtures());
    }

    private function createAndLoginUser(string $email = 'test@example.com', string $password = 'password123'): User
    {
        $passwordHasher = $this->client->getContainer()->get(UserPasswordHasherInterface::class);
        $user = new User();
        $user->setEmail($email);
        $user->setPassword($passwordHasher->hashPassword($user, $password));

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $this->client->loginUser($user);

        return $user;
    }

    public function testProtectedRoutesRedirectUnauthorizedUserToLogin(): void
    {
        foreach (['/orders', '/order/create'] as $url) {
            $this->client->request('GET', $url);
            $this->assertResponseRedirects('/login');
        }
    }

    public function testPublicRoutesAreAccessible(): void
    {
        foreach (['/login', '/register'] as $url) {
            $this->client->request('GET', $url);
            $this->assertResponseIsSuccessful();
        }
    }

    public function testAuthenticatedUserCanAccessOrderCreationForm(): void
    {
        $this->createAndLoginUser();

        $crawler = $this->client->request('GET', '/order/create');
        $this->assertResponseIsSuccessful();

        $form = $crawler->selectButton('Заказать')->form();
        $this->assertNotNull($form, 'Form button not found');

        $this->assertSelectorExists('form[name="create_order"]');
        $this->assertSelectorExists('input[name="create_order[email]"]');
        $this->assertSelectorExists('select[name="create_order[serviceId]"]');
    }

    public function testOrderCreationWithoutEmail(): void
    {
        $this->createAndLoginUser();
        $crawler = $this->client->request('GET', '/order/create');
        $this->assertResponseIsSuccessful();

        $service = $this->entityManager
            ->getRepository(Service::class)
            ->findOneBy(['name' => 'оценка стоимости автомобиля']);

        $this->assertNotNull($service, 'Service should exist in the database after loading fixtures');

        $form = $crawler->selectButton('Заказать')->form();
        $form['create_order[email]'] = '';
        $form['create_order[serviceId]'] = $service->getId();

        $this->client->submit($form);
        $this->assertResponseStatusCodeSame(200);

        $this->assertStringContainsString(
            'Email не может быть пустым',
            $this->client->getCrawler()->filter('html')->text()
        );
    }

    public function testOrderCreationWithoutService(): void
    {
        $this->createAndLoginUser();
        $crawler = $this->client->request('GET', '/order/create');
        $this->assertResponseIsSuccessful();

        $form = $crawler->selectButton('Заказать')->form();
        $form['create_order[email]'] = 'test@example.com';
        $form['create_order[serviceId]'] = '';

        $this->client->submit($form);
        $this->assertResponseStatusCodeSame(200);

        $this->assertStringContainsString(
            'Пожалуйста, выберите услугу',
            $this->client->getCrawler()->filter('html')->text()
        );
    }

    public function testOrderCreationWithValidData(): void
    {
        $this->createAndLoginUser();

        $crawler = $this->client->request('GET', '/order/create');
        $this->assertResponseIsSuccessful();

        $service = $this->entityManager
            ->getRepository(Service::class)
            ->findOneBy(['name' => 'оценка стоимости квартиры']);

        $this->assertNotNull($service, 'Service should exist in the database after loading fixtures');

        $form = $crawler->selectButton('Заказать')->form();
        $form['create_order[email]'] = 'valid@example.com';
        $form['create_order[serviceId]'] = $service->getId();

        $this->client->submit($form);
        $this->assertResponseRedirects('/orders');

        $crawler = $this->client->followRedirect();
        $this->assertSelectorTextContains('body', 'valid@example.com');

        $orderRepository = $this->entityManager->getRepository(\App\Entity\Order::class);
        $order = $orderRepository->findOneBy(['email' => 'valid@example.com']);

        $this->assertNotNull($order);
        $this->assertSame($service->getId(), $order->getServiceId()->getId());
        $this->assertSame('valid@example.com', $order->getEmail());
    }
}
