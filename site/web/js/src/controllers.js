'use strict';

/* Controllers */
var radiowiziControllers = angular.module('radiowiziControllers', []);

radiowiziControllers.controller('mainController', ['$scope', '$http', '$interval', 'videoManager', 'notificationManager',
    function ($scope, $http, $interval, videoManager, notificationManager) {

        var seekto = 0;
        var loadupcoming;
        var currentplaytime;
        var currenttimeint;
        var currenttime;
        var currentplayer;

        $scope.videoavailable = false;

        //Load last 10 songs.
        var lastsongs = videoManager.getLastSongs();
        lastsongs.then(function(data){
            $scope.lastsongs = data.lastsongs;
        });

        //load upcoming songs.
        var upcomingsongs = videoManager.getUpcomingSongs();
        upcomingsongs.then(function(data){
            $scope.upcomingsongs = data.upcomingsongs;
        });

        //check every 5 seconds for new tracks.
        loadupcoming = $interval(function(){
            var upcomingsongs = videoManager.getUpcomingSongs();
            upcomingsongs.then(function(data){
                $scope.upcomingsongs = data.upcomingsongs;
            });
        }, 5000);

        //Load videoManager and get video to play.
        var vid = videoManager.getVideo();
        vid.then(function(data){
            //video loaded and playing ok
            $scope.videoavailable = true;
            $scope.video = data.video;
            $scope.video.duration = videoManager.toHHMMSS(data.video.duration);
            $scope.diff = data.diff;
            seekto = data.diff;
            $scope.playerVars = data.playerVars;
            $scope.radiowizivideo = data.radiowizivideo;
        }, function(reason){
            $scope.videoavailable = false;

            var searchforvideo = $interval(function(){
                var vidinterval = videoManager.getVideo();
                vidinterval.then(function(data){
                    //video loaded and playing ok
                    $interval.cancel(searchforvideo);
                    $scope.videoavailable = true;
                    $scope.video = data.video;
                    $scope.video.duration = videoManager.toHHMMSS(data.video.duration);
                    $scope.diff = data.diff;
                    seekto = data.diff;
                    $scope.playerVars = data.playerVars;
                    $scope.radiowizivideo = data.radiowizivideo;
                });
            }, 5000);
            //alert('Something went wrong with loading the video, please refresh this page.');
        });

        $scope.mute = function() {
            if (currentplayer.isMuted()) {
                currentplayer.unMute();
                $('.player__mute').removeClass('player--muted');
            } else {
                currentplayer.mute();
                $('.player__mute').addClass('player--muted');
            }
        };

        //React on youtube events.
        $scope.$on('youtube.player.ready', function ($event, player) {
            notificationManager.showNotifiction(player);

            $http.get(origin + '/' + folder + '/ajax/start-playing/' + $scope.video.id).success(function (data) {
                //start time is now written
            });
            player.seekTo(seekto);
            currentplayer = player;
        });

        $scope.$on('youtube.player.playing', function ($event, player) {
            currentplaytime = $interval(function(){

                    if(typeof player.getCurrentTime === 'function' && !isNaN(player.getCurrentTime())) {
                        currenttimeint = Number(player.getCurrentTime());
                        currenttime = videoManager.toHHMMSS(String(currenttimeint));
                        $scope.currenttime = currenttime;

                        //calculate percentage for css theming?
                        $scope.progressBarStyle = {width:Math.round((100/Number(player.getDuration())) * Number(currenttimeint)*100)/100+'%'};
                    }
            }, 500);
        });

        $scope.$on('youtube.player.ended', function ($event, player) {
            $interval.cancel(currentplaytime);

            $http.get(origin + '/' + folder + '/ajax/set-done/' + $scope.video.id).success(function (data) {
                //load next video
                var newvideo = videoManager.getVideo();
                newvideo.then(function(data){
                    //video loaded and playing ok
                    $scope.videoavailable = true;
                    $scope.video = data.video;
                    $scope.video.duration = videoManager.toHHMMSS(data.video.duration);
                    $scope.diff = data.diff;
                    seekto = data.diff;
                    $scope.playerVars = data.playerVars;
                    $scope.radiowizivideo = data.radiowizivideo;

                }, function(reason){
                    $scope.videoavailable = false;

                    var searchforvideo = $interval(function(){
                        var vidinterval = videoManager.getVideo();
                        vidinterval.then(function(data){
                            //video loaded and playing ok
                            $interval.cancel(searchforvideo);
                            $scope.videoavailable = true;
                            $scope.video = data.video;
                            $scope.video.duration = videoManager.toHHMMSS(data.video.duration);
                            $scope.diff = data.diff;
                            seekto = data.diff;
                            $scope.playerVars = data.playerVars;
                            $scope.radiowizivideo = data.radiowizivideo;
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

    }
]);
