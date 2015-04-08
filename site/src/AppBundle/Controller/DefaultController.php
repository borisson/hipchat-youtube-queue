<?php

namespace AppBundle\Controller;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use AppBundle\Entity\Repository\YoutubeMovieRepository;
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
        return $this->render('base.html.twig');
    }

    /**
     * @Route("/api/loadsong", name="load-song")
     * @Method("GET")
     */
    public function radiowiziapiLoadSongsAction()
    {
        /** @var EntityManager $em */
        $em = $this->getDoctrine()->getManager();

        /** @var YoutubeMovieRepository $ytRepository */
        $ytRepository = $em->getRepository('AppBundle:YoutubeMovie');
        /** @var YoutubeMovie $yt */
        $yt = $ytRepository->findOneBy(['played' => 0, 'skipped' => 0]);

        $diff = 0;
        if ($yt instanceof YoutubeMovie && $yt->getStartedTime() instanceof \DateTime && $yt->getStartedTime()->format('Y') !== '-0001') {
            $now = new \DateTime();
            $diff = $now->getTimestamp() - $yt->getStartedTime()->getTimestamp();
            return new JsonResponse(array('obj' => $yt->getDataForJson(), 'diff'=>$diff), 200);
        }

        if ($yt instanceof YoutubeMovie){
            return new JsonResponse(array('obj' => $yt->getDataForJson(), 'diff'=>$diff), 200);
        }

        // No results found, get 20 songs with most airtime, play a random song of those.
        $top20Songs = $ytRepository->findTopSongsWithMostAirtime();
        $randomTopSongKey = array_rand($top20Songs);
        $yt = $top20Songs[$randomTopSongKey];

        if ($yt instanceof YoutubeMovie){
            $this->addVideo('Random top hit', $yt->getYoutubeKey());
        }

        return new JsonResponse(array(),204);
    }

    /**
     * @Route("/api/loadlastsongs", name="load-last-songs")
     * @Method("GET")
     */
    public function radiowiziapiLoadLastSongsAction()
    {
        /** @var EntityManager $em */
        $em = $this->getDoctrine()->getManager();

        /** @var EntityRepository $ytRepository */
        $ytRepository = $em->getRepository('AppBundle:YoutubeMovie');
        $lastSongs = $ytRepository->findBy(['skipped' => 0, 'played' => 1],['id' => 'DESC'], 9);

        $data = [];
        /** @var YoutubeMovie  $movie */
        foreach ($lastSongs as $k => $movie) {
            $data[$k]['title'] = $movie->getTitle();
            $data[$k]['image'] = $movie->getImage();
            $data[$k]['requestname'] = $movie->getRequestName();
        }

        return new JsonResponse($data);
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
        $ytMovies = $ytRepository->findBy(['played' => 0, 'skipped' => 0], null, 9, 1);

        $data = [];
        /** @var YoutubeMovie  $movie */
        foreach ($ytMovies as $k => $movie) {
            $data[$k]['title'] = $movie->getTitle();
            $data[$k]['image'] = $movie->getImage();
            $data[$k]['requestname'] = $movie->getRequestName();
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
     * @Route("/ajax/start-playing/{id}")
     * @Method("GET")
     */
    public function ajaxLoad($id)
    {
        /** @var EntityManager $em */
        $em = $this->getDoctrine()->getManager();

        /** @var EntityRepository $ytRepository */
        $ytRepository = $em->getRepository('AppBundle:YoutubeMovie');

        /** @var YoutubeMovie $youtube */
        $youtube = $ytRepository->find($id);
        $youtube->startPlaying();

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
        if (preg_match(
          '%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/)([^"&?/ ]{11})%i',
          $youtubeLink,
          $match
        )) {
            $video_id = $match[1];
        }

        if (!isset($video_id)) {
            return new Response('Invalid url', 422);
        }

        $requestname = $request->get('requestname');

        $this->addVideo($requestname, $video_id);

        return new Response("ok \n");
    }

    /**
     * Add video to database.
     *
     * @param $requestName
     * @param $videoId
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function addVideo($requestName, $videoId)
    {

        $client = new GuzzleClient();

        $response = $client->get(
          'http://gdata.youtube.com/feeds/api/videos/' . $videoId . '?v=2&alt=jsonc&prettyprint=true',
          [
            'headers' => ['Content-Type' => 'text/json'],
            'verify' => false,
            'timeout' => 5,
          ]
        );

        $json = $response->json();
        $jsondata = $json['data'];
        $totalSeconds = $jsondata['duration'];

        //Check Belgian country check
        if(isset($jsondata['restrictions'])){
            foreach($jsondata['restrictions'] as $restriction){
                if($restriction['type'] == 'country' && $restriction['relationship'] == 'deny'){
                    if (strpos($restriction['countries'],'BE') !== false) {
                        return new Response("This video can't be added. Belgium is not allowed. :( \n");
                    }
                }
            }
        }

        //Embed check
        if(isset($jsondata['accessControl']['embed']) && $jsondata['accessControl']['embed'] != 'allowed'){
          return new Response("This video can't be added. The video is not embeddable. :( \n");
        }

        // Get correct filename of a thumbnail before attempting download
        if (isset($jsondata['thumbnail']['hqDefault'])) {
            $youtubeFileName = $jsondata['thumbnail']['hqDefault'];
        } else {
            $youtubeFileName = $jsondata['thumbnail']['sqDefault'];
        }

        if (!$this->checkImageExists($videoId)) {
            $this->downloadImage($videoId, $youtubeFileName);
        }

        // Check if we need to create a jingle
        $this->addJingle();

        // Create a new YoutubeMovie to be saved in database.
        $yt = new YoutubeMovie($videoId, $totalSeconds, $jsondata['title'], $requestName);

        /** @var EntityManager $entityManager */
        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->persist($yt);
        $entityManager->flush();
    }

    /**
     * Create a Jingle if there isn't one found in the previous 20 songs.
     *
     * The jingles are kept in an array in this function ($jingles).
     * Preferably these are fetched from a youtubeChannel that holds all the jingles.
     */
    private function addJingle()
    {
        $jingles = [
            'cx0R0XSGvTo',
            'b0k8nGCtdaU',
            'UHBZX8rNxPA',
            'K8GsVLwJIOs',
            'w2cjgThAexc',
        ];

        /** @var EntityManager $entityManager */
        $entityManager = $this->getDoctrine()->getManager();
        $movieRepo = $entityManager->getRepository('AppBundle:YoutubeMovie');

        $songs = $movieRepo->findBy([], ['id' => 'DESC'], 12);

        $needsJingle = true;

        /** @var YoutubeMovie $song */
        foreach ($songs as $song) {
            if (in_array($song->getYoutubeKey(), $jingles)) {
                $needsJingle = false;
            }
        }

        // There was no jingle in the past 20 songs, add a new one.
        if ($needsJingle) {
            // Get a random jingle from the array of jingles
            $randomJingle = array_rand($jingles);
            $jingleKey = $jingles[$randomJingle];

            $client = new GuzzleClient();

            $response = $client->get(
              'http://gdata.youtube.com/feeds/api/videos/' . $jingleKey . '?v=2&alt=jsonc&prettyprint=true',
              [
                'headers' => ['Content-Type' => 'text/json'],
                'verify' => false,
                'timeout' => 5,
              ]
            );

            $json = $response->json();
            $jsondata = $json['data'];
            $totalSeconds = $jsondata['duration'];

            // Create a new YoutubeMovie to be saved in database.
            $jingle = new YoutubeMovie($jingleKey, $totalSeconds, $jsondata['title'], 'Radio wizi');
            $entityManager->persist($jingle);
        }
    }

    /**
     * Check if the file exists before downloading it.
     */
    private function checkImageExists($video_id)
    {
        return file_exists('images/youtube/' . $video_id . '.jpg');
    }

    /**
     * Download image from youtube
     *
     * @param $videoId
     * @param $filename
     */
    private function downloadImage($videoId, $filename)
    {
        $fileContents = file_get_contents($filename);
        file_put_contents('images/youtube/' . $videoId . '.jpg', $fileContents);
    }
}
