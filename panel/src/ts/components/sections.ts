import { $$ } from "../utils/selectors";

export class Sections {
    constructor() {
        $$(".collapsible .section-header").forEach((element) => {
            const section = element.parentNode as HTMLElement;

            const formName = section.closest("form")?.dataset.form;
            const key = formName ? `${formName}.${section.id}` : section.id;

            if (section.id) {
                const state = window.localStorage.getItem(`formwork.sectionStatus[${key}]`);
                if (state) {
                    section.classList.toggle("collapsed", state === "collapsed");
                }
            }

            element.addEventListener("click", () => {
                const collapsed = section.classList.toggle("collapsed");
                if (section.id) {
                    window.localStorage.setItem(`formwork.sectionStatus[${key}]`, collapsed ? "collapsed" : "expanded");
                }
            });
        });
    }
}
