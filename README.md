# PTV Timetable API

This composer package provides a simple PHP wrapper class for the PTV (Public Transport Victoria) Timetable API (v3).

## Disclaimer

This is an unofficial package and not affiliated in any way with Public Transport Victoria. All data provided by the API is Licensed from Public Transport Victoria under a Creative Commons Attribution 4.0 International Licence.

This package is provided as is and no support will be provided by either myself or Public Transport Victoria.

For more information about the API and the use of its data, please see [https://www.ptv.vic.gov.au/about-ptv/ptv-data-and-reports/digital-products/ptv-timetable-api/](https://www.ptv.vic.gov.au/about-ptv/ptv-data-and-reports/digital-products/ptv-timetable-api/)

## Requirements

* PHP >= 5.6
* cURL Extension
* A PTV Timetable API developer ID and key (see below)

## Installation

    composer require njmh/ptv-timetable-api

## Developer ID and key

In order to use the PTV Timetable API, you must first register for a developer ID and key directly with PTV.

Follow these instructions, which are taken from **[this document](https://static.ptv.vic.gov.au/PTV/PTV%20docs/API/1475462320/PTV-Timetable-API-key-and-signature-document.RTF)** provided by PTV.

> Send an email to [APIKeyRequest@ptv.vic.gov.au](mailto:APIKeyRequest@ptv.vic.gov.au) with the following information in the subject line of the email:
> "PTV Timetable API – request for key"
---
> Once we've got your email request, we'll send you an API key and a user Id by return email.
>
> **Note**
> A high volume of requests may result in a delay in providing you with your API key and user Id. We'll try to get it to you as soon as we can.
---
> We'll also add your email address to our API mailing list so we can keep you informed about the API.
>
> **Note**
> PTV does not provide technical support for the API.
>
> The "APIKeyRequest" email address is only used to send you the API key and user ID as well as any relevant notifications. Only requests for keys will be responded to.
---
> **Note**
> We'll be monitoring the use of our API to make sure our mailing list is current and sustainable. If you haven't used the API for over 3 months, we may disable your API key and remove you from the list – but you can always register for a new key if you need one.

For more information, see (RTF document):

[https://static.ptv.vic.gov.au/PTV/PTV%20docs/API/1475462320/PTV-Timetable-API-key-and-signature-document.RTF](https://static.ptv.vic.gov.au/PTV/PTV%20docs/API/1475462320/PTV-Timetable-API-key-and-signature-document.RTF)

## Usage

    $ptv = new PtvTimetableApi();

Set your developer ID and key (see above):

    $ptv->setDeveloperId('xxxxxxx');
    $ptv->setDeveloperKey('xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx');

Examples:

    // Get all PTV routes
    $getRoutes = $ptv->routes()->get();

    // Get only train and tram routes
    $getRoutes = $ptv->routes([$ptv::TRAIN, $ptv::TRAM])->get();

    // Get stops close to a location
    $getStops = $ptv->stopsNear(-37.817979, 144.969058, ['max_distance' => 250])->get();

## Notes

### `->get()` and `->url()`

The wrapper methods provided in this class do nothing until they are chained with either `->get()`, which makes a cURL request to the API and returns the data, or `->url()`, which simply returns the full API endpoint URL including the required `devid` and `signature` parameters as described in the official API documentation. This may be useful if you wish to use something other than cURL to make the call (eg. Guzzle), or provide the URL to a javascript front end and use the browser to make the API calls.

#### Examples

#### `->get()`

```
// Get tram routes with 'Brunswick' in the name
$getTramRoutes = $ptv->routes($ptv::TRAM, 'Brunswick')->get();

// Returns

stdClass Object
(
    [routes] => Array
        (
            [0] => stdClass Object
                (
                    [route_type] => 1
                    [route_id] => 1041
                    [route_name] => East Brunswick - St Kilda Beach
                    [route_number] => 96
                )

        )

    [status] => stdClass Object
        (
            [version] => 3.0
            [health] => 1
        )

    [url] => https://timetableapi.ptv.vic.gov.au/v3/routes?devid=xxxxxxx&route_types=1&route_name=Brunswick&signature=AF6CB93E1AB6CDA93C6D9E6B4100905927D45DA3
    [execution] => 0.94602799415588
    [time_utc] => 2017-08-27T11:56:50Z
)
```
#### `->url()`
```
// Get tram routes with 'Brunswick' in the name
$getTramRoutes = $ptv->routes($ptv::TRAM, 'Brunswick')->url();

// Returns

'https://timetableapi.ptv.vic.gov.au/v3/routes?devid=xxxxxxx&route_types=1&route_name=Brunswick&signature=AF6CB93E1AB6CDA93C6D9E6B4100905927D45DA3'
```

### Route Types

Many wrapper methods require the route type to be specified as an integer (or an array of integers):

```
// Get all train and tram routes
$getRoutes = $ptv->routes([0, 1])->get();
```

Below are the available route types for all PTV services:

```
0 - Train
1 - Tram
2 - Bus
3 - Vline
4 - Night Bus
```
These values can also be requested from the API with the `routeTypes` method:

```
$ptv->routeTypes()->get();
```

To make method calls more verbose and the route types easier to remember, there is a class constant defined for each type. These include `$ptv::TRAIN`, `$ptv::TRAM`, `$ptv::BUS`, `$ptv::VLINE` and `$ptv::NIGHT_BUS` which can be used in place of the integers listed above:

```
// Get all train and tram routes
$getRoutes = $ptv->routes([$ptv::TRAIN, $ptv::TRAM])->get();
```

### HTTPS

By default, this package uses the `https://` URL for the API.  If for some reason you can't or don't want to use this for API calls, use the following method to tell the package to use the `http://` only URL instead:

```
$ptv->dontUseHttps();
```

## TODO

* Expand documentation
* Add tests

## Authors

* **Nick Morton** - nick.john.morton@gmail.com

This package is provided as is, please do not contact me for support.
