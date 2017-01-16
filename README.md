# Translink Bus Tracker

**Description:** Connects to the Translink API to search and pull in bus data based on a user's search.

## Live Examples

http://www.ericsestimate.com/playground/translink

## Dependencies

PHP 5+
Composer
Guzzle

## Installation

Install package in the directory where you want to run the Translink Bus Tracker.

Run composer in the root directory of the package if you want to update Guzzle

```
composer update
```

Update the **auth_example.php** file with your Translink API information and hostname. Then rename the file to **auth.php**

```
define('API_KEY','YOUR_API_KEY');        // TRANSLINK API KEY
define('API_URL','YOUR_API_URL');        // API GET URL
define('HOST_NAME','YOUR_APP_HOSTNAME'); // hostname where you host the front end

```

Have fun!
