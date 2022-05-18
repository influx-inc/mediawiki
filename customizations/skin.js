$(function(){
  // Hide the "Switch to old look" button. It's the new look or bust.
  let $sidebarItems = $(".mw-sidebar-action-content")

  $sidebarItems.find("a[href^='/index.php?title=Special:Preferences']").remove()
  $sidebarItems.show()

})
