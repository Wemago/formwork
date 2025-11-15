import { $ } from "../../utils/selectors";

export class ColorInput {
    readonly element: HTMLInputElement;

    constructor(element: HTMLInputElement) {
        this.element = element;

        this.initInput();
    }

    get name() {
        return this.element.name;
    }

    set name(value: string) {
        this.element.name = value;
    }

    get value() {
        return this.element.value;
    }

    set value(value: string) {
        this.element.value = value;
    }

    private initInput() {
        const outputElement = $(`output[for="${this.element.id}"]`);

        if (outputElement) {
            const updateValueLabel = (element: HTMLInputElement) => {
                outputElement.innerHTML = element.value;
            };

            this.element.addEventListener("change", () => updateValueLabel(this.element));
            this.element.addEventListener("input", () => updateValueLabel(this.element));

            updateValueLabel(this.element);

            outputElement.addEventListener("click", () => {
                this.element.focus();
                this.element.click();
            });
        }
    }
}
