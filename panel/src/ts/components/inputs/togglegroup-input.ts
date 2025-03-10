import { $ } from "../../utils/selectors";

export class TogglegroupInput {
    readonly name: string;

    readonly element: HTMLFieldSetElement;

    constructor(fieldset: HTMLFieldSetElement) {
        this.element = fieldset;

        this.name = fieldset.id;

        $(`label[for="${this.name}"]`)?.addEventListener("click", () => {
            $(`input:checked`, this.element)?.focus();
        });
    }

    get value() {
        return ($(`input:checked`, this.element) as HTMLInputElement).value;
    }

    set value(value: string) {
        const input = $(`input[value="${value}"]`, this.element) as HTMLInputElement;
        if (input) {
            input.checked = true;
        }
    }
}
