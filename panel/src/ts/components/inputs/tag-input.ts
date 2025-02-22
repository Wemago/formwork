import { $, $$ } from "../../utils/selectors";
import { escapeRegExp, makeDiacriticsRegExp } from "../../utils/validation";
import { debounce } from "../../utils/events";
import { insertIcon } from "../icons";
import Sortable from "sortablejs";

interface TagInputOptions {
    labels: { [key: string]: string };
    addKeyCodes: string[];
    limit: number;
    accept: "options" | "any";
    orderable: boolean;
}

interface TagInputDropdownItem {
    value: string;
    icon?: string;
    thumb?: string;
}

export class TagInput {
    constructor(input: HTMLInputElement, userOptions: Partial<TagInputOptions>) {
        const defaults = { addKeyCodes: ["Comma"], limit: Infinity, accept: "options", orderable: true };

        const options = Object.assign({}, defaults, userOptions);

        let tags: string[] = [];
        let placeholder: string, dropdown: HTMLElement | undefined;

        const field = document.createElement("div");
        const list = document.createElement("span");
        const innerInput = document.createElement("input");
        const hiddenInput = document.createElement("input");

        createField();
        createDropdown();

        registerInputEvents();

        function createField() {
            const isRequired = input.hasAttribute("required");
            const isDisabled = input.hasAttribute("disabled");

            if ("limit" in input.dataset) {
                options.limit = parseInt(input.dataset.limit as string);
            }

            if (!("orderable" in input.dataset)) {
                options.orderable = false;
            }

            field.className = "form-input-tag";

            innerInput.className = "form-input tag-inner-input";
            innerInput.type = "text";
            innerInput.placeholder = input.placeholder;

            hiddenInput.className = "form-input-tag-hidden";
            hiddenInput.name = input.name;
            hiddenInput.id = input.id;
            hiddenInput.type = "text";
            hiddenInput.value = input.value;
            hiddenInput.readOnly = true;
            hiddenInput.hidden = true;

            if (isRequired) {
                hiddenInput.required = true;
            }

            if (isDisabled) {
                field.setAttribute("disabled", "disabled");
                innerInput.disabled = true;
                hiddenInput.disabled = true;
            }

            (input.parentNode as ParentNode).replaceChild(field, input);
            field.appendChild(list);
            field.appendChild(innerInput);
            field.appendChild(hiddenInput);

            if (hiddenInput.value) {
                tags = hiddenInput.value.split(", ");
                tags.forEach((value, index) => {
                    value = value.trim();
                    tags[index] = value;
                    insertTag(value, list);
                });
            }

            if (innerInput.placeholder) {
                placeholder = innerInput.placeholder;
                updatePlaceholder();
            } else {
                placeholder = "";
            }

            field.addEventListener("mousedown", (event) => {
                innerInput.focus();
                event.preventDefault();
            });

            if (options.orderable) {
                Sortable.create(list, {
                    forceFallback: true,
                    animation: 150,
                    filter: ".tag-remove",

                    onStart() {
                        field.classList.add("is-dragging");
                        if (dropdown) {
                            dropdown.style.display = "none";
                        }
                    },

                    onFilter(event: Sortable.SortableEvent) {
                        if (event.target.matches(".tag-remove")) {
                            removeTag(event.item.innerText);
                            list.removeChild(event.item);
                        }
                    },

                    onEnd(event: Sortable.SortableEvent) {
                        field.classList.remove("is-dragging");
                        const newIndex = event.newIndex;
                        const oldIndex = event.oldIndex;
                        innerInput.blur();
                        innerInput.focus();
                        if (newIndex === oldIndex || newIndex === undefined || oldIndex === undefined) {
                            return;
                        }
                        tags.splice(newIndex, 0, tags.splice(oldIndex, 1)[0]);
                        updateTags();
                    },
                });
            }
        }

        function createDropdown() {
            if ("options" in input.dataset) {
                const list: { [key: string | number]: string | TagInputDropdownItem } = JSON.parse(input.dataset.options ?? "{}");
                const isAssociative = !Array.isArray(list);

                if ("accept" in input.dataset) {
                    options.accept = (input.dataset.accept ?? "options") as "options" | "any";
                }

                dropdown = document.createElement("div");
                dropdown.className = "dropdown-list";
                dropdown.style.display = "none";

                for (const key in list) {
                    const item = document.createElement("div");

                    const { value, icon, thumb } = typeof list[key] === "object" ? list[key] : { value: list[key], icon: undefined, thumb: undefined };

                    item.className = "dropdown-item";
                    item.innerHTML = value;
                    item.dataset.value = isAssociative ? key : value;

                    if (thumb) {
                        const img = document.createElement("img");
                        img.src = thumb;
                        img.className = "dropdown-thumb";
                        item.insertAdjacentElement("afterbegin", img);
                    } else if (icon) {
                        insertIcon(icon, item);
                    }

                    item.addEventListener("click", function () {
                        if (this.dataset.value) {
                            addTag(this.dataset.value);
                        }
                    });
                    dropdown.appendChild(item);
                }

                field.appendChild(dropdown);

                innerInput.addEventListener("focus", () => {
                    if (dropdown && getComputedStyle(dropdown).display === "none") {
                        updateDropdown();
                        dropdown.scrollTop = 0;
                    }
                });

                innerInput.addEventListener("blur", () => {
                    if (dropdown && getComputedStyle(dropdown).display !== "none") {
                        updateDropdown();
                        dropdown.style.display = "none";
                    }
                });

                innerInput.addEventListener("keydown", (event) => {
                    switch (event.key) {
                        case "Backspace":
                            updateDropdown();
                            break;
                        case "Enter":
                            if (dropdown && getComputedStyle(dropdown).display !== "none") {
                                addTagFromSelectedDropdownItem();
                                event.preventDefault();
                            }
                            break;
                        case "ArrowUp":
                            if (dropdown && getComputedStyle(dropdown).display !== "none") {
                                selectPrevDropdownItem();
                                event.preventDefault();
                            }
                            break;
                        case "ArrowDown":
                            if (dropdown && getComputedStyle(dropdown).display !== "none") {
                                selectNextDropdownItem();
                                event.preventDefault();
                            }
                            break;
                        default:
                            if (options.addKeyCodes.includes(event.code)) {
                                if (dropdown && getComputedStyle(dropdown).display !== "none") {
                                    addTagFromSelectedDropdownItem();
                                    event.preventDefault();
                                }
                            }
                    }
                });

                innerInput.addEventListener(
                    "keyup",
                    debounce((event: KeyboardEvent) => {
                        const value = innerInput.value.trim();
                        switch (event.key) {
                            case "Escape":
                                if (dropdown) {
                                    dropdown.style.display = "none";
                                }
                                break;
                            case "ArrowUp":
                            case "ArrowDown":
                                return true;
                            default:
                                if (tags.length >= options.limit) {
                                    event.preventDefault();
                                }
                                filterDropdown(value);
                                if (value.length > 0) {
                                    selectFirstDropdownItem();
                                }
                        }
                    }, 100),
                );
            }
        }

        function registerInputEvents() {
            innerInput.addEventListener("focus", () => field.classList.add("focused"));

            innerInput.addEventListener("blur", () => {
                const value = innerInput.value.trim();
                if (value !== "") {
                    addTag(value);
                }
                field.classList.remove("focused");
            });

            innerInput.addEventListener("keydown", (event) => {
                innerInput.classList.remove("form-input-invalid");
                const value = innerInput.value.trim();
                switch (event.key) {
                    case "Backspace":
                        if (value === "") {
                            removeTag(tags[tags.length - 1]);
                            const lastTag = list.childNodes[list.childNodes.length - 1];
                            if (lastTag) {
                                list.removeChild(lastTag);
                            }
                            event.preventDefault();
                        } else {
                            innerInput.size = Math.max(innerInput.value.length, innerInput.placeholder.length, 1);
                        }
                        break;
                    case "Enter":
                    case "Comma":
                        event.preventDefault();
                        if (value !== "") {
                            if (tags.length >= options.limit || tags.includes(value)) {
                                innerInput.classList.add("form-input-invalid");
                            } else {
                                addTag(value);
                            }
                        }
                        break;
                    case "Escape":
                        clearInput();
                        innerInput.blur();
                        event.preventDefault();
                        break;
                    default:
                        if (options.addKeyCodes.includes(event.key)) {
                            event.preventDefault();
                            if (value !== "") {
                                if (tags.length >= options.limit || tags.includes(value)) {
                                    innerInput.classList.add("form-input-invalid");
                                } else {
                                    addTag(value);
                                }
                            }
                            break;
                        }
                        if (value.length > 0) {
                            innerInput.size = innerInput.value.length + 2;
                        }
                        break;
                }
            });
        }

        function updateTags() {
            hiddenInput.value = tags.join(", ");
            hiddenInput.dispatchEvent(new Event("input", { bubbles: true }));
            hiddenInput.dispatchEvent(new Event("change", { bubbles: true }));
            updatePlaceholder();
        }

        function updatePlaceholder() {
            if (placeholder.length > 0) {
                if (tags.length === 0) {
                    innerInput.placeholder = placeholder;
                    innerInput.size = placeholder.length;
                } else {
                    innerInput.placeholder = "";
                    innerInput.size = 1;
                }
            }
        }

        function validateTag(value: string) {
            if (tags.length >= options.limit) {
                return false;
            }
            if (!tags.includes(value)) {
                if (dropdown && options.accept === "options") {
                    return $(`[data-value="${value}"]`, dropdown) !== null;
                }
                return true;
            }
            return false;
        }

        function insertTag(value: string, parent: HTMLElement) {
            const tag = document.createElement("span");
            const tagRemove = document.createElement("i");
            tag.className = "tag";
            tag.innerHTML = value;
            tag.style.marginRight = ".25rem";
            parent.appendChild(tag);

            tagRemove.className = "tag-remove";
            tagRemove.setAttribute("role", "button");
            tagRemove.addEventListener("mousedown", (event) => {
                removeTag(value);
                parent.removeChild(tag);
                event.preventDefault();
            });
            tag.appendChild(tagRemove);
        }

        function addTag(value: string) {
            if (validateTag(value)) {
                tags.push(value);
                insertTag(value, list);
                updateTags();
            } else {
                updatePlaceholder();
            }
            innerInput.value = "";
            updateDropdown();
        }

        function removeTag(value: string) {
            const index = tags.indexOf(value);
            if (index > -1) {
                tags.splice(index, 1);
                updateTags();
            }
            updateDropdown();
        }

        function clearInput() {
            innerInput.value = "";
            updatePlaceholder();
        }

        function updateDropdown() {
            if (!dropdown) {
                return;
            }
            if (tags.length >= options.limit) {
                dropdown.style.display = "none";
                return;
            }
            let visibleItems = 0;
            $$(".dropdown-item", dropdown).forEach((element) => {
                if (!tags.includes(element.dataset.value as string)) {
                    element.style.display = "block";
                    visibleItems++;
                } else {
                    element.style.display = "none";
                }
                element.classList.remove("selected");
            });
            if (visibleItems > 0) {
                dropdown.style.display = "block";
            } else {
                dropdown.style.display = "none";
            }
        }

        function filterDropdown(value: string) {
            if (!dropdown) {
                return;
            }
            if (value === "" && tags.length < options.limit) {
                dropdown.style.display = "block";
                return;
            }
            let visibleItems = 0;
            $$(".dropdown-item", dropdown).forEach((element) => {
                if (value === "") {
                    return true;
                }
                const text = `${element.textContent}`;
                const regexp = new RegExp(`(^|\\b)${makeDiacriticsRegExp(escapeRegExp(value))}`, "i");
                if (text.match(regexp) !== null && element.style.display !== "none") {
                    element.style.display = "block";
                    visibleItems++;
                } else {
                    element.style.display = "none";
                }
            });
            if (visibleItems > 0) {
                dropdown.style.display = "block";
            } else {
                dropdown.style.display = "none";
            }
        }

        function scrollToDropdownItem(item: HTMLElement) {
            if (!dropdown) {
                return;
            }
            const dropdownScrollTop = dropdown.scrollTop;
            const dropdownHeight = dropdown.clientHeight;
            const dropdownScrollBottom = dropdownScrollTop + dropdownHeight;
            const dropdownStyle = getComputedStyle(dropdown);
            const dropdownPaddingTop = parseInt(dropdownStyle.paddingTop);
            const dropdownPaddingBottom = parseInt(dropdownStyle.paddingBottom);
            const itemTop = item.offsetTop;
            const itemHeight = item.clientHeight;
            const itemBottom = itemTop + itemHeight;
            if (itemTop < dropdownScrollTop) {
                dropdown.scrollTop = itemTop - dropdownPaddingTop;
            } else if (itemBottom > dropdownScrollBottom) {
                dropdown.scrollTop = itemBottom - dropdownHeight + dropdownPaddingBottom;
            }
        }

        function addTagFromSelectedDropdownItem() {
            const selectedItem = $(".dropdown-item.selected", dropdown);
            if (selectedItem && getComputedStyle(selectedItem).display !== "none") {
                innerInput.value = selectedItem.dataset.value as string;
            }
        }

        function selectDropdownItem(item: HTMLElement) {
            const selectedItem = $(".dropdown-item.selected", dropdown);
            if (selectedItem) {
                selectedItem.classList.remove("selected");
            }
            if (item) {
                item.classList.add("selected");
                scrollToDropdownItem(item);
            }
        }

        function selectFirstDropdownItem() {
            const items = $$(".dropdown-item", dropdown);
            for (let i = 0; i < items.length; i++) {
                if (getComputedStyle(items[i]).display !== "none") {
                    selectDropdownItem(items[i]);
                    return;
                }
            }
        }

        function selectLastDropdownItem() {
            const items = $$(".dropdown-item", dropdown);
            for (let i = items.length - 1; i >= 0; i--) {
                if (getComputedStyle(items[i]).display !== "none") {
                    selectDropdownItem(items[i]);
                    return;
                }
            }
        }

        function selectPrevDropdownItem() {
            const selectedItem = $(".dropdown-item.selected", dropdown);
            if (selectedItem) {
                let previousItem = selectedItem.previousSibling as HTMLElement;
                while (previousItem && previousItem.style.display === "none") {
                    previousItem = previousItem.previousSibling as HTMLElement;
                }
                if (previousItem) {
                    return selectDropdownItem(previousItem);
                }
                selectDropdownItem(selectedItem.previousSibling as HTMLElement);
            }
            selectLastDropdownItem();
        }

        function selectNextDropdownItem() {
            const selectedItem = $(".dropdown-item.selected", dropdown);
            if (selectedItem) {
                let nextItem = selectedItem.nextSibling as HTMLElement;
                while (nextItem && nextItem.style.display === "none") {
                    nextItem = nextItem.nextSibling as HTMLElement;
                }
                if (nextItem) {
                    return selectDropdownItem(nextItem as HTMLElement);
                }
            }
            selectFirstDropdownItem();
        }
    }
}
