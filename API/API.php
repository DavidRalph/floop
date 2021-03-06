<?php

require_once 'Lib.php';
require_once 'DBInterface.php';
require_once 'Friends.php';
include_once 'Films.php';
include_once 'TrailerLookup.php';
include_once 'Freezer.php';


// Removes URL encoding from string (eg. %20 for spaces)
$fullURI = urldecode(getenv('REQUEST_URI'));

//URI starts with /floop/ so uri[0-1] can be discarded
$uri = array_slice(explode("/", $fullURI), 2); //array representing URI

$method = getenv('REQUEST_METHOD');
$response = null;
$requestBody;

if ($method === "POST" || $method === "PUT") {
    $requestBody = json_decode(file_get_contents('php://input'));
}

$db = new DBConnection;

if ($uri[0] === "accounts") {

    //URI format /accounts
    if (count($uri) === 1) {
        if ($method === "GET") {
            $response = getAccounts();
        } elseif ($method === "POST") {
            $response = addAccount($requestBody);
        }
    } elseif (count($uri) === 2) {

        //URI format /accounts/full
        if ($uri[1] === "full" && $method === "GET") {
            $response = getUserDetails();
        }

        //URI format /accounts/$username
        elseif ($method === "PUT") {
            $response = editUser($uri[1], $requestBody);
        } elseif ($method === "DELETE") {
            $response = deleteUser($uri[1]);
        }
    }
} elseif ($uri[0] === "films") {

    //URI format /films
    if (count($uri) === 1) {
        if ($method === "GET") {
            $response = getFilms(null);
        } elseif ($method === "POST") {
            $response = addFilm($requestBody);
        }
    }

    //URI format /films/$filmID
    elseif (count($uri) === 2) {
        if ($method === "DELETE") {
            $response = deleteFilm($uri[1]);
        } elseif ($method === "PUT") {
            $response = editFilm($uri[1], $requestBody);
        }
    } elseif (count($uri) === 3) {

        //URI format /films/viewers/$viewerList
        if ($uri[1] === "viewers" && $method === "GET") {
            $response = getFilms($uri[2]);
        }

        //URI format /films/$filmID/$viewer
        elseif ($method === "PUT") {
            $response = rateFilm($uri[1], $uri[2], $requestBody);
        } elseif ($method === "DELETE") {
            $response = deleteFilmRating($uri[1], $uri[2]);
        }
    }
} elseif ($uri[0] === "food") {

    //URI format /food
    if (count($uri) === 1) {
        if ($method === "GET") {
            $response = getFoods(null, null);
        } elseif ($method === "POST") {
            $response = addFood($requestBody);
        }
    }

    //URI format /food/$itemName
    elseif (count($uri) === 2) {
        if ($method === "PUT") {
            $response = editFood($uri[1], $requestBody);
        }
    } elseif (count($uri) === 3) {

        //URI format /food/search/$term
        if ($uri[1] === "search" && $method === "GET") {
            $response = getFoods(null, $uri[2]);
        }

        //URI format /food/viewers/$viewerList
        elseif ($uri[1] === "viewers" && $method === "GET") {
            $response = getFoods($uri[2], null);
        }

        //URI format /food/$itemName/$viewer
        elseif ($method === "PUT") {
            $response = stockFood($uri[1], $uri[2], $requestBody);
        } elseif ($method === "DELETE") {
            $response = deleteFoodStock($uri[1], $uri[2]);
        }
    }

    //URI format /food/search/$term/viewers/$viewerList
    elseif (count($uri) === 5) {
        if ($uri[1] === "search" && $uri[3] === "viewers" && $method === "GET") {
            $response = getFoods($uri[4], $uri[2]);
        }
    }
}

$db->close();


if ($response !== null) {
    header("Content-Type: application/json; charset=UTF-8");
    echo json_encode($response);
}    