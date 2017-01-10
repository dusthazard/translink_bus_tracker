<!doctype html>

<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

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
  
        <div class='menu_section'>
        
          <div class='menu_item active'>
          
            <div class='menu_item_title'>Search By Bus Route</div>
            
            <div class='menu_item_content content'>
              <form id='bus-route'>
                <div class='input-group'>
                  <input name='route' class='form-control' type='text' placeholder='Search for a bus route (ex: 99)'>
                  <span class='input-group-btn'><button class='btn btn-info' type='submit'>+</button></span>
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
                <p><input id="from-input" class="form-control" type="text" placeholder="Starting Point"></p>
                <p class='push'><input id="to-input" class="form-control" type="text" placeholder="Destination"></p>
                <p class='text-right'>
                  <button type='submit' class='btn btn-info'><span class='fa fa-search'></span> Search</button>
                </p>
              </form>
            </div>
          
          </div>
          
          <div class='menu_item'>
          
            <div class='menu_item_title'>Find Bus Stops</div>
            
            <div class='menu_item_content content'>
              <form id='bus-route'>
                <div class='input-group'>
                  <input name='route' class='form-control' type='text' placeholder='Search for an address'>
                  <span class='input-group-btn'><button class='btn btn-secondary' type='submit'><i class='fa fa-search'></i></button></span>
                </div>
              </form>
            </div>
          
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
  <script type="text/javascript" src="js/functions.js"></script>

  <script>
  
  var map,
      markers = {},
      img = {},
      routes = {"routes": [],"type": null},
      lines,
      timer = false,
      directionsDisplay,
      directionsService;
  
  function refreshData() {
  
    if(routes) {
  
      var xhr = new XMLHttpRequest();
      xhr.open('POST', 'http://www.ericsestimate.com/playground/translink/api/get');
      xhr.onload = function() {
          if (xhr.status === 200) {
              
              data = JSON.parse(xhr.responseText);
              
              updateMarkers(data);
              
          }
          else {
              console.log('Ajax call failed');
          }
      };
      xhr.send(JSON.stringify(routes));
      
    }
  
  }
  
  function easeInCubic(t) {
      return Math.pow(t,3);
  }
  
  function moveMarker(marker,time,speed,latlng,start) {
  
    if(time < speed) {
    
      // return a cubic timestamp
    
      var t = easeInCubic(time/speed);
      
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
  
  function updateMarkers(data) {
  
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
            timer = setInterval(refreshData,5000);
        
        }
        
      } else {
        console.log('Directions request failed due to ' + status);
      }
    });
  }
  
  function clearAllLines() {
  
    directionsDisplay.setMap(null);
  
    // remove all lines from the map
    
    for(var i in lines) {
    
      lines[i].setMap(null);
    
    }
    
    lines = [];
  
  }
  
  function removeAllMarkers() {
  
    // remove all of the markers from the map
  
    for(var i in markers) {
    
      for(var j in markers[i]) {
      
        markers[i][j].setMap(null);
      
      }
    
    }
    
    // reset the marker object
    
    markers = {};
  
  }
  
  function init() {
  
    directionsService = new google.maps.DirectionsService;
    directionsDisplay = new google.maps.DirectionsRenderer;
    
    map = new google.maps.Map(
      document.getElementById("map"), 
      {
        center: new google.maps.LatLng(49.2827, -123.1207),
        zoom: 12
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
  
  google.maps.event.addDomListener(window, 'load', init);
  
  (function() {
  
  
    // menu item accordian functionality
    
  
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
    
    
    // controls button functionality
    
    
    document.querySelector('#controls').addEventListener('click',function() {
    
      return this.parentNode.classList.contains('open') ? this.parentNode.classList.remove('open') : this.parentNode.classList.add('open');
    
    });
    
    
    // bus route filter form functionality
    
    
    document.querySelector('#bus-route').addEventListener('submit',function(e) {
    
      e.preventDefault();
      
      var new_route = this.elements[0].value;
      
      // make sure that the route is at least 3 digits
      
      while(new_route.length < 3) {
        
        new_route = '0' + new_route;
      
      }
      
      document.querySelector('#route_filter').innerHTML = document.querySelector('#route_filter').innerHTML + "<div class='route_filter' data='"+new_route+"'>" + new_route + " <i class='fa fa-close'></i></div>";
      
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
        timer = setInterval(refreshData,5000);
      
      return false;
    
    });
  
  })();
  
  </script>

</body>
</html>