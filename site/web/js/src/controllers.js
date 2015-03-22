'use strict';

/* Controllers */

var radiowiziControllers = angular.module('radiowiziControllers', []);

radiowiziControllers.controller('mainController', ['$scope', '$http',
    function ($scope, $http) {

        $http.get(origin + '/' + folder + '/api/loadlastsongs').success(function (data) {
            $scope.lastsongs = data;
        });

        $http.get(origin + '/' + folder + '/api/loadsong').success(function (data) {
            $scope.video = data.obj;
            $scope.diff = data.diff;
            $scope.playerVars = {
                controls: 0,
                autoplay: 1
            };
            $scope.radiowizivideo = $scope.video.youtubekey;
        });

    }
]);