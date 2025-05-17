import "../polyfills/request-submit";
import { $, $$ } from "../utils/selectors";
import { app } from "../app";
import { ArrayInput } from "./inputs/array-input";
import { ColorInput } from "./inputs/color-input";
import { DateInput } from "./inputs/date-input";
import { DurationInput } from "./inputs/duration-input";
import { EditorInput } from "./inputs/editor-input";
import { ImagePicker } from "./inputs/image-picker";
import { RangeInput } from "./inputs/range-input";
import { SelectInput } from "./inputs/select-input";
import { serializeForm } from "../utils/forms";
import { SlugInput } from "./inputs/slug-input";
import { TagsInput } from "./inputs/tags-input";
import { TogglegroupInput } from "./inputs/togglegroup-input";
import { UploadInput } from "./inputs/upload-input";

interface FormOptions {
    preventUnloadOnChanges?: boolean;
}

interface Input {
    name: string;
    value: string;
}
export class Form {
    inputs: { [name: string]: Input } = {};

    originalData: string;
    element: HTMLFormElement;
    options: FormOptions = {
        preventUnloadOnChanges: true,
    };

    constructor(form: HTMLFormElement, options: Partial<FormOptions> = {}) {
        this.element = form;

        this.loadInputs();

        // Serialize after inputs are loaded
        this.originalData = serializeForm(form);

        this.options = { ...this.options, ...options };

        if (this.options.preventUnloadOnChanges) {
            this.preventUnloadOnChanges();
        }
    }

    private loadInputs() {
        const parent = this.element;

        $$(".editor-textarea", parent).forEach((element: HTMLTextAreaElement) => (this.inputs[element.name] = new EditorInput(element)));

        $$(".form-input-color", parent).forEach((element: HTMLInputElement) => (this.inputs[element.name] = new ColorInput(element)));

        $$(".form-input-array", parent).forEach((element: HTMLInputElement) => (this.inputs[element.dataset.name as string] = new ArrayInput(element)));

        $$(".form-input-date", parent).forEach((element: HTMLInputElement) => (this.inputs[element.name] = new DateInput(element, app.config.DateInput)));

        $$(".form-input-duration", parent).forEach((element: HTMLInputElement) => (this.inputs[element.name] = new DurationInput(element, app.config.DurationInput)));

        $$(".form-input-slug", parent).forEach((element: HTMLInputElement) => (this.inputs[element.name] = new SlugInput(element)));

        $$(".form-input-tags", parent).forEach((element: HTMLInputElement) => (this.inputs[element.name] = new TagsInput(element, app.config.TagsInput)));

        $$(".form-togglegroup[id]", parent).forEach((element: HTMLFieldSetElement) => (this.inputs[element.id] = new TogglegroupInput(element)));

        $$(".image-picker", parent).forEach((element: HTMLSelectElement) => (this.inputs[element.name] = new ImagePicker(element)));

        $$("input[type=file]", parent).forEach((element: HTMLInputElement) => (this.inputs[element.name] = new UploadInput(element, this)));

        $$("input[type=range]", parent).forEach((element: HTMLInputElement) => (this.inputs[element.name] = new RangeInput(element)));

        $$("select:not([hidden])", parent).forEach((element: HTMLSelectElement) => (this.inputs[element.name] = new SelectInput(element, app.config.SelectInput)));

        $$(".form-input-action[data-reset]", parent).forEach((element) => {
            const targetId = element.dataset.reset;
            if (targetId) {
                element.addEventListener("click", () => {
                    const target = document.getElementById(targetId) as HTMLInputElement;
                    target.value = "";
                    target.dispatchEvent(new Event("input", { bubbles: true }));
                    target.dispatchEvent(new Event("change", { bubbles: true }));
                });
            }
        });

        $$("input[data-enable]", parent).forEach((element: HTMLInputElement) => {
            element.addEventListener("change", () => {
                const targetId = element.dataset.enable;
                if (targetId) {
                    const inputs = targetId.split(",");
                    for (const name of inputs) {
                        const input = $(`input[name="${name}"]`) as HTMLInputElement;
                        if (!element.checked) {
                            input.disabled = true;
                        } else {
                            input.disabled = false;
                        }
                    }
                }
            });
        });
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

    private preventUnloadOnChanges() {
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

        this.element.addEventListener("submit", removeBeforeUnload);

        const changesModal = app.modals["changesModal"];

        if (changesModal) {
            changesModal.onCommand("continue", (_, button) => {
                removeBeforeUnload();
                if (button?.dataset.href) {
                    window.location.href = button.dataset.href;
                }
            });

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
        }
    }
}
