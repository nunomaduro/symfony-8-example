<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Article;
use App\Form\ArticleType;
use App\Repository\ArticleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/admin/articles')]
#[IsGranted('ROLE_ADMIN')]
class ArticleController extends AbstractController
{
    #[Route('', name: 'admin_article_index', methods: ['GET'])]
    public function index(ArticleRepository $articleRepository): Response
    {
        return $this->render('admin/article/index.html.twig', [
            'articles' => $articleRepository->findAllOrderedByNewest(),
        ]);
    }

    #[Route('/new', name: 'admin_article_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager,
        SluggerInterface $slugger,
        ArticleRepository $articleRepository,
    ): Response {
        $article = new Article();

        $form = $this->createForm(ArticleType::class, $article);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $baseSlug = $slugger->slug($article->getTitle())->lower()->toString();
            $article->setSlug($articleRepository->uniqueSlug($baseSlug));

            $entityManager->persist($article);
            $entityManager->flush();

            $this->addFlash('success', 'Article created successfully.');

            return $this->redirectToRoute('admin_article_index');
        }

        return $this->render('admin/article/form.html.twig', [
            'form' => $form,
            'is_edit' => false,
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_article_edit', methods: ['GET', 'POST'])]
    public function edit(
        Article $article,
        Request $request,
        EntityManagerInterface $entityManager,
        SluggerInterface $slugger,
        ArticleRepository $articleRepository,
    ): Response {
        $form = $this->createForm(ArticleType::class, $article);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $baseSlug = $slugger->slug($article->getTitle())->lower()->toString();
            $article->setSlug($articleRepository->uniqueSlug($baseSlug, $article->getId()));

            $entityManager->flush();

            $this->addFlash('success', 'Article updated successfully.');

            return $this->redirectToRoute('admin_article_index');
        }

        return $this->render('admin/article/form.html.twig', [
            'form' => $form,
            'is_edit' => true,
        ]);
    }

    #[Route('/{id}', name: 'admin_article_delete', methods: ['DELETE'])]
    public function delete(
        Article $article,
        Request $request,
        EntityManagerInterface $entityManager,
    ): Response {
        if ($this->isCsrfTokenValid('delete-'.$article->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($article);
            $entityManager->flush();

            $this->addFlash('success', 'Article deleted successfully.');
        }

        return $this->redirectToRoute('admin_article_index');
    }
}
