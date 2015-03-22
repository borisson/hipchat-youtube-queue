'use strict';

/* Controllers */

var radiowiziControllers = angular.module('radiowiziControllers', []);

radiowiziControllers.controller('mainController', ['$scope', '$http', 'videoManager',
    function ($scope, $http, videoManager) {

        //Load last 10 songs.
        $http.get(origin + '/' + folder + '/api/loadlastsongs').success(function (data) {
            $scope.lastsongs = data;
        });

        //Load videoManager and get video to play.
        var vid = videoManager.getVideo();
        vid.then(function(data){
            //video loaded and playing ok
            $scope.video = data.video;
            $scope.diff = data.diff;
            $scope.playerVars = data.playerVars;
            $scope.radiowizivideo = data.radiowizivideo;
        }, function(reason){
            alert('Something went wrong with loading the video, please refresh this page.');
        });

        //React on youtube events.
        $scope.$on('youtube.player.ready', function ($event, player) {
            $http.get(origin + '/' + folder + '/ajax/start-playing/' + $scope.video.id).success(function (data) {
                //start time is now written
            });
        });

        $scope.$on('youtube.player.ended', function ($event, player) {
            $http.get(origin + '/' + folder + '/ajax/set-done/' + $scope.video.id).success(function (data) {
                //load next video
                var newvideo = videoManager.getVideo();
                newvideo.then(function(data){
                    //video loaded and playing ok
                    $scope.video = data.video;
                    $scope.diff = data.diff;
                    $scope.playerVars = data.playerVars;
                    $scope.radiowizivideo = data.radiowizivideo;
                }, function(reason){
                    alert('Something went wrong with loading the video, please refresh this page.');
                });

            });
        });

    }
]);

radiowiziApp.factory('videoManager', ['$http', '$q', function ($http, $q) {
    return {
        getVideo: function () {
            var deferred = $q.defer();
            $http.get(origin + '/' + folder + '/api/loadsong')
                .success(function (data) {
                    deferred.resolve({
                        video: data.obj,
                        diff: data.diff,
                        playerVars: {
                            controls: 0,
                            autoplay: 1
                        },
                        radiowizivideo: data.obj.youtubekey
                    });
                }).error(function (msg, code) {
                    deferred.reject(msg);
                });
            return deferred.promise;
        }
    };
}]);
