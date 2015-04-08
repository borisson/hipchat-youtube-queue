<?php

namespace AppBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;

class YoutubeMovieRepository extends EntityRepository
{
    public function findRandomTopSong()
    {
        $query = "SELECT id,
          COUNT(id) AS num
        FROM `youtube_movies`
        WHERE title != 'jingle'
        GROUP BY video_id
        HAVING num > (num/2)
        LIMIT 1";

        $connection = $this->getEntityManager()->getConnection();
        $statement = $connection->prepare($query);
        $statement->execute();
        $result = $statement->fetch();
        return $this->find($result['id']);
    }

}
