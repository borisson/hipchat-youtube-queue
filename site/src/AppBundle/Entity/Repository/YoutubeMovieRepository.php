<?php

namespace AppBundle\Entity\Repository;

use AppBundle\Entity\Genre;
use AppBundle\Entity\YoutubeMovie;
use Doctrine\ORM\EntityRepository;

class YoutubeMovieRepository extends EntityRepository
{
    const minimumSongsInGenre = 10;

    const maxSameSongsInGenre = 6;

    public function findRandomTopSong()
    {
        // Find the last played song, so we can do genre checking.
        $lastPlayedSong = $this->findOneBy([], ['postedTime' => 'DESC']);

        $queryFilter = $this->createFilterForRandom($lastPlayedSong);

        $query = "SELECT *
        FROM `youtube_movies`
        WHERE title != 'Jingle'
            AND length < 1800
            AND skipped = 0
            AND requestname <> 'Random top hit'
            AND requestname <> 'Radio wizi'
            AND requestname <> 'Ultra Wizi TOP 10'
            $queryFilter
        GROUP BY video_id
        ORDER BY RAND()
        LIMIT 1";

        $connection = $this->getEntityManager()->getConnection();
        $statement = $connection->prepare($query);
        $statement->execute();
        $result = $statement->fetch();

        if ($result === false) {
            return false;
        }

        return $this->find($result['id']);
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
     * This method holds all business logic related getting a new random song.
     *
     * This includes getting a song with the correct genre.
     *
     * The different branches are:
     * ----
     *
     * - is the previous song requested by a human and it had NO genre:
     * --> just make sure it's not in one of the last 10 songs
     *
     * - is the previous song requested by a human
     * - and it was from sub genre
     * - and the sub genre has > minimumSongsInGenre songs
     * --> make sure it's not in the last 10 songs
     * --> make sure it plays the same genre
     *
     * - is the previous song requested by a human
     * - and it was from sub genre
     * - and the sub genre has < minimumSongsInGenre songs
     * - and the parent genre has > minimumSongsInGenre songs
     * --> make sure it's not in the last 10 songs
     * --> make sure it plays the parent genre
     *
     * - is the previous song requested by a human
     * - and it was from sub genre
     * - and the sub genre has < minimumSongsInGenre songs
     * - and the parent genre has < minimumSongsInGenre songs
     * --> make sure it's not in the last 10 songs
     * --> make sure it plays a song with no genre.
     *
     * - is the previous song requested by a human
     * - and it was from parent genre
     * - and the genre has > minimumSongsInGenre songs
     * --> make sure it's not in the last 10 songs
     * --> make sure it plays the same genre.
     *
     * - is the previous song requested by a human
     * - and it was from parent genre
     * - and the parent genre has < minimumSongsInGenre songs
     * --> make sure it's not in the last 10 songs
     * --> make sure it plays a song with no genre.
     *
     * If the previous song is requested by the robot (random top hit)
     * we're going to check all the same trees that are check for a human requester.
     * But not before checking that the previously played songs so we can check
     * that there are only $maxSameSongsInGenre songs of the same genre played
     * in succession.
     *
     *
     * @param \AppBundle\Entity\YoutubeMovie $lastPlayed
     * @return string
     */
    private function createFilterForRandom(YoutubeMovie $lastPlayed)
    {
        /** @var Genre $lastGenre */
        $lastGenre = $lastPlayed->getGenre();
        $machineRequestNames = ['random top hit', 'radio wizi', 'ultra wizi top 10'];

        $lastPlayedSongs = $this->findBy([], ['postedTime' => 'DESC'], 10, 1);
        $excludeableVideoIds = [];

        /** @var YoutubeMovie $song */
        foreach ($lastPlayedSongs as $song) {
            $excludeableVideoIds[] = $song->getYoutubeKey();
        }


        // Check if the last song was requested by a human or the bot.
        if (!in_array(strtolower($lastPlayed->getRequestName()), $machineRequestNames)) {
            // -- requested by human.

            // Last played song had no genre
            if (is_null($lastGenre)) {
                return " AND video_id NOT IN ('".implode("','", $excludeableVideoIds)."')";
            }

            $songsWithCurrentGenre = $this->findBy(['genre' => $lastGenre]);

            // Check if this is a parent genre
            if (is_null($lastGenre->getParent())) {
                if (count($songsWithCurrentGenre) > self::minimumSongsInGenre) {
                    return "AND genre = '".$lastGenre->getGenreid()."' AND video_id NOT IN ('".implode("','", $excludeableVideoIds)."')";
                } else {
                    return "AND (genre IS NULL OR genre <> ".$lastGenre->getGenreid().") AND video_id NOT IN ('".implode("','", $excludeableVideoIds)."')";
                }
            }

            $songsWithParentGenre = $this->findBy(['genre' => $lastGenre->getParent()]);
            if (count($songsWithCurrentGenre) > self::minimumSongsInGenre) {
                return "AND genre = '".$lastGenre->getGenreid()."' AND video_id NOT IN ('".implode("','", $excludeableVideoIds)."')";
            } else {
                if (count($songsWithParentGenre) > self::minimumSongsInGenre) {
                    return "AND genre = '".$lastGenre->getParent()->getGenreId()."' AND video_id NOT IN ('".implode("','", $excludeableVideoIds)."')";
                } else {
                    return "AND (genre IS NULL OR genre <> ".$lastGenre->getGenreid().") AND video_id NOT IN ('".implode("','", $excludeableVideoIds)."')";
                }
            }

        }

        // Requested by bot.
        // Last played song had no genre
        if (is_null($lastGenre)) {
            return " AND video_id NOT IN ('".implode("','", $excludeableVideoIds)."')";
        }

        $songsWithCurrentGenre = $this->findBy(['genre' => $lastGenre]);

        /** @var YoutubeMovie $song */
        $countSameGenres = 0;
        foreach ($lastPlayedSongs as $song) {
            if ($song->getGenre() === $lastGenre && !in_array(strtolower($lastPlayed->getRequestName()), $machineRequestNames)) {
                $countSameGenres++;
            }
        }

        if ($countSameGenres > self::maxSameSongsInGenre) {
            return "AND (genre IS NULL OR genre <> ".$lastGenre->getGenreid().") AND video_id NOT IN ('".implode("','", $excludeableVideoIds)."')";
        }

        // Check if this is a parent genre
        if (is_null($lastGenre->getParent())) {
            if (count($songsWithCurrentGenre) > self::minimumSongsInGenre) {
                return "AND genre = '".$lastGenre->getGenreid()."' AND video_id NOT IN ('".implode("','", $excludeableVideoIds)."')";
            } else {
                return "AND (genre IS NULL OR genre <> ".$lastGenre->getGenreid().") AND video_id NOT IN ('".implode("','", $excludeableVideoIds)."')";
            }
        }

        $songsWithParentGenre = $this->findBy(['genre' => $lastGenre->getParent()]);
        if (count($songsWithCurrentGenre) > self::minimumSongsInGenre) {
            return "AND genre = '".$lastGenre->getGenreid()."' AND video_id NOT IN ('".implode("','", $excludeableVideoIds)."')";
        } else {
            if (count($songsWithParentGenre) > self::minimumSongsInGenre) {
                return "AND genre = '".$lastGenre->getParent()->getGenreId()."' AND video_id NOT IN ('".implode("','", $excludeableVideoIds)."')";
            } else {
                return "AND (genre IS NULL OR genre <> ".$lastGenre->getGenreid().") AND video_id NOT IN ('".implode("','", $excludeableVideoIds)."')";
            }
        }
    }

}
