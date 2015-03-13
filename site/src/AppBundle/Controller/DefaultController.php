<?php

namespace AppBundle\Controller;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use AppBundle\Entity\YoutubeMovie;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use GuzzleHttp\Client as GuzzleClient;


class DefaultController extends Controller
{
    /**
     * @Route("/", name="homepage")
     */
    public function indexAction()
    {
        /** @var EntityManager $em */
        $em = $this->getDoctrine()->getManager();

        /** @var EntityRepository $ytRepository */
        $ytRepository = $em->getRepository('AppBundle:YoutubeMovie');
        $yt = $ytRepository->findOneBy(['played' => 0, 'skipped' => 0]);

        if (is_null($yt)) {
            return $this->render('default/playlist-empty.html.twig');
        } else {
            return $this->render('default/index.html.twig', ['video' => $yt]);
        }
    }

    /**
     * @Route("/ajax/load-videos", name="load-more")
     * @Method("GET")
     */
    public function ajaxLoadVideoAction()
    {
        /** @var EntityManager $em */
        $em = $this->getDoctrine()->getManager();

        /** @var EntityRepository $ytRepository */
        $ytRepository = $em->getRepository('AppBundle:YoutubeMovie');
        $ytMovies = $ytRepository->findBy(['played' => 0, 'skipped' => 0]);

        $data = [];
        /** @var YoutubeMovie  $movie */
        foreach ($ytMovies as $k => $movie) {
            $data[$k]['title'] = $movie->getTitle();
        }

        return new JsonResponse($data);
    }

    /**
     * @Route("/ajax/set-done/{id}")
     * @Method("GET")
     */
    public function ajaxSetVideoDone($id)
    {
        /** @var EntityManager $em */
        $em = $this->getDoctrine()->getManager();

        /** @var EntityRepository $ytRepository */
        $ytRepository = $em->getRepository('AppBundle:YoutubeMovie');

        /** @var YoutubeMovie $youtube */
        $youtube = $ytRepository->find($id);
        $youtube->setPlayed();

        $em->flush();
        return new Response();
    }

    /**
     * @Route("/add", name="add-movie")
     * @Method("POST")
     */
    public function postAction(Request $request)
    {
        $youtubeLink = $request->get('link');
        if (preg_match('%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/)([^"&?/ ]{11})%i', $youtubeLink, $match)) {
            $video_id = $match[1];
        }

        if (!isset($video_id)) {
          return new Response('Invalid url', 422);
        }

        $client = new GuzzleClient();

        $response = $client->get(
          'http://gdata.youtube.com/feeds/api/videos/' . $video_id,
          [
            'headers' => ['Content-Type' => 'text/xml'],
            'verify' => false,
            'timeout' => 5,
          ]
        );

        $xml = $response->xml();

        $yt = new YoutubeMovie($video_id, 100,$xml->title);

        /** @var EntityManager $entityManager */
        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->persist($yt);
        $entityManager->flush();

        return new Response("ok \n");
    }
}
