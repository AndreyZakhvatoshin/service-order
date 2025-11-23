<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\OrderDto;
use App\Entity\Order;
use App\Repository\ServiceRepository;
use Doctrine\ORM\EntityManagerInterface;

class OrderCreator
{
    private EntityManagerInterface $em;
    private ServiceRepository $serviceRepository;

    public function __construct(EntityManagerInterface $em, ServiceRepository $serviceRepository)
    {
        $this->em = $em;
        $this->serviceRepository = $serviceRepository;
    }

    public function createOrder(OrderDto $dto): Order
    {
        $order = new Order();
        $order->setEmail($dto->email);

        $service = $this->serviceRepository->find($dto->serviceId);
        $order->setServiceId($service);

        $this->em->persist($order);
        $this->em->flush();

        return $order;
    }
}
