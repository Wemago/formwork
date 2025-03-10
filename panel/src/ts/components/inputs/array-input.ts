import { $, $$ } from "../../utils/selectors";
import Sortable from "sortablejs";

export class ArrayInput {
    readonly element: HTMLInputElement;

    readonly name: string;

    readonly isAssociative: boolean;

    constructor(element: HTMLInputElement) {
        this.element = element;

        this.name = element.dataset.name as string;

        this.isAssociative = element.classList.contains("form-input-array-associative");

        $$(".form-input-array-row", element).forEach((element) => this.bindRowEvents(element));

        $(`label[for="${element.id}"]`)?.addEventListener("click", () => $(".form-input", element)?.focus());

        Sortable.create(element, {
            handle: ".sortable-handle",
            forceFallback: true,
            invertSwap: true,
            swapThreshold: 0.75,
            animation: 150,
        });
    }

    get value(): string {
        const values: { [key: string]: string } = {};

        let i = 0;

        $$(".form-input-array-row", $(`[data-name="${this.name}"]`) as HTMLElement).forEach((row) => {
            const inputKey = $(".form-input-array-key", row) as HTMLInputElement;
            const inputValue = $(".form-input-array-value", row) as HTMLInputElement;

            const key = inputKey.value.trim();
            const value = inputValue.value.trim();

            if (this.isAssociative && key) {
                values[key] = value;
            } else if (value) {
                values[i++] = value;
            }
        });

        return JSON.stringify(values);
    }

    private addRow(row: HTMLElement) {
        const clone = row.cloneNode(true) as HTMLElement;
        const parent = row.parentNode as ParentNode;
        this.clearRow(clone);
        this.bindRowEvents(clone);
        if (row.nextSibling) {
            parent.insertBefore(clone, row.nextSibling);
        } else {
            parent.appendChild(clone);
        }
    }

    private removeRow(row: HTMLElement) {
        const parent = row.parentNode as ParentNode;
        if ($$(".form-input-array-row", parent).length > 1) {
            parent.removeChild(row);
        } else {
            this.clearRow(row);
        }
    }

    private clearRow(row: HTMLElement) {
        if (this.isAssociative) {
            const inputKey = $(".form-input-array-key", row) as HTMLInputElement;
            inputKey.value = "";
            inputKey.removeAttribute("value");
        }
        const inputValue = $(".form-input-array-value", row) as HTMLInputElement;
        inputValue.value = "";
        inputValue.removeAttribute("value");
        inputValue.name = `${this.name}[]`;
    }

    private updateAssociativeRow(row: HTMLElement) {
        const inputKey = $(".form-input-array-key", row) as HTMLInputElement;
        const inputValue = $(".form-input-array-value", row) as HTMLInputElement;
        inputValue.name = `${this.name}[${inputKey.value.trim()}]`;
    }

    private bindRowEvents(row: HTMLElement) {
        const inputAdd = $(".form-input-array-add", row) as HTMLButtonElement;
        const inputRemove = $(".form-input-array-remove", row) as HTMLButtonElement;

        inputAdd.addEventListener("click", () => this.addRow(row));
        inputRemove.addEventListener("click", () => this.removeRow(row));

        if (this.isAssociative) {
            const inputKey = $(".form-input-array-key", row) as HTMLInputElement;
            const inputValue = $(".form-input-array-value", row) as HTMLInputElement;
            inputKey.addEventListener("keyup", () => this.updateAssociativeRow(row));
            inputValue.addEventListener("keyup", () => this.updateAssociativeRow(row));
        }
    }
}
