<?php

namespace App\Controller;

use App\Entity\Apartment;
use App\Form\ApartmentType;
use App\Repository\ApartmentRepository;
use App\Service\VapiKnowledgeBaseService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ApartmentController extends AbstractController
{
    // ── Public route ─────────────────────────────────────────────────
    #[Route('/apartments', name: 'apartment_public_list', methods: ['GET'])]
    public function publicList(ApartmentRepository $apartmentRepository): Response
    {
        $apartments = $apartmentRepository->findAll();

        return $this->render('apartment/public_list.html.twig', [
            'apartments' => $apartments,
        ]);
    }

    // ── Admin routes ─────────────────────────────────────────────────
    #[Route('/admin/apartments', name: 'apartment_admin_index', methods: ['GET'])]
    public function index(ApartmentRepository $apartmentRepository): Response
    {
        $apartments = $apartmentRepository->findAll();

        return $this->render('apartment/index.html.twig', [
            'apartments' => $apartments,
        ]);
    }

    #[Route('/admin/apartments/{id}/edit', name: 'apartment_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        Apartment $apartment,
        EntityManagerInterface $entityManager,
        VapiKnowledgeBaseService $vapiService,
    ): Response {
        $form = $this->createForm(ApartmentType::class, $apartment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            // Sync updated apartments to Vapi Knowledge Base
            $vapiService->syncKnowledgeBase();

            $this->addFlash('success', 'Apartamento actualizado correctamente.');

            return $this->redirectToRoute('apartment_admin_index');
        }

        return $this->render('apartment/edit.html.twig', [
            'apartment' => $apartment,
            'form' => $form,
        ]);
    }
}
