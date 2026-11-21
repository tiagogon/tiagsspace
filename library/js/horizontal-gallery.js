/**
 * Horizontal galleries (template-parts/entry-gallery-horizontal.php). Theme code, no library.
 *
 * classic: the track is a native scroller (overflow-x in _page-single-gallery.scss), so
 *   trackpads, touch screens and Shift+wheel scroll it with the system's own inertia and
 *   a vertical wheel always scrolls the page. This file only adds what browsers lack:
 *   dragging with a mouse or pen (with a short glide on release) and ←/→ by one picture.
 * still: one picture at a time (.is-current), swapped every data-hgallery-delay ms while
 *   at least half the gallery is on screen and the tab is visible; a click or ←/→ steps.
 *
 * ←/→ act on the one gallery most in view, never on every gallery of a Log page at once.
 *
 * Each gallery queues its track on window.tiagsspaceHGalleryQueue right after its markup;
 * this file drains the queue and then takes new entries directly (Infinite Scroll pages
 * insert this file again, async, so a gallery may queue before or after it runs).
 */
(function () {
  'use strict';

  if (window.tiagsspaceHGallery) {
    drainQueue(window.tiagsspaceHGallery);
    return;
  }

  var DRAG_THRESHOLD = 4;     // px before a press becomes a drag
  var GLIDE_TIME = 325;       // ms, time constant of the release glide (exponential decay)
  var GLIDE_MIN_SPEED = 0.02; // px/ms below which the glide stops
  var VELOCITY_WINDOW = 100;  // ms of pointer samples used for the release velocity

  var reduceMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)');
  var tracks = [];

  function prefersReducedMotion() {
    return !!(reduceMotion && reduceMotion.matches);
  }

  // ------------------------------------------------------------------
  // classic: mouse / pen drag with a glide
  // ------------------------------------------------------------------

  function setupClassic(track) {
    var down = false;
    var dragging = false;
    var pointerId = null;
    var startX = 0;
    var startScroll = 0;
    var samples = [];
    var glideFrame = 0;
    var swallowClick = false;

    function stopGlide() {
      if (glideFrame) {
        cancelAnimationFrame(glideFrame);
        glideFrame = 0;
      }
    }
    track.tiagsspaceStopGlide = stopGlide;

    function glide(velocity) {
      if (prefersReducedMotion() || Math.abs(velocity) < GLIDE_MIN_SPEED) return;
      var last = performance.now();
      function frame(now) {
        var dt = Math.min(now - last, 50);
        last = now;
        var before = track.scrollLeft;
        track.scrollLeft = before + velocity * dt;
        velocity *= Math.exp(-dt / GLIDE_TIME);
        // Stop at either end (the position did not move) or once slow enough
        if (Math.abs(velocity) < GLIDE_MIN_SPEED || track.scrollLeft === before) {
          glideFrame = 0;
          return;
        }
        glideFrame = requestAnimationFrame(frame);
      }
      glideFrame = requestAnimationFrame(frame);
    }

    track.addEventListener('pointerdown', function (e) {
      stopGlide();
      // Touch scrolls natively; only mouse and pen are dragged by hand
      if (e.pointerType === 'touch' || e.button !== 0) return;
      down = true;
      dragging = false;
      pointerId = e.pointerId;
      startX = e.clientX;
      startScroll = track.scrollLeft;
      samples = [[e.timeStamp, e.clientX]];
    });

    track.addEventListener('pointermove', function (e) {
      if (!down || e.pointerId !== pointerId) return;
      var dx = e.clientX - startX;
      if (!dragging) {
        if (Math.abs(dx) < DRAG_THRESHOLD) return;
        dragging = true;
        try { track.setPointerCapture(pointerId); } catch (err) { /* pointer already gone */ }
        track.classList.add('is-grabbing');
      }
      e.preventDefault();
      track.scrollLeft = startScroll - dx;
      samples.push([e.timeStamp, e.clientX]);
      while (samples.length > 2 && e.timeStamp - samples[0][0] > VELOCITY_WINDOW) samples.shift();
    });

    function release(e) {
      if (!down || e.pointerId !== pointerId) return;
      down = false;
      if (!dragging) return;
      dragging = false;
      track.classList.remove('is-grabbing');
      swallowClick = true;
      setTimeout(function () { swallowClick = false; }, 0);

      // Velocity of the last VELOCITY_WINDOW ms; none if the pointer rested before release
      var first = samples[0];
      var lastSample = samples[samples.length - 1];
      var dt = lastSample[0] - first[0];
      var resting = e.timeStamp - lastSample[0] > 60;
      if (e.type === 'pointerup' && dt > 0 && !resting) {
        glide(-(lastSample[1] - first[1]) / dt);
      }
    }
    track.addEventListener('pointerup', release);
    track.addEventListener('pointercancel', release);

    // The click that ends a drag is not a click
    track.addEventListener('click', function (e) {
      if (swallowClick) {
        e.preventDefault();
        e.stopPropagation();
      }
    }, true);

    // Any other way of scrolling takes over from the glide
    track.addEventListener('wheel', stopGlide, { passive: true });
    track.addEventListener('touchstart', stopGlide, { passive: true });
  }

  function stepClassic(track, direction) {
    if (track.tiagsspaceStopGlide) track.tiagsspaceStopGlide();
    var items = track.querySelectorAll('.hgallery__item');
    var inset = parseFloat(getComputedStyle(track).paddingLeft) || 0;
    var current = track.scrollLeft;
    var target = null;
    for (var i = 0; i < items.length; i++) {
      var left = items[i].offsetLeft - inset;
      if (direction > 0 && left > current + 2) { target = left; break; }
      if (direction < 0 && left < current - 2) { target = left; }
    }
    if (target === null) target = direction > 0 ? track.scrollWidth : 0;
    track.scrollTo({ left: target, behavior: prefersReducedMotion() ? 'auto' : 'smooth' });
  }

  // ------------------------------------------------------------------
  // still: one picture at a time on a timer
  // ------------------------------------------------------------------

  var stillObserver = null;

  function setupStill(track) {
    var items = track.querySelectorAll('.hgallery__item');
    var state = {
      items: items,
      index: 0,
      delay: parseInt(track.getAttribute('data-hgallery-delay'), 10) || 1800,
      timer: 0,
      inView: false
    };
    for (var i = 0; i < items.length; i++) {
      if (items[i].classList.contains('is-current')) { state.index = i; break; }
    }
    track.tiagsspaceStill = state;
    if (items.length < 2) return;

    track.addEventListener('click', function () {
      stepStill(track, 1);
    });

    if (!stillObserver) {
      stillObserver = new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
          var s = entry.target.tiagsspaceStill;
          if (!s) return;
          // Half of the gallery on screen, or (a gallery taller than two windows) half
          // the window filled by it. isIntersecting alone is true at any overlap.
          s.inView = entry.isIntersecting && (entry.intersectionRatio >= 0.5 ||
            entry.intersectionRect.height >= window.innerHeight / 2);
          scheduleStill(entry.target);
        });
      }, { threshold: [0, 0.1, 0.2, 0.3, 0.4, 0.5] });
    }
    stillObserver.observe(track);
  }

  function scheduleStill(track) {
    var s = track.tiagsspaceStill;
    clearTimeout(s.timer);
    s.timer = 0;
    if (!s.inView || document.hidden || s.items.length < 2) return;
    s.timer = setTimeout(function () {
      showStill(track, s.index + 1);
      scheduleStill(track);
    }, s.delay);
  }

  function showStill(track, index) {
    var s = track.tiagsspaceStill;
    var count = s.items.length;
    index = ((index % count) + count) % count;
    if (index === s.index) return;
    s.items[s.index].classList.remove('is-current');
    s.items[index].classList.add('is-current');
    s.index = index;
  }

  function stepStill(track, direction) {
    showStill(track, track.tiagsspaceStill.index + direction);
    scheduleStill(track); // a full delay before the next swap
  }

  document.addEventListener('visibilitychange', function () {
    tracks.forEach(function (track) {
      if (track.tiagsspaceStill) scheduleStill(track);
    });
  });

  // ------------------------------------------------------------------
  // ←/→: the gallery most in view
  // ------------------------------------------------------------------

  function mostInView() {
    var best = null;
    var bestRatio = 0.5; // at least half of it (or half the window) on screen
    var vh = window.innerHeight;
    tracks.forEach(function (track) {
      if (!track.isConnected) return;
      var r = track.getBoundingClientRect();
      if (!r.height) return;
      var visible = Math.min(r.bottom, vh) - Math.max(r.top, 0);
      var ratio = visible / Math.min(r.height, vh);
      if (ratio >= bestRatio) {
        best = track;
        bestRatio = ratio;
      }
    });
    return best;
  }

  document.addEventListener('keydown', function (e) {
    if (e.key !== 'ArrowLeft' && e.key !== 'ArrowRight') return;
    if (e.defaultPrevented || e.altKey || e.ctrlKey || e.metaKey || e.shiftKey) return;
    var t = e.target;
    if (t && (t.isContentEditable || /^(INPUT|TEXTAREA|SELECT)$/.test(t.tagName))) return;
    if (document.querySelector('.pswp--open')) return; // a lightbox has the arrows
    var track = mostInView();
    if (!track) return;
    e.preventDefault();
    var direction = e.key === 'ArrowRight' ? 1 : -1;
    if (track.tiagsspaceStill) {
      stepStill(track, direction);
    } else {
      stepClassic(track, direction);
    }
  });

  // ------------------------------------------------------------------
  // Setup
  // ------------------------------------------------------------------

  function setup(track) {
    if (!track || track.hasAttribute('data-hgallery-ready')) return;
    track.setAttribute('data-hgallery-ready', '');
    tracks = tracks.filter(function (t) { return t.isConnected; });
    tracks.push(track);
    if (track.getAttribute('data-hgallery') === 'still') {
      setupStill(track);
    } else {
      setupClassic(track);
    }
  }

  function drainQueue(fn) {
    var queue = window.tiagsspaceHGalleryQueue;
    if (Array.isArray(queue)) queue.forEach(fn);
    window.tiagsspaceHGalleryQueue = { push: fn };
  }

  window.tiagsspaceHGallery = setup;
  drainQueue(setup);
})();
