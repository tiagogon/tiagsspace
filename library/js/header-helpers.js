(function(){
  function byId(id){ return document.getElementById(id); }
  window.showText = function(text){ var el = byId('over-text'); if(el){ el.innerHTML = text || ''; } };
  window.hide = function(){ var el = byId('over-text'); if(el){ el.innerHTML = ''; } };

  window.showYear = function(text){ var el = byId('over-text-year-published'); if(el){ el.innerHTML = text || ''; } };
  window.hideYear = function(){ var el = byId('over-text-year-published'); if(el){ el.innerHTML = ''; } };

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
