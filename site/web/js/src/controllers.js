'use strict';

/* Controllers */

var radiowiziControllers = angular.module('radiowiziControllers', []);

radiowiziControllers.controller('mainController', ['$scope', '$http',
    function($scope, $http) {
        //$scope.lastSongs = [
        //    {'title': 'Nexus S'},
        //    {'title': 'Motorola XOOM™ with Wi-Fi'},
        //    {'title': 'MOTOROLA XOOM™'},
        //    {'title': 'MOTOROLA XOOM™ 2'},
        //
        //];
        $http.get(origin + '/' + folder + '/api/loadlastsongs').success(function(data) {
            $scope.lastsongs = data;
        });
    }]);