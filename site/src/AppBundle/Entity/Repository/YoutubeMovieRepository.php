<?php

namespace AppBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;

class YoutubeMovieRepository extends EntityRepository
{
    public function findRandomTopSong()
    {
        $query = "SELECT id,
    	COUNT(id) AS num,
    	(
        	SELECT count(b.id) as countMax
        	FROM youtube_movies as b
        	WHERE title != 'jingle'
        	GROUP BY video_id
        	ORDER BY countMax DESC
        	LIMIT 1
    	) AS max
        FROM `youtube_movies`
        WHERE title != 'jingle'
            AND length < 1800
        GROUP BY video_id
        HAVING num > max / 5
        ORDER BY RAND()
        LIMIT 1";

        $connection = $this->getEntityManager()->getConnection();
        $statement = $connection->prepare($query);
        $statement->execute();
        $result = $statement->fetch();
        return $this->find($result['id']);
    }

    public function getTop10Songs()
    {
        $query = "SELECT id, COUNT(id) as num
        FROM `youtube_movies`
        WHERE title != 'jingle'
            AND length < 1800
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

}
