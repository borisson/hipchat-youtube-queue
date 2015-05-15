<?php

namespace AppBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;

class YoutubeMovieRepository extends EntityRepository
{
    public function findRandomTopSong()
    {
        $query = "SELECT *
        FROM `youtube_movies`
        WHERE title != 'Jingle'
            AND length < 1800
            AND skipped = 0
            AND requestname <> 'Random top hit'
            AND requestname <> 'Radio wizi'
            AND requestname <> 'Ultra Wizi TOP 10'
        GROUP BY video_id
        ORDER BY RAND()
        LIMIT 1";

        $connection = $this->getEntityManager()->getConnection();
        $statement = $connection->prepare($query);
        $statement->execute();
        $result = $statement->fetch();
        return $this->find($result['id']);
    }

    public function findByVideoId($id){

    }

    public function getTop10Songs()
    {
        $query = "SELECT id, COUNT(id) as num
        FROM `youtube_movies`
        WHERE title != 'Jingle'
            AND length < 1800
            AND skipped = 0
            AND requestname <> 'Random top hit'
            AND requestname <> 'Radio wizi'
            AND requestname <> 'Ultra Wizi TOP 10'
            AND started_time > NOW() - INTERVAL 2 WEEK
        GROUP BY video_id
        ORDER BY num DESC LIMIT 0,9";

        $connection = $this->getEntityManager()->getConnection();
        $statement = $connection->prepare($query);
        $statement->execute();
        $results = $statement->fetchAll();

        $output = [];
        foreach ($results as $row) {
            $output[] = $this->find($row['id']);
        }
        return $output;
    }

    public function getUltraWiziTop10Songs()
    {
        $query = "SELECT id, COUNT(id) as num
        FROM `youtube_movies`
        WHERE title != 'Jingle'
            AND length < 1800
            AND requestname <> 'Radio wizi'
            AND requestname <> 'Ultra Wizi TOP 10'
            AND requestname <> 'Random top hit'
            AND started_time > NOW() - INTERVAL 2 WEEK
        GROUP BY video_id
        ORDER BY num DESC LIMIT 0,10";

        $connection = $this->getEntityManager()->getConnection();
        $statement = $connection->prepare($query);
        $statement->execute();
        $results = $statement->fetchAll();

        $output = [];
        foreach ($results as $row) {
            $output[] = $this->find($row['id']);
        }
        return $output;
    }

}
