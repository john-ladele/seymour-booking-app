
SeymourApp
.controller('BookingCtrl', function($scope, Data) {

	console.log("in BookingCtrl");

	$scope.booking = {};
    $scope.discountRate_Arr = [];
	$scope.items = [];
	$scope.items.push({});

	// set the default value of our number


	$scope.addMore = function() {
		$scope.items.push({});
        $('.mytooltip').tooltip();
	};

    $scope.deleteItem = function (item, i) {
        console.log("item,i in deleteItem:", item, i);
        /*if (confirm("Are you sure?")) {
            $scope.items.splice(i, 1);
        }*/
    };

	Data.get("test")
	.then(function(results) {
		// console.log("test results:", results);
	});

	var data = "Data Content";
	Data.post("testPost", {data:data})
	.then(function(results) {
		// console.log("testPost results:", results);
	});

    //rowTotalcalculation
    // $scope.item.bi_num_days = 1;
	
	//loadVehicles
	$scope.loadVehicles = function() {
        Data.get("getVehicleList").then(function(results) { 
		console.log("in loadVehicles");
            console.log("getVehicleList results:", results);
            if (results) {
                if (results.status == "success") {
                    $scope.vehicles = results.vehicles;
                    console.log("Vehicles:", vehicles);
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
    }

    //getDiscountRate
    $scope.discountRate = [];
    $scope.getDiscountRate = function(discounts, num_vehicles, i) {
        // console.log("in getDiscountRate");
        // console.log("Number of Vehicles:", num_vehicles);
        // console.log("Discounts New:", discounts);
        
        // $scope.discountRate[i] 
        for(var j = 0; j < discounts.length; j++) {
            if(num_vehicles >= discounts[j].dsc_qty_min && num_vehicles <= discounts[j].dsc_qty_max) {
                // return discounts[i].dsc_percent;
                $scope.discountRate[i] = $scope.discounts[j].dsc_percent;
                console.log("i:", $scope.discountRate[i] ); 
                // console.log("Discount Rate:", $scope.discountRate);

            }
        }
        var length = discounts.length;
        return discounts[length - 1].dsc_percent;
        // console.log("Else discount Rate:", discounts[length - 1].dsc_percent);
     };


    //getVehiclePrice
    $scope.discountInNaira = [];
    $scope.getVehiclePrice = function(vehicles, veh_name, i) {
        for (var j = 0; j < vehicles.length; j++) {
            // console.log("Length of vehicle:", vehicles.length);
            if (veh_name == vehicles[j].veh_name) {

                $scope.vehiclePrice = $scope.vehicles[j].veh_price;
                console.log("Corresponding Vehicle Price:", $scope.vehiclePrice);
                console.log("veh_name", veh_name);
                $scope.discountInNaira[i] = ($scope.discountRate[i] * $scope.vehiclePrice)/100;
                console.log("Discount In Naira:", $scope.discountInNaira[i]);
            }
            else {
                // $scope.vehiclePrice = " ";
                // $scope.discountInNaira = " ";
            }
        }
    };

    //getDiscountInNaira
    // $scope.getDiscountInNaira = function(vehiclePrice, discountRate[i]) {

    //     console.log("Discount in Rate:", vehiclePrice);
    //     console.log("Vehicle Price:", $scope.vehiclePrice);
    //     $scope.discountInNaira[i] = ($scope.discountRate[i] * $scope.vehiclePrice)/100;
    //     console.log("Discount In Naira:", $scope.discountInNaira[i]);

    // };



    
    //totalCalculation
    // $scope.total = function(days) {
    //     console.log("Days Picked:", days);
    //     // console.log("Discount in Naira:", $scope.discountInNaira);
    //     $scope.total = $scope.discountInNaira * days;
    //     console.log("Total:", total);
    // }
                    


    $scope.loadVehicles();
    $scope.loadDiscounts();

})

.controller('StatusCtrl', function($scope) {

	console.log("in StatusCtrl");

})
