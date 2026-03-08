<?php

namespace App\Controller;

use App\Application\Apartment\Command\SyncKnowledgeBaseCommand;
use App\Application\Apartment\Command\UpdateApartmentCommand;
use App\Application\Apartment\Query\GetAllApartmentsQuery;
use App\Form\ApartmentType;
use App\Infrastructure\Persistence\Doctrine\Entity\Apartment as DoctrineApartment;
use App\Domain\Apartment\Apartment as DomainApartment;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ApartmentController extends AbstractController
{
    // ── Public route ─────────────────────────────────────────────────
    #[Route('/apartments', name: 'apartment_public_list', methods: ['GET'])]
    public function publicList(GetAllApartmentsQuery $getAllApartmentsQuery): Response
    {
        $apartments = $getAllApartmentsQuery->execute();

        return $this->render('apartment/public_list.html.twig', [
            'apartments' => $apartments,
        ]);
    }

    // ── Admin routes ─────────────────────────────────────────────────
    #[Route('/admin/apartments', name: 'apartment_admin_index', methods: ['GET'])]
    public function index(GetAllApartmentsQuery $getAllApartmentsQuery): Response
    {
        $apartments = $getAllApartmentsQuery->execute();

        return $this->render('apartment/index.html.twig', [
            'apartments' => $apartments,
        ]);
    }

    #[Route('/admin/apartments/{id}/edit', name: 'apartment_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        DoctrineApartment $doctrineApartment,
        UpdateApartmentCommand $updateApartmentCommand,
        SyncKnowledgeBaseCommand $syncKnowledgeBaseCommand,
    ): Response {
        $form = $this->createForm(ApartmentType::class, $doctrineApartment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Map Doctrine to Domain
            $domainApartment = new DomainApartment(
                $doctrineApartment->getName(),
                $doctrineApartment->getAddress(),
                $doctrineApartment->getPrice(),
                $doctrineApartment->isAvailable(),
                $doctrineApartment->getId()
            );

            $updateApartmentCommand->execute($domainApartment);

            // Sync updated apartments to Vapi Knowledge Base
            $syncKnowledgeBaseCommand->execute();

            $this->addFlash('success', 'Apartamento actualizado correctamente.');

            return $this->redirectToRoute('apartment_admin_index');
        }

        return $this->render('apartment/edit.html.twig', [
            'apartment' => $doctrineApartment,
            'form' => $form,
        ]);
    }
}
