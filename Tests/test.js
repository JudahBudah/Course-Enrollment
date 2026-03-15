function ajax( url, callback ) {
   var xhr = new window.XMLHttpRequest();
   xhr.open("GET", url + "?rel=page", true);
   xhr.onload = function() {
      if ( xhr.readyState === xhr.DONE && (xhr.status >= 200 && xhr.status < 300 ) ) {
         if ( this.response ) {
            callback.call( this, this.response );
         }
      }
   }
   xhr.send();
}


var anchor = document.querySelectorAll("a[rel=page]");
[].slice.call( anchor ).forEach( function( trigger ) {
   trigger.addEventListener("click", function( e ) {
      e.preventDefault();

      var pageUrl = this.getAttribute("href");

      ajax( pageUrl, function( data ) {
         document.querySelector("#load").innerHTML = data;
      } );

      if ( pageUrl != window.location ) {
         window.history.pushState( { url: pageUrl }, '', pageUrl );
      }
      return false;
   })
} );

window.addEventListener("popstate", function() {
   ajax( this.window.location.pathname, function( data ) {
      document.querySelector("#load").innerHTML = data;
   } );
} );


function setActiveLink() {
    const current = window.location.pathname.split("/").pop(); // gets "test1.php"
    
    document.querySelectorAll("a[rel='page']").forEach(link => {
        const linkPage = link.getAttribute("href").split("/").pop();
        link.classList.toggle("active", linkPage === current);
    });
}

document.querySelectorAll("a[rel='page']").forEach(link => {
    link.addEventListener("click", function (e) {
        e.preventDefault();

        fetch(this.href + "?rel=page")
            .then(res => res.text())
            .then(html => {
                document.getElementById("load").innerHTML = html;
                history.pushState({}, "", this.href);
                setActiveLink(); // update active link after each navigation
            });
    });
});

setActiveLink(); // also run on first page load