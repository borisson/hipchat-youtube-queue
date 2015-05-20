<?php

namespace AppBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;

class YoutubeMovieRepository extends EntityRepository
{
    public function findRandomTopSong()
    {
        // Find the last played song, so we can do genre checking.
        $lastPlayedSong = $this->findOneBy([], ['postedTime' => 'DESC']);
        $lastGenre = $lastPlayedSong->getGenre();

        $compareGenreQuery = "";
        // If the last played song had a genre set
        if (!is_null($lastGenre)) {
            // If the last song was request by an actual person, use the same genre
            if (!in_array($lastPlayedSong->getRequestName(), ['Random top hit', 'Radio wizi', 'Ultra Wizi TOP 10'])) {
                $compareGenreQuery = "AND genre = " . $lastGenre->getGenreid();
            } else {
                if ($this->keepPlayingPreviousGenre($lastPlayedSong)) {
                    $compareGenreQuery = "AND genre = " . $lastGenre->getGenreid();
                } else {
                    $compareGenreQuery = "AND genre <> " . $lastGenre->getGenreid();
                }
            }
        }

        $query = "SELECT *
        FROM `youtube_movies`
        WHERE title != 'Jingle'
            AND length < 1800
            AND skipped = 0
            AND requestname <> 'Random top hit'
            AND requestname <> 'Radio wizi'
            AND requestname <> 'Ultra Wizi TOP 10'
            $compareGenreQuery
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

    /**
     * We can only automatically play 5 songs following each other
     * with the same genre. This means, we're now going to check
     * the songs before $lastPlayedSong.
     * They have to be checked on:
     * - requestername = 'Random top hit', 'Radio wizi', 'Ultra Wizi TOP 10'
     * - genre = $lastGenre
     *
     * If all five previous songs have this, we're going to play a song of a
     * different genre.
     *
     * @param \AppBundle\Entity\YoutubeMovie $lastPlayed
     * @return bool
     */
    private function keepPlayingPreviousGenre(\AppBundle\Entity\YoutubeMovie $lastPlayed)
    {
        $lastGenre = $lastPlayed->getGenre();
        $lastPlayedSongs = $this->findBy([], ['postedTime' => 'DESC'],5,1);

        /** @var \AppBundle\Entity\YoutubeMovie  $lastSong */
        foreach ($lastPlayedSongs as $lastSong) {
            // Only if the genre is not the same genre as the previous genre.
            if ($lastGenre !== $lastSong->getGenre()) {
                return true;
            }

            // If the last song was request by an actual person, use the same genre.
            if (!in_array($lastSong->getRequestName(), ['Random top hit', 'Radio wizi', 'Ultra Wizi TOP 10'])) {
                return true;
            }
        }

        return false;
    }
}
