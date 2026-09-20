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

  // Reveal/Hide word: follow the MENU only. The toggle collapses three targets at
  // once (menu, top title, lower date); Bootstrap rewrites the trigger's
  // aria-expanded after each one, and the last processed is a title being hidden,
  // so the word ended up inverted. shown/hidden fire after the whole toggle.
  function bindMenuWord(){
    if (!window.jQuery) { return; }
    var $menu = jQuery('#collapseMenu');
    var $trigger = jQuery('#topbar a[href="#collapseMenu"]');
    if (!$menu.length || !$trigger.length) { return; }
    $menu.on('shown.bs.collapse hidden.bs.collapse', function(e){
      if (e.target !== this) { return; } // ignore nested collapses (Log branches) bubbling up
      $trigger.attr('aria-expanded', e.type === 'shown' ? 'true' : 'false');
    });
  }

  function init(){ autoOpenCollapses(); bindMenuWord(); }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
