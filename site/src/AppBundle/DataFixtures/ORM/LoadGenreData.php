<?php

namespace AppBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use AppBundle\Entity\Genre;

class LoadGenreData implements FixtureInterface
{
    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
        $genre = new Genre();
        $genre->setGenre('Bagger');
        $genre->setParent(null);
        $manager->persist($genre);

        $genre_rock = new Genre();
        $genre_rock->setGenre('Rock');
        $genre_rock->setParent(null);
        $manager->persist($genre_rock);

        $genre = new Genre();
        $genre->setGenre('Metal');
        $genre->setParent($genre_rock);
        $manager->persist($genre);

        $genre = new Genre();
        $genre->setGenre('Punk');
        $genre->setParent($genre_rock);
        $manager->persist($genre);

        $genre = new Genre();
        $genre->setGenre('Hardcore');
        $genre->setParent($genre_rock);
        $manager->persist($genre);

        $genre = new Genre();
        $genre->setGenre('Grunge');
        $genre->setParent($genre_rock);
        $manager->persist($genre);

        $genre = new Genre();
        $genre->setGenre('Post-Rock');
        $genre->setParent($genre_rock);
        $manager->persist($genre);

        $genre = new Genre();
        $genre->setGenre('Classic Rock');
        $genre->setParent($genre_rock);
        $manager->persist($genre);

        $genre_pop = new Genre();
        $genre_pop->setGenre('Pop');
        $genre_pop->setParent(null);
        $manager->persist($genre_pop);

        $genre = new Genre();
        $genre->setGenre('70s');
        $genre->setParent($genre_pop);
        $manager->persist($genre);

        $genre = new Genre();
        $genre->setGenre('80s');
        $genre->setParent($genre_pop);
        $manager->persist($genre);

        $genre = new Genre();
        $genre->setGenre('90s');
        $genre->setParent($genre_pop);
        $manager->persist($genre);

        $genre = new Genre();
        $genre->setGenre('New Wave');
        $genre->setParent($genre_pop);
        $manager->persist($genre);

        $genre_electronica = new Genre();
        $genre_electronica->setGenre('Electronica');
        $genre_electronica->setParent(null);
        $manager->persist($genre_electronica);

        $genre = new Genre();
        $genre->setGenre('Ambient');
        $genre->setParent($genre_electronica);
        $manager->persist($genre);

        $genre = new Genre();
        $genre->setGenre('Dance');
        $genre->setParent($genre_electronica);
        $manager->persist($genre);

        $genre = new Genre();
        $genre->setGenre('Hardstyle');
        $genre->setParent($genre_electronica);
        $manager->persist($genre);

        $genre = new Genre();
        $genre->setGenre('Hardcore');
        $genre->setParent($genre_electronica);
        $manager->persist($genre);

        $genre = new Genre();
        $genre->setGenre('Jump');
        $genre->setParent($genre_electronica);
        $manager->persist($genre);

        $genre = new Genre();
        $genre->setGenre('Drum & Bass');
        $genre->setParent($genre_electronica);
        $manager->persist($genre);

        $genre = new Genre();
        $genre->setGenre('Dubstep');
        $genre->setParent($genre_electronica);
        $manager->persist($genre);

        $genre_hiphop = new Genre();
        $genre_hiphop->setGenre('Hip Hop');
        $genre_hiphop->setParent(null);
        $manager->persist($genre_hiphop);

        $genre = new Genre();
        $genre->setGenre('Nederhop');
        $genre->setParent($genre_hiphop);
        $manager->persist($genre);

        $genre_klassiek = new Genre();
        $genre_klassiek->setGenre('Klassieke Muziek');
        $genre_klassiek->setParent(null);
        $manager->persist($genre_klassiek);

        $manager->flush();
    }
}