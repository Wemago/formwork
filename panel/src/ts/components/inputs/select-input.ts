import { $, $$ } from "../../utils/selectors";
import { escapeRegExp, makeDiacriticsRegExp } from "../../utils/validation";

type SelectInputListItem = {
    label: string;
    value: string;
    selected: boolean;
    disabled: boolean;
    dataset: Record<string, string>;
};

interface SelectInputOptions {
    labels: {
        empty: string;
    };
}

export class SelectInput {
    constructor(select: HTMLSelectElement, userOptions: Partial<SelectInputOptions>) {
        const defaults: SelectInputOptions = { labels: { empty: "No matching options" } };

        const options = Object.assign({}, defaults, userOptions);

        let dropdown: HTMLElement;

        const labelInput = document.createElement("input");

        const emptyState = document.createElement("div");

        createField();

        function createField() {
            const hasWrap = select.closest(".form-input-wrap");

            const wrap = hasWrap || document.createElement("div");

            if (!hasWrap) {
                wrap.className = "form-input-wrap";
                (select.parentNode as ParentNode).insertBefore(wrap, select.nextSibling);
            }

            select.hidden = true;

            labelInput.type = "text";

            labelInput.classList.add("form-select");
            labelInput.dataset.for = select.id;

            const selectLabel = $(`label[for="${select.id}"]`);

            if (selectLabel) {
                labelInput.setAttribute("aria-label", selectLabel.innerText);
            }

            if (select.hasAttribute("disabled")) {
                labelInput.disabled = true;
            }

            for (const key in select.dataset) {
                labelInput.dataset[key] = select.dataset[key];
            }

            const list: SelectInputListItem[] = [];

            $$("option", select).forEach((option: HTMLOptionElement) => {
                const dataset: Record<string, string> = {};

                for (const key in option.dataset) {
                    dataset[key] = option.dataset[key] as string;
                }

                list.push({
                    label: option.innerText,
                    value: option.value,
                    selected: option.selected,
                    disabled: option.disabled,
                    dataset,
                });

                if (option.selected) {
                    labelInput.value = option.innerText;
                }
            });

            wrap.appendChild(labelInput);

            wrap.appendChild(select);

            createDropdown(list, wrap as HTMLElement);
        }

        function createDropdown(list: SelectInputListItem[], wrap: HTMLElement) {
            dropdown = document.createElement("div");
            dropdown.className = "dropdown-list";

            dropdown.dataset.for = select.id;

            const container = document.createElement("div");
            container.className = "dropdown-list-items";
            dropdown.appendChild(container);

            emptyState.className = "dropdown-empty";
            emptyState.style.display = "none";
            emptyState.innerText = options.labels.empty;

            container.appendChild(emptyState);

            for (const option of list) {
                const item = document.createElement("div");
                item.className = "dropdown-item";

                item.innerText = option.label;
                item.dataset.value = option.value;

                if (option.selected) {
                    item.classList.add("selected");
                }

                if (option.disabled) {
                    item.classList.add("disabled");
                }

                for (const key in option.dataset) {
                    item.dataset[key] = option.dataset[key];
                }

                item.addEventListener("mousedown", (event) => {
                    if (!item.classList.contains("disabled")) {
                        selectDropdownItem(item);
                        setCurrent(item);
                    } else {
                        event.preventDefault();
                    }
                    event.stopPropagation();
                });

                container.appendChild(item);
            }

            wrap.appendChild(dropdown);

            let hasKeyboardInput = false;

            labelInput.addEventListener("focus", () => {
                selectCurrent();
                labelInput.setSelectionRange(0, 0);
                hasKeyboardInput = false;
            });

            labelInput.addEventListener("mousedown", (event) => {
                labelInput.focus();
                event.preventDefault();
            });

            labelInput.addEventListener("blur", () => {
                if (!validateDropdownItem(labelInput.value)) {
                    labelInput.value = getCurrentLabel();
                }
                dropdown.style.display = "none";
            });

            labelInput.addEventListener("keydown", (event) => {
                const selectedItem = $(".dropdown-item.selected", dropdown);

                switch (event.key) {
                    case "Backspace": // backspace
                        updateDropdown();
                        break;

                    case "ArrowUp": // up arrow
                        if (getComputedStyle(dropdown).display !== "none") {
                            selectPrevDropdownItem();
                        } else {
                            selectCurrent();
                        }
                        event.preventDefault();
                        break;

                    case "ArrowDown": // down arrow
                        if (getComputedStyle(dropdown).display !== "none") {
                            selectNextDropdownItem();
                        } else {
                            selectCurrent();
                        }
                        event.preventDefault();
                        break;

                    case "Enter":
                        if (selectedItem && getComputedStyle(selectedItem).display !== "none") {
                            setCurrent(selectedItem);
                        }

                        // dropdown.style.display = 'none';
                        labelInput.blur();
                        event.preventDefault();
                        break;

                    case "Escape":
                    case "ArrowLeft":
                    case "ArrowRight":
                        break;

                    default:
                        if (!hasKeyboardInput) {
                            labelInput.value = "";
                            hasKeyboardInput = true;
                        }
                        break;
                }
            });

            labelInput.addEventListener("keyup", (event) => {
                const value = labelInput.value.trim();
                switch (event.key) {
                    case "Escape":
                        labelInput.blur();
                        event.stopPropagation();
                        break;
                    case "ArrowUp":
                    case "ArrowDown":
                    case "ArrowLeft":
                    case "ArrowRight":
                    case "Tab":
                    case "Enter":
                        return true;
                    default:
                        dropdown.style.display = "block";
                        filterDropdown(value);
                        if (value.length > 0) {
                            selectFirstDropdownItem();
                        }
                }
            });
        }

        function updateDropdown() {
            let visibleItems = 0;
            $$(".dropdown-item", dropdown).forEach((element) => {
                if (getComputedStyle(element).display !== "none") {
                    visibleItems++;
                }
                element.classList.remove("selected");
            });

            if (visibleItems > 0) {
                emptyState.style.display = "none";
            } else {
                emptyState.style.display = "block";
            }
        }

        function filterDropdown(value: string) {
            const filter = (element: HTMLElement) => {
                if (value === "") {
                    return true;
                }
                const text = `${element.textContent}`;
                const regexp = new RegExp(`(^|\\b)${makeDiacriticsRegExp(escapeRegExp(value))}`, "i");
                return regexp.test(text);
            };

            let visibleItems = 0;
            $$(".dropdown-item", dropdown).forEach((element) => {
                if (value === null || filter(element)) {
                    element.style.display = "block";
                    visibleItems++;
                } else {
                    element.style.display = "none";
                }
            });

            if (visibleItems > 0) {
                emptyState.style.display = "none";
            } else {
                emptyState.style.display = "block";
            }
        }

        function scrollToDropdownItem(item: HTMLElement) {
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

        function selectDropdownItem(item: HTMLElement) {
            const selectedItem = $(".dropdown-item.selected", dropdown);
            if (selectedItem) {
                selectedItem.classList.remove("selected");
            }
            if (item) {
                const isDisabled = item.classList.contains("disabled");
                if (!isDisabled) {
                    item.classList.add("selected");
                    scrollToDropdownItem(item);
                }
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
            const selectedItem = $(".dropdown-item.selected", dropdown) as HTMLElement;
            if (selectedItem) {
                let previousItem = selectedItem.previousSibling as HTMLElement;
                while (previousItem && (previousItem.style.display === "none" || previousItem.classList.contains("disabled"))) {
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
            const selectedItem = $(".dropdown-item.selected", dropdown) as HTMLElement;
            if (selectedItem) {
                let nextItem = selectedItem.nextSibling as HTMLElement;
                while (nextItem && (nextItem.style.display === "none" || nextItem.classList.contains("disabled"))) {
                    nextItem = nextItem.nextSibling as HTMLElement;
                }
                if (nextItem) {
                    return selectDropdownItem(nextItem);
                }
            }
            selectFirstDropdownItem();
        }

        function setCurrent(item: HTMLElement) {
            select.value = item.dataset.value as string;
            labelInput.value = item.innerText;
            select.dispatchEvent(new Event("input", { bubbles: true }));
            select.dispatchEvent(new Event("change", { bubbles: true }));
        }

        function getCurrent() {
            return $(`[data-value="${select.value}"]`, dropdown) as HTMLElement;
        }

        function getCurrentLabel() {
            return getCurrent().innerText;
        }

        function selectCurrent() {
            if (getComputedStyle(dropdown).display === "none") {
                filterDropdown("");
                updateDropdown();
                selectDropdownItem(getCurrent());
                dropdown.style.display = "block";
                scrollToDropdownItem(getCurrent());
            }
        }

        function validateDropdownItem(value: string) {
            const items = $$(".dropdown-item", dropdown);
            for (let i = 0; i < items.length; i++) {
                if (items[i].innerText === value) {
                    return true;
                }
            }
            return false;
        }
    }
}
