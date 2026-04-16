<?php

namespace App\Controller;

use App\Entity\Listing;
use App\Form\ListingType;
use App\Repository\ListingRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/listing')]
#[IsGranted('ROLE_USER')]
final class ListingController extends AbstractController
{
    #[Route(name: 'app_listing_index', methods: ['GET'])]
    public function index(ListingRepository $listingRepository): Response
    {
        return $this->render('listing/index.html.twig', [
            'listings' => $listingRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_listing_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $listing = new Listing();
        $form = $this->createForm(ListingType::class, $listing);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $photoFile = $form->get('photo')->getData();
            if ($photoFile) {
                $originalFilename = pathinfo($photoFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $photoFile->guessExtension();

                $photoFile->move(
                    $this->getParameter('photos_directory'),
                    $newFilename
                );

                $listing->setPhotoPath('uploads/photos/' . $newFilename);
            }

            $listing->setAuthor($this->getUser());
            $entityManager->persist($listing);
            $entityManager->flush();

            $this->addFlash('success', 'Votre annonce a bien été créée.');

            return $this->redirectToRoute('app_listing_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('listing/new.html.twig', [
            'listing' => $listing,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_listing_show', methods: ['GET'])]
    public function show(Listing $listing): Response
    {
        return $this->render('listing/show.html.twig', [
            'listing' => $listing,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_listing_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Listing $listing, EntityManagerInterface $entityManager): Response
    {

        if ($listing->getAuthor() !== $this->getUser() && !$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas modifier cette annonce.');
        }

        $form = $this->createForm(ListingType::class, $listing);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Votre annonce a bien été publiée.');

            return $this->redirectToRoute('app_listing_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('listing/edit.html.twig', [
            'listing' => $listing,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_listing_delete', methods: ['POST'])]
    public function delete(Request $request, Listing $listing, EntityManagerInterface $entityManager): Response
    {

        if ($listing->getAuthor() !== $this->getUser() && !$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas supprimer cette annonce.');
        }

        if ($this->isCsrfTokenValid('delete' . $listing->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($listing);
            $entityManager->flush();

            $this->addFlash('success', 'Votre annonce a bien été supprimée.');
        }

        return $this->redirectToRoute('app_listing_index', [], Response::HTTP_SEE_OTHER);
    }
}
