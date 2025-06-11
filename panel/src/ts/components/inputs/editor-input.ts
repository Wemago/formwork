import { $ } from "../../utils/selectors";
import { CodeView } from "./editor/code/view";
import { debounce } from "../../utils/events";
import { escapeRegExp } from "../../utils/validation";
import { MarkdownView } from "./editor/markdown/view";

function addBaseUri(markdown: string, baseUri: string) {
    return markdown.replace(/(!\[.*\])\((?!https?:\/\/)([^)]+)\)/g, `$1(${baseUri}$2)`);
}

function removeBaseUri(markdown: string, baseUri: string) {
    return markdown.replace(new RegExp(`(!\\[.*\\])\\(${escapeRegExp(baseUri)}([^)]+)\\)`, "g"), "$1($2)");
}

interface EditorInputOptions {
    baseUri: string;
    height: number;
    spellcheck: boolean;
    inputEventHandler: (value: string) => void;
}

export class EditorInput {
    readonly element: HTMLTextAreaElement;

    readonly name: string;

    readonly options: EditorInputOptions;

    private container: HTMLElement | null;

    private editor: MarkdownView | CodeView;

    constructor(textarea: HTMLTextAreaElement, options: Partial<EditorInputOptions> = {}) {
        this.element = textarea;
        this.name = textarea.name;

        const defaults: EditorInputOptions = {
            baseUri: textarea.dataset.baseUri ?? "",
            height: textarea.offsetHeight ?? 200,
            spellcheck: true,
            inputEventHandler: (value: string) => {
                this.element.value = removeBaseUri(value, this.options.baseUri);
                debounce(
                    () => {
                        this.element.dispatchEvent(new Event("input", { bubbles: true }));
                        this.element.dispatchEvent(new Event("change", { bubbles: true }));
                    },
                    500,
                    true,
                )();
            },
        };

        this.options = { ...defaults, ...options };

        this.container = (textarea.parentNode as HTMLElement).classList.contains("editor-wrap") ? (textarea.parentNode as HTMLElement) : null;

        if (this.container) {
            textarea.style.display = "none";

            const mode = window.localStorage.getItem(`formwork.editorMode[${this.name}]`);

            const codeSwitch = $("[data-command=toggle-markdown]", this.container) as HTMLButtonElement;

            if (mode === "code") {
                this.switchToCode();
                codeSwitch.classList.add("is-active");
            } else {
                this.switchToMarkdown();
                codeSwitch.classList.remove("is-active");
            }

            codeSwitch.addEventListener("click", () => {
                if (codeSwitch.classList.toggle("is-active")) {
                    this.switchToCode();
                    window.localStorage.setItem(`formwork.editorMode[${this.name}]`, "code");
                } else {
                    this.switchToMarkdown();
                    window.localStorage.setItem(`formwork.editorMode[${this.name}]`, "mardown");
                }
                this.editor.view.focus();
            });

            $(`label[for="${textarea.id}"]`)?.addEventListener("click", () => this.editor.view.focus());
        }
    }

    switchToMarkdown() {
        if (!this.container) {
            return;
        }
        this.editor?.destroy();
        this.editor = new MarkdownView(this.container, addBaseUri(this.element.value, this.options.baseUri), this.options.inputEventHandler, {
            spellcheck: this.options.spellcheck ? "true" : "false",
        });
        this.editor.view.dom.style.height = `${this.options.height}px`;
    }

    switchToCode() {
        if (!this.container) {
            return;
        }
        this.editor?.destroy();
        this.editor = new CodeView(this.container, removeBaseUri(this.element.value, this.options.baseUri), this.options.inputEventHandler);
        this.editor.view.dom.style.height = `${this.options.height}px`;
    }

    get value(): string {
        return this.editor.content;
    }
}
