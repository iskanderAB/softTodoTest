<?php

namespace App\Controller;

use App\Entity\Project;
use App\Form\ProjectType;
use App\Repository\ProjectRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use function PHPUnit\Framework\throwException;
#[Route('/')]
#[Route('/project')]
class ProjectController extends AbstractController
{
    #[Route('/', name: 'app_project_index', methods: ['GET','POST'])]
    public function index(Request $request, ProjectRepository $projectRepository): Response
    {
        if($request->isMethod("POST"))
        {
            $name = $request->request->get('name','');
            $fileName = $request->request->get('fileName','');
            $status = $request->request->get('status','');
            $projects = $projectRepository->filter($name, $status, $fileName);
        }else {
            $projects = $projectRepository->findAll();
        }
        return $this->render('project/index.html.twig', [
            'projects' => $projects,
            'names' => $projectRepository->findNames()
        ]);
    }

    #[Route('/new', name: 'app_project_new', methods: ['GET', 'POST'])]
    public function new(Request $request, ProjectRepository $projectRepository, SluggerInterface $slugger): Response
    {
        $project = new Project();
        $form = $this->createForm(ProjectType::class, $project);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $image = $form->get('image')->getData();
            if ($image) {
                $originalFilename = pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $image->guessExtension();
                try {
                    $image->move(
                        $this->getParameter('images_directory'),
                        $newFilename
                    );
                } catch (FileException $e) {
                    throw new FileException("file problem !");
                }
                $project->setImage($newFilename);
            }
            $project->setOwner($this->getUser());
            $projectRepository->save($project, true);
            return $this->redirectToRoute('app_project_index', [], Response::HTTP_SEE_OTHER);
        }
        return $this->renderForm('project/new.html.twig', [
            'project' => $project,
            'form' => $form,
        ]);
    }

    #[Route('/edit/{id}', name: 'app_project_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Project $project, MailerInterface $mailer, ProjectRepository $projectRepository, SluggerInterface $slugger, UserRepository $userRepository): Response
    {
        $status = $project->getStatus();
        $form = $this->createForm(ProjectType::class, $project);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $image = $form->get('image')->getData();
            if ($image) {
                $originalFilename = pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $image->guessExtension();
                try {
                    $image->move(
                        $this->getParameter('images_directory'),
                        $newFilename
                    );
                } catch (FileException $e) {
                    throw new FileException("file problem !");
                }
                $project->setImage($newFilename);
            }
            $projectRepository->save($project, true);
            if($status !== $project->getStatus()){
                $admins = $userRepository->findAdmins();
                if(is_array($admins) && count($admins)>0){
                    $admins = array_map(function ($element){ return $element['email']; }, $admins);
                    $email = (new Email())
                        ->from('hello@softtodo.com')
                        ->to(...$admins)
                        ->subject('status changed')
                        ->text('Hello 3arf')
                        ->html('<p> The status of project '. $project->getId().' is chenged to '.$project->getStatus().' ♥ </p>');
                    try {
                        $mailer->send($email);
                    } catch (TransportExceptionInterface $e) {
                        // log ...
                    }
                }
            }
            return $this->redirectToRoute('app_project_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('project/edit.html.twig', [
            'project' => $project,
            'form' => $form,
        ]);
    }



    #[Route('/show/{id}', name: 'app_project_show', methods: ['GET'])]
    public function show(Project $project): Response
    {
        return $this->render('project/show.html.twig', [
            'project' => $project,
        ]);
    }



    #[Route('/delete/{id}', name: 'app_project_delete', methods: ['POST'])]
    public function delete(Request $request, Project $project, ProjectRepository $projectRepository): Response
    {
        if ($this->isCsrfTokenValid('delete'.$project->getId(), $request->request->get('_token'))) {
            $projectRepository->remove($project, true);
        }

        return $this->redirectToRoute('app_project_index', [], Response::HTTP_SEE_OTHER);
    }
}
