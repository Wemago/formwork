import { makeSlug, validateSlug } from "../../utils/validation";
import { $ } from "../../utils/selectors";

export class SlugInput {
    readonly element: HTMLInputElement;
    readonly name: string;

    constructor(element: HTMLInputElement) {
        this.element = element;
        this.name = this.element.name;

        this.initInput();
    }

    get value() {
        return this.element.value;
    }

    private initInput() {
        const source = $(`[id="${this.element.dataset.source}"]`) as HTMLInputElement | null;
        const autoUpdate = "autoUpdate" in this.element.dataset && this.element.dataset.autoUpdate === "true";

        if (source) {
            if (autoUpdate) {
                source.addEventListener("input", () => (this.element.value = makeSlug(source.value)));
                this.element.value = makeSlug(source.value);
            } else {
                const generateButton = $(`[data-generate-slug="${this.element.id}"]`) as HTMLButtonElement | null;
                if (generateButton) {
                    generateButton.addEventListener("click", () => (this.element.value = makeSlug(source.value)));
                }
            }
        }

        const handleSlugChange = (event: Event) => {
            const target = event.target as HTMLInputElement;
            target.value = validateSlug(target.value);
        };

        this.element.addEventListener("keyup", handleSlugChange);
        this.element.addEventListener("blur", handleSlugChange);
    }
}
