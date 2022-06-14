$(function(){
  // Note: Mediawiki has it's own minifier which doesn't understand modern javascript.

  // Log visits via the Visit Gem
  var visitURL = "https://people.influx.com/visit/inject_event";

  if ( window.location.host == 'localhost' ) {
    visitURL = "http://localhost:3000/visit/inject_event";
  }

  // For user attribution we fetch the current user's email from the Mediawiki API
  var mediawikiURL = "/api.php?action=query&meta=userinfo&uiprop=email&format=json";

  function getCurrentUserEmail(callback) {
    let email = localStorage.getItem("UserEmail")
    if ( email ) {
      return callback(email)
    }
    fetch(mediawikiURL)
      .then(function(response) { return response.json() })
      .then(function(json) {
        if ( json.query ) {
          let email = json.query.userinfo.email
          window.localStorage.setItem("UserEmail", email)
          callback(email)
        }
      })
  }

  function injectEvent(email) {
    let payload = {
      url:         window.location.href,
      referrer:    document.referrer,
      page:        mw.config.values.wgPageName,
      email:       email
    }
    console.log('injectEvent', payload)

    let headers = {
      'Authorization': 'c8a5cadf7c6c9a03fb7069ec98b466e08db1e52d',
      'Content-type':  'application/json'
    }
    fetch(visitURL, { method: "POST", mode: "cors", headers: headers, body: JSON.stringify(payload) })
  }

  // Inject event on page load
  getCurrentUserEmail(function(email) { injectEvent(email) })

  // Hide the "Switch to old look" button. It's the new look or bust.
  let $sidebarItems = $(".mw-sidebar-action-content")
  $sidebarItems.find("a[href^='/index.php?title=Special:Preferences']").remove()
  $sidebarItems.show()
})
