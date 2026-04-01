<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Listing;
use App\Form\ListingType;
use App\Entity\Category;
use App\Form\CategoryType;
use App\Form\EditProfileType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use App\Repository\ListingRepository;
use App\Repository\UserRepository;
use App\Repository\CategoryRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin')]
#[IsGranted('ROLE_ADMIN')]
final class AdminController extends AbstractController
{
    #[Route('', name: 'app_admin')]
    public function index(
        ListingRepository $listingRepository,
        UserRepository $userRepository,
        CategoryRepository $categoryRepository
    ): Response {
        return $this->render('admin/index.html.twig', [
            'listings' => $listingRepository->findAll(),
            'users' => $userRepository->findAll(),
            'categories' => $categoryRepository->findAll(),
        ]);
    }
    #[Route('/users', name: 'app_admin_users')]
    public function users(UserRepository $userRepository): Response
    {
        return $this->render('admin/users.html.twig', [
            'users' => $userRepository->findAll(),
        ]);
    }

    #[Route('/users/{id}/edit', name: 'app_admin_user_edit')]
    public function editUser(Request $request, User $user, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(EditProfileType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'Utilisateur modifié avec succès !');

            return $this->redirectToRoute('app_admin_users');
        }

        return $this->render('admin/user_edit.html.twig', [
            'form' => $form,
            'user' => $user,
        ]);
    }

    #[Route('/users/{id}/delete', name: 'app_admin_user_delete', methods: ['POST'])]
    public function deleteUser(Request $request, User $user, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $user->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($user);
            $entityManager->flush();
            $this->addFlash('success', 'Utilisateur supprimé avec succès !');
        }

        return $this->redirectToRoute('app_admin_users');
    }

    #[Route('/users/{id}/role', name: 'app_admin_user_role', methods: ['POST'])]
    public function editUserRole(Request $request, User $user, EntityManagerInterface $entityManager): Response
    {
        $role = $request->request->get('role');
        $user->setRoles($role ? [$role] : []);
        $entityManager->flush();
        $this->addFlash('success', 'Rôle modifié avec succès !');

        return $this->redirectToRoute('app_admin_users');
    }
    #[Route('/listings', name: 'app_admin_listings')]
    public function listings(ListingRepository $listingRepository): Response
    {
        return $this->render('admin/listings.html.twig', [
            'listings' => $listingRepository->findAll(),
        ]);
    }

    #[Route('/listings/{id}/delete', name: 'app_admin_listing_delete', methods: ['POST'])]
    public function deleteListing(Request $request, Listing $listing, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $listing->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($listing);
            $entityManager->flush();
            $this->addFlash('success', 'Annonce supprimée avec succès !');
        }

        return $this->redirectToRoute('app_admin_listings');
    }

    #[Route('/categories', name: 'app_admin_categories')]
    public function categories(CategoryRepository $categoryRepository): Response
    {
        return $this->render('admin/categories.html.twig', [
            'categories' => $categoryRepository->findAll(),
        ]);
    }

    #[Route('/categories/new', name: 'app_admin_category_new')]
    public function newCategory(Request $request, EntityManagerInterface $entityManager): Response
    {
        $category = new Category();
        $form = $this->createForm(CategoryType::class, $category);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($category);
            $entityManager->flush();
            $this->addFlash('success', 'Catégorie créée avec succès !');

            return $this->redirectToRoute('app_admin_categories');
        }

        return $this->render('admin/category_new.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/categories/{id}/delete', name: 'app_admin_category_delete', methods: ['POST'])]
    public function deleteCategory(Request $request, Category $category, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $category->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($category);
            $entityManager->flush();
            $this->addFlash('success', 'Catégorie supprimée avec succès !');
        }

        return $this->redirectToRoute('app_admin_categories');
    }
}
