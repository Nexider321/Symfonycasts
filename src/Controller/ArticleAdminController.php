<?php

namespace App\Controller;

use App\Entity\Article;
use App\Form\ArticleFormType;
use App\Repository\ArticleRepository;
use App\Repository\UserRepository;
use App\Service\UploaderHelper;
use Doctrine\ORM\EntityManagerInterface;
use Gedmo\Sluggable\Util\Urlizer;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ArticleAdminController extends AbstractController
{



    /**
     * @Route("/admin/article/new", name="admin_article_new")
     * @IsGranted("ROLE_ADMIN_ARTICLE")
     */
    public function new(EntityManagerInterface $em, Request $request, UploaderHelper $uploaderHelper)
    {
            $form = $this->createForm(ArticleFormType::class);

            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()){
                $article = $form->getData();


                $uploadedFile = $form['imageFile']->getData();
                if ($uploadedFile) {
                    $newFilename = $uploaderHelper->uploadArticleImage($uploadedFile);
                    $article->setImageFilename($newFilename);
                }

                $em->persist($article);
                $em->flush();
                $this->addFlash('success', 'Article Created! Knowledge is power!');

                return $this->redirectToRoute('admin_article_list');
            }

            return $this->render('article_admin/new.html.twig', [
               'articleForm' => $form->createView()
            ]);
    }

    /**
     * @param Request $request
     * @Route("/admin/upload/test", name="upload_test")
     */
    public function temporaryUploadAction(Request $request)
    {
        $uploadedFile = $request->files->get('image');
        $destination = $this->getParameter('kernel.project_dir').'/public/uploads';

        $originalFilename = pathinfo($uploadedFile->getClientOriginalName(), PATHINFO_FILENAME);
        $newFilename = Urlizer::urlize($originalFilename).'-'.uniqid().'.'.$uploadedFile->guessExtension();
        dd($uploadedFile->move(
            $destination,
            $newFilename
        ));

    }

    /**
     * @param Request $request
     * @Route("/admin/article/location-select", name="admin_article_location_select")
     */
    public function getSpecificLocationSelect(Request $request)
    {
        $article = new Article();
        $article->setLocation($request->query->get('location'));
        $form = $this->createForm(ArticleFormType::class, $article);
        if (!$form->has('specificLocationName')){
            return new Response(null, 204);
        }
        return $this->render('article_admin/_specific_location_name.html.twig', [
            'articleForm' => $form->createView()
        ]);
    }





    /**
     * @param EntityManagerInterface $em
     * @param Request $request
     * @Route("/admin/article/{id}/edit", name="admin_article_edit")
     * @IsGranted("ROLE_ADMIN_ARTICLE")
     */

    public function edit(Article $article, EntityManagerInterface $em, Request $request, UploaderHelper $uploaderHelper)
    {
        $form = $this->createForm(ArticleFormType::class, $article, [
            'include_published_at' => true
        ]);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()){

            $uploadedFile = $form['imageFile']->getData();
            if ($uploadedFile) {

                $newFilename = $uploaderHelper->uploadArticleImage($uploadedFile);
                $article->setImageFilename($newFilename);
            }
            $article = $form->getData();
            $em->persist($article);
            $em->flush();
            $this->addFlash('success', 'Article Updated! Boxing is power!');

            return $this->redirectToRoute('admin_article_edit', [
                'id' => $article->getId(),
            ]);
        }

        return $this->render('article_admin/edit.html.twig', [
            'articleForm' => $form->createView()
        ]);

    }

    /**
     * @Route("/admin/article", name="admin_article_list")
     */
    public function list(ArticleRepository $articleRepo)
    {
        $articles = $articleRepo->findAll();

        return $this->render('article_admin/list.html.twig', [
            'articles' => $articles
        ]);
    }


}
