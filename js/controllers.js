SeymourApp
.controller('BookingCtrl', function($scope, $rootScope, Data, $state) {

  console.log("in BookingCtrl");

  // functions
    $scope.loadVehicles = function() {
        Data.get("getVehicleList").then(function(results) { 
    console.log("in loadVehicles");
            console.log("getVehicleList results:", results);
            if (results) {
                if (results.status == "success") {
                    $scope.vehicles = results.vehicles;
                    console.log("Vehicles:", $scope.vehicles);
                }
            }else {
                swal('Error', "Couldn't load vehicles. Something went wrong.", "error");
            }
        });
    }
    //loadDiscounts
    $scope.loadDiscounts = function() {
      Data.get("getDiscountList").then(function(results) {
        console.log("getDiscountList results:", results);
        if (results) {
          if (results.status == "success") {
            $scope.discounts = results.discounts;
          }
        }else {
          swal('Error', "Couldn't load discounts. something went wrong.", "error");
        }

      });
    };

    $scope.calcGrandTotal = function(items) {
        $scope.booking.bk_total = 0;
        for(var i=0; i<items.length; i++) {
            $scope.booking.bk_total += items[i].bi_total;
        }
    };

    $scope.addItem = function() {
        $scope.items.push({
            bi_discount_percent : 0.00,
            bi_discount_applied : 0.00,
            bi_disc_price : 0.00,
            bi_veh_price: 0.00,
            bi_num_days : 1,
            bi_num_vehicle : 1,
            bi_total : 0.00
        });
    };

    $scope.deleteItem = function (item, i) {
        console.log("item,i in deleteItem:", item, i);
        if (confirm("Are you sure?")) {
            $scope.items.splice(i, 1);
            $scope.calcGrandTotal($scope.items);
        }
    };

    $scope.getVehicleByID = function(vehicles, id) {
        for(var i=0; i<vehicles.length; i++) {
            if(id == vehicles[i].veh_id) {
                return vehicles[i];
            }
        }
    };

    $scope.calcItemTotal = function(i, disc_price, num_days, num_vehicles) {
        console.log("calcItemTotal called", i, disc_price, num_days, num_vehicles);
        $scope.items[i].bi_total = disc_price * num_days * num_vehicles;
        console.log("$scope.items[i]", $scope.items[i]);
        $scope.calcGrandTotal($scope.items);
    };

    $scope.updateVehiclePrice = function(i, veh_price, disc_percent, num_vehicles, num_days) {
        console.log("updateVehiclePrice called", i, veh_price, disc_percent, num_vehicles, num_days);
        $scope.items[i].bi_discount_applied = (veh_price * disc_percent) / 100;
        $scope.items[i].bi_disc_price = veh_price - $scope.items[i].bi_discount_applied;
        $scope.calcItemTotal(i, $scope.items[i].bi_disc_price, num_days, num_vehicles);
    };

    $scope.getVehiclePrice = function(i, veh_id) {
        console.log("getVehiclePrice called", i, veh_id);
        var vehicle = $scope.getVehicleByID($scope.vehicles, veh_id);
        console.log("vehicle in getVehiclePrice:", vehicle);

        $scope.items[i].bi_veh_price = vehicle.veh_price;
        $scope.items[i].bi_veh_name = vehicle.veh_name;
        $scope.updateVehiclePrice(i, vehicle.veh_price, $scope.items[i].bi_discount_percent, $scope.items[i].bi_num_vehicle, $scope.items[i].bi_num_days);
    };

    $scope.calcDiscountPercent = function(num_vehicles, discounts) {
        console.log("calcDiscountPercent called", num_vehicles, discounts);
        var discount_found = false;
        for(var j = 0; j < discounts.length; j++) {
            if(num_vehicles >= discounts[j].dsc_qty_min && num_vehicles <= discounts[j].dsc_qty_max) {
                var discount_found = true;
                return discounts[j].dsc_percent;
            }
        }
        // if num_vehicles is greater than the max set range
        if(!discount_found) {
            var length = discounts.length;
            return discounts[length - 1].dsc_percent;
        }
    };

    $scope.updateNumVehicles = function(i, item) {
        console.log("updateNumVehicles called", i, item);
        $scope.items[i].bi_discount_percent = $scope.calcDiscountPercent(item.bi_num_vehicle, $scope.discounts);
        $scope.items[i].bi_discount_applied = (item.bi_veh_price * $scope.items[i].bi_discount_percent) / 100;
        $scope.bi_disc_price = item.bi_veh_price - $scope.items[i].bi_discount_applied;
        $scope.calcItemTotal(i, $scope.items[i].bi_disc_price, item.bi_num_days, item.bi_num_vehicle);
    };

    $scope.updateNumDays = function(i, item) {
        console.log("updateNumDays called", i, item);
        $scope.calcItemTotal(i, item.bi_disc_price, item.bi_num_days, item.bi_num_vehicle);
    };

    $scope.saveBooking = function(booking, items) {
        console.log("booking, items in saveBooking:", booking, items);
        Data.post("createBooking", {booking:booking, items:items})
        .then(function(results) {
            if(results) {
                if(results.status == "success") {
                    $('#checkoutModal').modal('hide');
                    $('body').removeClass('modal-open');
                    $('.modal-backdrop').remove();
                    $state.go("payment", {bid:results.bk_id});
                } else {
                    swal('Error', results.message, "error");
                }
            } else {
                swal('Error', "Something went wrong while trying to save booking.", "error");
            }
        });
    };

    // page load
    $scope.booking = {};
    $scope.booking.bk_total = 0;
    $scope.items = [];
    $scope.addItem();
    console.log("items:",$scope.items);

    $scope.loadVehicles();
    $scope.loadDiscounts();

})

.controller('PaymentCtrl', function($scope, $rootScope, Data, $state, $stateParams) {
    $scope.loadBooking = function(bid) {
        Data.get("getBooking?id="+bid)
        .then(function(results) {
            if(results) {
                if(results.status == "success") {
                    $scope.booking = results.booking;
                    var user_names = $scope.booking.bk_name.split(" ");
                    $scope.user_firstname = user_names[0];
                    $scope.user_surname = user_names[1];
                } else {
                    swal('Error', results.message, "error");
                    $state.go("booking");
                }
            }
        })
    };

    $scope.computeReference = function() {
          var text = "";
          var possible = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789";

          for( var i=0; i < 10; i++ )
              text += possible.charAt(Math.floor(Math.random() * possible.length));

          return text.toUpperCase();
    };

    $scope.process = function() {
        $scope.processing = true;
    };

    $scope.stop = function() {
        $scope.processing = false;
    };

    $scope.callback = function (response) {
      console.log("callback response:", response);
      $scope.response = response.data;
    };

    $scope.close = function () {
      console.log("Payment closed");
      if($scope.response) {
        var response = $scope.response;
        // send a request to API to Store Transaction, Verify Transaction (if successful) and Add Value for Customer
        $scope.booking.bk_payment_ref = $scope.reference;
        $scope.processing = true;
        Data.post("processOnlinePayment", {booking:$scope.booking, response:response})
        .then(function(results) {
          console.log("processOnlinePayment:",results);
          if(results) {
            if(results.status == "success") {
              $state.go("status", {response:results});
            } else {
                swal('Error', results.message, "error");
                $scope.processing = false;
            }
          } else {
            swal('Error', "Something went wrong while trying to process your payment!", "error");
            $scope.processing = false;
          }
        }, function(err) {
            console.log("processOnlinePayment err:", err);
            swal('Error', "Server Connection Failed!", "error");
            $scope.processing = false;
        });
      } else {
        swal('Error', "Payment CANCELLED OR FAILED at Gateway!", "error");
        $scope.processing = false;
      }
    };

    $scope.reference = $scope.computeReference();

    $scope.metadata = [];
    $scope.metadata.push({
        metaname:'flightid',
        metavalue:'93849-MK5000'
     });

    $scope.customer = {
      currency: 'NGN',
      country: 'NG'
    };

    $scope.website = {
      title: 'Seymour Aviation',
      description: 'The best aviation infrastructure'
    };

    $scope.loadBooking($stateParams.bid);

})

.controller('StatusCtrl', function($scope, $rootScope, Data, $state, $stateParams) {

  console.log("in StatusCtrl");

  console.log("response received in $stateParams:",$stateParams.response);

  $scope.response = $stateParams.response;

})