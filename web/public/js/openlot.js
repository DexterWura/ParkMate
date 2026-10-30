(function () {
    "use strict";

    function ready(fn) {
        if (document.readyState !== "loading") fn();
        else document.addEventListener("DOMContentLoaded", fn);
    }

    ready(function () {
        var body = document.body;
        var menuBtn = document.querySelector("[data-ol-menu]");
        var backdrop = document.querySelector("[data-ol-backdrop]");

        function closeNav() {
            body.classList.remove("ol-nav-open");
            if (menuBtn) menuBtn.setAttribute("aria-expanded", "false");
        }

        function openNav() {
            body.classList.add("ol-nav-open");
            if (menuBtn) menuBtn.setAttribute("aria-expanded", "true");
        }

        if (menuBtn) {
            menuBtn.addEventListener("click", function () {
                if (body.classList.contains("ol-nav-open")) closeNav();
                else openNav();
            });
        }

        if (backdrop) backdrop.addEventListener("click", closeNav);

        document.querySelectorAll(".ol-nav a").forEach(function (link) {
            link.addEventListener("click", closeNav);
        });

        document.addEventListener("keydown", function (event) {
            if (event.key === "Escape") closeNav();
        });

        document.querySelectorAll("[data-ol-dismiss]").forEach(function (btn) {
            btn.addEventListener("click", function () {
                var target = btn.closest(".ol-flash, .flash, .ol-errors, .errors");
                if (target) target.remove();
            });
        });

        document.querySelectorAll("form[data-ol-confirm]").forEach(function (form) {
            form.addEventListener("submit", function (event) {
                var message = form.getAttribute("data-ol-confirm") || "Are you sure?";
                if (!window.confirm(message)) event.preventDefault();
            });
        });

        document.querySelectorAll(".ol-table-wrap").forEach(function (wrap) {
            function syncShadow() {
                var max = wrap.scrollWidth - wrap.clientWidth;
                wrap.classList.toggle("is-scrollable", max > 8);
                wrap.classList.toggle("is-scrolled", wrap.scrollLeft > 4);
            }
            wrap.addEventListener("scroll", syncShadow, { passive: true });
            window.addEventListener("resize", syncShadow);
            syncShadow();
        });

        var connection = document.getElementById("db_connection");
        if (connection) {
            var mysqlOnly = document.querySelectorAll("[data-mysql-only]");
            function syncDbFields() {
                var isMysql = connection.value === "mysql";
                mysqlOnly.forEach(function (el) {
                    el.hidden = !isMysql;
                    el.querySelectorAll("input, select").forEach(function (input) {
                        input.disabled = !isMysql;
                    });
                });
            }
            connection.addEventListener("change", syncDbFields);
            syncDbFields();
        }
    });
})();
