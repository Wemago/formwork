import { $, $$ } from "../../utils/selectors";
import { escapeRegExp, makeDiacriticsRegExp } from "../../utils/validation";
import { app } from "../../app";
import { debounce } from "../../utils/events";
import { Form } from "../form";
import { Notification } from "../notification";
import { Request } from "../../utils/request";
import { SelectInput } from "../inputs/select-input";
import Sortable from "sortablejs";

export class Pages {
    constructor() {
        const commandExpandAllPages = $("[data-command=expand-all-pages]") as HTMLButtonElement;
        const commandCollapseAllPages = $("[data-command=collapse-all-pages]") as HTMLButtonElement;
        const commandReorderPages = $("[data-command=reorder-pages]") as HTMLButtonElement;
        const commandPreview = $("[data-command=preview]") as HTMLButtonElement;

        const searchInput = $(".page-search");

        const newPageModal = app.modals["newPageModal"];

        $$(".pages-tree").forEach((element) => {
            if (element.dataset.orderableChildren === "true") {
                initSortable(element);
            }
        });

        $$(".page-details").forEach((element) => {
            if ($(".pages-tree-children-toggle", element)) {
                element.addEventListener("click", (event) => {
                    togglePageItem(element);
                    event.stopPropagation();
                });
            }
        });

        $$(".page-details a").forEach((element) => {
            element.addEventListener("click", (event) => {
                event.stopPropagation();
            });
        });

        $$(".pages-tree .sortable-handle").forEach((element) => {
            element.addEventListener("click", (event) => {
                event.stopPropagation();
            });
        });

        if (commandExpandAllPages) {
            commandExpandAllPages.addEventListener("click", () => {
                expandAllPages();
                commandExpandAllPages.blur();
            });
        }

        if (commandCollapseAllPages) {
            commandCollapseAllPages.addEventListener("click", () => {
                collapseAllPages();
                commandCollapseAllPages.blur();
            });
        }

        if (commandReorderPages) {
            commandReorderPages.addEventListener("click", () => {
                commandReorderPages.classList.toggle("active");
                ($(".pages-tree") as HTMLElement).classList.toggle("is-reordering");
                commandReorderPages.blur();
            });
        }

        if (searchInput) {
            searchInput.addEventListener("focus", () => {
                $$(".pages-tree-item").forEach((element) => {
                    element.dataset.expanded = element.classList.contains("expanded") ? "true" : "false";
                });
            });

            const handleSearch = (event: Event) => {
                const value = (event.target as HTMLInputElement).value;
                if (value.length === 0) {
                    ($(".pages-tree-root") as HTMLElement).classList.remove("is-filtered");

                    $$(".pages-tree-item").forEach((element) => {
                        const title = $(".page-title a", element) as HTMLElement;
                        title.innerHTML = title.textContent as string;
                        ($(".pages-tree-row", element) as HTMLElement).style.display = "";
                        element.classList.toggle("is-expanded", element.dataset.expanded === "true");
                    });
                } else {
                    ($(".pages-tree-root") as HTMLElement).classList.add("is-filtered");

                    const regexp = new RegExp(`(^|\\b)${makeDiacriticsRegExp(escapeRegExp(value))}`, "gi");

                    $$(".pages-tree-item").forEach((element) => {
                        const title = $(".page-title a", element) as HTMLElement;
                        const text = title.textContent as string;
                        const pagesItem = $(".pages-tree-row", element) as HTMLElement;

                        if (text.match(regexp) !== null) {
                            title.innerHTML = text.replace(regexp, "<mark>$&</mark>");
                            pagesItem.style.display = "";
                        } else {
                            pagesItem.style.display = "none";
                        }

                        element.classList.add("is-expanded");
                    });
                }
            };

            searchInput.addEventListener("keyup", debounce(handleSearch, 100));
            searchInput.addEventListener("search", handleSearch);

            document.addEventListener("keydown", (event) => {
                if (event.ctrlKey || event.metaKey) {
                    if (event.key === "f" && document.activeElement !== searchInput) {
                        searchInput.focus();
                        event.preventDefault();
                    }
                }
            });
        }

        if (newPageModal) {
            const form = newPageModal.form as Form;

            const parentSelect = form.inputs["newPageModal[parent]"] as SelectInput;

            parentSelect.element.addEventListener("change", () => {
                const option = parentSelect.selectedDropdownItem;

                if (!option) {
                    return;
                }

                const allowedTemplates = option.dataset.allowedTemplates ? option.dataset.allowedTemplates.split(" ") : [];

                const templateSelect = form.inputs["newPageModal[template]"] as SelectInput;

                if (allowedTemplates.length > 0) {
                    templateSelect.element.dataset.previousValue = templateSelect.value;

                    templateSelect.value = allowedTemplates[0];

                    templateSelect.dropdownItems.forEach((item) => {
                        if (!allowedTemplates.includes(item.dataset.value as string)) {
                            item.classList.add("disabled");
                        }
                    });
                } else {
                    if ("previousValue" in templateSelect.element.dataset) {
                        templateSelect.value = templateSelect.element.dataset.previousValue as string;
                        delete templateSelect.element.dataset.previousValue;
                    }

                    templateSelect.dropdownItems.forEach((item) => {
                        item.classList.remove("disabled");
                    });
                }
            });
        }

        if (commandPreview) {
            const editorForm = app.forms["page-editor-form"];

            const pageParent = $("#parent", editorForm.element) as HTMLInputElement;
            const previousParent = pageParent.value;

            if (editorForm) {
                editorForm.element.addEventListener(
                    "input",
                    debounce(() => {
                        if (pageParent.value !== previousParent) {
                            // Prevent preview if the parent page has changed
                            commandPreview.disabled = true;
                            commandPreview.classList.remove("button-indicator");
                            return;
                        }
                        commandPreview.disabled = false;
                        commandPreview.classList.toggle("button-indicator", editorForm.hasChanged());
                    }, 500),
                );
            }
        }

        function expandAllPages() {
            $$(".pages-tree-item").forEach((element) => {
                element.classList.add("is-expanded");
            });
        }

        function collapseAllPages() {
            $$(".pages-tree-item").forEach((element) => {
                element.classList.remove("is-expanded");
            });
        }

        function togglePageItem(list: HTMLElement) {
            const element = list.closest(".pages-tree-item");
            element?.classList.toggle("is-expanded");
        }

        function initSortable(element: HTMLElement) {
            let originalOrder: string[] = [];

            const sortable = Sortable.create(element, {
                handle: ".sortable-handle",
                filter: ".is-not-orderable",
                forceFallback: true,
                swapThreshold: 0.75,
                invertSwap: true,
                animation: 150,
                preventOnFilter: false, // Workaround to allow touch events on iOS

                onChoose() {
                    const height = document.body.offsetHeight;
                    document.body.style.height = `${height}px`;

                    const e = () => {
                        window.document.body.style.height = "";
                        window.removeEventListener("scroll", e);
                    };
                    window.addEventListener("scroll", e);
                },

                onStart() {
                    element.classList.add("is-dragging");
                },

                onMove(event: Sortable.MoveEvent) {
                    if (event.related.classList.contains("is-not-orderable")) {
                        return false;
                    }
                },

                onEnd(event: Sortable.SortableEvent) {
                    element.classList.remove("is-dragging");

                    document.body.style.height = "";

                    if (event.newIndex === event.oldIndex) {
                        return;
                    }

                    sortable.option("disabled", true);

                    const data = {
                        "csrf-token": ($("meta[name=csrf-token]") as HTMLMetaElement).content,
                        page: event.item.dataset.route,
                        before: (event.item.nextElementSibling! as HTMLElement).dataset.route,
                        parent: element.dataset.parent,
                    };

                    new Request(
                        {
                            method: "POST",
                            url: `${app.config.baseUri}pages/reorder/`,
                            data: data,
                        },
                        (response) => {
                            if (response.status) {
                                const notification = new Notification(response.message, response.status, { icon: "check-circle" });
                                notification.show();
                            }
                            if (!response.status || response.status === "error") {
                                sortable.sort(originalOrder);
                            }
                            sortable.option("disabled", false);
                            originalOrder = sortable.toArray();
                        },
                    );
                },
            });

            originalOrder = sortable.toArray();
        }
    }
}
