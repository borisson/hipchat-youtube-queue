'use strict';

/* Controllers */
var radiowiziControllers = angular.module('radiowiziControllers', []);

radiowiziControllers.controller('mainController', ['$scope', '$http', '$interval', 'videoManager', 'notificationManager',
    function ($scope, $http, $interval, videoManager, notificationManager) {

        var seekto = 0;
        var loadupcoming;
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
                    $scope.diff = data.diff;
                    seekto = data.diff;
                    $scope.playerVars = data.playerVars;
                    $scope.radiowizivideo = data.radiowizivideo;
                });
            }, 5000);
            //alert('Something went wrong with loading the video, please refresh this page.');
        });

        //React on youtube events.
        $scope.$on('youtube.player.ready', function ($event, player) {
            notificationManager.showNotifiction(player);

            $http.get(origin + '/' + folder + '/ajax/start-playing/' + $scope.video.id).success(function (data) {
                //start time is now written
            });
            player.seekTo(seekto);
        });

        $scope.$on('youtube.player.ended', function ($event, player) {
            $http.get(origin + '/' + folder + '/ajax/set-done/' + $scope.video.id).success(function (data) {
                //load next video
                var newvideo = videoManager.getVideo();
                newvideo.then(function(data){
                    //video loaded and playing ok
                    $scope.videoavailable = true;
                    $scope.video = data.video;
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

            });
        });

    }
]);
