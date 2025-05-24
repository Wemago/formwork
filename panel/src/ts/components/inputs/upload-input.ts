import { $, $$ } from "../../utils/selectors";
import { app } from "../../app";
import { Form } from "../form";
import { insertIcon } from "../icons";
import { Notification } from "../notification";
import { Request } from "../../utils/request";
import { SelectInput } from "./select-input";
import { TagsInput } from "./tags-input";

export class UploadInput {
    readonly element: HTMLInputElement;
    readonly name: string;

    private readonly form: Form;
    private isSubmitted: boolean = false;

    private readonly label: HTMLElement;
    private readonly dropTarget: HTMLElement;
    private readonly dropTargetLabel: HTMLElement;
    private readonly defaultDropLabel: string;

    private readonly filesList: HTMLElement;

    constructor(element: HTMLInputElement, form: Form) {
        this.element = element;
        this.name = this.element.name;

        this.form = form;

        this.label = $(`label[for="${this.element.id}"]`) as HTMLElement;
        this.dropTarget = this.element.closest(".form-upload-drop-target") as HTMLElement;
        this.dropTargetLabel = $("span", this.dropTarget) as HTMLElement;
        this.defaultDropLabel = this.dropTargetLabel.innerHTML ?? "";

        this.initInput();

        this.filesList = $(`.files-list[data-for="${this.element.id}"]`) as HTMLElement;

        if (this.filesList) {
            this.initFileList();
            this.initModals();
        } else if (this.element.dataset.autoUpload === "true") {
            this.element.addEventListener("change", () => {
                if (!this.form.hasChanged(false)) {
                    this.form.element.requestSubmit($("[type=submit]", this.form.element));
                }
            });
        }
    }

    get value(): string {
        return this.element.value;
    }

    private initInput() {
        this.label?.addEventListener("click", (event) => {
            this.dropTarget.focus();
            event.preventDefault();
        });

        this.element.addEventListener("change", () => this.updateDropTargetLabel());
        this.element.addEventListener("input", () => this.updateDropTargetLabel());

        this.element.form?.addEventListener("submit", () => {
            this.isSubmitted = true;
            this.updateDropTargetLabel();
        });

        this.dropTarget.addEventListener("drag", (event) => event.preventDefault());
        this.dropTarget.addEventListener("dragstart", (event) => event.preventDefault());
        this.dropTarget.addEventListener("dragend", (event) => event.preventDefault());
        this.dropTarget.addEventListener("dragover", (event) => {
            this.dropTarget.classList.add("drag");
            event.preventDefault();
        });
        this.dropTarget.addEventListener("dragenter", (event) => {
            this.dropTarget.classList.add("drag");
            event.preventDefault();
        });
        this.dropTarget.addEventListener("dragleave", (event) => {
            this.dropTarget.classList.remove("drag");
            event.preventDefault();
        });

        this.dropTarget.addEventListener("drop", (event) => {
            event.preventDefault();
            if (this.isSubmitted) {
                return;
            }
            if (event.dataTransfer) {
                this.element.files = event.dataTransfer.files;
                // Firefox won't trigger a change event, so we explicitly do that
                this.element.dispatchEvent(new Event("change"));
            }
        });

        this.dropTarget.addEventListener("click", (event) => {
            if (this.isSubmitted) {
                event.preventDefault();
            }
        });

        this.dropTarget.addEventListener("keydown", (event) => {
            if (event.key === "Enter") {
                this.element.click();
            }
        });
    }

    private initFileList() {
        const toggle = $(".form-togglegroup.files-list-view-as", this.filesList);

        if (toggle) {
            const fieldName = toggle.dataset.for;
            const viewAs = window.localStorage.getItem(`formwork.filesListViewAs[${fieldName}]`);

            if (viewAs) {
                $$("input", toggle).forEach((input: HTMLInputElement) => (input.checked = false));
                ($(`input[value=${viewAs}]`, this.filesList) as HTMLInputElement).checked = true;
                this.filesList.classList.toggle("is-thumbnails", viewAs === "thumbnails");
            }

            $$("input", toggle).forEach((input: HTMLInputElement) => {
                input.addEventListener("input", () => {
                    this.filesList.classList.toggle("is-thumbnails", input.value === "thumbnails");
                    window.localStorage.setItem(`formwork.filesListViewAs[${fieldName}]`, input.value);
                });
            });
        }

        document.addEventListener("click", (event) => {
            const target = event.target as HTMLElement;
            if (!target.closest(".dropdown") && target.closest(".files-item")) {
                const item = target.closest(".files-item") as HTMLElement;
                if (typeof item.dataset.href === "string") {
                    location.href = item.dataset.href;
                }
            }
        });

        if (this.element.dataset.autoUpload === "true") {
            this.element.addEventListener("change", () => {
                if (this.element.files?.length) {
                    for (const file of Array.from(this.element.files)) {
                        const formData = new FormData();
                        formData.append("csrf-token", app.config.csrfToken as string);
                        formData.append(this.element.name, file);

                        this.dropTargetLabel.innerHTML += ' <span class="spinner"></span>';

                        new Request(
                            {
                                method: "POST",
                                data: formData,
                            },
                            (response) => {
                                const notification = new Notification(response.message, response.status);

                                if (response.status === "success") {
                                    const data = response.data[0];
                                    const template = $("template[id=files-item]") as HTMLTemplateElement;
                                    this.addFilesItem(data, template);
                                    this.sortFilesList(this.filesList, ".file-name");

                                    for (const name in this.form.inputs) {
                                        const input = this.form.inputs[name];
                                        if (input instanceof SelectInput && (input.element.classList.contains("form-file") || (input.element.classList.contains("form-image") && data.type === "image"))) {
                                            input.addOption({
                                                label: data.name,
                                                value: data.name,
                                                thumb: data.thumbnail,
                                                icon: `file-${data.type}`,
                                            });
                                            input.sortDropdownItems();
                                        }

                                        if (input instanceof TagsInput && (input.element.classList.contains("form-files") || (input.element.classList.contains("form-images") && data.type === "image"))) {
                                            input.addDropdownItem({
                                                label: data.name,
                                                value: data.name,
                                                thumb: data.thumbnail,
                                                icon: `file-${data.type}`,
                                            });
                                            input.sortDropdownItems();
                                        }
                                    }
                                }

                                this.element.value = "";
                                this.updateDropTargetLabel();

                                notification.show();
                            },
                        );
                    }
                }
            });
        }

        this.filesList.addEventListener("click", (event) => {
            const element = (event.target as HTMLElement).closest("[data-command=replaceFile]") as HTMLElement;
            if (element) {
                const fileInput = document.createElement("input");
                fileInput.type = "file";
                fileInput.accept = element.dataset.mimetype as string;
                fileInput.click();

                fileInput.addEventListener("change", () => {
                    if (fileInput.files?.length) {
                        const formData = new FormData();
                        formData.append("filename", (element.closest("[data-filename]") as HTMLElement).dataset.filename as string);
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
                                    if (element.closest("[data-form=page-file-form]")) {
                                        window.location.reload();
                                    } else if (response.data.thumbnail) {
                                        const thumbnail = $(".file-thumbnail", element.closest(".files-item") as HTMLElement) as HTMLImageElement;
                                        const fileSize = $(".file-size", element.closest(".files-item") as HTMLElement) as HTMLImageElement;

                                        if (response.data.type === "image") {
                                            thumbnail.style.backgroundImage = `url(${response.data.thumbnail})`;
                                        } else if (response.data.type === "video") {
                                            thumbnail.src = response.data.thumbnail;
                                        }

                                        fileSize.textContent = `(${response.data.size})`;
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
                        filename: (trigger.closest("[data-filename]") as HTMLElement)?.dataset.filename,
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

                            ($(".file-name", item as HTMLElement) as HTMLElement).innerHTML = response.data.filename;

                            const template = $("template[id=files-item]") as HTMLTemplateElement;
                            const uri = ($(".files-item", template.content) as HTMLElement).dataset.href as string;
                            (item as HTMLElement).dataset.href = `${uri}${response.data.filename}`;

                            this.sortFilesList(this.filesList, ".file-name");
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
                        filename: (trigger.closest("[data-filename]") as HTMLElement)?.dataset.filename,
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

                        const notification = new Notification(response.message, response.status);
                        notification.show();

                        modal.close();
                    },
                );

                modal.close();
            });
        }
    }

    private formatFileSize(size: number) {
        const units = ["B", "KB", "MB", "GB", "TB"];
        const exp = Math.min(Math.floor(Math.log(size) / Math.log(1024)), units.length - 1);
        return `${(size / 1024 ** exp).toFixed(2)} ${units[exp]}`;
    }

    private updateDropTargetLabel() {
        if (this.element.files && this.element.files.length > 0) {
            const filenames: string[] = [];
            for (const file of Array.from(this.element.files)) {
                filenames.push(`${file.name} <span class="file-size">(${this.formatFileSize(file.size)})</span>`);
            }
            this.dropTargetLabel.innerHTML = filenames.join(", ");

            if (this.isSubmitted && !$(".spinner", this.dropTargetLabel)) {
                const spinner = document.createElement("span");
                spinner.classList.add("spinner", "ml-3");
                this.dropTargetLabel.appendChild(spinner);
            }
        } else {
            this.dropTargetLabel.innerHTML = this.defaultDropLabel;
        }
    }

    private sortFilesList(filesList: HTMLElement, selector: string = ".file-name") {
        const filesItems = $$(".files-item", filesList);
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

    private addFilesItem(info: { [key: string]: string }, template: HTMLTemplateElement) {
        const node = template.content.cloneNode(true) as HTMLElement;
        const filesItem = $(".files-item", node) as HTMLElement;

        filesItem.dataset.filename = info.name;
        filesItem.dataset.href += `${info.name}/`;

        if (info.type === "image") {
            ($(".file-thumbnail", filesItem) as HTMLElement).style.backgroundImage = `url(${info.thumbnail})`;
        } else if (info.type === "video") {
            const video = document.createElement("video");
            video.classList.add("file-thumbnail");
            video.src = info.thumbnail;
            video.preload = "metadata";
            $(".file-thumbnail", filesItem)?.replaceWith(video);
        } else {
            $(".file-thumbnail", filesItem)?.remove();
        }

        insertIcon(info.type ? `file-${info.type}` : "file", $(".file-icon", filesItem) as HTMLElement);

        ($(".file-name", filesItem) as HTMLElement).textContent = info.name;
        ($(".file-size", filesItem) as HTMLElement).textContent = `(${info.size})`;

        ($(".dropdown-button", filesItem) as HTMLElement).dataset.dropdown = `dropdown-${info.hash}`;
        ($(".dropdown-menu", filesItem) as HTMLElement).id = `dropdown-${info.hash}`;

        ($("[data-command=infoFile", filesItem) as HTMLAnchorElement).href += `${info.name}/`;

        ($("[data-command=previewFile"), filesItem as HTMLAnchorElement).href = info.uri;
        ($("[data-command=previewFile"), filesItem as HTMLAnchorElement).target = `formwork-preview-file-${info.hash}`;

        ($("[data-command=replaceFile", filesItem) as HTMLElement).dataset.extension = `${info.mimeType}`;

        $(".files-items", this.filesList)?.appendChild(node);
    }
}
