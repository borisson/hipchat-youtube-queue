'use strict';

/* Controllers */

var radiowiziControllers = angular.module('radiowiziControllers', []);

radiowiziControllers.controller('mainController', ['$scope', '$http',
    function($scope, $http) {
        $http.get(origin + '/' + folder + '/api/loadlastsongs').success(function(data) {
            $scope.lastsongs = data;
        });
    }
]);