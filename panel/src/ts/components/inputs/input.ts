export class Input {
    readonly element: HTMLInputElement | HTMLSelectElement | HTMLTextAreaElement;

    constructor(element: HTMLInputElement | HTMLSelectElement | HTMLTextAreaElement) {
        this.element = element;
    }

    get name(): string {
        return this.element.name;
    }

    set name(value: string) {
        this.element.name = value;
    }

    get value(): string {
        return this.element.value;
    }

    set value(value: string) {
        this.element.value = value;
    }
}
