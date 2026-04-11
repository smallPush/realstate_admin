<?php

namespace App\Controller;

use App\Domain\ApartmentGroup\ApartmentGroupRepositoryInterface;
use App\Form\ApartmentGroupType;
use App\Infrastructure\Persistence\Doctrine\Entity\ApartmentGroup;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/apartment-groups')]
#[IsGranted('ROLE_ADMIN')]
class ApartmentGroupController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ApartmentGroupRepositoryInterface $apartmentGroupRepository
    ) {
    }

    #[Route('/', name: 'apartment_group_index', methods: ['GET'])]
    public function index(): Response
    {
        $groups = $this->entityManager->getRepository(ApartmentGroup::class)->findAll();

        return $this->render('apartment_group/index.html.twig', [
            'groups' => $groups,
        ]);
    }

    #[Route('/new', name: 'apartment_group_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $group = new ApartmentGroup();
        $form = $this->createForm(ApartmentGroupType::class, $group);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($group);
            $this->entityManager->flush();

            $this->addFlash('success', 'Grupo creado correctamente.');

            return $this->redirectToRoute('apartment_group_index');
        }

        return $this->render('apartment_group/new.html.twig', [
            'group' => $group,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/edit', name: 'apartment_group_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, ApartmentGroup $group): Response
    {
        $form = $this->createForm(ApartmentGroupType::class, $group);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();

            $this->addFlash('success', 'Grupo actualizado correctamente.');

            return $this->redirectToRoute('apartment_group_index');
        }

        return $this->render('apartment_group/edit.html.twig', [
            'group' => $group,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'apartment_group_delete', methods: ['POST'])]
    public function delete(Request $request, ApartmentGroup $group): Response
    {
        if ($this->isCsrfTokenValid('delete'.$group->getId(), $request->request->get('_token'))) {
            $this->entityManager->remove($group);
            $this->entityManager->flush();
            $this->addFlash('success', 'Grupo eliminado correctamente.');
        }

        return $this->redirectToRoute('apartment_group_index');
    }
}
