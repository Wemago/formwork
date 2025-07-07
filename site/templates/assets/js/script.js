window.addEventListener("load", function () {
    /** Toggleable menus handler **/
    const menuToggles = document.getElementsByClassName("menu-toggle button");
    for (const toggle of menuToggles) {
        toggle.addEventListener("click", () => {
            const id = toggle.getAttribute("data-toggle");
            const element = document.getElementById(id);
            helpers.toggleElement(element, 250);
            element.classList.toggle("menu-expanded");
            if (!element.ariaExpaned) {
                element.ariaExpaned = true;
            } else {
                element.ariaExpaned = false;
            }
        });
    }
});

const helpers = {
    /**
     * Measures real element height as if it was rendered with
     * `display: block` and `height: auto` CSS properties
     */
    measureElementHeight: function (element) {
        const styleHeight = element.style.height;
        const styleDisplay = element.style.height;
        element.style.height = "";
        element.style.display = "block";
        const height = element.clientHeight;
        element.style.height = styleHeight;
        element.style.display = styleDisplay;
        return height;
    },

    /**
     * Toggles an element animating its height
     */
    toggleElement: function (element, duration) {
        const direction = element.clientHeight === 0 ? 1 : -1;
        const measuredHeight = helpers.measureElementHeight(element);
        let steps = Math.floor(duration / 10);
        const delta = (measuredHeight / steps) * direction;
        if (direction > 0) {
            element.style.height = 0;
        } else {
            Object.assign(element.style, {
                display: "block",
                flex: "1 0 100%",
                height: measuredHeight + "px",
            });
        }
        const interval = window.setInterval(() => {
            if (steps-- >= 0) {
                element.style.height = parseInt(element.style.height) + delta + "px";
            } else {
                Object.assign(element.style, {
                    flex: "",
                    display: "",
                    height: "",
                });
                window.clearInterval(interval);
            }
        }, 10);
    },
};
