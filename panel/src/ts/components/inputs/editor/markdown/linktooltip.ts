import { debounce } from "../../../../utils/events";
import { EditorView } from "prosemirror-view";
import { getMarkRange } from "./utils";
import { Mark } from "prosemirror-model";
import { passIcon } from "../../../icons";
import { Plugin } from "prosemirror-state";
import { schema } from "prosemirror-markdown";
import { Tooltip } from "../../../tooltip";

function addBaseUri(text: string, baseUri: string) {
    return text.replace(/^\/?(?!https?:\/\/)(.+)/, `${baseUri}$1`);
}
class LinkTooltipView {
    editorView: EditorView;
    tooltip?: Tooltip;
    currentLink?: Mark;
    baseUri: string;

    constructor(view: EditorView, baseUri: string) {
        this.editorView = view;

        this.baseUri = baseUri;

        this.editorView.dom.addEventListener(
            "scroll",
            debounce(() => this.destroy(), 100, true),
        );
        this.editorView.dom.addEventListener("blur", () => this.destroy());
    }

    update(view: EditorView) {
        const state = view.state;

        const { from, to } = state.selection;

        const range = getMarkRange(state, schema.marks.link);
        if (!(range && from >= range.from && to <= range.to)) {
            this.destroy();
            return;
        }

        const link = range.mark;

        if (link) {
            if (this.tooltip) {
                this.tooltip.remove();
            }

            const domAtPos = view.domAtPos(range.from + 1);

            let linkDom: HTMLElement | null = domAtPos.node as HTMLElement;

            while (linkDom && linkDom.tagName !== "A") {
                linkDom = linkDom.parentElement;
            }

            if (!linkDom) {
                return;
            }

            passIcon("link", (icon) => {
                this.tooltip = new Tooltip(
                    `<div class="flex">
                        <div>${icon}</div>
                        <div class="truncate ml-2"><a href="${addBaseUri(link.attrs.href, this.baseUri)}" target="_blank">${link.attrs.href}</a></div>
                    </div>`,
                    {
                        referenceElement: linkDom,
                        removeOnMouseout: false,
                        delay: 0,
                        zIndex: 7,
                    },
                );

                this.tooltip.show();
            });
        }
    }

    destroy() {
        if (this.tooltip) {
            const tooltip = this.tooltip;
            window.setTimeout(() => tooltip.remove(), 100);
        }
        this.tooltip = undefined;
    }
}

export function linkTooltip(baseUri: string): Plugin {
    return new Plugin({
        view(editorView: EditorView) {
            return new LinkTooltipView(editorView, baseUri);
        },
    });
}
