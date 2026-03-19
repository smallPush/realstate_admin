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
    public function __construct(
        private readonly UpdateApartmentCommand $updateApartmentCommand,
        private readonly SyncKnowledgeBaseCommand $syncKnowledgeBaseCommand,
    ) {
    }

    // ── Public route ─────────────────────────────────────────────────
    #[Route('/', name: 'apartment_public_list', methods: ['GET'])]
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
        $user = $this->getUser();
        if (!$user) {
            throw $this->createAccessDeniedException();
        }

        if ($this->isGranted('ROLE_ADMIN')) {
            $apartments = $getAllApartmentsQuery->execute();
        } else {
            $groupIds = [];
            foreach ($user->getApartmentGroups() as $group) {
                // To support true hierarchy we would fetch all children IDs as well.
                // For simplicity, we just fetch IDs of directly assigned groups + their direct children here.
                // A better approach would be a recursive function in the repository, but this is a start.
                $groupIds[] = $group->getId();
                foreach ($group->getChildren() as $childGroup) {
                    $groupIds[] = $childGroup->getId();
                    // Let's go one more level deep just in case
                    foreach ($childGroup->getChildren() as $grandChildGroup) {
                        $groupIds[] = $grandChildGroup->getId();
                    }
                }
            }
            $groupIds = array_unique($groupIds);
            $apartments = $getAllApartmentsQuery->execute($groupIds);
        }

        return $this->render('apartment/index.html.twig', [
            'apartments' => $apartments,
        ]);
    }

    #[Route('/admin/apartments/{id}/edit', name: 'apartment_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        DoctrineApartment $doctrineApartment,
    ): Response {
        if (!$this->isGranted('ROLE_ADMIN')) {
            $user = $this->getUser();
            $hasAccess = false;

            $userGroupIds = [];
            foreach ($user->getApartmentGroups() as $group) {
                $userGroupIds[] = $group->getId();
                foreach ($group->getChildren() as $childGroup) {
                    $userGroupIds[] = $childGroup->getId();
                    foreach ($childGroup->getChildren() as $grandChildGroup) {
                        $userGroupIds[] = $grandChildGroup->getId();
                    }
                }
            }

            foreach ($doctrineApartment->getApartmentGroups() as $aptGroup) {
                if (in_array($aptGroup->getId(), $userGroupIds)) {
                    $hasAccess = true;
                    break;
                }
            }

            if (!$hasAccess && !$doctrineApartment->getApartmentGroups()->isEmpty()) {
                throw $this->createAccessDeniedException('No tienes permiso para editar este apartamento.');
            }
        }
        $form = $this->createForm(ApartmentType::class, $doctrineApartment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Because domain apartment does not persist relations via application layer here easily,
            // we will flush the relations from Doctrine layer directly before calling command
            // Ideally this should go through Domain, but keeping it simple for now based on current structure.
            $em = $this->container->get('doctrine')->getManager();
            $em->persist($doctrineApartment);
            $em->flush();

            // Map Doctrine to Domain
            $domainApartment = new DomainApartment(
                $doctrineApartment->getName(),
                $doctrineApartment->getAddress(),
                $doctrineApartment->getPrice(),
                $doctrineApartment->isAvailable(),
                $doctrineApartment->getId()
            );

            $this->updateApartmentCommand->execute($domainApartment);

            // Sync updated apartments to Vapi Knowledge Base
            $this->syncKnowledgeBaseCommand->execute();

            $this->addFlash('success', 'Apartamento actualizado correctamente.');

            return $this->redirectToRoute('apartment_admin_index');
        }

        return $this->render('apartment/edit.html.twig', [
            'apartment' => $doctrineApartment,
            'form' => $form,
        ]);
    }
}
