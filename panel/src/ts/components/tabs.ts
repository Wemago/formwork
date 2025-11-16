import { $, $$ } from "../utils/selectors";

export class Tabs {
    constructor() {
        $$(".tabs").forEach((tabs) => {
            const tabButtons = $$(".tabs-tab", tabs);
            const tabPanels: HTMLElement[] = [];

            tabButtons.forEach((tabButton) => {
                const targetPanel = $(`.tabs-panel[data-tab="${tabButton.dataset.tab}"]`);

                if (targetPanel) {
                    tabPanels.push(targetPanel);
                }

                tabButton.addEventListener("click", () => {
                    tabButtons.forEach((button) => button.classList.remove("active"));
                    tabPanels.forEach((panel) => panel.classList.remove("visible"));

                    tabButton.classList.add("active");

                    targetPanel?.classList.add("visible");
                });
            });
        });
    }
}
