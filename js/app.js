// Seymour App - Angular JS

var SeymourApp = angular.module("SeymourApp", ["ui.router", 'ravepayment']);

SeymourApp.config(function($stateProvider, $urlRouterProvider) {

      // Redirect any unmatched url
        $urlRouterProvider
        .otherwise(function($injector, $location){
          $injector.invoke(['$state', function($state) {
            $state.go('booking');
          }]);
        });

    $stateProvider

        // booking
        .state('booking', {
            url: "/booking",
            templateUrl: "views/booking.html",
            controller: "BookingCtrl"
            // controller: "loadVehicles"
        })

        // booking
        .state('payment', {
            url: "/payment/:bid",
            templateUrl: "views/payment.html",
            controller: "PaymentCtrl"
            // controller: "loadVehicles"
        })

        // payment status
        .state('status', {
            url: "/status",
            templateUrl: "views/status.html",
            controller: "StatusCtrl",
            params: {response:null}
        })

})

.config(function($raveProvider) {

  $raveProvider.config({
      // key: "FLWPUBK-e8cca97644bb14d4d1339c5cf799b167-X", //sandbox
      key: "FLWPUBK-39558a75453ee6a3f69d3b3cf14926fb-X", //live
      isProduction: true
  });
})

.controller('AppCtrl', function($scope) {

  console.log("angular is working!");

  $scope.year = new Date().getFullYear();

})