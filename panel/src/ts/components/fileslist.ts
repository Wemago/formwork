import { $, $$ } from "../utils/selectors";
import { escapeRegExp, makeDiacriticsRegExp } from "../utils/validation";
import { app } from "../app";
import { debounce } from "../utils/events";
import { Form } from "./form";
import { Notification } from "./notification";
import { Request } from "../utils/request";
import { SelectInput } from "./inputs/select-input";
import { TagsInput } from "./inputs/tags-input";

export class FilesList {
    readonly element: HTMLElement;
    readonly form?: Form;

    constructor(element: HTMLElement, form?: Form) {
        this.element = element;
        this.form = form;

        this.initFileList();
        this.initModals();
    }

    sort(selector: string = ".file-name") {
        const filesItems = $$(".files-item", this.element);
        Array.from(filesItems)
            .sort((a: HTMLElement, b: HTMLElement) => {
                const keyA = $(selector, a)?.textContent;
                const keyB = $(selector, b)?.textContent;
                return keyA?.localeCompare(keyB ?? "") ?? 0;
            })
            .forEach((element: HTMLElement) => {
                element.parentElement?.appendChild(element);
            });
    }

    private initFileList() {
        const toggle = $(".form-togglegroup.files-list-view-as", this.element);
        const searchInput = $(".files-search", this.element);

        if (toggle) {
            const formName = this.element.closest("form")?.dataset.form;

            const fieldName = toggle.dataset.for;

            const key = formName ? `${formName}.${fieldName}` : fieldName;
            const viewAs = window.localStorage.getItem(`formwork.filesListViewAs[${key}]`);

            if (viewAs) {
                $$("input", toggle).forEach((input: HTMLInputElement) => (input.checked = false));
                ($(`input[value=${viewAs}]`, this.element) as HTMLInputElement).checked = true;
                this.element.classList.toggle("is-thumbnails", viewAs === "thumbnails");
            }

            $$("input", toggle).forEach((input: HTMLInputElement) => {
                input.addEventListener("input", () => {
                    this.element.classList.toggle("is-thumbnails", input.value === "thumbnails");
                    window.localStorage.setItem(`formwork.filesListViewAs[${key}]`, input.value);
                });
            });
        }

        document.addEventListener("click", (event) => {
            const target = event.target as HTMLElement;
            if (!target.closest(".dropdown") && target.closest(".files-item")) {
                const item = target.closest(".files-item") as HTMLElement;
                const list = item.closest(".files-list") as HTMLElement;
                const anchor = $(".file-name a", item) as HTMLAnchorElement;
                if (list.classList.contains("is-thumbnails") && anchor.href) {
                    location.href = anchor.href;
                }
            }
        });

        this.element.addEventListener("click", (event) => {
            const element = (event.target as HTMLElement).closest("[data-command=replaceFile]") as HTMLElement;
            if (element) {
                const fileInput = document.createElement("input");
                fileInput.type = "file";
                fileInput.accept = element.dataset.mimetype as string;
                fileInput.click();

                fileInput.addEventListener("change", () => {
                    if (fileInput.files?.length) {
                        const formData = new FormData();
                        formData.append("csrf-token", app.config.csrfToken as string);
                        formData.append("file", fileInput.files[0]);

                        new Request(
                            {
                                method: "POST",
                                url: element.dataset.action as string,
                                data: formData,
                            },
                            (response) => {
                                const notification = new Notification(response.message, response.status);

                                if (response.status === "success") {
                                    if (element.closest("[data-form=file-form]")) {
                                        window.location.reload();
                                    } else if (response.data.thumbnail) {
                                        const thumbnail = $(".file-thumbnail", element.closest(".files-item") as HTMLElement) as HTMLImageElement | HTMLVideoElement;
                                        thumbnail.src = response.data.thumbnail;

                                        const fileDate = $(".file-date", element.closest(".files-item") as HTMLElement) as HTMLElement;
                                        fileDate.textContent = response.data.lastModifiedTime;

                                        const fileSize = $(".file-size", element.closest(".files-item") as HTMLElement) as HTMLElement;
                                        fileSize.textContent = response.data.size;
                                    }
                                }

                                notification.show();
                            },
                        );
                    }

                    fileInput.remove();
                });
            }
        });

        if (searchInput) {
            const handleSearch = (event: Event) => {
                const value = (event.target as HTMLInputElement).value;
                ($(".files-item") as HTMLElement).classList.toggle("is-filtered", value.length > 0);

                $$(".files-item").forEach((element) => {
                    let matches = 0;

                    for (const selector of [".file-name", ".file-parent-title"]) {
                        const item = $(selector, element) as HTMLElement;

                        if (!item) {
                            continue;
                        }

                        const text = item.textContent as string;

                        const regexp = value ? new RegExp(`${makeDiacriticsRegExp(escapeRegExp(value))}`, "gi") : null;

                        if (regexp && text.match(regexp) !== null) {
                            item.innerHTML = text.replace(regexp, "<mark>$&</mark>");
                            matches++;
                        } else {
                            item.innerHTML = text;
                        }
                    }

                    if (!value || matches > 0) {
                        element.style.display = "";
                    } else {
                        element.style.display = "none";
                    }
                });
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
    }

    private initModals() {
        const renameFileItemModal = app.modals["renameFileItemModal"];

        if (renameFileItemModal) {
            $('[id="renameFileItemModal.filename"]', renameFileItemModal.element)?.addEventListener("keydown", (event) => {
                if (event.key === "Enter") {
                    renameFileItemModal.triggerCommand("rename-file");
                    event.preventDefault();
                }
            });

            renameFileItemModal.onOpen((modal, trigger) => {
                if (trigger) {
                    const input = $('[id="renameFileItemModal.filename"]', modal.element) as HTMLInputElement;
                    input.value = (trigger.closest("[data-filename]") as HTMLElement)?.dataset.filename as string;
                    input.setSelectionRange(0, input.value.lastIndexOf("."));

                    Object.assign(modal.data, {
                        action: trigger.dataset.action,
                        item: trigger.closest(".files-item"),
                        input,
                    });
                }
            });

            renameFileItemModal.onCommand("rename-file", (modal) => {
                const { action, item, filename, input } = modal.data;

                new Request(
                    {
                        method: "POST",
                        url: action as string,
                        data: {
                            filename,
                            "renameFileItemModal[filename]": (input as HTMLInputElement).value,
                            "csrf-token": app.config.csrfToken as string,
                        },
                    },
                    (response) => {
                        if (response.status === "success") {
                            (item as HTMLElement).dataset.filename = response.data.filename;

                            const anchor = $(".file-name a", item as HTMLElement) as HTMLAnchorElement;
                            anchor.innerHTML = response.data.filename;
                            anchor.href = response.data.uri;

                            ($("[data-command=infoFile]", item as HTMLElement) as HTMLAnchorElement).href = response.data.actions.info;
                            ($("[data-command=previewFile]", item as HTMLElement) as HTMLAnchorElement).href = response.data.uri;
                            ($("[data-command=renameFile]", item as HTMLElement) as HTMLElement).dataset.action = response.data.actions.rename;
                            ($("[data-command=replaceFile]", item as HTMLElement) as HTMLElement).dataset.action = response.data.actions.replace;
                            ($("[data-command=deleteFile]", item as HTMLElement) as HTMLElement).dataset.action = response.data.actions.delete;

                            if (response.data.thumbnail) {
                                const thumbnail = $(".file-thumbnail", item as HTMLElement) as HTMLImageElement | HTMLVideoElement;
                                thumbnail.src = response.data.thumbnail;
                            }

                            this.sort(".file-name");
                        }

                        const notification = new Notification(response.message, response.status);
                        notification.show();

                        modal.close();
                    },
                );

                modal.close();
            });
        }

        const deleteFileItemModal = app.modals["deleteFileItemModal"];

        if (deleteFileItemModal) {
            deleteFileItemModal.onOpen((modal, trigger) => {
                if (trigger) {
                    Object.assign(modal.data, {
                        action: trigger.dataset.action,
                        item: trigger.closest(".files-item"),
                    });
                }
            });

            deleteFileItemModal.onCommand("delete-file", (modal) => {
                const { action, item, filename } = modal.data;

                new Request(
                    {
                        method: "POST",
                        url: action as string,
                        data: {
                            filename,
                            "csrf-token": app.config.csrfToken as string,
                        },
                    },
                    (response) => {
                        if (response.status === "success") {
                            (item as HTMLElement).remove();

                            if (this.element.querySelectorAll(".files-item").length === 0) {
                                this.element.hidden = true;
                            }

                            if (this.form) {
                                for (const name in this.form.inputs) {
                                    const input = this.form.inputs[name];
                                    if (input instanceof SelectInput && !input.element.classList.contains("form-file") && !input.element.classList.contains("form-image")) {
                                        input.removeOption(filename as string);
                                    }

                                    if (input instanceof TagsInput && (input.element.classList.contains("form-files") || input.element.classList.contains("form-images"))) {
                                        input.removeDropdownItem(filename as string);
                                    }
                                }
                            }
                        }

                        const notification = new Notification(response.message, response.status);
                        notification.show();

                        modal.close();
                    },
                );

                modal.close();
            });
        }
    }
}
