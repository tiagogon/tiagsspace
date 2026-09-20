(function(){
  function byId(id){ return document.getElementById(id); }
  // Both targets live inside fixed, mix-blend-mode text blocks: only touch the DOM when
  // the label actually changes, so an unchanged hover never invalidates the blended box.
  function setLabel(id, text){
    var el = byId(id); text = text || '';
    if (el && el.innerHTML !== text) { el.innerHTML = text; }
  }
  window.showText = function(text){ setLabel('over-text', text); };
  window.hide = function(){ setLabel('over-text', ''); };

  window.showYear = function(text){ setLabel('over-text-year-published', text); };
  window.hideYear = function(){ setLabel('over-text-year-published', ''); };

  // Auto-open Log branch collapse if there's an active link
  function autoOpenCollapses(){
    var logBranch = document.getElementById('collapselog-branch');
    if (logBranch && logBranch.querySelector('.active')) {
      if (window.jQuery && typeof jQuery(logBranch).collapse === 'function') {
        jQuery(logBranch).collapse('show');
      } else {
        logBranch.classList.add('show');
      }
    }
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', autoOpenCollapses);
  } else {
    autoOpenCollapses();
  }
})();
