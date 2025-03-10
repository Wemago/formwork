import { $, $$ } from "../../utils/selectors";
export class ImagePicker {
    readonly element: HTMLSelectElement;
    readonly name: string;

    constructor(element: HTMLSelectElement) {
        this.element = element;
        this.name = this.element.name;

        this.initInput();
    }

    get value() {
        return this.element.value;
    }

    private initInput() {
        this.element.hidden = true;
        this.element.ariaHidden = "true";
        this.element.tabIndex = -1;

        const pickImage = (thumbnail: HTMLElement) => {
            $$(".image-picker-thumbnail").forEach((element) => {
                element.classList.remove("selected");
            });
            thumbnail.classList.add("selected");
            this.element.value = thumbnail.dataset.uri ?? "";
        };

        const options = $$("option", this.element);

        if (options.length > 0) {
            const container = document.createElement("div");
            container.className = "image-picker-thumbnails";

            for (const option of Array.from(options) as HTMLOptionElement[]) {
                const thumbnail = document.createElement("div");
                thumbnail.className = "image-picker-thumbnail";
                thumbnail.style.backgroundImage = `url(${option.dataset.thumbnail ?? option.value})`;
                thumbnail.dataset.uri = option.value;
                thumbnail.dataset.filename = option.text;
                thumbnail.addEventListener("click", () => pickImage(thumbnail));
                thumbnail.addEventListener("dblclick", () => pickImage(thumbnail));
                container.appendChild(thumbnail);
            }

            (this.element.parentNode as ParentNode).insertBefore(container, this.element);
            ($(".image-picker-empty-state") as HTMLElement).style.display = "none";
        }
    }
}
