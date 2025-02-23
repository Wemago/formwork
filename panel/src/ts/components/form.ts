import "../polyfills/request-submit";
import { $, $$ } from "../utils/selectors";
import { app } from "../app";
import { Inputs } from "./inputs";
import { serializeForm } from "../utils/forms";

export class Form {
    inputs: Inputs;
    originalData: string;
    element: HTMLFormElement;

    constructor(form: HTMLFormElement) {
        this.element = form;

        this.inputs = new Inputs(form);

        // Serialize after inputs are loaded
        this.originalData = serializeForm(form);

        const handleBeforeunload = (event: Event) => {
            if (this.hasChanged()) {
                event.preventDefault();
                event.returnValue = false;
            }
        };

        const removeBeforeUnload = () => {
            window.removeEventListener("beforeunload", handleBeforeunload);
        };

        window.addEventListener("beforeunload", handleBeforeunload);

        form.addEventListener("submit", removeBeforeUnload);

        $$('a[href]:not([href^="#"]):not([target="_blank"]):not([target^="formwork-"])').forEach((element: HTMLAnchorElement) => {
            if (element.closest(".editor-wrap")) {
                return;
            }
            element.addEventListener("click", (event) => {
                if (this.hasChanged()) {
                    event.preventDefault();

                    app.modals["changesModal"].onOpen((modal) => {
                        const continueCommand = $("[data-command=continue]", modal.element);
                        if (continueCommand) {
                            continueCommand.dataset.href = element.href;
                        }
                    });

                    app.modals["changesModal"].open();
                }
            });
        });

        $$("input[type=file][data-auto-upload=true]", form).forEach((element) => {
            element.addEventListener("change", () => {
                if (!this.hasChanged(false)) {
                    form.requestSubmit($("[type=submit]", form));
                }
            });
        });

        registerModalExceptions();

        function registerModalExceptions() {
            const changesModal = app.modals["changesModal"];
            const deletePageModal = app.modals["deletePageModal"];
            const deleteUserModal = app.modals["deleteUserModal"];

            if (changesModal) {
                changesModal.onCommand("continue", (_, button) => {
                    removeBeforeUnload();
                    if (button?.dataset.href) {
                        window.location.href = button.dataset.href;
                    }
                });
            }

            if (deletePageModal) {
                deletePageModal.onCommand("delete", () => {
                    removeBeforeUnload();
                });
            }

            if (deleteUserModal) {
                deleteUserModal.onCommand("delete", () => {
                    removeBeforeUnload();
                });
            }
        }
    }

    hasChanged(checkFileInputs: boolean = true) {
        const fileInputs = $$("input[type=file]", this.element) as NodeListOf<HTMLInputElement>;

        if (checkFileInputs === true && fileInputs.length > 0) {
            for (const fileInput of Array.from(fileInputs)) {
                if (fileInput.files && fileInput.files.length > 0) {
                    return true;
                }
            }
        }

        return serializeForm(this.element) !== this.originalData;
    }
}
