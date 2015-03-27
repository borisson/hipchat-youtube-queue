# hipchat-youtube-queue
This repo has bot a bot used to queue youtube links and a website to play them. 
It's functional from version 0.0.1, the angular version is implemented in version 0.0.2.
A redesign is implemented in version 0.0.3

Check out the [releases](https://github.com/borisson/hipchat-youtube-queue/releases)

## Installation
- copy bot/capabilities.dist to capabilities
- replace %url% in that file to the url where you'll be hosting the bot.
- copy config.php.dist to config.php
- fill in all config parameters. 
If you want to be able to search you'll need a youtube api key, if you don't want to search you can remove bot/handlers/searchHandler.php
- you'll need to do ``composer install`` in both the bot and site directories.
- To make sure the website has styling you'll need to do ``npm install`` and ``grunt build`` as well.

We've got some [open issues](https://github.com/borisson/hipchat-youtube-queue/issues) and we appreciate every pull request.
