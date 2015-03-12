<?php

namespace Handlers;


class RadioHandler
{

    public $pattern;

    public function __construct($pattern = "/.*/")
    {
        $this->pattern = $pattern;
    }

    public function Process($message)
    {
        global $config;

        if (!preg_match('%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/)([^"&?/ ]{11})%i', $message['message'], $match)) {
            throw new \InvalidArgumentException("Doesn't match youtube url");
        }

        makePost($config['radioUrl'] . '/add', [
            'link' => $message['message']
        ]);

        return array("action" => "reply", "data" => 'Added ' . $message['message'] . ' to radio');

    }
}