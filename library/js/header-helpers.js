(function(){
  function byId(id){ return document.getElementById(id); }
  // Both targets live inside fixed, mix-blend-mode text blocks: only touch the DOM when
  // the label actually changes, so an unchanged hover never invalidates the blended box.
  function setLabel(id, text){
    var el = byId(id); text = text || '';
    if (el && el.innerHTML !== text) { el.innerHTML = text; }
  }
  // On a single work the header line is the work's title. While a related
  // thumbnail is hovered, everything after the site crumb (.site-title-entry,
  // rendered by library/page-title/) gives way to that thumbnail's label, and
  // comes back on leave. Only while the label target is displayed (from the sm
  // breakpoint up): below it the span is hidden, and a touch tap, which fires
  // mouseenter, would otherwise blank the title.
  function entrySpan(){ return document.querySelector('#site-header .site-title-entry'); }
  function labelShown(){ var el = byId('over-text'); return !!el && getComputedStyle(el).display !== 'none'; }
  window.showText = function(text){
    var entry = entrySpan();
    if (entry && labelShown()) { entry.hidden = true; }
    setLabel('over-text', text);
  };
  window.hide = function(){
    setLabel('over-text', '');
    var entry = entrySpan();
    if (entry) { entry.hidden = false; }
  };

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

  // Keyboard parity for the hover label: focusing a thumbnail's link shows the
  // same label as the mouse does (the figures carry data-label / data-year).
  // One delegated pair of listeners; focusin/focusout bubble, unlike focus/blur.
  function bindLabelFocus(){
    function figureOf(target){
      return target && target.closest ? target.closest('figure[data-label]') : null;
    }
    document.addEventListener('focusin', function(e){
      var fig = figureOf(e.target);
      if (fig) { window.showText(fig.dataset.label); window.showYear(fig.dataset.year || ''); }
    });
    document.addEventListener('focusout', function(e){
      if (figureOf(e.target)) { window.hide(); window.hideYear(); }
    });
  }

  // A page restored from the back/forward cache may still show a label whose
  // mouseleave never fired.
  function bindPageShow(){
    window.addEventListener('pageshow', function(){ window.hide(); window.hideYear(); });
  }

  function init(){ autoOpenCollapses(); bindMenuWord(); bindLabelFocus(); bindPageShow(); }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
