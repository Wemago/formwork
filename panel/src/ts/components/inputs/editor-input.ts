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

export class EditorInput {
    readonly element: HTMLTextAreaElement;

    readonly name: string;

    private editor: MarkdownView | CodeView;

    constructor(textarea: HTMLTextAreaElement) {
        this.element = textarea;
        this.name = textarea.name;

        const editorWrap = (textarea.parentNode as HTMLElement).classList.contains("editor-wrap") ? (textarea.parentNode as HTMLElement) : null;

        if (editorWrap) {
            const textareaHeight = textarea.offsetHeight;
            const baseUri = textarea.dataset.baseUri ?? "";

            const attributes = {
                spellcheck: textarea.spellcheck ? "true" : "false",
            };

            textarea.style.display = "none";

            const inputEventHandler = debounce(
                (content: string) => {
                    textarea.value = removeBaseUri(content, baseUri);
                    textarea.dispatchEvent(new Event("input", { bubbles: true }));
                    textarea.dispatchEvent(new Event("change", { bubbles: true }));
                },
                500,
                true,
            );

            this.editor = new MarkdownView(editorWrap, addBaseUri(textarea.value, baseUri), inputEventHandler, attributes, baseUri);
            this.editor.view.dom.style.height = `${textareaHeight}px`;

            $(`label[for="${textarea.id}"]`)?.addEventListener("click", () => this.editor.view.focus());

            const codeSwitch = $("[data-command=toggle-markdown]", editorWrap) as HTMLButtonElement;
            codeSwitch.addEventListener("click", () => {
                codeSwitch.classList.toggle("is-active");
                if (codeSwitch.classList.contains("is-active")) {
                    this.editor.destroy();
                    this.editor = new CodeView(editorWrap, removeBaseUri(this.editor.content, baseUri), inputEventHandler);
                    this.editor.view.dom.style.height = `${textareaHeight}px`;
                } else {
                    this.editor.destroy();
                    this.editor = new MarkdownView(editorWrap, addBaseUri(this.editor.content, baseUri), inputEventHandler, attributes, baseUri);
                    this.editor.view.dom.style.height = `${textareaHeight}px`;
                }
                this.editor.view.focus();
            });
        }
    }

    get value(): string {
        return this.editor.content;
    }
}
