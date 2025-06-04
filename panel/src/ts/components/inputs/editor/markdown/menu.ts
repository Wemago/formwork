import { Command, EditorState, NodeSelection, Plugin } from "prosemirror-state";
import { lift, setBlockType, toggleMark, wrapIn } from "prosemirror-commands";
import { MarkType, NodeType } from "prosemirror-model";
import { redo, redoDepth, undo, undoDepth } from "prosemirror-history";
import { sinkListItem, wrapInList } from "prosemirror-schema-list";
import { $ } from "../../../../utils/selectors";
import { app } from "../../../../app";
import { EditorView } from "prosemirror-view";
import { getMarkRange } from "./utils";
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
    initModals();

    return plugin([
        {
            name: app.config.EditorInput.labels.paragraph,
            command: setBlockType(schema.nodes.paragraph),
            dom: createMenuItem(app.config.EditorInput.labels.paragraph),
            dropdown: "editor-level",
            group: "style",
        },
        {
            name: app.config.EditorInput.labels.heading1,
            command: setBlockType(schema.nodes.heading, { level: 1 }),
            dom: createMenuItem(app.config.EditorInput.labels.heading1),
            dropdown: "editor-level",
            group: "style",
        },
        {
            name: app.config.EditorInput.labels.heading2,
            command: setBlockType(schema.nodes.heading, { level: 2 }),
            dom: createMenuItem(app.config.EditorInput.labels.heading2),
            dropdown: "editor-level",
            group: "style",
        },
        {
            name: app.config.EditorInput.labels.heading3,
            command: setBlockType(schema.nodes.heading, { level: 3 }),
            dom: createMenuItem(app.config.EditorInput.labels.heading3),
            dropdown: "editor-level",
            group: "style",
        },
        {
            name: app.config.EditorInput.labels.heading4,
            command: setBlockType(schema.nodes.heading, { level: 4 }),
            dom: createMenuItem(app.config.EditorInput.labels.heading4),
            dropdown: "editor-level",
            group: "style",
        },
        {
            name: app.config.EditorInput.labels.heading5,
            command: setBlockType(schema.nodes.heading, { level: 5 }),
            dom: createMenuItem(app.config.EditorInput.labels.heading5),
            dropdown: "editor-level",
            group: "style",
        },
        {
            name: app.config.EditorInput.labels.heading6,
            command: setBlockType(schema.nodes.heading, { level: 6 }),
            dom: createMenuItem(app.config.EditorInput.labels.heading6),
            dropdown: "editor-level",
            group: "style",
        },
        {
            name: app.config.EditorInput.labels.code,
            node: schema.nodes.code_block,
            command: setBlockType(schema.nodes.code_block),
            dom: createMenuItem(app.config.EditorInput.labels.code),
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
            command: (state, dispatch, view) => {
                if (!dispatch) {
                    return canInsert(state, schema.nodes.image);
                }
                if (view) {
                    app.modals["imagesModal"].open();

                    app.modals["imagesModal"].onCommand("pick-image", (modal) => {
                        const selected = $(".image-picker-thumbnail.selected", modal.element);

                        if (selected && view) {
                            const baseUri = $("textarea", view.dom.parentNode!)!.dataset.baseUri;
                            const filename = selected.dataset.filename;
                            view.dispatch(
                                state.tr.replaceSelectionWith(
                                    schema.nodes.image.createAndFill({
                                        src: `${baseUri}${filename}`,
                                        alt: "",
                                    })!,
                                ),
                            );
                            view.focus();
                        }

                        modal.close();
                    });
                }
                return true;
            },
            dom: createButton("image", app.config.EditorInput.labels.image),
            group: "media",
        },
        {
            mark: schema.marks.link,
            command: (state, dispatch, view) => {
                if (!dispatch) {
                    return true;
                }

                if (!view) {
                    return false;
                }

                let currentUri: string | undefined;

                const range = getMarkRange(state, schema.marks.link);

                if (range) {
                    const nodeAfter = state.selection.$from.nodeAfter;
                    const linkMark = nodeAfter?.marks.find((mark) => mark.type === schema.marks.link);
                    currentUri = linkMark?.attrs.href;
                }

                const { linkModal } = app.modals;

                linkModal.onOpen((modal) => {
                    const uriInput = $('[id="linkModal.uri"]', modal.element) as HTMLInputElement;

                    if (currentUri) {
                        uriInput.value = currentUri;
                    } else {
                        uriInput.value = "https://";
                        uriInput.setSelectionRange(8, 8);
                    }
                });

                linkModal.open();

                const { from, to } = state.selection;
                const inRange = range && from >= range.from && to <= range.to;

                linkModal.onCommand("insert-link", (modal) => {
                    const uriInput = $('[id="linkModal.uri"]', modal.element) as HTMLInputElement;

                    if (uriInput.value) {
                        const linkMark = schema.marks.link.create({ href: uriInput.value });

                        if (!inRange) {
                            dispatch(view.state.tr.addStoredMark(linkMark));
                            dispatch(view.state.tr.replaceSelectionWith(schema.text(uriInput.value), true));
                        } else {
                            view.dispatch(view.state.tr.removeMark(range.from, range.to, schema.marks.link).addMark(range.from, range.to, linkMark));
                        }
                    } else if (inRange) {
                        view.dispatch(view.state.tr.removeMark(range.from, range.to, schema.marks.link));
                    }
                    modal.close();
                });

                linkModal.onCommand("remove-link", (modal) => {
                    if (inRange) {
                        view.dispatch(view.state.tr.removeMark(range.from, range.to, schema.marks.link));
                    }
                    modal.close();
                });

                linkModal.onClose(() => view.focus());

                return true;
            },
            dom: createButton("link", app.config.EditorInput.labels.link),
            group: "media",
        },
        {
            command: (state, dispatch, view) => {
                if (!dispatch) {
                    return undoDepth(state) > 0;
                }
                return undo(state, dispatch, view);
            },
            dom: createButton("rotate-left", app.config.EditorInput.labels.undo),
        },
        {
            command: (state, dispatch, view) => {
                if (!dispatch) {
                    return redoDepth(state) > 0;
                }
                return redo(state, dispatch, view);
            },
            dom: createButton("rotate-right", app.config.EditorInput.labels.redo),
        },
    ]);
}

function canInsert(state: EditorState, nodeType: NodeType) {
    const $from = state.selection.$from;
    for (let d = $from.depth; d >= 0; d--) {
        const index = $from.index(d);
        if ($from.node(d).canReplaceWith(index, index, nodeType)) return true;
    }
    return false;
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

function isMarkActive(state: EditorState, type: MarkType) {
    const { from, $from, to, empty } = state.selection;
    if (empty) {
        return !!type.isInSet(state.storedMarks || $from.marks());
    }
    return state.doc.rangeHasMark(from, to, type);
}

let modalsInitialized = false;

function initModals() {
    if (modalsInitialized) {
        return;
    }

    const { linkModal } = app.modals;

    $('[id="linkModal.uri"]', linkModal.element)?.addEventListener("keydown", (event) => {
        if (event.key === "Enter") {
            linkModal.triggerCommand("insert-link");
            event.preventDefault();
        }
    });

    modalsInitialized = true;
}
