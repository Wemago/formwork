import { $, $$ } from "../utils/selectors";

export class Tabs {
    constructor() {
        $$(".tabs").forEach((tabs) => {
            const tabButtons = $$(".tabs-tab", tabs);
            tabButtons.forEach((tabButton) => {
                tabButton.addEventListener("click", () => {
                    tabButtons.forEach((button) => {
                        button.classList.toggle("active", button === tabButton);
                        button.ariaSelected = (button === tabButton).toString();
                        const tabPanel = $(`.tabs-panel[data-tab="${button.dataset.tab}"]`);
                        tabPanel?.classList.toggle("visible", button === tabButton);
                    });
                });
            });
        });
    }
}
