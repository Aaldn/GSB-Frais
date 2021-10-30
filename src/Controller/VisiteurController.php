<?php

namespace App\Controller;

use App\Entity\Etat;
use App\Entity\Fichefrais;
use App\Entity\Fraisforfait;
use App\Entity\Lignefraisforfait;
use App\Entity\Lignefraishorsforfait;
use App\Entity\Visiteur;
use App\Form\FichefraisType;
use App\Form\FraisforfaitType;
use App\Form\LignefraisforfaitType;
use App\Form\LignefraishorsforfaitType;
use App\Repository\FichefraisRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;


class VisiteurController extends AbstractController
{
    public function index(Request $request): Response
    {
        return $this->render('visiteur/index.html.twig', [
            'controller_name' => 'VisiteurController',
        ]);
    }

    public function saisirFicheFrais(Request $request, EntityManagerInterface $em): Response
    {
        $session = $request->getSession();
        $session->start();
        $idvisiteur = $session->get('utilisateur')['id'];

        $visiteur = $em->getRepository(Visiteur::class)->find($idvisiteur);
        $fraisForfaits = $em->getRepository(Fraisforfait::class)->findAllAsc();
        $ficheFrais = $em->getRepository(Fichefrais::class)->findFichefraisCourante($idvisiteur);
        $ligneFraisForfaits = $em->getRepository(Lignefraisforfait::class)->findByFichefrais($idvisiteur);
        $fraisHorsForfaits = $em->getRepository(Lignefraishorsforfait::class)->findByFichefrais($idvisiteur);

        $ficheFraisInstance = new Fichefrais();
        $formFichefrais = $this->createForm(FichefraisType::class, $ficheFraisInstance);
        $formFichefrais->handleRequest($request);

        $ligneFraisForfaitInstance = new Lignefraisforfait();
        $formLigneFraisForfait = $this->createForm(LignefraisforfaitType::class, $ligneFraisForfaitInstance);
        $formLigneFraisForfait->handleRequest($request);

        $fraisHorsForfaitInstance = new Lignefraishorsforfait();
        $formFraisHorsForfait = $this->createForm(LignefraishorsforfaitType::class, $fraisHorsForfaitInstance);
        $formFraisHorsForfait->handleRequest($request);

        $fraisForfaitInstance = new Fraisforfait();
        $formFraisForfait = $this->createForm(FraisforfaitType::class, $fraisForfaitInstance);
        $formFraisForfait->handleRequest($request);

        if (!$ficheFrais) {
            $ficheFrais = $this->creerFichefrais($ficheFrais, $visiteur);
        }

        if (!$ligneFraisForfaits) {
            $ligneFraisForfaits = $this->creerLignefraisforfaits($ligneFraisForfaits, $ficheFrais, $fraisForfaits);
        }

        if ($formFraisForfait->isSubmitted() && $formFraisForfait->isValid()) {
            $i = 0;
            foreach ($formFraisForfait->getData()->getLignefraisforfaits() as $ligneFraisForfaitInput) {
                $ligneFraisForfaits[$i]->setQuantite($ligneFraisForfaitInput->getQuantite());
                $em->persist($ligneFraisForfaits[$i]);
                $i++;
            }
            $em->flush();

            return $this->redirectToRoute('saisir_fiche_frais');
        }

        if ($formFraisHorsForfait->isSubmitted() && $formFraisHorsForfait->isValid()) {
            $fraisHorsForfaitInstance->setIdvisiteur($ficheFrais->getIdvisiteur()->getId());
            $fraisHorsForfaitInstance->setMois($ficheFrais->getMois());
            $fraisHorsForfaitInstance->setIdfichefrais($ficheFrais);
            $em->persist($formFraisHorsForfait->getData());
            $em->flush();

            return $this->redirectToRoute('saisir_fiche_frais');
        }

        return $this->render('visiteur/saisir_fiche_frais.html.twig', [
            'controller_name' => 'VisiteurController',
            'formFichefrais' => $formFichefrais->createView(),
            'formLigneFraisForfait' => $formLigneFraisForfait->createView(),
            'formFraisHorsForfait' => $formFraisHorsForfait->createView(),
            'formFraisForfait' => $formFraisForfait->createView(),
            'ficheFrais' => $ficheFrais,
            'ligneFraisForfaits' => $ligneFraisForfaits,
            'fraisHorsForfaits' => $fraisHorsForfaits,
            'fraisForfaits' => $fraisForfaits
        ]);
    }

    public function consulterFicheFrais(Request $request, FichefraisRepository $ficheFraisRepository): Response
    {
        $fichesFrais = $ficheFraisRepository->findAll();

        $ficheFraisInstance = new Fichefrais();
        $formFichefrais = $this->createForm(FichefraisType::class, $ficheFraisInstance);
        $formFichefrais->handleRequest($request);

        return $this->render('visiteur/consulter_fiche_frais.html.twig', [
            'controller_name' => 'VisiteurController',
            'formFichefrais' => $formFichefrais->createView(),
            'fichesFrais' => $fichesFrais
        ]);
    }

    private function creerLignefraisforfaits($ligneFraisForfaits, $ficheFrais, $fraisForfaits)
    {
        $em = $this->getDoctrine()->getManager();
        for ($i = 0; $i < 4; $i++) {
            // dd($ligneFraisForfaits);
            array_push($ligneFraisForfaits, new Lignefraisforfait);
            $ligneFraisForfaits[$i]->setFicheFrais($ficheFrais);
            $ligneFraisForfaits[$i]->setFraisForfait($fraisForfaits[$i]);
            $ligneFraisForfaits[$i]->setQuantite(0);
            $em->persist($ligneFraisForfaits[$i]);
        }
        $em->flush();

        return $ligneFraisForfaits;
    }

    private function creerFichefrais($ficheFrais, $visiteur)
    {
        $em = $this->getDoctrine()->getManager();
        $ficheFrais = new ficheFrais();
        $ficheFrais->setIdvisiteur($visiteur);
        $ficheFrais->setIdetat($em->getRepository(Etat::class)->find('CR'));
        $em->persist($ficheFrais);
        $em->flush();

        return $ficheFrais;
    }

    public function supprimerFraisHorsForfait(Request $request, Lignefraishorsforfait $lignefraishorsforfait): Response
    {
        if ($this->isCsrfTokenValid('delete' . $lignefraishorsforfait->getId(), $request->request->get('supprimer_fraishorsforfait_token'))) {
            $em = $this->getDoctrine()->getManager();
            $em->remove($lignefraishorsforfait);
            $em->flush();
        }

        return $this->redirectToRoute('saisir_fiche_frais', [], Response::HTTP_SEE_OTHER);
    }
}
