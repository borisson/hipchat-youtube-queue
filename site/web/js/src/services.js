'use strict';

/* Services */
var radiowiziServices = angular.module('radiowiziServices', []);

radiowiziServices.factory('videoManager', ['$http', '$q', function ($http, $q) {
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
        },
        getLastSongs: function () {
            var deferred = $q.defer();
            $http.get(origin + '/' + folder + '/api/loadlastsongs')
                .success(function (data) {
                    deferred.resolve({
                        lastsongs: data
                    });
                }).error(function (msg, code) {
                    deferred.reject(msg);
                });
            return deferred.promise;
        }
    };
}]);

