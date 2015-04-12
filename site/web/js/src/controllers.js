'use strict';

/* Controllers */
var radiowiziControllers = angular.module('radiowiziControllers', ['truncate']);

radiowiziControllers.controller('MainController', [ '$scope', '$http', '$interval', 'videoManager', 'notificationManager',
    function ($scope, $http, $interval, videoManager, notificationManager) {

        var seekto = 0;
        var currentplaytime;
        var currenttimeint = 0;
        var currenttime;
        var currentplayer;

        $scope.videoavailable = false;
        $scope.videoUpcoming = false;
        $scope.pagetitle = 'Nothing playing';
        $scope.currenttime = '00:00'

        //Load last 10 songs.
        var lastsongs = videoManager.getLastSongs();
        lastsongs.then(function(data){
            $scope.lastsongs = data.lastsongs;
        });
        var topsongs = videoManager.getTopSongs();
        topsongs.then(function(data) {
            $scope.topsongs = data.topsongs;
        });

        //load upcoming songs.
        var upcomingsongs = videoManager.getUpcomingSongs();
        upcomingsongs.then(function(data){
            $scope.upcomingsongs = data.upcomingsongs;
            if(data.upcomingsongs.length > 0){
              $scope.videoUpcoming = true;
            } else {
              $scope.videoUpcoming = false;
            }
        });

        //check every 5 seconds for new tracks.
        var loadupcoming = $interval(function(){
            var upcomingsongs = videoManager.getUpcomingSongs();
            upcomingsongs.then(function(data){
                $scope.upcomingsongs = data.upcomingsongs;
                if(data.upcomingsongs.length > 0){
                  $scope.videoUpcoming = true;
                } else {
                  $scope.videoUpcoming = false;
                }
            });
        }, 5000);

        //Load videoManager and get video to play.
        var vid = videoManager.getVideo();
        vid.then(function(data){
            //video loaded and playing ok
            $scope.videoavailable = true;
            $scope.pagetitle = data.video.title;
            $scope.video = data.video;
            $scope.video.duration = videoManager.toHHMMSS(data.video.duration);
            $scope.diff = data.diff;
            seekto = data.diff;
            $scope.playerVars = data.playerVars;
            $scope.radiowizivideo = data.radiowizivideo;
            $scope.image = data.image;
        }, function(reason){
            $scope.videoavailable = false;
            var searchforvideo = $interval(function(){
                var vidinterval = videoManager.getVideo();
                vidinterval.then(function(data){
                    //video loaded and playing ok
                    $interval.cancel(searchforvideo);
                    $scope.videoavailable = true;
                    $scope.pagetitle = data.video.title;
                    $scope.video = data.video;
                    $scope.video.duration = videoManager.toHHMMSS(data.video.duration);
                    $scope.diff = data.diff;
                    seekto = data.diff;
                    $scope.playerVars = data.playerVars;
                    $scope.radiowizivideo = data.radiowizivideo;
                    $scope.image = data.image;
                });
            }, 5000);
            //alert('Something went wrong with loading the video, please refresh this page.');
        });

        $scope.mute = function() {
            if (currentplayer.isMuted()) {
                currentplayer.unMute();
                $scope.isMute = false;
            } else {
                currentplayer.mute();
                $scope.isMute = true;
            }
        };

        //React on youtube events.
        $scope.$on('youtube.player.ready', function ($event, player) {

          currenttimeint = 0;
          currenttime = videoManager.toHHMMSS(String(currenttimeint));
          $scope.currenttime = currenttime;

            notificationManager.showNotifiction(player, $scope.video.requestname);

            $http.get(origin + '/' + folder + '/api/start-playing/' + $scope.video.id).success(function (data) {
                //start time is now written
            });
            player.seekTo(seekto);
            currentplayer = player;

          var image = document.getElementById('player__img');

          var colorThief = new ColorThief();
          var colorPalette = colorThief.getPalette(image, 2);

          stackBlurImage('player__img', 'player__canvas', 35, false );

          $scope.logoAnimation =
              'rgb(' + colorPalette[1][0] + ',' + colorPalette[1][1] + ',' + colorPalette[1][2] + ');' +
              'rgb(' + colorPalette[2][0] + ',' + colorPalette[2][1] + ',' + colorPalette[2][2] + ');' +
              'rgb(' + colorPalette[1][0] + ',' + colorPalette[1][1] + ',' + colorPalette[1][2] + ');';
          $scope.progressBarColor = 'rgb(' + colorPalette[1][0] + ',' + colorPalette[1][1] + ',' + colorPalette[1][2] + ')';
        });

        $scope.$on('youtube.player.playing', function ($event, player) {
            currentplaytime = $interval(function(){

                if(typeof player.getCurrentTime === 'function' && !isNaN(player.getCurrentTime())) {
                    currenttimeint = Number(player.getCurrentTime());
                    currenttime = videoManager.toHHMMSS(String(currenttimeint));
                    $scope.currenttime = currenttime;

                    //calculate percentage for css theming?
                    $scope.progressBarWidth = Math.round((100/Number(player.getDuration())) * Number(currenttimeint)*100)/100 + '%';
                    }
            }, 1000);
        });

        $scope.$on('youtube.player.ended', function ($event, player) {
            $interval.cancel(currentplaytime);

            currenttimeint = 0;
            $http.get(origin + '/' + folder + '/api/set-done/' + $scope.video.id).success(function (data) {
                //load next video
                var newvideo = videoManager.getVideo();
                newvideo.then(function(data){
                    //video loaded and playing ok
                    $scope.videoavailable = true;
                    $scope.pagetitle = data.video.title;
                    $scope.video = data.video;
                    $scope.currenttime = videoManager.toHHMMSS(String(currenttimeint));
                    $scope.video.duration = videoManager.toHHMMSS(data.video.duration);
                    $scope.diff = data.diff;
                    seekto = data.diff;
                    $scope.playerVars = data.playerVars;
                    $scope.radiowizivideo = data.radiowizivideo;
                    $scope.image = data.image;
                }, function(reason){
                    $scope.videoavailable = false;
                    $scope.pagetitle = 'Nothing playing';
                    var searchforvideo = $interval(function(){
                        var vidinterval = videoManager.getVideo();
                        vidinterval.then(function(data){
                            //video loaded and playing ok
                            $interval.cancel(searchforvideo);
                            $scope.videoavailable = true;
                            $scope.video = data.video;
                            $scope.pagetitle = data.video.title;
                            $scope.currenttime = videoManager.toHHMMSS(String(currenttimeint));
                            $scope.video.duration = videoManager.toHHMMSS(data.video.duration);
                            $scope.diff = data.diff;
                            seekto = data.diff;
                            $scope.playerVars = data.playerVars;
                            $scope.radiowizivideo = data.radiowizivideo;
                            $scope.image = data.image;
                        });
                    }, 5000);
                    //alert('Something went wrong with loading the video, please refresh this page.');
                });

                //update last songs
                var lastsongs = videoManager.getLastSongs();
                lastsongs.then(function(data){
                    $scope.lastsongs = data.lastsongs;
                });

                //update upcoming
                var upcomingsongs = videoManager.getUpcomingSongs();
                upcomingsongs.then(function(data){
                    $scope.upcomingsongs = data.upcomingsongs;
                });
            });
        });

      $scope.tab = 1;
      $scope.selectTab = function (setTab){
        $scope.tab = setTab;
      };
      $scope.isSelected = function(checkTab) {
        return $scope.tab === checkTab;
      };

    }
]);
