'use strict';

/* Services */
var radiowiziServices = angular.module('radiowiziServices', []);

radiowiziServices.factory('videoManager', ['$http', '$q', function ($http, $q) {
    return {
        getVideo: function () {
            var deferred = $q.defer();
            $http.get(origin + '/' + folder + '/api/loadsong')
                .success(function (data) {
                    if(data.obj == undefined){
                        deferred.reject('204 no content');
                        return deferred.promise;
                    }
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
        },
        getUpcomingSongs: function () {
            var deferred = $q.defer();
            $http.get(origin + '/' + folder + '/api/load-videos')
                .success(function (data) {
                    deferred.resolve({
                        upcomingsongs: data
                    });
                }).error(function (msg, code) {
                    deferred.reject(msg);
                });
            return deferred.promise;
        },
        getTopSongs: function() {
          var deferred = $q.defer();
          $http.get(origin + '/' + folder + '/api/load-top-songs')
            .success(function (data) {
              deferred.resolve({
                topsongs: data
              });
            }).error(function (msg, code) {
              deferred.reject(msg);
            });
          return deferred.promise;
        },
        toHHMMSS: function (string) {
            var sec_num = parseInt(string, 10);
            var hours = Math.floor(sec_num / 3600);
            var minutes = Math.floor((sec_num - (hours * 3600)) / 60);
            var seconds = sec_num - (hours * 3600) - (minutes * 60);

            if (hours < 10) {
                hours = "0" + hours;
            }
            if (minutes < 10) {
                minutes = "0" + minutes;
            }
            if (seconds < 10) {
                seconds = "0" + seconds;
            }
            if (hours != 0) {
              return hours + ':' + minutes + ':' + seconds;
            } else {
              return minutes + ':' + seconds;
            }
        }
    };
}]);

radiowiziServices.factory('notificationManager', ['$timeout', function ($timeout) {
    return {
        showNotifiction: function (player) {
            if ('Notification' in window) {
                Notification.requestPermission(function () {
                    var notification = new Notification('Radio Wizi - Now playing', {
                        body: player.getVideoData().title + '\nRequested by ' + $('.player__requester-name').html(),
                        icon: 'http://img.youtube.com/vi/' + player.getVideoData().video_id + '/default.jpg'
                    });

                    $timeout(function () {
                        notification.close();
                    }, 5000);

                });
            }
        }
    };
}]);
