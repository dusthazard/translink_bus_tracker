<?php

// PRIMARY MAP PAGE

require_once('auth.php');

?>

<!doctype html>

<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1">

  <title>Translink Bus Tracker</title>
  <meta name="description" content="Translink Bus Tracker">
  <meta name="author" content="Eric Sloan">
  
  <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">
  <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">

  <link rel="stylesheet" href="css/styles.css?v=<?= microtime(); ?>">

  <!--[if lt IE 9]>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html5shiv/3.7.3/html5shiv.js"></script>
  <![endif]-->
  
</head>

<body>

  <div id='menu' class='open'>
  
    <div id='controls'>
    
      <span class='fa fa-bars'></span>
    
    </div>
    
    <div class='menu_content'>
    
        <h3 class='text-center'>Vancouver Bus Tracker</h3>
        <p id='status' class='text-center'>Search for a bus route or directions below</p>
  
        <div class='menu_section'>
        
          <div class='menu_item active'>
          
            <div class='menu_item_title'>Search By Bus Route</div>
            
            <div class='menu_item_content content'>
              <form id='bus-route' class='push'>
                <div class='input-group'>
                  <input name='route' class='form-control' type='text' placeholder='Search for a bus route (ex: 99)' required>
                  <span class='input-group-btn'><button class='btn btn-info' type='submit'><i class='fa fa-plus'></i></button></span>
                </div>
              </form>
              <div id='route_filter'></div>
            </div>
          
          </div>
          
          <div class='menu_item'>
          
            <div class='menu_item_title'>Get Transit Directions</div>
            
            <div class='menu_item_content content'>
              <form id='directions-search'>
                <div id='directions-icon'>
                  <i class='fa fa-circle-thin'></i>
                  <i class='fa fa-angle-down'></i>
                  <i class='fa fa-bullseye'></i>
                </div>
                <p><input id="from-input" class="form-control" type="text" placeholder="Starting Point" required></p>
                <p class='push'><input id="to-input" class="form-control" type="text" placeholder="Destination" required></p>
                <p class='text-right'>
                  <button type='submit' class='btn btn-info'><span class='fa fa-search'></span> Search</button>
                </p>
              </form>
            </div>
          
          </div>
        
        </div>
        
        <div class='text-center'><span id='last_update'></span></div>
        
        <div id='footer'>
          
          <div class='footer_item'>
          
            Made with <i class='fa fa-beer text-warning'></i> by <a href='https://www.twitter.com/ericsestimate' target='_blank'>@ericsestimate</a>
          
          </div>
        
        </div>
        
    </div>
  
  </div>

  <div id='map_container'>

    <div id='map'>
    
      
    
    </div>
    
  </div>
  
  <script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyDn6E0pyDN51LUVc7nED0t92WqfEYRI_ys&libraries=places"></script>
  <script type="text/javascript" src="js/richmarker.js"></script>

  <script>
  
  var map,
      markers = {},
      img = {},
      routes = {"routes": [],"type": null},
      lines,
      timer = false,
      directionsDisplay,
      directionsService,
      heartbeat = false;
      
      
  /**
   * @desc - refresh the data from the API
   */
      
  
  function refreshData() {
  
    if(routes && routes.routes.length > 0) {
  
      var xhr = new XMLHttpRequest();
      xhr.open('POST', '<?= API_URL; ?>');
      xhr.onload = function() {
          if (xhr.status === 200) {
              
              data = JSON.parse(xhr.responseText);
              
              updateMarkers(data.data);
              updateTimestamp(data.timestamp);
              
              document.querySelector('#bus-route button i').classList.remove('fa-spin');
              
          }
          else {
              console.log('Ajax call failed');
          }
      };
      xhr.send(JSON.stringify(routes));
      
    } else {
    
      // remove all the markers, there is no data
      
      removeAllMarkers();
    
    }    
  
  }
  
  
  /**
   * @desc - updates the latest timestamp from the data refresh
   */
   
 
  function updateTimestamp(timestamp) {
  
    if(heartbeat) {
      
      clearInterval(heartbeat);
      heartbeat = false;
    
    }
  
    heartbeat = setInterval(function() {
    
      var seconds = Math.floor((new Date().getTime() / 1000) - timestamp);
      document.querySelector('#last_update').innerHTML = "Updated " + seconds + " seconds ago";
      
    },1000);
  
  }
  
  
  /**
   * @desc - calculate a quartratic curve for the animation
   */
  
  
  function animate(t) {
      return 1-Math.pow((1-t),4);
  }
  
  
  /**
   * @desc - render the animation when a marker moves
   */
  
  
  function moveMarker(marker,time,speed,latlng,start) {
  
    if(time < speed) {
    
      // return a cubic timestamp
    
      var t = animate(time/speed);
      
      // get the starting position of the marker
  
      start = start || {
        lat: marker.getPosition().lat(),
        lng: marker.getPosition().lng()
      };
      
      // end the function if there is no change in position
      
      if(start.lat == latlng.lat && start.lng == latlng.lng)
        return;
        
      // set the new position of the marker
      
      marker.setPosition(new google.maps.LatLng(
          ((latlng.lat - start.lat) * t + start.lat),
          ((latlng.lng - start.lng) * t + start.lng)
      ));
      
      // increment the time
      
      time += 50;
      
      // wait 5ms and rerun the function with the updated time
      
      setTimeout(function() {
      
        moveMarker(marker,time,speed,latlng,start);
      
      },50);
      
    } else {
    
      // set the final position
    
      marker.setPosition(new google.maps.LatLng(
        latlng.lat,
        latlng.lng
      ));
    
    }
  
  }
  
  
  /**
   * @desc - Update the position of the markers
   */
  
  
  function updateMarkers(data) {
  
    // find any removed markers
    
    if(data.length !== markers.length) {
    
      // loop through existing markers
      
      for(var i in markers) {
      
        // find out if the route exists in the new data
      
        var exists = false;
      
        for(j=0;j<data.length;j++) {
        
          if(data[j].RouteNo == i)
            exists = true;
        
        }
        
        // if it doesn't exist, remove the markers
        
        if(!exists) {
        
          for(var j in markers[i]) {
          
            markers[i][j].setMap(null);
          
          }
        
        }
      
      }
      
    }
    
    // create the markers
  
    for(i=0;i<data.length;i++) {
    
      // setup the new position of this marker
    
      var newPosition = {
        lat: data[i].Latitude, 
        lng: data[i].Longitude
      }
    
      if(markers[data[i].RouteNo] && markers[data[i].RouteNo][data[i].VehicleNo]) {
        
        // move and animate the marker
      
        moveMarker(markers[data[i].RouteNo][data[i].VehicleNo],0,600,newPosition);
        
      } else {
      
        // remove leading zeros from bus numbers
        
        var clean_route = data[i].RouteNo;
        
        while(clean_route.substr(0,1) == 0) {
          
          clean_route = clean_route.substr(1);
        
        }
      
        // Make sure the route number object is set
      
        markers[data[i].RouteNo] = markers[data[i].RouteNo] || {};
        
        // Create the new marker
      
        markers[data[i].RouteNo][data[i].VehicleNo] = new RichMarker({
          position: new google.maps.LatLng(data[i].Latitude, data[i].Longitude),
          content: "<div class='markerLabel marker"+data[i].Direction+"'>" + clean_route + "</div>",
          shadow: 'none',
          map: map
        });
      
      }
      
    }
  
  }
  
  
  /**
   * @desc - calculate and display a route on the map
   * @param directionService - Google maps api direction service
   */
  
  
  function calculateAndDisplayRoute(directionsService, directionsDisplay) {
  
    directionsService.route({
      origin: document.querySelector('#from-input').value,
      destination: document.querySelector('#to-input').value,
      travelMode: 'TRANSIT',
      provideRouteAlternatives: true
    }, function(response, status) {
      if (status === 'OK') {
      
        // set the primary directions
      
        directionsDisplay.setDirections(response);
        
        // add the other route lines
        
        for(i=0;i<response.routes.length;i++) {
        
          if(i === 0) continue;
          
          lines[i] = new google.maps.Polyline({
            path: response.routes[i].overview_path,
            strokeColor: '#ff0000',
            strokeOpacity: 0.5,
            strokeWeight: 4
          });
          
          lines[i].setMap(map);
        
        }
        
        // set the bus routes
        
        if(typeof response.routes[0].legs[0].steps !== 'undefined' && response.routes[0].legs[0].steps.length > 0) {
        
          var steps = response.routes[0].legs[0].steps;
          routes = {
            "routes": [],
            "type": "directions"
          };
        
          for(i=0;i<steps.length;i++) {
          
            if(steps[i].travel_mode == "TRANSIT") {
            
              routes.routes.push(steps[i].transit.line.short_name);
            
            }
          
          }
          
          refreshData();
          
          if(!timer)
            timer = setInterval(refreshData,8000);
        
        }
        
      } else {
        console.log('Directions request failed due to ' + status);
      }
    });
  }
  
  
  /**
   * @desc - clear all of the direction lines from the map
   */
  
  
  function clearAllLines() {
  
    directionsDisplay.setMap(null);
  
    // remove all lines from the map
    
    for(var i in lines) {
    
      lines[i].setMap(null);
    
    }
    
    lines = [];
  
  }
  
  
  /**
   * @desc - remove all of the markers from the map
   */
   
  
  function removeAllMarkers() {
  
    // remove all of the markers from the map
  
    for(var i in markers) {
    
      // loop through route numbers
    
      for(var j in markers[i]) {
      
        // loop through the markers
      
        markers[i][j].setMap(null);
      
      }
    
    }
    
    // reset the marker object
    
    markers = {};
  
  }
  
  /**
   * @desc init function to setup the map
   */
  
  function init() {
  
    directionsService = new google.maps.DirectionsService;
    directionsDisplay = new google.maps.DirectionsRenderer;
    
    map = new google.maps.Map(
      document.getElementById("map"), 
      {
        center: new google.maps.LatLng(49.2827, -123.1207),
        zoom: 12,
        gestureHandling: 'greedy'
      }
    );
    
    // Add the directions search box functionality
    
    var searchBox = [
      new google.maps.places.SearchBox(document.getElementById('from-input')),
      new google.maps.places.SearchBox(document.getElementById('to-input'))
    ];
    
    // Bias the SearchBox results towards current map's viewport.
    map.addListener('bounds_changed', function() {
      searchBox[0].setBounds(map.getBounds());
      searchBox[1].setBounds(map.getBounds());
    });
    
    
    
    // directions search form functionality
    
    
    document.querySelector('#directions-search').addEventListener('submit',function(e) {
    
      e.preventDefault();
    
      clearTimeout(timer); timer = false;
      removeAllMarkers();
      clearAllLines();
      
      directionsDisplay.setMap(map);
      calculateAndDisplayRoute(directionsService, directionsDisplay);
    
    });



    // Try HTML5 geolocation.
    
    
    
    if (navigator.geolocation) {
    
      navigator.geolocation.getCurrentPosition(function(position) {
      
        var pos = {
          lat: position.coords.latitude,
          lng: position.coords.longitude
        };
        
        // create a marker for the user's current position
        
        var myPosition = new google.maps.Marker({map: map});
        myPosition.setPosition(pos);
        
        map.setCenter(pos);
        map.setZoom(14);
        
      }, function() {
        // todo: add fallback
      });
      
    }
    
  }
  
  
  
  /*
   * @desc Function to check if a route exists in the current data
   * @param new_route string - route to check
   * @return bool
   */
   
   
  
  function route_exists(new_route) {
  
    if(routes && routes.routes) {
    
      for(i=0;i<routes.routes.length;i++) {
      
        if(routes.routes[i] == new_route)
          return true;
      
      }
    
    }
    
    return false;
  
  }
  
  
  // load the init function
  
  
  google.maps.event.addDomListener(window, 'load', init);
  
  
  /*
   * Function to setup all of the event listeners for the UI
   */
  
  
  (function() {
  
  
  
    /*
     * Add Event Listeners
     * @desc menu item accordian functionality
     * @element array .menu_item_title
     * @event click
     */
    
    
  
    var menu_items = document.querySelectorAll('.menu_item_title');
    
    for(i=0;i<menu_items.length;i++) {
    
      menu_items[i].addEventListener('click',function() {
      
        // ensure all menu items are closed
        
        var item_contents = document.querySelectorAll('.menu_item');
        
        for(j=0;j<item_contents.length;j++) {
        
          item_contents[j].classList.remove('active');
        
        }
        
        // activate the current item
      
        this.parentNode.classList.add('active');
      
      });
    
    }
    
    
    
    /**
     * Event Listener
     * @desc controls button functionality
     * @element #controls
     * @event click
     */
    
    
    
    document.querySelector('#controls').addEventListener('click',function() {
    
      return this.parentNode.classList.contains('open') ? this.parentNode.classList.remove('open') : this.parentNode.classList.add('open');
    
    });
    
    
    
    /**
     * Event Listener
     * @desc bus route filter form functionality
     * @element #bus-route
     * @event submit
     */
    
    
    
    document.querySelector('#bus-route').addEventListener('submit',function(e) {
    
      e.preventDefault();
      
      var new_route = this.elements[0].value;
      
      document.querySelector('#bus-route button i').classList.add('fa-spin');
      
      // make sure that the route is at least 3 digits
      
      while(new_route.length < 3) {
        
        new_route = '0' + new_route;
      
      }
      
      // check if the route already exists
      
      if(route_exists(new_route)) return;
      
      // create the GUI button
      
      var new_filter = document.createElement('div');
             
      new_filter.setAttribute('class','route_filter');
      new_filter.setAttribute('data',new_route);
      new_filter.innerHTML = new_route + " <i class='fa fa-close'></i>";
      
      document.querySelector('#route_filter').appendChild(new_filter);
      
      // add an event listener to the new element
      
      new_filter.addEventListener('click',function() {
      
        var this_route = this.getAttribute('data');
      
        this.parentNode.removeChild(this);
        
        for(i=0;i<routes.routes.length;i++) {
        
          if(routes.routes[i] == this_route)
            routes.routes.splice(i,1);
        
        }
        
        refreshData();
      
      });
      
      // reset the routes if need be
      
      if(typeof routes.type == 'undefined' || routes.type !== 'filter') {
      
        // reset the map
      
        clearTimeout(timer); timer = false;
        clearAllLines();
        removeAllMarkers();
      
        routes = {
          "routes": [],
          "type": "filter"
        }
        
      }
      
      routes.routes.push(new_route);
      
      refreshData();
      
      if(!timer)
        timer = setInterval(refreshData,8000);
      
      return false;
    
    });
  
  })();
  
  </script>

</body>
</html>