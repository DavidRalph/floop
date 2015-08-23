<?php

/**
 * Gets an array of all films in the database.
 * 
 * @return {array(Film)}
 */
function getFilms() {
    $query = "SELECT Film.filmID, SUM(rating) AS totalRating FROM Film "
            . "LEFT JOIN Rating ON Film.filmID = Rating.filmID "
            . "GROUP BY Film.filmID ORDER BY watched, totalRating DESC";

    $results = $GLOBALS['db']->select($query, null);
    $filmIDs = [];
    foreach ($results as $result) {
        $filmIDs[] = $result->filmID;
    }
    return(getFilmDetails($filmIDs));
}

/**
 * Gets details for an array of $filmIDs.
 * 
 * @param {array(integer)} $filmIDs
 * @return {array(Film)}
 */
function getFilmDetails($filmIDs) {
    $films = [];

    $query = "SELECT filmID, title, trailer, watched FROM Film WHERE filmID = ?";

    $query2 = "SELECT Rating.username, rating FROM Rating "
            . "INNER JOIN Account ON Rating.username = Account.username "
            . "WHERE filmID = ? ORDER BY regDate";

    foreach ($filmIDs as $filmID) {
        $film = $GLOBALS['db']->select($query, array($filmID))[0];
        $film->ratings = $GLOBALS['db']->select($query2, array($filmID));
        $films[] = $film;
    }
    return($films);
}

/**
 * Returns the ID of film with $title.
 * 
 * @param {String} $title
 * @return {int}
 */
function getFilmID($title) {
    $query = "SELECT filmID FROM Film WHERE title = ?";
    $results = $GLOBALS['db']->select($query, array($title));

    if (sizeOf($results) === 0) {
        http_response_code(404); // Not Found
        return $results;
    }
    $result = $results[0]->filmID;

    return $result;
}

function filmExists($title) {
    return sizeOf(getFilmID($title)) !== 0;
}

/**
 * Creates a new film with $title provided.
 * 
 * @param {String} $title
 * @return {boolean}
 */
function addFilm($title) {

    // Check if already exists
    if (filmExists($title)) {
        http_response_code(500); // Internal Server Error
        return "Film already exists";
    } else {
        http_response_code(204); // No Content
    }

    $query = "INSERT INTO Film (title) VALUES (?)";
    $result = $GLOBALS['db']->run($query, array($title));
    return reportStatus($result);
}

/**
 * Updates a property of film with $filmID
 * Input should be an array of 2 elements, the property to modify and the value.
 * 
 * @param {String} $filmID
 * @param {array} $input
 */
function editFilm($filmID, $input) {

    $property = $input[0];
    $value = $input[1];

    $query = "UPDATE Film SET $property = ? WHERE filmID = ?";
    $result = $GLOBALS['db']->run($query, array($value, $filmID));
    return reportStatus($result);
}

/**
 * Removes all rating for and deletes film of $filmID.
 * 
 * @param {String} $filmID
 */
function deleteFilm($filmID) {

    $query = "
        DELETE FROM Rating
        WHERE filmID = ?";
    $result = $GLOBALS['db']->run($query, array($filmID));

    $query = "
        DELETE FROM Film
        WHERE filmID = ?";
    $result = $GLOBALS['db']->run($query, array($filmID));

    return reportStatus($result);
}

/**
 * Sets the watched status of film with $filmID to $value. 
 * 
 * @param {String} $filmID
 * @param {boolean} $value
 */
function markWatched($filmID, $value) {
    $query = "UPDATE Film SET watched = ? WHERE filmID = ?";
    $result = $GLOBALS['db']->run($query, array($value, $filmID));
    return reportStatus($result);
}

/**
 * Converts a YouTube 'watch' uri into an embedable uri.
 * 
 * @param {String} $fullURI
 * @return {String}
 */
function embedableURL($fullURI) {

    // YouTube
    $uri = explode("v=", $fullURI);
    if (count($uri) === 2) {
        return ("https://www.youtube.com/embed/$uri[1]");
    }

    return $fullURI;
}

/* * ***************************************************************************
 * RATINGS
 * ************************************************************************** */

/**
 * Returns the rating of film with $filmID by $user.
 * 
 * @param {String} $user
 * @param {integer} $filmID
 * @return {integer}
 */
function getRatingBy($user, $filmID) {
    $query = "SELECT rating FROM Rating WHERE filmID = ? AND username = ?";
    $results = $GLOBALS['db']->select($query, array($filmID, $user));

    if (sizeOf($results) === 0) {
        http_response_code(404); // Not Found
        return $results;
    }
    $result = $results[0]->rating;

    return $result;
}

/**
 * Returns whether $user has and existing rating for film with $filmID.
 * 
 * @param {integer} $filmID
 * @param {String} $user
 * @return {boolean}
 */
function hasRated($filmID, $user) {
    return sizeOf(getRatingBy($user, $filmID)) !== 0;
}

/**
 * Creates or updates the rating of film with $filmID 
 * by user with $username to $value.
 * 
 * @param {String} $filmID
 * @param {String} $username
 * @param {integer} $value
 */
function rateFilm($filmID, $username, $value) {

    if (!hasRated($filmID, $username)) {
        $result = createFilmRating($filmID, $username, $value);
    } else {
        $result = modifyFilmRating($filmID, $username, $value);
    }

    return reportStatus($result);
}

/**
 * Creates a new rating of film with $filmID by user with $username of $value.
 * 
 * @param {integer} $filmID
 * @param {String} $username
 * @param {integer} $value
 */
function createFilmRating($filmID, $username, $value) {
    $query = "INSERT INTO Rating VALUES (?,?,?);";
    return $GLOBALS['db']->run($query, array($filmID, $username, $value));
}

/**
 * Updates the value of an existing rating of film 
 * with $filmID by user with $username to $value.
 * 
 * @param {integer} $filmID
 * @param {String} $username
 * @param {integer} $value
 */
function modifyFilmRating($filmID, $username, $value) {
    $query = "UPDATE Rating SET rating = ? WHERE filmID = ? AND username = ?";
    return $GLOBALS['db']->run($query, array($value, $filmID, $username));
}

/**
 * Deletes rating of film with $filmID by user with $username.
 * 
 * @param {String} $filmID
 * @param {String} $username
 */
function deleteFilmRating($filmID, $username) {
    $query = "DELETE FROM Rating WHERE filmID = ? AND username = ?";
    $result = $GLOBALS['db']->run($query, array($filmID, $username));
    return reportStatus($result);
}
