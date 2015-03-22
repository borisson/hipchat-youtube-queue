'use strict';

var origin = document.location.origin;
var folder = document.location.pathname.split('/')[1];
var path = origin + '/partials/';

/* App Module */
var radiowiziApp = angular.module('radiowiziApp', [
    'ngRoute',
    'radiowiziControllers',
    'youtube-embed'
]);

radiowiziApp.config(['$routeProvider',
    function($routeProvider) {
        $routeProvider.
            when('/', {
                templateUrl: path + 'main.html',
                controller: 'mainController'
            }).
            otherwise({
                redirectTo: '/'
            });
    }]);
