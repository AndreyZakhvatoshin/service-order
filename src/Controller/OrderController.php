<?php

namespace App\Controller;

use App\Entity\Order;
use App\Form\CreateOrderType;
use App\Repository\OrderRepository;
use App\Repository\ServiceRepository;
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
    public function store(Request $request, EntityManagerInterface $em, ServiceRepository $serviceRepository): Response
    {
        $order = new Order();
        $form = $this->createForm(CreateOrderType::class, $order);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($order);
            $em->flush();

            $this->addFlash('success', 'Заказ создан!');

            return $this->redirectToRoute('app_orders');
        }

        $services = $serviceRepository->findAll();
        return $this->render('order/create.html.twig', [
            'form' => $form->createView(),
            'services' => $services,
        ]);
    }
}
