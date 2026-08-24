(function () {
  var doc = document.documentElement;
  var nav = window.navigator;
  var isStandalone = window.matchMedia && window.matchMedia("(display-mode: standalone)").matches;
  var isIosStandalone = "standalone" in nav && nav.standalone;
  var isCapacitor = !!(window.Capacitor || window.webkit && window.webkit.messageHandlers && window.webkit.messageHandlers.bridge);

  doc.classList.toggle("willow-standalone", !!(isStandalone || isIosStandalone));
  doc.classList.toggle("willow-native-shell", isCapacitor);

  if ("scrollRestoration" in window.history) {
    window.history.scrollRestoration = "manual";
  }

  window.WillowPullRefresh = {
    init: function (options) {
      options = options || {};
      var target = typeof options.target === "string" ? document.querySelector(options.target) : options.target;
      var indicator = document.querySelector("[data-pull-refresh]");
      var label = indicator ? indicator.querySelector("[data-pull-label]") : null;
      var startY = 0;
      var startX = 0;
      var distance = 0;
      var pulling = false;
      var canPull = false;
      var refreshing = false;
      var threshold = options.threshold || 64;
      var maxDistance = options.maxDistance || 92;

      if (!target || !indicator || !("ontouchstart" in window)) {
        return;
      }

      target.classList.add("willow_pull_target");

      function setDistance(nextDistance) {
        distance = Math.max(0, Math.min(maxDistance, nextDistance));
        var progress = Math.min(1, distance / threshold);
        indicator.style.setProperty("--willow-pull-progress", progress.toFixed(2));
        indicator.classList.toggle("is_visible", distance > 4 || refreshing);
        indicator.classList.toggle("is_ready", progress >= 1 && !refreshing);
        if (label) {
          label.textContent = refreshing ? "새로고침 중" : (progress >= 1 ? "놓으면 새로고침" : "아래로 당겨 새로고침");
        }
        target.style.transform = "translate3d(0," + distance + "px,0)";
      }

      function resetPull() {
        pulling = false;
        canPull = false;
        document.documentElement.classList.remove("willow_pull_active");
        target.classList.add("willow_pull_releasing");
        setDistance(0);
        window.setTimeout(function () {
          target.classList.remove("willow_pull_releasing");
          indicator.classList.remove("is_visible", "is_ready");
        }, 220);
      }

      document.addEventListener("touchstart", function (event) {
        canPull = false;
        pulling = false;
        distance = 0;

        if (refreshing || window.scrollY > 0 || event.touches.length !== 1) {
          return;
        }
        if (event.target.closest && event.target.closest("input, textarea, select, button, a, [data-topic-picker]")) {
          return;
        }
        startY = event.touches[0].clientY;
        startX = event.touches[0].clientX;
        canPull = true;
      }, { passive: true });

      document.addEventListener("touchmove", function (event) {
        if (!canPull || refreshing || event.touches.length !== 1) {
          return;
        }

        var touch = event.touches[0];
        var deltaY = touch.clientY - startY;
        var deltaX = Math.abs(touch.clientX - startX);

        if (!pulling && (window.scrollY > 0 || deltaY <= 0 || Math.abs(deltaY) < deltaX * 1.2)) {
          return;
        }

        if (deltaY > 0) {
          pulling = true;
          document.documentElement.classList.add("willow_pull_active");
          event.preventDefault();
          setDistance(deltaY * 0.46);
        }
      }, { passive: false });

      document.addEventListener("touchend", function () {
        if (!canPull || !pulling || refreshing) {
          canPull = false;
          return;
        }

        if (distance >= threshold) {
          refreshing = true;
          document.documentElement.classList.remove("willow_pull_active");
          document.documentElement.classList.add("willow_pull_refreshing");
          target.classList.add("willow_pull_releasing");
          setDistance(56);
          window.setTimeout(function () {
            window.location.reload();
          }, 420);
          return;
        }

        resetPull();
      }, { passive: true });

      document.addEventListener("touchcancel", resetPull, { passive: true });
    }
  };

  if ("serviceWorker" in nav && window.location.protocol === "https:") {
    window.addEventListener("load", function () {
      nav.serviceWorker.register("/sw.js").catch(function () {});
    });
  }

  document.addEventListener("click", function (event) {
    var link = event.target.closest && event.target.closest("a[href]");
    if (!link || link.target || link.hasAttribute("download")) {
      return;
    }

    var url;
    try {
      url = new URL(link.href);
    } catch (error) {
      return;
    }

    if (url.origin !== window.location.origin) {
      link.target = "_blank";
      link.rel = "noopener noreferrer";
    }
  });

  window.willowAppBridge = {
    isStandalone: !!(isStandalone || isIosStandalone),
    isNativeShell: isCapacitor,
    canUsePush: "Notification" in window && "serviceWorker" in nav,
    requestPushPermission: function () {
      if (!("Notification" in window)) {
        return Promise.resolve("unsupported");
      }

      if (Notification.permission !== "default") {
        return Promise.resolve(Notification.permission);
      }

      return Notification.requestPermission();
    }
  };
})();
