# Rave Payment Component for Angular 1.x
An Angular library for RavePay Payment Gateway.

### Demo
![Demo Image](Demo.png?raw=true "Demo Image")

### Get Started

This AngularJS library provides a wrapper to add RavePay Payment to your AngularJS 1.x applications

###Install

##### NPM
```
npm install angularjs-ravepayment --save
```

##### Javascript via CDN
```
<!-- angular 1.x -->
<script src="https://ajax.googleapis.com/ajax/libs/angularjs/1.6.7/angular.min.js"></script>
<!-- Angularjs-Ravepayment -->
<script src="https://unpkg.com/angularjs-ravepayment/dist/angular-rave.min.js"></script>

```

### Usage

```
<div ng-app="RaveApp" ng-controller="RaveController">
    <rave-pay-button
        class="paymentbtn"
        text="Pay Me, My Money"
        email="email"
        amount="amount"
        reference="reference"
        meta="metadata"
        callback="callback"
        close="close"
        integrity_hash="integrityHash"
        currency="customer.currency"
        country="customer.country"
        customer_firstname="customer.firstName"
        customer_lastname="customer.lastName"
        custom_title="website.title"
        custom_description="website.description"
        custom_logo="website.logo"
    ></rave-pay-button>
</div>
```
```
<script>
    let raveApp = angular.module("RaveApp", ['ravepayment'])

    raveApp.config(['$raveProvider', function ($raveProvider) {
        $raveProvider.config({
            key: "FLWPUBK-XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX-X",
            isProduction: false
        })
    }])

    raveApp.controller("RaveController", function($scope){
        $scope.amount = 1000 //Naira
        $scope.customer = {
            firstName: 'Foo',
            lastName: 'Bar'
            currency: 'NGN',
            country: 'NG'
        };

        $scope.website = {
            title: 'website name',
            description: 'best ecommerce store',
            logo: 'http://website.com/logo.png'
        };

        $scope.integrityHash = function() {
            // retrieve value from server.
        }

	    $scope.metadata = [
		   {
		        metaname:‘flightid’,
		        metavalue:‘93849-MK5000’
           }
		]

	    $scope.computeReference = function() {
            let text = "";
            let possible = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789";

            for( let i=0; i < 10; i++ )
                text += possible.charAt(Math.floor(Math.random() * possible.length));

            return text;
        };

	    $scope.reference = $scope.computeReference();

	    $scope.email = "rave@flutterwave.com";
	    $scope.callback = function (response) {
		    console.log(response);
	    };

	    $scope.close = function () {
		    console.log("Payment closed");
	    };
    })
</script>
```

# Notice

**For complete payment security, kindly use our integrity checksum feature to hash all payment values before passing it to the front end for processing.**

**Please see link to implement checksum: https://flutterwavedevelopers.readme.io/v1.0/docs/checksum**

**Also ensure you verify all transactions before giving value to your customer.**

**Please see link to verify transactions: https://flutterwavedevelopers.readme.io/v1.0/docs/status-check**

[Usage](index.html)

Please checkout [Rave Documentation](https://flutterwavedevelopers.readme.io/v1.0/reference#introduction) for other available options you can add to the tag

## Deployment
REMEMBER TO CHANGE THE KEY WHEN DEPLOYING ON A LIVE/PRODUCTION SYSTEM AND CHANGE `isProduction` to `true`

## Contributing
1. Fork it!
2. Create your feature branch: `git checkout -b feature-name`
3. Commit your changes: `git commit -am 'Some commit message'`
4. Push to the branch: `git push origin feature-name`
5. Submit a pull request 😉😉

## How can I thank you?

Why not star the github repo? I'd love the attention! Why not share the link for this repository on Twitter or Any Social Media? Spread the word!

Don't forget to [follow me on twitter](https://twitter.com/iamraphson)!

Thanks!
Ayeni Olusegun.

## License
This project is licensed under the MIT License - see the [LICENSE.md](LICENSE) file for details


