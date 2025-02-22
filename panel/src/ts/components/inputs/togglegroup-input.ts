import { $ } from "../../utils/selectors";

export class TogglegroupInput {
    constructor(element: HTMLFieldSetElement) {
        $(`label[for="${element.id}"]`)?.addEventListener("click", () => {
            $(`input[type="radio"]:checked`, element)?.focus();
        });
    }
}
