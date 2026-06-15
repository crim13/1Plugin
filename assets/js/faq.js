(function () {
  var setFaqIcon = function (button, expanded) {
    var iconWrap = button.querySelector(".oneplugin2-faq__icon");
    if (!iconWrap) {
      return;
    }

    var activeState = expanded ? "close" : "open";
    iconWrap.querySelectorAll("[data-oneplugin2-faq-icon-state]").forEach(function (icon) {
      icon.hidden = icon.getAttribute("data-oneplugin2-faq-icon-state") !== activeState;
      icon.setAttribute("aria-hidden", "true");
    });
  };

  var collapsePanel = function (button, panel, duration) {
    if (panel.__onePluginFaqTimer) {
      window.clearTimeout(panel.__onePluginFaqTimer);
    }

    button.setAttribute("aria-expanded", "false");
    setFaqIcon(button, false);
    panel.style.height = panel.scrollHeight + "px";
    panel.offsetHeight;
    panel.style.height = "0px";
    panel.__onePluginFaqTimer = window.setTimeout(function () {
      panel.hidden = true;
      panel.style.height = "";
      panel.__onePluginFaqTimer = null;
    }, duration);
  };

  document.addEventListener("click", function (event) {
    var button = event.target.closest("[data-oneplugin2-faq] .oneplugin2-faq__trigger");
    if (!button) {
      return;
    }

    var panel = document.getElementById(button.getAttribute("aria-controls"));
    if (!panel) {
      return;
    }

    var isExpanded = button.getAttribute("aria-expanded") === "true";
    var nextExpanded = !isExpanded;
    var animation = panel.getAttribute("data-animation") || "slide";
    var duration = parseInt(panel.getAttribute("data-duration") || "220", 10);
    var wrapper = button.closest("[data-oneplugin2-faq]");
    var accordionMode = button.getAttribute("data-accordion-mode") || "single";

    if (!duration || duration < 0) {
      duration = 220;
    }

    button.setAttribute("aria-expanded", nextExpanded ? "true" : "false");
    setFaqIcon(button, nextExpanded);

    if (animation !== "slide") {
      if (nextExpanded && accordionMode === "single" && wrapper) {
        wrapper.querySelectorAll('.oneplugin2-faq__trigger[aria-expanded="true"]').forEach(function (otherButton) {
          if (otherButton === button) {
            return;
          }
          var otherPanel = document.getElementById(otherButton.getAttribute("aria-controls"));
          if (!otherPanel) {
            return;
          }
          otherButton.setAttribute("aria-expanded", "false");
          setFaqIcon(otherButton, false);
          otherPanel.hidden = true;
        });
      }
      panel.hidden = !nextExpanded;
      return;
    }

    panel.style.transition = "height " + duration + "ms ease";
    panel.style.overflow = "hidden";
    if (panel.__onePluginFaqTimer) {
      window.clearTimeout(panel.__onePluginFaqTimer);
      panel.__onePluginFaqTimer = null;
    }

    if (nextExpanded && accordionMode === "single" && wrapper) {
      wrapper.querySelectorAll('.oneplugin2-faq__trigger[aria-expanded="true"]').forEach(function (otherButton) {
        if (otherButton === button) {
          return;
        }
        var otherPanel = document.getElementById(otherButton.getAttribute("aria-controls"));
        if (!otherPanel) {
          return;
        }
        collapsePanel(otherButton, otherPanel, duration);
      });
    }

    if (nextExpanded) {
      panel.hidden = false;
      panel.style.height = "0px";
      panel.offsetHeight;
      panel.style.height = panel.scrollHeight + "px";
      panel.__onePluginFaqTimer = window.setTimeout(function () {
        panel.style.height = "";
        panel.__onePluginFaqTimer = null;
      }, duration);
      return;
    }

    panel.style.height = panel.scrollHeight + "px";
    panel.offsetHeight;
    panel.style.height = "0px";
    panel.__onePluginFaqTimer = window.setTimeout(function () {
      panel.hidden = true;
      panel.style.height = "";
      panel.__onePluginFaqTimer = null;
    }, duration);
  });

  var initFaq = function (context) {
    (context || document).querySelectorAll("[data-oneplugin2-faq] .oneplugin2-faq__trigger").forEach(function (button) {
      setFaqIcon(button, button.getAttribute("aria-expanded") === "true");
    });
  };

  window.OnePluginFAQ = window.OnePluginFAQ || {};
  window.OnePluginFAQ.init = initFaq;

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", function () {
      initFaq(document);
    });
  } else {
    initFaq(document);
  }
})();
