import { $, $$ } from "../../utils/selectors";
export class ImagePicker {
    constructor(element: HTMLSelectElement) {
        const options = $$("option", element);

        element.hidden = true;
        element.ariaHidden = "true";
        element.tabIndex = -1;

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

            (element.parentNode as ParentNode).insertBefore(container, element);
            ($(".image-picker-empty-state") as HTMLElement).style.display = "none";
        }

        function pickImage(thumbnail: HTMLElement) {
            $$(".image-picker-thumbnail").forEach((element) => {
                element.classList.remove("selected");
            });
            thumbnail.classList.add("selected");
        }
    }
}
