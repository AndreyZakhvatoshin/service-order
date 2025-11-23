<?php

namespace App\Controller;

use App\Dto\OrderDto;
use App\Entity\Order;
use App\Entity\Service;
use App\Form\CreateOrderType;
use App\Repository\OrderRepository;
use App\Repository\ServiceRepository;
use App\Service\OrderCreator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Request;

final class OrderController extends AbstractController
{
    #[Route('/orders', name: 'app_orders', methods: ['GET'])]
    public function index(OrderRepository $orderRepository): Response
    {
        $orders = $orderRepository->findAll();

        return $this->render('/order/index.html.twig', [
            'orders' => $orders,
        ]);
    }

    #[Route('/order/create', name: 'app_order_create', methods: ['GET', 'POST'])]
    public function store(Request $request, OrderCreator $orderCreator, ServiceRepository $serviceRepository): Response
    {
        $services = $serviceRepository->findAll();
        $serviceChoices = [];
        foreach ($services as $service) {
            $serviceChoices[$service->getName()] = $service->getId();
        }

        $form = $this->createForm(CreateOrderType::class, null, [
            'service_choices' => $serviceChoices,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $orderCreator->createOrder($form->getData());
            return $this->redirectToRoute('app_orders');
        }

        return $this->render('order/create.html.twig', [
            'form' => $form->createView(),
            'services' => $services,
        ]);
    }
}
