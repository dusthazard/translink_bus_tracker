<?php

use GuzzleHttp\Client;

/**
 * Api class used to load/save data to a json file and refresh the data from Translink's api if necessary
 *
 * Public functions: getData
 */

class api {


  //****************************
  // PUBLIC FUNCTIONS
  //****************************
  
  
  
  //****************************
  //
  // GET THE JSON DATA
  //
  // @return DATA AS ARRAY
  //
  // @error SET 400 HEADER
  //
  //****************************
  
  

  public function getData($routes) {
  
    // get data from file
  
    if($data = $this->loadData()) {
      
      // check the timestamp of the data
      
      if(is_array($data) && $data['timestamp'] < (time() - 2)) {
      
        // get an updated version of the data
      
        if($refresh = $this->refresh()) {
        
          // save the new data to file
        
          $timestamp = $this->saveData(json_decode($refresh,true));
          
          // return the data as an array
        
          return array(
            'timestamp' => $timestamp,
            'data'      => json_decode($refresh,true)
          );
        
        } else {
        
          // couldn't get data from the API - return cached data
        
          return $data;
        
        }
      
      } else {
      
        // if the data is fresh, return it
      
        return $data;
      
      }
      
    } else {
    
      // no data in the file, get new data
      
      if($refresh = $this->refresh()) {
        
        // save the new data to file
      
        $this->saveData(json_decode($refresh,true));
        
        // return the data as an array
      
        return json_decode($refresh,true);
      
      }
    
    }
  
  }
  
  
  
  //****************************
  // PRIVATE FUNCTIONS
  //****************************
  
  
  
  //****************************
  //
  // SAVE THE DATA TO A JSON FILE
  //
  // @var array data
  //
  // RETURNS TRUE WHEN DONE
  //
  //****************************
   
   

  private function saveData($data) {
  
    $timestamp = time();
  
    // add the timestamp to the data
  
    $contents = json_encode([
      'timestamp' => $timestamp,
      'data' => $data
    ]);
    
    // open the file to write
    
    $file = fopen(__DIR__ . "/../data/translink.json","w") or die("Can't open file");
    
    // overwrite the data
    
    fwrite($file,$contents);
    fclose($file);
    
    return $timestamp;
  
  }
  
  
  
  //*********************************
  //
  // REFRESH THE DATA FROM
  // TRANSLINK'S API
  //
  // RETURNS: DATA IN JSON FORMAT
  //
  //*********************************
  
  
  
  
  private function refresh() {

    try {
    
      // try to call the translink api

      $client = new Client([
          'base_uri' => 'http://api.translink.ca/rttiapi/v1/',
          'timeout'  => 5.0,
      ]);

      $response = $client->request('GET', 'buses?apikey=' . API_KEY, [
        'headers' => [
          'Content-Type' => 'application/json',
          'Accept' => 'application/json'
        ]
      ]);
      
      // cast the result as a string

      $data = (string) $response->getBody();
      
      // return the content
      
      $result = [];
      
      foreach(json_decode($data,true) as $vehicle) {
      
        $result[$vehicle['RouteNo']][] = $vehicle;
      
      }
      
      return json_encode($result);
      
    } catch(Exception $e) {
    
      // http request failed

      return false;

    }
  
  }
  
  
  
  //****************************
  //
  // LOAD DATA FROM FILE
  //
  // RETURNS: DATA AS ARRAY
  //
  //****************************
  
  
  
  private function loadData() {
  
    // open the data file
  
    $file = fopen(__DIR__ . "/../data/translink.json","r") or die("Can't open file");
    
    // read the file to a variable
    
    $contents = json_decode(fread($file,filesize(__DIR__ . "/../data/translink.json")),true);
    
    fclose($file);
    
    // return the decoded data
    
    return (json_last_error() === JSON_ERROR_NONE) ? $contents : false;
  
  }
  
  
  
  //****************************
  // END CLASS
  //****************************

}