import { Command, NodeSelection, Plugin } from "prosemirror-state";
import { insertImage, insertLink, isMarkActive, lift, redo, setBlockType, sinkListItem, toggleMark, undo, wrapIn, wrapInList } from "./commands";
import { MarkType, NodeType } from "prosemirror-model";
import { $$ } from "../../../../utils/selectors";
import { app } from "../../../../app";
import { EditorView } from "prosemirror-view";
import { passIcon } from "../../../icons";
import { schema } from "prosemirror-markdown";

interface MenuItem {
    command: Command;
    dom: HTMLElement;
    mark?: MarkType;
    node?: NodeType;
    dropdown?: string;
    name?: string;
    group?: string;
}

class MenuView {
    items: MenuItem[];
    editorView: EditorView;
    dom: HTMLElement;
    dropdowns: { [name: string]: HTMLElement };

    constructor(items: MenuItem[], editorView: EditorView) {
        this.items = items;
        this.editorView = editorView;

        this.dom = document.createElement("div");

        this.dropdowns = {};

        let currentGroup: string | undefined;

        items.forEach(({ dom, dropdown, group }, index) => {
            let target = this.dom;

            if (index > 0 && group !== currentGroup) {
                const separator = document.createElement("div");
                separator.className = "separator";
                target.appendChild(separator);
            }

            currentGroup = group;

            if (dropdown) {
                if (!(dropdown in this.dropdowns)) {
                    const el = createDropdown();
                    this.dropdowns[dropdown] = el;
                    target.appendChild(el);
                }
                target = this.dropdowns[dropdown].querySelector(".dropdown-menu")!;
            }

            target.appendChild(dom);
        });

        this.update();

        this.dom.addEventListener("mousedown", (e) => {
            e.preventDefault();
            editorView.focus();
            items.forEach(({ command, dom }) => {
                if (dom.contains(e.target as HTMLElement)) {
                    command(editorView.state, editorView.dispatch, editorView);
                }
            });
        });
    }

    update() {
        this.items.forEach(({ command, dom, mark, dropdown, name, node }) => {
            const state = this.editorView.state;

            const applicable = command(state, undefined, this.editorView);

            if (dom instanceof HTMLButtonElement) {
                dom.disabled = !applicable;
                dom.classList.remove("is-active");
            }

            if (dropdown && name) {
                if (!applicable) {
                    this.dropdowns[dropdown].querySelector(".dropdown-button")!.textContent = name;
                }
                dom.classList.toggle("is-active", !applicable);
            }

            if (applicable && node) {
                dom.classList.toggle("is-active", state.selection instanceof NodeSelection && state.selection.node.type === node);
            }

            if (applicable && mark) {
                dom.classList.toggle("is-active", isMarkActive(state, mark));
            }
        });
    }

    destroy() {
        this.dom.remove();
    }
}

function plugin(items: MenuItem[]) {
    return new Plugin({
        view(editorView: EditorView) {
            const menuView = new MenuView(items, editorView);

            const toolbar = editorView.dom.parentNode!.querySelector(".editor-toolbar");
            toolbar!.prepend(menuView.dom);

            return menuView;
        },
    });
}

export function menuPlugin() {
    let modalsInitialized = false;

    if (!modalsInitialized) {
        initModals();
        modalsInitialized = true;
    }

    return plugin([
        {
            name: app.config.EditorInput.labels.paragraph,
            command: setBlockType(schema.nodes.paragraph),
            dom: createMenuItem(app.config.EditorInput.labels.paragraph),
            dropdown: "editor-level",
            group: "style",
        },
        {
            name: app.config.EditorInput.labels.code,
            node: schema.nodes.code_block,
            command: setBlockType(schema.nodes.code_block),
            dom: createMenuItem(`<span class="text-monospace">${app.config.EditorInput.labels.code}</span>`),
            dropdown: "editor-level",
            group: "style",
        },
        {
            name: app.config.EditorInput.labels.heading1,
            command: setBlockType(schema.nodes.heading, { level: 1 }),
            dom: createMenuItem(`<span class="h1">${app.config.EditorInput.labels.heading1}</span>`),
            dropdown: "editor-level",
            group: "style",
        },
        {
            name: app.config.EditorInput.labels.heading2,
            command: setBlockType(schema.nodes.heading, { level: 2 }),
            dom: createMenuItem(`<span class="h2">${app.config.EditorInput.labels.heading2}</span>`),
            dropdown: "editor-level",
            group: "style",
        },
        {
            name: app.config.EditorInput.labels.heading3,
            command: setBlockType(schema.nodes.heading, { level: 3 }),
            dom: createMenuItem(`<span class="h3">${app.config.EditorInput.labels.heading3}</span>`),
            dropdown: "editor-level",
            group: "style",
        },
        {
            name: app.config.EditorInput.labels.heading4,
            command: setBlockType(schema.nodes.heading, { level: 4 }),
            dom: createMenuItem(`<span class="h4">${app.config.EditorInput.labels.heading4}</span>`),
            dropdown: "editor-level",
            group: "style",
        },
        {
            name: app.config.EditorInput.labels.heading5,
            command: setBlockType(schema.nodes.heading, { level: 5 }),
            dom: createMenuItem(`<span class="h5">${app.config.EditorInput.labels.heading5}</span>`),
            dropdown: "editor-level",
            group: "style",
        },
        {
            name: app.config.EditorInput.labels.heading6,
            command: setBlockType(schema.nodes.heading, { level: 6 }),
            dom: createMenuItem(`<span class="h6">${app.config.EditorInput.labels.heading6}</span>`),
            dropdown: "editor-level",
            group: "style",
        },
        {
            mark: schema.marks.strong,
            command: toggleMark(schema.marks.strong),
            dom: createButton("bold", app.config.EditorInput.labels.bold),
            group: "style",
        },
        {
            mark: schema.marks.em,
            command: toggleMark(schema.marks.em),
            dom: createButton("italic", app.config.EditorInput.labels.italic),
            group: "style",
        },
        {
            mark: schema.marks.code,
            command: toggleMark(schema.marks.code),
            dom: createButton("code", app.config.EditorInput.labels.code),
            group: "style",
        },
        {
            command: wrapInList(schema.nodes.bullet_list, schema.nodes.list_item),
            dom: createButton("list-unordered", app.config.EditorInput.labels.bulletList),
            group: "blocks",
        },
        {
            command: wrapInList(schema.nodes.ordered_list, schema.nodes.list_item),
            dom: createButton("list-ordered", app.config.EditorInput.labels.numberedList),
            group: "blocks",
        },
        {
            node: schema.nodes.blockquote,
            command: wrapIn(schema.nodes.blockquote),
            dom: createButton("blockquote", app.config.EditorInput.labels.quote),
            group: "blocks",
        },
        {
            command: sinkListItem(schema.nodes.list_item),
            dom: createButton("indent-increase", app.config.EditorInput.labels.increaseIndent),
            group: "blocks",
        },
        {
            command: lift,
            dom: createButton("indent-decrease", app.config.EditorInput.labels.decreaseIndent),
            group: "blocks",
        },
        {
            node: schema.nodes.image,
            command: insertImage,
            dom: createButton("image", app.config.EditorInput.labels.image),
            group: "media",
        },
        {
            mark: schema.marks.link,
            command: insertLink,
            dom: createButton("link", app.config.EditorInput.labels.link),
            group: "media",
        },
        {
            command: undo,
            dom: createButton("rotate-left", app.config.EditorInput.labels.undo),
        },
        {
            command: redo,
            dom: createButton("rotate-right", app.config.EditorInput.labels.redo),
        },
    ]);
}

function createButton(icon: string, title: string) {
    const btn = document.createElement("button");
    btn.type = "button";
    btn.className = `button toolbar-button`;
    btn.title = title;
    btn.ariaLabel = title;
    passIcon(icon, (data) => (btn.innerHTML = data));
    return btn;
}

function createMenuItem(text: string) {
    const item = document.createElement("a");
    item.className = "dropdown-item";
    item.innerHTML = text;
    return item;
}

function createDropdown() {
    const dropdown = document.createElement("div");
    dropdown.className = "dropdown";

    const btn = document.createElement("button");
    btn.type = "button";
    btn.classList.add("button", "toolbar-button", "dropdown-button", "caret");
    btn.dataset.dropdown = "dropdown-editor";

    dropdown.appendChild(btn);

    const menu = document.createElement("div");
    menu.className = "dropdown-menu";
    menu.id = "dropdown-editor";

    dropdown.appendChild(menu);

    return dropdown;
}

function initModals() {
    const { linkModal } = app.modals;

    $$('[id="linkModal.text"], [id="linkModal.uri"]', linkModal.element).forEach((input) =>
        input.addEventListener("keydown", (event) => {
            if (event.key === "Enter") {
                linkModal.triggerCommand("insert-link");
                event.preventDefault();
            }
        }),
    );
}
