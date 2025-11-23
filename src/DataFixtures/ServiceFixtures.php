<?php

namespace App\DataFixtures;

use App\Entity\Service;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class ServiceFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $service1 = new Service();
        $service1->setName('оценка стоимости автомобиля');
        $service1->setPrice('49.99');
        $manager->persist($service1);

        $service2 = new Service();
        $service2->setName('оценка стоимости квартиры');
        $service2->setPrice('99.99');
        $manager->persist($service2);

        $service3 = new Service();
        $service3->setName('оценка стоимости бизнеса');
        $service3->setPrice('29.99');
        $manager->persist($service3);

        $manager->flush();

        $manager->flush();
    }
}
