<?php

namespace AppBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;

class YoutubeMovieRepository extends EntityRepository
{
    public function findTopSongsWithMostAirtime()
    {
        $query = "SELECT id,
          COUNT(id) AS num ,
          SUM(`length`) AS airtime
        FROM `youtube_movies`
        WHERE title != 'jingle'
        GROUP BY video_id
        HAVING num > 2
        ORDER BY airtime DESC
        LIMIT 0,20";

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