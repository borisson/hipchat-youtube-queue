<?php
/**
 * Created by PhpStorm.
 * User: Tom
 * Date: 15/12/2014
 * Time: 23:23
 */

namespace Handlers;

use \PDO;

class BadgeHandler {

    var $pattern;
    var $db;

    public function __construct($pattern = "/.*/") {
        $this->pattern = $pattern;

        $this->db = new PDO("sqlite:data/badges/badges.sqlite");
        $this->checkTables();

    }

    private function checkTables() {
        // check if tables are present, if not, create
        $this->db->exec("CREATE TABLE IF NOT EXISTS badges (id INTEGER PRIMARY KEY, badge VARCHAR(255))");
        $this->db->exec("CREATE TABLE IF NOT EXISTS awards (id INTEGER PRIMARY KEY, badgeid INTEGER, user VARCHAR(255), user2 VARCHAR(255), reason VARCHAR(255))");
    }

    public function Process($message) {

        // badges? Yeah we've got badges!

        // check for /badge commands
        if (preg_match('/^\\/badges( ([a-z]+)( (.*))?)?/i', $message['message'], $matches)) {

            $command = strtolower(isset($matches[2]) ? $matches[2] : "");
            $params = isset($matches[4]) ? $matches[4] : "";

            switch ($command) {

                case "":
                    break;

                case "mine":
                    // show my badges
                    $results = $this->searchUserAwards($message['from']['mention_name'], $params);

                    if (count($results)) {
                        $message = $message['from']['mention_name'] . ", jij hebt volgende badges:";
                        foreach ($results as $result) {
                            $message .= "\n\t" . $result["badge"] . " gekregen van " . $result['user2'] . $result['reason'];
                        }
                    } else {
                        $message = $message['from']['mention_name'] . "De enige badge die jij hebt is de ik-heb-geen-badges badge";
                    }

                    return array("action" => "reply", "data" => $message );
                    break;

                case "user":
                    // show badges of user
                    $p2 = explode(" ", $params, 2);
                    if (substr($p2[0],0,1) == '@') $p2[0] = substr($p2[0], 1);
                    $results = $this->searchUserAwards($p2[0], $p2[1]);

                    if (count($results)) {
                        $message = $p2[0] . " heeft volgende badges:";
                        foreach ($results as $result) {
                            $message .= "\n\t" . $result["badge"] . " gekregen van " . $result['user2'] . $result['reason'];
                        }
                    } else {
                        $message = $p2[0] . " heeft geen badges";
                    }

                    return array("action" => "reply", "data" => $message );
                    break;

                case "search":

                    // badges zoeken
                    $results = $this->searchBadges($params);

                    if (count($results)) {
                        $message = "Gevonden badges:";
                        foreach ($results as $result) {
                            $message .= "\n\t" . $result["badge"];
                        }
                    } else {
                        $message = "Geen badges gevonden";
                    }

                    return array("action" => "reply", "data" => $message );


            }

        } elseif (preg_match('/(ik ken|ken ik) @([a-z0-9]+) de (.*)[\ \-]?badge toe\b(.*)/i', $message['message'], $matches)) {
            // check if $1 is a mention
            foreach ($message['mentions'] as $mention) {
                if ($mention['mention_name'] == $matches[2]) {

                    if ($matches[2] == "," . $message['from']['mention_name']) {
                        $badge = "ik-ben-een-lutzer";
                        $reden = "omdat hij een lutzer is";
                    } else {
                        $badge = $matches[3];
                        $reden = isset($matches[4]) ? $matches[4] : "";
                    }

                    $schenker = $message['from']['mention_name'];
                    $ontvanger = $matches[2];

                    // mention gevonden, badge toekennen
                    if ($this->userHasBadge($badge, $ontvanger)) {
                        // already has this badge
                        return array("action" => "reply", "data" => "@".$ontvanger . " heeft de " . $badge . "-badge al.");
                    } else {
                        // add badge
                        $this->addBadge($badge, $ontvanger, $schenker, $reden);
                        return array("action" => "reply", "data" => "Aaaandacht! Aaaandacht! @".$ontvanger . " heeft de " . $badge . "-badge toegekend gekregen van @".$schenker.".");
                    }
                }
            }
        }

    }

    private function userHasBadge($badge, $user) {
        return $this->db->query("SELECT * FROM badges b JOIN awards a ON b.id = a.badgid WHERE b.badge = '".addslashes($badge)."' AND a.user= '".addslashes($user)."'");
    }

    private function addBadge($badge, $user, $from, $reason) {
        LogMe("addBadge $badge $user $from $reason");
        $b = $this->getBadge($badge);
        if (!$b) {
            $this->db->exec("INSERT INTO badges (`badge`) VALUES('".addslashes($badge)."')");
            $b = $this->getBadge($badge);
        }

        $this->db->exec("INSERT INTO awards (`badgeid`, `user`, `user2`, `reason`) VALUES('".$b['id']."', '".addslashes($user)."', '".addslashes($from)."', '".addslashes($reason)."')");

    }

    private function getBadge($badge) {
        // scan index to see if badge is there
        return $this->db->query("SELECT * FROM badges WHERE badge = '".addslashes($badge)."'")->fetch(PDO::FETCH_ASSOC);
    }

    private function searchBadges($query, $limit = 10) {
        return $this->db->query("SELECT badge FROM badges WHERE badge LIKE '%".addslashes($query)."%'")->fetchAll(PDO::FETCH_ASSOC);
    }

    private function searchUserAwards($user, $query = "", $limit = 10) {
        return $this->db->query("SELECT b.badge, a.user2, a.reason FROM badges b JOIN awards a ON a.badgeid = b.id WHERE a.user = '".addslashes($user)."' AND b.badge LIKE '%".addslashes($query)."%'")->fetchAll(PDO::FETCH_ASSOC);
    }

} 