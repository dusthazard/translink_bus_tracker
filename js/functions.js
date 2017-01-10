//*************************************
// animation function
//*************************************



function animate(elem,style,unit,from,to,duration,type) {
  
  if( !elem) return;
  var start = new Date().getTime(),
      timer = setInterval(function() {
          var time = new Date().getTime() - start;
          var t = time / duration;
          var step = (type == 'ease') ? from + easeOutElastic(t) * (to-from) : from + Math.pow(t,3) * (to-from);
          elem.style[style] = step+unit;
          if( time >= duration ) clearInterval(timer);
      },25);
  elem.style[style] = from+unit;
  
}


//*************************************
// ELASTIC CALCULATOR
//*************************************


function easeOutElastic(t){
  var p = 0.7;
  return Math.pow(2,-10*t) * Math.sin((t-p/4)*(2*Math.PI)/p) + 1;
}