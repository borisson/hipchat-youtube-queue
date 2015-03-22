'use strict';

/* Controllers */
var radiowiziControllers = angular.module('radiowiziControllers', []);

radiowiziControllers.controller('mainController', ['$scope', '$http', 'videoManager', 'notificationManager',
    function ($scope, $http, videoManager, notificationManager) {

        var seekto = 0;

        //Load last 10 songs.
        var lastsongs = videoManager.getLastSongs();
        lastsongs.then(function(data){
            $scope.lastsongs = data.lastsongs;
        });

        //Load videoManager and get video to play.
        var vid = videoManager.getVideo();
        vid.then(function(data){
            //video loaded and playing ok
            $scope.video = data.video;
            $scope.diff = data.diff;
            seekto = data.diff;
            $scope.playerVars = data.playerVars;
            $scope.radiowizivideo = data.radiowizivideo;
        }, function(reason){
            alert('Something went wrong with loading the video, please refresh this page.');
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
                    console.log(data);
                    //video loaded and playing ok
                    $scope.video = data.video;
                    $scope.diff = data.diff;
                    seekto = data.diff;
                    $scope.playerVars = data.playerVars;
                    $scope.radiowizivideo = data.radiowizivideo;
                }, function(reason){
                    alert('Something went wrong with loading the video, please refresh this page.');
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
